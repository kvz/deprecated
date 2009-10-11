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
    'track_toc' => true,
    'link_toc' => false,
    'echo' => true,
    'buffer' => true,
));

$E->h1('New application');
    $E->p($E->loremIpsum);
$E->h2('Users');
    $E->blockquote($E->loremIpsum);
$E->h3('Permissions');
    $E->p($E->loremIpsum);
$E->h4('General Concept');
    $E->p($E->loremIpsum);
$E->h4('Exceptions');
    $E->p($E->loremIpsum);
$E->h3('Usability');
    $E->ul(); // An empty body will just open the tag: <ul>
        $E->li('Point 1');
        $E->li('Point 2');
        $E->li();
            $E->span('Point 3');
            $E->br(null);  // NULL will make a self closing tag: <br />
        $E->li(false);
    $E->ul(false);  // False will close the tag: </ul>

// Turn off document buffering
$E->setOption('buffer', false);
$toc      = $E->getToc();
$document = $E->getBuffer();

$E->h1('Table of Contents');
echo $toc;
echo $document;
?>