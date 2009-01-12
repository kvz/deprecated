#!/usr/bin/php -q
<?php
// Allowed arguments & their defaults
$runmodes = array(
    "help" => false,
    "index" => false,
    "test" => false
);

// Scan command line attributes for allowed arguments
foreach ($argv as $k=>$arg) {
    if (substr($arg, 0, 2) == "--" && isset($runmodes[substr($arg, 2)])) {
        $runmodes[substr($arg, 2)] = true;
    }
}

function help($runmodes, $argv) {
    // Help mode. Shows allowed argumentents and quit directly
    echo "Usage: ".$argv[0]." [runmode]\n";
    echo "Available runmodes:\n";
    foreach ($runmodes as $runmode=>$val) {
        echo " --".$runmode."\n";
    }
    die();
}

// Include Class
error_reporting(E_ALL);
require_once "php/classes/KvzLib.php";
$KvzLib = new KvzLib(dirname(__FILE__));

if ($runmodes["index"] === true) {
    $KvzLib->index();
    print_r($KvzLib->languages);
} else {
    help($runmodes, $argv);
}
?>
