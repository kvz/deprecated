<?php
/**
 * Helper will actually turn HTML into PDF
 *
 */
class PdfitHelper extends Helper {
	protected $_options = array(
		'title' => '',
		'subtitle' => '',
		'debug' => 0,
		'dumphtml' => false,
		'toc' => false,
		'background' => false,
		'serve' => false,
		'tidy' => false,
		'dir' => '',
		'filemask' => '/tmp/document-:day-:uuid.:ext',
		'set_time_limit' => 0,
		'memory_limit' => '1624M',
	);
	protected $_html;
	protected $_served;
	public	  $logs;

	public function  __call ($name, $arguments) {
		$format = array_shift($arguments);
		$str	= vsprintf($format, $arguments);
		$this->logs[$name][] = $str;

		if ($this->_options['debug'] > 0) {
			if ($name === 'err') {
				trigger_error($str, E_USER_ERROR);
			}
		}
		if ($this->_options['debug'] > 1) {
			if ($name === 'debug') {
				echo $str . "\n";
			}
		}

		return false;
	}

	public function  __construct ($options) {
		$this->setup($options);
	}

	public function setup ($options) {
		$args = func_get_args();
		if (count($args) === 2) {
			$options = array(
				$args[0] => $args[1],
			);
		}

		$this->_options = array_merge($this->_options, $options);
		$this->_served = false;

		set_time_limit($this->_options['set_time_limit']);
		ini_set('memory_limit', $this->_options['memory_limit']);

	}

	protected function _headers ($filename) {
		// Disable cache (from Cake core file controller.php, disableCache function):
		//
		//		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		//		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		//		header("Cache-Control: no-store, no-cache, must-revalidate");
		//		header("Cache-Control: post-check=0, pre-check=0", false);
		//		header("Pragma: no-cache");
		//		header("Content-type: application/pdf");

//		header("Pragma: public");
//		header("Content-Type: application/force-download");
//		header("Content-Type: application/octet-stream");
//		header("Content-Type: application/download");

		header("Expires: 0");
		header("Pragma: no-cache");
		header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
		header('Content-Disposition: attachment; filename=' . basename($filename));
		header("Content-Type: application/pdf");
		header("Content-Transfer-Encoding: binary");
		header('Content-Length: ' . filesize($filename));
	}

	public function serve ($filename) {
		$this->_served = true;
		$this->_headers($filename);
		readfile($filename);
	}

	public function exe () {
		$args = func_get_args();
		$cmd  = array_shift($args);
		if (count($args)) {
			$cmd = vsprintf($cmd, $args);
		}

		$buf = shell_exec($cmd);
		$this->debug('Running \'%s\', returned: \'%s\'', $cmd, $buf);
		return $buf;
	}

	public function tidy ($html, $options = array()) {
		$this->debug('%s() called', __FUNCTION__);

		// Prereqs
		if (!function_exists('tidy_parse_string')) {
			return $this->err('Function \'tidy_parse_string\' not found. You need to: aptitude install php5-tidy');
		}

		// Specify configuration
		$default_options = array(
			'clean' => true,
			'indent' => true,
			'indent-spaces' => 4,
			'output-html' => true,
			'wrap' => 200,
		);

		$options = array_merge($default_options, $options);

		// Tidy
		$tidy = tidy_parse_string($html, $options, 'utf8');
		$tidy->cleanRepair();

		// Output
		return (string)$tidy;
	}

	public function pdf ($html, $options = array()) {
		if (!empty($options)) {
			$this->setup($options);
		}
		$this->_html = $html;

		if ($this->_options['tidy']) {
			if (false === ($this->_html = $this->tidy($html))) {
				return false;
			}
		}

		if ($this->_options['dumphtml']) {
			echo sprintf('<xmp>%s</xmp>', $this->_html);
			return;
		}

		if (false !== ($pdfFilePath = call_user_func(array($this, '_wkhtmltopdf'), $this->_html))) {
			if ($this->_options['serve'] && !$this->_served) {
				return $this->serve($pdfFilePath);
			}
		}

		return $pdfFilePath;
	}

	protected function _filepath ($ext = 'pdf', $method = '') {
		return String::insert(
			$this->_options['filemask'],
			array(
				'uuid' => String::uuid(),
				'day' => date('Ymd'),
				'ext' => $ext,
				'method' => $method,
			)
		);
	}

	protected function _retrieve ($filepath) {
		$this->debug('%s() called', __FUNCTION__);

		if (substr($filepath, 0, 4) === 'http') {
			$tmpfile = $this->_filepath('cache', __FUNCTION__);

			if (!($buf = file_get_contents($filepath))) {
				return $this->err('Unable to retrieve %s', $filepath);
			}

			if (!file_put_contents($tmpfile, $buf)) {
				return $this->err('Unable to store %s in %s', $filepath, $tmpfile);
			}

			$filepath = $tmpfile;
		}

		if (!file_exists($filepath)) {
			return $this->err('%s does not exist', $filepath);
		}

		return $filepath;
	}

	protected function _wkhtmltopdf () {
		$this->debug('%s() called', __FUNCTION__);
		$htmlFilePath  = $this->_filepath('html');
		$pdfFilePath   = $this->_filepath('pdf', __FUNCTION__);
		$bgPdfFilePath = $this->_filepath('bg.pdf', __FUNCTION__);

		if (!file_exists('/usr/local/bin/wkhtmltopdf')) {
			return $this->err(
				'wkhtmltopdf is not installed. Try: %s/vendors/wkhtmltopdf/install.sh',
				dirname(dirname(__DIR__))
			);
		}

		$pdfTk = '/usr/bin/pdftk';
		if ($this->_options['background'] && !file_exists($pdfTk)) {
			return $this->err(
				'For backgrounds in wkhtmltopdf you need pdftk but it is not installed. Try: aptitude install pdftk'
			);
		}

		// save html
		if (!file_put_contents($htmlFilePath, $this->_html)) {
			return $this->err('Unable to write %s', $htmlFilePath);
		}

		$opts = array(
			'--outline',

			'--margin-top 15mm',
			'--margin-bottom 20mm',

			'--page-size A4',
			'--dpi 96',
			'--orientation Portrait',
		);

		if ($this->_options['toc']) {
			$opts[] = '--toc';
			$opts[] = '--toc-font-name Helvetica';
			$opts[] = '--toc-depth 2';
			$opts[] = '--toc-no-dots';
		}

		$cmd = sprintf(
			'/usr/local/bin/wkhtmltopdf %s %s %s',
			join(' ', $opts),
			$htmlFilePath, 
			$pdfFilePath
		);
		$o = $this->exe($cmd);

		if (!file_exists($pdfFilePath)) {
			return $this->err('Unable to convert html to pdf using command "%s". %s', $cmd, $o);
		}

		if ($this->_options['background']) {
			$bgPath = $this->_retrieve($this->_options['background']);
			$cmd    = sprintf('%s %s background %s output %s', $pdfTk, $pdfFilePath, $bgPath, $bgPdfFilePath);
			$o      = $this->exe($cmd);
			if (!file_exists($bgPdfFilePath)) {
				return $this->err('Error while running command "%s". %s', $cmd, $o);
			}

			rename($bgPdfFilePath, $pdfFilePath);
		}

		@unlink($htmlFilePath);

		return $pdfFilePath;
	}
}