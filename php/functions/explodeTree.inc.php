<?php
/**
 * Explode any single-dimensional array into a full blown tree structure, 
 * based on the delimiters found in it's keys.
 * 
 * The following code block can be utilized by PEAR's Testing_DocTest 
 * <code>
 * // Input //
 * $key_files = array(
 *     "/etc/php5" => "/etc/php5", 
 *     "/etc/php5/cli" => "/etc/php5/cli", 
 *     "/etc/php5/cli/conf.d" => "/etc/php5/cli/conf.d", 
 *     "/etc/php5/cli/php.ini" => "/etc/php5/cli/php.ini", 
 *     "/etc/php5/conf.d" => "/etc/php5/conf.d",
 *     "/etc/php5/conf.d/mysqli.ini" => "/etc/php5/conf.d/mysqli.ini", 
 *     "/etc/php5/conf.d/curl.ini" => "/etc/php5/conf.d/curl.ini", 
 *     "/etc/php5/conf.d/snmp.ini" => "/etc/php5/conf.d/snmp.ini", 
 *     "/etc/php5/conf.d/gd.ini" => "/etc/php5/conf.d/gd.ini", 
 *     "/etc/php5/apache2" => "/etc/php5/apache2", 
 *     "/etc/php5/apache2/conf.d" => "/etc/php5/apache2/conf.d", 
 *     "/etc/php5/apache2/php.ini" => "/etc/php5/apache2/php.ini" 
 * );
 * 
 * // Execute //
 * $tree = explodeTree($key_files, "/", true);
 * 
 * // Show //
 * print_r($tree);
 * 
 * // expects:
 * // Array
 * // (
 * //     [etc] => Array
 * //         (
 * //             [php5] => Array
 * //                 (
 * //                     [__base_val] => /etc/php5
 * //                     [cli] => Array
 * //                         (
 * //                             [__base_val] => /etc/php5/cli
 * //                             [conf.d] => /etc/php5/cli/conf.d
 * //                             [php.ini] => /etc/php5/cli/php.ini
 * //                         )
 * // 
 * //                     [conf.d] => Array
 * //                         (
 * //                             [__base_val] => /etc/php5/conf.d
 * //                             [mysqli.ini] => /etc/php5/conf.d/mysqli.ini
 * //                             [curl.ini] => /etc/php5/conf.d/curl.ini
 * //                             [snmp.ini] => /etc/php5/conf.d/snmp.ini
 * //                             [gd.ini] => /etc/php5/conf.d/gd.ini
 * //                         )
 * // 
 * //                     [apache2] => Array
 * //                         (
 * //                             [__base_val] => /etc/php5/apache2
 * //                             [conf.d] => /etc/php5/apache2/conf.d
 * //                             [php.ini] => /etc/php5/apache2/php.ini
 * //                         )
 * // 
 * //                 )
 * // 
 * //         )
 * // 
 * // )
 * </code>
 * 
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author    Lachlan Donald
 * @author    Takkie
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: explodeTree.inc.php 89 2008-09-05 20:52:48Z kevin $
 * @link      http://kevin.vanzonneveld.net/
 * 
 * @param array   $array
 * @param string  $delimiter
 * @param boolean $baseval
 * 
 * @return array
 */
function explodeTree($array, $delimiter = '_', $baseval = false)
{
    if(!is_array($array)) return false;
    $splitRE   = '/' . preg_quote($delimiter, '/') . '/';
    $returnArr = array();
    foreach ($array as $key => $val) {
        // Get parent parts and the current leaf
        $parts    = preg_split($splitRE, $key, -1, PREG_SPLIT_NO_EMPTY);
        $leafPart = array_pop($parts);

        // Build parent structure 
        // Might be slow for really deep and large structures
        $parentArr = &$returnArr;
        foreach ($parts as $part) {
            if (!isset($parentArr[$part])) {
                $parentArr[$part] = array();
            } elseif (!is_array($parentArr[$part])) {
                if ($baseval) {
                    $parentArr[$part] = array('__base_val' => $parentArr[$part]);
                } else {
                    $parentArr[$part] = array(); 
                }
            }
            $parentArr = &$parentArr[$part];
        }

        // Add the final part to the structure
        if (empty($parentArr[$leafPart])) {
            $parentArr[$leafPart] = $val;
        } elseif ($baseval && is_array($parentArr[$leafPart])) {
            $parentArr[$leafPart]['__base_val'] = $val;
        }
    }
    return $returnArr;
}
?>