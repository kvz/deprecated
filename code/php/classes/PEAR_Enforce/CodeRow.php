<?php
Class CodeRow {
    private $_codeRow = '';
    private $_length = 0;
    private $_Token = false;
    private $_tokenized = array();
    
    public function CodeRow($codeRow) {
        $this->_codeRow = $codeRow;
        $this->_changed();
    }
    
    private function _changed(){
        $this->_length = strlen($this->_codeRow);
                
        $this->_Token = new Token($this->_codeRow);
        $this->_tokenized = $this->_Token->getTokenized();
    }
    
    public function getCodeRow() {
        return $this->_codeRow;
    }
    
    /**
     * Shorthand method for replace with $use_regex=true
     *
     * @param string $search
     * @param string $replace
     * @param mixed  $onlyTokenTypes
     * 
     * @return boolean
     */
    public function regplace($search, $replace, $onlyTokenTypes=false) {
        return $this->replace($search, $replace, $onlyTokenTypes, true);
    }
    
    /**
     * Main method for replacing current codeRow.
     * Optionally enforces Token Types, and support for regexes 
     *
     * @param string  $search
     * @param string  $replace
     * @param mixed   $onlyTokenTypes
     * @param boolean $use_regex
     * 
     * @return boolean
     */
    public function replace($search, $replace, $onlyTokenTypes=false, $use_regex=false) {

        // Default to array
        if (!$onlyTokenTypes) $onlyTokenTypes = array();
        
        // Allow for token type to be a string; Put inside array anyway
        if (is_string($onlyTokenTypes)) $onlyTokenTypes = array($onlyTokenTypes);
        
        if (count($onlyTokenTypes)) {
            // Only replace within certain token types
            foreach ($this->_tokenized as $i=>$token) {
                // Replace local version of token
                if (in_array($token["type"], $onlyTokenTypes)) {
                    $this->_tokenized[$i] = $this->_replace($search, $replace, $this->_tokenized[$i], $use_regex);
                }
            }
            
            // Save back to token object
            $this->_Token->setTokenized($this->_tokenized);
            
            // Retrieve content from renewed token
            $this->_codeRow = $this->_Token->getContent();
        } else {
            // Replace on entire string
            $this->_codeRow = $this->_replace($search, $replace, $this->_codeRow, $use_regex);
        }
        
        $this->_changed();
        return true;
    }

    /**
     * Underwater method for the actual replacement of a string
     *
     * @param string  $search
     * @param string  $replace
     * @param string  $subject
     * @param boolean $use_regex
     * 
     * @return string
     */
    private function _replace($search, $replace, $subject, $use_regex=false) {
        if ($use_regex) {
            return preg_replace('#'. $search.'#', $replace, $subject);
        } else {
            return str_replace($search, $replace, $subject);
        }
    }

    
    public function setIndent($spaces) {
        $current = $this->getIndent();
        $needed  = $spaces - $current;
        
        if ($needed > 0) {
            $this->_codeRow = str_repeat(" ", $needed).$this->_codeRow;
        } elseif($needed < 0) {
            $this->deleteAt(1, abs($needed));
        }
        
        $this->_changed();
        return true;
    }
    
    /**
     * Automatically switches to backspaceAt when howmany is negative
     *
     * @param integer $at
     * @param string  $chars
     * @param integer $howmany
     * 
     * @return boolean
     */
    public function insertAt($at, $chars=" ", $howmany=1) {
        if ($howmany < 0) {
            return $this->backspaceAt($at, abs($howmany));
        }
        
        $t = $this->_codeRow;
        // Compensate
        $at--;
        $this->_codeRow = substr($t, 0, $at) . str_repeat($chars, $howmany) . substr($t, $at, strlen($t));
        
        $this->_changed();
        return true;
    }
    
    public function deleteAt($at, $howmany=1) {
        $t = $this->_codeRow;
        // Compensate
        $at--;
        $this->_codeRow = substr($t, 0, $at) . substr($t, $at+$howmany, strlen($t));
        
        $this->_changed();
        return true;
    }
    
    public function backspaceAt($at, $howmany=1) {
        $t = $this->_codeRow;
        // Compensate
        $at--;
        $this->_codeRow = substr($t, 0, $at-$howmany) . substr($t, $at, strlen($t));
        
        $this->_changed();
        return true;
    }
    
    public function getTokenized() {
        return $this->_tokenized;
    }
    
    public function getPosEqual() {
        foreach ($this->_tokenized as $i=>$token) {
             if ($token["type"] == 'T_EQUAL') {
                 return $token["col"];
             }
        }
        
        return 0;
    }
    
    public function getOpeningBracePos() {
        // Compensate
        return strpos($this->_codeRow, "{")+1;
    }

    public function getClosingBracePos() {
        // Compensate
        return strrpos($this->_codeRow, "}")+1;
    }
        
    public function getIndentation($extra = 0){
        return str_repeat(" ", $this->getIndent($extra));
    }
    
    public function getIndent($extra = 0) {
        for ($i = 0; $i < $this->_length; $i++) {
            if (substr($this->_codeRow, $i, 1) != " " && substr($this->_codeRow, $i, 1) != "\t") {
                return $i + $extra;
            }
        }
        return false;
    }    
}
?>