<?php
Class Store{

    public $_options = array(
        'outputdir' => false,
        'photovirt' => false,
    );

    protected $_movies = array();
    protected $_type = 'html';

    public function __construct($movies, $type, $options = array()) {
        $this->_movies = $movies;
        $this->_type = $type;

        // Set given options
        $this->_options = $options;
    }


    /**
     * Retrieves option
     *
     * @param string $optionName
     *
     * @return mixed
     */
    public function getOption($optionName) {
        if (!isset($this->_options[$optionName])) {
            $this->log("Option: ".$optionName." has not been initialized!", self::LOG_ERR);
            return null;
        }

        return $this->_options[$optionName];
    }

    public function save() {
        switch($this->_type) {
            case 'html':
                $Html = new Html();
                
                $photovirt = $this->getOption('photovirt');
                $outputdir = $this->getOption('outputdir');
                $outputfile = $outputdir.'/index.html';
                
                $head  = $Html->title('Movies').
                    $Html->css('moviexplore.css').
                    $Html->js('http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js').
                    $Html->js('moviexplore.js');
                    
                $body  = '';
                $index = '';
                $prevdirname = '';
                $dircontent = '';
                ksort($this->_movies);

                foreach ($this->_movies as $file=>$movie) {
                    $parts = explode(DIRECTORY_SEPARATOR, $file);
                    $dirname = array_shift($parts);
                    $imgFile = $photovirt.'/'.basename($file).'.jpg';
                    if (!file_exists(realpath($outputdir.'/'.$imgFile))) {
                        $imgFile = 'title_noposter.gif';
                    }
                    if (!is_array($movie['cast'])) {
                        $movie['cast'] = array();
                    }
                    if (!is_array($movie['genres'])) {
                        $movie['genres'] = array();
                    }

                    $movie['cast'] = array_slice($movie['cast'], 0, 3);
                    $castar = array();
                    foreach($movie['cast'] as $actor) {
                        $castar[] = $Html->span($actor['name'], 'actor');
                    }
                    $cast = implode(', ', $castar);

                    if ($prevdirname != $dirname) {
                        $index .= $Html->div(ucwords($dirname), 'directory');
                        $index .= $Html->hr();
                    }

                    $index .= $Html->div(
                        $Html->div(
                            $Html->a($movie['main_url'], $Html->img($imgFile, 'poster')) .
                            $Html->p($movie['rating'], 'rating') .
                            $Html->p($movie['runtime'].'m', 'runtime') ,
                            'left'
                        ). $Html->div(
                            $Html->h1($movie['title'], 'title') .
                            $Html->h2($movie['tagline'], 'tagline') .
                            $Html->p($file, 'file') .
                            $Html->p($movie['plotoutline'], 'plotoutline') .
                            $Html->p(implode(', ', $movie['genres']), 'genres') .
                            $Html->p($cast, 'cast'),
                            'right'
                        ) . $Html->div('', 'end'),
                        'movie'
                    );

                    $prevdirname = $dirname;
                }
                
                $body .= $Html->div($index, 'index');

                file_put_contents($outputfile, $Html->html(
                    $Html->head($head) .
                    $Html->body($body))
                );

                break;
        }
    }
}
?>
