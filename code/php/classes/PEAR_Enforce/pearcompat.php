#!/usr/bin/php -q
<?php
require_once "Enforce.php";

$file   = (!isset($argv[1])) ? "" : $argv[1];
$action = (!isset($argv[2])) ? "" : $argv[2]; 
$id     = (!isset($argv[3])) ? "" : $argv[3];

$PEAR_Enforce = new PEAR_Enforce($file);

switch ($action) {
    case "token":
        $lines = file($file);
        $line  = $lines[$id-1];
        
        $Token = new Token($line);
        
        print_r($Token->getTokenized())."\n";
        
        break;
    case "test":
        echo "Running Test [$test]\n\n";
        
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
        break;
}
?>