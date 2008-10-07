<?php
/**
 * Returns new array and preserves until a specific line is found in the old one.
 * Ideal for rewriting config files with dynamic content, but still allowing
 * custom rules above that. 
 *
 * @param array $original
 * @param array $dynamic
 * @param string $splitLine
 * 
 * @return array
 */
function preserveUntil($original=array(), $dynamic=array(), $splitLine = "# PLEASE DO NOT EDIT BELOW THIS LINE! #") {
    $new = array();
    
    $splitLineAt = false;
    foreach ($original as $n=>$line) {
        if (trim($line) == trim($splitLine)) {
            $splitLineAt = $n;
            break;
        }
    }
    
    if (is_numeric($splitLineAt) && $splitLineAt) {
        $new = array_slice($original, 0, ($splitLineAt-1));
    } else {
        // Failsafe. No splitLine found. Preserve entire original.
        $new = $original;
    }
    
    $new[] = $splitLine;
    $new   = array_merge($new, $dynamic);
    
    return $new;
}
?>