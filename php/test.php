#!/usr/bin/php -q
<?php
/*
 * Requirements:
 *  pear install -f Testing_DocTest
*/

require_once "classes/KvzLib.php";
 
$KvzLib = new KvzLib(dirname(__FILE__));
$KvzLib->test();
?>