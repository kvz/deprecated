<?php
$dir = realpath(dirname(__FILE__)."/functions");
// Check all files in the functions directory
foreach (glob($dir) as $filepath) {
    // Valid include file?
    if (is_file($filepath) && substr($filepath, -7) == ".inc.php") {
        // Function already exists?
        $function = basename($filepath, ".inc.php"); 
        if (!function_exists($function)) {
            include_once $filepath;
        }
    }
}    
?>