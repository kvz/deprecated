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
        
    
    /**
     * Holds paths of commands
     *
     * @var array
     */
    protected $_cmds = array();
    
    /**
     * Holds output of last command
     * Usefull when exe has returned false on error, and you 
     * want to analyze the output.
     *
     * @var array
     */
    public $output = array();
    
    
    /**
     * Holds last command
     *
     * @var unknown_type
     */
    public $command = "";
    
    
    
    /**
     * Constructor
     *
     * @return KvzShell
     */
    public function KvzShell() {
        
    }
    
    
    
    /**
     * Tries to which all commands in $cmds, so later on, full
     * paths will be used when executing commands.
     *
     * @param array   $cmds
     * @param boolean $dieOnFail
     * 
     * @return boolean
     */
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
    
    /**
     * Logs a message
     *
     * @param string  $str
     * @param integer $level
     * 
     * @return boolean
     */
    public function log($str, $level=KvzShell::LOG_INFO) {
        echo $str."\n";
        
        if ($level < KvzShell::LOG_CRIT) {
            die();
        }
        
        return true;
    }
    
    /**
     * Wrapper around exe, will expect something in return, or will produce false
     *
     * @param string $cmd    Fully qualifed command
     * @param string $expect What to expect
     * @param string $mode   Type of expectation
     * 
     * @return mixed array on success or boolean on failure
     */
    public function exePect($cmd, $expect, $mode="LIKE") {
        if (($x = $this->exe($cmd)) === false) {
            return false;
        }
        
        $xn           = implode("\n", $x);
        $expect_quote = preg_quote($expect);
        
        switch ($mode) {
            case "REGEX_MULTILINE":
                $pattern = '@'.$expect.'@Umi';
                if (!preg_match($pattern, $xn)) {
                    return false; 
                }
                break;
            case "LIKE":
                $pattern = '@(.*)'.preg_quote($expect).'(.*)@Umi';
                if (!preg_match($pattern, $xn)) {
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
    
    /**
     * Combines arguments to form a fully qualified command, then forwards it to exe
     *
     * @return mixed array on success or boolean on failure
     */
    public function exeGlue(){
        $args = func_get_args();
        $cmd = implode(" ", $args);
        return $this->exe($cmd);
    }
    
    /**
     * Combines arguments to form a fully qualified command, then forwards it to exePect
     * Only works with exePects' LIKE mode
     *
     * @return mixed array on success or boolean on failure
     */
    public function exePectGlue(){
        $args = func_get_args();
        $expect = array_pop($args);
        $cmd = implode(" ", $args);
        return $this->exePect($cmd, $expect);
    }    
    
    /**
     * Executes commands and returns false or output. 
     * Uses fullpath if command has been initialized with initCommands
     *
     * @param string $cmd
     * 
     * @return mixed array on success or boolean on failure
     */
    public function exe($cmd) {
        $parts = preg_split("[\s]", $cmd, null, PREG_SPLIT_NO_EMPTY);
        $base  = array_shift($parts);
        $cmdE  = $cmd;
        
        if (isset($this->_cmds[$base])) {
            $cmdE = $this->_cmds[$base] ." ". implode(" ", $parts); 
        } else {
            if (isset($this->_cmds) && is_array($this->_cmds) && count($this->_cmds)) {
                $this->log("Command: ".$cmd." has not been initialized yet, but other commands have.", KvzShell::LOG_WARNING);
            }
        }
        
        return $this->_exe($cmdE);
    }
    
    /**
     * Main exe function. Used internally by all other function 
     *
     * @param unknown_type $cmd
     * 
     * @return mixed array on success or boolean on failure
     */
    protected function _exe($cmd) {
        $this->command = $cmd;
        exec($cmd, $o, $r);
        $this->output = $o;
        if ($r != 0) {
            return false;
        }
        return $o;
    }
    
    /**
     * Tries to locate command and saves exact location for later use by exe
     *
     * @param string $cmd
     * @param boolean $dieOnFail
     * 
     * @return boolean
     */
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