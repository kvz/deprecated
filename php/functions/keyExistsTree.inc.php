<?php
/**
 * Recusive alternative to array_key_exists
 *
 * Taken from http://nl3.php.net/manual/en/function.array-key-exists.php#82890
 *  
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $array = array(
 *     'c' => array(
 *         'd' => 4,
 *         'a' => 1,
 *         'b' => 2,
 *         'c' => 3,
 *         'e' => 5,
 *     ),
 *     'a' => array(
 *         'd' => 4,
 *         'b' => 2,
 *         'a' => 1,
 *         'e' => 5,
 *         'c' => 3,
 *     ),
 *     'b' => array(
 *         'x' => 4,
 *         'y' => 2,
 *         'z' => 3,
 *     )
 * );
 *
 * // Execute //
 * $output   = array();
 * $output[] = keyExistsTree('z', $array) ? 'true' : 'false';
 * $output[] = keyExistsTree('a', $array) ? 'true' : 'false';
 * $output[] = keyExistsTree('i', $array) ? 'true' : 'false';
 * $output[] = keyExistsTree('c', $array) ? 'true' : 'false';
 *
 * // Show //
 * print_r($output);
 *
 * // expects:
 * // Array
 * // (
 * //     [0] => true
 * //     [1] => true
 * //     [2] => false
 * //     [3] => true
 * // )
 * </code>
 *
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/
 *
 * @param string $needle
 * @param array  $haystack
 *
 * @return boolean
 */
function keyExistsTree($needle, $haystack) {
    if (!is_array($haystack)) {
        return false;
    }


    $result = array_key_exists($needle, $haystack);
    if ($result) {
        return $result;
    }

    if (is_array($haystack)) {
        foreach ($haystack as $v) {
            if (is_array($v)) {
                $result = keyExistsTree($needle, $v);
            }
            if ($result) {
                return $result;
            }
        }
    }

    return $result;
}
?>