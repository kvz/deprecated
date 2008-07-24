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
    $parts  = explode($delimiter, $string);
    $first  = array_shift($parts);
    $string = implode($delimiter, $parts);
    return $first;
}
?>