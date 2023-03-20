<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of functions
 *
 * @author Chris
 */
class Functions {

    public static function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    public static function endsWith($haystack, $needle) {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== FALSE);
    }

    public static function formatDateDiff($interval) {

        $doPlural = function ($nb, $str) {
            return $nb > 1 ? $str . 's' : $str;
        }; // adds plurals

        $format = array();
        if ($interval->y !== 0) {
            $format[] = "%y " . $doPlural($interval->y, "year");
        }
        if ($interval->m !== 0) {
            $format[] = "%m " . $doPlural($interval->m, "month");
        }
        if ($interval->d !== 0) {
            $format[] = "%d " . $doPlural($interval->d, "day");
        }
        if ($interval->h !== 0) {
            $format[] = "%h " . $doPlural($interval->h, "hour");
        }
        if ($interval->i !== 0) {
            $format[] = "%i " . $doPlural($interval->i, "minute");
        }
        if ($interval->s !== 0) {
            if (!count($format)) {
                return "less than a minute ago";
            } else {
                $format[] = "%s " . $doPlural($interval->s, "second");
            }
        }

        // We use the two biggest parts
        if (count($format) > 1) {
            $format = array_shift($format) . " and " . array_shift($format);
        } else {
            $format = array_pop($format);
        }

        // Prepend 'since ' or whatever you like
        return $interval->format($format);
    }

    public static function getExtension($path) {
        $parts = explode(".", $path);
        if (count($parts) == 1) {
            return null;
        }
        return $parts[count($parts) - 1];
    }

    public static function deleteFolder($dir) {
        if (file_exists($dir)) {
            // delete folder and its contents
            foreach (glob($dir . '/*') as $file) {
                if (is_dir($file)) {
                    Functions::deleteFolder($file);
                } else {
                    unlink($file);
                }
            }
            rmdir($dir);
        }
    }

    public static function errorEmail($feed, $error) {

        date_default_timezone_set('Europe/London');
        $domain = "theramblers.org.uk";
        // Create a new PHPMailer instance
        $mailer = new PHPMailer\PHPMailer\PHPMailer;
        $mailer->setFrom("admin@" . $domain, $domain);
        $mailer->addAddress(NOTIFYEMAILADDRESS, 'Web Master');
        $mailer->isHTML(true);
        $mailer->Subject = "Ramblers Feed Error";
        $mailer->Body = "<p>Feed error found while running: " . TASK . "</p>" .
                "<p>Feed: " . $feed . "</p>"
                . "<p>Error: " . $error . "</p>"
                . "<p>Logfile: " . Logfile::name() . " may contain additional information.</p>";
        $log=Logfile::fileGetContents();
      //  if(!$log){
            $mailer->Body=$mailer->Body.str_replace("\n","<br>",$log);
      //  }
        $okay = $mailer->send();
        if (!$okay) {
            Logfile::writeWhen("Error message sent");
            Logfile::writeWhen("Task: " . TASK);
            Logfile::writeWhen("Feed: " . $feed);
            Logfile::writeWhen("Error: " . $error);
        }
    }

    public static function getJsonFeed($feedurl) {
        Logfile::writeWhen("Feed: " . $feedurl);
        $json = file_get_contents($feedurl);
        if ($json === false) {
            self::errorEmail($feedurl, "Unable to read feed: file_get_contents failed");
            die();
        }
        Logfile::writeWhen("---- Feed read");
        $items = json_decode($json);
        if (json_last_error() == JSON_ERROR_NONE) {
            
        } else {
            self::errorEmail($feedurl, "Error when decoding JSON feed");
            die();
        }
        Logfile::writeWhen("---- JSON decoded");
        return $items;
    }

    public static function findSite($response, $code) {
        if ($response->success) {
            $sites = $response->data;
            foreach ($sites as $site) {
                if ($site->code == $code) {
                    return $site;
                }
            }
        }

        return null;
    }

    public static function addTableHeader($cols) {
        if (is_array($cols)) {
            $out = "<tr>";
            foreach ($cols as $value) {
                $out .= "<th>" . $value . "</th>";
            }
            $out .= "</tr>" . PHP_EOL;
            return $out;
        } else {
            return "<tr><td>invalid argument in html::addTableHeader</td></tr>";
        }
    }

    public static function addTableRow($cols, $class = "") {
        if (is_array($cols)) {
            if ($class == "") {
                $out = "<tr>";
            } else {
                $out = "<tr class='" . $class . "'>";
            }

            foreach ($cols as $value) {
                $out .= "<td>" . $value . "</td>";
            }
            $out .= "</tr>" . PHP_EOL;
            return $out;
        } else {
            return "<tr><td>invalid argument in html::addTableRows</td></tr>";
        }
    }

    public static function addListItem($text) {
        return "<li>" . $text . "</li>";
    }

    public static function contains($needle, $haystack) {
        return strpos($haystack, $needle) !== false;
    }

}
