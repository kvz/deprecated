<?php
/**
 * Recusive alternative to ksort
 * 
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @author    Lachlan Donald
 * @author    Takkie
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