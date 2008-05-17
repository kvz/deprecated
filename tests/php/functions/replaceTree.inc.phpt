--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
$settings = array(
    "Credits" => "@appname@ created by @author@",
    "Description" => "@appname@ can parse logfiles and store then in mysql",
    "@author@_mail" => "kevin@vanzonneveld.net"    
);    
$mapping = array(
    "@author@" => "kevin",
    "@appname@" => "logchopper"
);

// Execute //
$settings = replaceTree(
    array_keys($mapping), array_values($mapping), $settings, true
);

// Show //
print_r($settings);
?>
--EXPECT--
Array
(
    [Credits] => logchopper created by kevin
    [Description] => logchopper can parse logfiles and store then in mysql
    [kevin_mail] => kevin@vanzonneveld.net
)