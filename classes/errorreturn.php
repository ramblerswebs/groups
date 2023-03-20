<?php

class Errorreturn {

    public $error = true;
    public $code = 500;
    public $reason = '';

    function __construct($message, $code) {
        $this->reason = $message;
        $this->code = $code;
    }
}