<?php

$defaultValues = [
    "help" => false,
    "verbose" => false,
    "simulation" => true,
    //"accounts" => null,
    "html" => false,
];

$defaultKeys = [
    "help::",
    "verbose::",
    "simulation::",
    "html::",
];

$givenArguments = getopt("", $defaultKeys);
if($givenArguments === false) {
    $givenArguments = [];
}

$givenArguments = array_map(function($item) { return !$item; }, $givenArguments);

$inputs = filter_input_array(INPUT_GET, [
    'verbose' => ['filter' => FILTER_VALIDATE_BOOLEAN],
    'simulation' => ['filter' => FILTER_VALIDATE_BOOLEAN],
    'html' => ['filter' => FILTER_VALIDATE_BOOLEAN],
]);

if($inputs == null) {
    $inputs = [];
}
$inputs = array_filter($inputs, function($var) { return $var !== null; });

$options = array_merge($defaultValues, $givenArguments, $inputs);

$_ENV['verbose'] = $options['verbose'];
$_ENV['simulation'] = $options['simulation'];

if($_ENV['simulation']) {
    $_ENV['verbose'] = true;
}

