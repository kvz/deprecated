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
	public $callbacks = array();
	public $collect = false;

	public function reset() {
		$this->okay	 = null;
		$this->code	 = null;
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

	public function stdout($str) {
		$this->stdout[] = $str;
		$this->stdcmb[] = $str;
		if (isset($this->callbacks[__FUNCTION__])) {
			return call_user_func($this->callbacks[__FUNCTION__], $str);
		}
	}

	public function stderr($str) {
		$this->stderr[] = $str;
		$this->stdcmb[] = $str;
		if (isset($this->callbacks[__FUNCTION__])) {
			return call_user_func($this->callbacks[__FUNCTION__], $str);
		}
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
			while ($this->lastline = fgets($pipes[1])) {
				$this->lastline = rtrim($this->lastline);
				$this->stdout($this->lastline);
			}
			fclose($pipes[1]);
			while ($this->lastline = fgets($pipes[2])) {
				$this->lastline = rtrim($this->lastline);
				$this->stderr($this->lastline);
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