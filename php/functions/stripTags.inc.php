<?php
/**
 * PHP's own strip_tags will destroy text after a < character, even if
 * it's not a real tag. So this is the improved version of strip_tags that
 * tries to match full tags, and only strips them.
 *
 * The following code block can be utilized by PEAR's Testing_DocTest
 * <code>
 * // Input //
 * $input = "Kevin and <b>Max</b> go for walk in the <i>park</i>.";
 * 
 * // Execute //
 * $output   = array();
 * $output[] = stripTags($input);
 * 
 * // Show //
 * print_r($output);
 * 
 * // expects:
 * // Array
 * // (
 * //     [0] => Kevin and Max go for walk in the park.
 * // )
 * </code>
 * 
 * @param string $str
 * 
 * @return string
 */
function stripTags($str)
{
    return preg_replace('@<\/?([^>]+)\/?>@s', '', $str);
}
?>