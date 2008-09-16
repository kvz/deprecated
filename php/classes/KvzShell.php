<?php
class KvzShell_Exception extends Exception {
    
}
class KvzShell {
    
    /**
     * System is unusable
     */
    const LOG_EMERG = 0;
    
    /**
     * Immediate action required
     */ 
    const LOG_ALERT = 1;
    
    /**
     * Critical conditions
     */
    const LOG_CRIT = 2;
    
    /**
     * Error conditions
     */
    const LOG_ERR = 3;
    
    /**
     * Warning conditions
     */
    const LOG_WARNING = 4;
    
    /**
     * Normal but significant
     */
    const LOG_NOTICE = 5;
    
    /**
     * Informational
     */
    const LOG_INFO = 6;
    
    /**
     * Debug-level messages
     */
    const LOG_DEBUG = 7;
        
    
    protected $_cmds = array();
    protected $_path = "";
    
    public $output = array();
    public $command = "";
    
    
    public function KvzShell() {
    }
    
    public function initCommands($cmds = false, $dieOnFail=false) {
        if (!$cmds) $cmds = array();
        foreach($cmds as $cmd) {
            if (!$this->_which($cmd, $dieOnFail)) {
                return false;
                break;
            }
        }
        return true;
    }
    
    public function log($str, $level=KvzShell::LOG_INFO) {
        echo $str."\n";
        
        if ($level < KvzShell::LOG_CRIT) {
            die();
        }
        
        return true;
    }
    
    public function exePect($cmd, $expect, $mode="REGEX_MULTILINE") {
        if (($x = $this->exe($cmd)) === false) {
            return false;
        }
        
        $xn           = implode("\n", $x);
        $expect_quote = preg_quote($expect);
        
        switch ($mode) {
            case "REGEX_MULTILINE":
                if (!preg_match('/'.$expect.'/Umi', $xn)) {
                    return false; 
                }
                break;
            default:
                throw new KvzShell_Exception("Unsupported mode: $mode");
                return false;
                break;
        }
        
        return $x;
        
    }
    
    public function exeGlue(){
        $args = func_get_args();
        $cmd = implode(" ", $args);
        return $this->exe($cmd);
    }
    
    public function exe($cmd) {
        $parts = preg_split("[\s]", $cmd, null, PREG_SPLIT_NO_EMPTY);
        $base  = array_shift($parts);
        $cmdE  = $cmd;
        if (isset($this->_cmds[$base])) {
            $cmdE = $this->_cmds[$base] ." ". implode(" ", $parts); 
        } 
        
        return $this->_exe($cmdE);
    }
    
    protected function _exe($cmd) {
        #$this->log($cmd, KvzShell::LOG_DEBUG);
        
        $this->command = $cmd;
        exec($cmd, $o, $r);
        $this->output = $o;
        if ($r != 0) {
            return false;
        }
        return $o;
    }
    
    protected function _which($cmd, $dieOnFail=false) {
        $cmdW = "/usr/bin/which ".escapeshellcmd($cmd);
        if (($o = $this->_exe($cmdW)) === false) {
            if ($dieOnFail) {
                $this->log("Command: '$cmd' not found", KvzShell::LOG_EMERG);
            }
            return false;
        }
        
        $this->_cmds[$cmd] = implode("\n", $o);
        
        return true;
    }
}
?>