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
    protected $_buffer = array();
    
    protected $_options = array();

    public $loremIpsum = 'Lorem ipsum dolor sit amet, consectetur adipisicing 
elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi
ut aliquip ex ea commodo consequat. Duis aute irure dolor in
reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla
pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa
qui officia deserunt mollit anim id est laborum';
    
    public function  __construct($options = array()) {
        $this->_options = $options;
        
        if (!isset($this->_options['xhtml'])) $this->_options['xhtml'] = true;
        if (!isset($this->_options['track_toc'])) $this->_options['track_toc'] = false;
        if (!isset($this->_options['link_toc'])) $this->_options['link_toc'] = true;
        if (!isset($this->_options['indentation'])) $this->_options['indentation'] = 4;
        if (!isset($this->_options['newlines'])) $this->_options['newlines'] = true;
        if (!isset($this->_options['buffer'])) $this->_options['buffer'] = false;
        if (!isset($this->_options['xml'])) $this->_options['xml'] = false;

        // Not recommended cause you cannot nest tags with echo:
        if (!isset($this->_options['echo'])) $this->_options['echo'] = false;
    }

    public function setOption($key, $val) {
        if (!array_key_exists($key, $this->_options)) {
            trigger_error(sprintf('%s is not a valid options',
                $key), E_USER_ERROR);
            return false;
        }
        
        $this->_options[$key] = $val;
    }

    protected function _trackToc($tag) {
        if (preg_match('/h(\d)/i', $tag, $m)) {
            $tocId     = count($this->_toc);
            $tocLevel  = $m[1];
            $tocLine   = '';
            $suffix    = '';
            $prefix    = '';

            if ($this->_tocIdPrev === false) {
                // root element
                $prefix .= $this->_tag('a', '', array(
                    'name' => 'toc_root',
                    '__trimbody' => true,
                    '__echo' => false,
                ));
            }

            if ($this->_tocIdPrev === false
                || $tocLevel > $this->_tocLevelPrev) {
                // Indent
                $prefix .= str_repeat(
                    str_repeat(' ', $tocLevel-1) .
                    '<ul>'.$this->_linesep(), ($tocLevel - $this->_tocLevelPrev)
                );
            } elseif ($tocLevel < $this->_tocLevelPrev) {
                // Outdent
                for($i = $this->_tocLevelPrev; $i >= $tocLevel; $i--) {
                    $prefix .= str_repeat(' ', $i).'</ul>'.$this->_linesep();
                }
            }

            $tocLine .= $prefix;
            $tocLine .= str_repeat(' ', $tocLevel). '<li>';
            $tocLine .= trim($body);
            if ($this->_options['link_toc']) {
                // Add Jump link to anchor
                $tocLine .= ' '.$this->_tag('a', '[jump]', array(
                    'href' => '#toc_'.$tocId,
                    '__trimbody' => true,
                    '__echo' => false,
                    '__newlineAfterClosingTag' => false,
                ));
                $bodySuffix .= $this->_tag('a', '', array(
                    'name' => 'toc_'.$tocId,
                    '__trimbody' => true,
                    '__echo' => false,
                    '__newlineAfterClosingTag' => false,
                ));
                $bodySuffix .= $this->_tag('a', '[toc]', array(
                    'href' => '#toc_'.'root',
                    '__trimbody' => true,
                    '__echo' => false,
                    '__newlineAfterClosingTag' => false,
                ));
            }
            $tocLine .= '</li>'.$this->_linesep();
            $tocLine .= $suffix;

            $this->_toc[$tocId]  = $tocLine;
            $this->_tocLevelPrev = $tocLevel;
            $this->_tocIdPrev    = $tocId;
        }
    }

    public function __call($tag, $arguments) {
        if (!array_key_exists(0, $arguments)) {
            return $this->_tag($tag);
        }
        $body       = array_shift($arguments);
        $args       = array_shift($arguments);
        $bodySuffix = '';

        // TOC?
        if ($this->_options['track_toc']) {
            $bodySuffix = $this->_trackToc($tag);
        }

        if (is_string($body)) {
            $body .= $bodySuffix;
        } 
        
        return $this->_tag($tag, $body, $args);
    }

    public function pr() {
        $args = func_get_args();
        echo '<pre>'."\n";
        foreach($args as $arg) {
            print_r($arg);
        }
        echo '</pre>'."\n";
    }
    public function prd() {
        $args = func_get_args();
        call_user_func_array(array($this, 'pr'), $args);
        die();
    }

    public function reset() {
        $this->_idCnt = array();
        $this->_ids = array();
        $this->_buffer = array();
        $this->_toc = array();
        $this->_tocLevelPrev = false;
        $this->_tocIdPrev = false;
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

    protected function _args($args = array()) {
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
        return $argumentsT;
    }

    protected function _tag($tag, $body = true, $options = array()) {
        if (!is_array($options)) $options = array();
        if (is_array($body)) {
            $body = implode("\n", $body);
        }

        // Set Class options if not present in Tag's args
        foreach($this->_options as $key => $val) {
            if (!array_key_exists('__' . $key, $options)) {
                $options['__' . $key] = $val;
            }
        }

        // Use a default for every Tag specific option that
        // remains
        if (!array_key_exists('__newlineAfterOpeningTag', $options))
            $options['__newlineAfterOpeningTag'] = true;
        if (!array_key_exists('__newlineAfterClosingTag', $options))
            $options['__newlineAfterClosingTag'] = true;
        if (!array_key_exists('__trimbody', $options)) $options['__trimbody'] = false;
        if (!array_key_exists('__gt', $options)) $options['__gt'] = '>';
        if (!array_key_exists('__lt', $options)) $options['__lt'] = '<';
        if (!array_key_exists('__skip', $options)) $options['__skip'] = false;
        if (!array_key_exists('__onlybody', $options)) $options['__onlybody'] = false;
        if (!array_key_exists('__cdata', $options)) $options['__cdata'] = false;
        if (!array_key_exists('__closetag', $options)) $options['__closetag'] = null;
        if (!array_key_exists('__opentag', $options)) $options['__opentag'] = null;
        if (!array_key_exists('__sclose', $options)) $options['__sclose'] = null;

        // Indent body
        $bodyIndented = $this->indent($body)."\n";
        
        // Other defaults for XML
        if ($options['__xml']) {
            if (strtolower($tag) === '?xml')  {
                $bodyIndented = ($body);
            }
            if (!$options['__trimbody'] && strpos($body, '<') === false) {
                $bodyIndented = trim($bodyIndented);
                if ($body !== true) {
                    $options['__newlineAfterOpeningTag'] = false;
                }
            }
        }

        if ($options['__trimbody']) {
            $bodyIndented = trim($bodyIndented);
            $options['__newlineAfterOpeningTag'] = false;
        }

        if (true === @$options['id']) {
            // auto id
            $options['id'] = $this->_createId($tag);
        }

        if ($options['__skip']) {
            return '';
        }

        if ($options['__cdata'] && !is_bool($body)  && !is_null($body)) {
            $bodyIndented = '<![CDATA[' . $bodyIndented . ']]>';
        }
        
        if ($options['__onlybody']) {
            return $bodyIndented;
        }

        $tagArguments = $this->_args($options);

        if ($options['__opentag'] === null) {
            $options['__opentag'] = $options['__lt'] .
                '%s' .
                $tagArguments .
                $options['__gt'] .
                $this->_linesep($options['__newlineAfterOpeningTag']);
        }

        if ($options['__closetag'] === null) {
            $options['__closetag']  = $options['__lt'] .
                '/%s' .
                $options['__gt'] .
                $this->_linesep($options['__newlineAfterClosingTag']);
        }

        if ($options['__sclose'] === null) {
            $options['__sclose'] = $options['__lt'] .
                '%s' .
                $tagArguments . ' ' .
                ($options['__xhtml'] ? '/' : '') . $options['__gt'] .
                $this->_linesep($options['__newlineAfterClosingTag'] || $options['__newlineAfterOpeningTag']);
        }

        $options['__opentag']  = sprintf($options['__opentag'], $tag);
        $options['__closetag'] = sprintf($options['__closetag'], $tag);
        $options['__sclose']   = sprintf($options['__sclose'], $tag);
        
        if (true === $body) {
            // Opening tag
            $result = $options['__opentag'];
        } else if (false === $body) {
            // Close tag
            $result = $options['__closetag'];
        } else if (null === $body) {
            // Self-closing tag
            $result = $options['__sclose'];
        } else {
            // Full tag
            $result = $options['__opentag'] . 
                $bodyIndented .
                $options['__closetag'];
        }

        if ($options['__echo']) {
            if ($options['__buffer']) {
                $this->_buffer[] = $result;
            } else {
                echo $result;
            }
            return true;
        } else {
            return $result;
        }
    }

    /**
     * Taken from CakePHP's Set Class
     * array_merge & $this->_merge is just never what you need
     *
     * @param <type> $arr1
     * @param <type> $arr2
     * @return <type>
     */
    protected function _merge($arr1, $arr2 = null) {
		$args = func_get_args();
		$r = (array)current($args);
		while (($arg = next($args)) !== false) {
			foreach ((array)$arg as $key => $val)	 {
				if (is_array($val) && isset($r[$key]) && is_array($r[$key])) {
					$r[$key] = $this->_merge($r[$key], $val);
				} elseif (is_int($key)) {
					$r[] = $val;
				} else {
					$r[$key] = $val;
				}
			}
		}
		return $r;
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
        return $this->_tag('div', $body, $this->_merge(array(
            'style' => array(
                'clear' => 'both',
            ),
        ), $args));
    }
    public function page($body = true, $args = array()) {
        return $this->_tag('div', $body, $this->_merge(array(
            'class' => array(
                'page'
            ),
        ), $args));
    }
    public function xml($body = true, $args = array()) {
        return $this->_tag('?xml', $body, $this->_merge(array(
            'version' => '1.0',
            'encoding' => 'UTF-8',
            '__gt' => '?>',
            '__closetag' => '',
        ), $args));
    }

    public function float($body = true, $args = array()) {
        return $this->_tag('div', $body, $this->_merge(array(
            'style' => array(
                'float' => 'left',
            ),
        ), $args));
    }

    public function img($link, $args = array()) {
        $args = $this->_merge(array('src' => $link), $args);
        return $this->_tag('img', null, $args);
    }

    public function getToc() {
        $toc = $this->_toc;

        $t = &$toc[];

        for($i = $this->_tocLevelPrev; $i >= 0; $i--) {
            $t .= str_repeat(' ', $i).'</ul>'.$this->_linesep();
        }

        #$toc[] = str_repeat('</ul>'. $this->_linesep(), $this->_tocLevelPrev);
        return join('', $toc);
    }

    public function getBuffer($clear = true) {
        $r = join('', $this->_buffer);
        if ($clear) {
            $this->reset();
        }
        return $r;
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