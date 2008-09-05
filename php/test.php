#!/usr/bin/php -q
<?php
/*
 * Requirements:
 *  pear install -f Testing_DocTest
*/

require_once "classes/KvzShell.php";

class KvzLib extends KvzShell {
    protected $_path = "";
    
    public function KvzLib($path=false) {
        if (!$path) {
            $this->log("Path: '$path' is empty", KvzLib::LOG_EMERG);
            return false;
        }
        
        if (!file_exists($path)) {
            $this->log("Path: '$path' does not exist", KvzLib::LOG_EMERG);
            return false;
        }
        
        $this->_path = $path;
        
        $cmds = array("pear", "phpdt");
        $this->initCommands($cmds, true);
    }
    
    public function test(){
        $x = $this->exeGlue("phpdt", $this->_path);
        echo implode("\n", $x);
    }
}

$KvzLib = new KvzLib(dirname(__FILE__));
$KvzLib->test();
?>