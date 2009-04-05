<?
	set_time_limit(0);   // Remove time limit 
	
	$config['mysql_user']			= "trueserver";
	$config['mysql_password']		= "WhazaAB!";
	$config['mysql_host']			= "database.trueserver.nl";
	$config['mysql_database']		= "trueserver";
	/*
	Setting up the database connection. If the script can't setup a connection catch the error, throw it 
	to the alert routine and exit the script.
	
	Select the database which we are going to use. If the script can't select the database defined, mention it
	and die in peace.
	*/
	
	
	if (! @mysql_connect( $config['mysql_host'], $config['mysql_user'], $config['mysql_password'] ) ) {
		die("Mysql Connection Failed!\n");
	}
	if (! @mysql_select_db( $config['mysql_database'] ) ) {
		die("Mysql Connection Failed!\n");
	}
	
	
	$config['app_name']					= "kHosting (c)2005 True,KvZ 1.6";
	$config['app_path']					= "/usr/local/khosting";
	
	$config['hosting_server_id']		= 1;
	$config['hosting_server_name']		= "True kHosting Server 1";
	$config['hosting_server_shortname']	= "khosting1";
	
	$config['partition']				= "/dev/sda";
	$config['data_dir']					= "/data/hosting";
	$config['nfs_exports']				= "/etc/exports";
	$config['hosts_allow']				= "/etc/hosts.allow";
	$config['hosts_deny']				= "/etc/hosts.deny";
	
	$config['logfile']					= "/var/log/khosting";
	$config['skipdisplay']				= 1; //0 also includes debug info, 1 information, 2 warnings, 3 criticals, 4 fatals, 5 no logging to this output
	$config['skiplogging']				= 0; //0 also includes debug info, 1 information, 2 warnings, 3 criticals, 4 fatals, 5 no logging to this output
	$config['skipmailing']				= 2; //0 also includes debug info, 1 information, 2 warnings, 3 criticals, 4 fatals, 5 no logging to this output
	
	$config['mail_alert']				= "kevin@true.nl";
	$config['mail_from']				= $config['hosting_server_shortname']."@true.nl";
	
	$config['blocksize']				= 1024; //determineBlockSize(); //;///1024
	
	$config['uid_prefix']				= "4";
	
	$config['iptables_whitelist_buf'] 	="
		127.0.0.1			(localhost)
		80.247.200.1		(isis.2fast.nl)
		80.247.192.23		(esta.2fast.nl)
		80.247.211.1		(aviva.2fast.nl)
		80.247.199.7		(hydrogen.2fast.nl)
		80.247.192.22		(dana.2fast.nl)
		213.193.213.138		(akasha.true.nl)
		80.247.192.25		(yetti.2fast.nl)
		213.239.129.60		(indigo.trueserver.nl)
		213.193.243.82		(slurp 1)
		213.193.243.83		(slurp 2)
		217.19.20.246		(office dsl)
		82.197.201.143		(kevin home)
		213.239.138.82		(bleeker home)
		80.247.216.149		(revolv01.dns.true.nl)
	";
	
	$arl = explode("\n",$config['iptables_whitelist_buf']);
	$config['iptables_whitelist'] = array();
	foreach($arl as $key=>$l){
		$parts = explode("(",$l);
		$ip = trim($parts[0]);
		if(substr_count($ip,".")==3){
			$config['iptables_whitelist'][] = $ip;
		}
	}
	
	$config['iptables_whitelist'] 		= array_unique($config['iptables_whitelist']);
	
	$config['arrLogLevels'][0] = "debug";		//e.g.: detailed information about the process
	$config['arrLogLevels'][1] = "info";		//e.g.: information about the process
	$config['arrLogLevels'][2] = "warning";	//e.g.: alerts
	$config['arrLogLevels'][3] = "critical";	//e.g.: cannot perform a query
	$config['arrLogLevels'][4] = "fatal";		//e.g.: cannot make database connection
	
	foreach($_SERVER["argv"] as $key=>$param){
		if ($param == "--debug"){
			$config['opt']["debug"]["enabled"] = true;
		}
	}
	
	
	$include_dir = $config["app_path"]."/include";
	$handle = @opendir($include_dir);
	$i=0;
	while ($file = @readdir($handle)){ 
		$locpath = $include_dir."/".$file;
		if (!is_dir($locpath) && $file != $_SERVER["PHP_SELF"]){
			$i++;
			include_once($locpath);
		}
	}
	logit($i." files included",0);
	
?>