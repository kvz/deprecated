<?php
/**
 * Explodes phpt content into different sections
 *
 * @param string $str
 * 
 * @return array
 */
function phptSections($str) 
{
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
    
    $borked    = false;
    $bork_info = '';
    if (strncmp('--TEST--', $line, 8)) {
        $bork_info = "tests must start with --TEST-- [$file]";
        $borked    = true;
    }
    $section = 'TEST';
    $secfile = false;
    $secdone = false;
    foreach ($lines as $line) {
        // Match the beginning of a section.
        if (preg_match('/^--([_A-Z]+)--/', $line, $r)) {
            $section                = $r[1];
            $section_text[$section] = '';
            $secfile                = $section == 'FILE' || $section == 'FILEEOF';
            $secdone                = false;
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
?>