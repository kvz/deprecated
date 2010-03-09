<?php
/**
 * Helper will actually turn HTML into PDF
 *
 */
class PdfitHelper extends Helper {
    protected $_options;
    protected $_html;
    protected $_served;
    public    $logs;

    protected function _defaultOpts() {
        if (!isset($this->_options['title'])) $this->_options['title'] = '';
        if (!isset($this->_options['subtitle'])) $this->_options['subtitle'] = '';
        if (!isset($this->_options['debug'])) $this->_options['debug'] = 0;
        if (!isset($this->_options['dumphtml'])) $this->_options['dumphtml'] = false;
        if (!isset($this->_options['background'])) $this->_options['background'] = false;
        if (!isset($this->_options['serve'])) $this->_options['serve'] = false;
        if (!isset($this->_options['tidy'])) $this->_options['tidy'] = false;
        if (!isset($this->_options['method'])) $this->_options['method'] = 'tcpdf';
        if (!isset($this->_options['dir'])) $this->_options['dir'] = '/tmp';
        if (!isset($this->_options['filebase'])) $this->_options['filebase'] = tempnam($this->_options['dir'], 'pdfit_'. date('Ymd_His').'_').'%s.%s';
    }

    public function  __call($name, $arguments) {
        $format = array_shift($arguments);
        $str    = vsprintf($format, $arguments);
        $this->logs[$name][] = $str;

        if ($this->_options['debug'] > 0) {
            if ($name === 'err') {
                trigger_error($str, E_USER_ERROR);
            }
        }
        if ($this->_options['debug'] > 1) {
            if ($name === 'debug') {
                echo($str."\n");
            }
        }

        return false;
    }
    
    public function  __construct($options) {
        $this->setup($options);
    }
    
    public function setup($options) {
        set_time_limit(0);
        ini_set('memory_limit', '1624M');
        $this->_options = $options;
        $this->_served = false;
        $this->_defaultOpts();
    }

    protected function _headers($filename) {
        // Disable cache (from Cake core file controller.php, disableCache function):
        //
        //        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        //        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        //        header("Cache-Control: no-store, no-cache, must-revalidate");
        //        header("Cache-Control: post-check=0, pre-check=0", false);
        //        header("Pragma: no-cache");
        //        header("Content-type: application/pdf");

        header("Pragma: public");
        header("Expires: 0");
        header("Pragma: no-cache");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-disposition: attachment; filename=' . basename($filename));
        header("Content-Type: application/pdf");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . filesize($filename));
    }
    
    public function serve($filename) {
        $this->_served = true;
        $this->_headers($filename);
        readfile($filename);
    }

    public function exe() {
        $args = func_get_args();
        $cmd  = array_shift($args);
        if (count($args)) {
            $cmd = vsprintf($cmd, $args);
        }

        $buf = shell_exec($cmd);
        $this->debug('Running \'%s\', returned: \'%s\'', $cmd, $buf);
        return $buf;
    }

    public function tidy($html, $options = array()) {
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

    public function pdf($html, $options = array()) {
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

        if (false !== ($pdfFilePath = call_user_func(array($this, '_'.$this->_options['method']), $this->_html))) {
            if ($this->_options['serve'] && !$this->_served) {
                return $this->serve($pdfFilePath);
            }
        }

        return $pdfFilePath;
    }

    protected function _pdfFile($extension = 'pdf', $method = '') {
        return sprintf($this->_options['filebase'], $method, $extension);
    }

    protected function _html2ps() {
        $htmlFilePath = $this->_pdfFile('html');
        $psFilePath   = $this->_pdfFile('ps');
        $pdfFilePath  = $this->_pdfFile('pdf', __FUNCTION__);

        // save html
        file_put_contents($htmlFilePath, $this->_html);
        
        // 2 ps
        $o = $this->exe('html2ps --number -D --toc bh -dc -o ', $psFilePath, $htmlFilePath);
        
        // 2 pdf
        $o = $this->exe('ps2pdf', $psFilePath, $pdfFilePath);
        //if (false === $o) {
        //    echo 'return: ';
        //    print_r($S->return_var);
        //    echo 'output: ';
        //    print_r($S->output);
        //    echo 'errors: ';
        //    print_r($S->errors);
        //    echo 'command: ';
        //    print_r($S->command);
        //    die();
        //}

        @unlink($htmlFilePath);
        @unlink($psFilePath);

        return $pdfFilePath;
    }

    protected function _retrieve($filepath) {
        $this->debug('%s() called', __FUNCTION__);
        
        if (substr($filepath, 0, 4) === 'http') {
            $temp = tempnam($this->_options['dir'], 'pdfit_cache_');
            
            if (!($buf = file_get_contents($filepath))) {
                return $this->err('Unable to retrieve %s', $filepath);
            }
            
            if (!file_put_contents($temp, $buf)) {
                return $this->err('Unable to store %s in %s', $filepath, $temp);
            }

            $filepath = $temp;
        }

        if (!file_exists($filepath)) {
            return $this->err('%s does not exist', $filepath);
        }
        
        return $filepath;
    }

    protected function _wkhtmltopdf() {
        $this->debug('%s() called', __FUNCTION__);
        $htmlFilePath  = $this->_pdfFile('html');
        $pdfFilePath   = $this->_pdfFile('pdf', __FUNCTION__);
        $bgPdfFilePath = $this->_pdfFile('bg.pdf', __FUNCTION__);

        // Prereqs
        if (!file_exists('/bin/wkhtmltopdf')) {
            return $this->err('wkhtmltopdf is not installed. Try:

sudo aptitude install openssl build-essential xorg libqt4-dev qt4-dev-tools xvfb

# You will want to compile a patched static vertion of qt and build
# wkhtmltopdf using that

# 32 BIT LINUX - A STATIC IS READILY AVAILABLE, JUST COPY TO BIN:

cd /usr/src
wget http://wkhtmltopdf.googlecode.com/files/wkhtmltopdf-0.8.3-static.tar.bz2
tar -xjvf wkhtmltopdf-0.8.3-static.tar.bz2
mv wkhtmltopdf /bin/wkhtmltopdf

# OR, 64 BIT LINUX - MAKE YOUR OWN STATIC:

cd /usr/src
svn checkout http://wkhtmltopdf.googlecode.com/svn/tags/0.8.3/ wkhtmltopdf
cd wkhtmltopdf
sed -i~ \'s#i386#amd64#g\' static-build.sh
./static-build.sh linux
cp ./wkhtmltopdf /bin/wkhtmltopdf
chmod a+x /bin/wkhtmltopdf

            ');
        }

        $pdfTk = '/usr/bin/pdftk';
        if ($this->_options['background'] && !file_exists($pdfTk)) {
            return $this->err('For backgrounds in wkhtmltopdf you need pdftk but it is not installed. Try:

aptitude install pdftk
            ');
        }

        # From: http://code.google.com/p/wkhtmltopdf/issues/detail?id=3
        $wkhDir = dirname(dirname(dirname(__FILE__))).'/vendors/wkhtmltopdf';
        $wkhExe = $wkhDir.'/html2pdf.sh';


        // save html
        if (!file_put_contents($htmlFilePath, $this->_html)) {
            return $this->err('Unable to write %s', $htmlFilePath);
        }

        if (!isset($this->_options['title'])) $this->_options['title'] = '';
        if (!isset($this->_options['subtitle'])) $this->_options['subtitle'] = '';

        $opts = array(
            '--outline',

            '--margin-top 15mm',
            '--margin-bottom 20mm',

            '--no-background',
            '--page-size A4',
            '--dpi 96',
            '--orientation Portrait',

            '--disable-javascript',
        );

        if (!empty($this->_options['toc'])) {
            $opts[] = '--toc';
            $opts[] = '--toc-font-name Helvetica';
            $opts[] = '--toc-depth 2';
            $opts[] = '--toc-no-dots';
        }
        
//      @todo: Header text is breaking hard because of escaping issues
//        $headertxt = '';
//        if (!empty($this->_options['title'])) {
//            $headertxt .= $this->_options['title'];
//        }
//        if (!empty($this->_options['subtitle'])) {
//            $headertxt .= ' ndash; '.$this->_options['subtitle'];
//        }
//        if ($headertxt) {
//            #$opts[] = '--header-left \''.$headertxt.'\'';
//            $opts[] = '--header-right [page]/[toPage]';
//            $opts[] = '--header-line';
//        }
        
        $o = $this->exe('bash %s %s %s '.join(' ', $opts).'', $wkhExe, $htmlFilePath, $pdfFilePath);
        $this->debug($o);

        if ($this->_options['background']) {
            $bgPath = $this->_retrieve($this->_options['background']);
            $o = $this->exe('%s %s background %s output %s', $pdfTk, $pdfFilePath, $bgPath, $bgPdfFilePath);
            $this->debug($o);
            rename($bgPdfFilePath, $pdfFilePath);
        }
        
        @unlink($htmlFilePath);

        return $pdfFilePath;
    }

}