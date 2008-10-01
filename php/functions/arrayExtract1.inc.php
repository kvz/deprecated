<?php
/**
 * Takes all 1-level deep elements and compiles a new array
 *
 * @param array $array
 * 
 * @return mixed boolean on failure or array on success
 */
function arrayExtract1($array) {
    $newArray = array();
    if (!is_array($array)) {
        return false;
    }
    
    foreach ($array as $k=>$array1) {
        foreach ($array1 as $k1=>$v1) {
            $newArray[] = $v1;
        }
    }
    return $newArray;
}
?>