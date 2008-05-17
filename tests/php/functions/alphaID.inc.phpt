--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
$number_in = 2188847690240;
$alpha_in  = "SpQXn7Cb";

// Execute //
$alpha_out  = alphaID($number_in, false, 8);
$number_out = alphaID($alpha_in, true, 8);

if ($number_in != $number_out) {
    echo "Conversion failure, ".$alpha_in." returns ".$number_out." instead of the ";
    echo "desired: ".$number_in."\n";
}
if ($alpha_in != $alpha_out) {
    echo "Conversion failure, ".$number_in." returns ".$alpha_out." instead of the ";
    echo "desired: ".$alpha_in."\n";
}

// Show //
echo $number_in." => ".$alpha_out."\n";
echo $alpha_in." => ".$number_out."\n";
?>
--EXPECT--
2188847690240 => SpQXn7Cb
SpQXn7Cb => 2188847690240