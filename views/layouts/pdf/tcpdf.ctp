<?php
App::import('Vendor', 'pdfview.xtcpdf');

$pdf = new XTCPDF_report(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);


$subTitle = '';

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
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $title_for_layout, $subTitle);

// set document information
$pdf->SetTitle($title_for_layout);

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
    $htmlcontent = $content_for_layout;
}

if ($debug > 1) {
    print_r($htmlcontent);
    die();
}

$pdf->setY(1.0);

// output the HTML content
//$pdf->writeHTML($htmlcontent, true, 0, true, 0);
writeHTMLSections($pdf, $htmlcontent);


if ($debug < 1) {
    // Disable cache (from Cake core file controller.php, disableCache function):
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-store, no-cache, must-revalidate");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Content-type: application/pdf");
}



echo $pdf->Output($title_for_layout . ' ' . date('Y-m-d') . '.pdf', 'I');
?>