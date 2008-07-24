<?php
/**
 * Finds a substring between two needles
 *
 * @param string  $haystack
 * @param string  $left
 * @param string  $right
 * @param boolean $include_needles
 * @param boolean $case_sensitive
 * 
 * @return mixed boolean or string
 */
function strBetween($haystack, $left, $right, $include_needles=false, $case_sensitive=true)
{
    // Set parameters
    $left      = preg_quote($left);
    $right     = preg_quote($right);
    $modifiers = "s";
    
    // Case insensitive modifier 
    if ($case_sensitive){
        $modifiers .= "i";
    }
    
    // Match
    $pattern = '/('.$left.')(.+?)('.$right.')/s'.$modifiers;
    if (!preg_match($pattern, $haystack, $r)) {
        // Not found
        return false;
    }
    
    // Include needles?
    $return = $r[2];
    if ($include_needles) {
        $return = $r[1].$return.$r[3];
    }
    
    // Return
    return $return;
}
?>