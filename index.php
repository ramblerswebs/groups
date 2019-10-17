<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);

// Code to 
// http://www.ramblers.org.uk/api/lbs/groups/
define("VERSION_NUMBER", "0.0.0");
define("GROUPFILE", "cache/allgroups.json");

// 	First Release
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    echo 'You MUST be running on PHP version 7.0.0 or higher, running version: ' . \PHP_VERSION . "\n";
    die();
}
// set current directory to current run directory
$exepath = dirname(__FILE__);
define('BASE_PATH', dirname(realpath(dirname(__FILE__))));
chdir($exepath);
$key = NULL;

require('classes/autoload.php');
spl_autoload_register('autoload');

$opts = new Options();
$groups = new GroupsFile();
If ($opts->noGets() === 0) {
    $allGroups = $groups->allGroups();
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode($allGroups);
}

$latitude = $opts->gets("latitude");
$longitude = $opts->gets("longitude");
$distance = $opts->gets("dist");
$maxpoints = $opts->gets("maxpoints");
$exit = false;
if ($latitude === null) {
    $exit = true;
}
if ($longitude === null) {
    $exit = true;
}
if ($distance === null) {
    $exit = true;
}
if ($maxpoints === null) {
    $exit = true;
}
If ($exit) {
    return "['Invalid call']";
}

$closestGroups = $groups->process($latitude, $longitude, $distance, $maxpoints);
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
echo json_encode($closestGroups);

return;
