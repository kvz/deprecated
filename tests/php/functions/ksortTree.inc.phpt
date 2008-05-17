--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
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

// Execute //
ksortTree($array);

// Show //
print_r($array);
?>
--EXPECT--
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