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
     * Holds trace of refering code
     *
     * @var array
     */
    protected $_trace = array();
    
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
    public function KvzShell($options = false) {
        $this->setOptions($options);
    }
    
    
    /**
     * Sets option array with options like enable_trace
     *
     * @param array $options
     * 
     * @return boolean
     */
    public function setOptions($options=false) {
        if (!$options) $options = array();
        if (!isset($options["enable_trace"])) $options["enable_trace"] = false;
        
        $this->_options = $options;
        return true;
    }
    
    /**
     * Retrieves trace of refering code  
     *
     * @return array
     */
    public function getTrace() {
        if (!$this->_options["enable_trace"]) {
            $this->log("Tracing not enabled. Set the enable_trace option. ", self::LOG_WARNING);
            return false;
        }
        
        if (!is_array($this->_trace)) {
            return false;
        }
        
        if (!count($this->_trace)) {
            return false;
        }
        
        return $this->_trace;
    }
    
    /**
     * Finds trace of refering code and saves it internally
     *
     * @return boolean
     */
    protected function _setTrace() {
        if (!$this->_options["enable_trace"]) {
            return false;
        }
        
        $traces     = debug_backtrace();
        $use_trace  = array();
        $prev_trace = array();
        
        // Both original & extended classnames should be excluded from
        // our search for calling boject
        $classNames = array(get_class($this), get_class());
        
        foreach ($traces as $i=>$trace) {
            if (!isset($trace["class"]) || !in_array($trace["class"], $classNames)) {
                // This is the last position outside our class. So the refering code.
                $use_trace = $prev_trace;
                break; 
            }
            // Remember previous trace.
            // We don't want to work with (i-1);
            $prev_trace = $trace;
        }
        
        if (!count($use_trace)) {
            // Only internal class info in trace
            return false;
        }
        
        $use_trace["filebase"] = basename($use_trace["file"]); 
        $this->_trace = $use_trace;
        
        return true;
    }
     
    /**
     * Enter description here...
     *
     * @param string  $cmd
     * @param string  $path
     * @param boolean $dieOnFail
     * 
     * @return boolean
     */
    public function initCommand($cmd="", $path=false, $dieOnFail=false) {
        if (!$cmd || !$path || !is_file($path)) {
            if ($dieOnFail) {
                $level = self::LOG_EMERG;
            } else {
                $level = self::LOG_WARNING;
            }
            $this->log("Command: '".$cmd."' ('".$path."') not found", $level);
            return false;
        }
        $this->_cmds[$cmd] = $path;
        return true;
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
            $path = $this->_which($cmd);
            
            if (false === $this->initCommand($cmd, $path, $dieOnFail)) {
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
        
        if ($level < self::LOG_CRIT) {
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
        $base  = basename(array_shift($parts));
        $cmdE  = $cmd;
        
        if (isset($this->_cmds[$base])) {
            $cmdE = $this->_cmds[$base] ." ". implode(" ", $parts); 
        } else {
            if (isset($this->_cmds) && is_array($this->_cmds) && count($this->_cmds)) {
                $this->log("Command: ".$base." has not been initialized yet, but other commands have.", self::LOG_WARNING);
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
        //$this->log($cmd, self::LOG_DEBUG);
        $this->_setTrace();
        
        $this->output  = "";
        $this->command = $cmd;
        exec($cmd, $this->output, $r);
        if ($r != 0) {
            return false;
        }
        return $this->output;
    }
    
    /**
     * Tries to locate command and saves exact location for later use by exe
     *
     * @param string $cmd
     * 
     * @return boolean
     */
    protected function _which($cmd) {
        $cmdW = "/usr/bin/which ".escapeshellcmd($cmd);
        if (($o = $this->_exe($cmdW)) === false) {
            return false;
        }
        
        return trim(implode("\n", $o));
    }
}
?>