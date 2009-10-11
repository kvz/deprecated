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

$cdata = array(
    '__cdata' => true,
);

$H->setOption('echo', true);
$H->xml(true, array(
    'version' => '2.0',
    'encoding' => 'UTF-16',
));
$H->setOption('echo', false);
echo $H->auth(
    $H->username('kvz', $cdata) .
    $H->api_key(sha1('xxxxxxxxxxxxxxxx'), $cdata)
);

echo $H->users_list(true);
    echo $H->users(true, array('type' => 'array'));
        echo $H->user(
            $H->id(442, $cdata) .
            $H->name('Jason Shellen', $cdata) .
            $H->screen_name('shellen', $cdata) .
            $H->location('iPhone: 37.889321,-122.173345', $cdata) .
            $H->description('CEO and founder of Thing Labs, makers of Brizzly! Former Blogger/Google dude, father of two little dudes.', $cdata)
        );
    echo $H->users(false);
echo $H->users_list(false);
?>