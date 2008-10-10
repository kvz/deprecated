<?php
/**
 * 'Trims' an array. Removes empty elements from the bottom and top.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input = array('', 'Kevin', 'van', 'Zonneveld', false);
 * 
 * // Execute //
 * $output = arrayTrim($input);
 * 
 * // Show //
 * print_r($output);
 * 
 * // expects:
 * // Array
 * // (
 * //     [0] => Kevin
 * //     [1] => van
 * //     [2] => Zonneveld
 * // )
 * </code>
 * 
 * @param array $array
 * 
 * @return array
 */
function arrayTrim($array) {
    if (!is_array($array)) {
        return false;
    }
    if (!count($array)) {
        return $array;
    }
    
    
    while (strlen(reset($array)) === 0) {
        array_shift($array);
    }
    while (strlen(end($array)) === 0) {
        array_pop($array);
    }
    return $array;
}   
?>