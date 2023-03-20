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
    private $version = 1;

    public function __construct($version, $types) {
        $this->version = $version;
        switch ($version) {
            case V2:
                $json = file_get_contents(GROUPFILEV2);
                $groups = json_decode($json);
                foreach ($groups as $key => $value) {
                    $this->groups [$key] = $value;
                }
                break;
            case V1:
            default:
                $json = file_get_contents(GROUPFILEV1);
                $this->groups = json_decode($json);
                break;
        }
        foreach ($this->groups as $key => $group) {
            $found = false;
            if (str_contains($types, $group->scope)) {
                $found = true;
            }
            if (!$found) {
                unset($this->groups[$key]);
            }
        }
    }

    public function process($latitude, $longitude, $distance, $maxpoints) {
        foreach ($this->groups as $group) {
            $lat = $group->latitude;
            $lon = $group->longitude;
            $dist = GeometryGreatcircle::distance($latitude, $longitude, $lat, $lon, GeometryGreatcircle::KM);
            $group->distance = $dist;
        }
        switch ($this->version) {
            case V1:
                usort($this->groups, "GroupsFile::cmpDistance");
            case V2:
                uasort($this->groups, "GroupsFile::cmpDistance");
        }


        // remove items ftoo far away
        foreach ($this->groups as $key => $group) {
            if ($group->distance > $distance) {
                unset($this->groups[$key]);
            }
        }
        // remove items after the limit of items(maxpoints) has been reached
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
            //         if (strpos(strtolower($group->name), $find) !== false) {
            if (strpos(strtolower($group->name), $find)) {
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

    public function singleGroup($code) {

        foreach ($this->groups as $key => $group) {
            $found = false;
            if (strtolower($group->groupCode) === strtolower($code)) {
                $found = true;
            }
            if (!$found) {
                unset($this->groups[$key]);
            }
        }
        return $this->groups;
    }

    public function areaGroups($code) {

        foreach ($this->groups as $key => $group) {
            $found = false;
            if (str_starts_with(strtolower($group->groupCode), strtolower($code))) {
                $found = true;
            }
            if (!$found) {
                unset($this->groups[$key]);
            }
        }
        return $this->groups;
    }

    private static function cmpDistance($a, $b) {
        $dist = $a->distance - $b->distance;
        if ($dist == 0) {
            return 0;
        }
        if ($dist > 0) {
            return 1;
        }
        return -1;
    }

    public function allGroups() {
        return $this->groups;
    }

}
