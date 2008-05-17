<?php
/**
 * Recursive alternative to str_replace that supports replacing keys as well
 *
 * @param unknown_type $search
 * @param unknown_type $replace
 * @param unknown_type $array
 * @param unknown_type $keys_too
 * 
 * @return unknown
 */
function replaceTree($search="", $replace="", $array=false, $keys_too=false)
{ 
    $newArr = array();
    if (is_array($array)) {
        foreach ($array as $k=>$v) {
            $add_key = (!$keys_too?$k:str_replace($search, $replace, $k));
            if (is_array($v)) {
                $newArr[$add_key] = replaceTree($search, $replace, $v, $keys_too);
            } else {
                $newArr[$add_key] = str_replace($search, $replace, $v);
            }
        }
    }
    return $newArr;
}
?>