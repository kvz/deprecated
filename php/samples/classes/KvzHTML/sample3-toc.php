<?php
error_reporting(E_ALL);
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';

// Some options:
// - create a ToC
// - don't automatically create links for ToC navigation
// - echo output, don't return
// - save all echoed output in a buffer
// - Don't automatically Tidy the output (btw, only works with buffer on)
$E = new KvzHTML(array(
    'track_toc' => true,
    'link_toc' => false,
    'echo' => true,
    'buffer' => true,
    'tidy' => false,
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
        $E->strong('Point 3');
        $E->br(null);  // NULL will make a self closing tag: <br />
        $E->span('Has some implications.');
    $E->li(false);
$E->ul(false);  // False will close the tag: </ul>

// Save both chucks so further KvzHTML calls
// wont impact them anymore
$toc      = $E->getToc();
$document = $E->getBuffer();

// Print a heading that says TOC
$E->h1('Table of Contents', array('__buffer' => false));

// Print toc
echo $toc;

// Print original document
echo $document;
?>