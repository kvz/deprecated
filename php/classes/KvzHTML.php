<?php
/**
 * Contains some methods that ease up working with html
 *
 * PHP version 5
 *
 * @package   KvzShell
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net/code/
 */
Class KvzHtml {
    public function __call($tag, $arguments) {
        $body       = array_shift($arguments);
        $args       = array_shift($arguments);
        return $this->tag($tag, $body, $args);
    }
    
    public function tag($tag, $body = false, $args = array()) {
        $argumentsT = '';
        if (is_array($args) && count($args)) {
            foreach($args as $k=>$v) {

                if (is_array($v)) {
                    if ($k == 'style') {
                        $v2 = '';
                        foreach($v as $stylek => $stylev) {
                            $v2 .= sprintf(' %s: %s;', $stylek, $stylev);
                        }
                        $v = trim($v2);
                    } else {
                        $v = implode(' ', $v);
                    }
                }

                if (is_numeric($k)) {
                    $argumentsT .= sprintf(' %s', $v);
                } else {
                    $argumentsT .= sprintf(' %s=\'%s\'', $k, $v);
                }
            }
        } else {
            $argumentsT = '';
        }
        
        if (false === $body) {
            // End tag
            return '<'.$tag.$argumentsT.' />'."\n";
        } else if (null === $body) {
            // Opening tag
            return '<'.$tag.$argumentsT.'>'."\n";
        } else {
            // Full tag
            return '<'.$tag.$argumentsT.'>'."\n".$this->indent($body)."\n".'</'.$tag.'>'."\n";
        }
    }
    
    public function a($link, $title = '') {
        return sprintf('<a href="%s">%s</a>'."\n", $link, $title);
    }

    public function css($link) {
        return sprintf('<link rel="stylesheet" type="text/css" href="%s" />'."\n", $link);
    }

    public function js($link) {
        return sprintf('<script type="text/javascript" src="%s"></script>'."\n", $link);
    }

    public function img($link, $class = '') {
        return sprintf('<img src="%s" class="%s" />'."\n", $link, $class);
    }
    
    public function indent($str) {
        $lines = explode("\n", $str);
        foreach ($lines as &$line) {
            $line = '    '. $line;
        }

        return implode("\n", $lines);
    }
}
?>