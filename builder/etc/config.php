<?php
return array(
    'authzFile' => '/data/submin/authz',
    'userFile' => '/data/submin/userproperties.conf',
    'buildFile' => '/etc/submin/build.php',

    'ipFile' => '/etc/submin/builder_ipmap',
    'vhostTemplate' => '/etc/submin/builder_template.vhost',

    'devGroup' => 'developers',
    'sysWebGroup' => 'www-data',
    'maindomain' => 'example.com',
    'basehome' => '/home',
    'devDomain' => 'dev.example.com',
    'svnUrl' => 'https://dev.example.com/svn/%s',
    'webUrl' => 'http://%s:%s',

    'baseip' => '123.123.123.100',
    'baseport' => '8000',

    'logFile' => '/var/log/submin.log',
);
?>