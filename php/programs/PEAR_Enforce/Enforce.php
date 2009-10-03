<?php
/**
 * PEAR_Enforce cleans up PHP Scripts.
 *
 * Analyzes PHPCS output and creates a new version of your
 * source file with many of the PHPCS suggestions already fixed.
 *
 * PHP version 5
 *
 * @package   PEAR_Enforce
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: Enforce.php 220 2009-01-12 16:41:34Z kevin $
 * @link      http://kevin.vanzonneveld.net/code/php_programs_PEAR_Enforce/
 */

// Autoloader borrowed from PHP_CodeSniffer, see function for credits
spl_autoload_register(array("PEAR_Enforce", "autoload"));

Class PEAR_Enforce {

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
    
    public  $wasModifiedBy = array();
    
    protected $_definitions = array();
    protected $_fixCodesMaxLen = 0;

    protected $_CodeRows = false;
    
    protected $_cntProblemsTotal = 0;
    protected $_cntProblemsFixed = 0;
    
    protected $_rowProblems = array();
    protected $_fixedLog = array();
    protected $_reportLog = "";

    protected $_fileOriginal = false;
    protected $_fileImproved = false;
    
    protected $_docBlockDefaults = array();
    
    public $cmd_phpcs = "/usr/bin/phpcs";
    
    /**
     * == General Functions 
     */
    
    public function syntaxCheckFile($file) {
        if (!file_exists($file)) {
            $this->_log("Directory: $tmpdir not found", PEAR_Enforce::LOG_ERR);
            return false;
        }
        
        $code = file_get_contents($file);
        
        // Execute
        return $this->_checkEval($code);
    }
        
    public function setDocBLockDefaults($defaults = false, $category="all") {
        if (!$defaults) $defaults = array();
        $this->_docBlockDefaults = $defaults;
        return true;      
    }
    
    public function syntaxCheckCode($code) {
        // Execute
        return $this->_checkEval($code);
    }
        
    /**
     * Performs a Syntax check without killing the parser (hopefully)
     * By kevin at metalaxe dot com, http://nl.php.net/manual/en/function.php-check-syntax.php#82811
     *
     * @param string PHP to be evaluated
     * 
     * @return mixed array or boolean on success
     */    
    protected function _checkEval( $code )
    {
        $evalStr = 'return;';
    
        // Get the string tokens
        $crack = token_get_all( "<?php\n".$code );
       
        // Drop our manually entered opening tag
        array_shift( $crack );
        $this->_tokenFix( $crack );
       
        // Check to see how we need to proceed
        // prepare the string for parsing
        if( isset( $crack[0][0] ) &&
            (
                $crack[0][0] === T_CONSTANT_ENCAPSED_STRING ||
                $crack[0][0] === T_STRING ||
                $crack[0][0] === T_OPEN_TAG
            )
        ){
            $evalStr .= '?>';
        }
    
        // Make sure syntax reporting is on...probably only need E_PARSE...but I'm anal
        $oldErr = error_reporting( E_ALL | E_STRICT );
    
        // Prevent output
        ob_start();
        eval( $evalStr.$code );
        $evaluput = ob_get_clean();
    
        // Reset error reporting
        error_reporting( $oldErr );
    
        // Only do this if there was output
        if ($evaluput !== '') {
            // Strip annoying html tags, in case html reporting is enabled in php.ini
            $evaluput = strip_tags( $evaluput );
    
            // Fancy Regex to pull the error that killed us
            $pattern    = '/Parse error:\s*syntax error,(.+?)\s+in\s+.+?\s*line\s+(\d+)/';
            $parse_fail    = (bool)preg_match( $pattern, $evaluput, $match );
    
            // Parse error to report?
            if( $parse_fail === true )
            {
                return array(
                    'line'    =>    (int)$match[2],
                    'msg'    =>    $match[1]
                );
            }
        }
        return true;
    }    
    
    /**
     * fixes related bugs: 29761, 34782 => token_get_all returns <?php NOT as T_OPEN_TAG
     * By rogier at dsone dot nl, http://nl2.php.net/manual/en/function.token-get-all.php#80335;
     *
     * @param array &$tokens
     */
    protected function _tokenFix( &$tokens ) {
        if (!is_array($tokens) || (count($tokens)<2)) {
            return;
        }
       //return of no fixing needed
        if (is_array($tokens[0]) && (($tokens[0][0]==T_OPEN_TAG) || ($tokens[0][0]==T_OPEN_TAG_WITH_ECHO)) ) {
            return;
        }
        //continue
        $p1 = (is_array($tokens[0])?$tokens[0][1]:$tokens[0]);
        $p2 = (is_array($tokens[1])?$tokens[1][1]:$tokens[1]);
        $p3 = '';
    
        if (($p1.$p2 == '<?') || ($p1.$p2 == '<%')) {
            $type = ($p2=='?')?T_OPEN_TAG:T_OPEN_TAG_WITH_ECHO;
            $del = 2;
            //update token type for 3rd part?
            if (count($tokens)>2) {
                $p3 = is_array($tokens[2])?$tokens[2][1]:$tokens[2];
                $del = (($p3=='php') || ($p3=='='))?3:2;
                $type = ($p3=='=')?T_OPEN_TAG_WITH_ECHO:$type;
            }
            //rebuild erroneous token
            $temp = array($type, $p1.$p2.$p3);
            if (version_compare(phpversion(), '5.2.2', '<' )===false && isset($token[0]) && isset($token[0][2])) {
                $temp[] = $token[0][2];
            }
            //rebuild
            $tokens[1] = '';
            if ($del==3) $tokens[2]='';
            $tokens[0] = $temp;
        }
        return;
    }    
    
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
    protected function _str_shift($delimiter, &$string)
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
    protected function _valMaxLen2D($array) {
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
    protected function _exe($cmd) {
        $o = array(); 
        exec($cmd, $o, $r);
        if ($r) {
            // Probably a syntax error
            return array(false, $o);
        }
        return array(true, $o);
    }    
    
    /**
     * == Specific protected Functions 
     */
    
    
    /**
     * Returns a fixCode's pattern
     *
     * @param string $fixCode
     */
    protected function _getPattern($fixCode) {
        foreach ($this->_definitions as $pattern=>$fixCodes) {
            if (array_search($fixCode, $fixCodes) !== false){
                return $pattern;
            }
        }
        return false;        
    }

    /**
     * Logs messages. Anything from and above LOG_CRIT will kill the app. 
     *
     * @param string  $str
     * @param integer $level
     */
    protected function _log($str, $level=PEAR_Enforce::LOG_INFO) {
        echo $str."\n";
        if ($level <= PEAR_Enforce::LOG_CRIT) {
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
    protected function _preFormat($source) {
        $source = str_replace("\r", "", $source);
        $source = str_replace("\t", "    ", $source);
        $source = trim($source);
        return $source;
    }
    
    /**
     * Performs massive changes like converts special chars to newlines
     *
     * @param string $source
     * 
     * @return string
     */
    protected function _postFormat($source) {
        // Newlines
        $source = str_replace($this->_getPostFormatAddNewline(), "\n", $source);

        // Backspace until curly brace found
        $f = preg_quote($this->_getPostFormatBackSpaceCB());
        $p = '\}(\s*)('.$f.')';
        // $p = '('.$f.')';
        $source = preg_replace('#'.$p.'#ms', '}', $source);
        
        return $source;
    }    
    
    protected function _getPostFormatAddNewline() {
        // Should be Concatenated so this script can also be run on itself.
        $buf  = "";
        $buf .= "/*<PEAR_Enforce:";
        $buf .= "NEWLINE";
        $buf .= ">*/";
        return $buf;
    }
    
    protected function _getPostFormatBackSpaceCB(){
        // Should be Concatenated so this script can also be run on itself.
        $buf  = "";
        $buf .= "/*<PEAR_Enforce:";
        $buf .= "BACKSPACE, T_CURLY_BRACKET";
        $buf .= ">*/";
        return $buf;
    }    
    
    /**
     * Opens a file, optionally preformats and saves it to a working file. 
     *
     * @param string $file
     * @param boolean $preformat
     * 
     * @return boolean
     */
    protected function _loadFile($file, $preformat=true) {
        
        $file = realpath($file);
        
        if ($file == $this->_fileOriginal) {
            $this->_log("File '$file' already loaded", PEAR_Enforce::LOG_DEBUG);
            return true;
        }
        
        if (!file_exists($file)) {
            $this->_log("File '$file' does not exist", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        if (substr($file, -4) != ".php") {
            $this->_log("File '$file' does not end in .php", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        $this->_fileOriginal = $file; 
        $this->_fileImproved = str_replace(".php", ".enforce.php", $this->_fileOriginal);
        
        if ($this->_fileOriginal == $this->_fileImproved) {
            $this->_log("Both original and improved file locations ended up being identical: '".$this->_fileOriginal."'", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        $source = file_get_contents($this->_fileOriginal);
        
        if ($preformat) {
            $source = $this->_preFormat($source);
        }
        
        if (!file_put_contents($this->_fileImproved, $source)) {
            $this->_log("Cannot write to file '".$this->_fileImproved."'", PEAR_Enforce::LOG_CRIT);
            return false;
        }

        $this->_CodeRows = array();
        $lines = file($this->_fileImproved);
        foreach($lines as $i=>$codeRow) {
            // Compensate
            $this->_CodeRows[$i+1] = new CodeRow($codeRow);
        }
        
        return true;
    }

    protected function _runPHPCSCode($code, $tmpdir="/tmp") {
        
        $tmpfile = tempnam($tmpdir, "enforce_").".php";
        file_put_contents($tmpfile, $code);
        $x = $this->_runPHPCSFile($tmpfile);
        unlink($tmpfile);
        
        return $x;
    }
    
    protected function _runPHPCSFile($file) {
        return $this->_runPHPCSWithClass($file);
    }
    
    protected function _runPHPCSWithClass($file) {
        
        // Check the PHP version.
        if (version_compare(PHP_VERSION, '5.1.0') === -1) {
            echo 'ERROR: PEAR_Enforce requires PHP version 5.1.0 or greater.'."\n";
            exit(2);
        }
        
        require_once 'PHP/CodeSniffer.php';
        
        $phpcs = new PHP_CodeSniffer(0, 4);
        $phpcs->process($file, "PEAR", array(), false);
        $results = $phpcs->prepareErrorReport(true);
        $results = $results['files'][$file]['messages'];
        
        return $results;        
    }

    public function showReportRow($fixCode, $fixMessage, $fixed, $lvl, $row, $col) {
        $buf  = "";
        $buf .= " ".str_pad($fixCode, $this->_fixCodesMaxLen, " ", STR_PAD_LEFT)." ";
        $buf .= str_pad($lvl, 7, " ", STR_PAD_RIGHT)." ";
        $buf .= str_pad($row, 4, " ", STR_PAD_LEFT)." ";
        $buf .= str_pad($col, 3, " ", STR_PAD_LEFT)." ";

        if ($fixed) {
            $buf .= str_pad("FIXED", 7, " ", STR_PAD_LEFT)." ";
        } else {
            $buf .= str_pad("UNFIXED", 7, " ", STR_PAD_LEFT)." ";
        }
        
        $buf .= $fixMessage;

        $buf .= "\n";
        
        return $buf;
    }


    /**
     * Mapping of different output patterns of PHPCS to 'fixCodes',
     * adding flexibility to the fix-architecture.
     *
     * @param array $add_definitions
     */
    protected function _setDefinitions($add_definitions=false) {
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
         * CMT  Comment
         * TAG  Tag
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
        $predefined['End of line character is invalid; expected "\n" but found "\r\n"'][] = 'FND_WNL';
        $predefined['Line exceeds %d characters; contains %d characters'][]                   = 'WONTFIX';//'TOO_LNG';
        
        $predefined['Line indented incorrectly; expected %d space%z, found %d'][]            = 'IND';
        $predefined['Line indented incorrectly; expected at least %d space%z, found %d'][]   = 'IND';
        $predefined['Break statement indented incorrectly; expected %d space%z, found %d'][] = 'IND';
        $predefined['Closing brace indented incorrectly; expected %d space%z, found %d'][]   = 'IND';
        
        $predefined['Space %c %c parenthesis of function call prohibited'][] = 'EXPECTED';
        
        $predefined['Equals sign not aligned correctly; expected %d space%z but found %d space%z'][]                     = 'MIS_ALN_EQL';
        $predefined['Equals sign not aligned with surrounding assignments; expected %d space%z but found %d space%z'][]  = 'MIS_ALN_EQL';
        
        // Small alignment
        $predefined['Space found before comma in function call'][]           = 'FND_SWS_BFR_CMA';
        
        $predefined['%BEGINExpected %a'][] = 'EXPECTED';

        $predefined['No space found after comma in function call'][]         = 'MIS_SPC_AFT_CMA';
        
        // Newlines
        $predefined['Closing brace must be on a line by itself'][]      = 'MIS_NWL_ARN_CLS_BRC';
        $predefined['Opening function brace should be on a new line'][] = 'MIS_NWL_ARN_OPN_BRC';
        $predefined['Opening brace should be on a new line'][] = 'MIS_NWL_ARN_OPN_BRC';
        $predefined['Opening brace of a Class must be on the line after the definition'][] = 'MIS_NWL_ARN_OPN_BRC';

        // Comments
        $predefined['You must use "/**" style comments for a %c comment'][]                                   = 'IVD_DSC';
        $predefined['Perl-style comments are not allowed. Use "// Comment." or "/* comment */" instead.'][] = 'IVD_PSC';
        $predefined['Missing %c doc comment'][]                                                                 = 'MIS_DSC';
        $predefined['Missing comment for param "$%c" at position %d'][]                                       = 'MIS_PRM_CMT';
        $predefined['The comments for parameters $%c (%d) and $%c (%d) do not align'][]                         = 'MIS_CMT_TAG';
        $predefined['Missing @%c tag in %c comment'][]                                                          = 'MIS_ALN_PRM_CMT';

        // Language
        $predefined['Short PHP opening tag used%a'][]       = 'MIS_LNG_TAG';
        $predefined['Constants must be uppercase; expected %c but found %c'][]              = 'MIS_UPC_CNS';
        $predefined['"%c" is a statement, not a function; no parentheses are required'][] = 'FND_PTH_ARN_STM';
        $predefined['File is being unconditionally included; use "require" instead'][]    = 'FND_IVD_STM';
        
        // Not going to fix. Ever.
        $predefined['protected method name "%c::%c" must not be prefixed with an underscore'][] = 'WONTFIX';
        //$predefined[''][] = 'WONTFIX';
        
        
        
        //$this->_definitions      = array_merge($predefined, $add_definitions);
        
        foreach ($predefined as $pattern=>$fixCodes) {
            $prep = $this->_patternPrepare($pattern);
            $prepared[$prep] = $fixCodes;
        }
        
        $this->_definitions = $prepared;
        $this->_fixCodesMaxLen = $this->_valMaxLen2D($this->_definitions);
    }
    
    /**
     * Takes a custom pattern and returns a valid perl regex
     *
     * @param string $pattern
     * 
     * @return string
     */
    protected function _patternPrepare($pattern) {
        $pattern = preg_quote($pattern);
        $pattern = str_replace('%z', '[s]?', $pattern); // 's' or not to, match plural words
        
        $pattern = str_replace('%c', '(\w[\w\d_]+)', $pattern);
        $pattern = str_replace('%d', '(\d+)', $pattern);
        $pattern = str_replace('%s', '([\s \t]+)', $pattern);
        
        $pattern = str_replace('%a', '(.+)', $pattern);
        $pattern = str_replace('%aN', '[.+]', $pattern);
        
        $pattern = str_replace('%BEGIN', '^', $pattern);
        
        return $pattern; 
    }        
    
    
    protected function _determineFixCodes($fixMessage) {
        if (!is_array($this->_definitions) || count($this->_definitions) < 5) {
            log("What happened to my fixcode definitions?!", PEAR_Enforce::LOG_EMERG);
            return false;
        }
        
        foreach($this->_definitions as $pattern=>$fixCodes) {
            if (!$pattern || !$fixCodes) continue;
            if (preg_match("#".$pattern."#i", trim($fixMessage))) {
                return array($pattern, $fixCodes); 
            }
        }
        
        return array(false, array("**UNKNOWN"));
    }    
        
    /**
     * Fix a spefic problem as reported by PHPCS
     *
     * @param string  $fixMessage
     * @param string  $fixCode
     * @param string  $pattern
     * @param integer $row
     * @param integer $col
     * 
     * @return boolean
     */
    protected function _fixProblem($fixMessage, $fixCode, $pattern, $row, $col) {
        
        $debug   = false;
        $CodeRow = $this->_CodeRows[$row];
        $NextRow = false;
        $PrevRow = false;
        
        if (isset($this->_CodeRows[$row+1])) {
            $NextRow = $this->_CodeRows[$row+1];
        }
        if (isset($this->_CodeRows[$row-1])) {
            $PrevRow = $this->_CodeRows[$row-1];
        }
        
        $original = $CodeRow->getCodeRow();
        $matches  = array();
        
        $pattExpression   = '[^\)]+';
        $pattFunctionCall = '[a-zA-Z0-9\_]+';
        $controlStructures = array("if", "else", "elseif", "do", "while", "switch", "for", "foreach");
        $controlStructuresTxt = implode("|", $controlStructures);
        
        // Init
        $this->_fixedLog[$fixCode][$row]["error"] = "";
        
        // Get matched variables from message based on pattern 
        if (preg_match_all("#".$pattern."#", $fixMessage, $matches_raw)) {
            unset($matches_raw[0]);
            foreach($matches_raw as $i=>$match) {
                $matches[$i-1] = $match[0];
            }
            
            if (count($matches) == 2) {
                list($expected, $found) = $matches;
                $needed = @($expected - $found);
            } elseif (count($matches) == 1) {
                list($expected) = $matches;
            }
        }
        
        switch ($fixCode) {
            case "EXPECTED":
                
                // '( '
                $CodeRow->regplace('(\()[\s]+', '(', 'T_ALLOTHER', 1);
                
                // ' )'
                $CodeRow->regplace('[\s]+(\))', ')', 'T_ALLOTHER', 1);
                
                // '){'
                $CodeRow->regplace('\){', ') {', 'T_ALLOTHER', 1);
                
                // 'else{'
                $CodeRow->regplace('('.$controlStructuresTxt.')[\s]+{', $this->_getPostFormatBackSpaceCB() . ' $1 {', 'T_ALLOTHER', 1);

                // '               else {'
                $CodeRow->regplace('^[\s]+('.$controlStructuresTxt.')[\s]*{', $this->_getPostFormatBackSpaceCB() . ' $1 {', 'T_ALLOTHER', 1);
                
                // 'elseif (!$insensitive && substr_count($l, $pattern)) {'
                $CodeRow->regplace('^[\s]*(elseif)', $this->_getPostFormatBackSpaceCB() . ' $1 ', 'T_ALLOTHER', -1);
                
                // '}else{' || '}  else      {'  
                $CodeRow->regplace('}[\s]*('.$controlStructuresTxt.')[\s]*{', '} $1 {', 'T_ALLOTHER', -1);
                
                // 'while($row = mysql_fetch_array($res)) {'
                $CodeRow->regplace('('.$controlStructuresTxt.')\(', '$1 (', 'T_ALLOTHER', -1);
                
                // 'count ('
                if (preg_match_all('/('.$pattFunctionCall.') \(/', $CodeRow->getCodeRow(), $m)) {
                    $functionCalls = $m[1];
                    foreach ($functionCalls as $functionCall) {
                        // Don't do this for control structures!
                        if (in_array($functionCall, $controlStructures)) continue;
                        $CodeRow->regplace('('.$functionCall.') \(', '$1(', 'T_ALLOTHER', -1);
                    }
                }
                
                // 'if ($v) {$keep = !$keep;'
                $CodeRow->regplace('{[\s]*(\S+)', '{ '. $this->_getPostFormatAddNewline().$CodeRow->getIndentation(+4).'$1', 'T_ALLOTHER', -1);
                
                
                
/*                if ($expected == '"if (...) {\n"; found "if (...) {"') {
                    $CodeRow->insertAt($CodeRow->getPosBraceOpen(+1), 
                        $this->_getPostFormatAddNewline() . $CodeRow->getIndentation(+4));
                }
*/                
                break;
            case "TOO_LNG":
                // "Line exceeds 85 characters; contains 96 characters
                
                $CodeRow->wrap($this->_getPostFormatAddNewline(), 85, $CodeRow->getIndentation(+4));
                
                break;
            case "IVD_DSC":
                // You must use "/**" style comments for a function comment
                
            case "MIS_DSC":
                // Missing function/doc comment
                
                $indent = $CodeRow->getIndent();    
                
                $DocBlockWriter = new DocBlockWriter();
                $DocBlockWriter->setDocBLockDefaults($this->_docBlockDefaults);
                $DocBlockWriter->setIndent($indent);
                $DocBlockWriter->setNewLineChar($this->_getPostFormatAddNewline());
                
                if (!isset($expected)) {
                    $this->_fixedLog[$fixCode][$row]["error"] .= "var expected was not set!!!";
                } elseif ($expected == "function") {
                    $CodeRow->insertAt($CodeRow->getIndent(+1), 
                        $DocBlockWriter->generateFunction($CodeRow->getCodeRow()));
                } else if ($expected == "class") {
                    $CodeRow->insertAt($CodeRow->getIndent(+1), 
                        $DocBlockWriter->generateClass($CodeRow->getCodeRow()));
                } elseif ($expected == "file") {
                    $CodeRow->insertAt(1, 
                        $DocBlockWriter->generateFile());
                }
                
                if (count($DocBlockWriter->errors)) {
                    $this->_fixedLog[$fixCode][$row]["error"] .= "DocBlock Errors: ". implode('; ', $DocBlockWriter->errors);
                }
                
                break;
            case "IVD_PSC":
                // Perl-style comments are not allowed. Use "// Comment.\" or "/* comment */" instead.
                
                $CodeRow->regplace('\#(\s*)', '// ', 'T_COMMENT', 1);
                break;
            case "MIS_LNG_TAG":
                // Short PHP opening tag used. Found "<?" Expected "<?php".
                
                $CodeRow->replace('<?', '<?php', 'T_OPEN_TAG');
                break;
            case "MIS_UPC_CNS":
                // Constants must be uppercase; expected IS_NUMERIC but found is_numeric
                
                $CodeRow->replace($found, $expected, 'T_ALLOTHER', 1);
                break;
            case "MIS_ALN_EQL":
                // Equals sign not aligned correctly
                // Equals sign not aligned with surrounding assignments; expected 3 spaces but found 1 space
                
                
                // Before Equal
                // Insert will actually backspace on negative amount
                $posEq    = $CodeRow->getPosEqual();
                $posBefEq = $posEq - 1;
                $chrBefEq = $CodeRow->getCharAt($posBefEq);
                $posIns   = $posEq;
                if (in_array($chrBefEq, array("."))) {
                    $posIns   = $posBefEq;
                }
                
                $CodeRow->insertAt($posIns, " ", $needed);
                
                // After Equal
                $CodeRow->regplace('=([a-zA-Z0-9\$\_]+)', '= $1', 'T_ALLOTHER', 1);
                
                break;
            case "IND":
                // Line indented incorrectly; expected 12 spaces, found 16
                
                $CodeRow->setIndent($expected);
                break;
            case "MIS_SPC_AFT_CMA":
                // No space found after comma in function call
                
                $CodeRow->regplace(',([^ ]|$)', ', $1', 'T_ALLOTHER', -1);
                break;
            case "MIS_NWL_ARN_CLS_BRC":
                // Closing brace must be on a line by itself
                
                $CodeRow->insertAt($CodeRow->getPosBraceClose(), 
                    $this->_getPostFormatAddNewline() . $CodeRow->getIndentation());
                break;
            case "MIS_NWL_ARN_OPN_BRC":
                // Opening function brace should be on a new line
                
                $CodeRow->insertAt($CodeRow->getPosBraceOpen(), 
                    $this->_getPostFormatAddNewline() . $CodeRow->getIndentation());
                break;
            case "FND_SWS_BFR_CMA":
                // Space found before comma in function call
                
                $CodeRow->regplace('(\s+),', ',', 'T_ALLOTHER', 1);
                break;
            case "FND_SPC_PTH":
                // Space surrounding parentheses
                
                list($spc_loc, $pth_typ) = $matches;
                $a = $b = $pth = "";
                
                $pth = ($pth_typ == 'opening' ? 'c' : '(\))');
                $spc = '[\s+]';
                
                if ($spc_loc == 'before') {
                    $a = $spc;
                } else {
                    $b = $spc;
                }
                
                $debug = "[$spc_loc][$pth_typ] replacing ".$a.$pth.$b;
                $CodeRow->regplace($a.$pth.$b, '$1', 'T_ALLOTHER', 1);
                break;
            default:
                $this->_fixedLog[$fixCode][$row]["error"] .= "No such fix: ".$fixCode."!!!";
                return false;
                break;
        }
        
        $this->wasModifiedBy[$row][] = $fixCode;
        
        // Default case returns false, so all matched cases don't have to.
        if ($debug) {
            $this->_fixedLog[$fixCode][$row]["debug"] = $debug;
        }
        
        $modified = $CodeRow->getCodeRow();
        if ($modified == $original) {
            $this->_fixedLog[$fixCode][$row]["error"] .= "Nothing was modified!!!";
        }
        
        $this->_fixedLog[$fixCode][$row]["match"] = implode(", ", $matches);
        
        return true;
    }

        
    protected function _improveCode($results) {
        $this->_reportLog = "";
        $this->_debugLog = "";
        $fixedResults = "";
        
        foreach($results as $row=>$cols) {
            $this->_rowProblems[$row] = array();
            foreach($cols as $col=>$reports) {
                foreach($reports as $nmr=>$report) {
                    if (isset($report['message'])) {
                        $fixMessage = $report['message'];
                    } elseif (isset($report['fixMessage'])) {
                        $fixMessage = $report['fixMessage'];
                    } else {
                        $this->_log("No fixMessage found in row $row col $col nmr $nmr",PEAR_Enforce::LOG_ERR);
                        continue;
                    }
                     
                    if (isset($report['type'])) {
                        $lvl = $report['type'];
                    } elseif (isset($report['lvl'])) {
                        $lvl = $report['lvl'];
                    } else {
                        $this->_log("No fixLevel found in row $row col $col nmr $nmr",PEAR_Enforce::LOG_ERR);
                        continue;
                    }
                    
                    list($pattern, $fixCodes) = $this->_determineFixCodes($fixMessage);
                    $this->_rowProblems[$row] = array_merge($this->_rowProblems[$row], $fixCodes);
                    
                    $this->_cntProblemsTotal++;
                    foreach($fixCodes as $fixCode) {
                        $this->_fixedLog[$fixCode][$row]["assig"] = $fixMessage; 
                        $this->_fixedLog[$fixCode][$row]["types"] = implode(", ", $this->_CodeRows[$row]->getTokenTypes());

                        $this->_fixedLog[$fixCode][$row]["befor"] = str_replace("\n", "", $this->_CodeRows[$row]->getCodeRow());
                        $fixed = $this->_fixProblem($fixMessage, $fixCode, $pattern, $row, $col);
                        if ($fixed) {
                            $this->_cntProblemsFixed++;
                            $this->_fixedLog[$fixCode][$row]["after"] = str_replace("\n", "", $this->_CodeRows[$row]->getCodeRow());
                        }                         
                        
                        $this->_reportLog .= $this->showReportRow($fixCode, $fixMessage, $fixed, $lvl, $row, $col);
                    }
                }
            }
        }
        
        $fixedResults  = "";
        foreach($this->_CodeRows as $row=>$CodeRow) {
            $fixedResults .= $CodeRow->getCodeRow();
        }
        
        return $fixedResults;
    }
    
    
    /**
     * == Specific Public Functions 
     */
    
    /**
     * Constructor. Use argument to load a file
     *
     * @param string $file
     * 
     * @return PEAR_Enforce
     */
    public function PEAR_Enforce($file = false) {        
        
        $this->_setDefinitions();
        if ($file) {
            if (!$this->_loadFile($file)){
                return false;
            }
        }
        return true;
    }

    
    /**
     * Autoload static method for loading classes and interfaces.
     * Code from the PHP_CodeSniffer package by Greg Sherwood and 
     * Marc McIntyre
     *
     * @param string $className The name of the class or interface.
     *
     * @return void
     */
    static public function autoload($className)
    {
        $parent     = 'PEAR_Enforce';
        $parent     = '';
        $parent_len = strlen($parent);
        if (substr($className, 0, $parent_len) == $parent) {
            $newClassName = substr($className, $parent_len);
        } else {
            $newClassName = $className;
        }

        $path = str_replace('_', '/', $newClassName).'.php';

        if (is_file(dirname(__FILE__).'/'.$path) === true) {
            // Check standard file locations based on class name.
            include dirname(__FILE__).'/'.$path;
        } else {
            // Everything else.
            @include $path;
        }

    }//end autoload()    
    
    /**
     * Combines protected functions to convert loaded codefile and store the 
     * improved version in $this->_fileImproved
     *
     * @return boolean
     */
    public function enforce() {
        if (!$this->_fileOriginal) {
            $this->_log("Please load a file first, this can be done by using the constructor.", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        if (!file_exists($this->_fileOriginal)) {
            $this->_log("File '".$this->_fileOriginal."' does not exist", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        if (!file_exists($this->_fileImproved)) {
            $this->_log("File '".$this->_fileImproved."' should have been created automatically, but does not exist", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        if (($res = $this->syntaxCheckFile($this->_fileOriginal)) !== true) {
            extract($res);
            $this->_log("File '".$this->_fileOriginal."' contains syntax error: $msq on line: $line. Please fix first.", PEAR_Enforce::LOG_CRIT);
            return false;
        }
        
        
        $source    = file_get_contents($this->_fileOriginal);

        $results   = $this->_runPHPCSCode($source);
        $source    = $this->_improveCode($results);
        
        $formatted = $this->_postFormat($source);
        

        if (!file_put_contents($this->_fileImproved, $formatted)) {
            $this->_log("Cannot write to file '".$this->_fileImproved."'", PEAR_Enforce::LOG_CRIT);
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
        
        if ($which == "errors") {
            foreach ($this->_fixedLog as $code=>$rows) {
                foreach ($rows as $nr=>$row) {
                    if ($row["error"]) {
                         //$report .= "Fixcode: $code @ line: $nr: ".$row["error"]." (".$row["befor"].")\n";
                         $report .= $this->showReportRow($code, $row["befor"], false, "FIXERROR-".$row["error"], $nr, 0);
                    }
                }
            }
        } elseif ($which && $pattern) {
            $report .= "Results for $which fix, pattern: \n    ".$pattern.""."\n"."\n";
            
            $row = false;
            $found = false;
            foreach($this->_rowProblems as $row=>$fixCodes) {
                if (in_array($which, $fixCodes)) {
                    $found = true;
                    break;
                }
            }
            
            if ($found) {
                $report .= "Example at line $row: \n    ".$this->_CodeRows[$row]->getCodeRow()."\n"."\n"; 
            } else {
                $report .= "No examples found in current source"."\n"."\n";
            }
            
            $report .= print_r($this->_fixedLog[$which], true);
        } else {
            $report .= "All fixes"."\n";
            $report .= $this->_reportLog;
        }

        $report .= "\n"."\n";
        $report .= "Detected ".$this->_cntProblemsTotal." problems"."\n";
        $report .= "Fixed    ".$this->_cntProblemsFixed." problems"."\n";
        $report .= "Saved to ".$this->_fileImproved." (".filesize($this->_fileImproved).")"."\n";
        
        if (($res = $this->syntaxCheckFile($this->_fileImproved)) !== true) {
            extract($res);
            $report .= "Sorry, I've accidentaly created syntax error: $msg on line: $line.\n";
        } else {
            $report .= "No syntax errors created.\n";
        }
        
        return $report;
    }
}
?>