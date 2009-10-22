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

$index = array();
$tcp = array();

// Create Developer homes
foreach($groups[$devGroup]['users'] as $i=>$username) {
    $user = &$users[$username];

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
        $script_pull = projPath($repo, $user, 'bin/pull.sh');
        file_put_contents($script_pull,
            'cd '.projPath($repo, $user, 'Source') . "\n" .
            'svn checkout --force '.sprintf($config['svnUrl'], $repo['name']).'/'."\n"
        );
        chmod($script_pull, 0744);
        
        $script_reload = projPath($repo, $user, 'bin/reload.sh');
        file_put_contents($script_reload,
            'sudo /usr/sbin/apache2ctl configtest && sudo /usr/sbin/apache2ctl restart'. "\n"
        );
        chmod($script_reload, 0744);

        // Pull in Repos ?
        

        // Chown Home
        chownr($user['homedir'], $user['name'], $config['sysWebGroup']);

        // Save TCP information for Apache Listen instructions
        $tcp[$repo['ip']][80] = true;
        $tcp[$repo['ip']][$user['port']] = true;
    }
}

// http.conf
file_put_contents('/etc/apache2/httpd.conf', '');

// Open ports
foreach($tcp as $ip=>$p) {
    foreach($p as $port=>$bool) {
        file_put_contents('/etc/apache2/httpd.conf', "Listen ".$ip.":".$port."\n", FILE_APPEND);
    }
}

// Include all found developer vhosts to apache
// (even custom created ones)
$p = $config['basehome'] .'/*/Projects/*/etc/' ;
$includes = array();
foreach (glob($p) as $f) {
    $includes[] = "Include ".$f;
}
file_put_contents('/etc/apache2/httpd.conf', join("\n", $includes)."\n", FILE_APPEND);

// Index HTML
$indexH  = '<html>';
$indexH .= '<style>';
$indexH .= 'h1, .i1{padding-left: 0px;}';
$indexH .= 'h2, .i2{padding-left: 20px;}';
$indexH .= 'h3, .i3{padding-left: 40px;}';
$indexH .= 'h4, .i4{padding-left: 60px;}';
$indexH .= '</style>';
$indexH .= '<h1>Links</h1>';
$indexH .= '<ul>';
    $indexH .= '<li>';
        $indexH .= '<a href="https://'.$config['devDomain'].'/submin/login">https://'.$config['devDomain'].'/submin/login</a> (admin can manage repositories &amp; users)';
    $indexH .= '</li>';
    $indexH .= '<li>';
        $indexH .= '<a href="https://www.truecare.nl">https://www.truecare.nl</a> (manage DNS records, create tickets, etc)';
    $indexH .= '</li>';
$indexH .= '</ul>';
$indexH .= '<h1>Project Index</h1>';
$indexH .= '<p>These are the IPs & ports so it will Always work.
But you are free to attach any domain name or rewrite to these
addresses..</p>';

foreach($repos as $reponame=>$repo) {
    $indexH .= '<h2>'.$reponame.'</h2>';
    $indexH .= htmUserBlock($users['admin'], $repo);
    foreach($groups[$devGroup]['users'] as $i=>$username) {
        if ($username === 'admin') {
            continue;
        }
        $indexH .= htmUserBlock($users[$username], $repo);
    }
}

file_put_contents('/var/www/index.html', $indexH);
info('Written %s', '/var/www/index.html');
?>