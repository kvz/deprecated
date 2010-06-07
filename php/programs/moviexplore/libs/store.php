<?php
Class Store{

    public $_options = array(
        'outputdir' => false,
        'outputfile' => 'index.html',
        'photovirt' => false,
        'separate_on_dir' => 0,
    );

    protected $_movies = array();
    protected $_type = 'html';
    protected $_output = '';
    protected $_outputfile = '';

    public function __construct($movies, $type, $options = array()) {
        $this->_movies = $movies;
        $this->_type = $type;

        // Set given options
        $this->_options = $options;

        $this->_outputfile = $this->getOption('outputdir').'/'.$this->getOption('outputfile');

        $this->generate();
    }

    public function generate() {
        switch($this->_type) {
            case 'html':
                $Html = new KvzHTML();

                $photovirt = $this->getOption('photovirt');

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
                    if (empty($movie)) {
                        echo('Skipping '.$file.'. Invalid movie information'. "\n");
                        continue;
                    }

                    if (($separate_on_dir = $this->getOption('separate_on_dir'))) {
                        $parts = explode(DIRECTORY_SEPARATOR, $file);
                        $dirname = $parts[($separate_on_dir-1)];
                    }

                    $imgFile = Movie::imageFromFile($file, $photovirt);
                    if (!file_exists(realpath($this->getOption('outputdir').'/'.$imgFile))) {
                        $imgFile = 'title_noposter.gif';
                    }
                    if (!is_array($movie['cast'])) {
                        $movie['cast'] = array();
                    }
                    if (!is_array($movie['genres'])) {
                        $movie['genres'] = array();
                    }

                    // 4 --- 9
                    //     7
                    // 1 --- 6 -3
                    //

                    $rateColor = statusColor((6-($movie['rating']-3.5)).'/6');

                    $movie['cast'] = array_slice($movie['cast'], 0, 3);
                    $castar = array();

                    foreach($movie['cast'] as $actor) {
                        $castar[] = $Html->span($actor['name'], array('class' => 'actor'));
                    }
                    foreach($movie['director'] as $director) {
                        $castar[] = $Html->span($director['name'], array('class' => 'director'));
                    }
                    foreach($movie['writing'] as $writter) {
                        $castar[] = $Html->span($writter['name'], array('class' => 'writer'));
                    }

                    $cast = implode(', ', $castar);

                    if ($separate_on_dir) {
                        if ($prevdirname != $dirname) {
                            $index .= $Html->div(ucwords($dirname), array('class' => 'directory'));
                            $index .= $Html->hr(null);
                        }
                    }

                    $movie['tagline'] = strip_tags($movie['tagline']);
                    $movie['plotoutline'] = strip_tags($movie['plotoutline']);

                    $index .= $Html->div(
                        $Html->div(
                            $Html->a($movie['main_url'], $Html->img($imgFile, array('class' => 'poster'))) .
                            $Html->p($movie['rating'], array('class' => 'rating', 'style' => '"color:'.$rateColor.';"')) .
                            $Html->p($movie['runtime'] ? $movie['runtime'].'m' : '', 'runtime') ,
                            array('class' => 'left')
                        ). $Html->div(
                            $Html->h1($movie['title'], array('class' => 'title')) .
                            $Html->h2($movie['tagline'], array('class' => 'tagline')) .
                            $Html->p($movie['plotoutline'], array('class' => 'plotoutline')) .
                            $Html->p(implode(', ', $movie['genres']), array('class' => 'genres')) .
                            $Html->p($cast, array('class' => 'cast')),
                            array('class' => 'right')
                        ) . $Html->div('', array(
                            'class' => 'end'
                        )),
                        array('class' => 'movie')
                    );

                    if ($separate_on_dir) {
                        $prevdirname = $dirname;
                    }
                }

                $body .= $Html->div($index, array('class' => 'index'));


                $this->_output = $Html->html(
                    $Html->head($head) .
                    $Html->body($body)
                );

                break;
        }
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
        file_put_contents($this->_outputfile, $this->_output);
    }
    public function output() {
        echo $this->_output;
    }
}
?>
