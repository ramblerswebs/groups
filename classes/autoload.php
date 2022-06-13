<?php

// auto load where Capital letters imply folder
// e.g. TestClassTest is stored in test/class/test.php

function autoload($class_name) {
    $split = preg_split('/(?=[A-Z])/', $class_name, -1, PREG_SPLIT_NO_EMPTY);
    $file = "classes/" . implode('/', $split) . ".php";
    $file = BASE_PATH."/".strtolower($file);
    if (file_exists($file)) {
        require_once( $file );
    } else {
        echo "Unable to find class file: " . $file;
        Logfile::writeError("Unable to find class file: " . $file);
    }
}
