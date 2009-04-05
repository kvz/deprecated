<?php
Class Crawler extends KvzShell{

    public $_options = array(
        'dir' => false,
        'minSize' => false,
        'cachedir' => false,
        'photodir' => false,
        'cacheage' => 2678400,
    );

    public function  __construct($options = array()) {
        // Merge parent's possible options with own
        $parent        = get_parent_class($this);
        $parentVars    = get_class_vars($parent);
        $parentOptions = $parentVars['_options'];
        $this->_options = arrayMerge($parentOptions, $this->_options);

        // Set given options
        $this->setOptions($options);
    }

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
            $cnt++;
            $relativeFile = substr($file, strlen($dir)+1);
            $hash         = strtolower(preg_replace('/[^a-z0-9\-\.\_]/i', '_', $relativeFile));
            $cacheFile    = $cachedir.'/'.$hash.'.json';
            $imgFile      = $photodir.'/'.basename($file).'.jpg';

            if (file_exists($cacheFile) && filemtime($cacheFile) > (time()-($cacheage))) {
                // Load cache
                $movies[$relativeFile] = json_decode(file_get_contents($cacheFile));
            } else {
                $Movie = new Movie($file);
                $details = $Movie->getDetails();


                $movies[$relativeFile] = $details;
                if (false !== $details) {
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

            if ($cnt > 5) {
                break;
            }
        }

        return $movies;
    }
}

Class Crawler_Exception extends Exception {

}
?>
