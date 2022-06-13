<?php

//
// produces data in V1 format but from the new WM API rather than the GWEM Feed
//

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);
ini_set('default_socket_timeout', 120);

define("VERSION_NUMBER", "0.0.2");
define("GROUPFILE", "../cache/v1/allgroups.json");
define("APIRESPONSE", "../cache/v1/apiResponse.json");
define("NOTIFY", "feeds@ramblers-webs.org.uk");
define("TASK", "https://groups.theramblers.org.uk/v1/taskV1.php");
define("GROUPSFEED", "https://uat-be.ramblers.nomensa.xyz/api/volunteers/groups?groups");
define("APIKEY", "9a68085e6f159f6b2ecd0b7533805282");
define("RAMBLERSWEBSSITES", "https://sites.ramblers-webs.org.uk/feed.php");
define("BR", "<br>");

// 	First Release
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    echo 'You MUST be running on PHP version 7.0.0 or higher, running version: ' . \PHP_VERSION . BR;
    die();
}
// set current directory to current run directory
$exepath = dirname(__FILE__);
define('BASE_PATH', dirname(realpath(dirname(__FILE__))));
chdir($exepath);

$key = NULL;
echo "Task started" . BR;
require('../classes/autoload.php');
spl_autoload_register('autoload');

$properties = array("scope", "group_code", "area_code", "groups_in_area", "name", "url", "external_url", "description", "latitude", "longitude", "date_updated", "date_walks_events_updated");
$feed = GROUPSFEED . "&api-kep=" . APIKEY;
$groups = Functions::getJsonFeed(GROUPSFEED, $properties);
$json = json_encode($groups, JSON_PRETTY_PRINT);
file_put_contents(APIRESPONSE, $json);

$properties2 = array("code", "name", "area", "website", "status");
$sites = Functions::getJsonFeed(RAMBLERSWEBSSITES, $properties2);
usort($groups, function ($a, $b) {
    return strcmp($a->group_code, $b->group_code);
});
echo "<ul>";
foreach ($groups as $key => $group) {


    // Check for validity of data

    if ($group->scope != "A" and $group->scope != "G") {
        echo Functions::addListItem("ERROR: Invalid Group Scope : " . $group->group_code);
    }

    if (!(is_numeric($group->latitude) AND is_numeric($group->longitude))) {
        echo Functions::addListItem("ERROR: Invalid Latitude or longitude : " . $group->group_code);
    }
    if (strlen($group->external_url) > 0) {
        if (substr($group->external_url, 0, 4) != "http") {
            echo Functions::addListItem("ERROR: Invalide External url : " . $group->group_code);
        }
    }

    // rename fields

    $group->areaCode = $group->area_code;
    unset($group->area_code);
    $group->groupCode = $group->group_code;
    unset($group->group_code);

    unset($group->groups_in_area);
    unset($group->date_walks_events_updated);
    $group->groupURL = $group->external_url;
    unset($group->external_url);

    $site = Functions::findSite($sites, $group->groupCode);
    if ($site != null) {
        $group->areaname = $site->area;
        $group->website = $site->website;
        $group->status = $site->status;
    }
    if ($group->type == "rww-group-hub") {
        unset($groups[$key]);
    }else{
        unset($group->type);
    }
}
echo "</ul>";

$json = json_encode($groups, JSON_PRETTY_PRINT);
$written = file_put_contents(GROUPFILE, $json);
if ($written === false) {
    Functions::errorEmail(TASK, "Unable to write " . GROUPFILE);
    die();
}
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

$headers = ["Code", "Name", "Description"];
echo "<h2>List of Groups</h2>";
echo "<table>";
echo Functions::addTableHeader($headers);
foreach ($groups as $group) {
    $fields = [];
    $fields[] = $group->groupCode;
    $fields[] = $group->name;
    $fields[] = $group->description;

    echo Functions::addTableRow($fields);
}

echo "</table>";
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
echo "Task completed" . BR;

