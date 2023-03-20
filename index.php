<?php

//
// produces data in V1 format but from the old GWEM Feed
//
// https://groups.theramblers.org.uk/?v=1&latitude=51.4589653&longitude=-2.52582669&maxpoints=100&dist=30

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('assert.warning', 1);

// Code to 
// http://www.ramblers.org.uk/api/lbs/groups/
define("VERSION_NUMBER", "0.0.1");
define("GROUPFILEV1", "cache/allgroupsv1.json");
define("GROUPFILEV2", "cache/allgroupsv2.json");
define("V1", "1");
define("V2", "2");

// 	First Release
if (version_compare(PHP_VERSION, '8.0.0') < 0) {
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode(new Errorreturn("You MUST be running on PHP version 8.0.0 or higher, running version", 500), JSON_PRETTY_PRINT);
    return;
}
// set current directory to current run directory
$exepath = dirname(__FILE__);
//echo $exepath;
//define('BASE_PATH', dirname(realpath(dirname(__FILE__))));
define('BASE_PATH', $exepath);
chdir($exepath);
//echo BASE_PATH;
$key = NULL;

require('classes/autoload.php');
spl_autoload_register('autoload');

$opts = new Options();
$no = $opts->noGets();
$version = $opts->gets("v");
$versionSpecified = $version !== null;
if ($versionSpecified) {
    $no -= 1;
} else {
    $version = V1;
}
if ($version !== V1 && $version !== V2) {
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode(new Errorreturn("Invalid calling sequence", 501), JSON_PRETTY_PRINT);
    return;
}
// which group types to return
$types = $opts->gets("types");
if ($types === null) {
    $types = "A,G";
} else {
    $types = strtoupper($types);
    $no -= 1;
}

$groups = new GroupsFile($version, $types);

// return all groups

If ($no === 0) {
    // return all groups
    $allGroups = $groups->allGroups();
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode($allGroups, JSON_PRETTY_PRINT);
    return;
}
// return groups by name search
$search = $opts->gets("search");
if ($search != null) {
    // do a name search
    $number = $opts->gets("number");
    if ($number == null) {
        $number = 20;
    }
    $groups = $groups->search($search, $number);
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode($groups, JSON_PRETTY_PRINT);
    return;
}
// return group by group code 
$group = $opts->gets("group");
if ($group != null) {
    // return single record for one group

    $groups = $groups->singleGroup($group);
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode($groups, JSON_PRETTY_PRINT);
    return;
}

// return groups by area code 
$area = $opts->gets("area");
if ($area != null) {
    // return single record for one group

    $groups = $groups->areaGroups($area);
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode($groups, JSON_PRETTY_PRINT);
    return;
}

// do a location search
$latitude = $opts->gets("latitude");
$longitude = $opts->gets("longitude");
$distance = $opts->gets("dist");
$maxpoints = $opts->gets("maxpoints");

$code = 0;
if ($latitude === null) {
    $code = 502;
}
if ($longitude === null) {
    $code = 503;
}
if ($distance === null) {
    $code = 504;
}
if ($maxpoints === null) {
    $code = 505;
}

if ($code > 0) {
    header("Access-Control-Allow-Origin: *");
    header("Content-type: application/json");
    echo json_encode(new Errorreturn("Invalid calling sequence", $code), JSON_PRETTY_PRINT);
    return;
}

$closestGroups = $groups->process($latitude, $longitude, $distance, $maxpoints);
header("Access-Control-Allow-Origin: *");
header("Content-type: application/json");
echo json_encode($closestGroups, JSON_PRETTY_PRINT);
return;
