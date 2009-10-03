<?php
/**
 * Explodes phpt content into different sections
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code> 
 * // Input //
 * $input = "--TEST--\n--FILE--\n<?php\necho 'a\n';\n\n?>\n--EXPECT--\na";
 * 
 * // Execute //
 * $sections = phptSections($input);
 * 
 * // Show //
 * print_r($sections);
 * 
 * // expects: 
 * // Array
 * // (
 * //     [TEST] => 
 * //     [SKIPIF] => 
 * //     [GET] => 
 * //     [COOKIE] => 
 * //     [POST_RAW] => 
 * //     [POST] => 
 * //     [UPLOAD] => 
 * //     [ARGS] => 
 * //     [FILE] => <?php
 * // echo 'a
 * // ';
 * // 
 * // ?>
 * // 
 * //     [EXPECT] => a
 * // 
 * // )
 * </code>
 * 
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: phptSections.inc.php 89 2008-09-05 20:52:48Z kevin $
 * @link      http://kevin.vanzonneveld.net/
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