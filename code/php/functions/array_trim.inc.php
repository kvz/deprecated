<?php
/**
 * Trims an array
 *
 * @param array $array
 * 
 * @return array
 */
function array_trim($array) {
    while (strlen(reset($array)) === 0) {
        array_shift($array);
    }
    while (strlen(end($array)) === 0) {
        array_pop($array);
    }
    return $array;
}   
?>