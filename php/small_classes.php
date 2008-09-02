<?php
$dir   = realpath(dirname(__FILE__)."/classes");
$files = glob($dir."/*.php");
// Check all files in the classs directory
foreach ($files as $file) {
    // Valid include file?
    if (file_exists($file)) {
        // class already exists?
        $class = basename($file, ".php"); 
        if (!class_exists($class)) {
            include_once $file;
        }
    }
}    
?>