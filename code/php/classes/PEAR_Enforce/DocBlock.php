<?php
/**
 * Generates docBlocks
 *
 */
class DocBlock {
    private $_params;
    private $_header;
    private $_tables;
    
    private $_indent = 0;
    private $_indentation = '';
    private $_docBlock = '';
    private $_maxWidth = '70';
    private $_varTypes = array(
        "string"  => array("T_CONSTANT_ENCAPSED_STRING"), 
        "integer" => array(), 
        "boolean" => array("T_FALSE", "T_TRUE"), 
        "array"   => array()
    );
    private $_newLineBefore = array("return");
    private $_newLineAfter  = array("header");
    private $_newLineChar = "\n";
    
    public  $errors = array();
    
    public function DocBlock () {
        
    }
    
    public function setNewLineChar($newLineChar="\n") {
         $this->_newLineChar = $newLineChar;
    }
    
    /**
     * Returns the length of the longest key in an array 
     *
     * @param array $array
     * 
     * @return integer
     */
    private function _keyMaxLen($array) {
        $longest = 0;
        foreach($array as $key=>$array) {
            $len = strlen($key);
            if($len > $longest) {
                $longest = $len;
            }
        }
        return $longest;
    }    
    
    public function setRow($table, $name, $type="", $description="") {
        $this->_tables[$table][$name]["name"] = trim($name);
        $this->_tables[$table][$name]["type"] = trim($type);
        $this->_tables[$table][$name]["description"] = trim($description);
        return true;
    }
    
    public function setHeader($header="Unknown") {
        $this->_header = $header;
        return true;
    }
    
    public function setIndent($indent) {
        if ($indent > 40 || $indent < 0) {
            return false;
        }
        $this->_indentation = str_repeat(" ", $indent);
        return true;
    }
    
    public function getParams() {
        return $this->_params;
    }
    
    public function generateFile($str="") {
        $this->setRow("author", "Kevin");
        return $this->generate();
    }

    public function generateClass($str="") {
        $this->setRow("author", "Kevin");
        return $this->generate();
    }
    
    
    /**
     * Enter description here...
     *
     * @param unknown_type $codeFunction
     * 
     * @return unknown
     */
    public function generateFunction($codeFunction) {
        
        $Token = new Token($codeFunction);
        
        $this->setHeader("Enter description here...");
        
        $vars = $Token->getVariables();
        foreach ($vars as $var=>$valueData) {
            $know_type = "unknown_type";
            
            if (is_array($valueData)) {
                foreach($this->_varTypes as $type=>$possibilities) {
                    if ($know_type != "unknown_type") continue;
                    if (is_array($possibilities) && in_array($valueData["type"], $possibilities)) {
                        $know_type = $type;
                    }
                }
                
                $know_type = "Kevin_please_translate: ".$valueData["type"];
            }
            
            $this->setRow("param", $var, $know_type, "");
        }
        
        $this->setRow("return", "unknown");
        
        return $this->generate();
    }
    
    private function _longestKeys($array) {
        $longest = array();
        foreach ($array as $name=>$params) {
            foreach($params as $key=>$value) {
                if (!isset($longest[$key])) $longest[$key] = null;
                $len = strlen($value);
                if($len > $longest[$key]) {
                    $longest[$key] = $len;
                }
            }
        }
        return $longest;
    }
    
    private function _addTable2D($tableName, $rows) {
        if (!is_array($rows) || !count($rows)) {
            $this->errors[] = "No valid array provided to _addTable2D";
            return false;
        }
        
        $maxColumnLengths = $this->_longestKeys($rows);
        
        if (in_array($tableName, $this->_newLineBefore)) {
            $this->_addLine("");
        }
        
        foreach ($rows as $rowName=>$cells) {
            $str  = "";
            $str .= "@";
            $str .= $tableName." ";
            
            foreach($cells as $cellName=>$value) {
                $l = $maxColumnLengths[$cellName];
                if (!trim($value) && !$l) continue;
                
                $str .= str_pad($value, $l, " ", STR_PAD_RIGHT)." ";
            }
            $this->_addLine($str);
        }
        
        if (in_array($tableName, $this->_newLineAfter)) {
            $this->_addLine("");
        }
        
    }
    
    private function _addHeader($header="Unknown", $maxWidth=70) {
        if (in_array("header", $this->_newLineBefore)) {
            $this->_addLine("");
        }
        
        $headerLines = explode($this->_newLineChar, wordwrap($header, $maxWidth, $this->_newLineChar, true));
        foreach($headerLines as $headerLine) {
            $this->_addLine($headerLine);
        }
        
        if (in_array("header", $this->_newLineAfter)) {
            $this->_addLine("");
        }
    }
    
    private function _reset() {
        // Reset
        $this->_docBlock = '';
    }
    
    public function generate() {
        $this->_reset();

        $this->_addLine("", "head");
        $this->_addHeader($this->_header, $this->_maxWidth);
        
        foreach ($this->_tables as $tableName=>$rows) {
            $this->_addTable2D($tableName, $rows);
        }
        
        $this->_addLine("", "tail");
        
        return $this->_docBlock;
    }
    
    private function _addLine($str="", $type="body") {
        $in = $this->_indentation;
        
        if ($type == "head") {
            $this->_docBlock .= $in."/**".$this->_newLineChar;
        } elseif ($type == "tail") {
            $this->_docBlock .= $in." */".$this->_newLineChar;
        } else {
            $this->_docBlock .= $in." * $str".$this->_newLineChar;
        }
    }
}
?>