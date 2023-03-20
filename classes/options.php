<?php

/**
 * Class to find out command line, get and put options
 *
 * @author Chris Vaughan
 */
class Options {

    private $gets = array();
    private $posts = array();
    private static $thisclass;

    function __construct() {
        self::$thisclass = $this;

        foreach ($_GET as $key => $value) {
            if ($value !== 'netbeans-xdebug') {
                $this->gets[$key] = htmlspecialchars($value);
            }
        }
        foreach ($_POST as $key => $value) {
            $this->posts[$key] = htmlspecialchars($value);
        }
    }

    public function getOptions() {
        return self::$thisclass;
    }

    public function gets($name) {
        if (isset($this->gets[$name])) {
            return $this->gets[$name];
        } else {
            return null;
        }
    }

    public function posts($name) {
        if (isset($this->posts[$name])) {
            return $this->posts[$name];
        } else {
            return null;
        }
    }

    public function noGets() {
        return count($this->gets);
    }

}
