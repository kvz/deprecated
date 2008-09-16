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
        if (!isset($options["bash_support"])) $options["bash_support"] = false;
        if (!isset($options["one"])) $options["one"] = false;
        
        if ($options["bash_support"]) {
            $pat = '/[\#]?\/\*\*(.+)[\#]? \*\//isUm';
        } else {
            $pat = '/\/\*\*(.+)[\#]? \*\//isUm';
        }
        
        $blocks = array();
        if (!preg_match_all($pat, $str, $m)) {
            throw new DocBlockReader_Exception("Unable to parse $str");
        }
        
        foreach ($m[1] as $blockNr=>$rawBlock) {
            $rawLines = explode("\n", trim($rawBlock));

            $txtLines = array();
            foreach ($rawLines as $rawLine) {
                $txtLines[] = trim(preg_replace('/^[\s|#|\*]*/', '', $rawLine)); 
            }
            
            $block = $this->parseDocBlock(implode("\n", $txtLines)); 
            $blocks[] = $block; 
            
            if ($options["one"]) {
                break;
            }
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
        $keyChars      = array("@");
        $lines         = explode("\n", $str);
        $head          = "";
        $text          = "";
        $headRecing    = true;
        $keys          = array();
        
        foreach ($lines as $i=>$line) {
            $tline = trim($line);
            $firstChar = substr($tline, 0, 1);
            
            if (in_array($firstChar, $keyChars)) {
                $parts = preg_split('/[\s]+/', $tline,  -1, PREG_SPLIT_NO_EMPTY);
                $keys[array_shift($parts)][] = $parts;
            } else {
                if ($headRecing) {
                    $head .= $tline."\n";
                }
                
                $text .= $tline."\n";
            }
            
            if (!$tline) {
                $headRecing = false;
            }
        }
        
        $parts    = explode("\n", $head);
        $title    = trim(array_shift($parts));
        $subtitle = trim(implode("\n", $parts));
        $head     = trim($head);
        $text     = trim($text);
        
        return compact("title", "subtitle", "head", "text", "keys");
    }
}
class DocBlockReader_Exception extends Exception {
    
}
?>