<?php
/**
 * Contains a lot of functions to ease
 * working on a system
 *
 * @author kvz
 */
require_once dirname(__FILE__).'/Base.php';
require_once dirname(__FILE__).'/Cmd.php';

class EggShell extends Base {

    protected $_options = array(
        'dryrun' => false,
        'write-replace' => false,
    );

    /**
     * Options are passed to every Class contstructor
     * and hence available troughout the entire system.
     *
     * To avoid collission, That's why they're prefixed with scope.
     *
     * @param array $options
     */
    public function  __construct($options = array()) {
        $this->className = get_class($this);
        
        // Take care of recursion when there are ++++ levels of inheritance
        parent::__construct($options);
        // Get direct parent defined options
        $parentVars    = @get_class_vars(@get_parent_class(__CLASS__));
        // Override with own defined options
        $this->_options = $this->merge((array)@$parentVars['_options'], $this->_options);
        // Override with own instance options
        $this->_options = $this->merge($this->_options, $options);
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
        if ($args !== 'plain') {
            $args = func_get_args();
            $cmd  = array_shift($args);
            if (count($args)) {
                $cmd = vsprintf($cmd, $args);
            }
        }
        
        $this->debug('Running \'%s\'', $cmd);
        $Cmd = new Cmd();
        $Cmd->callbacks = array(
            'stdout' => array($this, 'stdout'),
            'stderr' => array($this, 'stderr'),
        );
        $Cmd->cmd($cmd);

        if (false === $Cmd->okay) {
            return $this->warning('Command: %s failed (%s). %s',
                $cmd,
                $Cmd->code,
                $Cmd->stderr);
        }
        
        return $Cmd->stdout;
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

        if ($options['write-once'] && $this->fileExists($filename)) {
            if (strpos(file_get_contents($filename), $data) !== false) {
                $this->debug('Skipping writing to %s. Content already there.', $filename);
                return null;
            }
        }

        if (!$options['dryrun']) {
            if (!is_string($filename)) {
                return $this->err('Filename cannot be: %s', $filename);
            }

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

    public function fileExists($filename) {
        return file_exists($filename);
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

        $hostscript = '/etc/init.d/hostname.sh';
        if ($this->fileExists($hostscript)) {
            $this->exe($hostscript);
        } else {
            $this->exe('/bin/hostname %s', $hostname);
        }
        
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
     * Mkdir once
     *
     * @param <type> $pathname
     * @param <type> $mode
     * @param <type> $recursive
     *
     * @return <type>
     */
    public function mkdirOnce($pathname, $mode = 0777, $recursive = false) {
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
        return $this->exe('export DEBIAN_FRONTEND=noninteractive && apt-get -yfq --force-yes install %s', $package);
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
        if (false === $this->exe('aptitude -o Aptitude::Cmdline::ignore-trust-violations=true -y update')) {
            return false;
        }
        if (false === $this->exe('dpkg --configure -a')) {
            return false;
        }
        return true;
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
                return $this->warning('Could not copy %s to %s', $source, $dest);
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

        return $this->exe($cmd);
    }

    /**
     * Adds a system user
     *
     * @param <type> $username
     * @param <type> $groupname
     * @param <type> $uid
     * @param <type> $fullname
     * @param <type> $home
     * @param <type> $shell
     *
     * @return <type>
     */
    function userAdd($username, $groupname = null, $uid = null, $fullname = null, $home = null, $shell = '/bin/bash') {
        $cmd  = '/usr/sbin/useradd';
        $cmd .= ' '. $username;

        if ($uid) $cmd .= ' --uid '.escapeshellarg($uid);
        if ($groupname) {
            if (!groupExists($groupname)) {
                $this->err('Group: %s does not exit yet', $groupname);
                return false;
            }

            $cmd .= ' -N --gid '.escapeshellarg($groupname);
        }
        if ($fullname) $cmd .= ' --comment '.escapeshellarg($fullname);
        if ($home) $cmd .= ' --create-home --home-dir '.escapeshellarg($home);
        if ($shell) $cmd .= ' --shell '.escapeshellarg($shell);

        if (false === $this->exe($cmd)) {
            return false;
        }

        return true;
    }

    /**
     * Checks if system user exists
     *
     * @param <type> $filter
     * @param <type> $field
     *
     * @return mixed array or boolean on failure
     */
    function userExists($filter, $field = 'username') {
        $lines = file('/etc/passwd', FILE_IGNORE_NEW_LINES ^ FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $parts = explode(':', $line);
            list($username, $shadow, $uid, $gid, $fullname, $home, $shell) = $parts;

            $com      = explode(',', $fullname);
            $fullname = array_shift($com);

            if ($filter === ${$field}) {
                return compact('username', 'shadow', 'uid', 'gid', 'fullname', 'home', 'shell');
            }
        }

        return false;
    }

    /**
     * Change unix (and optionally Samba) password
     *
     * @param <type> $username
     * @param <type> $password
     * @param <type> $options
     *
     * @return <type>
     */
    function userPasswd($username, $password, $options = array()) {
        if (!array_key_exists('samba', $options)) $options['samba'] = false;

        if (false === $this->exe('echo "%s:%s" | /usr/sbin/chpasswd',
            escapeshellcmd($username),
            escapeshellcmd($password))) {

            return false;
        }
        
        if ($options['samba']) {
            if (false === $this->exe('(echo "%s"; echo "%s") | /usr/bin/smbpasswd -a -s -U %s',
                    $password,
                    $password,
                    $username)) {
                return false;
            }
        }

        return true;
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


    public function chown($filename, $user = null, $group = null) {
        $this->mark();
        
        if ($user !== null) {
            if (false === chown($filename, $user)) {
                return $this->err('Unable to chown(%s) %s', $user, $filename);
            }
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
    public function chmodr ($pathname, $mode) {
        $this->mark();

		if (!is_file($pathname) && !is_dir($pathname)) {
			return $this->err('Path not found while doing recursive chmod: %s', $pathname);
		}

        $iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($pathname),
			RecursiveIteratorIterator::SELF_FIRST
		);

        foreach ($iterator as $filename) {
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

		if (!is_file($pathname) && !is_dir($pathname)) {
			return $this->err('Path not found while doing recursive chown: %s', $pathname);
		}

        $iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($pathname),
			RecursiveIteratorIterator::SELF_FIRST
		);

        foreach ($iterator as $filename) {
            if (false === $this->chown($filename, $user, $group)) {
                return false;
            }
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
