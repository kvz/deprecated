#!/usr/bin/php
<?php
function phptParts($str) {
    // Load the sections of the test file.
    $section_text = array(
        'TEST'   => '',
        'SKIPIF' => '',
        'GET'    => '',
        'COOKIE' => '',
        'POST_RAW' => '',
        'POST'   => '',
        'UPLOAD' => '',
        'ARGS'   => '',
    );
    
    $lines = explode("\n", $str);
    $line  = array_shift($lines)."\n";
    
    $borked = false;
    $bork_info = '';
    if (strncmp('--TEST--', $line, 8)) {
        $bork_info = "tests must start with --TEST-- [$file]";
        $borked = true;
    }
    $section = 'TEST';
    $secfile = false;
    $secdone = false;
    foreach ($lines as $line) {
        // Match the beginning of a section.
        if (preg_match('/^--([_A-Z]+)--/', $line, $r)) {
            $section = $r[1];
            $section_text[$section] = '';
            $secfile = $section == 'FILE' || $section == 'FILEEOF';
            $secdone = false;
            continue;
        }
        
        // Add to the section text.
        if (!$secdone) {
            $section_text[$section] .= $line."\n";
        }

        // End of actual test?
        if ($secfile && preg_match('/^===DONE===$/', $line."\n")) {
            $secdone = true;
        }        
    }
    return $section_text;
}

// Check file
if (!($filepath = $argv[1])) {
    die("Nothing to test");
} 

$filepath = realpath($filepath);
if (!file_exists($filepath)) {
    die($filepath." does not exist.");
}

$buf      = file_get_contents($filepath);
$sections = phptParts($buf);

if (!($exec = $sections["FILE"])) {
    die("Nothing to execute");
}

$tempfile = $filepath.".php";

$handle = fopen($tempfile, "w");
fwrite($handle, $exec);
fclose($handle);
chmod($tempfile, 0777);

$x = exec("/usr/bin/php -q ".$tempfile, $o, $r);
echo implode("\n", $o);

unlink($tempfile);

//print_r($sections); 
?>