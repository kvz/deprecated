<?php
/**
 * Implode any multi-dimensional array to a less dimensional tree structure,
 * based on the delimiters found in it's keys.
 *
 * Code taken from CakePHP's Set::flatten
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $tree = array(
 *     'etc' => array(
 *         'php5' => array(
 *             'cli',
 *             'conf.d',
 *             'apache2',
 *         )
 *     )
 * );
 *
 * // Execute //
 * $trees[0] = implodeTree($tree, ".");
 * $trees[1] = implodeTree($tree, ".", true);
 *
 * // Show //
 * print_r($trees);
 *
 * // expects:
 * // Array
 * // (
 * //     [0] => Array
 * //         (
 * //             [etc.php5.0] => cli
 * //             [etc.php5.1] => conf.d
 * //             [etc.php5.2] => apache2
 * //         )
 * //
 * //     [1] => Array
 * //         (
 * //             [etc.php5] => Array
 * //                 (
 * //                     [0] => cli
 * //                     [1] => conf.d
 * //                     [2] => apache2
 * //                 )
 * //
 * //         )
 * //
 * // )
 * //
 * </code>
 *
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/
 *
 * @param array   $array
 * @param string  $delimiter
 * @param boolean $preserveLastDimension
 *
 * @return array
 */



function implodeTree($data, $delimiter = '.', $preserveLastDimension = false) {
    $result = array();
    $path = null;

    if (is_array($delimiter)) {
        extract($delimiter, EXTR_OVERWRITE);
    }

    if (!is_null($path)) {
        $path .= $delimiter;
    }

    foreach ($data as $key => $val) {
        if (is_array($val) && !($preserveLastDimension && !is_array(reset($val)))) {
                $result += (array)implodeTree($val, array(
                    'delimiter' => $delimiter,
                    'path' => $path . $key
                ), $preserveLastDimension);
        } else {
            $result[$path . $key] = $val;
        }
    }
    return $result;
}
?>
