<?php
/**
 * Recusive alternative to ksort
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code> 
 * // Input //
 * $array = array(
 *     "c" => array(
 *         "d" => 4,
 *         "a" => 1,
 *         "b" => 2,
 *         "c" => 3,
 *         "e" => 5 
 *     ),
 *     "a" => array(
 *         "d" => 4,
 *         "b" => 2,
 *         "a" => 1,
 *         "e" => 5,
 *         "c" => 3
 *     ),
 *     "b" => array(
 *         "d" => 4,
 *         "b" => 2,
 *         "c" => 3,   
 *         "a" => 1
 *     )
 * );
 * 
 * // Execute //
 * ksortTree($array);
 * 
 * // Show //
 * print_r($array);
 * 
 * // expects:
 * // Array
 * // (
 * //     [a] => Array
 * //         (
 * //             [a] => 1
 * //             [b] => 2
 * //             [c] => 3
 * //             [d] => 4
 * //             [e] => 5
 * //         )
 * // 
 * //     [b] => Array
 * //         (
 * //             [a] => 1
 * //             [b] => 2
 * //             [c] => 3
 * //             [d] => 4
 * //         )
 * // 
 * //     [c] => Array
 * //         (
 * //             [a] => 1
 * //             [b] => 2
 * //             [c] => 3
 * //             [d] => 4
 * //             [e] => 5
 * //         )
 * // 
 * // )
 * </code>
 * 
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/
 * 
 * @param array $array
 */
function ksortTree( &$array )
{
    ksort($array);
    foreach ($array as $k=>$v) {
        if (is_array($v)) {
            ksortTree($array[$k]);
        }
    }
}
?>