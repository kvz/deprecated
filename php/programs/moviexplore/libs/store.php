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
                $Html = new Html();

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
                        trigger_error('Skipping '.$file.'. Invalid movie information', E_USER_NOTICE);
                        continue;
                    }

                    if ($separate_on_dir = $this->getOption('separate_on_dir')) {
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
                        $castar[] = $Html->span($actor['name'], 'actor');
                    }
                    $cast = implode(', ', $castar);

                    if ($separate_on_dir) {
                        if ($prevdirname != $dirname) {
                            $index .= $Html->div(ucwords($dirname), 'directory');
                            $index .= $Html->hr();
                        }
                    }

                    $index .= $Html->div(
                        $Html->div(
                            $Html->a($movie['main_url'], $Html->img($imgFile, 'poster')) .
                            $Html->p($movie['rating'], 'rating', 'style="color:'.$rateColor.';"') .
                            $Html->p($movie['runtime'] ? $movie['runtime'].'m' : '', 'runtime') ,
                            'left'
                        ). $Html->div(
                            $Html->h1($movie['title'], 'title') .
                            $Html->h2($movie['tagline'], 'tagline') .
                            $Html->p($file.' --&gt;&gt; '.$movie['cleanedName'], 'file') .
                            $Html->p($movie['plotoutline'], 'plotoutline') .
                            $Html->p(implode(', ', $movie['genres']), 'genres') .
                            $Html->p($cast, 'cast'),
                            'right'
                        ) . $Html->div('', 'end'),
                        'movie'
                    );

                    if ($separate_on_dir) {
                        $prevdirname = $dirname;
                    }
                }

                $body .= $Html->div($index, 'index');


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