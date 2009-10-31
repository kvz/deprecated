<?php
return ($config = array(
    'rpc_url' => 'http://xxx.wordpress.com/xmlrpc.php',
    'rpc_user' => 'xxx',
    'rpc_pass' => 'pass',
    'schema' => array(10, 11, 14, 19, 20),
    'sources' => array(
        'http://feeds.delicious.com/v2/rss/xxx?count=50' => array(
            'title' => 'Delicious',
            'language' => 'en',
        ),
    ),
));
?>