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

echo $H->xml(
    $H->auth(
        $H->username('kvz') .
        $H->api_key(sha1('xxxxxxxxxxxxxxxx'))
    ) .
    $H->server_reboot(
        $H->dry_run(null) .
        $H->hostname('www1.example.com') .
        $H->server_id(888)
    )
);
?>