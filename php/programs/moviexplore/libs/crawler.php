<?php
Class Crawler extends KvzShell {

    public $_options = array(
        'dir' => false,
        'minSize' => false,
        'cachedir' => false,
        'photodir' => false,
        'cacheage' => 2678400,
    );

    public function crawl() {
        $dir      = $this->getOption('dir');
        $minSize  = $this->getOption('minSize');
        $cachedir = $this->getOption('cachedir');
        $photodir = $this->getOption('photodir');
        $cacheage = $this->getOption('cacheage');

        if (!is_dir($dir)) {
            throw new Crawler_Exception('Directory not found: '.$dir);
            return false;
        }

        $files = $this->exe(sprintf('find %s -size +%s', $dir, $minSize));

        $movies = array();
        $cnt    = 0;
        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'vob') {
                $file = dirname($file);
                //trigger_error('Think this is a DVD. Using: '. $file, E_USER_NOTICE);
                if (basename($file) == 'VIDEO_TS') {
                    $file = dirname($file);
                    //trigger_error('Think this is a DVD. Using: '. $file, E_USER_NOTICE);
                }
            }

            $cnt++;
            $relativeFile = substr($file, strlen($dir)+1);
            $slug         = Movie::fileslug($file);
            $cacheFile    = $cachedir.'/'.$slug. '.json';
            #$imgFile      = $photodir.'/'.$slug.'.jpg';
            $imgFile      = Movie::imageFromFile($file, $photodir);

            if (file_exists($cacheFile) && filemtime($cacheFile) > (time()-($cacheage))) {
                // Load cache
                $movies[$relativeFile] = json_decode(file_get_contents($cacheFile), true);
            } else {
                $Movie = new Movie($file);
                $details = $Movie->getDetails();
                if (false === $details) {

                    echo('No movie info found for: '.$Movie->cleanedName.', '. $file . "\n");
                } else {
                    // Use this
                    $movies[$relativeFile] = $details;
                    
                    // Save photo
                    if (!file_exists($imgFile) && !empty($details['photo'])) {
                        if (false === $this->wget($details['photo'], $imgFile)) {
                            trigger_error('wget error', E_USER_ERROR);
                            return false;
                        }
                    }
                    
                    // Save cache
                    file_put_contents($cacheFile, json_encode($details));
                }
            }

//            if ($cnt > 5) {
//                break;
//            }
        }

        return $movies;
    }
}

Class Crawler_Exception extends Exception {

}
