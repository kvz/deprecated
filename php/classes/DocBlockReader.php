<?php
/**
 * Reads docBlocks from string and returns an array
 * with usefull information.
 *
 */
class DocBlockReader {

    protected $_options = array();

    /**
     * Constructor
     *
     * @return DocBlockReader
     */
    public function  __construct($options = false) {
        $this->_options = array(
            "bash_support" => true,
            "one" => true
        );

        // Overwrite default options
        if (is_array($options)) {
            $this->_options = array_merge($this->_options, $options);
        }
    }
    
    /**
     * Extracts docblocks from any string
     *
     * @param string $str
     * @param array  $options
     * 
     * @return array
     */
    public function getDocBlocks($str, $curOptions=false) {
        // Overwrite default options temporarily
        if (is_array($curOptions)) {
            $curOptions = array_merge($this->_options, $curOptions);
        }

        if ($curOptions["bash_support"]) {
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
            
            if ($curOptions["one"]) {
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

        $codetags = array();
        if (false !== stripos($text, '<code>')) {
            $pat = '/<code>([^<]+)<\/code>/isUm';
            if (preg_match_all($pat, $text, $matches)) {
                $codetags = $matches[1];
            }
        }
        
        return compact("title", "subtitle", "head", "text", "keys", "codetags");
    }
}
class DocBlockReader_Exception extends Exception {
    
}
?>