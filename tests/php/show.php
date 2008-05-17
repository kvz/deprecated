#!/usr/bin/php
<?php

require_once realpath(dirname(__FILE__)."/../../code/php/functions/phptSections.inc.php");

// Check file
if (!($filepath = $argv[1])) {
    die("Nothing to test");
} 

$filepath = realpath($filepath);
if (!file_exists($filepath)) {
    die($filepath." does not exist.");
}

$buf      = file_get_contents($filepath);
$sections = phptSections($buf);

if (!($exec = $sections["FILE"])) {
    die("Nothing to execute");
}

$tempfile = $filepath.".php";

$handle = fopen($tempfile, "w");
fwrite($handle, $exec);
fclose($handle);
chmod($tempfile, 0777);

$x = exec("/usr/bin/php -q ".$tempfile, $o, $r);
echo implode("\n", $o);
echo "\n";
unlink($tempfile);

//print_r($sections); 
?>