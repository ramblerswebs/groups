<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);
ini_set('default_socket_timeout', 120);

define("VERSION_NUMBER", "0.0.2");
define("GROUPFILE", "cache/allgroups.json");
define("NOTIFY", "feeds@ramblers-webs.org.uk");
define("TASK", "https://groups.theramblers.org.uk/task.php");
define("GROUPSFEED", "https://www.ramblers.org.uk/api/lbs/groups/");
define("RAMBLERSWEBSSITES", "https://sites.ramblers-webs.org.uk/feed.php");
define("BR","<br>");

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

require('classes/autoload.php');
spl_autoload_register('autoload');

$json = file_get_contents(GROUPSFEED);
if ($json === false) {
    errorEmail(GROUPSFEED, "Unable to read feed: file_get_contents failed");
    die();
} else {
    if (!functions::startsWith("$json", "[{")) {
        errorEmail(GROUPSFEED, "JSON code does not start with [{");
        die();
    }
}
echo "RA Group Feed read".BR;
$groups = json_decode($json);
$properties = array("scope", "groupCode", "name", "url", "description", "latitude", "longitude");
if (functions::checkJsonFileProperties($groups, $properties) > 0) {
    errorEmail(GROUPSFEED, "Expected properties not found in JSON code");
    die();
}

$json = file_get_contents(RAMBLERSWEBSSITES);
if ($json === false) {
    errorEmail(RAMBLERSWEBSSITES, "Unable to read feed: file_get_contents failed");
    die();
}
echo "Ramblers webs site data read".BR;
$sites = json_decode($json);
foreach ($groups as $group) {
    $code = $group->groupCode;
    $site = findSite($sites, $code);
    if ($site != null) {
        $group->areaname = $site->area;
        $group->website = $site->website;
        $group->status = $site->status;
    }
}
$json = json_encode($groups);
$written = file_put_contents(GROUPFILE, $json);
if ($written === false) {
    errorEmail(TASK,"Unable to write " . GROUPFILE);
    die();
}
echo "Task completed";

// write GROUPFILE
function findSite($sites, $code) {
    foreach ($sites as $site) {
        if ($site->code == $code) {
            return $site;
        }
    }
    return null;
}

//function startsWith($string, $startString) {
//    $len = strlen($startString);
//    return (substr($string, 0, $len) === $startString);
//}

function errorEmail($feed, $error) {
    require 'classes/phpmailer/src/PHPMailer.php';
    require 'classes/phpmailer/src/SMTP.php';
    require 'classes/phpmailer/src/Exception.php';
    date_default_timezone_set('Europe/London');
    $domain = "theramblers.org.uk";
    // Create a new PHPMailer instance
    $mailer = new PHPMailer\PHPMailer\PHPMailer;

    $mailer->setFrom("admin@" . $domain, $domain);
    $mailer->addAddress(NOTIFY, 'Web Master');
    $mailer->isHTML(true);
    $mailer->Subject = "Ramblers Feed Error";
    $mailer->Body = "<p>Feed error found while running: " . TASK . "</p><p>"
            . $error . "</p>";
    $mailer->send();
    echo "Error message sent".BR;
    echo "Task: ".TASK.BR;
    echo "Feed: ".$feed.BR;
    echo "Error: ".$error.BR;
}
