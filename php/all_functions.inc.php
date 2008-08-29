<?php
$dir   = realpath(dirname(__FILE__)."/functions");
$files = glob($dir."/*.inc.php");
// Check all files in the functions directory
foreach ($files as $file) {
    // Valid include file?
    if (file_exists($file)) {
        // Function already exists?
        $function = basename($file, ".inc.php"); 
        if (!function_exists($function)) {
            include_once $file;
        }
    }
}    
?>