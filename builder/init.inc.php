<?php
/*
 * Runtime settings
 */
error_reporting(E_ALL);

/*
 * Config
 */
if (!isset($skipConf)) {
    $config = indexConfig(array(
        'authzFile' => '/data/submin/authz',
        'userFile' => '/data/submin/userproperties.conf',
        'ipFile' => '/etc/submin/ipmap',
        'vhostTemplate' => '/etc/submin/template.vhost',
        'devGroup' => 'developers',
        'sysWebGroup' => 'www-data',
        'maindomain' => 'webclusive.com',
        'baseip' => '87.233.31.10',
        'baseport' => '8000',
        'basehome' => '/data/home',
        'logFile' => '/var/log/submin.log',
        'buildFile' => '/etc/submin/build.php',
        'reloadFile' => '/etc/submin/reload.php',
    ));
}

/**
 * Report an error
 *
 * @param <type> $format
 * @param <type> $args
 * 
 * @return <type>
 */
function err($format, $args = array()) {
    global $config;
    $args = func_get_args();
    $format  = array_shift($args);
    if (count($args)) {
        $format = vsprintf($format, $args);
    }
    file_put_contents($config['logFile'], $format."\n", FILE_APPEND);
    trigger_error($format, E_USER_WARNING);
    return false;
}


/**
 * Report success
 *
 * @param <type> $format
 * @param <type> $args
 *
 * @return <type>
 */
function info($format, $args = array()) {
    global $config;
    $args = func_get_args();
    $format  = array_shift($args);
    if (count($args)) {
        $format = vsprintf($format, $args);
    }
    echo $format."\n";
    file_put_contents($config['logFile'], $format."\n", FILE_APPEND);
    return true;
}

/**
 * Execute a shell command
 *
 * @param <type> $cmd
 * @param <type> $args
 *
 * @return <type>
 */
function exe($cmd, $args = array()) {
    $args = func_get_args();
    $cmd  = array_shift($args);
    if (count($args)) {
        $cmd = vsprintf($cmd, $args);
    }

    $output = array();
    $error  = '';
    $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin
        1 => array("pipe", "w"),  // stdout
        2 => array("pipe", "w"),
    );
    $process = proc_open($cmd, $descriptorspec, $pipes);
    if (is_resource($process)) {
        while ($lastline = fgets($pipes[1], 1024)) {
            $output[] = $lastline;
        }
        fclose($pipes[1]);
        while ($lastline = fgets($pipes[2], 1024)) {
            $error .= $lastline . "\n";
        }
        fclose($pipes[2]);
    }

    $return_var = proc_close($process);
    $output     = join("\n", $output);

    if ($return_var !== 0 && $return_var !== 255) {
        return err('Command: %s failed. %s', $cmd, $error);
    }

    return $output;
}

/**
 * print_r shortcut
 *
 */
function pr() {
    $args = func_get_args();

    if (php_sapi_name() !=='cli') {
        echo '<pre>'."\n";
    }
    foreach($args as $arg) {
        print_r($arg);
    }
    if (php_sapi_name() !=='cli') {
        echo '</pre>'."\n";
    }
}

/**
 * print_r & die shortcut
 *
 */
function prd() {
    $args = func_get_args();
    call_user_func_array('pr', $args);
    die();
}

/**
 * Parse commandline arguments, return options array
 *
 * @param <type> $argv
 *
 * @return <type>
 */
function getArgs($argv) {
    $arguments = array();
    for($i = 1; $i < count($argv); $i++) {
        if (substr($argv[$i], 0, 2) === '--') {
            if (!isset($argv[($i+1)]) || substr($argv[($i+1)], 0, 1) === '-') {
                $arguments[substr($argv[$i], 2)] = true;
            } else {
                $arguments[substr($argv[$i], 2)] = $argv[($i+1)];
                $i++;
            }
        } elseif (substr($argv[$i], 0, 1) === '-') {
            if (!isset($argv[($i+1)]) || substr($argv[($i+1)], 0, 1) === '-') {
                $arguments[substr($argv[$i], 1)] = true;
            } else {
                $arguments[substr($argv[$i], 1)] = $argv[($i+1)];
                $i++;
            }
        }
    }
    return $arguments;
}

/**
 * Checks if system user exists
 *
 * @param <type> $filter
 * @param <type> $field
 * 
 * @return mixed array or boolean on failure
 */
function userExists($filter, $field = 'user') {
    $lines = file('/etc/passwd', FILE_IGNORE_NEW_LINES ^ FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        list($user, $shadow, $uid, $gid, $fullname, $home, $shell) = $parts;

        $com      = explode(',', $fullname);
        $fullname = array_shift($com);

        if ($filter === ${$field}) {
            return compact('user', 'shadow', 'uid', 'gid', 'fullname', 'home', 'shell');
        }
    }

    return false;
}

/**
 * Adds a system user
 *
 * @param <type> $user
 * @param <type> $group
 * @param <type> $uid
 * @param <type> $fullname
 * @param <type> $home
 * @param <type> $shell
 *
 * @return <type>
 */
function userAdd($user, $group = null, $uid = null, $fullname = null, $home = null, $shell = '/bin/bash') {
    $cmd  = '/usr/sbin/useradd';
    $cmd .= ' '. $user;

    if ($uid) $cmd .= ' --uid '.escapeshellarg($uid);
    if ($group) {
        if (!groupExists($group)) {
            err('Group: %s does not exit yet', $group);
            return false;
        }

        $cmd .= ' -N --gid '.escapeshellarg($group);
    }
    if ($fullname) $cmd .= ' --comment '.escapeshellarg($fullname);
    if ($home) $cmd .= ' --create-home --home-dir '.escapeshellarg($home);
    if ($shell) $cmd .= ' --shell '.escapeshellarg($shell);

    return exe($cmd);
}

/**
 * Checks if a system group exists
 *
 * @param <type> $filter
 * @param <type> $field
 *
 * @return <type>
 */
function groupExists($filter, $field = 'group') {
    $lines = file('/etc/group', FILE_IGNORE_NEW_LINES ^ FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode(':', $line);
        list($group, $shadow, $gid) = $parts;

        if ($filter === ${$field}) {
            return compact('group', 'shadow', 'gid');
        }
    }

    return false;
}

/**
 * Adds a system group
 *
 * @param <type> $group
 * @param <type> $gid
 *
 * @return <type>
 */
function groupAdd($group, $gid = null) {
    $cmd  = '/usr/sbin/groupadd';
    $cmd .= ' '. $group;
    
    if ($gid) $cmd .= ' --gid '.escapeshellarg($gid);

    return exe($cmd);
}

/**
 * Recursive chown & chgrp
 *
 * @param <type> $pathname
 * @param <type> $user
 * @param <type> $group    (optional)
 *
 * @return boolean
 */
function chownr($pathname, $user, $group = null) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathname), RecursiveIteratorIterator::SELF_FIRST);

    foreach($iterator as $filename) {
        if (false === chwn($filename, $user, $group)) {
            return false;
        }
    }

    return true;
}

function chwn($filename, $user, $group = null) {
    if (false === chown($filename, $user)) {
        return err('Unable to chown(%s) %s', $user, $filename);
    }

    if ($group !== null) {
        if (false === chgrp($filename, $group)) {
            return err('Unable to chrp(%s) %s', $group, $filename);
        }
    }

    return true;
}
























/**
 * Readz authz file and returns users, groups, repos
 *
 * @param <type> $config
 * @return <type>
 */
function indexConfig($config = array()) {
    $config['repos'] = array();
    $authSections    = parse_ini_file($config['authzFile'], true);
    $userSections    = parse_ini_file($config['userFile'], true);
    $repoCnt         = 0;
    foreach ($authSections as $authSection=>$authKeys) {
        if (substr($authSection, -2) === ':/') {
            $repo = substr($authSection, 0, -2);
            // repos
            $config['repos'][$repo] = array (
                'name' => $repo,
                'repo' => $authKeys,
                'ip' => claimIPPort($config, 'repo', $repo),
            );
        } elseif($authSection === 'groups') {
            // groups
            $config['groups'] = $authKeys;
            $config['users']  = array();
            foreach ($config['groups'] as $group => $userlist) {
                $groupusers = explode(', ', $userlist);
                $config['groups'][$group] = array(
                    'users' => $groupusers,
                    'name' => $group,
                );
                foreach($groupusers as $groupuser) {
                    $config['users'][$groupuser]['groups'][] = $group;
                }
            }

            // users
            foreach($config['users'] as $username => &$user) {
                $user['name'] = $username;
                $user['homedir'] = $config['basehome'].'/'.$username;
                $user['fullname'] = $userSections[$username]['fullname'];
                $user['email'] = $userSections[$username]['email'];
                $user['port'] = claimIPPort($config, 'user', $username);
            }

        } else {
            // unknown
        }
    }

    return $config;
}

function claimIPPort($config, $type, $name) {
    $ipArr = (array)@json_decode(@file_get_contents($config['ipFile']), true);

    if (!empty($ipArr[$type][$name])) {
        return $ipArr[$type][$name];
    }
    if (empty($ipArr[$type])) {
        $ipArr[$type] = array();
    }

    $cnt = count($ipArr[$type]) + 1;

    if ($type === 'user') {
        $val = $config['baseport'] + $cnt;
    } elseif ($type === 'repo') {
        $val = long2ip(ip2long($config['baseip']) + $cnt);
    } else {
        return false;
    }

    $ipArr[$type][$name] = $val;

    pr(compact('ipArr'));

    return file_put_contents($config['ipFile'], json_encode($ipArr));
}

function projPath($repo, $user, $type) {
    if ($type === 'w') {
        return 'w';
    }

    $path = $user['homedir'].'/Projects/'.$repo['name'].'/'.$type;
    return $path;
}

function vhost($file, $user, $repo) {
    global $config;

    if (file_exists($file) && empty($config['force'])) {
        return null;
    }

    $docroot = projPath($repo, $user, 'www');
    $source  = projPath($repo, $user, 'Source');
    $access  = projPath($repo, $user, 'log/access.log');
    $error   = projPath($repo, $user, 'log/error.log');

    if (!($template = file_get_contents($config['vhostTemplate']))) {
        return false;
    }
    
    $vars = array(
        '${email}' => $user['email'],
        '${docroot}' => $docroot,
        '${home}' => $user['homedir'],
        '${domain}' => $repo['name'].'.'.$config['maindomain'],
        '${ip}' => $repo['ip'],
        '${port}' => $user['port'],
        '${source}' => $source,
        '${repo}' => $repo['name'],
        '${user}' => $user['name'],
        '${access_log}' => $access,
        '${error_log}' => $error,
    );

    $template = str_replace(array_keys($vars), array_values($vars), $template);

    if (!file_put_contents($file, $template)) {
        return false;
    }

    if (!file_exists($docroot)) {
        symlink($source, $docroot);
    }

    // touch log
    touch($access);
    chgrp($access, $config['sysWebGroup']);
    chmod($access, 0664);
    
    touch($error);
    chgrp($error, $config['sysWebGroup']);
    chmod($error, 0664);

    return true;
}



?>