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
 * //     [3] => 
 * // )
 * </code>
 * 
 * @param array $array
 * 
 * @return array
 */
function arrayTrim($array) {
    // Escapes
    if (!is_array($array)) {
        return false;
    }
    if (!count($array)) {
        return $array;
    }
    
    // Trim beginning of array
    while (true) {
        if (false !== ($item = reset($array)) && 0 === strlen(trim($item))) {
            array_shift($array);
        } else {
            break;
        }
    }
    
    // Trim end of array
    while (true) {
        if (false !== ($item = end($array)) && 0 === strlen(trim($item))) {
        } else {
            break;
        }
    }
    
    return $array;
}   
?>