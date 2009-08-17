<?php
/**
 * An attempt at solid cache invalidation.
 * When writing cache, certain events that make it invalid have
 * to be registered.
 * When these events occur cache can be purged
 *
 * @author kvz
 */

/**
 * Easy static access to EventCacheInst
 *
 */
class EventCache {
    static public    $config = array();
    static protected $_instance = null;
    
    static protected function _getInstance() {
        if (EventCache::$_instance === null) {
            EventCache::$_instance = new EventCache(EventCache::$config);
        }

        return EventCache::$_instance;
    }
    static public function setOption($key, $val = null) {
        $_this = EventCache::_getInstance();
        return $_this->setOption($key, $val);
    }
    
    static public function read($key) {
        $_this = EventCache::_getInstance();
        return $_this->read($key);
    }
    
    static public function write($key, $val, $events = array(), $options = array()) {
        $_this = EventCache::_getInstance();
        return $_this->write($key, $val, $events, $options);
    }
}

/**
 * Main EventCache Class
 *
 */
class EventCacheInst {
    const LOG_EMERG = 0;
    const LOG_ALERT = 1;
    const LOG_CRIT = 2;
    const LOG_ERR = 3;
    const LOG_WARNING = 4;
    const LOG_NOTICE = 5;
    const LOG_INFO = 6;
    const LOG_DEBUG = 7;
    
    protected $_logLevels = array(
        self::LOG_EMERG => 'emerg',
        self::LOG_ALERT => 'alert',
        self::LOG_CRIT => 'crit',
        self::LOG_ERR => 'err',
        self::LOG_WARNING => 'warning',
        self::LOG_NOTICE => 'notice',
        self::LOG_INFO => 'info',
        self::LOG_DEBUG => 'debug'
    );
    
    protected $_config = array(
        'app' => 'base',
        'delimiter' => '-',
        'adapter' => 'EventCacheMemcachedAdapter',
        'trackEvents' => false,
        'servers' => array(
            '127.0.0.1',
        ),
    );
    
    protected $_dir   = null;
    protected $_Cache = null;

    public function  __construct($config) {
        $this->_config       = array_merge($this->_config, $config);
        $this->_Cache        = new $this->_config['adapter'](array(
            'servers' => $this->_config['servers'],
        ));
    }
    public function setOption($key, $val = null) {
        if (is_array($key) && $val === null) {
            foreach ($key as $k => $v) {
                if (!$this->setOption($k, $v)) {
                    return false;
                }
            }
            return true;
        }
        
        $_this->_config[$key] = $val;
    }


    public function write($key, $val, $events = array(), $options = array()) {
        if (!isset($options['ttl'])) $options['ttl'] = 0;

        $this->register($key, $events);

        $kKey = $this->cKey('key', $key);
        return $this->_set($kKey, $val, $options['ttl']);
    }
    public function read($key) {
        $kKey = $this->cKey('key', $key);
        return $this->_get($kKey);
    }
    public function delete($key) {
        $kKey = $this->cKey('key', $key);
        return $this->_del($kKey);
    }
    public function clear($events = array()) {
        if (!$this->_config['trackEvents']) {
            $this->err('You need to enable the slow "trackEvents" option for this');
            return false;
        }

        if (empty($events)) {
            $events = $this->getEvents();
            $this->clear($events);
        } else {
            $events = (array)$events;
            foreach($events as $eKey=>$event) {
                $cKeys = $this->getEventCKeys($event);
                $this->_del($cKeys);
                
                $this->_del($eKey);
            }
        }
    }
    public function flush() {
        return $this->_flush();
    }


    public function register($key, $events = array()) {
        $events = (array)$events;
        if ($this->_config['trackEvents']) {
            // Slows down performance
            $etKey = $this->cKey('events', 'track');
            foreach($events as $event) {
                $eKey = $this->cKey('event', $event);
                $this->_listAdd($etKey, $eKey, $event);
            }
        }

        foreach($events as $event) {
            $eKey = $this->cKey('event', $event);
            $kKey = $this->cKey('key', $key);
            $this->_listAdd($eKey, $kKey, $key);
        }
    }

    public function trigger($event) {
        $cKeys = $this->getEventCKeys($event);
        $this->_del($cKeys);
    }

    public function getEvents() {
        if (!$this->_config['trackEvents']) {
            $this->err('You need to enable the slow "trackEvents" option for this');
            return false;
        }

        $etKey  = $this->cKey('events', 'track');
        $events = $this->_get($etKey);
        return $events;
    }
    
    public function getKeys($event) {
        $eKey = $this->cKey('event', $event);
        return $this->_get($eKey);
    }
    
    public function getEventCKeys($event) {
        $list = $this->getKeys($event);
        if (!is_array($list)) {
            return $list;
        }
        return array_keys($list);
    }
    
    
    
    public function cKey($type, $key) {
        $cKey = $this->sane($key);
        return $this->_config['app'].$this->_config['delimiter'].$type.$this->_config['delimiter'].$this->sane($cKey);
    }
    public function sane($str) {
        if (is_array($str)) {
            foreach($str as $k => $v) {
                $str[$k] = $this->sane($v);
            }
            return $str;
        } else {
            $allowed = array(
                '0-9' => true,
                'a-z' => true,
                'A-Z' => true,
                '\-' => true,
                '\_' => true,
                '\.' => true,
            );

            if (isset($allowed['\\'.$this->_config['delimiter']])) {
                unset($allowed['\\'.$this->_config['delimiter']]);
            }
            
            return preg_replace('/[^'.join('', array_keys($allowed)).']/', '_', $str);
        }
    }


    public function debug($str) {
        return;
        $args = func_get_args();
        return self::_log(self::LOG_DEBUG, array_shift($args), $args);
    }
    public function err($str) {
        $args = func_get_args();
        return self::_log(self::LOG_ERR, array_shift($args), $args);
    }
    protected function _log($level, $str, $args) {
        $log  = '';
        $log .= '';
        $log .= $this->_logLevels[$level];
        $log .= ': ';
        $log .= vsprintf($str, $args);
        $log .= "\n";
        echo $log;
        return $log;
    }

    protected function _listAdd($memKey, $cKey, $val = null, $ttl = 0) {
        if ($val === null) {
            $val = time();
        }
        $list = $this->_get($memKey);
        $this->debug('Adding key: %s to list: %s count: %s', $cKey, $memKey, count($list));
        $list[$cKey] = $val;
        return $this->_set($memKey, $list, $ttl);
    }
    
    protected function _add($cKey, $val, $ttl = 0) {
        $this->debug('Adding key: %s with val: %s', $cKey, $val);
        return $this->_Cache->add($cKey, $val, $ttl);
    }
    protected function _del($cKeys, $ttl = 0) {
        if (is_array($cKeys)) {
            foreach($cKeys as $cKey) {
                if (!$this->_del($cKey)) {
                    return false;
                }
            }
            return true;
        }
        if (empty($cKeys)) {
            return true;
        }
        
        $this->debug('Deleting key: %s', $cKeys);
        return $this->_Cache->delete($cKeys, $ttl);
    }
    protected function _set($cKey, $val, $ttl = 0) {
        $this->debug('Setting key: %s to val: %s', $cKey, $val);
        return $this->_Cache->set($cKey, $val, $ttl);
    }
    protected function _get($cKey) {
        $this->debug('Getting key: %s', $cKey);
        return $this->_Cache->get($cKey);
    }
    protected function _flush() {
        $this->debug('Flushing');
        return $this->_Cache->flush();
    }
}

/**
 * Memcache adapter to EventCache
 *
 */
class EventCacheMemcachedAdapter {
	protected $_config = array(
		'servers' => null,
	);

	protected $_memd;

	public function __construct($options) {
		$this->_config =  $options + $this->_config;

		$this->_memd = new Memcache();
		foreach ($this->_config['servers'] as $server) {
			call_user_func_array(array($this->_memd, 'addServer'), $server);
		}
	}

	public function get($key) {
		return $this->_memd->get($key);
	}

	public function flush() {
		return $this->_memd->flush();
	}

	public function set($key, $val, $ttl = 0) {
		return $this->_memd->set($key, $val, 0, $ttl);
	}

	public function add($key, $val, $ttl = 0) {
		return $this->_memd->add($key, $val, 0, $ttl);
	}

	public function delete($key, $ttl = 0) {
		return $this->_memd->delete($key, $ttl);
	}

	public function increment($key, $value = 1) {
		return $this->_memd->increment($key, $value);
	}

	public function decrement($key, $value = 1) {
		return $this->_memd->decrement($key, $value);
	}
}
?>