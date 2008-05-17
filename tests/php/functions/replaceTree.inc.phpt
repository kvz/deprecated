--TEST--
--FILE--
<?php
require_once str_replace(".inc.phpt.php", ".inc.php", __FILE__);

$settings = array(
    "Credits" => "@appname@ created by @author@",
    "Description" => "@appname@ can parse logfiles and store then in mysql",
    "@author@_mail" => "kevin@vanzonneveld.net"    
);    
$mapping = array(
    "@author@" => "kevin",
    "@appname@" => "logchopper"
);

$settings = replaceTree(
    array_keys($mapping), array_values($mapping), $settings, true
);

print_r($settings);
?>
--EXPECT--
Array
(
    [Credits] => logchopper created by kevin
    [Description] => logchopper can parse logfiles and store then in mysql
    [kevin_mail] => kevin@vanzonneveld.net
)