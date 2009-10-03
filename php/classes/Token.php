<?php
define('T_NONE', 0);
define('T_OPEN_CURLY_BRACKET', 1000);
define('T_CLOSE_CURLY_BRACKET', 1001);
define('T_OPEN_SQUARE_BRACKET', 1002);
define('T_CLOSE_SQUARE_BRACKET', 1003);
define('T_OPEN_PARENTHESIS', 1004);
define('T_CLOSE_PARENTHESIS', 1005);
define('T_COLON', 1006);
define('T_STRING_CONCAT', 1007);
define('T_INLINE_THEN', 1008);
define('T_NULL', 1009);
define('T_FALSE', 1010);
define('T_TRUE', 1011);
define('T_SEMICOLON', 1012);
define('T_EQUAL', 1013);
define('T_MULTIPLY', 1015);
define('T_DIVIDE', 1016);
define('T_PLUS', 1017);
define('T_MINUS', 1018);
define('T_MODULUS', 1019);
define('T_POWER', 1020);
define('T_BITWISE_AND', 1021);
define('T_BITWISE_OR', 1022);
define('T_ARRAY_HINT', 1023);
define('T_GREATER_THAN', 1024);
define('T_LESS_THAN', 1025);
define('T_BOOLEAN_NOT', 1026);
define('T_SELF', 1027);
define('T_PARENT', 1028);
define('T_DOUBLE_QUOTED_STRING', 1029);
define('T_COMMA', 1030);
define('T_HEREDOC', 1031);
define('T_PROTOTYPE', 1032);
define('T_THIS', 1033);
define('T_REGULAR_EXPRESSION', 1034);

/**
 * Wrapper around token_get_all(). Borrows heavily from
 * PHP_CodeSniffer.
 *
 * PHP version 5
 *
 * @package   Token
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2008 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id: Token.php 220 2009-01-12 16:41:34Z kevin $
 * @link      http://kevin.vanzonneveld.net/code/
 */
Class Token {
    
    protected $_tokenized = array();
    protected $_row = 1;
    protected $_col  = 1;
    protected $_colCorrectionBegin = 0;
    protected $_colCorrectionEnd = 0;
    protected $_addTags = true;
    protected $_addedTagBegin = false;
    protected $_addedTagClose = false;
    
    /**
     * Takes a token produced from <code>token_get_all()</code> and produces a
     * more uniform token.
     *
     * Note that this method also resolves T_STRING tokens into more descrete
     * types, therefore there is no need to call _resolveTstringToken()
     *
     * @param string|array $token The token to convert.
     *
     * @return array The new token.
     */
    protected function _standardiseToken($token)
    {
        if (is_array($token) === false) {
            $newToken = $this->_resolveSimpleToken($token);
        } else {
            // Some T_STRING tokens can be more specific.
            if ($token[0] === T_STRING) {
                $newToken = $this->_resolveTstringToken($token);
            } else {
                $newToken            = array();
                $newToken['code']    = $token[0];
                $newToken['content'] = $token[1];
                $newToken['type']    = token_name($token[0]);
            }
        }
        
        return $newToken;

    }//end _standardiseToken()
    
    protected function _surroundTags($code) {
        if ($this->_addTags) {
            if (strpos($code, "<?") === false) {
                $code = "<?".$code;
                $this->_addedTagBegin = true;
            }
            if (strpos($code, "?>") === false) {
                $code = $code."?>";
                $this->_addedTagClose = true;
            }
        }
        return $code;
    }
    
    /**
     * Converts T_STRING tokens into more usable token names.
     *
     * The token should be produced using the token_get_all() function.
     * Currently, not all T_STRING tokens are converted.
     *
     * @param string|array $token The T_STRING token to convert as constructed
     *                            by token_get_all().
     *
     * @return array The new token.
     */
    protected function _resolveTstringToken(array $token)
    {
        $newToken = array();
        switch (strtolower($token[1])) {
        case 'false':
            $newToken['type'] = 'T_FALSE';
            break;
        case 'true':
            $newToken['type'] = 'T_TRUE';
            break;
        case 'null':
            $newToken['type'] = 'T_NULL';
            break;
        case 'self':
            $newToken['type'] = 'T_SELF';
            break;
        case 'parent':
            $newToken['type'] = 'T_PARENT';
            break;
        default:
            $newToken['type'] = 'T_STRING';
            break;
        }

        $newToken['code']    = constant($newToken['type']);
        $newToken['content'] = $token[1];

        return $newToken;

    }//end _resolveTstringToken()

    /**
     * Converts simple tokens into a format that conforms to complex tokens
     * produced by token_get_all().
     *
     * Simple tokens are tokens that are not in array form when produced from
     * token_get_all().
     *
     * @param string $token The simple token to convert.
     *
     * @return array The new token in array format.
     */
    protected function _resolveSimpleToken($token)
    {
        $newToken = array();

        switch ($token) {
        case '{':
            $newToken['type'] = 'T_OPEN_CURLY_BRACKET';
            break;
        case '}':
            $newToken['type'] = 'T_CLOSE_CURLY_BRACKET';
            break;
        case '[':
            $newToken['type'] = 'T_OPEN_SQUARE_BRACKET';
            break;
        case ']':
            $newToken['type'] = 'T_CLOSE_SQUARE_BRACKET';
            break;
        case '(':
            $newToken['type'] = 'T_OPEN_PARENTHESIS';
            break;
        case ')':
            $newToken['type'] = 'T_CLOSE_PARENTHESIS';
            break;
        case ':':
            $newToken['type'] = 'T_COLON';
            break;
        case '.':
            $newToken['type'] = 'T_STRING_CONCAT';
            break;
        case '?':
            $newToken['type'] = 'T_INLINE_THEN';
            break;
        case ';':
            $newToken['type'] = 'T_SEMICOLON';
            break;
        case '=':
            $newToken['type'] = 'T_EQUAL';
            break;
        case '*':
            $newToken['type'] = 'T_MULTIPLY';
            break;
        case '/':
            $newToken['type'] = 'T_DIVIDE';
            break;
        case '+':
            $newToken['type'] = 'T_PLUS';
            break;
        case '-':
            $newToken['type'] = 'T_MINUS';
            break;
        case '%':
            $newToken['type'] = 'T_MODULUS';
            break;
        case '^':
            $newToken['type'] = 'T_POWER';
            break;
        case '&':
            $newToken['type'] = 'T_BITWISE_AND';
            break;
        case '|':
            $newToken['type'] = 'T_BITWISE_OR';
            break;
        case '<':
            $newToken['type'] = 'T_LESS_THAN';
            break;
        case '>':
            $newToken['type'] = 'T_GREATER_THAN';
            break;
        case '!':
            $newToken['type'] = 'T_BOOLEAN_NOT';
            break;
        case ',':
            $newToken['type'] = 'T_COMMA';
            break;
        default:
            $newToken['type'] = 'T_NONE';
            break;

        }//end switch

        $newToken['code']    = constant($newToken['type']);
        $newToken['content'] = $token;

        return $newToken;

    }//end _resolveSimpleToken()
    
    public function Token($code, $addTags=true) {
        $this->_addTags = $addTags;
        $code = $this->_surroundTags($code);
        $this->_tokenized = $this->_tokenizeString($code, '\n');
    }
    
    public function getTokenized() {
        return $this->_tokenized;
    }
    
    public function setTokenized($tokenized) {
        $this->_tokenized = $tokenized;
    }
    
    public function getContent() {
        $cont  = "";
        foreach ($this->_tokenized as $i=>$token) {
            $cont .= $token["content"];
        }
        return $cont;
    }
    
    public function getVariables() {
        $cont = array();
        
        // Delete whitespace first to simplify further processing
        $tokens = $this->_tokenized;
        foreach($tokens as $i=>$token) {
            if ($token["type"] == "T_WHITESPACE") {
                unset($tokens[$i]);
            }
        }
        
        if (isset($tokens[0]) && $tokens[0]["type"] != "T_FUNCTION") {
            return array();
        }
        
        foreach ($tokens as $i=>$token) {
            if ($token["type"] == "T_VARIABLE") {
                // Store variable as key
                $cont[$token["content"]] = "";
                
                // See if we can also store a default value
                if (isset($tokens[$i+1]) && $tokens[$i+1]["type"] == "T_EQUAL" && isset($tokens[$i+2])) {
                    $cont[$token["content"]]["type"]    = $tokens[$i+2]["type"];
                    $cont[$token["content"]]["content"] = $tokens[$i+2]["content"];
                }
            }
        }
        return $cont;
    }        
        
    public function getTypes() {
        $cont = array();
        foreach ($this->_tokenized as $i=>$token) {
            $cont[] = $token["type"];
        }
        return $cont;
    }    
    
    protected function _updateRowCol($c) {
        // Update line count
        $numNewLines = substr_count($c, "\n");
        if (1 <= $numNewLines) {
            // Have new lines, add them in
            $this->_row += $numNewLines;
            //$ret_row     = $this->_row; 
            $this->_col   =  1;
    
            // Skip to right past the last new line, as it won't affect the column position
            $c = substr($c, strrpos($c, "\n") + 1);
            if ($c === false) {
                $c = '';
            }
        }
        $len = strlen($c);
        
        $ret_col = $this->_col;
        $ret_row = $this->_row;
        
        // Update column count
        $this->_col += $len;
        
        return array($ret_row, $ret_col, $len);
    }    
    
    /**
     * Creates an array of tokens when given some PHP code.
     *
     * Starts by using token_get_all() but does a lot of extra processing
     * to insert information about the context of the token.
     *
     * @param string $string  The string to tokenize.
     * @param string $eolChar The EOL character to use for splitting strings.
     *
     * @return array
     */
    protected function _tokenizeString($string, $eolChar='\n')
    {
        $tokens      = @token_get_all($string);
        $finalTokens = array();
        $this->_row = 1;
        $this->_col  = 1; 
        
        $newStackPtr = 0;
        $numTokens   = count($tokens);
        for ($stackPtr = 0; $stackPtr < $numTokens; $stackPtr++) {
            $token        = $tokens[$stackPtr];
            $tokenIsArray = is_array($token);

            /*
                If we are using \r\n newline characters, the \r and \n are sometimes
                split over two tokens. This normally occurs after comments. We need
                to merge these two characters together so that our line endings are
                consistent for all lines.
            */

            if ($tokenIsArray === true && substr($token[1], -1) === "\r") {
                if (isset($tokens[($stackPtr + 1)]) === true && is_array($tokens[($stackPtr + 1)]) === true && $tokens[($stackPtr + 1)][1][0] === "\n") {
                    $token[1] .= "\n";

                    if ($tokens[($stackPtr + 1)][1] === "\n") {
                        // The next token's content has been merged into this token,
                        // so we can skip it.
                        $stackPtr++;
                    } else {
                        $tokens[($stackPtr + 1)][1] = substr($tokens[($stackPtr + 1)][1], 1);
                    }
                }
            }//end if

            /*
                If this is a double quoted string, PHP will tokenise the whole
                thing which causes problems with the scope map when braces are
                within the string. So we need to merge the tokens together to
                provide a single string.
            */

            if ($tokenIsArray === false && $token === '"') {
                $tokenContent = '"';
                $nestedVars   = array();
                for ($i = ($stackPtr + 1); $i < $numTokens; $i++) {
                    $subTokenIsArray = is_array($tokens[$i]);

                    if ($subTokenIsArray === true) {
                        $tokenContent .= $tokens[$i][1];
                        if ($tokens[$i][1] === '{') {
                            $nestedVars[] = $i;
                        }
                    } else {
                        $tokenContent .= $tokens[$i];
                        if ($tokens[$i] === '}') {
                            array_pop($nestedVars);
                        }
                    }

                    if ($subTokenIsArray === false && $tokens[$i] === '"' && empty($nestedVars) === true) {
                        // We found the other end of the double quoted string.
                        break;
                    }
                }

                $stackPtr = $i;

                // Convert each line within the double quoted string to a
                // new token, so it conforms with other multiple line tokens.
                $tokenLines = explode($eolChar, $tokenContent);
                $numLines   = count($tokenLines);
                $newToken   = array();

                for ($j = 0; $j < $numLines; $j++) {
                    $newToken['content'] = $tokenLines[$j];
                    if ($j === ($numLines - 1)) {
                        if ($tokenLines[$j] === '') {
                            break;
                        }
                    } else {
                        $newToken['content'] .= $eolChar;
                    }

                    $newToken['code']          = T_DOUBLE_QUOTED_STRING;
                    $newToken['type']          = 'T_DOUBLE_QUOTED_STRING';
                    $finalTokens[$newStackPtr] = $newToken;
                    $newStackPtr++;
                }

                // Continue, as we're done with this token.
                continue;
            }//end if

            /*
                If this is a heredoc, PHP will tokenise the whole
                thing which causes problems when heredocs don't
                contain real PHP code, which is almost never.
                We want to leave the start and end heredoc tokens
                alone though.
            */

            if ($tokenIsArray === true && $token[0] === T_START_HEREDOC) {
                // Add the start heredoc token to the final array.
                $finalTokens[$newStackPtr] = $this->_standardiseToken($token);
                $newStackPtr++;

                $tokenContent = '';
                for ($i = ($stackPtr + 1); $i < $numTokens; $i++) {
                    $subTokenIsArray = is_array($tokens[$i]);
                    if ($subTokenIsArray === true && $tokens[$i][0] === T_END_HEREDOC) {
                        // We found the other end of the heredoc.
                        break;
                    }

                    if ($subTokenIsArray === true) {
                        $tokenContent .= $tokens[$i][1];
                    } else {
                        $tokenContent .= $tokens[$i];
                    }
                }

                $stackPtr = $i;

                // Convert each line within the heredoc to a
                // new token, so it conforms with other multiple line tokens.
                $tokenLines = explode($eolChar, $tokenContent);
                $numLines   = count($tokenLines);
                $newToken   = array();

                for ($j = 0; $j < $numLines; $j++) {
                    $newToken['content'] = $tokenLines[$j];
                    if ($j === ($numLines - 1)) {
                        if ($tokenLines[$j] === '') {
                            break;
                        }
                    } else {
                        $newToken['content'] .= $eolChar;
                    }

                    $newToken['code']          = T_HEREDOC;
                    $newToken['type']          = 'T_HEREDOC';
                    $finalTokens[$newStackPtr] = $newToken;
                    $newStackPtr++;
                }

                // Add the end heredoc token to the final array.
                $finalTokens[$newStackPtr] = $this->_standardiseToken($tokens[$stackPtr]);
                $newStackPtr++;

                // Continue, as we're done with this token.
                continue;
            }//end if

            /*
                If this token has newlines in its content, split each line up
                and create a new token for each line. We do this so it's easier
                to asertain where errors occur on a line.
                Note that $token[1] is the token's content.
            */

            if ($tokenIsArray === true && strpos($token[1], $eolChar) !== false) {
                $tokenLines = explode($eolChar, $token[1]);
                $numLines   = count($tokenLines);
                $tokenName  = token_name($token[0]);

                for ($i = 0; $i < $numLines; $i++) {
                    $newToken['content'] = $tokenLines[$i];
                    if ($i === ($numLines - 1)) {
                        if ($tokenLines[$i] === '') {
                            break;
                        }
                    } else {
                        $newToken['content'] .= $eolChar;
                    }

                    $newToken['type']          = $tokenName;
                    $newToken['code']          = $token[0];
                    $finalTokens[$newStackPtr] = $newToken;
                    $newStackPtr++;
                }
            } else {
                $newToken = $this->_standardiseToken($token);

                // This is a special condition for T_ARRAY tokens use to
                // type hint function arguments as being arrays. We want to keep
                // the parenthsis map clean, so let's tag these tokens as
                // T_ARRAY_HINT.
                if ($newToken['code'] === T_ARRAY) {
                    // Recalculate number of tokens.
                    $numTokens = count($tokens);
                    for ($i = $stackPtr; $i < $numTokens; $i++) {
                        if (is_array($tokens[$i]) === false) {
                            if ($tokens[$i] === '(') {
                                break;
                            }
                        } else if ($tokens[$i][0] === T_VARIABLE) {
                            $newToken['code'] = T_ARRAY_HINT;
                            $newToken['type'] = 'T_ARRAY_HINT';
                            break;
                        }
                    }
                }

                $finalTokens[$newStackPtr] = $newToken;
                $newStackPtr++;
            }//end if
        }//end for

        
        /*
            If we added tags at runtime, we need to avoid
            enclosing them in our token
        */            
        foreach ($finalTokens as $i=>$token) {  
            if ($this->_addedTagBegin && $token['type'] == 'T_OPEN_TAG') {
                unset($finalTokens[$i]);
            } elseif ($this->_addedTagClose && $token['type'] == 'T_CLOSE_TAG') {
                unset($finalTokens[$i]);
            } elseif ($this->_addedTagClose && $token['type'] == 'T_DOUBLE_QUOTED_STRING') {
                if (substr($token["content"],-2) == "?>") {
                    $finalTokens[$i]["content"] = substr($token["content"], 0, strlen($token["content"])-2);
                }
            }
        }

        /*
            Cols & Rows would be nice
        */            
        
        foreach ($finalTokens as $i=>$token) {
            list($line, $col, $len) = $this->_updateRowCol($token['content']);
            
            $finalTokens[$i]['row'] = $line;
            $finalTokens[$i]['col'] = $col;
            $finalTokens[$i]['len'] = $len; 
        }
        
        return $finalTokens;

    }//end _tokenizeString()
}    
?>