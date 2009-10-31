<?php
if (!function_exists('pr')) {
    function pr($arr) {
        return print_r($arr);
    }
}
if (!function_exists('prd')) {
    function prd($arr) {
        pr($arr);
        die();
    }
}

if (!defined('DIR_VBLOG')) {
    define('DIR_VBLOG', dirname(__FILE__));
}

$VBlog = new VBlog(include(DIR_VBLOG.'/config/config.php'));
$VBlog->run();

?>