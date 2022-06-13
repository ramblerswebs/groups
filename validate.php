<?php

require_once 'vendor/autoload.php';

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

$config = json_decode(file_get_contents('config.json'));
$validator = new Validator;
$validator->validate(
        $config,
        (object) ['$ref' => 'file://' . realpath('schema.json')],
        Constraint::CHECK_MODE_APPLY_DEFAULTS
);

if ($validator->isValid()) {
    echo "JSON validates OK\n";
} else {
    echo "JSON validation errors:\n";
    foreach ($validator->getErrors() as $error) {
        print_r($error);
    }
}

print "\nResulting config:\n";
print_r($config);
