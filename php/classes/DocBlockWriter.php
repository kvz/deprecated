<?php
/**
 * Writes docBlocks based on input
 *
 * PHP version 5
 *
 * @package   DocBlockWriter
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: DocBlockWriter.php 220 2009-01-12 16:41:34Z kevin $
 * @link      http://kevin.vanzonneveld.net/code/
 */
class DocBlockWriter {
    protected $_params;
    protected $_header;
    protected $_tables;
    protected $_fields;
    
    protected $_indent = 0;
    protected $_indentation = '';
    protected $_docBlock = '';
    protected $_maxWidth = '70';
    protected $_varTypes = array(
        "string"  => array("T_CONSTANT_ENCAPSED_STRING"), 
        "integer" => array(), 
        "boolean" => array("T_FALSE", "T_TRUE"), 
        "array"   => array()
    );
    protected $_newLineBefore = array("return");
    protected $_newLineAfter  = array("header");
    protected $_newLineChar = "\n";
    
    protected $_docBlockDefaults = array();
    
    protected $_lastWasEmptyLine = false;
    
    public  $errors = array();
    
    public function DocBlock () {
        $this->_docBlockDefaults = array(
            "@category" => "Unknown_Category",
            "@package" => "Unknown_Package", 
            "@author" => "Unknown Author <unknown_firstname@unknown_domain.tld>",
            "@copyright" => date("Y")." Unknown Author (http://unknown_url)",  
            "@license" => "New BSD License",
            "@version" => "SVN: Release: \$Id\$", 
            "@link"=> "http://unknown_url"
        );
    }
    
    public function setDocBLockDefaults($defaults) {
        $this->_docBlockDefaults = array_merge($this->_docBlockDefaults, $defaults);
        return true;
    }
    
    protected function _reset() {
        // Reset
        $this->_docBlock = '';
        $this->_header = '';
        $this->_tables = array();
        $this->_fields = array();
        $this->_lastWasEmptyLine = false;
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
    protected function _longestKey($array) {
        $longest = 0;
        foreach($array as $key=>$array) {
            $len = strlen($key);
            if($len > $longest) {
                $longest = $len;
            }
        }
        return $longest;
    }    
    
    /**
     * Enter description here...
     *
     * @param unknown_type $array
     * @return unknown
     */
    protected function _longestKeys2D($array) {
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
    
    public function setRow($table, $name, $type="", $description="") {
        $this->_tables[$table][$name]["type"] = trim($type);
        $this->_tables[$table][$name]["name"] = trim($name);
        $this->_tables[$table][$name]["description"] = trim($description);
        return true;
    }
    
    public function setField($fieldName, $fieldValue) {
        $this->_fields[$fieldName] = $fieldValue;
        return true;
    }
    
    public function addHeader($header="Unknown") {
        $this->_header[] = $header;
        return true;
    }
    
    public function setIndent($indent) {
        if ($indent > 40 || $indent < 0) {
            return false;
        }
        $this->_indent = $indent; 
        $this->_indentation = str_repeat(" ", $indent);
        return true;
    }
    
    public function getParams() {
        return $this->_params;
    }
    
    public function setFields($info) {
        if (!$info) $info = array();
        $licenseUrls = array(
            "New BSD License" => "http://www.opensource.org/licenses/bsd-license.php"
        );
        
        $fields = array_merge($this->_docBlockDefaults, $info);
        
        if (isset($fields["license"])) {
            if (isset($licenseUrls[$fields["license"]])) {
                $licenseUrl = $licenseUrls[$fields["license"]];
                $fields["license"] = $licenseUrl." ".$fields["license"];
            }
        }
        foreach ($fields as $fieldName=>$fieldValue) {
            $this->setField($fieldName, $fieldValue);
        }
    }
    
    public function generateFile($info=false, $php_version="5") {
        $this->addHeader("Unknown Package");
        $this->addHeader("PHP version ".$php_version);
        $this->setFields($info);
        return $this->_generate();
    }

    public function generateClass($info=false) {
        $this->addHeader("Unknown Class");
        $this->setFields($info);
        return $this->_generate();
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $codeFunction
     * 
     * @return unknown
     */
    public function generateFunction($codeFunction) {
        $unKnown = "unknown_type";
        $Token   = new Token($codeFunction);
        
        $this->addHeader("Enter description here...");
        
        $vars = $Token->getVariables();
        foreach ($vars as $var=>$valueData) {
            $knowType = $unKnown;
            
            if (is_array($valueData)) {
                foreach($this->_varTypes as $type=>$possibilities) {
                    if (is_array($possibilities) && in_array($valueData["type"], $possibilities)) {
                        $knowType = $type;
                        break;
                    }
                }
                if ($knowType == $unKnown) {
                    $knowType = "Kevin_please_translate: ".$valueData["type"];
                }
            }
            
            $this->setRow("param", $var, $knowType, "Enter description here...");
        }
        
        $this->setRow("return", $unKnown);
        return $this->_generate();
    }
    
    protected function _generate() {

        $this->_writeLine("", "head");
        
        $this->_writeHeaders($this->_maxWidth);
        
        $this->_writeFields();
        
        if (count($this->_tables)) {
            $this->_writeLine("");
            $this->_writeTables();
        }
        
        $this->_writeLine("", "tail");
        
        return "\n".$this->_docBlock.$this->_indentation;
    }
    
    protected function _writeTable2D($tableName, $rows) {
        if (!is_array($rows) || !count($rows)) {
            $this->errors[] = "No valid array provided to ".__FUNCTION__;
            return false;
        }
        
        $maxColumnLengths = $this->_longestKeys2D($rows);
        
        if (in_array($tableName, $this->_newLineBefore)) {
            $this->_writeLine("");
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
            $this->_writeLine($str);
        }
        
        if (in_array($tableName, $this->_newLineAfter)) {
            $this->_writeLine("");
        }
    }
    
    protected function _writeFields() {
        $fields = $this->_fields;
        if (!is_array($fields) || !count($fields)) {
            $this->errors[] = "No valid array provided to ".__FUNCTION__;
            return false;
        }
        
        $this->_writeLine("");
        
        $l = $this->_longestKey($fields);
        foreach ($fields as $fieldName=>$fieldValue) {
            $str  = "";
            $str .= str_pad($fieldName, $l, " ", STR_PAD_RIGHT)." ".$fieldValue;
            $this->_writeLine($str);
        }
    }
    
    protected function _writeTables() {
        $tables = $this->_tables;
        if (!is_array($tables) || !count($tables)) {
            $this->errors[] = "No valid array provided to ".__FUNCTION__;
            return false;
        }
        
        foreach ($tables as $tableName=>$rows) {
            $this->_writeTable2D($tableName, $rows);
        }
    }
    
    protected function _writeHeaders($maxWidth=70) {
        $headers = $this->_header;
        if (!is_array($headers) || !count($headers)) {
            $this->errors[] = "No valid array provided to ".__FUNCTION__;
            return false;
        }
                
        foreach ($headers as $header) {
            if (in_array("header", $this->_newLineBefore)) {
                $this->_writeLine("");
            }
            
            $headerLines = explode($this->_newLineChar, wordwrap($header, $maxWidth, $this->_newLineChar, true));
            foreach($headerLines as $headerLine) {
                $this->_writeLine($headerLine);
            }
            
            if (in_array("header", $this->_newLineAfter)) {
                $this->_writeLine("");
            }
        }
    }

    
    protected function _writeLine($str="", $type="body") {
        $thisIsEmptyLine = ($str == "");
        
        if ($type=="body" && $this->_lastWasEmptyLine == true && $thisIsEmptyLine == true) {
            // No double empty lines allowed!
        } else {
            $in = $this->_indentation;
            
            if ($type == "head") {
                $this->_docBlock .= $in."/**".$this->_newLineChar;
            } elseif ($type == "tail") {
                $this->_docBlock .= $in." */".$this->_newLineChar;
            } else {
                $this->_docBlock .= $in." * $str".$this->_newLineChar;
            }
        }
            
        $this->_lastWasEmptyLine = $thisIsEmptyLine;
    }
}
?>