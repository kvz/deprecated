<?php
/**
 * Takes 2 elements and compiles an associative array
 *
 * @param array $array
 * 
 * @return mixed boolean on failure or array on success
 */
function arraySquash($array, $useKey=null, $useVal=null){
    if (!is_array($array)) {
        return false;
    }
    
    $newArray = array();
    $keys     = null;
    
    foreach ($array as $k=>$array1) {
        if ($keys === null) {
            $keys = array_keys($array1);
        }
        if ($useKey === null) {
            $useKey = array_shift($keys);
        }
        if ($useVal === null) {
            $useVal = array_shift($keys);
        }
        
        $newArray[$array1[$useKey]] = $array1[$useVal];
    }
    
    return $newArray;
}
?>