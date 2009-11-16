<?php
/**
 * Some functions i'd like to have in any shell class
 *
 * @author kvz
 */
class Base {
    protected $_options = array(
        'log-print-level' => 'info',
        'log-file-level' => 'debug',
        'log-memory-level' => 'debug',
        'log-break-level' => 'err',
        'log-mark-trace' => false,
        'log-file' => '/var/log/egg.log',
        'log-section-open' => array('section_open'),
        'log-section-close' => array('section_close'),
        'log-trail-level' => 'trail',
        'app-root' => '',
        'class-autobind' => false,
        'class-autosetup' => false,
    );

    public $logs;

    /**
     * Allows to automatically instantiate other Objects
     * re-using the $options array for their constructors
     *
     * @var array
     */
    public $register = array();

    /**
     * Apparently not even Reflection can get a classes
     * own methods.
     * Resorting to source preg_matching until a better
     * solution can be found.
     *
     * @param <type> $filename
     * @param <type> $search
     * 
     * @return <type>
     */
    public function ownMethods($filename, $search = null) {
        if (!file_exists($filename)) {
            return $this->err('Class source: %s not found', $filename);
        }
        
        $buf = file_get_contents($filename, FILE_IGNORE_NEW_LINES);
        if (!preg_match_all('/^[\t a-z]*function\s+?(.+)\s*\(/ismU', $buf, $matches)) {
            return array();
        }
        $methods = $matches[1];
        if ($search !== null) {
            return in_array($search, $methods);
        }
        
        return $methods;
    }

    public function conclude($res) {
        if (false === $res) {
            return 'Fail';
        } else if (null === $res) {
            return 'Skip';
        } else {
            return 'Okay';
        }
    }

    public function out($str) {
        $args = func_get_args();
        $str  = array_shift($args);
        echo vsprintf($str, $args);
        echo "\n";
    }
    
    public function abbr($str, $cutAt = 30, $suffix = '...') {
        if (strlen($str) <= 30) {
            return $str;
        }

        $canBe = $cutAt - strlen($suffix);

        return substr($str, 0, $canBe). $suffix;
    }

    public function indent($lines, $indentation = 4, $newlines = "\n") {
        // Setup Input
        if (is_string($lines)) {
            $lines = explode("\n", $lines);
        }
        if (!is_array($lines)) {
            // Neither string nor array
            // give this stuff back before accidents happen
            return $lines;
        }

        // Lot of ways to set indent
        if (is_numeric($indentation)) {
            $indent = str_repeat(' ', $indentation);
        } elseif (is_string($indentation)) {
            $indent = $indentation;
        } elseif ($indentation === true || $indentation === null) {
            $indent = '    ';
        } elseif ($indentation === false) {
            $indent = '';
        } else {
            return $this->err('Indendation can be a lot of things but not "%s"', $indentation);
        }

        // Indent
        foreach ($lines as &$line) {
            $line = $indent . $line;
        }

        // Newline
        return join($newlines, $lines);
    }

    public function sensible($arguments) {
        if (!is_array($arguments)) {
            if (!is_numeric($arguments) && !is_bool($arguments)) {
                $arguments = "'".$arguments."'";
            }
            return $arguments;
        }
        $arr = array();
        foreach($arguments as $key=>$val) {
            if (is_array($val)) {
                $val = json_encode($val);
            } elseif (!is_numeric($val) && !is_bool($val)) {
                $val = "'".$val."'";
            }

            $val = $this->abbr($val);
            
            $arr[] = $key.': '.$val;
        }
        return join(', ', $arr);
    }

    /**
     * Taken from CakePHP's Set Class
     * array_merge & $this->_merge is just never what you need
     *
     * @param <type> $arr1
     * @param <type> $arr2
     * @return <type>
     */
    public function merge($arr1, $arr2 = null) {
        $args = func_get_args();
        $r = (array)current($args);
        while (($arg = next($args)) !== false) {
            foreach ((array)$arg as $key => $val)     {
                if (is_array($val) && isset($r[$key]) && is_array($r[$key])) {
                    $r[$key] = $this->merge($r[$key], $val);
                } elseif (is_int($key)) {
                    $r[] = $val;
                } else {
                    $r[$key] = $val;
                }
            }
        }
        return $r;
    }

    public function camelize($str) {
        // for now a ucfirst will do.
        // can take cake inflector later if necessary
        return ucfirst($str);
    }

    public function trace($strip = 2, $dump = false, $array=false) {
        $want = array(
            'file',
            'line',
            'args',
            'class',
            'function',
        );

        $traces = array();
        $debug_traces_orig = debug_backtrace();
        $debug_traces = $debug_traces_orig;
        array_splice($debug_traces, 0, $strip);
        foreach($debug_traces as $debug_trace) {
            $debug_trace = array_intersect_key($debug_trace, array_flip($want));
            $debug_trace['file'] = $this->inPath(@$debug_trace['file']);

            if ($array) {
                $traces[] = $debug_trace;
            } else {
                $traces[] = sprintf('%20s#%-4s %12s->%s()',
                    @$debug_trace['file'],
                    @$debug_trace['line'],
                    @$debug_trace['class'],
                    @$debug_trace['function']
                );
            }

        }

        $traces = array_reverse($traces);

        if ($dump) {
            #prd(compact('strip', 'dump', 'traces', 'debug_traces', 'debug_traces_orig'));
            foreach($traces as $trace) {
                $this->out($trace);
            }
        }

        return $traces;
    }

    public function mark($level = 'debug') {
        if (empty($this->_options['log-mark-trace'])) {
            return null;
        }

        $traces = $this->trace(2, false, true);
        $trace  = array_pop($traces);
        return call_user_func(array($this, $level), '=Fired: %s->%s(%s)',
            @$trace['class'],
            @$trace['function'],
            $this->sensible(@$trace['args'][0])
        );
    }

    public function inPath($filename) {
        if (is_string($filename)) {
            $filename = str_replace($this->_options['app-root'], '', $filename);
        }

        return $filename;
    }

    /**
     * Generic log function
     *
     * @param <type> $name
     * @param <type> $arguments
     *
     * @return false so you can easily break out of a function
     */
    public function log($name, $arguments) {
        array_unshift($arguments, $name);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function emerg() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function crit() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function err() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function warning() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function notice() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function info() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function debug() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function debugv() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function section_open() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function section_close() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function trail() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function stdout() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }
    public function stderr() {
        $arguments = func_get_args(); array_unshift($arguments, __FUNCTION__);
        return call_user_func_array(array($this, '_log'), $arguments);
    }

    public function  __construct($options = array()) {
        // Get parent defined options
        $parentVars    = @get_class_vars(@get_parent_class($this));
        // Override with own defined options
        $this->_options = $this->merge((array)@$parentVars['_options'], $this->_options);
        // Override with own instance options
        $this->_options = $this->merge($this->_options, $options);

        // Automatically instantiate classes
        if ($this->_options['class-autobind']) {
            foreach(get_class_vars(get_class($this)) as $property=>$val) {
                if ($val === null && substr($property, 0 , 1) === strtoupper(substr($property, 0 , 1))) {
                    if (class_exists($property)) {
                        $this->{$property} = new $property($this->_options);
                    }
                }
            }
        }

        if ($this->_options['class-autosetup']) {
            // Call setup method if it exists
            if (method_exists($this, '__setup')) {
                call_user_func(array($this, '__setup'));
            }
        }
    }

    /**
     * Get or set ->_options
     *
     * @param mixed string or array $key
     * @param mixed                 $val
     * @param boolean               $forceWrite if value is set, it will be written. unless you want to write null. use force in that case
     *
     * @return mixed
     */
    public function opt($key, $val = null, $forceWrite = false) {
        if (is_array($key)) {
            foreach($key as $k => $v) {
                $this->opt($k, $v);
            }
            return $this->_options;
        }
        if ($val !== null || $forceWrite) {
            $this->_options[$key] = $val;
        }
        return $this->_options[$key];
    }

    /**
     * Internal log function. Always address it via another function
     * or the traces wont work
     *
     * @param <type> $name
     * @param <type> $arguments
     *
     * @return false so you can easily break out of a function
     */
    protected function _log($level, $format, $arg1 = null, $arg2 = null, $arg3 = null) {
        $arguments = func_get_args();
        $level     = $arguments[0];
        $format    = $arguments[1];

        // recurse?
        if (is_array($format)) {
            foreach($format as $f) {
                $arguments[1] = $f;
                call_user_func_array(array($this, '_log'), $arguments);
            }
            return false;
        } else {
            unset($arguments[0]);
            unset($arguments[1]);
        }

        $str       = @vsprintf($format, $arguments);

        $levels = array_flip(array(
            'emerg',
            'alert',
            'crit',
            'err',
            'warning',
            'notice',
            'info',
            'debug',
            'debugv',
        ));

        $section   = false;
        $showLevel = $level;
        $useLevel  = $level;
        $date      = date('H:i:s');
        $indent    = '    ';
        $prefix    = "";
        if (in_array($level, $this->_options['log-section-open'])) {
            $useLevel  = 'notice';
            $showLevel = '';
            #$date      = '------->';
            $date      = '        ';
            $section   = 'open';
            $indent    = ' ';
            $prefix    = "\n";
        } else if (in_array($level, $this->_options['log-section-close'])) {
            $useLevel  = 'notice';
            $showLevel = '';
            $section   = 'close';
            $date      = '        ';
            $indent    = ' ';
            // Leave this out for now
            #return false;
        } elseif ($level == $this->_options['log-trail-level']) {
            $useLevel  = 'notice';
        } elseif ($level == 'stderr') {
            $useLevel  = 'warning';
            $date      = '        ';
            $showLevel = '';
            $indent    = '        ';
        } elseif ($level == 'stdout') {
            $useLevel  = 'debug';
            $date      = '        ';
            $showLevel = '';
            $indent    = '        ';
        }
        
        $msgWeight    = $levels[$useLevel];
        $printWeight  = $levels[$this->_options['log-print-level']];
        $memoryWeight = $levels[$this->_options['log-memory-level']];
        $fileWeight   = $levels[$this->_options['log-file-level']];
        $breakWeight  = $levels[$this->_options['log-break-level']];

//        $str = sprintf('[%4s][%8s] %s%s%s',
//            str_repeat('*', (7-$msgWeight)),
//            $showLevel,
//            date('H:i:s'),
//            $indent,
//            $str);
        $str = $prefix.sprintf('%8s %s%s%s',
            $showLevel,
            $date,
            $indent,
            $str);
        
        if ($msgWeight <= $memoryWeight) {
            $this->logs[$level][] = $str;
        }
        if ($msgWeight <= $printWeight) {
            $this->out($str);
        }
        if ($msgWeight <= $fileWeight) {
            file_put_contents($this->_options['log-file'], $str."\n", FILE_APPEND);
        }
        if ($msgWeight <= $breakWeight) {
            
            $this->trail('');
            $this->trail(' Process halt, triggered by the following path: ');
            $this->trail('');
            $this->trail($this->trace(4));
            $traces = $this->trace(4);
            $this->trail('');
            exit(1);
        }
        
        return false;
    }
}
?>
