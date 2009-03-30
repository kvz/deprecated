#!/usr/bin/php
<?php
/**
 * Uses googleChartify function to generate Google Chart API URL.
 * Accepts STDIN for data input
 */

require_once dirname(dirname(__FILE__)).'/functions/googleChartify.inc.php';

$stdin = fopen('php://stdin', 'r');

$data = '';
while(!feof($stdin)) {
    $data .= fgets($stdin);
}
fclose($stdin);

$options = array(
    'debug' => false,
    'chtt' => 'Some gain after > 50 inserts',
    'chdl' => 'Transaction|Normal',
    'chxl2' => 'inserts',
    'takeColums' => array(1, 2),
);
echo googleChartify($data, $options);
?>