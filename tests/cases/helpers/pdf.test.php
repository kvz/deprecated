<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
App::import('Helper', 'Pdfview.Pdfit');

class PdfTest extends CakeTestCase {
    public $Pdf;
    public $html;
    
    public function startTest() {
        $filename   = dirname(dirname(dirname(__FILE__))).'/fixtures/doc1.html';
        $this->html = file_get_contents($filename);
        
        $this->Pdf  = new PdfitHelper(array(
            'method' => 'xtcpdf',
            'serve' => false,
            'background' => dirname(dirname(dirname(__FILE__))).'/fixtures/truetogether_a.jpg',
        ));
    }

    public function testPdf() {
        $pdfFilePath = $this->Pdf->pdf($this->html);

        $cmd = '/opt/Adobe/Reader9/bin/acroread '.$pdfFilePath;
        echo "$cmd\n";
        shell_exec($cmd);
    }
}
?>