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
 * @version   SVN: Release: $Id: KvzShell.php 371 2009-07-24 13:36:32Z kevin $
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
     * Available log levels
     *
     * @var array
     */
    static protected $_logLevels = array(
        self::LOG_EMERG => "emerg",
        self::LOG_ALERT => "alert",
        self::LOG_CRIT => "crit",
        self::LOG_ERR => "err",
        self::LOG_WARNING => "warning",
        self::LOG_NOTICE => "notice",
        self::LOG_INFO => "info",
        self::LOG_DEBUG => "debug"
    );

    /**
     * Available PHP error levels and their meaning in POSIX loglevel terms
     *
     * @var array
     */
    static protected $_logPhpMapping = array(
        E_ERROR => self::LOG_ERR,
        E_WARNING => self::LOG_WARNING,
        E_PARSE => self::LOG_EMERG,
        E_NOTICE => self::LOG_DEBUG,
        E_CORE_ERROR => self::LOG_EMERG,
        E_CORE_WARNING => self::LOG_WARNING,
        E_COMPILE_ERROR => self::LOG_EMERG,
        E_COMPILE_WARNING => self::LOG_WARNING,
        E_USER_ERROR => self::LOG_ERR,
        E_USER_WARNING => self::LOG_WARNING,
        E_USER_NOTICE => self::LOG_DEBUG,
        E_RECOVERABLE_ERROR => self::LOG_CRIT,
    );

// Only as of PHP 5.3:
//        E_DEPRECATED => self::LOG_NOTICE,
//        E_USER_DEPRECATED  => self::LOG_NOTICE,


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
     * Holds options like enable_trace. Child options will be merged by
     * __contructor
     *
     * It's adviced to keep KvzShell's native option in lowercase format,
     * and to use camelizedVersions yourSelf to make a clear distinction
     *
     * @var array
     */
    protected $_options = array(
        'print_log_level' => KvzShell::LOG_INFO,
        'enable_trace' => false,
        'die_on_fail' => false,
        'die_on_nocli' => false,
        'merge_stderr' => false,
        'save_stderr' => false,
        'log_phperr' => false,
        'log_stderr' => false,
        'log_origin' => true,
        'log_file' => false,
        'log_prependtype' => true,
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
     * Holds errors of last command if save_stderr is set
     *
     * @var array
     */
    public $errors = array();

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
        // @todo: Merging Parent options fails to recurse

        $parentOptions = array();

        // Merge parent's possible options with own
        $parent        = get_parent_class($this);
        if (!empty($parent)) {
            $parentVars    = get_class_vars($parent);
            if (!empty($parentVars['_options'])) {
                $parentOptions = $parentVars['_options'];
            }
        }

        $this->_options = array_merge($parentOptions, $this->_options);

        $this->setOptions($options);
        
        if ($this->getOption('die_on_nocli') && php_sapi_name() !== 'cli') {
            $this->emerg('Please use CLI interface');
        }
        
        if ($this->getOption('log_stderr') && !$this->getOption('save_stderr')) {
            $this->setOption('save_stderr', true);
        }
        
        if ($this->getOption('log_phperr')) {
            set_error_handler(array($this, 'phpErrors'), E_ALL);
        }

        if ($this->getOption('log_file')) {
            if (!self::isWritable($this->getOption('log_file'))) {
                $this->err('Logfile %s is not writable!', $this->getOption('log_file'));
                return false;
            }
        }
    }

    /**
     * Catches PHP Errors and forwards them to log function
     *
     * @param integer $errno   Level
     * @param string  $errstr  Error
     * @param string  $errfile File
     * @param integer $errline Line
     *
     * @return boolean
     */
    public function phpErrors($errno, $errstr, $errfile, $errline)
    {
        // Ignore suppressed errors
        if (error_reporting() == 0) {
            return;
        }

        // Map PHP error level to System_Daemon log level
        if (empty(self::$_logPhpMapping[$errno])) {
            $this->warning('Unknown PHP errorno: '.$errno);
            $lvl = self::LOG_ERR;
        } else {
            $lvl = self::$_logPhpMapping[$errno];
        }

        // Log it
        $this->log('[PHP Error] '.$errstr, $lvl, $errfile, __CLASS__,
            __FUNCTION__, $errline);

        return true;
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
    public function setOption($optionName, $value) {
        if (!array_key_exists($optionName, $this->_options)) {
            $this->err("Unrecognized option: '%s'", $optionName);
            return null;
        }

        $this->_options[$optionName] = $value;
        return true;
    }



    /**
     * Retrieves option
     *
     * @param string $optionName
     * @param string $subName1   In case of array, let's you extract a nested item
     * 
     * @return mixed
     */
    public function getOption($optionName, $subName1 = null) {
        if (!array_key_exists($optionName, $this->_options)) {
            $this->err("Unrecognized option: '%s'", $optionName);
            return null;
        }

        $val = &$this->_options[$optionName];

        if ($subName1 && is_array($val)) {
            if (!array_key_exists($subName1, $val)) {
                $this->err("Unrecognized option: '[%s][%s]'", $optionName, $subName1);
                return null;
            }

            return $val[$subName1];
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
        $classNames = array(get_class($this), __CLASS__);
        
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
                $this->emerg("Command: '%s' ('%s') not found", $cmd, $path);
            } else {
                $this->warning("Command: '%s' ('%s') not found", $cmd, $path);
            }
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

    protected function _die($str, $exitCode = 0) {
        $this->out($str);
        exit($exitCode);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function debug($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_DEBUG);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function info($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_INFO);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function notice($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_NOTICE);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function warning($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_WARNING);
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
            foreach($args as $k=>$v) {
                if (is_array($v)) {
                    $args[$k] = print_r($v, true);
                }
            }
            $str = vsprintf($str, $args);
        }
        
        $class    = false;
        $function = false;
        $file     = false;
        $line     = false;
        if ($this->getOption('log_origin')) {
            if (function_exists("debug_backtrace") && ($file == false
                || $class == false || $function == false || $line == false)) {
                $dbg_bt   = @debug_backtrace();
                $class    = (isset($dbg_bt[2]["class"])?$dbg_bt[2]["class"]:"");
                $function = (isset($dbg_bt[2]["function"])?$dbg_bt[2]["function"]:"");
                $file     = basename($dbg_bt[1]["file"]);
                $line     = $dbg_bt[1]["line"];
            }
        }

        return $this->log($str, $level, $class, $function, $file, $line);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function err($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_ERR);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function crit($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_ERR);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function alert($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_ALERT);
    }

    /**
     * Shortcut to log
     *
     * @param string $str
     */
    public function emerg($str) {
        $args  = func_get_args();
        return $this->_logf($str, $args, KvzShell::LOG_EMERG);
    }

    /**
     * Logs a message.  Please use debug()|info()|etc instead though.
     *
     * @param string  $str
     * @param integer $level
     * @param string  $class
     * @param string  $function
     * @param string  $file
     * @param integer $line
     *
     * @return boolean
     */
    public function log($str, $level=KvzShell::LOG_INFO, $class = false, $function = false, $file = false, $line = false) {
        $str_level  = str_pad(KvzShell::$_logLevels[$level]."", 8, " ", STR_PAD_LEFT);
        $str_origin = '';
        if ($this->getOption('log_origin')) {
            $str_origin = sprintf('[f: %s, l:%s]', $file, $line);
        }

        if ($this->getOption('log_prependtype')) {
            $str = $str_level. ' ' . $str;
        }

        if ($level != KvzShell::LOG_INFO && $level != KvzShell::LOG_NOTICE) {
            $str = $str. ' '.$str_origin;
        }

        $this->logOut($str, $level);
        $this->logAppend($str, $level);
        
        if ($level < self::LOG_CRIT) {
            $this->warning('Can\'t continue after last event');
            trigger_error($str, E_USER_ERROR);
            $this->_die('Can\'t continue after last event', 1);
        }
        
        return true;
    }

    public function logAppend($str, $level) {
        if ($this->getOption('log_file')) {
            return file_put_contents($this->getOption('log_file'), $str."\n", FILE_APPEND);
        }
        return true;
    }
    
    public function logOut($str, $level) {
        if ($level <= $this->getOption('print_log_level')) {
            return $this->out($str);
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
    public function out($str, $newline = true) {
        if ($str === true) {
            $str = 'true';
        } else if ($str === false) {
            $str = 'false';
        } else if (is_array($str)) {
            $str = print_r($str, true);
        }
        
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

        if ($this->getOption('merge_stderr')) {
            $cmd .= ' 2>&1';
        } else if ($this->getOption('save_stderr')) {
            $errfile = tempnam('/tmp', 'kvzshell.stderr');
            $cmd .= ' 2>'.$errfile;
        }
        
        exec($cmd, $this->output, $this->return_var);

        // Load errors
        if ($this->getOption('save_stderr') && file_exists($errfile)) {
            $this->errors = file($errfile, FILE_IGNORE_NEW_LINES);
            @unlink($errfile);
        }
        if ($this->return_var === $this->errReturnVar) {
            if ($this->getOption('log_stderr')) {
                foreach($this->errors as $err) {
                    $this->err('Commandline error: '. $err);
                }
            }
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

        if (isset($_SERVER['PATH'])) {
            $possiblePaths = array_unique(array_merge(explode(':', $_SERVER['PATH']), $possiblePaths)); 
        }

        foreach ($possiblePaths as $possiblePath) {
            $testPath = $possiblePath."/".escapeshellcmd($cmd);
            $this->debug('trying %s', $testPath);
            if (file_exists($testPath)) {
                $this->debug('found %s', $testPath);
                return $testPath;
            }
        }

        return false;
    }

    /**
     * A 'better' is_writable. Taken from PHP.NET comments:
     * http://nl.php.net/manual/en/function.is-writable.php#73596
     * Will work in despite of Windows ACLs bug
     * NOTE: use a trailing slash for folders!!!
     * see http://bugs.php.net/bug.php?id=27609
     * see http://bugs.php.net/bug.php?id=30931
     *
     * @param string $path Path to test
     *
     * @return boolean
     */
    public static function isWritable($path)
    {
        if ($path{strlen($path)-1}=='/') {
            //// recursively return a temporary file path
            return self::isWritable($path.uniqid(mt_rand()).'.tmp');
        } else if (is_dir($path)) {
            return self::isWritable($path.'/'.uniqid(mt_rand()).'.tmp');
        }
        // check tmp file for read/write capabilities
        $rm = file_exists($path);
        $f  = @fopen($path, 'a');
        if ($f === false) {
            return false;
        }
        fclose($f);
        if (!$rm) {
            unlink($path);
        }
        return true;
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
            $this->emerg('Can\t include Console_Getopt. Please: pear install Console_Getopt');
            return false;
        }
        
        $cg = new Console_Getopt();
        $r = $cg->getopt($cg->readPHPArgv(), $short, $long);

        if (PEAR::isError($r)) {
            $this->emerg($r->message);
            return false;
        }

        list($options, $args) = $r;
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
        if (!is_array($params)) {
            if ($params){
                $params = $keyPrefix.$params;
            }
            return $params;
        }
        $arguments = '';
        foreach($params as $k=>$v) {
            if (is_array($v)) {
                foreach($v as $k1=>$v1) {
                    if (!is_numeric($k) && $v !== false) {
                        $arguments .= $keyPrefix.$k.' ';
                    }
                    $arguments .= $v1. ' ';
                }
            } else {
                if (!is_numeric($k) && $v !== false) {
                    $arguments .= $keyPrefix.$k.' ';
                }
                $arguments .= $v. ' ';
            }
        }
        return trim($arguments);
    }


}
class KvzShell_Exception extends Exception {

}
?>