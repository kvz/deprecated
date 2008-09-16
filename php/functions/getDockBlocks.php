<?php
/**
 * Enter description here...
 *
 * @param unknown_type $buf
 * @param unknown_type $options
 * 
 * @return unknown
 */
function getDocBlocks($str, $options=false) {
    if (!$options) $options = array();
    if (!isset($options[""]))
    
    $pat = '/[\#]?\/\*\*(.+)[\#]? \*\//isUm';
    
    $blocks = array();
    preg_match_all($pat, $str, $m);
    
    foreach ($m[1] as $blockNr=>$rawBlock) {
        $rawLines = explode("\n", trim($rawBlock));
        $txtLines = array();
        foreach ($rawLines as $rawLine) {
            $txtLine = trim(preg_replace('/^[\s|#|\*]*/', '', $rawLine));
            $txtLines[] = $txtLine; 
        }
        
        $block = parseDocBlock(implode("\n", $txtLines)); 
        $blocks[] = $block; 
    }
    
    return $blocks;
}
?>