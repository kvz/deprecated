<?php
/**
 * Contains a lot of functions to ease
 * working on a system
 *
 * @author kvz
 */
class EggShell extends Base {
    /**
     * Contstructor. Options are passed to every Class contstructor
     * and hence available troughout the entire system.
     *
     * To avoid collission, That's why they're prefixed with scope.
     *
     * @param array $options
     */
    public function  __construct($options = array()) {
        $this->className = get_class($this);
        $this->_options = $options;
    }
    
    /**
     * Wrapper for _exe. Dies on failure.
     *
     * @return mixed string or boolean on failure
     */
    public function exe() {
        $args = func_get_args();
        if (false === ($res = call_user_func_array(array($this, '_exe'), $args))) {
            // Errors will halt everthing
            return $this->err('Cant continue after this');
        }
        return $res;
    }
    /**
     * Wrapper for _exe. Continues on failure.
     *
     * @return mixed string or boolean on failure
     */
    public function exeContinue() {
        $args = func_get_args();

        if (false === ($res = call_user_func_array(array($this, '_exe'), $args))) {
            return false;
        }

        return $res;
    }

    public function gitGet($repository, $directory, $options = array()) {
        $options = $this->merge($this->_options, $options);

        if (!array_key_exists('git-checkout', $options)) $options['git-checkout'] = 'master';
        if (!array_key_exists('git-refspec', $options)) $options['git-refspec'] = 'origin master';
        
        $parent  = dirname($directory);

        if (false === $this->mkdirOnce($parent)) {
            return false;
        }

        if (!is_dir($directory)) {
            $this->exe('git clone %s %s', $repository, $directory);
        } else {
            $this->exe('cd %s && git pull %s',
                $directory,
                $options['git-refspec']);
        }

        if ($options['git-checkout']) {
            $this->exe('cd %s && git checkout %s',
                $directory,
                $options['git-checkout']);
        }

        return true;
    }

    /**
     * Sprintf way of executing commands
     *
     * @param <type> $cmd
     * @param <type> $args
     *
     * @return mixed string or boolean on failure
     */
    protected function _exe($cmd, $args = array()) {
        $args = func_get_args();
        $cmd  = array_shift($args);
        if (count($args)) {
            $cmd = vsprintf($cmd, $args);
        }

        $output     = '';
        $return_var = 0;

        $this->debug('Running \'%s\'', $cmd);
        if (!$this->_options['dryrun']) {
            if ($this->_options['exe-method'] === 'exec') {
                $cmd .= ' 2>&1';
                $lastline   = exec($cmd, $output, $return_var);
            } elseif ($this->_options['exe-method'] === 'proc_open') {
                $output = array();
                $error  = '';
                $descriptorspec = array(
                    0 => array("pipe", "r"),  // stdin
                    1 => array("pipe", "w"),  // stdout
                    2 => array("pipe", "r")   // stderr ?? instead of a file
                );
                $process = proc_open($cmd, $descriptorspec, $pipes);
                if (is_resource($process)) {
                    #fwrite($pipes[0], $secret);
                    #fclose($pipes[0]);
                    while ($lastline = fgets($pipes[1], 1024)) {
                    // read from the pipe
                        $output[] = $lastline;
                        $this->stdout(rtrim($lastline));
                    }
                    fclose($pipes[1]);
                    // optional:
                    while ($lastline = fgets($pipes[2], 1024)) {
                        $error .= $lastline . "\n";
                        $this->stderr(rtrim($lastline));
                    }
                    fclose($pipes[2]);
                }

                $return_var = proc_close($process);
            } else {
                return $this->err('exe-method %s not supported', $this->_options['exe-method']);
            }
        } else {
            $lastline   = '(dryrun)';
            $output     = (array)'(dryrun)';
            $return_var = 0;
        }

        $output = join("\n", $output);

        #$this->debug('Running \'%s\', returned: %s: \'%s\'', $cmd, $return_var, $lastline);

        if ($return_var !== 0 && $return_var !== 255) {
            // 255 for aptitude when 1 ppa cannot be found
            // in a different distro.
            // and === 1 is to rigureus.
            return $this->warning('Command: %s failed', $cmd);
            return false;
        }

        return $output;
    }

    /**
     * Wrapper for ->append()
     * Will append to the file once only
     *
     * @param <type> $filename
     * @param <type> $data
     * @param <type> $options
     *
     * @return mixed boolean or null when skipped
     */
    public function appendOnce($filename, $data, $options = array()) {
        $options['write-once'] = true;
        return $this->append($filename, $data, $options);
    }

    /**
     * Wrapper for ->write()
     * Will append to the file
     *
     * @param <type> $filename
     * @param <type> $data
     * @param <type> $options
     *
     * @return mixed boolean or null when skipped
     */
    public function append($filename, $data, $options = array()) {
        $options['write-flags'] = FILE_APPEND;
        return $this->write($filename, $data, $options);
    }

    /**
     * Wrapper for file_put_contents
     * Can optionally use ->replace()
     *
     * @param <type> $filename
     * @param <type> $data
     * @param <type> $options
     *
     * @return mixed boolean or null when skipped
     */
    public function write($filename, $data, $options = array()) {
        $options = $this->merge($this->_options, $options);

        if (is_array($data)) {
            $data = join("\n", $data);
        }

        if (!array_key_exists('write-replace', $options)) $options['write-replace'] = true;
        if (!array_key_exists('write-flags', $options)) $options['write-flags'] = null;
        if (!array_key_exists('write-once', $options)) $options['write-once'] = false;


        $this->debug('Writing to %s', $filename);

        if (!empty($options['write-replace'])) {
            $this->replace($data, $options['write-replace']);
        }

        if ($options['write-once'] && file_exists($filename)) {
            if (strpos(file_get_contents($filename), $data) !== false) {
                $this->notice('Skipping writing to %s. Content already there.', $filename);
                return null;
            }
        }

        if (!$options['dryrun']) {
            $r = file_put_contents($filename, $data, $options['write-flags']);
        } else {
            $r = null;
            $this->debug('"%s" > %s written', $data, $filename);
        }

        if ($r === false) {
            return $this->err('%s could not be written', $filename);
        }

        return true;
    }

    public function mountpoint($device) {
        $this->mark();
        $mountline = $this->exeContinue('/bin/mount |grep "%s "', $device);
        if (!$mountline) {
            return false;
        }

        $words = explode(' ', $mountline);
        return $words[2];
    }

    public function hasDevice($device){
        $this->mark();
        $res = $this->exeContinue('/usr/bin/stat %s', $device);
        if (false === $res) {
            return false;
        }
        return true;
    }



    /**
     * Reads a file or url and returns it's contents or false on failure
     *
     * @param <type> $filename
     * @param <type> $options
     *
     * @return mixed string or array (if 'read-array' is set) or boolean on failure
     */
    public function read($filename, $options = array()) {
        $options = $this->merge($this->_options, $options);

        if (!array_key_exists('read-array', $options)) $options['read-array'] = false;

        $r = file_get_contents($filename);

        if ($r === false) {
            return $this->err('%s could not be read', $filename);
        } else {
            $this->debug('%s sucessfully read', $filename);
        }

        if ($options['read-array']) {
            $r = explode("\n", $r);
        }

        return $r;
    }

    /**
     * Set a machine's hostname
     *
     * @param string $hostname
     *
     * @return boolean
     */
    public function setHostname($hostname) {
        $this->mark();

        $this->info('Setting hostname to: %s', $hostname);

        $this->write('/etc/hostname', $hostname);

        // Protection againt mail storm
        $this->write('/etc/mailname', 'localhost');

        $this->exe('/etc/init.d/hostname.sh');
        return true;
    }

    /**
     * Get a machine's hostname
     *
     * @return string
     */
    public function getHostname() {
        return trim($this->read('/etc/hostname'));
    }

    /**
     * Sets a machine's role.
     *  e.g.: queen, drone, primary
     *
     * @param <type> $role
     *
     * @return boolean
     */
    public function setRole($role) {
        $this->mark();

        $this->info('Setting role to: %s', $role);
        $this->write('/etc/transload.role', $role);
        return true;
    }

    /**
     * Gets a machine's role
     *  e.g.: queen, drone, primary
     *
     *
     * @return mixed string or boolean on failure
     */
    public function getRole() {
        return $this->read('/etc/transload.role');
    }


    /**
     * Mkdir once
     *
     * @param <type> $pathname
     * @param <type> $mode
     * @param <type> $recursive
     *
     * @return <type>
     */
    public function mkdirOnce($pathname, $mode = null, $recursive = null) {
        $this->mark();

        if (!is_dir($pathname)) {
            return mkdir($pathname, $mode, $recursive);
        }
        return true;
    }

    /**
     * Create symlink once
     *
     * @param string $target
     * @param string $link
     * @param string $options
     *
     * @return array
     */
    public function symlinkOnce($target, $link, $options = array()) {
        $this->mark();

        if (!is_link($link)) {
            if (false === symlink($target, $link)) {
                return false;
            }

            return true;
        }

        return null;
    }

    /**
     * Returns true if an APT package was successfully installed
     *
     * @param string $package
     * @param array  $options
     *
     * @return boolean
     */
    public function aptInstalled($package, $options = array()) {
        $this->mark();

        $res = $this->exeContinue("dpkg -s %s |grep 'Status:'", $package);
        if (false !== strpos($res, 'Status: install ok installed')) {
            return true;
        }

        return false;
    }

    /**
     * Wrapper for ->aptInstall(), switches and returns to another distro
     * version so you can take a package from a higher distro.
     *
     * @param mixed string or array $package
     * @param string                $dist
     * @param array                 $options
     *
     * @return boolean
     */
    public function aptInstallOtherDist($package, $dist, $options = array()) {
        $this->mark();

        $options = $this->merge($this->_options, $options);

        // Already in this dist??
        if ($dist === $this->_options['ubuntu-distr']) {
            $this->notice('Already in distro: %s, no need to switch! Might as well remove the $takeFrom property from %s',
                $dist,
                $package
            );
            if (false === $this->aptInstall($package, $options)) {
                return false;
            }
            return true;
        }

        // Force an install if we're going to take this from another distro
        if (!array_key_exists('force-install', $options)) $options['force-install'] = true;

        // Jump to new distro
        $this->info('Upgrading to "%s" temporarily', $dist);
        if (false === $this->aptSources(array(
            'ubuntu-distr' => $dist,
            'apt-refresh' => true,
        ))) {
            return false;
        }

        // Install
        if (false === $this->aptInstall($package, $options)) {
            return false;
        }

        // Back to original distro
        $this->info('Going back to "%s"', $this->_options['ubuntu-distr']);
        if (false === $this->aptSources(array(
            'ubuntu-distr' => $this->_options['ubuntu-distr'],
            'apt-refresh' => true,
        ))) {
            return false;
        }
    }

    public function aptInstall($package, $options = array()) {
        $this->mark();

        $options = $this->merge($this->_options, $options);
        if (!array_key_exists('force-install', $options)) $options['force-install'] = false;
        if (!array_key_exists('apt-merge-packages', $options)) $options['apt-merge-packages'] = false;
        if (!array_key_exists('apt-debconfs', $options)) $options['apt-debconfs'] = array();

        if (is_array($package)) {
            if (!$options['apt-merge-packages']) {
                // Recurse
                foreach($package as $p) {
                    if (false === $this->aptInstall($p, $options)) {
                        return false;
                    }
                }
                return true;
            } else {
                // Merge
                if (!$options['force-install']) {
                    $options['force-install'] = true;
                    $this->debug('Had to turn on option: force otherwise option: merge wont work');
                }
                $package = join(' ', $package);
            }
        }

        // Single
        $this->info('Aptitude installing %s', $this->abbr($package));
        if (!empty($options['apt-debconfs'])) {
            foreach($options['apt-debconfs'] as $debconf) {
                $this->exe('echo "%s %s" | debconf-set-selections',
                    $package,
                    $debconf);
            }
        }

        if (!$options['force-install']) {
            if ($this->aptInstalled($package)) {
                return null;
            }
        }

        #return $this->exe('export DEBIAN_FRONTEND=noninteractive && aptitude -y install %s', $package);
        return $this->exe('export DEBIAN_FRONTEND=noninteractive && apt-get -y --force-yes install %s', $package);
    }

    /**
     * Reset APT sources.list
     *
     * @param <type> $ubuntu_distr
     * @param <type> $mirror
     *
     * @return <type>
     */
    public function aptSources($options = array()) {
        $this->mark();

        $options = $this->merge($this->_options, $options);
        if (!array_key_exists('ubuntu-distr', $options)) $options['ubuntu-distr'] = null;
        if (!array_key_exists('ubuntu-medibuntu', $options)) $options['ubuntu-medibuntu'] = true;
        if (!array_key_exists('apt-mirror', $options)) $options['apt-mirror'] = 'us';
        if (!array_key_exists('apt-refresh', $options)) $options['apt-refresh'] = true;
        if (!array_key_exists('apt-ppas', $options)) $options['apt-ppas'] = false;
        if (!array_key_exists('apt-src', $options)) $options['apt-src'] = false;

        if ($options['ubuntu-distr'] === null) {
            return $this->err('Need a valid ubuntu-distr');
        }

        $lines = array();

        // Normal Ubuntu
        $lines[] = '# Normal unlocked ubuntu';
        $lines[] = 'deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR} main restricted universe multiverse';
        $lines[] = 'deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-updates main restricted universe multiverse';
        $lines[] = 'deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-backports main restricted universe multiverse';
        $lines[] = 'deb http://${MIRROR}.archive.ubuntu.com/ubuntu/ ${UBUNTU_DISTR}-security main restricted universe multiverse';
        $lines[] = '';

        // Medibuntu
        if ($options['ubuntu-medibuntu']) {
            $lines[] = '# Medibuntu';
            $lines[] = 'deb http://packages.medibuntu.org/ ${UBUNTU_DISTR} free non-free';
            $lines[] = '';
        }

        // Normal PPAs
        if ($options['apt-ppas']) {
            $options['apt-ppas'] = array_unique($options['apt-ppas']);
            $lines[] = '# PPAs';
            foreach($options['apt-ppas'] as $ppa) {
                $lines[] = 'deb '.$ppa;
            }
            $lines[] = '';
        }

        // All deb-src for all sources
        if ($options['apt-src']) {
            $lines[] = '# Sources for compiling';
            foreach($lines as $line) {
                // Skip comments
                if (substr(trim($line), 0 ,1) === '#') {
                    continue;
                }

                // Add a src for the deb
                if (strpos($line, 'deb ') !== false) {
                    $lines[] = str_replace('deb ', 'deb-src ', $line);
                }
            }
        }

        // Write lines to new file
        $newfile  = '/etc/apt/sources.list.new';
        $realfile = '/etc/apt/sources.list';
        if (false === $this->write($newfile, $lines, array(
            'write-replace' => array(
                'MIRROR' => $options['apt-mirror'],
                'UBUNTU_DISTR' => $options['ubuntu-distr'],
            ),
        ))) {
            return false;
        }

        // If files are the same, skip everything else
        if (sha1_file($newfile) === sha1_file($realfile)) {
            $this->info('Apt sources where the same. No need to check keys or update index.');
            $this->del($newfile, true);
            return null;
        } else {
            $this->info('New Apt sources detected. Fixing keys & updating index.');
        }

        // The files differ.
        $this->mv($newfile, $realfile);

        // Fix PPA Key errors:
        if (!empty($options['apt-ppas'])) {
            $lu = dirname(__FILE__).'/launchpad-update';
            $this->chmod($lu, 0744);
            $this->exe($lu);
        }

        // Fix Medibuntu Key errors:
        if ($options['ubuntu-medibuntu']) {
            if (false === $this->aptSourcesUpdate()) {
                return false;
            }
            $this->exe('apt-get -y --force-yes install medibuntu-keyring');
        }
        
        if ($options['apt-refresh']) {
            if (false === $this->aptSourcesUpdate()) {
                return false;
            }
        }

        return true;
    }

    public function portping($host, $port, $timeout = 2, $retry = 3) {
        while ($retry >= 0) {
            $errno  = '';
            $errstr = '';
            if (($con = @fsockopen($host, $port, $errno, $errstr, $timeout))) {
                @fclose($con);
                return true;
            }

            $retry--;
            if (!$retry) {
                return $errstr ? $errstr : $errno;
            }
            $this->notice('portping failed, retry in 1s (%s attempts left)', $retry);
            sleep(1);
        }
    }

    public function aptSourcesUpdate() {
        return $this->exe('aptitude -o Aptitude::Cmdline::ignore-trust-violations=true -y update');
    }

    public function aptDistUpgrade() {
        return $this->exe('aptitude -y dist-upgrade');
    }

    /**
     * Copies a file and sets permisions afterwards
     *
     * @param <type> $source
     * @param <type> $dest
     * @param <type> $mode
     * @param <type> $user
     * @param <type> $group
     *
     * @return <type>
     */
    public function permCopy($source, $dest, $mode=0600, $user='root', $group='root', $options = array()) {
        $this->mark();

        // Support for arrays
        if (is_array($source)) {
            foreach($source as $src => $dst) {
                if (false === $this->permCopy($src, $dst, $mode, $user, $group, $options)) {
                    return false;
                }
            }
            return true;
        }

        // Proceed in single mode
        $this->debug('Copying %s to %s in mode %s belonging to %s.%s',
            $source,
            $dest,
            $mode,
            $user,
            $group
        );

        if (!array_key_exists('write-replace', $options)) $options['write-replace'] = false;

        if ($options['write-replace']) {
            // Rewrite config
            $data = $this->read($source);
            $this->write($dest, $data, array(
                'write-replace' => $options['write-replace'],
            ));
        } else {
            // Just copy
            if (!copy($source, $dest)) {
                return $this->err('Could not copy %s to %s', $source, $dest);
            }
        }

        if (!$this->chmod($dest, $mode)) {
            return false;
        }

        if (false === $this->chown($dest, $user, $group)){
            return false;
        }
        return true;
    }

    public function chmod($filename, $mode = 0600) {
        $this->mark();

        if (!chmod($filename, $mode)) {
            return $this->err('Could not chmod %s', $filename);
        }

        return true;
    }
    
    public function mv($oldname, $newname) {
        $this->mark();

        if (!rename($oldname, $newname)) {
            return $this->err('Could not move %s to %s', $oldname, $newname);
        }

        return true;
    }

    public function del($filename, $mayFail = false) {
        $this->mark();
        if (!@unlink($filename)) {
            if ($mayFail) {
                return null;
            }
            return $this->err('Could not delete %s', $filename);
        }
        return true;
    }


    public function chown($filename, $user, $group = null) {
        $this->mark();

        if (false === chown($filename, $user)) {
            return $this->err('Unable to chown(%s) %s', $user, $filename);
        }

        if ($group !== null) {
            if (false === chgrp($filename, $group)) {
                return $this->err('Unable to chrp(%s) %s', $group, $filename);
            }
        }

        return true;
    }

    /**
     * Recursive chmod
     *
     * @param <type> $pathname
     * @param <type> $mode
     *
     * @return <type>
     */
    public function chmodr($pathname, $mode) {
        $this->mark();

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathname), RecursiveIteratorIterator::SELF_FIRST);

        foreach($iterator as $filename) {
            if (false === $this->chmod($filename, $mode)) {
                return false;
            }
        }

        return true;
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
    public function chownr($pathname, $user, $group = null) {
        $this->mark();

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pathname), RecursiveIteratorIterator::SELF_FIRST);

        foreach($iterator as $filename) {
            if (false === $this->chown($filename, $user, $group)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Replaces vars & writes config files
     * Can also set permCopy options like: user, group, mode, etc
     *
     * @param string $dst     e.g.: /etc/postfix/main.cf
     * @param array  $options
     *
     * @return boolean
     */
    public function dynamicConfig($dst, $options = array()) {
        $this->mark();

        $baseopts = $this->_options;
        $src      = $this->_options['egg-root'].$dst;

        $baseopts['user']  = 'root';
        $baseopts['group'] = 'root';
        $baseopts['mode']  = 0644;
        $baseopts['replaceVars'] = array(
            'OPT_EGG_FILE' => $baseopts['egg-file'],
            'USER_WWW' => $baseopts['perm-user-www'],
            'GROUP_WWW' => $baseopts['perm-group-www'],
            'HOSTNAME' => $this->getHostname(),
            'DOMAIN' => $baseopts['domain'],
            'CFGSOURCE' => $src,
            'CFGCMD' => 'cd '.dirname($options['egg-root-ingit']).'; ./bin/egg.php '. $this->className. ' config',
        );

        $options = $this->merge($baseopts, $options);

        if (false === $this->permCopy($src, $dst, $options['mode'], $options['user'], $options['group'], array(
            'write-replace' => $options['replaceVars'],
        ))) {
            return false;
        }

        return true;
    }

    /**
     * Add a crontab command
     *
     * @param <type> $command
     * @param <type> $timeschedule
     *
     * @return <type>
     */
    public function crontabAdd($command, $timeschedule) {
        $this->mark();
        // Get
        if (!($list =$this->exeContinue('crontab -l | grep -v "'.addslashes($command).'"'))) {
            $list = '';
        }
        
        // Change
        $list .= sprintf('%s %s', $timeschedule, $command). "\n";

        // Set
        $this->exe('echo "'.addslashes($list).'" | crontab -');

        return true;
    }

    /**
     * Replaces a string (in-place) using $this->vars
     *
     * @param <type> $str
     */
    public function replace(&$str, $vars = array()) {
        $this->mark();
        if (empty($vars) || $vars === true) {
            if (!empty($this->vars)) {
                // Use vars from class config
                $vars = $this->vars;
            } else {
                return $this->debug('No variables supplied by class config nor argument');
            }
        }

        foreach($vars as $key=>$val) {
            $find = '${'.$key.'}';
            $this->debugv('Replacing %s with \'%s\'', $find, $val);
            $str = str_replace($find, $val, $str);
        }
    }
}
?>
