<?php
/**
 * Contains a lot of methods that ease up working with a shell
 *
 * PHP version 5
 *
 * @package   KvzShell
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/code/
 */
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
     * Holds options like enable_trace
     *
     * @var array
     */
    protected $_options = array(
        'enable_trace' => false,
        'die_on_fail' => false,
        'die_on_nocli' => false,
        'merge_stderr' => false,
    );
        
    /**
     * Holds output of last command
     * Usefull when exe has returned false on error, and you 
     * want to analyze the output.
     *
     * @var array
     */
    public $output = array();
    
    /**
     * Holds return_var of last command
     * Usefull when exe has returned false on error, and you 
     * want to analyze the output.
     *
     * @var array
     */
    public $return_var = 0;    

    /**
     * Let's the class know what return var means an error
     *
     * @var integer
     */
    public $errReturnVar = 1;

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
    public function __construct($options = array()) {
        // Merge parent's possible options with own
        $parent        = get_parent_class($this);
        $parentVars    = get_class_vars($parent);
        $parentOptions = $parentVars['_options'];

        $this->_options = array_merge($parentOptions, $this->_options);

        $this->setOptions($options);
        
        if ($this->getOption('die_on_nocli') && php_sapi_name() !== 'cli') {
            $this->emerg('Please use CLI interface');
        }
    }
    
    
    /**
     * Sets option array with options like enable_trace
     *
     * @param array $options
     * 
     * @return boolean
     */
    public function setOptions($options = array()) {
        foreach($options as $k=>$v){
            if (!$this->setOption($k, $v)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Sets option
     *
     * @param array $options
     *
     * @return boolean
     */
    public function setOption($name, $value) {
        if (!isset($this->_options[$name])) {
            $this->err("Option: ".$name." does not exist");
            return false;
        }

        $this->_options[$name] = $value;
        return true;
    }



    /**
     * Retrieves option
     *
     * @param string $optionName
     * 
     * @return mixed
     */
    public function getOption($optionName) {
        if (!isset($this->_options[$optionName])) {
            $this->err("Option: ".$optionName." has not been initialized!");
            return null;
        }
        
        return $this->_options[$optionName];
    }
    
    /**
     * Retrieves options
     *
     * @return array
     */
    public function getOptions() {
        return $this->_options;
    }
        
    
    /**
     * Retrieves trace of refering code  
     *
     * @return array
     */
    public function getTrace() {
        if (!$this->getOption("enable_trace")) {
            $this->warning("Tracing not enabled. Set the enable_trace option. ");
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
        if (!$this->getOption("enable_trace")) {
            return false;
        }
        
        $traces     = debug_backtrace();
        $use_trace  = array();
        $prev_trace = array();
        
        // Both original & extended classnames should be excluded from
        // our search for calling boject
        $classNames = array(get_class($this), get_class(__CLASS__));
        
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
    public function initCommand($cmd = "", $path = null, $dieOnFail=null) {
        if ($dieOnFail === null) {
            $dieOnFail = $this->getOption('die_on_fail');
        }

        if ($path === null && $cmd) {
            $path = $this->_which($cmd);
        }
        
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
        return $path;
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
    public function initCommands($cmds, $dieOnFail = null) {
        if ($dieOnFail === null) {
            $dieOnFail = $this->getOption('die_on_fail');
        }

        foreach($cmds as $cmd) {
            $path = $this->_which($cmd);
            
            if (false === $this->initCommand($cmd, $path, $dieOnFail)) {
                return false;
                break;
            }
        }
        return true;
    }

    protected function _die($str) {
        $this->out($str);
        die();
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function debug($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_DEBUG);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function info($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_INFO);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function notice($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_NOTICE);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function warning($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_WARNING);
    }

    /**
     * Support sprintf formatted strings
     * 
     * @param <type> $str
     * @param <type> $args
     * @param <type> $level
     */
    public function _logf($str, $args, $level) {
        if (is_array($args) && !empty($args)) {
            $str   = array_shift($args);
        }
        if (is_array($args)) {
            $str = vsprintf($str, $args);
        }

        $this->log($str, $level);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function err($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_ERR);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function crit($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_ERR);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function alert($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_ALERT);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function emerg($str) {
        $args  = func_get_args();
        $this->_logf($str, $args, KvzShell::LOG_EMERG);
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
        $this->out($str);
        
        if ($level < self::LOG_CRIT) {
            $this->_die('Can\'t continue after last event');
        }
        
        return true;
    }

    /**
     * Echo like function
     *
     * @staticvar resource $stream
     * @param     String   $str
     * @param     boolean  $newline
     * 
     * @return    boolean
     */
	public static function out($str, $newline = true) {
		if ($newline) {
			$str = $str."\n";
		}

		static $stream;
		if (!is_resource($stream)) {
			$stream = fopen('php://stdout','w');
		}
		return fwrite($stream, $str);
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
    public function exe($cmd, $dieOnFail = null) {
        if ($dieOnFail === null) {
            $dieOnFail = $this->getOption('die_on_fail');
        }
        $parts = preg_split("[\s]", $cmd, null, PREG_SPLIT_NO_EMPTY);
        $base  = basename(array_shift($parts));
        $cmdE  = $cmd;

        if (isset($this->_cmds[$base])) {
            $cmdE = $this->_cmds[$base] ." ". implode(" ", $parts);
        } else {
            if (isset($this->_cmds) && is_array($this->_cmds) && count($this->_cmds)) {
                // Command has not been initialized yet, but other commands have
                if (false === $cmdE = $this->initCommand($base)) {
                    $this->log("Command: '".$cmd."' ('".$path."') not found", self::LOG_ERR);
                }
            }
        }

        return $this->_exe($cmdE, $dieOnFail);
    }

    /**
     * Main exe function. Used internally by all other functions.
     * Returns false if return_var is errReturnVar
     *
     * @param string $cmd
     *
     * @return mixed array on success or boolean on failure
     */
    protected function _exe($cmd, $dieOnFail = null) {
        if ($dieOnFail === null) {
            $dieOnFail = $this->getOption('die_on_fail');
        }
        //$this->log($cmd, self::LOG_DEBUG);
        $this->_setTrace();

        $this->output  = "";
        $this->command = $cmd;

        if ($this->getOptions('merge_stderr')) {
            $cmd .= ' 2>&1';
        }

        exec($cmd, $this->output, $this->return_var);
        if ($this->return_var === $this->errReturnVar) {
            if ($dieOnFail) {
                $this->emerg('Unable to execute: '.$cmd);
            }
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
        if (file_exists($cmd)) {
            return $cmd;
        }

        $possiblePaths = array(
            "/usr/local/sbin",
            "/usr/local/bin",
            "/usr/sbin",
            "/usr/bin",
            "/sbin",
            "/bin",
            "/usr/games",
        );

        foreach ($possiblePaths as $possiblePath) {
            $testPath = $possiblePath."/".escapeshellcmd($cmd);
            if (file_exists($testPath)) {
                return $testPath;
            }
        }

        return false;
    }

    public function wget($url, $path, $dieOnFail = null) {
        if ($dieOnFail === null) {
            $dieOnFail = $this->getOption('die_on_fail');
        }
        
        if (!$temp = tempnam('/tmp', basename($path))) {
            $this->log("Unable to create tempfile", $dieOnFail ? self::LOG_EMERG : self::LOG_ERR);
            return false;
        }

        $cmd = 'wget -qO- "'.$url.'" > "'.$temp.'" && mv "'.$temp.'" "'.$path.'"';
        return $this->exe($cmd);
    }

    public function crontabAdd($command, $timeschedule) {

        $oldErrReturnVar    = $this->errReturnVar;
        $this->errReturnVar = 99;

        // Get & Filter
        $crons = array();
        if (false === $this->exeGlue("crontab", "-l", "|", $this->_which("grep")," -v '".addslashes($command)."'")) {
            $this->debug("No current crontab listing");
        }
        $crons = $this->output;

        // Add
        $crons[] = $timeschedule . " ". $command;

        // Set
        if (false === $this->exeGlue($this->_which("echo"), '"'.addslashes(implode("\n", $crons)).'"', "|", $this->_which("crontab"), "-")) {
            $this->err("Unable to set crontab");
            return false;
        }
        
        $this->debug("Crontab updated");

        $this->errReturnVar = $oldErrReturnVar;

        return true;
    }



    /**
     * Uses PEAR dependency: Console_GetOps to return an array of commandline
     * arguments & options
     *
     * @author Felix Geisendoerfer
     * @param <type> $short
     * @param <type> $long
     * 
     * @return array
     */
	public function getArguments($short, $long = null) {
        if (!@include_once("Console/Getopt.php")) {
            $this->crit('Can\t include Console_Getopt. Please: pear install Console_Getopt');
            return false;
        }
        
		$cg = new Console_Getopt();
		list($options, $args) = $cg->getopt($cg->readPHPArgv(), $short, $long);
		$opt = array();
		foreach ($options as $option) {
			list($key, $val) = $option;
			$val = is_null($val)
				? true
				: $val;

			$opt[$key] = (!isset($opt[$key]))
				? $val
				: array_merge((array)$opt[$key], (array)$val);
		}
		return compact('opt', 'args');
	}

    /**
     * Takes an array and returns an argument string
     *
     * @param array  $params
     * @param string $keyPrefix
     *
     * @return string
     */
    function argumentize($params, $keyPrefix = '--') {
        $arguments = '';
        foreach($params as $k=>$v) {
            if (!is_numeric($k)) {
                $arguments .= $keyPrefix.$k.' ';
            }
            $arguments .= $v. ' ';
        }
        return trim($arguments);
    }


}
class KvzShell_Exception extends Exception {

}
?>