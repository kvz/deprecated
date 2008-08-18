#!/usr/bin/php -q
<?php

/*$foo = "kevin martijn erwin disable1dennis";
$result = preg_replace('#(?!disable1|disable2)[a-z0-9]+#is', '$1X', $foo);

echo "result: ".$result."\n";
*/
/*$exc = "kevin|jp|martijn|erwin";
$str = "dennis> kevin> jp> martijn> erwin>";
$pat = '(?!(kevin>)+)';
$str = preg_replace('/'.$pat.'/', 'XXX', $str);

echo "pat: ".$pat."\n";
echo "str: ".$str."\n";

die();
*/
error_reporting(E_ALL);
require_once "Enforce.php";

$file   = (!isset($argv[1])) ? "" : $argv[1];
$action = (!isset($argv[2])) ? "" : $argv[2]; 
$id     = (!isset($argv[3])) ? "" : $argv[3];
$arg4   = (!isset($argv[4])) ? "" : $argv[4];

$PEAR_Enforce = new PEAR_Enforce($file);

switch ($action) {
    case "token":
        $lines = file($file);
        $line  = $lines[$id-1];
        
        $Token = new TokenSimple($line);
        
        echo "Showing Tokens [$line]\n\n";
        
        print_r($Token->getTokenized())."\n";
        
        break;
    case "test":
        echo "Running Test [".$id."]\n\n";
        
        $CodeRow = new CodeRow("abcdefghijklmnopqrstuvwxyz'=' = 12345");
        $CodeRow = new CodeRow("\$x = \"\\\$kevin = 1\";");
        
        switch ($id) {
            case 1:
                $CodeRow->setIndent(12);
                break;
            case 2:
                $CodeRow->deleteAt(4, -2);
                break;
            case 3:
                $CodeRow->insertAt(4, "x", -2);
                break;
            case 4:
                echo $CodeRow->getCodeRow()."\n";
                print_r($CodeRow->getTokenized())."\n";
                break;
            case 5:
                $CodeRow->insertAt(4, "x", -2);
                break;
            case 6:
                $x = $PEAR_Enforce->syntaxCheckFile($file);
                print_r($x);
                break;
        }
        
        break;
    case "docblock":
        $DocBlock = new DocBlock();
        $DocBlock->setIndent(4);
        
        $DocBlock->setHeader("Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! ");
        
        $DocBlock->setRow("param", "\$strData", "string", "Used for processing");
        $DocBlock->setRow("param", "\$no", "integer", "");
        $DocBlock->setRow("param", "\$lines", "array");
        
        $DocBlock->setRow("return", "array");
        
        print_r($DocBlock->getParams());
        
        
        echo $DocBlock->generate();
        echo "\n";
        
        if (count($DocBlock->errors)) {
            print_r($DocBlock->errors);
        }
        
        break; 
    case "":
    case "report":
    default:
        if (!$action) {
            $action = "report";
        }
        
        $PEAR_Enforce->enforce();
        echo $PEAR_Enforce->report($action);
        
        if (is_numeric($id)) {
            if (!isset($PEAR_Enforce->wasModifiedBy[$id])) {
                echo "Line $id was not modified by any fixer\n";
            } else {
                echo "Line $id was modified by:\n";
                print_r($PEAR_Enforce->wasModifiedBy[$id]);
            }
        }
        
        break;
}
?>