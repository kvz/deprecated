<?php
/**
 * MoviExplore scans a directory for movies and gathers IMDB information
 *
 * Requires imdbphp which is installable through apt:
 *  wget -O- http://apt.izzysoft.de/izzysoft.asc | apt-key add -
 *  deb http://apt.izzysoft.de/ubuntu generic universe
 *  aptitude -y update
 *  aptitude -y install imdbphp
 *
 * PHP version 5
 *
 * @package   MoviExplore
 * @author    Kevin van Zonneveld <kevin@vanzonneveld.net>
 * @copyright 2009 Kevin van Zonneveld (http://kevin.vanzonneveld.net)
 * @license   http://www.opensource.org/licenses/bsd-license.php New BSD Licence
 * @version   SVN: Release: $Id$
 * @link      http://kevin.vanzonneveld.net
 */

error_reporting(E_ALL);
if (!function_exists('pr')) {
    function pr($arr) {
        if (is_array($arr) && count($arr)) {
            print_r($arr);
        } else {
            var_dump($arr);
        }
        echo "\n";
    }
}
if (!function_exists('prd')) {
    function prd($arr) {
        pr($arr);
        die();
    }
}

if (!defined('DIR_ROOT')) {
    define('DIR_ROOT', dirname(__FILE__));
}

if (!defined('DIR_KVZLIB')) {
    $lookIn = array(
        '/Users/kevin/workspace/kvzlib',
        '/home/kevin/workspace/kvzlib',
        DIR_ROOT.'/ext/kvzlib',
    );

    foreach($lookIn as $dir) {
        if (is_dir($dir) && file_exists($dir.'/kvzlib.php')) {
            define('DIR_KVZLIB', $dir);
            break;
        }
    }

    if (!defined('DIR_KVZLIB')) {
        trigger_error('KvzLib not found in either: '.implode(', ', $lookIn), E_USER_ERROR);
    }
}

define('IMDBPHP_CONFIG',DIR_ROOT.'/config/imdb.php');

ini_set("include_path", DIR_KVZLIB.":".DIR_ROOT.":".ini_get("include_path"));

require_once DIR_KVZLIB.'/php/classes/KvzShell.php';
require_once DIR_KVZLIB.'/php/classes/KvzHTML.php';
require_once DIR_KVZLIB.'/php/all_functions.php';
require_once DIR_ROOT.'/libs/crawler.php';
require_once DIR_ROOT.'/libs/movie.php';
require_once DIR_ROOT.'/libs/store.php';
require_once 'imdb.class.php';

$outDir  = '/home/kevin/Dropbox/Public/cinema';
$outFile = 'kijken.html';

$crawlerOptions = array(
    'dir' => '/data/moviesHD',
    'minSize' => '600M',
    'cachedir' => DIR_ROOT.'/cache',
    'photodir' => $outDir.'/images',
);
$Crawler = new Crawler($crawlerOptions);
$movies  = $Crawler->crawl();

$Store = new Store($movies, 'html', array(
    'photovirt' => 'images',
    'outputdir' => $outDir,
    'outputfile' => $outFile,
    'separate_on_dir' => 1,
));
$Store->save();
#$Store->output();
