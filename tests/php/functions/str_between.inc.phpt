--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
$input = "Kevin and Max go for walk in the park.";

// Execute //
$output   = array();
$output[] = strBetween($input, "and ", " go");
$output[] = strBetween($input, "and ", " GO", true, true);

// Show //
print_r($output);
?>
--EXPECT--
Array
(
    [0] => Max
    [1] => and Max go
)