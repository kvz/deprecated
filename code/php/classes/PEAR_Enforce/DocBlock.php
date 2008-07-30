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
    private $_varTypes = array("string", "integer", "boolean", "array");
    private $_newLineBefore = array("return");
    private $_newLineAfter = array("header");
    
    public  $errors = array();
    
    public function DocBlock () {
        
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
    
    public function setRow($table, $name, $type=false, $description=false) {
        $this->_tables[$table][$name] = compact("name", "type", "description");
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
    
    public function generateFunction($codeFunction) {
        
        $Token = new Token($codeFunction);
        
        return print_r($Token->getVariables(), true);
        return $this->generate();
    }
    
    private function _longestKeys($array) {
        $longest = array();
        foreach ($array as $name=>$params) {
            foreach($params as $key=>$value) {
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
        
        $headerLines = explode("\n", wordwrap($header, $maxWidth, "\n", true));
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
            $this->_docBlock .= $in."/**"."\n";
        } elseif ($type == "tail") {
            $this->_docBlock .= $in." */"."\n";
        } else {
            $this->_docBlock .= $in." * $str"."\n";
        }
    }
}
?>