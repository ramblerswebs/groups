<?php

//
// produces data into both V1 and V2 formats from the new WM API rather than the GWEM Feed
//

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);
ini_set('default_socket_timeout', 120);

define("VERSION_NUMBER", "0.0.3");
define("GROUPFILEV1", "cache/allgroupsv1.json");
define("GROUPFILEV2", "cache/allgroupsv2.json");
define("APIRESPONSE", "cache/apiResponse.json");
define("NOTIFYEMAILADDRESS", "feeds@ramblers-webs.org.uk");
define("TASK", "https://groups.theramblers.org.uk/task.php");
//define("GROUPSFEED", "https://uat-be.ramblers.nomensa.xyz/api/volunteers/groups?");
define("GROUPSFEED", "https://walks-manager.ramblers.org.uk/api/volunteers/groups?");
define("APIKEY", "9a68085e6f159f6b2ecd0b7533805282");
define("RAMBLERSWEBSSITES", "https://ramblers-webs.org.uk/index.php?option=com_rw_accounts&task=domains.controller&format=json");
define("BR", "<br>");

// 	First Release
if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    echo 'You MUST be running on PHP version 8.0.0 or higher, running version: ' . \PHP_VERSION . BR;
    die();
}
// set current directory to current run directory
$exepath = dirname(__FILE__);
define('BASE_PATH', dirname(realpath(dirname(__FILE__))));
chdir($exepath);

$key = NULL;

require('classes/autoload.php');
spl_autoload_register('autoload');

require_once 'vendor/autoload.php';
require 'classes/phpmailer/src/PHPMailer.php';
require 'classes/phpmailer/src/SMTP.php';
require 'classes/phpmailer/src/Exception.php';
date_default_timezone_set('Europe/London');
Logfile::create("logfiles/logfile");

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

Logfile::writeWhen("Task started");
$feed = GROUPSFEED . "&api-key=" . APIKEY;
$groups = Functions::getJsonFeed($feed);

// save feed response
file_put_contents(APIRESPONSE, json_encode($groups, JSON_PRETTY_PRINT));
$schema = json_decode(file_get_contents('schemas/groupsGenerated.json'));

// validate the feed against the schema
fixFeed($groups);
$validator = new Validator;
$validator->validate(
        $groups,
        $schema,
        Constraint::CHECK_MODE_COERCE_TYPES
);
$errorFound = false;
if ($validator->isValid()) {
    Logfile::writeWhen("---- JSON validates OK");
} else {
    Logfile::writeError("JSON validation errors:");
    $errorFound = true;
    $errors = $validator->getErrors();
    foreach ($errors as $error) {
        $items = explode('/', $error["pointer"]);
        $no = intval($items[1]);
        $group = $groups[$no];
        Logfile::writeError($group->group_code . ": " . $error["property"] . " - " . $error["message"]);
    }
    // email administrator
    // die();
}

$sites = Functions::getJsonFeed(RAMBLERSWEBSSITES);
usort($groups, function ($a, $b) {
    return strcmp($a->group_code, $b->group_code);
});
// more data checks
Logfile::writeWhen("Checking data quality");

$lastCode = null;
$removedGroups = [];
foreach ($groups as $key => $group) {
    $error = false;
    // Check for validity of data
    if ($group->group_code === "") {
        Logfile::writeError("ERROR: Invalid blank Group Code, Group name: " . $group->name);
        $error = true;
    }
    if ($group->group_code === $lastCode) {
        Logfile::writeError("ERROR: More than one group has Group code: " . $group->group_code);
        $error = true;
    }
    $lastCode = $group->group_code;
    switch ($group->scope) {
        case "A":
        case "G":
        case "W":
            break;
        default:
            Logfile::writeError("ERROR: Invalid Group Scope : " . $group->group_code);
            $error = true;
    }

    if ($group->latitude === null OR $group->longitude === null) {
        Logfile::writeError("ERROR: Latitude or longitude is NULL : " . $group->name);
        $error = true;
    } else {
        if ($group->latitude === 0 OR $group->longitude === 0) {
            Logfile::writeError("ERROR: Latitude or longitude is zero : " . $group->name);
            $error = true;
        }
    }
    if ($group->external_url !== null) {
        $okay = false;
        if (Functions::startsWith($group->external_url, 'https://')) {
            $okay = true;
        }
        if (Functions::startsWith($group->external_url, 'http://')) {
            $okay = true;
        }
        if (!$okay) {
            Logfile::writeError("ERROR: Invalid External url for Group code: " . $group->group_code . "<ul><li>URL: " . $group->external_url . "</li></ul>");
            $error = true;
        }
    }
    if ($error) {
        $errorFound = true;
        array_push($removedGroups, $group);
        unset($groups[$key]);
    }
}
if ($errorFound) {
    $msg = "ERRORS found in CORE data from CENTRAL OFFICE feed.";
    Functions::errorEmail(GROUPSFEED, $msg);
    echo "<p>" . $msg . "</p>";
    $msg = "Groups removed from RW group feed";
    Logfile::writeWhen($msg);
    echo "<p>" . $msg . "</p>";
    echo "<ol>";
    foreach ($removedGroups as $key => $value) {
        $msg = "        Name: " . $value->name . ", code: " . $value->group_code;
        Logfile::writeWhen($msg);
        echo "<li>" . $msg . "</li>";
    }
    echo "</ol>";
}

Logfile::writeWhen("Cross referencing Central Office and Ramblers-webs data");

foreach ($groups as $key => $group) {

    $site = Functions::findSite($sites, $group->group_code);
    if ($site != null) {
        if ($site->status === "Hosted" or $site->status === "HostedDNSSet") {
            if (!Functions::contains($site->domain, $group->external_url)) {
                Logfile::writeError("WARNING: Group web site URL/address does not match. Central Office and Ramblers-Webs.org.uk have different URLs for the group's website. Group Code: " . $group->group_code . ", Group Name: " . $group->name .", Group web page: ".$group->url. ", Central Office URL for group website: " . $group->external_url . ", Ramblers-Webs URL for group website: https://" . $site->domain);
                echo "WARNING: Group web site URL/address does not match. Central Office and Ramblers-Webs.org.uk have different URLs for the group's web site.<ul><li>Group Code: " . $group->group_code . "</li><li> Group Name: " . $group->name . "</li><li>Group web page: ".$group->url."</li><li>Central Office URL for group website: " . $group->external_url . "</li><li>Ramblers-Webs URL for group website: https://" . $site->domain . "</li></ul>";
            }
        }
    }
}

$headers = ["Code", "Name", "Description"];
echo "<h2>List of Groups</h2>";
echo "<table>";
echo Functions::addTableHeader($headers);
foreach ($groups as $group) {
    $fields = [];
    $fields[] = $group->group_code;
    $fields[] = $group->name;
    $fields[] = $group->description;

    echo Functions::addTableRow($fields);
}

echo "</table>";
writeV1objects($groups, $sites);
writeV2objects($groups, $sites);

echo "Task completed" . BR;

function writeV1objects($groups, $sites) {
    $newGroups = [];
    foreach ($groups as $key => $group) {
        //    if ($group->type !== "rww-group-hub") {
        $newGroup = new v1group();
        $newGroup->scope = $group->scope;
        $newGroup->groupCode = $group->group_code;
        $newGroup->name = $group->name;
        $newGroup->url = $group->url;
        $newGroup->description = $group->description;
        $newGroup->latitude = $group->latitude;
        $newGroup->longitude = $group->longitude;
        $newGroup->areaname = "";
        $newGroup->website = "";
        $newGroup->status = "";
        $site = Functions::findSite($sites, $group->group_code);
        if ($site !== null) {
            $newGroup->areaname = $site->areaname;
            $newGroup->website = $site->domain;
            $newGroup->status = $site->status;
        }
        $newGroups[] = $newGroup;
        //  }
    }
    $json = json_encode($newGroups, JSON_PRETTY_PRINT);
    $written = file_put_contents(GROUPFILEV1, $json);
    if ($written === false) {
        Functions::errorEmail(TASK, "Unable to write " . GROUPFILEV1);
        die();
    }
    echo "<p>Version 1 groups written</p>";
}

function writeV2objects($groups, $sites) {
    $newGroups = [];
    $hosted = 0;
    foreach ($groups as $group) {
        //  if ($group->type !== "rww-group-hub") {
        $code = $group->group_code;
        $newGroup = new v2group();
        $newGroup->scope = $group->scope;
        $newGroup->groupCode = $group->group_code;
        $newGroup->name = $group->name;
        $newGroup->url = $group->url;
        $newGroup->groupUrl = $group->external_url;
        $newGroup->description = $group->description;
        $newGroup->latitude = $group->latitude;
        $newGroup->longitude = $group->longitude;
        $newGroup->areaCode = $group->area_code;
        if ($newGroup->scope == "A") {
            switch ($group->groups_in_area) {
                case NULL:
                case "":
                    $newGroup->groupsInArea = [];
                    break;
                default:
                    $newGroup->groupsInArea = $group->groups_in_area;
            }
        }
        $newGroup->areaName = "";
        $newGroup->dateUpdated = $group->date_updated;
        $newGroup->rwStatus = "";

        $site = Functions::findSite($sites, $group->group_code);
        if ($site !== null) {
            $newGroup->rwStatus = $site->status;
        }
        $newGroups[$code] = $newGroup;
    }
    //  }
    // add in Area Name to each group
    foreach ($newGroups as $newGroup) {
        $areaCode = $newGroup->areaCode;
        if ($areaCode !== "") {
            $newGroup->areaName = $newGroups[$areaCode]->name;
        }
    }

    $json = json_encode($newGroups, JSON_PRETTY_PRINT);
    $written = file_put_contents(GROUPFILEV2, $json);
    if ($written === false) {
        Functions::errorEmail(TASK, "Unable to write " . GROUPFILEV2);
        die();
    }
    echo "<p>Version 2 groups written</p>";
}

function writeCSV($groups) {

// diagnostics


    $fp = fopen('file.csv', 'w');
    foreach ($groups as $group) {
        $fields = [];
        $fields[] = $group->groupCode;
        $fields[] = $group->name;
        $fields[] = $group->url;
        $fields[] = $group->groupURL;

        if (property_exists($group, "website")) {
            $fields[] = $group->website;
            $fields[] = $group->status;
            $fields[] = $group->areaname;
        } else {
            $fields[] = "";
            $fields[] = "";
            $fields[] = "";
        }
        fputcsv($fp, $fields);
    }

    fclose($fp);
    echo "CSF file 'file.csv' created" . BR;
}

function writeTable($groups) {
    $headers = ["Code", "Name", "RA Page", "Group web site", "Domain", "Status", "Area name"];
    echo "<h2></h2>";
    echo "<table>";
    echo Functions::addTableHeader($headers);
    foreach ($groups as $group) {
        $fields = [];
        $fields[] = $group->groupCode;
        $fields[] = $group->name;
        $fields[] = $group->url;
        $fields[] = $group->groupURL;
        if (property_exists($group, "website")) {
            $fields[] = $group->website;
            $fields[] = $group->status;
            $fields[] = $group->areaname;
        } else {
            $fields[] = "";
            $fields[] = "";
            $fields[] = "";
        }
        echo Functions::addTableRow($fields);
    }
    echo "</table>";
}

function fixFeed($groups) {
    foreach ($groups as $group) {
        $group->date_updated = $group->date_updated . 'Z';
        $group->date_walks_events_updated = $group->date_walks_events_updated . 'Z';
    }
}

class v1group {

    public $scope;
    public $groupCode;
    public $name;
    public $url;
    public $description;
    public $latitude;
    public $longitude;
    public $areaname;
    public $website;
    public $status;

}

class v2group {

    public $scope;
    public $groupCode;
    public $name;
    public $url;
    public $groupUrl;
    public $description;
    public $latitude;
    public $longitude;
    public $areaCode;
    public $groupsInArea;
    public $areaName;
    public $dateUpdated;
    public $rwStatus;

}
