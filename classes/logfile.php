<?php

/**
 * Description of logfile
 *
 * @author Chris Vaughan
 */
class Logfile {

    private static $logfile;
    private static $noerrors = 0;
    private static $errors;
    private static $logfilename = "";

    static function create($name) {
        $subname = date("YmdHis");
        self::$logfilename = $name . $subname . ".log";
        self::$logfile = fopen(self::$logfilename, "w+") or die("Unable to open logfile file!");
        Logfile::writeWhen("Logfile " . $subname . ".log created");
        self::deleteOldFiles();
        self::$errors = [];
    }

    static function deleteOldFiles() {
        $today = date("Y-m-d");
        $date = new DateTime($today);
        $date->sub(new DateInterval('P28D'));
        $datestring = $date->format('Y-m-d');
        $fileSystemIterator = new FilesystemIterator('logfiles');
        foreach ($fileSystemIterator as $fileInfo) {
            $entry = $fileInfo->getFilename();
            if (Functions::endsWith($entry, ".log")) {
                $filename = 'logfiles/' . $entry;
                $modified = date("Y-m-d", filemtime($filename));
                if ($modified < $datestring) {
                    unlink($filename);
                    logfile::writeWhen("Old logfile deleted: " . $filename);
                }
            }
        }
    }

    static function fileGetContents() {
        $text = file_get_contents(self::$logfilename);
        return $text;
    }

    static function write($text) {
        if (isset(self::$logfile)) {
            fwrite(self::$logfile, $text . "\n");
        }
    }

    static function writeWhen($text) {
        $today = new DateTime(NULL);
        $when = $today->format('Y-m-d H:i:s');
        self::write($when . " " . $text);
    }

    static function writeError($text) {
        self::$noerrors += 1;
        self::writeWhen(" ERROR: " . $text);
        self::addError($text);
    }

    private static function addError($text) {
        if (self::$noerrors <= 10) {
            self::$errors[] = $text;
        }
    }

    static function getNoErrors() {
        return self::$noerrors;
    }

    static function getErrors() {
        return self::$errors;
    }

    static function resetNoErrrors() {
        self::$noerrors = 0;
    }

    static function name() {
        return self::$logfilename;
    }

    static function close() {
        if (isset(self::$logfile)) {
            fclose(self::$logfile);
            self::$logfile = NULL;
        }
    }

}
