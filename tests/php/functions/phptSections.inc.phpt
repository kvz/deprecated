--TEST--
--FILE--
<?php
require_once str_replace(array("/tests/", ".inc.phpt.php"), array("/code/", ".inc.php"), __FILE__);
// Input //
$input = "--TEST--\n--FILE--\n<?php\necho 'a\n';\n\n?>\n--EXPECT--\na";

// Execute //
$sections = phptSections($input);

// Show //
print_r($sections);
?>
--EXPECT--
Array
(
    [TEST] => 
    [SKIPIF] => 
    [GET] => 
    [COOKIE] => 
    [POST_RAW] => 
    [POST] => 
    [UPLOAD] => 
    [ARGS] => 
    [FILE] => <?php
echo 'a
';

?>

    [EXPECT] => a

)