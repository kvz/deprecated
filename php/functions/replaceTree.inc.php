<?php
/**
 * Recursive alternative to str_replace that supports replacing keys as well
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $settings = array(
 *     "Credits" => "@appname@ created by @author@",
 *     "Description" => "@appname@ can parse logfiles and store then in mysql",
 *     "@author@_mail" => "kevin@vanzonneveld.net"    
 * );    
 * $mapping = array(
 *     "@author@" => "kevin",
 *     "@appname@" => "logchopper"
 * );
 * 
 * // Execute //
 * $settings = replaceTree(
 *     array_keys($mapping), array_values($mapping), $settings, true
 * );
 * 
 * // Show //
 * print_r($settings);
 * 
 * // expects:
 * // Array
 * // (
 * //     [Credits] => logchopper created by kevin
 * //     [Description] => logchopper can parse logfiles and store then in mysql
 * //     [kevin_mail] => kevin@vanzonneveld.net
 * // )
 * </code>
 * 
 * @param string  $search
 * @param string  $replace
 * @param array   $array
 * @param boolean $keys_too
 * 
 * @return array
 */
function replaceTree($search="", $replace="", $array=false, $keys_too=false)
{ 
    if (!is_array($array)) {
        // Regular replace
        return str_replace($search, $replace, $array);
    }
    
    $newArr = array();
    foreach ($array as $k=>$v) {
        // Replace keys as well?
        $add_key = $k;
        if ($keys_too) {
            $add_key = str_replace($search, $replace, $k);
        }
        
        // Recurse
        $newArr[$add_key] = replaceTree($search, $replace, $v, $keys_too);
    }
    return $newArr;
}
?>