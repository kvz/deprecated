#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/init.inc.php';

$Builder = new Builder(array(
    'log-file' => '/var/log/builder.log',
    'log-file-level' => 'info',
    'app-root' => DIR_BUILDER_ROOT,
));

$Builder->indexConfig('/etc/submin/builder/config.php', $Builder->getArgs($argv));

if (($pwstring = $Builder->getCfg('setpassword'))) {
    list($username, $password) = explode(':', $pwstring);
    if (!$Builder->setPassword($username, $password)) {
        exit(1);
    }
}

$Builder->createHomes();

$Builder->createApacheConf();

$Builder->createIndexHtml();
?>