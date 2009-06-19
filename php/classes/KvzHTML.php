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

    /**
     * Can track TOC if you use H1, H2 tags
     *
     * @var array
     */
    protected $_toc = array();
    protected $_tocLevelPrev = false;
    protected $_tocIdPrev = false;
    
    protected $_options = array();

    
    public function  __construct($options = array()) {
        $this->_options = $options;
        
        if (!isset($this->_options['track_toc'])) $this->_options['track_toc'] = false;
        if (!isset($this->_options['link_toc'])) $this->_options['link_toc'] = true;
    }

    public function __call($tag, $arguments) {
        $body       = array_shift($arguments);
        $args       = array_shift($arguments);
        $bodySuffix = '';

        // TOC?
        if ($this->_options['track_toc']) {
            if (preg_match('/h(\d)/i', $tag, $m)) {
                $tocId     = count($this->_toc);
                $tocLevel  = $m[1];
                $tocLine   = '';
                $suffix    = '';
                $prefix    = '';
                
                if ($this->_tocIdPrev === false) {
                    // root element
                    $prefix = $this->tag('a', '', array('name' => 'toc_'.'root', '__trimbody' => true));
                } elseif ($tocLevel < $this->_tocLevelPrev) {
                    $prefix = "\n". str_repeat('</ul>', ($this->_tocLevelPrev - $tocLevel));
                } elseif ($this->_tocIdPrev === false || $tocLevel > $this->_tocLevelPrev) {
                    $prefix = '<ul>'."\n";
                }
                
                $tocLine .= $prefix;
                $tocLine .= str_repeat(' ', $tocLevel). '<li>';
                $tocLine .= trim($body);
                if ($this->_options['link_toc']) {
                    // Add Jump link to anchor
                    $tocLine .= ' '.$this->tag('a', '[jump]', array('href' => '#toc_'.$tocId, '__trimbody' => true));
                    $bodySuffix .= $this->tag('a', '', array('name' => 'toc_'.$tocId, '__trimbody' => true));
                    $bodySuffix .= $this->tag('a', '[toc]', array('href' => '#toc_'.'root', '__trimbody' => true));
                } 
                $tocLine .= '</li>';
                $tocLine .= $suffix;

                $this->_toc[$tocId]  = $tocLine;
                $this->_tocLevelPrev = $tocLevel;
                $this->_tocIdPrev    = $tocId;
            }
        }

        return $this->tag($tag, $body . $bodySuffix, $args);
    }

    public function tag($tag, $body = false, $args = array()) {
        if (is_array($body)) {
            $body = implode("\n", $body);
        }

        $newLineAfterOpeningTag = true;

        $bodyIndented = $this->indent($body)."\n";
        if (!empty($args['__trimbody'])) {
            $bodyIndented           = trim($bodyIndented);
            $newLineAfterOpeningTag = false;
        }

        if (!empty($args['__skip'])) {
            return '';
        }

        if (!empty($args['__onlybody'])) {
            return $bodyIndented;
        }

        $argumentsT = '';
        if (is_array($args) && count($args)) {
            foreach($args as $k=>$v) {
                if (substr($k, 0, 2) == '__') {
                    continue;
                }

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
                    if (!empty($v)) {
                        $argumentsT .= sprintf(' %s=\'%s\'', $k, trim($v));
                    }
                }
            }
        } else {
            $argumentsT = '';
        }

        if (false === $body) {
            // End tag
            return '<'.$tag.$argumentsT.' />'.($newLineAfterOpeningTag ? "\n" : "");
        } else if (null === $body) {
            // Opening tag
            return '<'.$tag.$argumentsT.'>'.($newLineAfterOpeningTag ? "\n" : "");
        } else {
            // Full tag
            return '<'.$tag.$argumentsT.'>'. ($newLineAfterOpeningTag ? "\n" : "").$bodyIndented.'</'.$tag.'>'."\n";
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

    public function getToc() {
        $toc = $this->_toc;
        $toc[] = str_repeat('</ul>', $this->_tocLevelPrev);
        return $toc;
    }

    public function indent($str, $indent = 4) {
        if (is_array($str)) {
            $str = implode("\n", $str);
        }
        if (!is_string($str)) {
            return $str;
        }

        $lines = explode("\n", $str);
        foreach ($lines as &$line) {
            $line = str_repeat(' ', $indent) . $line;
        }

        return implode("\n", $lines);
    }
}
?>