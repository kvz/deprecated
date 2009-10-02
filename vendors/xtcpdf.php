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
class XTCPDF extends TCPDF {
    public $backgroundImage;
    function Header() {

        // Full background image
        $auto_page_break = $this->AutoPageBreak;
        $this->SetAutoPageBreak(false, 0);
        $img_file = $this->backgroundImage;
        $this->Image($img_file, $x=0, $y=0, $w=210, $h=297, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0);
        $this->SetAutoPageBreak($auto_page_break);
        

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
?>
