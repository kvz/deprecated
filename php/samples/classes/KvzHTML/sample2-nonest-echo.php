<?php
error_reporting(E_ALL);
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';
$E = new KvzHTML(array(
    'echo' => true,
));

$E->p();
    $E->span('You dont need to nest tags if you dont want to.');
    $E->br(null);
    $E->span('KvzHTML is flexible.');
$E->p(false);

$E->ul();
    $E->li('Leaving a tag empty will just result in an open tag in HTML');
    $E->li('Close tags with FALSE');
    $E->li('For selfclosing tags like BR, use NULL');
$E->ul(false);
?>