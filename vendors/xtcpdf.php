<?php
define('BEGIN_NO_BREAK', '<!-- nobreak -->');
define('END_NO_BREAK', '<!-- /nobreak -->');

/**
 * Uses writeHTML calls to output HTML content in sections of breaking and non-breaking sections
 * based on the existance of any embedded BEGIN_NO_BREAK and END_NO_BREAK markers. If any "nobreak"
 * section is longer than a full page, it will start printing on a new page, but will then span
 * across as many pagebreaks as necessary (obviously if it doesn't fit on a single page, it must span
 * pages at some point!).
 *
 * Example: $htmlcontent = '
		<table id="table_1">
			...
		</table>

		<!-- nobreak -->
		<table id="table_2">
			...
		</table>
		<!-- /nobreak -->

		<table id="table_3">
			...
		</table>';

 * @param object &$pdf reference to current TCPDF class instance.
 * @param string $htmlcontent block of html text that may have "nobreak" comments..
 * @return void
 * @access public
 */

function writeHTMLSections(&$pdf, $htmlcontent = '') {
	$writeSections = array();
	$tmp1 = array();
	$tmp2 = array();

	// 1. Parse html content into array of sections (each section is flagged to allow breaks or not).
	while ($htmlcontent && strpos($htmlcontent, BEGIN_NO_BREAK) !== false) {
		$noBreak = false;
		$tmp1 = explode(BEGIN_NO_BREAK, $htmlcontent, 2);
		$writeSections[] = array($noBreak, trim($tmp1[0]));
		$tmp2 = explode(END_NO_BREAK, $tmp1[1], 2);
		if (trim($tmp2[0])) {
			$noBreak = true;
			$writeSections[] = array(1, trim($tmp2[0]));
		}
		$htmlcontent = trim($tmp2[1]);
	}

	if (trim($htmlcontent)) {
		$writeSections[] = array(0, trim($htmlcontent));
	}
    
	// 2. Output each section in order. If section is flagged to not span across a page break,
	//    use transactions to determine if a forced pagebreak is required before section output.
	foreach ($writeSections as $writeSection) {
		if (!$writeSection[0]) {
			// allow section to span across page break
			$pdf->writeHTML($writeSection[1], true, 0, true, 0);
			continue;
		}

		$pdf->startTransaction();
		$current_page = $pdf->getPage();
		$pdf->writeHTML($writeSection[1], true, 0, true, 0);
		if ($pdf->getPage() > $current_page) {
			// didn't fit on current page
			$pdf = $pdf->rollbackTransaction();
			$pdf->AddPage();
			$pdf->writeHTML($writeSection[1], true, 0, true, 0);
		}
		$pdf->commitTransaction();
	}
}

App::import('Vendor','pdfview.tcpdf/tcpdf');
class XTCPDF_report extends TCPDF {

    var $footertext  = "Copyright � %d Credit Risk Management, L.L.C.. All rights reserved.";
    var $footerfontsize = 6;

    /**
    * Overwrites the default header
    * set the text in the view using
    *    $fpdf->xheadertext = 'YOUR ORGANIZATION';
    * set the fill color in the view using
    *    $fpdf->xheadercolor = array(0,0,100); (r, g, b)
    * set the font in the view using
    *    $fpdf->setHeaderFont(array('YourFont','',fontsize));
    */
    function Header() {
		$top_margin = 0.5;
		$ormargins = $this->getOriginalMargins();
		$headerfont = $this->getHeaderFont();
		$headerdata = $this->getHeaderData();

		$width = 0;
		$border = 0;
		$ln = 1; // current position should go after call, 0: right, 1: beginning of next line; 2: below;
		$align = 'C';

		// First header - Bank Name
		list($r, $b, $g) = $this->headercolor;
        $this->SetTextColor($r, $b, $g);
		$this->SetFont($headerfont[0], 'B', $headerfont[2] * K_TITLE_MAGNIFICATION);
		$cell_height = round(($this->getCellHeightRatio() * $headerfont[2] * K_TITLE_MAGNIFICATION) / $this->getScaleFactor(), 2);
        $this->Cell($width, $cell_height, $this->headertext, $border, $ln, $align);

		// Second header - Title
		$this->SetX($ormargins['left']);
		$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2] * (0.5 * (K_TITLE_MAGNIFICATION - 1) + 1));
		$cell_height = round(($this->getCellHeightRatio() * $headerfont[2] * (0.5 * (K_TITLE_MAGNIFICATION - 1) + 1)) / $this->getScaleFactor(), 2);
		$this->Cell($width, $cell_height, $headerdata['title'], $border, $ln, $align);

		// header string
		$this->SetX($ormargins['left']);
		$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2] * 0.8);
		$cell_height = round(($this->getCellHeightRatio() * $headerfont[2] * 0.8) / $this->getScaleFactor(), 2);

		$fill = 0;
		$reseth = true;
		$strecth = 0;
		$ishtml = false;
		$this->MultiCell($width, $cell_height, $headerdata['string'], $border, $align, $fill, 1, '', '', $reseth, $strecth, $ishtml);
	}

	function Footer() {
		$this->SetY(-1);
		$this->SetFont('Helvetica', '', 6);
		$tmpX = $this->getX();
        $year = date('Y');
        $footertext = sprintf($this->footertext, $year);
		$this->Cell(0, 1, $footertext, 0, 0, 'C');
		$this->SetX($tmpX);
		$this->Cell(0, 1, 'Date/Time printed: '. date('Y-m-d H:i:s'), 0, 0, 'L');
		$this->Cell(0, 1, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
		$this->footerset[$this->page] = 1;
 	}
}

class XTCPDF extends TCPDF {
    function Header() {
		$top_margin = 0.5;
		$ormargins = $this->getOriginalMargins();
		$headerfont = $this->getHeaderFont();
		$headerdata = $this->getHeaderData();

		// First header - Bank Name
        list($r, $b, $g) = $this->headercolor;
        $this->SetTextColor($r, $b, $g);
		$this->SetFont($headerfont[0], 'B', $headerfont[2] * K_TITLE_MAGNIFICATION);
        $this->Cell(0, $top_margin, $this->headertext, 0, 0, 'C');
		$cell_height = round(($this->getCellHeightRatio() * $headerfont[2] * K_TITLE_MAGNIFICATION) / $this->getScaleFactor(), 2);

		// Second header - Title
		$this->SetX($ormargins['left']);
		$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2] * (0.5 * (K_TITLE_MAGNIFICATION - 1) + 1));
		$this->Cell(0, $top_margin + ($cell_height * 2), $headerdata['title'], 0, 0, 'C');
	}

	function Footer() {
		$this->SetY(-1);
		$this->SetFont('Helvetica', '', 6);
		$tmpX = $this->getX();
        $year = date('Y');
        $footertext = sprintf($this->footertext, $year);
		$this->Cell(0, 1, $footertext, 0, 0, 'C');
		$this->SetX($tmpX);
		$this->Cell(0, 1, 'Date/Time printed: '. date('Y-m-d H:i:s'), 0, 0, 'L');
		$this->Cell(0, 1, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'R');
		$this->footerset[$this->page] = 1;
 	}
}

class XTCPDF_RGA extends TCPDF {

	// class for Risk Grade Analysis pdf

	var $_bank = "";
	var $_report = "";
	var $_width = 197;

	function InitReportValues($bank, $report, $hw=null) {
		$this->_bank = $bank;
		$this->_report = $report;
		if (!$hw == null) {
			$this->_width = $hw;
		}
	}

	/**
    * Allows you to control how the pdf is returned to the user, most of the time in CakePHP you probably want the string
    *
    * @param string $name name of the file.
    * @param string $destination where to send the document values: I, D, F, S
    * @return string if the $destination is S
    */
    function tcpdfOutput ($name = 'page.pdf', $destination = 'd') {
        // I: send the file inline to the browser. The plug-in is used if available.
        //    The name given by name is used when one selects the "Save as" option on the link generating the PDF.
        // D: send to the browser and force a file download with the name given by name.
        // F: save to a local file with the name given by name.
        // S: return the document as a string. name is ignored.
        return $this->Output($name, $destination);
    }

	 /**
    * Overwrites the default header
    * set the text in the view using
    *    $fpdf->xheadertext = 'YOUR ORGANIZATION';
    * set the fill color in the view using
    *    $fpdf->xheadercolor = array(0,0,100); (r, g, b)
    * set the font in the view using
    *    $fpdf->setHeaderFont(array('YourFont','',fontsize));
    */
//    function Header()
//    {
//		#$this->ImageEps(WWW_ROOT.'client'.DS.'banklogo.ai',10,10,40);
//		$this->SetFont('Helvetica','B', 14);
//		$this->SetXY(10, 12);
//		$this->Cell($this->_width, 5, $this->_bank, 0, 1, "C");
//		$this->SetFont('Helvetica','', 12);
//		$this->Cell($this->_width, 5, $this->_report, 0, 1, "C");
//		$this->SetXY(10,28);
//    }


    /**
    * Overwrites the default footer
    * set the text in the view using
    * $fpdf->xfootertext = 'Copyright � %d YOUR ORGANIZATION. All rights reserved.';
    */
    function Footer()
    {
		$this->SetY(-10);
		//Page number
		$this->SetFont('Helvetica','', 6);
		$this->Cell(0,10,'Date/Time printed: '.date('Y-m-d H:i:s') ,0,0,'L');
		$this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'R');
		// set footerset
		$this->footerset[$this->page] = 1;
 	}
}
?>
