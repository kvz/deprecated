<?php
/**
 * Reads docBlocks from string and returns an array
 * with usefull information.
 *
 */
class DocBlockReader {
    /**
     * Constructor
     *
     * @return DocBlockReader
     */
    public function DocBlockReader() {
        
    }
    
    /**
     * Extracts docblocks from any string
     *
     * @param string $str
     * @param array  $options
     * 
     * @return array
     */
    public function getDocBlocks($str, $options=false) {
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
            
            $block = $this->parseDocBlock(implode("\n", $txtLines)); 
            $blocks[] = $block; 
        }
        
        return $blocks;
    }
    
    /**
     * Takes a single docBlock string and parses it
     *
     * @param string $str
     * 
     * @return array
     */
    public function parseDocBlock($str) {
        $keyChars   = array("@");
        $lines      = explode("\n", $str);
        $head       = "";
        $text       = "";
        $headRecing = true;
        $keys       = array();
        
        foreach ($lines as $i=>$line) {
            $tline = trim($line);
            $firstChar = substr($tline, 0, 1);
            
            if (in_array($firstChar, $keyChars)) {
                $parts = preg_split('/[\s]+/', $tline,  -1, PREG_SPLIT_NO_EMPTY);
                $keys[array_shift($parts)] = $parts;
            } else {
                if ($headRecing) {
                    $head .= $tline;
                }
                
                $text .= $tline;
            }
            
            if (!$tline) {
                $headRecing = false;
            }
        }
        
        $parts = explode("\n", $head);
        $title = reset($head);
        
        return compact("title", "head", "text", "keys");
    }
}
?>