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
            'method' => 'wkhtmltopdf',
            'serve' => false,
            'tidy' => true,
            'background' => 'http://admin.true.dev/img/backgrounds/truetogether_a.pdf',
            'title' => 'Cluster report',
            'subtitle' => 'DHL Productie cluster',
        ));
    }

    public function testPdf() {
        $pdfFilePath = $this->Pdf->pdf($this->html);

        echo 'logs'."\n";
        print_r($this->Pdf->logs);

        $this->assertTrue(!!$pdfFilePath);
        $this->assertTrue(!count(@$this->Pdf->logs['err']));



        
        $cmd = '/opt/Adobe/Reader9/bin/acroread '.$pdfFilePath;
        echo "$cmd\n";
        shell_exec($cmd);
    }
}
?>