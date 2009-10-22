#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/init.inc.php';

$Builder = new Builder(array(
    'log-file' => '/var/log/builder.log',
));

if (!isset($skipConf)) {
    $config = $Builder->indexConfig('/etc/submin/builder/config.php');
}

$Builder->setConfig(array_merge($config, $Builder->getArgs($argv)));

if (!empty($config['setpassword'])) {
    list($username, $password) = explode(':', $config['setpassword']);
    if (!$Builder->setPassword($username, $password)) {
        exit(1);
    }
}

$Builder->createHomes();

$Builder->createApacheConf();

$Builder->createIndexHtml();
?>