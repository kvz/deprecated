<?php
error_reporting(E_ALL);
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// LANG::xml
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';

$H = new KvzHTML(array(
    'xml' => true,
));

$lorem  = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum';


$H->setOption('echo', true);
$H->xml(true, array(
    'version' => '2.0',
    'encoding' => 'UTF-16',
));
$H->setOption('echo', false);
echo $H->auth(
    $H->username('kvz') .
    $H->api_key(sha1('xxxxxxxxxxxxxxxx')) 
);
echo $H->request('request', array(
    '__cdata' => true,
));
?>