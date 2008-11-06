<?php
/**
 * file_exists does not check the include paths. This function does.
 * It was not written by me, I don't know where it's from exactly.
 * Let me know if you do.
 *
 * @param string $file
 * 
 * @return boolean
 */
function fileExistsInPath($file){
    // Using explode on the include_path is three times faster than using fopen

    // no file requested?
    $file = trim($file);
    if (! $file) {
        return false;
    }
    
    // using an absolute path for the file?
    // dual check for Unix '/' and Windows '\',
    // or Windows drive letter and a ':'.
    $abs = ($file[0] == '/' || $file[0] == '\\' || $file[1] == ':');
    if ($abs && file_exists($file)) {
        return $file;
    }
    
    // using a relative path on the file
    $path = explode(PATH_SEPARATOR, ini_get('include_path'));
    foreach ($path as $base) {
        // strip Unix '/' and Windows '\'
        $target = rtrim($base, '\\/') . DIRECTORY_SEPARATOR . $file;
        if (file_exists($target)) {
            return $target;
        }
    }
    
    // never found it
    return false;   
}
?>