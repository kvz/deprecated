<?php
/**
 * Collects and parses docBlocks
 *
 * PHP version 5
 *
 * @package   DocBlockReader
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: DocBlockReader.php 220 2009-01-12 16:41:34Z kevin $
 * @link      http://kevin.vanzonneveld.net/code/
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
        } else {
            $curOptions = $this->_options;
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
        $body          = "";
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
                } else {
                    $body .= $tline."\n";
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
        
        return compact("title", "subtitle", "head", "body", "text", "keys", "codetags");
    }
}
class DocBlockReader_Exception extends Exception {
    
}
?>