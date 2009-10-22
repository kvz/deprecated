<?php
/*
 * Runtime settings
 */

error_reporting(E_ALL);

/*
 * Includes
 */
if (!defined('DIR_BUILDER_ROOT')) {
    define('DIR_BUILDER_ROOT', dirname(__FILE__));
}

if (!defined('DIR_EGGSHELL_ROOT')) {
    if (file_exists('/var/git/eggshell/EggShell.php')) {
        define('DIR_EGGSHELL_ROOT', '/var/git/eggshell');
    } else {
        define('DIR_EGGSHELL_ROOT', DIR_BUILDER_ROOT.'/eggshell');
    }
}

require_once DIR_EGGSHELL_ROOT.'/EggShell.php';
require_once DIR_BUILDER_ROOT.'/Builder.php';

/**
 * print_r shortcut
 */
function pr() {
    $args = func_get_args();

    if (php_sapi_name() !=='cli') {
        echo '<pre>'."\n";
    }
    foreach($args as $arg) {
        print_r($arg);
    }
    if (php_sapi_name() !=='cli') {
        echo '</pre>'."\n";
    }
}

/**
 * print_r & die shortcut
 */
function prd() {
    $args = func_get_args();
    call_user_func_array('pr', $args);
    die();
}
?>