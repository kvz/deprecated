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

// Include the SimplePie library
require_once DIR_VBLOG."/vendors/simplepie.inc";
require_once DIR_VBLOG."/vendors/simplepie_delicious.inc";
require_once DIR_VBLOG."/vendors/class-IXR.php";
require_once DIR_VBLOG."/vendors/eggshell/Base.php";
#require_once DIR_VBLOG."/vendors/bloggerClass/class.bloggerclient.php";
require_once DIR_VBLOG."/libs/VBlog.php";

$config = include(DIR_VBLOG.'/config/config.php');
$VBlog = new VBlog($config);
$VBlog->run();

?>