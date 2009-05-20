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
    public function __call($tag, $args) {
        $body       = array_shift($args);
        $this->tag($tag, $body, $args);
    }

    public function tag($tag, $body, $args) {
        if (is_array($arguments) && count($arguments)) {
            $argumentsT = implode(' ', $arguments);
        } else {
            $argumentsT = '';
        }
        
        if (false === $body) {
            // End tag
            return '<'.$tag.' class="'.$class.'" '.$argumentsT.' />'."\n";
        } else if (null === $body) {
            // Opening tag
            return '<'.$tag.' class="'.$class.'" '.$argumentsT.'>'."\n";
        } else {
            // Full tag
            return '<'.$tag.' class="'.$class.'" '.$argumentsT.'>'."\n".$this->indent($body)."\n".'</'.$tag.'>'."\n";
        }
    }

    public function hr() {
        return sprintf('<hr />'."\n");
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
