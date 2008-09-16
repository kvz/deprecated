<?php

function parseDocBlock($str) {
    $keyChars = array("@");
    
    $lines = explode("\n", $str);
    
    $head       = "";
    $text       = "";
    $headRecing = true;
    $keys       = array();
    
    foreach ($lines as $i=>$line) {
        $tline = trim($line);
        $firstChar = substr($tline, 0, 1);
        
        if (in_array($firstChar, $keyChars)) {
            $parts = preg_split('/[\s]+/', $tline,  -1, PREG_SPLIT_NO_EMPTY);
            $keys[array_shift($parts)] = $parts;
        } else {
            if ($headRecing) {
                $head .= $tline;
            }
            
            $text .= $tline;
        }
        
        if (!$tline) {
            $headRecing = false;
        }
    }
    
    $parts = explode("\n", $head);
    $title = reset($head);
    
    return compact("title", "head", "text", "keys");
}
?>