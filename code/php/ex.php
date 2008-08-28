#!/usr/bin/php -q
<?php


    /**
     * Doctest syntax prefix default is a standard php inline comment: '//'
     */
    define('SYNTAX_PREFIX','//');
    define('KW_DOCTEST_EXPECTS','expects');

    function _hasDocTest($data)
    {
        $p = preg_quote(SYNTAX_PREFIX, '/');
        $k = preg_quote(KW_DOCTEST_EXPECTS, '/');
        return preg_match("/$p\s?$k/m", $data);
    }

    function _extractCodeBlocs($docstring)
    {
        $ret = array();
        // extract <code></code> blocks, we use preg_match_all because there 
        // could be more than one code block by docstring
        $rx = '/<code>[\s\*]*(<[\?\%](php)?)?\s*' 
            . '(.*?)\s*([\?\%]>)?[\s\*]*<\/code>/si';

        $rx = '/<code>[\s\*]*(<[\?\%](php)?)?\s*' 
            . '(.*?)[\s]*([\?\%]>)?[\s\*]*<\/code>/si';

            
        $rx = '#<code>[\s\*]*(<[\?\%](php)?)?\s*' 
            . '(.*?)\s\*([\?\%]>)?[\s\*]*<\/code>#si';
            
        
        preg_match_all($rx, $docstring, $tokens);
        
        print_r($tokens);
                
        if (isset($tokens[3]) && is_array($tokens[3])) {
            foreach ($tokens[3] as $i => $token) {
                if (!_hasDocTest($token)) {
                    // not a doctest
                    continue;
                }
                $ret[] = $token;
            }
        }
        return $ret;
    }

    $fp = dirname(__FILE__)."/functions/explodeTree.inc.php";
    
    
    $b = file_get_contents($fp);
    
    
    $b = preg_replace('/[^a-zA-Z0-9\-\_\=\n\*\ \/\;\:\.\{\}\<\>\$\(\)\?\!\[\]\&\@\"\']/', '', $b);
    
    echo $b;
    
    $a = _extractCodeBlocs($b);
    print_r($a);
    
?>