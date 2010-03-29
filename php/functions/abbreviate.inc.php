<?php
/**
 * Abbreviates a string. Chops it down to length specified in $cutAt
 * The string will never be longer than cutAt, even with the
 * concatenated $suffix
 *
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input = "Kevin and Max go for walk in the park.";
 * 
 * // Execute //
 * $output   = array();
 * $output[] = abbreviate($input, 20);
 * $output[] = abbreviate($input, 10);
 * $output[] = abbreviate($input, 30, ' [more >>]');
 * 
 * // Show //
 * print_r($output);
 * 
 * // expects:
 * // Array
 * // (
 * //     [0] => Kevin and Max go ...
 * //     [1] => Kevin a...
 * //     [2] => Kevin and Max go for [more >>]
 * // )
 * </code>
 * 
 * @param string  $str
 * @param integer $cutAt
 * @param string  $suffix
 * 
 * @return mixed boolean or string
 */


function abbreviate($str, $cutAt = 30, $suffix = '...')
{
    if (strlen($str) <= $cutAt) {
        return $str;
    }
    
    $canBe = $cutAt - strlen($suffix);

    return substr($str, 0, $canBe). $suffix;
}
?>