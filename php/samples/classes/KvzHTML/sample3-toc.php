<?php
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';

$E = new KvzHTML(array(
    'track_toc' => true,
    'link_toc' => true,
    'echo' => true,
    'buffer' => true,
));

$lorem  = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum';

$E->h1('New application');
$E->p($lorem);
$E->h2('Users');
$E->blockquote($lorem);
$E->h3('Permissions');
$E->p($lorem);
$E->h4('General Concept');
$E->p($lorem);
$E->h4('Exceptions');
$E->p($lorem);
$E->h3('Usability');
$E->p($lorem);

$toc = $E->getToc();
$E->h1('Table of Contents');
echo $toc;
echo $E->getBuffer();
?>