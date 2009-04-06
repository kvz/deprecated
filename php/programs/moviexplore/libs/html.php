<?php
Class Html {


    public function __call($tag, $arguments) {

        $body       = array_shift($arguments);
        $class      = array_shift($arguments);
        $argumentsT = implode(' ', $arguments);
        
        return '<'.$tag.' class="'.$class.'" '.$argumentsT.'>'."\n".$this->indent($body)."\n".'</'.$tag.'>'."\n";
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
