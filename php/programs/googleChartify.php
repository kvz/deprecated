#!/usr/bin/php
<?php
/**
 * Uses googleChartify function to generate Google Chart API URL.
 * Accepts STDIN for data input
 */

require_once dirname(dirname(__FILE__)).'/functions/googleChartify.inc.php';

$options = array(
    'debug' => false,
    'chtt' => 'Some gain after > 50 inserts',
    'chdl' => 'Transaction|Normal',
    'chxl2' => 'inserts',
    'takeColums' => array(1, 2),
);

if (empty($argv[1])) {
    $stdin = fopen('php://stdin', 'r');

    $data = '';
    while(!feof($stdin)) {
        $data .= fgets($stdin);
    }
    fclose($stdin);
} else {
    $options['debug'] = true;
    $data = array();
    $data[] = array(0, 1, 2, 3, 4, 5);
    $data[] = array(1, 2, 3, 4, 5, 6);
    $data[] = array(2, 3, 4, 5, 6, 7);
    $data[] = array(3, 4, 5, 6, 7, 8);
    $data[] = array(4, 5, 6, 7, 8, 9);

}

echo googleChartify($data, $options);
?>