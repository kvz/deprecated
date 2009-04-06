#!/usr/bin/php -q
<?php
error_reporting(E_ALL);
set_time_limit(0);
ini_set("memory_limit", "32M");

define('DIR_KVZLIB', realpath(dirname(__FILE__)."/../../../"));
ini_set("include_path", DIR_KVZLIB.":/usr/share/php:/usr/share/pear");

require_once "php/small_classes.php";
require_once "Enforce.php";

$file   = (!isset($argv[1])) ? "" : $argv[1];
$action = (!isset($argv[2])) ? "" : $argv[2]; 
$id     = (!isset($argv[3])) ? "" : $argv[3];
$arg4   = (!isset($argv[4])) ? "" : $argv[4];

$PEAR_Enforce = new PEAR_Enforce($file);

$defaults = array(
    "@author" => "Kevin van Zonneveld <kevin@vanzonneveld.net>",
    "@copyright" => date("Y")." Kevin van Zonneveld (http://kevin.vanzonneveld.net)", 
    "@license" => "New BSD License",
    "@version" => "SVN: Release: \$Id\$", 
    "@link"=> "http://kevin.vanzonneveld.net"
);

$PEAR_Enforce->setDocBLockDefaults($defaults);

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
        $DocBlockWriter = new DocBlockWriter();
        $DocBlockWriter->setIndent(4);
        
/*        
        $DocBlockWriter->setHeader("Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! Very nice docBlock! ");
        
        $DocBlockWriter->setRow("param", "\$strData", "string", "Used for processing");
        $DocBlockWriter->setRow("param", "\$no", "integer", "");
        $DocBlockWriter->setRow("param", "\$lines", "array");
        
        $DocBlockWriter->setRow("return", "array");
        print_r($DocBlockWriter->getParams());
*/        
        
        
        echo $DocBlockWriter->generateFile();
        echo "\n";
        
        if (count($DocBlockWriter->errors)) {
            print_r($DocBlockWriter->errors);
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