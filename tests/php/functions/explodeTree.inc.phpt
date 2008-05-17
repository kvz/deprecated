--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
$key_files = array(
    "/etc/php5" => "/etc/php5", 
    "/etc/php5/cli" => "/etc/php5/cli", 
    "/etc/php5/cli/conf.d" => "/etc/php5/cli/conf.d", 
    "/etc/php5/cli/php.ini" => "/etc/php5/cli/php.ini", 
    "/etc/php5/conf.d" => "/etc/php5/conf.d",
    "/etc/php5/conf.d/mysqli.ini" => "/etc/php5/conf.d/mysqli.ini", 
    "/etc/php5/conf.d/curl.ini" => "/etc/php5/conf.d/curl.ini", 
    "/etc/php5/conf.d/snmp.ini" => "/etc/php5/conf.d/snmp.ini", 
    "/etc/php5/conf.d/gd.ini" => "/etc/php5/conf.d/gd.ini", 
    "/etc/php5/apache2" => "/etc/php5/apache2", 
    "/etc/php5/apache2/conf.d" => "/etc/php5/apache2/conf.d", 
    "/etc/php5/apache2/php.ini" => "/etc/php5/apache2/php.ini" 
);

// Execute //
$tree = explodeTree($key_files, "/", true);

// Show //
print_r($tree);
?>
--EXPECT--
Array
(
    [etc] => Array
        (
            [php5] => Array
                (
                    [__base_val] => /etc/php5
                    [cli] => Array
                        (
                            [__base_val] => /etc/php5/cli
                            [conf.d] => /etc/php5/cli/conf.d
                            [php.ini] => /etc/php5/cli/php.ini
                        )

                    [conf.d] => Array
                        (
                            [__base_val] => /etc/php5/conf.d
                            [mysqli.ini] => /etc/php5/conf.d/mysqli.ini
                            [curl.ini] => /etc/php5/conf.d/curl.ini
                            [snmp.ini] => /etc/php5/conf.d/snmp.ini
                            [gd.ini] => /etc/php5/conf.d/gd.ini
                        )

                    [apache2] => Array
                        (
                            [__base_val] => /etc/php5/apache2
                            [conf.d] => /etc/php5/apache2/conf.d
                            [php.ini] => /etc/php5/apache2/php.ini
                        )

                )

        )

)