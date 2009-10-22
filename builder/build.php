#!/usr/bin/php
<?php
require_once dirname(__FILE__).'/init.inc.php';

// Parse CLI options
$options = getArgs($argv);
#info(print_r($options, true));
// Add to config
$config = array_merge($config, $options);
#info(print_r($config, true));

extract($config);

// Password Hook
if (!empty($config['setpassword'])) {
    list($username, $password) = explode(':', $config['setpassword']);

    // Add developers as system users
    if (!userExists($username)) {
        err('User %s does not exist yet and cannot be created in this stage. Try again later',
            $username);
    } else {
        // Change password
        $ret = exe('echo "%s:%s" | /usr/sbin/chpasswd',
            escapeshellcmd($username),
            escapeshellcmd($password));

        // Bail out on fail
        if (false === ($ret)) exit(1);

        info('Changed password of user: %s', $username);
    }
}

// Create Developer homes
foreach($groups[$devGroup]['users'] as $i=>$username) {
    $user = $users[$username];

    // Add developers as system users
    if (!userExists($user['name'])) {
        if (false === userAdd($user['name'], $config['sysWebGroup'], null, null, $user['homedir'])) exit(1);
        info('Successfully created user %s belonging to group: %s',
            $user['name'],
            $config['sysWebGroup']);
    }

    // Go through repos
    foreach($repos as $reponame=>$repo) {
        @mkdir(projPath($repo, $user, 'etc'), 0755, true);
        @mkdir(projPath($repo, $user, 'Source'), 0755, true);
        @mkdir(projPath($repo, $user, 'log'), 0755, true);
        @mkdir(projPath($repo, $user, 'bin'), 0755, true);

        // Vhosts
        $file = projPath($repo, $user, 'etc/default');
        if (false === ($res = vhost($file, $user, $repo))) {
            err('Error while writing vhost: %s', $file);
            exit(1);
        } elseif (null === $res) {
            info('Skipped writing vhost: %s (force with --force)', $file);
        } else {
            info('Successfully written %s', $file);
        }

        // Bin scripts
        $reload = projPath($repo, $user, 'bin/reload.sh');
        file_put_contents($pull,
            'sudo /usr/sbin/apache2ctl configtest && sudo /usr/sbin/apache2ctl restart'. "\n"
        );
        chmod($reload, 0744);
        
        $pull = projPath($repo, $user, 'bin/pull.sh');
        file_put_contents($reload,
            'cd '.projPath($repo, $user, 'Source') . "\n" .
            'svn checkout --force https://dev-true.webclusive.true.nl/svn/'.$repo['name'].'/'."\n"
        );
        chmod($pull, 0744);

        // Pull in Repos
        
        # https://dev-true.webclusive.true.nl/svn/buurtleven/
        # /data/home/ronald/Projects/buurtleven/Source
        // Chown
        chownr($user['homedir'], $user['name'], $config['sysWebGroup']);
    }

    
}

// Empty http.conf
file_put_contents('/etc/apache2/httpd.conf', '');

// Add all found developer vhosts to apache
$p = $config['basehome'] .'/*/Projects/*/etc/' ;
$includes = array();
foreach (glob($p) as $f) {
    $includes[] = "Include ".$f;
}
file_put_contents('/etc/apache2/httpd.conf', join("\n", $includes), FILE_APPEND);




?>