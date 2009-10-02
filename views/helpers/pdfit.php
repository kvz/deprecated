<?php
/**
 * Helper will actually turn HTML into PDF
 *
 */
class PdfitHelper extends Helper {
    protected $_options;
    protected $_html;
    protected $_served;

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

    protected function _defaultOpts() {
        if (!isset($this->_options['title'])) $this->_options['title'] = '';
        if (!isset($this->_options['subtitle'])) $this->_options['subtitle'] = '';

        if (!isset($this->_options['debug'])) $this->_options['debug'] = 0;
        if (!isset($this->_options['dumphtml'])) $this->_options['dumphtml'] = false;
        if (!isset($this->_options['background'])) $this->_options['background'] = false;
        if (!isset($this->_options['serve'])) $this->_options['serve'] = false;
        if (!isset($this->_options['method'])) $this->_options['method'] = 'tcpdf';
        if (!isset($this->_options['dir'])) $this->_options['dir'] = '/tmp';
        if (!isset($this->_options['filebase'])) $this->_options['filebase'] = tempnam($this->_options['dir'], 'pdfconv_'. date('Ymd_His').'_').'%s.%s';
    }

    public function exe() {
        $args = func_get_args();
        $cmd  = array_shift($args);
        if (count($args)) {
            $cmd = vsprintf($cmd, $args);
        }

        return shell_exec($cmd);
    }

    public function pdf($html, $options = array()) {
        if (!empty($options)) {
            $this->setup($options);
        }
        $this->_html = $html;

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

    protected function _dompdf() {
        $htmlFilePath = $this->_pdfFile('html');
        $pdfFilePath  = $this->_pdfFile('pdf', __FUNCTION__);

        // save html
        file_put_contents($htmlFilePath, $this->_html);
        
        $domDir = dirname(dirname(dirname(__FILE__))).'/vendors/dompdf';
        $domExe = $domDir.'/dompdf.php';

        $o = $this->exe('php %s -f %s %s -v ', $domExe, $pdfFilePath, $htmlFilePath);
        @unlink($htmlFilePath);

        return $pdfFilePath;
    }

    protected function _html2fpdf() {
        App::import('Vendor', 'pdfview.html2pdf');
        $pdfFilePath  = $this->_pdfFile('pdf', __FUNCTION__);
        
        $pdf = new HTML2FPDF();
        $pdf->DisableTags();
        #$pdf->DisplayPreferences('FullScreen');

        $pdf->AddPage();
        $pdf->WriteHTML($this->_html);

        $pdf->Output($pdfFilePath, 'F');
        return $pdfFilePath;
    }

    // Messes up SimpleTest:
    //    protected function _dompdf() {
    //        $pdfFilePath = $this->_pdfFile('pdf');
    //
    //        error_reporting(E_ALL ^ E_STRICT);
    //
    //        App::import('Vendor', 'pdfview.dompdf', array('file' => 'dompdf_config.inc.php'));
    //
    //        $paper = DOMPDF_DEFAULT_PAPER_SIZE;
    //        $orientation = "portrait";
    //
    //        $dompdf = new DOMPDF();
    //        $dompdf->load_html($this->_html);
    //        $dompdf->set_paper($paper, $orientation);
    //        $dompdf->render();
    //        file_put_contents($pdfFilePath, $dompdf->output());
    //
    //        return $pdfFilePath;
    //    }

    protected function _tcpdf() {
        $pdfFilePath = $this->_pdfFile('pdf', __FUNCTION__);
        App::import('Vendor', 'pdfview.tcpdf');
        
        // Logic to create specially formatted link goes here...

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        //-- variable label names (see app\config\client\client.ini.php)
        $client = 'AnyClient';
        
        $textfont = 'freesans'; // looks better, finer, and more condensed than 'dejavusans'

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->SetAuthor('Credit Risk Managment at http://www.creditriskmgt.com/');
        $pdf->SetAutoPageBreak(false);
        $pdf->setHeaderFont(array($textfont,'',40));
        $pdf->headercolor = explode(',', '239,235,231');

        $pdf->headertext = $client;
        $pdf->footertext = 'Copyright � %d Credit Risk Management, L.L.C.. All rights reserved.';

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN + 1));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA + 1));

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER+5);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER+5);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $this->_options['title'], $this->_options['subtitle']);

        // set document information
        $pdf->SetTitle($this->_options['title']);

        $pdf->setPageOrientation('P'); // or 'L'

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // add a page
        $pdf->AddPage();

        // create some HTML content
        //$logo = WWW_ROOT . 'client\header-logo.png';
        //$vector_logo = WWW_ROOT . 'client\credit risk blue logo.svg';

        // set core font
        $pdf->SetFont('helvetica', '', 8);

        if (isset($results) && !$results['success']) {
            $htmlcontent = getPdfError($results);
        } else {
            $htmlcontent = $this->_html;
        }
        
        if ($this->_options['debug'] > 1) {
            return print_r($htmlcontent, true);
        }

        $pdf->setY(1.0);
        
        // output the HTML content
        //$pdf->writeHTML($htmlcontent, true, 0, true, 0);
        writeHTMLSections($pdf, $htmlcontent);
        
        $pdf->Output($pdfFilePath, 'F');
        return $pdfFilePath;
    }
    protected function _xtcpdf() {
        $pdfFilePath = $this->_pdfFile('pdf', __FUNCTION__);
        App::import('Vendor', 'pdfview.xtcpdf');

        //$this->_options['background']

        // Logic to create specially formatted link goes here...
        $pdf = new XTCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        if ($this->_options['background']) {
            prd($this->_options['background']);
            $pdf->backgroundImage = $this->_options['background'];
        }

        //-- variable label names (see app\config\client\client.ini.php)
        $client = 'AnyClient';
        
        $textfont = 'freesans'; // looks better, finer, and more condensed than 'dejavusans'

        // set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetSubject('');
        $pdf->SetKeywords('');
        $pdf->SetAuthor('Credit Risk Managment at http://www.creditriskmgt.com/');
        $pdf->SetAutoPageBreak(false);
        $pdf->setHeaderFont(array($textfont,'',40));
        $pdf->headercolor = explode(',', '239,235,231');

        $pdf->headertext = $client;
        $pdf->footertext = 'Copyright � %d Credit Risk Management, L.L.C.. All rights reserved.';

        // set header and footer fonts
        $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN + 1));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA + 1));

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER+5);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER+5);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set default header data
        $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $this->_options['title'], $this->_options['subtitle']);

        // set document information
        $pdf->SetTitle($this->_options['title']);
        
        $pdf->setPageOrientation('P'); // or 'L'

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // add a page
        $pdf->AddPage();

        // create some HTML content
        //$logo = WWW_ROOT . 'client\header-logo.png';
        //$vector_logo = WWW_ROOT . 'client\credit risk blue logo.svg';

        // set core font
        $pdf->SetFont('helvetica', '', 8);

        if (isset($results) && !$results['success']) {
            $htmlcontent = getPdfError($results);
        } else {
            $htmlcontent = $this->_html;
        }

        if ($this->_options['debug'] > 1) {
            return print_r($htmlcontent, true);
        }

        $pdf->setY(1.0);
        
        // output the HTML content
        //$pdf->writeHTML($htmlcontent, true, 0, true, 0);
        writeHTMLSections($pdf, $htmlcontent);
        
        // XPDF Can Serve itsself
        if ($this->_options['serve'] && !$this->_served) {
            $this->_served = true;
            $pdf->Output(basename($pdfFilePath), 'I');
        } else {
            $pdf->Output($pdfFilePath, 'F');
        }
        
        return $pdfFilePath;
    }
}
?>