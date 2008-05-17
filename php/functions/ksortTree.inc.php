<?php
/**
 * Recusive alternative to ksort
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