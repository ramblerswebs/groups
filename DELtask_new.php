<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);
ini_set('default_socket_timeout', 120);

define("VERSION_NUMBER", "0.0.2");
define("GROUPFILE", "cache/allgroups.json");
define("NOTIFY", "feeds@ramblers-webs.org.uk");
define("TASK", "https://groups.theramblers.org.uk/task.php");
define("RAMBLERSWEBSSITES", "https://sites.ramblers-webs.org.uk/feed.php");
//define("WALKMANAGER", "https://uat-be.ramblers.nomensa.xyz/api/volunteers/walksevents?types=group-walk");
define("GROUPSFEED","https://uat-be.ramblers.nomensa.xyz/api/volunteers/groups?groups=");
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
echo "Task started".BR;
require('classes/autoload.php');
spl_autoload_register('autoload');

$api= new RaApi();
$groups=$api->getGroupsFeed();

foreach ($groups as $group) {
    $group->groupCode = $group->group_code;
    // add in old name
}

$rw_properties = array("code", "name", "area", "website", "status");
$sites = Functions::getJsonFeed(RAMBLERSWEBSSITES, $rw_properties);

foreach ($groups as $group) {
    $code = $group->groupCode;
    $site = Functions::findSite($sites, $code);
    if ($site != null) {
        $group->areaname = $site->area;
        $group->website = $site->website;
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

function getGroups(){
    

$api_instance = new Swagger\Client\ApiDefaultApi();
$groups = []; // array[String] | 

try {
    $result = $api_instance->apiVolunteersGroupsGet($groups);
    print_r($result);
} catch (Exception $e) {
    echo 'Exception when calling DefaultApi->apiVolunteersGroupsGet: ', $e->getMessage(), PHP_EOL;
}

}