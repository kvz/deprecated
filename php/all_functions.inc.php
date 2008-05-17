<?php
    $dir = realpath(dirname(__FILE__)."/functions");
    foreach (glob($dir) as $filepath) {
        if (is_file($filepath) && substr($filepath, -7) == ".inc.php") { 
            require_once $filepath;
        }
    }    
?>