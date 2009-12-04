<?php
/**
 * Description of Cmd
 *
 * @author kvz
 */
class Cmd {
    public $stderr;
    public $stdout;
    public $stdcmb;
    public $code;
    public $okay;
    public $okaycode = array(0, 255);
    public $lastline;

    public function reset() {
        $this->okay     = null;
        $this->code     = null;
        $this->stdout   = array();
        $this->stderr   = array();
        $this->stdcmb   = array();
        $this->lastline = '';
        $this->okaycode = (array)$this->okaycode;
    }

    public function join() {
        $this->stdout = join("\n", $this->stdout);
        $this->stderr = join("\n", $this->stderr);
        $this->stdcmb = join("\n", $this->stdcmb);
    }

    public function  __construct($cmd = null) {
        if (null !== $cmd) {
            $this->cmd($cmd);
        }
    }
    
    public function cmdf($cmd) {
        $args = func_get_args();
        $cmd  = array_shift($args);
        if (count($args)) {
            $cmd = vsprintf($cmd, $args);
        }

        return $this->cmd($cmd);
    }
    
    public function cmd($cmd) {
        $this->reset();

        $descriptorspec = array(
            0 => array("pipe", "r"),  // stdin
            1 => array("pipe", "w"),  // stdout
            2 => array("pipe", "w")   // stderr ?? instead of a file
        );
        $process = proc_open($cmd, $descriptorspec, $pipes);
        if (is_resource($process)) {
            while ($this->lastline = fgets($pipes[1], 1024)) {
                $this->lastline = rtrim($this->lastline);
                $this->stdout[] = $this->lastline;
                $this->stdcmb[] = $this->lastline;
            }
            fclose($pipes[1]);
            while ($this->lastline = fgets($pipes[2], 1024)) {
                $this->lastline = rtrim($this->lastline);
                $this->stderr[] = $this->lastline;
                $this->stdcmb[] = $this->lastline;
            }
            fclose($pipes[2]);
        }

        $this->code = proc_close($process);
        $this->okay = in_array($this->code, $this->okaycode);

        $this->join();

        return $this->okay;
    }
    
    public function  __toString() {
        return $this->stdout;
    }
}
?>