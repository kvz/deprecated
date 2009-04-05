<?php
Class Crawler extends KvzShell{

    public $_options = array(
        'dir' => false,
        'minSize' => false,
        'cachedir' => false,
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
        $cacheage = $this->getOption('cacheage');

        if (!is_dir($dir)) {
            throw new Crawler_Exception('Directory not found: '.$dir);
            return false;
        }

        $files = $this->exe(sprintf('find %s -size +%s', $dir, $minSize));

        $movies = array();
        foreach ($files as $file) {
            $relativeFile = substr($file, strlen($dir));
            $hash = strtolower(preg_replace('/[^a-z0-9\-\.\_]/i', '_', $relativeFile));
            $cacheFile = $cachedir.'/'.$hash.'.json';

            if (file_exists($cacheFile) && filemtime($cacheFile) > (time()-($cacheage))) {
                // Load cache
                $movies[$relativeFile] = json_decode(file_get_contents($cacheFile));
            } else {
                $Movie = new Movie($file);
                $details = $Movie->getDetails();
                $movies[$relativeFile] = $details;
                if (false !== $details) {
                    // Save cache
                    file_put_contents($cacheFile, json_encode($details));
                }
            }

            break;
        }

        return $movies;
    }
}

Class Crawler_Exception extends Exception {

}
?>
