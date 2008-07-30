#!/usr/bin/php -q
<?php
Class CodeRow {
    private $_codeRow = '';
    private $_length = 0;
    
    public function CodeRow($codeRow) {
        $this->_codeRow = $codeRow;
        $this->_changed();
    }
    
    private function _changed(){
        $this->_length = strlen($this->_codeRow);
    }
    
    public function getCodeRow() {
        return $this->_codeRow;
    }
    
    
    public function regplace($search, $replace) {
        $this->_codeRow = preg_replace('#'. $search.'#', $replace, $this->_codeRow);
        $this->_changed();
        return true;
    }

    public function replace($search, $replace) {
        $this->_codeRow = str_replace($search, $replace, $this->_codeRow);
        $this->_changed();
        return true;
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

    public function getEqualSignPos(){
        $illegal = array("'", '"');
        $escape = '\\';
        $off = array();
        $p = "";
        
        for ($i=0; $i<$this->_length; $i++) {
            // Current char
            $c = substr($this->_codeRow, $i, 1);
            
            // Previous char
            if ($i> 0) {
                $p = substr($this->_codeRow, $i-1, 1);
            }

            // Dont allow the Equal sign to be inside a string
            // Keep escapes in mind \' != '
            if (in_array($c, $illegal) && $p != $escape) {
                $off[$c] = ($off[$c]?false:true);
            }
            
            // It's not inside a string
            if ($c == "=" && !in_array(true, $off)) {
                // Compensate
                return $i+1;
            }
        }
        
        return 0;
    }
    
    public function getOpeningBracePos(){
        // Compensate
        return strpos($this->_codeRow, "{")+1;
    }

    public function getClosingBracePos(){
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

Class PHPCS_Comply {

    /**
     * System is unusable
     */
    const LOG_EMERG = 0;
    
    /**
     * Immediate action required
     */ 
    const LOG_ALERT = 1;
    
    /**
     * Critical conditions
     */
    const LOG_CRIT = 2;
    
    /**
     * Error conditions
     */
    const LOG_ERR = 3;
    
    /**
     * Warning conditions
     */
    const LOG_WARNING = 4;
    
    /**
     * Normal but significant
     */
    const LOG_NOTICE = 5;
    
    /**
     * Informational
     */
    const LOG_INFO = 6;
    
    /**
     * Debug-level fixMessages
     */
    const LOG_DEBUG = 7;
    
    private $_problems = 0;
    private $_problems_fixed = 0;
    private $_report_log = "";
    private $_fixCodeMaxLen = 0;
    private $_fixed_show = array();
    private $_definitions = array();
    private $_file_original = false;
    private $_file_improved = false;
    private $_rowProblems = array();
    private $_CodeRows = false;
    private $_postFormatAddNewline  = "<@!NEWLINE!@>";
    private $_postFormatBackSpaceCB = "<@!BACKSPACE,}!@>";
    
    
    public $cmd_phpcs = "/usr/bin/phpcs";
    
    /**
     * == General Functions 
     */
    
    /**
     * Takes first part of a string based on the delimiter.
     * Returns that part, and mutates the original string to contain
     * the reconcatenated remains 
     *
     * @param string $delimiter
     * @param string &$string
     * 
     * @return string
     */
    private function _str_shift($delimiter, &$string)
    {
        // Explode into parts
        $parts  = explode($delimiter, $string);
        
        // Shift first
        $first  = array_shift($parts);
        
        // Glue back together, overwrite string by reference
        $string = implode($delimiter, $parts);
        
        // Return first part
        return $first;
    }
    
    /**
     * Returns the length of the longest val in an 2D array 
     *
     * @param array $array
     * 
     * @return integer
     */
    private function _valMaxLen2D($array) {
        $longest = 0;
        foreach($array as $key=>$array) {
            foreach($array as $val) {
                $len = strlen($val);
                if($len > $longest) {
                    $longest = $len;
                }
            }
        }
        return $longest;
    }
    
    /**
     * Execute a shell command and returns an array with 
     * first element: success, second element: output
     *
     * @param string $cmd
     * 
     * @return array
     */
    private function _exe($cmd) {
        $o = array(); 
        exec($cmd, $o, $r);
        if ($r) {
            // Probably a syntax error
            return array(false, $o);
        }
        return array(true, $o);
    }    
    
    /**
     * == Specific Private Functions 
     */
    
    private function _getPattern($fixCode) {
        //print_r($this->_definitions);
        
        foreach($this->_definitions as $pattern=>$fixCodes) {
            if (array_search($fixCode, $fixCodes) !== false){
                return $pattern;
            }
        }
        return false;        
    }
    
    private function _setDefinitions($add_definitions=false) {
        if (!$add_definitions) $add_definitions = array();

        /* 
         * How to make abbreviations
         * 
         * MIS  Missing
         * FND  Found
         * IVD  Invalid
         * VLD  Valid
         * 
         * TOO  Too
         * LNG  Long
         * UPC  Uppercase
         * 
         * FCD  Function declaration
         * FCC  Function call
         * STM  Statement
         * CNS  Constant
         * TAG  Tag
         * ELS  else
         * DSC  Doc Style Comments
         * ANC  Allowed Normal Comments
         * PSC  Perl Style Comments
         *
         * BRC  Brace
         * PTH  Parenthesis
         * 
         * SPC  Space
         * NWL  Newline
         * FIL  File
         * IND  Indentation
         * SWS  Superfluous white-space
         * WNL  Windows Newline
         * 
         * ARN  Around
         * BFR  Before
         * AFT  After
         * OPN  Opening
         * CLS  Closing
         * 
        */
                
        $predefined = array();
        $prepared   = array();
        
        // Large Indentation & Alignment
        $predefined['Spaces must be used to indent lines; tabs are not allowed'][]            = 'FND_TAB';
        $predefined['End of line character is invalid; expected \"\n\" but found \"\r\n\"'][] = 'FND_WNL';
        $predefined['Line exceeds %d characters; contains %d characters'][]                   = 'TOO_LNG';
        
        $predefined['Line indented incorrectly; expected %d space%z, found %d'][]            = 'IND';
        $predefined['Line indented incorrectly; expected at least %d space%z, found %d'][]   = 'IND';
        $predefined['Break statement indented incorrectly; expected %d space%z, found %d'][] = 'IND';
        $predefined['Closing brace indented incorrectly; expected %d space%z, found %d'][]   = 'IND';
        
        $predefined['Equals sign not aligned correctly; expected %d space%z but found %d space%z'][]                     = 'MIS_ALN';
        $predefined['Equals sign not aligned with surrounding assignments; expected %d space%z but found %d space%z'][]  = 'MIS_ALN';
        
        // Small alignment
        $predefined['Space found before comma in function call'][]           = 'FND_SWS_BFR_CMA';
        $predefined['Expected \"} else {\n\"; found \"}\n    else{\n\"'][]   = 'FND_SWS_BFR_ELS';
        $predefined['Expected \"} elseif (...) {\n\"; found \"...) {\n\"'][] = 'FND_SWS_BFR_ELI';
        
        $predefined['Space %c %c parenthesis of function call prohibited'][] = 'FND_SPC_PTH';

        $predefined['Expected \"if (...) {\n\"; found \"...){\"'][]         = 'MIS_NWL_AFT_OPN_BRC'; // Two things wrong with same pattern!
        $predefined['Expected \"if (...) {\n\"; found \"...){\"'][]         = 'MIS_SPC_BFR_OPN_BRC'; // Two things wrong with same pattern!
        $predefined['Expected \"%c (...) {\n\"; found \"...){\n\"'][]       = 'MIS_SPC_BFR_OPN_BRC';
        $predefined['Expected \"for (...) {\n\"; found \"...){\n\"'][]      = 'MIS_SPC_BFR_OPN_BRC';
        $predefined['Expected \"} elseif (...) {\n\"; found \"...){\n\"'][] = 'MIS_SPC_BFR_OPN_BRC';
        $predefined['Expected \"} else {\n\"; found \"} else{\n\"'][]       = 'MIS_SPC_BFR_OPN_BRC';
        $predefined['No space found after comma in function call'][]        = 'MIS_SPC_AFT_CMA';
        
        // Newlines
        $predefined['Closing brace must be on a line by itself'][]      = 'MIS_NWL_ARN_CLS_BRC';
        $predefined['Opening function brace should be on a new line'][] = 'MIS_NWL_ARN_OPN_BRC';

        // Comments
        $predefined['You must use \"/**\" style comments for a %c comment'][]                                   = 'IVD_DSC';
        $predefined['Perl-style comments are not allowed. Use \"// Comment.\" or \"/* comment */\" instead.'][] = 'IVD_PSC';
        $predefined['Missing %c doc comment'][]                                                                 = 'MIS_DSC';
        
        // Language
        $predefined['Short PHP opening tag used. Found \"<?\" Expected \"<?php\".'][]       = 'MIS_LNG_TAG';
        $predefined['Constants must be uppercase; expected %c but found %c'][]              = 'MIS_UPC_CNS';
        $predefined['\"%c\" is a statement, not a function; no parentheses are required'][] = 'FND_PTH_ARN_STM';
        $predefined['File is being unconditionally included; use \"require\" instead'][]    = 'FND_IVD_STM';
        
        
        
        //$this->_definitions      = array_merge($predefined, $add_definitions);
        
        foreach ($predefined as $pattern=>$fixCodes) {
            $prep = $this->_patternPrepare($pattern);
            $prepared[$prep] = $fixCodes;
        }
        
        $this->_definitions = $prepared;
        $this->_fixCodeMaxLen = $this->_valMaxLen2D($this->_definitions);
    }
    
    private function _patternPrepare($pattern) {
        $pattern = preg_quote($pattern);
        $pattern = str_replace('%c', '(\w[\w\d_]+)', $pattern);
        $pattern = str_replace('%d', '(\d+)', $pattern);
        $pattern = str_replace('%s', '([\s \t]+)', $pattern);
        $pattern = str_replace('%z', '[s]?', $pattern);
        $pattern = str_replace('%a', '(.+?)', $pattern);
        
        return $pattern;
    }    
    
    private function _log($str, $level=PHPCS_Comply::LOG_INFO) {
        echo $str."\n";
        if ($level <= PHPCS_Comply::LOG_CRIT) {
            die();
        }
    }

    /**
     * Performs massive changes like converts tabs to 4 spaces
     *
     * @param string $source
     * 
     * @return string
     */
    private function _preFormat($source) {
        return str_replace("\t", "    ", $source);
    }
    
    /**
     * Performs massive changes like converts special chars to newlines
     *
     * @param string $source
     * 
     * @return string
     */
    private function _postFormat($source) {
        return str_replace($this->_postFormatAddNewline, "\n", $source);
    }    
    
    /**
     * Opens a file, optionally preformats and saves it to a working file. 
     *
     * @param string $file
     * @param boolean $preformat
     * 
     * @return boolean
     */
    private function _loadFile($file, $preformat=true) {
        
        $file = realpath($file);
        
        if ($file == $this->_file_original) {
            $this->_log("File '$file' already loaded", PHPCS_Comply::LOG_DEBUG);
            return true;
        }
        
        if (!file_exists($file)) {
            $this->_log("File '$file' does not exist", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        if (substr($file, -4) != ".php") {
            $this->_log("File '$file' does not end in .php", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        $this->_file_original = $file; 
        $this->_file_improved = str_replace(".php", ".comply.php", $this->_file_original);
        
        if ($this->_file_original == $this->_file_improved) {
            $this->_log("Both original and improved file locations ended up being identical: '".$this->_file_original."'", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        $source = file_get_contents($this->_file_original);
        
        if ($preformat) {
            $source = $this->_preFormat($source);
        }
        
        if (!file_put_contents($this->_file_improved, $source)) {
            $this->_log("Cannot write to file '".$this->_file_improved."'", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        return true;
    }
    
    private function _determineFixCodes($fixMessage) {
        if (!is_array($this->_definitions) || count($this->_definitions) < 5) {
            log("What happened to my fixcode definitions?!", PHPCS_Comply::LOG_EMERG);
            return false;
        }
        
        foreach($this->_definitions as $pattern=>$fixCodes) {
            if (!$pattern || !$fixCodes) continue;
            if (preg_match("#".$pattern."#i", $fixMessage)) {
                return array($pattern, $fixCodes); 
            }
        }
        
        if (!count($use_codes)) {
            return array(false, array("**UNKNOWN"));
        } 
    }    
    
    private function _runPHPCS($file) {
        
        if (!file_exists($this->cmd_phpcs)) {
            log("Please: aptitude install php-pear && pear install PHP_Codesniffer", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        $results = array();
        
        $cmd = $this->cmd_phpcs." --standard=PEAR --report=csv ".$file;
        list($success, $codeRows) = $this->_exe($cmd);
        
        foreach ($codeRows as $i=>$codeRow) {
            $src = trim(str_replace('"', '', $this->_str_shift(",", $codeRow)));
            $row = trim(str_replace('"', '', $this->_str_shift(",", $codeRow)));
            $col = trim(str_replace('"', '', $this->_str_shift(",", $codeRow)));
            $lvl = trim(str_replace('"', '', $this->_str_shift(",", $codeRow)));
            $fixMessage = trim($codeRow);
            
            if ($src == "File") continue;
            
            $results[$row][$col][] = compact("lvl", "fixMessage");
        }
        
        return $results;        
    }
    
    private function _improveCode($results) {
        $this->_report_log = "";
        $this->_fixed_results = array();
        
        $this->_CodeRows = array();
        $lines = file($this->_file_improved);
        foreach($lines as $i=>$codeRow) {
            // Compensate
            $this->_CodeRows[$i+1] = new CodeRow($codeRow);
        }
        
        foreach($results as $row=>$cols) {
            $this->_rowProblems[$row] = array();
            foreach($cols as $col=>$reports) {
                foreach($reports as $nmr=>$report) {
                    extract($report);
                    list($pattern, $fixCodes) = $this->_determineFixCodes($fixMessage);
                    $this->_rowProblems[$row] = array_merge($this->_rowProblems[$row], $fixCodes);
                    
                    $this->_problems++;
                    foreach($fixCodes as $fixCode) {
                        $this->_report_log .= " ".str_pad($fixCode, $this->_fixCodeMaxLen, " ", STR_PAD_LEFT)." ";
                        $this->_report_log .= str_pad($lvl, 7, " ", STR_PAD_RIGHT)." ";
                        $this->_report_log .= str_pad($row, 4, " ", STR_PAD_LEFT)." ";
                        $this->_report_log .= str_pad($col, 3, " ", STR_PAD_LEFT)." ";
                        $before = str_replace("\n", "", $this->_CodeRows[$row]->getCodeRow());
                        if ($this->_fixProblem($fixMessage, $fixCode, $pattern, $row, $col)) {
                            $this->_problems_fixed++;
                            $this->_fixed_show[$fixCode][$row]["assig"] = $fixMessage; 
                            $this->_fixed_show[$fixCode][$row]["befor"] = $before;
                            $this->_fixed_show[$fixCode][$row]["after"] = str_replace("\n", "", $this->_CodeRows[$row]->getCodeRow());
                            $this->_report_log .= str_pad("FIXED", 7, " ", STR_PAD_LEFT)." ";
                            $this->_report_log .= $fixMessage;
                        } else {
                            $this->_report_log .= str_pad("UNFIXED", 7, " ", STR_PAD_LEFT)." ";
                            $this->_report_log .= $fixMessage;
                            
                        }
                        $this->_report_log .= "\n";
                    }
                }
            }
        }
        
        $this->_fixed_results[$file]  = "";
        foreach($this->_CodeRows as $CodeRow) {
            $this->_fixed_results[$file] .= $CodeRow->getCodeRow();
        }
        
        return $this->_fixed_results;
    }
    
    /**
     * Fix a spefic problem as reported by PHPCS
     *
     * @param array   $codeRows
     * @param string  $fixMessage
     * @param string  $fixCode
     * @param string  $pattern
     * @param integer $row
     * @param integer $col
     * 
     * @return boolean
     */
    private function _fixProblem($fixMessage, $fixCode, $pattern, $row, $col) {
        
        $debug = false;
        $CodeRow = $this->_CodeRows[$row];
        $matches = array();
        
        // Get matched variables from message based on pattern 
        preg_match_all("#".$pattern."#", $fixMessage, $matches_raw);
        unset($matches_raw[0]);
        foreach($matches_raw as $i=>$match) {
            $matches[$i-1] = $match[0];
        }
        
        
         
        switch ($fixCode) {
            case "MIS_ALN":
                // Equals sign not aligned correctly
                
                list($expected, $found) = $matches;
                $needed = $expected - $found;
                
                // Insert will actually backspace on negative amount
                $CodeRow->insertAt($CodeRow->getEqualSignPos(), " ", $needed);
                
                break;
            case "IND":
                $CodeRow->setIndent($matches[1]);
                break;
            case "FND_SWS_BFR_CMA":
                // @todo Running 
                // /home/kevin/workspace/plutonia-kvzlib/code/php/programs/pearcompat.php ./storage.php FND_SWS_BFR_CMA
                // messes up quotes 
                ///////////////////////$CodeRow->regplace('([^"\'])(.*?)[\s+],(.*?)([^"\'])', '$1$2,$3$4');
                break;
            case "MIS_SPC_AFT_CMA":
                $CodeRow->regplace(',([^ ])', ', $1');
                break;
			case "MIS_SPC_BFR_OPN_BRC":
				// Expected \"if (...) {\n\"; found \"...){\n\"
				// ){
				$CodeRow->insertAt($CodeRow->getOpeningBracePos(), " ");
				break;
			case "MIS_NWL_ARN_CLS_BRC":
				// Closing brace must be on a line by itself
				$CodeRow->insertAt($CodeRow->getClosingBracePos(), 
				    $this->_postFormatAddNewline . $CodeRow->getIndentation());
				break;
			case "MIS_NWL_AFT_OPN_BRC":
				// Not the same as MIS_NWL_ARN_OPN_BRC, which will put the brace itself
				// on a newline (for functions)
				// "Expected \"if (...) {\n\"; found \"...){\
				// {!\n
                $CodeRow->insertAt($CodeRow->getOpeningBracePos()+1, 
                    $this->_postFormatAddNewline . $CodeRow->getIndentation(4));
				break;
            case "MIS_NWL_ARN_OPN_BRC":
                // Opening function brace should be on a new line
				$CodeRow->insertAt($CodeRow->getOpeningBracePos(), 
				    $this->_postFormatAddNewline . $CodeRow->getIndentation());
                break;
            case "FND_SWS_BFR_ELS":
                // Expected \"} else {\n\"; found \"}\n    else{\n\"
                $CodeRow->replace('else{', $this->_postFormatBackSpaceCB. ' else {');
                
                break;
            case "FND_SPC_PTH":
				// Space surrounding parentheses
                list($spc_loc, $pth_typ) = $matches;
                $a = $b = $pth = "";
                
                $pth = ($pth_typ == 'opening' ? '(\()' : '(\()');
                $spc = '[\s+]';
                
                if ($spc_loc == 'before') {
                    $a = $spc;
                } else {
                    $b = $spc;
                }
                
                $CodeRow->regplace($a.$pth.$b, '$1');
                break;
            default:
                return false;
                break;
        }
        
        // Default case returns false, so all matched cases don't have to.
        if ($debug) {
            $this->_fixed_show[$fixCode][$row]["debug"] = $debug;
        }
        return true;
    }

    
    
    /**
     * == Specific Public Functions 
     */
    
    /**
     * Constructor. Use argument to load a file
     *
     * @param string $file
     * 
     * @return PHPCS_Comply
     */
    public function PHPCS_Comply($file = false) {        
        
        $this->_setDefinitions();
        if ($file) {
            if (!$this->_loadFile($file)){
                return false;
            }
        }
        return true;
    }
        
    /**
     * Combines private functions to convert loaded codefile and store the 
     * improved version in $this->_file_improved
     *
     * @return boolean
     */
    public function comply() {
        if (!$this->_file_original) {
            $this->_log("Please load a file first, this can be done by using the constructor.", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        if (!file_exists($this->_file_original)) {
            $this->_log("File '".$this->_file_original."' does not exist", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        if (!file_exists($this->_file_improved)) {
            $this->_log("File '".$this->_file_improved."' should have been created automatically, but does not exist", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        $results = $this->_runPHPCS($this->_file_original);
        $imroved = $this->_postFormat($this->_improveCode($results));
        

        if (!file_put_contents($this->_file_improved, $imroved)) {
            $this->_log("Cannot write to file '".$this->_file_improved."'", PHPCS_Comply::LOG_CRIT);
            return false;
        }
        
        return true;
    }
    
    /**
     * Returns detailed reports about the conversion
     *
     * @param string $which Which report. Use a definition to get details of one fix-type.
     * 
     * @return string
     */
    public function report($which="STANDARD") {
        $report  = "";
        
        $pattern = $this->_getPattern($which);
        
        if ($which && $pattern) {
            $report .= "Results for $which fix, pattern: \n    ".$pattern.""."\n"."\n";
            
            $row = false;
            foreach($this->_rowProblems as $row=>$fixCodes) {
                if (in_array($which, $fixCodes)) {
                    break;
                }
            }
            
            if ($row) {
                $report .= "Example at line $row: \n    ".$this->_CodeRows[$row]->getCodeRow()."\n"."\n"; 
            }
            
            $report .= print_r($this->_fixed_show[$which], true);
        } else {
            $report .= "All fixes"."\n";
            $report .= $this->_report_log;
        }

        $report .= "\n"."\n";
        $report .= "Detected ".$this->_problems." problems"."\n";
        $report .= "Fixed    ".$this->_problems_fixed." problems"."\n";
        $report .= "Saved to ".$this->_file_improved.""."\n";
        
        return $report;
    }
}


if ($argv[2] == "test") {
    
    
    if (!$argv[3]) {
        $test = 1; 
    } else {
        $test = $argv[3];
    }
    
    echo "Running Test [$test]\n\n";
    
    $CodeRow = new CodeRow("abcdefghijklmnopqrstuvwxyz'=' = 12345");
    
    switch ($test) {
        case 1:
            $CodeRow->setIndent(12);
            break;
        case 2:
            $CodeRow->deleteAt(4, -2);
            break;
        case 3:
            $CodeRow->insertAt(4, "x", -2);
            break;
        case 4:
            
            break;
    }
    
    
    echo $CodeRow->getCodeRow()."\n";
    echo $CodeRow->getEqualSignPos()."\n";
    
    
} else {
    $PHPCS_Comply = new PHPCS_Comply($argv[1]);
    $PHPCS_Comply->comply();
    echo $PHPCS_Comply->report($argv[2]);
}

?>