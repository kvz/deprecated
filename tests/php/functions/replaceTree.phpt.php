<?php

require_once str_replace(".example.", ".inc.", __FILE__);

$settings = array(
    "Credits" => "@appname@ created by @author@",
    "Description" => "@appname@ can parse logfiles and store then in mysql"    
);    

$settings = replaceTree("@appname@", "logchop", $settings);
var_dump($settings);

?>