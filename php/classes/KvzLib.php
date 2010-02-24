<?php
require_once dirname(__FILE__)."/KvzShell.php";
require_once dirname(__FILE__)."/DocBlockReader.php";

/**
 * Indexes all code in the KvzLib repository
 * Testing needs pear install -f Testing_DocTest
 *
 * PHP version 5
 *
 * @package   KvzLib
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/code/
 */
class KvzLib extends KvzShell {
    protected $_path = "";

    public $languages = array();

    public $DocBlockReader = false;

    public $blackList = array();

    public function  __construct($path=false) {
        if (!$path) {
            $this->log("Path: '$path' is empty", KvzLib::LOG_EMERG);
            return false;
        }

        if (!file_exists($path)) {
            $this->log("Path: '$path' does not exist", KvzLib::LOG_EMERG);
            return false;
        }

        $this->blackList = array();
        $this->blackList["bash.dev"] = true;
        $this->blackList["bash"]['scraps'] = true;
        $this->blackList["nbproject"] = true;
        $this->blackList["php"]['samples'] = true;

        $options = array(
            "bash_support" => true,
            "one" => true,
        );
        $this->DocBlockReader = new DocBlockReader($options);
        
        $this->_path = $path;
    }

    public function test(){
        $cmds = array("pear", "phpdt");
        $this->initCommands($cmds, true);

        $x = $this->exeGlue("phpdt", $this->_path);
        echo implode("\n", $x);
    }

    public function &getEntityByX($field, $value) {
        foreach($this->languages as $language=>$types){
            foreach($types as $type=>$entities) {
                foreach ($entities as $entityName=>$entity) {
                    if ($this->languages[$language][$type][$entityName][$field] == $value) {
                        return $this->languages[$language][$type][$entityName];
                    }
                }
            }
        }
        return false;
    }

    public function &getEntityByID($value) {
        return $this->getEntityByX("id", $value);
    }

    public function &getEntityByName($value) {
        return $this->getEntityByX("name", $value);
    }


    /**
     * Find largest source file if entity is directory
     *
     * @param string $pathSource
     * 
     * @return string
     */
    public function findSourceInDir($pathSource) {
        // Find largest source file if entity is directory
        $sourceFiles = array();

        foreach(glob($pathSource."/*") as $file) {
            if (is_dir($file)) {
                continue;
            }
            $sourceFiles[filesize($file)] = $file;
        }

        if (!count($sourceFiles)) {
            throw new KvzLib_Exception("Unable to find sources in ".$pathSource);
        }

        uksort($sourceFiles, "strnatcmp");
        $pathSource = end($sourceFiles);

        return $pathSource;
    }

    public function parseEntity($baseLanguage, $baseType, $pathEntity) {

        if (!file_exists($pathEntity)) {
            throw new KvzLib_Exception("File: '".$pathEntity."' does not exist");
        }

        $info = array();
        $info["name"]     = basename($pathEntity);
        $info["path"]     = $pathEntity;
        $info["language"] = $baseLanguage;
        $info["type"]     = $baseType;
        $info["id"]       = $info["language"]."_".$info["type"]."_".reset(explode(".", $info["name"]));

        $pathSource = $pathEntity;
        if (is_dir($pathSource)) {
            // Find largest source file if entity is directory
            $pathSource = $this->findSourceInDir($pathSource);
        }

        $source = file_get_contents($pathSource);
        try {
            $info["docblocks"] = $this->DocBlockReader->getDocBlocks($source);
        } catch (DocBlockReader_Exception $e) {
            // throw new KvzLib_Exception("Unable to parse ".$pathSource);
        }

        return $info;
    }

    public function index() {
        $this->languages = array();
        foreach(glob($this->_path."/*") as $pathLanguage) {
            if (!is_dir($pathLanguage)) { continue; }
            $baseLanguage = basename($pathLanguage);
            foreach(glob($pathLanguage."/*") as $pathType) {
                if (!is_dir($pathType)) { continue; }
                $baseType = basename($pathType);
                foreach(glob($pathType."/*") as $pathEntity) {
                    $baseEntitity = basename($pathEntity);

                    if (isset($this->blackList[$baseLanguage]) && $this->blackList[$baseLanguage] === true) {continue;}
                    if (isset($this->blackList[$baseLanguage][$baseType]) && $this->blackList[$baseLanguage][$baseType] === true) {continue;}
                    if (isset($this->blackList[$baseLanguage][$baseType][$baseEntitity]) && $this->blackList[$baseLanguage][$baseType][$baseEntitity] === true) {continue;}

                    $this->languages[$baseLanguage][$baseType][$baseEntitity] = $this->parseEntity($baseLanguage, $baseType, $pathEntity);
                }
            }
        }
    }
}

class KvzLib_Exception extends KvzShell_Exception {

}

?>