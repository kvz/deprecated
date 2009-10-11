<?php
error_reporting(E_ALL);
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';

// I find it easy to work with 2 instances.
//     One that will echo directly: $E
// and One that supports nesting: $H
$H = new KvzHTML();
$E = new KvzHTML(array('echo' => true, 'buffer' => true, 'tidy' => true));

// To save you even more typing. The following tags
// have an inconsistent interface:
// a, img, css, js

$E->html();
    $E->head(
        $H->title('Report') .
        $H->style('
            div.page {
                font-family: helvetica;
                font-size: 12px;
                page-break-after: always;
                min-height: 1220px;
                width: 830px;
            }
        ') .
        $H->css('/css/style.js') .
        $H->js('/js/jquery.js')
    );

    // Page 1
    $E->page(true, array('style' => array(
        'page-break-before' => 'always',
    )));
    
    $E->h1('Report') .

        $E->p(
            $H->a('http://true.nl', 'Visit our homepage') .
            $H->img('http://true.truestatic.nl/pivotx/templates/true/img/logo.gif')
        );
        
        $E->ul(
            $H->li('Health') .
            $H->li('Uptime') .
            $H->li('Logs') .
            $H->li('Recommendations')
        );
    $E->page(false);

    // Page 2
    $E->page();
        $E->float($H->img('http://en.gravatar.com/userimage/3781109/874501515fabcf6069d64c626cf8e4f6.png'));
        $E->float($H->img('http://en.gravatar.com/userimage/3781109/874501515fabcf6069d64c626cf8e4f6.png'));
        $E->clear();
    $E->page(false);

    // Page 3
    $E->page(
        $H->h2('Warnings') .
        $H->p('Disk space', array('class' => 'warning'))
    );

$E->html(false);

echo $E->getBuffer();
?>