<?php
/**
 * Takes a percentage, or a string divide and returns a color between
 * red & green. Based on Disk Usage Warning so 0 = green, 100 = red.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input   = array();
 * $input[0] = 1;
 * $input[1] = 99;
 * $input[2] = '500/1000';
 *
 * // Execute //
 * $output   = array();
 * $output[0] = statusColor($input[0]);
 * $output[1] = statusColor($input[1]);
 * $output[2] = statusColor($input[2]);
 *
 * // Show //
 * print_r($output);
 *
 * // expects:
 * // Array
 * // (
 * //     [0] => #00FF0A
 * //     [1] => #FF0600
 * //     [2] => #F7FF00
 * // )
 * </code>
 *
 * @param mixed integer or string $percentage
 * 
 * @return string
 */
function statusColor($percentage)
{
    if (substr_count($percentage, "/")) {
        $p = explode("/",$percentage);
        $v = intval($p[0]);
        $m = intval($p[1]);
        if ($m == 0) {
            $percentage = 0;
        } else {
            $percentage = ($v / $m * 100);
        }
    }

    if ($percentage > 100) {
        $percentage = 100;
    }
    if ($percentage < 1) {
        $percentage = 1;
    }

    $factor     = (100 / $percentage);
    $percentage = 87 - intval(87 / $factor) ;

    $h = $percentage;
    $s = 255;
    $v = 255;

    if (!function_exists('returnColor')) {
        function returnColor($color) {
            return sprintf('%02X%02X%02X',$color[0],$color[1],$color[2]);
        }
    }

    if (!function_exists('hsv2hex')) {
        function hsv2hex ( $h, $s, $v ) {
            $s /= 256.0;
            $v /= 256.0;
            if ($s == 0.0) {
                $r = $g = $b = $v;
                return '';
            } else {
                $h = $h/256.0*6.0;
                $i = floor($h);
                $f = $h - $i;

                $v *= 256.0;
                $p = (integer)($v * (1.0 - $s));
                $q = (integer)($v * (1.0 - $s * $f));
                $t = (integer)($v * (1.0 - $s * (1.0 - $f)));
                switch( $i ){
                    case 0:
                        $r = $v;
                        $g = $t;
                        $b = $p;
                    break;
                    case 1:
                        $r = $q;
                        $g = $v;
                        $b = $p;
                    break;
                    case 2:
                        $r = $p;
                        $g = $v;
                        $b = $t;
                    break;
                    case 3:
                        $r = $p;
                        $g = $q;
                        $b = $v;
                    break;
                    case 4:
                        $r = $t;
                        $g = $p;
                        $b = $v;
                    break;
                    default:
                        $r = $v;
                        $g = $p;
                        $b = $q;
                    break;
                }
            }
            
            $newcolor = array($r, $g, $b);
            return returnColor($newcolor);
        }
    }

    return  "#".hsv2hex( $h,$s,$v );
}
?>