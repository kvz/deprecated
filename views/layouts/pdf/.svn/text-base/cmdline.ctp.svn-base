<?php
App::import('Vendor', 'pdfview.xtcpdf');

set_time_limit(0);
ini_set('memory_limit', '1624M');
$S = new KvzShell(array(
    'enable_trace' => true,
    'die_on_fail' => true,
));

$html = sprintf('<html><body>%s</body></html>', $content_for_layout);

//echo sprintf('<xmp>%s</xmp>', $html);
//return;

$htmlFilePath = tempnam('/tmp', 'pdfconv_'. date('His').'_').'.html';
$psFilePath   = tempnam('/tmp', 'pdfconv_'. date('His').'_').'.ps';
$pdfFilePath  = tempnam('/tmp', 'pdfconv_'. date('His').'_').'.pdf';

// save html
file_put_contents($htmlFilePath, $html);

// 2 ps
$o = $S->exeGlue('html2ps --number -D --toc bh -dc -o ', $psFilePath, $htmlFilePath);

// 2 pdf
$o = $S->exeGlue('ps2pdf', $psFilePath, $pdfFilePath);
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

$filename = $pdfFilePath;
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
@readfile($filename);
exit(0);
?>