<?php
/**
 * Takes first part of a string based on the delimiter.
 * Returns that part, and mutates the original string to contain
 * the reconcatenated remains 
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input = "Kevin and Max go for walk in the park.";
 * 
 * // Execute //
 * $output   = array();
 * $output[] = strShift(" ", $input)." - ".$input;
 * $output[] = strShift(" ", $input)." - ".$input;
 * 
 * // Show //
 * print_r($output);
 * 
 * // expects:
 * // Array
 * // (
 * //     [0] => Kevin - and Max go for walk in the park.
 * //     [1] => and - Max go for walk in the park.
 * // )
 * </code>
 * 
 * @param string $delimiter
 * @param string &$string
 * 
 * @return string
 */
function strShift($delimiter, &$string)
{
    // Explode into parts
    $parts  = explode($delimiter, $string);
    
    // Shift first
    $first  = array_shift($parts);
    
    // Glue back together, overwrite string by reference
    $string = implode($delimiter, $parts);
    
    // Return first part
    return $first;
}
?>