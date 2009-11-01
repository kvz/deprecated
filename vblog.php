<?php
error_reporting(E_ALL);
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

if (!defined('DIR_EGGSHELL_ROOT')) {
	if (file_exists('/home/kevin/workspace/eggshell')) {
		define('DIR_EGGSHELL_ROOT', '/home/kevin/workspace/eggshell');
	} else {
		define('DIR_EGGSHELL_ROOT', DIR_VBLOG."/vendors/eggshell/Base.php");
	}
}

require_once DIR_EGGSHELL_ROOT.'/Base.php';

require_once DIR_VBLOG."/libs/Post.php";
require_once DIR_VBLOG."/libs/Posts.php";
require_once DIR_VBLOG."/libs/Rpc.php";
require_once DIR_VBLOG."/libs/Source.php";
require_once DIR_VBLOG."/libs/VBlog.php";

if (@$test) {
    return;
}
$config = include(DIR_VBLOG.'/config/config.php');

$config = Base::merge($config, array(
    'class-autobind' => true,
    'class-autosetup' => true,
));
$VBlog = new VBlog($config);
$VBlog->run();
?>