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
    protected $_idCnt = array();
    protected $_ids = array();
    
    protected $_options = array();

    
    public function  __construct($options = array()) {
        $this->_options = $options;
        
        if (!isset($this->_options['xhtml'])) $this->_options['xhtml'] = true;
        if (!isset($this->_options['track_toc'])) $this->_options['track_toc'] = false;
        if (!isset($this->_options['link_toc'])) $this->_options['link_toc'] = true;
        if (!isset($this->_options['indentation'])) $this->_options['indentation'] = 4;
        if (!isset($this->_options['newlines'])) $this->_options['newlines'] = true;

        // Not recommended cause you cannot nest tags with echo:
        if (!isset($this->_options['echo'])) $this->_options['echo'] = false;
    }

    public function __call($tag, $arguments) {
        if (!count($arguments)) {
            $body       = true;
            $args       = array();
        } else {
            $body       = array_shift($arguments);
            $args       = array_shift($arguments);
        }
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
                    $prefix = $this->_tag('a', '', array('name' => 'toc_'.'root', '__trimbody' => true));
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
                    $tocLine .= ' '.$this->_tag('a', '[jump]', array('href' => '#toc_'.$tocId, '__trimbody' => true));
                    $bodySuffix .= $this->_tag('a', '', array('name' => 'toc_'.$tocId, '__trimbody' => true));
                    $bodySuffix .= $this->_tag('a', '[toc]', array('href' => '#toc_'.'root', '__trimbody' => true));
                } 
                $tocLine .= '</li>';
                $tocLine .= $suffix;

                $this->_toc[$tocId]  = $tocLine;
                $this->_tocLevelPrev = $tocLevel;
                $this->_tocIdPrev    = $tocId;
            }
        }

        return $this->_tag($tag, (!is_string($body) ? $body : $body . $bodySuffix), $args);
    }

    public function reset() {
        $this->_idCnt = array();
        $this->_ids = array();
    }

    protected function _createId($tag) {
        if (!isset($this->_idCnt[$tag])) {
            $this->_idCnt[$tag] = 0;
        }
        
        $this->_idCnt[$tag]++;
        $id = $tag . '-' . $this->_idCnt[$tag];
        $this->_ids[] = $id;

        return $id;
    }

    public function getLastId() {
        return end($this->_ids);
    }

    protected function _tag($tag, $body = true, $args = array()) {
        if (is_array($body)) {
            $body = implode("\n", $body);
        }

        $newLineAfterOpeningTag = true;
        $newLineAfterClosingTag = true;

        $bodyIndented = $this->indent($body)."\n";
        if (!empty($args['__trimbody'])) {
            $bodyIndented           = trim($bodyIndented);
            $newLineAfterOpeningTag = false;
        }

        if (true === @$args['id']) {
            // auto id
            $args['id'] = $this->_createId($tag);
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
        
        if (null === $body) {
            // self closing tag
            $result = '<'.$tag.$argumentsT.' '.($this->_options['xhtml'] ? '/' : '').'>'.($newLineAfterOpeningTag ? "\n" : "");
        } else if (false === $body) {
            // End tag
            $result = '</'.$tag.'>'.($newLineAfterClosingTag ? "\n" : "");
        } else if (true === $body) {
            // Opening tag
            $result = '<'.$tag.$argumentsT.'>'.($newLineAfterOpeningTag ? "\n" : "");
        } else {
            // Full tag
            $result = '<'.$tag.$argumentsT.'>'. ($newLineAfterOpeningTag ? "\n" : "").$bodyIndented.'</'.$tag.'>'."\n";
        }

        if ($this->_options['echo']) {
            echo $result;
            return true;
        } else {
            return $result;
        }
    }

    public function a($link, $title = '') {
        return $this->_tag('link', $title, array(
            'href'=> $link,
        ));
    }

    public function css($link) {
        return $this->_tag('link', null, array(
            'type' => 'text/css',
            'rel' => 'stylesheet',
            'href'=> $link,
        ));
    }

    public function js($link) {
        return $this->_tag('script', '', array(
            'type' => 'text/javascript',
            'src'=> $link,
            '__trimbody' => true,
        ));
    }

    public function clear($body = '', $args = array()) {
        return $this->_tag('div', $body, array_merge_recursive(array(
            'style' => array(
                'clear' => 'both',
            ),
        ), $args));
    }
    public function page($body = true, $args = array()) {
        return $this->_tag('div', $body, array_merge_recursive(array(
            'class' => array(
                'page'
            ),
        ), $args));
    }
    public function float($body = true, $args = array()) {
        return $this->_tag('div', $body, array_merge_recursive(array(
            'style' => array(
                'float' => 'left',
            ),
        ), $args));
    }

    public function img($link, $args = array()) {
        $args = array_merge(array('src' => $link), $args);
        return $this->_tag('img', null, $args);
    }

    public function getToc() {
        $toc = $this->_toc;
        $toc[] = str_repeat('</ul>', $this->_tocLevelPrev);
        return $toc;
    }

    protected function _indent($indentation = null) {
        if ($indentation === null && isset($this->_options['indentation'])) {
            $indentation = $this->_options['indentation'];
        }
        // Lot of ways to set indent
        if (is_numeric($indentation)) {
            $indent = str_repeat(' ', $indentation);
        } elseif (is_string($indentation)) {
            $indent = $indentation;
        } elseif ($indentation === true || $indentation === null) {
            $indent = '    ';
        } elseif ($indentation === false) {
            $indent = '';
        } else {
            trigger_error(sprintf(
                    'Indendation can be a lot of things but not "%s"',
                    $indentation), E_USER_ERROR);
        }

        return $indent;
    }
    
    protected function _linesep($newlines = null) {
        if ($newlines === null && isset($this->_options['newlines'])) {
            $newlines = $this->_options['newlines'];
        }

        // Lot of ways to set line separators
        if (is_numeric($newlines)) {
            $linesep = str_repeat("\n", $newlines);
        } elseif (is_string($newlines)) {
            $linesep = $newlines;
        } elseif ($newlines === true || $newlines === null ) {
            $linesep = "\n";
        } elseif ($newlines === false) {
            $linesep = '';
        } else {
            trigger_error(sprintf(
                    'Newlines can be a lot of things but not "%s"',
                    $newlines), E_USER_ERROR);
        }
        
        return $linesep;
    }

    public function indent($lines, $indentation = null, $newlines = null) {
        // Setup Input
        if (is_string($lines)) {
            $lines = explode("\n", $lines);
        }
        if (!is_array($lines)) {
            // Neither string nor array
            // give this stuff back before accidents happen
            return $lines;
        }
        
        // Indent
        foreach ($lines as &$line) {
            $line = $this->_indent($indentation). $line;
        }

        // Newline
        return rtrim(join($this->_linesep($newlines), $lines));
    }
}
?>