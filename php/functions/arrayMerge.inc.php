<?php
/**
 * Taken from CakePHP!
 * This function can be thought of as a hybrid between PHP's array_merge and array_merge_recursive. The difference
 * to the two is that if an array key contains another array then the function behaves recursive (unlike array_merge)
 * but does not do if for keys containing strings (unlike array_merge_recursive). See the unit test for more information.
 *
 * Note: This function will work with an unlimited amount of arguments and typecasts non-array parameters into arrays.
 *
 * @param array $arr1 Array to be merged
 * @param array $arr2 Array to merge with
 * 
 * @return array Merged array
 */
function arrayMerge($arr1, $arr2 = null) {
    $args = func_get_args();

    if (!isset($r)) {
        $r = (array)current($args);
    }

    while (($arg = next($args)) !== false) {
        foreach ((array)$arg as $key => $val)	 {
            if (is_array($val) && isset($r[$key]) && is_array($r[$key])) {
                $r[$key] = arrayMerge($r[$key], $val);
            } elseif (is_int($key)) {
                $r[] = $val;
            } else {
                $r[$key] = $val;
            }
        }
    }
    return $r;
}
?>