<?php
	Configure::write('debug', 2);
	Configure::write('log', true);
	Configure::write('App.encoding', 'UTF-8');
	//Configure::write('App.baseUrl', env('SCRIPT_NAME'));
	//Configure::write('Routing.prefixes', array('admin'));
	//Configure::write('Cache.disable', true);
	//Configure::write('Cache.check', true);
	define('LOG_ERROR', 2);
	Configure::write('Session.save', 'php');
	//Configure::write('Session.model', 'Session');
	//Configure::write('Session.table', 'cake_sessions');
	//Configure::write('Session.database', 'default');
	Configure::write('Session.cookie', 'ORMIFY');
	Configure::write('Session.timeout', '120');
	Configure::write('Session.start', true);
	Configure::write('Session.checkAgent', true);
	Configure::write('Security.level', 'medium');
	Configure::write('Security.salt', 'aYhG93b0qyJfIafs2guVoU7bWwvniR2G9FgaC9mi');
	Configure::write('Security.cipherSeed', '96859309657453541496249684645');
	//Configure::write('Asset.timestamp', true);
	//Configure::write('Asset.filter.css', 'css.php');
	//Configure::write('Asset.filter.js', 'custom_javascript_output_filter.php');
	Configure::write('Acl.classname', 'DbAcl');
	Configure::write('Acl.database', 'default');
	date_default_timezone_set('UTC');
 
  	Cache::config('default', array(
 		'engine' => 'Apc', //[required]
 		'duration'=> 3600, //[optional]
 		'probability'=> 100, //[optional]
  		'prefix' => Inflector::slug(APP_DIR) . '_', //[optional]  prefix every cache file with this string
 	));
