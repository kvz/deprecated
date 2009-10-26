<?php
/**
 * Description of Builder
 *
 * @author kvz
 */
class Builder extends EggShell{
    protected $_config = array();

    public function  __construct($options = array()) {
        // Take care of recursion when there are ++++ levels of inheritance
        parent::__construct($options);
        // Get direct parent defined options
        $parentVars    = @get_class_vars(@get_parent_class(__CLASS__));
        // Override with own defined options
        $this->_config = $this->merge((array)@$parentVars['_options'], $this->_config);
        // Override with own instance options
        $this->_config = $this->merge($this->_config, $options);
    }

    public function getCfg($name) {
        if (!array_key_exists($name, $this->_config)) {
            return $this->debug('Config parameter %s does not exist in current config',
                $name);
        }
        return $this->_config[$name];
    }

    public function setPassword($username, $password) {
        // Add developers as system users
        if (!$this->userExists($username)) {
            return $this->err('User %s does not exist yet. Cant change password now',
                $username);
        }
        
        // Change password
        if (false === $this->userPasswd($username, $password, array(
            'samba' => true,
        )))  {
            return false;
        }
        
        $this->info('Changed password of user: %s', $username);
        
        return true;
    }

    public function createHomes() {
        // Create Developer homes
        foreach($this->_developers as $user) {
            // Add developers as system users
            if (!$this->userExists($user['name'])) {
                if (false === $this->userAdd($user['name'], $this->_config['sysWebGroup'], null, null, $user['homedir'])) exit(1);
                $this->info('Created user %s belonging to group: %s',
                    $user['name'],
                    $this->_config['sysWebGroup']);
            }

            // Go through repos
            foreach($this->_repos as $reponame=>$repo) {
                $this->mkdirOnce($this->projPath($repo, $user, 'etc'), 0755, true);
                $this->mkdirOnce($this->projPath($repo, $user, 'Source'), 0755, true);
                $this->mkdirOnce($this->projPath($repo, $user, 'log'), 0755, true);
                $this->mkdirOnce($this->projPath($repo, $user, 'bin'), 0755, true);

                // Vhosts
                $file = $this->projPath($repo, $user, 'etc/default');
                $res  = $this->createVHost($file, $user, $repo);
                if (false === ($res)) {
                    $this->err('Error while writing vhost: %s', $file);
                    exit(1);
                } elseif (null === $res) {
                    $this->info('Skipped writing vhost: %s (force with --force)', $file);
                } else {
                    $this->info('Written %s', $file);
                }

                // Bin scripts
                $script_pull = $this->projPath($repo, $user, 'bin/pull.sh');
                $this->write($script_pull, 
                    'cd '.$this->projPath($repo, $user, 'Source') . "\n" .
                    'svn checkout --force '.sprintf($this->_config['svnUrl'], $repo['name']).'/'."\n"
                );
                $this->chmod($script_pull, 0744);

                $script_reload = $this->projPath($repo, $user, 'bin/reload.sh');
                $this->write($script_reload,
                    'sudo /usr/sbin/apache2ctl configtest && sudo /usr/sbin/apache2ctl restart'. "\n"
                );
                $this->chmod($script_reload, 0744);

                // Pull in Repos ?


                // Chown Home
                $this->chownr($user['homedir'], $user['name'], $this->_config['sysWebGroup']);
            }
        }

        return true;
    }

    public function createApacheConf() {
        // Save TCP information for Apache Listen instructions
        foreach($this->_developers as $username=>$user) {
            foreach($this->_repos as $reponame=>$repo) {
                $this->appendOnce('/etc/apache2/httpd.conf', "Listen ".$repo['ip'].":".$user['port']."\n");
            }
        }

        // Include all found developer vhosts to apache
        // (even custom created ones)
        $vpaths = $this->_config['basehome'] .'/*/Projects/*/etc/' ;
        foreach (glob($vpaths) as $file) {
            $this->appendOnce('/etc/apache2/httpd.conf', "Include ".$file."\n");
        }

        return true;
    }

    public function createIndexHtml() {
        $html = $this->htmIndex();
        $this->write('/var/www/index.html', $html);
        $this->info('Written %s', '/var/www/index.html');
        return true;
    }

    public function htmIndex() {
        // Index HTML
        $html  = '<html>';
        $html .= '<style>';
        $html .= 'h1, .i1{padding-left: 0px;}';
        $html .= 'h2, .i2{padding-left: 20px;}';
        $html .= 'h3, .i3{padding-left: 40px;}';
        $html .= 'h4, .i4{padding-left: 60px;}';
        $html .= '</style>';
        $html .= '<h1>Links</h1>';
        $html .= '<ul>';
            $html .= '<li>';
                $html .= '<a href="https://'.$this->_config['devDomain'].'/submin/login">https://'.$this->_config['devDomain'].'/submin/login</a> (admin can manage repositories &amp; users)';
            $html .= '</li>';
            $html .= '<li>';
                $html .= '<a href="https://www.truecare.nl">https://www.truecare.nl</a> (manage DNS records, create tickets, etc)';
            $html .= '</li>';
        $html .= '</ul>';
        $html .= '<h1>Project Index</h1>';
        $html .= '<p>These are the IPs & ports so it will Always work.
        But you are free to attach any domain name or rewrite to these
        addresses..</p>';

        foreach($this->_repos as $reponame=>$repo) {
            $html .= '<h2>';
            $html .= $reponame;
            $html .= ' (';
            $html .= (ip2long($repo['ip']) - ip2long($this->_config['baseip']));
            $html .= ')';
            $html .= '</h2>';
            $html .= $this->htmUserBlock($this->_developers['admin'], $repo);
            foreach($this->_developers as $username=>$user) {
                if ($username === 'admin') {
                    continue;
                }
                $html .= $this->htmUserBlock($user, $repo);
            }
        }
        
        return $html;
    }

    public function htmUserBlock($user, $repo) {
        $indent = 4;

        $html  = '';
        $urls = array(
            'svn' => sprintf($this->_config['svnUrl'], $repo['name']),
            'web' => sprintf($this->_config['webUrl'], $repo['ip'], $user['port']),
            '-',
            'ftp' => sprintf('ftp://%s@%s', $user['name'], $repo['ip']),
            'sftp' => sprintf('ssh://%s@%s', $user['name'], $repo['ip']),
            '-',
            'Source' => $this->projPath($repo, $user, 'Source'),
            'www' => 'Create a symlink from any directory in Source to '.$this->projPath($repo, $user, 'www'),
            'log' => $this->projPath($repo, $user, 'log'),
            'etc' => $this->projPath($repo, $user, 'etc'),
            'bin' => $this->projPath($repo, $user, 'bin'),
        );

        if ($user['name'] === 'admin') {
            $indent = 3;
        } else {
            $html .= '<h3>';
            $html .= $user['name'];
            $html .= ' (';
            $html .= $user['port'] - $this->_config['baseport'];
            $html .= ')';
            $html .= '</h3>';
        }

        $html .= '<pre class="i'.$indent.'">';
            foreach  ($urls as $key=>$url) {
                if ($url === '-') {
                    $html .= ''."\n";
                    continue;
                }
                $html .= ''.str_pad($key.':', 7, ' ', STR_PAD_RIGHT).' ';
                if ($key === 'web') {
                    $html .= '<a href="'.$url.'">'.$url.'</a>';
                } else {
                    $html .= $url;
                }
                $html .= "\n";
            }
        $html .= '</pre>';

        return $html;
    }

    /**
     * Readz authz file and returns users, groups, repos
     *
     * @param <type> $cfgFile
     * @return <type>
     */
    public function indexConfig($cfgFile, $overruleConfig) {
        $this->_config = require($cfgFile);
        $this->_config = $this->merge($this->_config, $overruleConfig);

        $this->_config['repos'] = array();
        $authSections    = parse_ini_file($this->_config['authzFile'], true);
        $userSections    = parse_ini_file($this->_config['userFile'], true);
        $repoCnt         = 0;
        foreach ($authSections as $authSection=>$authKeys) {
            if (substr($authSection, -2) === ':/') {
                $repo = substr($authSection, 0, -2);
                // repos
                $this->_config['repos'][$repo] = array (
                    'name' => $repo,
                    'repo' => $authKeys,
                    'ip' => $this->claimIPPort('repo', $repo),
                );
            } elseif($authSection === 'groups') {
                // groups
                $this->_config['groups'] = $authKeys;
                $this->_config['users']  = array();
                foreach ($this->_config['groups'] as $group => $userlist) {
                    $groupusers = explode(', ', $userlist);
                    $this->_config['groups'][$group] = array(
                        'users' => $groupusers,
                        'name' => $group,
                    );
                    asort($this->_config['groups'][$group]['users']);
                    foreach($groupusers as $groupuser) {
                        $this->_config['users'][$groupuser]['groups'][] = $group;
                    }
                }

                // users
                foreach($this->_config['users'] as $username => &$user) {
                    $user['name'] = $username;
                    $user['homedir'] = $this->_config['basehome'].'/'.$username;
                    $user['fullname'] = $userSections[$username]['fullname'];
                    $user['email'] = $userSections[$username]['email'];
                    $user['port'] = $this->claimIPPort('user', $username);
                }
                ksort($this->_config['users']);
                ksort($this->_config['groups']);

            } else {
                $this->notice('Unknow config section: %s', $authSection);
            }
        }

        $this->_users  = $this->_config['users'];
        $this->_groups = $this->_config['groups'];
        $this->_repos  = $this->_config['repos'];

        $d = $this->_groups[$this->_config['devGroup']]['users'];
        $this->_developers = array();
        foreach($d as $i=>$username) {
            $this->_developers[$username] = $this->_users[$username];
        }

        return true;
    }

    public function claimIPPort($type, $name) {
        $ipArr = (array)@json_decode(@$this->read($this->_config['ipFile']), true);

        if ($type === 'user' && $name === 'admin') {
            return 80;
        }

        if (!empty($ipArr[$type][$name])) {
            return $ipArr[$type][$name];
        }
        if (empty($ipArr[$type])) {
            $ipArr[$type] = array();
        }

        $cnt = count($ipArr[$type]) + 1;

        if ($type === 'user') {
            $val = $this->_config['baseport'] + $cnt;
        } elseif ($type === 'repo') {
            $val = long2ip(ip2long($this->_config['baseip']) + $cnt);
        } else {
            return false;
        }

        $ipArr[$type][$name] = $val;

        return $this->write($this->_config['ipFile'], json_encode($ipArr));
    }

    public function projPath($repo, $user, $type) {
        if ($type === 'w') {
            return 'w';
        }

        $path = $user['homedir'].'/Projects/'.$repo['name'].'/'.$type;
        return $path;
    }

    public function createVHost($file, $user, $repo) {
        global $config;

        if (file_exists($file) && empty($config['force'])) {
            return null;
        }

        $docroot = $this->projPath($repo, $user, 'www');
        $source  = $this->projPath($repo, $user, 'Source');
        $access  = $this->projPath($repo, $user, 'log/access.log');
        $error   = $this->projPath($repo, $user, 'log/error.log');

        if (!($template = $this->read($config['vhostTemplate']))) {
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

        if (!$this->write($file, $template)) {
            return false;
        }

        $this->symlinkOnce($source, $docroot);

        // touch log
        touch($access);
        $this->chgrp($access, $config['sysWebGroup']);
        $this->chmod($access, 0664);

        touch($error);
        $this->chgrp($error, $config['sysWebGroup']);
        $this->chmod($error, 0664);

        return true;
    }



}
?>
