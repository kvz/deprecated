--TEST--
--FILE--
<?php
require_once str_replace(".inc.phpt.php", ".inc.php", __FILE__);

$array = array(
    "c" => array(
        "d" => 4,
        "a" => 1,
        "b" => 2,
        "c" => 3,
        "e" => 5 
    ),
    "a" => array(
        "d" => 4,
        "b" => 2,
        "a" => 1,
        "e" => 5,
        "c" => 3
    ),
    "b" => array(
        "d" => 4,
        "b" => 2,
        "c" => 3,   
        "a" => 1
    )
);

ksortTree($array);

print_r($array["a"]);
?>
--EXPECTF--
Array
(
    [a] => Array
        (
            [a] => 1
            [b] => 2
            [c] => 3
            [d] => 4
            [e] => 5
        )
    [b] => Array
        (
            [a] => 1
            [b] => 2
            [c] => 3
            [d] => 4
        )
    [c] => Array
        (
            [a] => 1
            [b] => 2
            [c] => 3
            [d] => 4
            [e] => 5
        )
)
