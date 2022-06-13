<?php

//
// produces data in V1 format from the GWEM Feed
//

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);
ini_set('default_socket_timeout', 120);

define("VERSION_NUMBER", "0.0.2");
define("GROUPFILE", "cache/allgroups.json");
define("NOTIFY", "feeds@ramblers-webs.org.uk");
define("TASK", "https://groups.theramblers.org.uk/task.php");
define("GROUPSFEED", "https://www.ramblers.org.uk/api/lbs/groups/");
define("RAMBLERSWEBSSITES", "https://ramblers-webs.org.uk/index.php?option=com_rw_accounts&task=domains.controller&format=json");
define("BR", "<br>");

// 	First Release
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    echo 'You MUST be running on PHP version 7.0.0 or higher, running version: ' . \PHP_VERSION . BR;
    die();
}
// set current directory to current run directory
$exepath = dirname(__FILE__);
echo $exepath;
//define('BASE_PATH', dirname(realpath(dirname(__FILE__))));
define('BASE_PATH', $exepath);
chdir($exepath);
echo BASE_PATH;
$key = NULL;
echo "Task started".BR;
require('classes/autoload.php');
spl_autoload_register('autoload');

$properties = array("scope", "groupCode", "name", "url", "description", "latitude", "longitude");
$groups = Functions::getJsonFeed(GROUPSFEED, $properties);

$properties2 = array("code", "name", "area", "website", "status");
$sites = Functions::getJsonFeed(RAMBLERSWEBSSITES, $properties2);

foreach ($groups as $group) {
    $code = $group->groupCode;
    $site = Functions::findSite($sites, $code);
    if ($site != null) {
        $group->areaname = $site->areaname;
        $group->website = $site->domain;
        $group->status = $site->status;
    }
}
$json = json_encode($groups);
$written = file_put_contents(GROUPFILE, $json);
if ($written === false) {
    Functions::errorEmail(TASK, "Unable to write " . GROUPFILE);
    die();
}
echo "Task completed".BR;