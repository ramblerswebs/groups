<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of file
 *
 * @author Chris Vaughan
 */
class GroupsFile {

    private $groups = [];

    public function __construct() {
        $json = file_get_contents(GROUPFILE);
        $this->groups = json_decode($json);
    }

    public function process($latitude, $longitude, $distance, $maxpoints) {
        foreach ($this->groups as $group) {
            $lat = $group->latitude;
            $lon = $group->longitude;

            $dist = GeometryGreatcircle::distance($latitude, $longitude, $lat, $lon, GeometryGreatcircle::KM);
            $group->distance = $dist;
        }
        usort($this->groups, "GroupsFile::cmpDistance");
        foreach ($this->groups as $key => $group) {
            if ($group->distance > $distance) {
                unset($this->groups[$key]);
            }
        }
        $no = 0;
        foreach ($this->groups as $key => $group) {
            $no += 1;
            if ($no > $maxpoints) {
                unset($this->groups[$key]);
            }
        }
        return $this->groups;
    }

    public function search($search, $number) {
        $find = strtolower($search);
        foreach ($this->groups as $key => $group) {
            $found = false;
            if (strpos(strtolower($group->name), $find) !== false) {
                $found = true;
            }
            if (!$found) {
                unset($this->groups[$key]);
            }
        }
        $no = 0;
        foreach ($this->groups as $key => $group) {
            $no += 1;
            if ($no > $number) {
                unset($this->groups[$key]);
            }
        }
        return $this->groups;
    }

    private static function cmpDistance($a, $b) {
        return $a->distance > $b->distance;
    }

    public function allGroups() {
        return $this->groups;
    }

}
