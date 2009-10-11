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

$cdataOpts = array('__cdata' => true);

$H->setOption('echo', true);
$H->xml(true, array(
    'version' => '2.0',
    'encoding' => 'UTF-16',
));
$H->setOption('echo', false);
echo $H->auth(
    $H->username('kvz', $cdataOpts) .
    $H->api_key(sha1('xxxxxxxxxxxxxxxx'), $cdataOpts)
);

echo $H->users_list(true);
    echo $H->users(true, array('type' => 'array'));
        echo $H->user(
            $H->id(442, $cdataOpts) .
            $H->name('Jason Shellen', $cdataOpts) .
            $H->screen_name('shellen', $cdataOpts) .
            $H->location('iPhone: 37.889321,-122.173345', $cdataOpts) .
            $H->description('CEO and founder of Thing Labs, makers of Brizzly! Former Blogger/Google dude, father of two little dudes.', $cdataOpts)
        );
    echo $H->users(false);
echo $H->users_list(false);
?>