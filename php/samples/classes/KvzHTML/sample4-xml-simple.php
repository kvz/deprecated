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

$E = new KvzHTML(array(
    'xml' => true,
    'echo' => true,
));

$E->xml();

$E->auth();
    $E->username('kvz');
    $E->api_key(sha1('xxxxxxxxxxxxxxxx'));
$E->auth(false);

$E->server_reboot();
    $E->dry_run(null);
    $E->server_id(888);
$E->server_reboot(false);
?>