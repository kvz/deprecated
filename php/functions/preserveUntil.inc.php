<?php
/**
 * Returns new array and preserves until a specific line is found in the old one.
 * Ideal for rewriting config files with dynamic content, but still allowing
 * custom rules above that. 
 * 
 * Tip, in combination with file(), consider using the FILE_IGNORE_NEW_LINES flag
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
    
    if (is_numeric($splitLineAt)) {
        $new = array_slice($original, 0, ($splitLineAt));
    } else {
        // Failsafe. No splitLine found. Preserve entire original.
        $new = $original;
    }
    
    $new[] = $splitLine;
    $new   = array_merge($new, $dynamic);
    
    return $new;
}
?>