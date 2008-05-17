<?php
/**
 * Recusive alternative to ksort
 *
 * @param unknown_type $array
 */
function ksortTree( &$array )
{
    ksort($array);
    foreach($array as $k=>$v){
        if (is_array($v)){
            ksortTree($array[$k]);
        }
    }
}
?>