<?php
error_reporting(E_ALL);
if (!defined('DIR_KVZLIB')) {
    define('DIR_KVZLIB', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
}
?>
// Sample starts here
<?php
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';

// These are the default options, so might
// as well have initialized KvzHTML with an
// empty first argument
$H = new KvzHTML(array(
    'xhtml' => true,
    'track_toc' => false,
    'link_toc' => true,
    'indentation' => 4,
    'newlines' => true,
    'echo' => false,
    'buffer' => false,
    'xml' => false,
    'tidy' => false,
));

echo $H->html(
    $H->head(
        $H->title('My page')
    ) .
    $H->body(
        $H->h1('Important website') .
        $H->p('Welcome to our website.') .
        $H->h2('Users') .
        $H->p('Here\'s a list of current users:') .
        $H->table(
            $H->tr($H->th('id') . $H->th('name') . $H->th('age')) .
            $H->tr($H->td('#1') . $H->td('Kevin van Zonneveld') . $H->td('26')) .
            $H->tr($H->td('#2') . $H->td('Foo Bar') . $H->td('28'))
        )
    )
);
?>