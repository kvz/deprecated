#!/usr/bin/php -q
<?php

$config["cmd_phpcs"] = "/usr/bin/phpcs";


/*
 * FC   function call
 * IF   if statement
 * EQ   equal sign
 * LN   LINE
 * WH   While
 * 
 * MIS  Missing
 * FND  Found
 * 
 * FCD   function declaration
 * SPC  Space
 * NWL  Newline
 * FIL  File
 * BFR  Before
 * AFT  After
 * OPN  Opening
 * CLS  Closing
 * TOO  Too
 * LNG  Long
 * IND  Indentation
 * PTH  Parenthesis
 * ELS  ELSE
 * BRC  Brace
 * DSC  Doc Style Comments
 * ANC  Allowed Normal Comments
 * PSC  Perl Style Comments
 * SWS  Superfluous white-space
 * VLD  Valid
 * TAG  Tag
 * UPC  Uppercase
 * CNS  Constant
 * ARN  Around
*/
$definitions = array();
$definitions['TOO_LNG'][]             = 'Line exceeds %d characters; contains %d characters';
$definitions['IND'][]                 = 'Line indented incorrectly; expected %d spaces, found %d';
$definitions['IND'][]                 = 'Break statement indented incorrectly; expected 12 spaces, found 8';
$definitions['FND_SPC_BFR_OPN_PTH'][] = 'Space before opening parenthesis of function call prohibited';
$definitions['FND_SPC_AFT_OPN_PTH'][] = 'Space after opening parenthesis of function call prohibited';
$definitions['FND_SPC_BFR_CLS_PTH'][] = 'Space before closing parenthesis of function call prohibited';
$definitions['FND_SPC_AFT_CLS_PTH'][] = 'Space after closing parenthesis of function call prohibited';
$definitions['MIS_ALN'][]             = 'Equals sign not aligned correctly; expected %d space but found %d spaces';
$definitions['MIS_ALN'][]             = 'Equals sign not aligned with surrounding assignments; expected %d spaces but found %d space';
$definitions['MIS_ALN'][]             = 'Equals sign not aligned with surrounding assignments; expected %d spaces but found %d spaces';
$definitions['MIS_ALN'][]             = 'Equals sign not aligned with surrounding assignments; expected %d space but found %d spaces';
$definitions['MIS_NWL_BFR_OPN_BRC'][] = 'Opening function brace should be on a new line';
$definitions['FND_SWS_BFR_ELS'][]     = 'Expected \"} else {\n\"; found \"}\n %selse{\n\"';
$definitions['MIS_SPC_AFT_CLS_PTH'][] = 'Expected \"if (...) {\n\"; found \"...){\n\"';
$definitions['MIS_SPC_AFT_CLS_PTH'][] = 'Expected \"while (...) {\n\"; found \"...){\n\"';
$definitions['MIS_SPC_AFT_CLS_PTH'][] = 'Expected \"foreach (...) {\n\"; found \"...){\n\"';
$definitions['MIS_SPC_BFR_BRC'][]     = 'Expected \"} elseif (...) {\n\"; found \"...){\n\"';
$definitions['MIS_SPC_BFR_BRC'][]     = 'Expected \"} else {\n\"; found \"} else{\n\"';
$definitions['MIS_SPC_BFR_BRC'][]     = 'Expected \"if (...) {\n\"; found \"...){\"';
$definitions['MIS_SPC_BFR_BRC'][]     = 'Expected \"for (...) {\n\"; found \"...){\n\"';
$definitions['MIS_SPC_AFT_CMA'][]     = 'No space found after comma in function call';
$definitions['FND_SPC_BFR_CMA'][]     = 'Space found before comma in function call';
$definitions['MIS_FCD_DSC'][]         = 'You must use \"/**\" style comments for a function comment';
$definitions['MIS_FIL_DSC'][]         = 'You must use \"/**\" style comments for a file comment';
$definitions['MIS_DSC'][]             = 'Missing function doc comment';
$definitions['FND_PSC'][]             = 'Perl-style comments are not allowed. Use \"// Comment.\" or \"/* comment */\" instead.';
$definitions['MIS_VLD_TAG'][]         = 'Short PHP opening tag used. Found \"<?\" Expected \"<?php\".';
$definitions['MIS_UPC_CNS'][]         = 'Constants must be uppercase; expected IS_NUMERIC but found is_numeric';
$definitions['MIS_NWL_ARN_BRC'][]     = 'Closing brace must be on a line by itself';
$definitions[''][] = '';
$definitions[''][] = '';
$definitions[''][] = '';
$definitions[''][] = '';

$fixed = 0;
$problems = 0;
$report_log = "";

$fixed_show = array();

Class Line {
    private $_line = '';
    
    public function Line($line){
        $this->_line = $line;
    }
    
    public function getLine(){
        return $this->_line;
    }
    
    public function indent($spaces) {
        $current = $this->beginsAt()-1;
        $needed  = $spaces - $current;
        
        if ($needed > 0) {
            $this->_line = str_repeat(" ", $needed).$this->_line;
        } elseif($needed < 0) {
            $this->removeAt(1, abs($needed));
        }
        
        return true;
    }
    
    public function regplace($search, $replace) {
        $this->_line = preg_replace('#'. $search.'#', $replace, $this->_line);
        return true;
    }

    public function replace($search, $replace) {
        $this->_line = str_replace($search, $replace, $this->_line);
        return true;
    }
    
    public function insertAt($at, $chars, $howmany=1) {
        $t = $this->_line;
        // Compensate
        $at--;
        $this->_line = substr($t, 0, $at) . str_repeat($chars, $howmany) . substr($t, $at, strlen($t));
        return true;
    }
    public function removeAt($at, $howmany) {
        $t = $this->_line;
        // Compensate
        $at--;
        $this->_line = substr($t, 0, $at) . substr($t, $at+$howmany, strlen($t));
        return true;
    }
    public function beginsAt() {
        for ($i = 0; $i < strlen($this->_line); $i++) {
            if (substr($this->_line, $i, 1) != " " && substr($this->_line, $i, 1) != "\t") {
                // Compensate
                return $i+1;
            }
        }
        return false;
    }    
}


// What's the longest meaning?
$longest = 0;
foreach($definitions as $meaning=>$pattern) {
    $len = strlen($meaning);
    if($len > $longest) {
        $longest = $len;
    }
}

// Load input
$input_file = realpath($argv[1]);
if (!file_exists($input_file)) {
    die("Input File: '$input_file' does not exist\n");
}

// Check PHPCS command
if (!file_exists($config["cmd_phpcs"])) {
    echo "Please:\n";
    echo "\n";
    echo "aptitude install php-pear\n";
    echo "pear install PHP_Codesniffer\n";
    die("");
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
function str_shift($delimiter, &$string)
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
 * Execute a shell command and returns an array with 
 * first element: success, second element: output
 *
 * @param string $cmd
 * 
 * @return array
 */
function exe($cmd) {
    $o = array(); 
    exec($cmd, $o, $r);
    $buf = implode("\n", $o);
    if ($r) {
        // Probably a syntax error
        return array(false, $buf);
    }
    return array(true, $buf);
}

function tabs2spaces($str) {
    return str_replace("\t", "    ", $str);
}

function workingfname($str) {
    return str_replace(".php", ".clean.php", $str);
}

function check_file($file, $config) {
    
    $workfile = workingfname($file);
    if ($workfile == $file) {
        die("Error, I was about to overwrite the original file!");
    }
    
    file_put_contents($workfile, tabs2spaces(file_get_contents($file)));
    
    $cmd = $config["cmd_phpcs"]." --standard=PEAR --report=csv ".$workfile;
    list($success, $output) = exe($cmd);
    $lines = explode("\n", $output);
    
    $result = array();
    
    foreach ($lines as $i=>$line) {
        $src = trim(str_replace('"', '', str_shift(",", $line)));
        $row = trim(str_replace('"', '', str_shift(",", $line)));
        $col = trim(str_replace('"', '', str_shift(",", $line)));
        $lvl = trim(str_replace('"', '', str_shift(",", $line)));
        $msg = trim($line);
        
        $result[$src][$row][$col][] = compact("lvl", "msg");
    }
    
    return array($workfile, $result);
}

function fix_results($results) {
    global $longest;
    global $fixed, $problems, $fixed_show, $report_log;
    $report_log = "";
    $fixed_results = array();
    $lines = array();
    $Lines = array();
    foreach ($results as $file=>$rows) {
        if ($file == "File") continue;
        if (!($lines = file($file))) {
            die("Could not open file: $file");
        }
        foreach ($lines as $i=>$line) {
            // Compensate
            $Lines[$i+1] = new Line($line);
        }
        
        foreach($rows as $row=>$cols) {
            foreach($cols as $col=>$reports) {
                foreach($reports as $nmr=>$report) {
                    extract($report);
                    
                    list($meaning, $pattern) = determine_meaning($msg);
                    $report_log .= str_pad($meaning, $longest, " ", STR_PAD_LEFT)." ";
                    $problems++;
                    $before = str_replace("\n", "", $Lines[$row]->getLine());
                    if (fix($Lines, $msg, $meaning, $pattern, $row, $col)) {
                        $fixed++;
                        $fixed_show[$meaning][$fixed]["befor"] = $before;
                        $fixed_show[$meaning][$fixed]["after"] = str_replace("\n", "", $Lines[$row]->getLine());
                        $fixed_show[$meaning][$fixed]["empty"] = ""; 
                        $report_log .= " FIXED  ";
                        //echo $Lines[$row]->getLine()."\n";
                    } else {
                        $report_log .= "UNFIXED ";
                    }
                    $report_log .= "$row, $col, $lvl, $msg\n";
                }
            }
        }
        
        $fixed_results[$file]  = "";
        foreach($Lines as $Line) {
            $fixed_results[$file] .= $Line->getLine();
        }
    }
    
    return $fixed_results;
}

function fix(&$lines, $message, $meaning, $pattern, $row, $col) {
    global $definitions, $fixed;
    
    if (!isset($definitions[$meaning])) {
        return false;
    }
    
    // Get information from error report, based on predefined 
    // pattern definition
    preg_match_all("#".$pattern."#", $message, $matches);
    unset($matches[0]);
    foreach($matches as $i=>$match) {
        $matches[$i] = $match[0];
    }
    
    $Line = &$lines[$row]; 
    //$beginsAt = $Line->beginsAt();
    
    switch ($meaning) {
        case "IND":
            $Line->indent($matches[1]);
            break;
        case "FND_SPC_BFR_OPN_PTH":
            $Line->replace(' (', '(');
            break;
        case "FND_SPC_BFR_CLS_PTH":
            $Line->replace(' }', '}');
            break;
        case "MIS_SPC_AFT_CMA":
            $Line->regplace(',([^ ])', ', $1');
            break;
        case "FND_SPC_BFR_OPN_PTH":
            $Line->regplace('[\s+](\()', '$1');
            break;
        case "FND_SPC_AFT_OPN_PTH":
            $Line->regplace('(\()[\s+]', '$1');
            break;
        default:
            return false;
            break;
    }
    
    // Default case returns false, so all matched cases don't have to.
    return true;
}

function prep_pattern($pattern) {
    $pattern = preg_quote($pattern);
    $pattern = str_replace("%d", "(\d)+", $pattern);
    $pattern = str_replace("%s", "( )+", $pattern);
    return $pattern;
}

function determine_meaning($message) {
    global $definitions;
    
    foreach($definitions as $meaning=>$patterns) {
        if (!$patterns || !$meaning) continue;
        foreach ($patterns as $pattern) {
            $pattern = prep_pattern($pattern);
            
            if (preg_match("#".$pattern."#", $message)) {
                return array($meaning, $pattern);
            }
        }
    }
    
    return array("**UNKNOWN", false);
}


if (false) {
    $Line = new Line("            123456789");
    //$Line->removeAt(7, 2);
    //$Line->insertAt(2, 'a');
    $Line->indent(4);
    
    echo "[".$Line->getLine()."]\n";
} else {
    list($workfile, $result) = check_file($input_file, $config);
    $fixed_results = fix_results($result);
    file_put_contents($workfile, $fixed_results);
    
    
    if ($argv[2]) {
        print_r($fixed_show[$argv[2]]);
    } else {
        echo $report_log; 
    }
    
    echo "\n\n";
    echo "Detected ".$problems." problems\n";
    echo "Fixed ".$fixed." problems\n";
    
}
?>