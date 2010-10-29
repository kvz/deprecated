#!/usr/bin/php
<?php
error_reporting(E_ALL);
if (!defined('DIR_EGG_ROOT')) {
	define('DIR_EGG_ROOT', dirname(__FILE__));
}

if (!function_exists('pr')) {
	function pr($arr) {
		if (php_sapi_name() !=='cli') {
			echo '<pre>'."\n";
		}
		if (is_array($arr) && count($arr)) {
			print_r($arr);
		} else {
			var_dump($arr);
		}
		if (php_sapi_name() !=='cli') {
			echo '</pre>';
		}

		echo "\n";
	}
}
if (!function_exists('prd')) {
	function prd($arr) {
		pr($arr);
		die();
	}
}

require_once DIR_EGG_ROOT.'/Base.php';
require_once DIR_EGG_ROOT.'/EggShell.php';

$E = new EggShell();

$E->chownr('/tmp/kevin', 'kevin');

