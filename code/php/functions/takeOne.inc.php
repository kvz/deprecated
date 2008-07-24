<?php
/**
 * Takes first part of a string based on the delimiter.
 * Returns that part, and mutates the original string to contain
 * the reconcatenated remains 
 *
 * @param string $delimiter
 * @param string &$string
 * 
 * @return array
 */
function takeOne($delimiter, &$string)
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