<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of raAPI
 *
 * @author chris
 */
class raApi {

    const ACCESS_TOKEN = '    ';
    private $GroupsProperties=["scope", "group_code","area_code","groups_in_area",
        "name", "url","external_url", "description", "latitude", "longitude","date_updated","date_walks_events_updated"];


    
    public function __construct() {
        require_once(__DIR__ . '/vendor/autoload.php');
        // Configure OAuth2 access token for authorization: oauth2
        Swagger\Client\Configuration::getDefaultConfiguration()->setAccessToken(ACCESS_TOKEN);
    }

    public function getGroupsFeed() {
        $api_instance = new Swagger\Client\ApiDefaultApi();
        $groups = []; // array[String] | 

        try {
            $result = $api_instance->apiVolunteersGroupsGet($groups);
            print_r($result);
             if (self::checkJsonFileProperties($items, $this->properties) > 0) {
                functions::errorEmail("Ramblers groups feed", "Expected properties not found in JSON feed");
                die();
            }
        } catch (Exception $e) {
            echo 'Exception when calling DefaultApi->apiVolunteersGroupsGet: ', $e->getMessage(), PHP_EOL;
        }
    }
      public static function checkJsonProperties($item, $properties) {
        foreach ($properties as $value) {
            if (!self::checkJsonProperty($item, $value)) {
                return 1;
            }
        }

        return 0;
    }

    private static function checkJsonProperty($item, $property) {
        if (property_exists($item, $property)) {
            return true;
        }
        return false;
    }


}
