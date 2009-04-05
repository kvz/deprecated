<?
	function curBandwithInTable($nowday, $ipa){
		$bid = ip2bid($ipa);
		$sql2 = "SELECT * FROM backup_bandwidth WHERE day = $nowday AND backup_mounts_id = $bid";
		$res2 = mysql_query($sql2) or logit(mysql_error()." [".$sql2."]",4);
		if(@mysql_num_rows($res2)){
			return mysql_fetch_array($res2);
		}
		else{
			return false;
		}
	}
	
	function isIPTablesSecure(){
		//find nescessary DROP lines
		global $config;
		$arr = array();
		$cmd = "/sbin/iptables -L -n -v |/bin/grep \"DROP\"";
		$buff = `$cmd`;
		if(substr_count($buff,"all")){
			return true;
		}
		else{
			return false;
		}
	}
	function getIPTablesArray($resetcounter=false){
		//retrieve IPTables
		global $config;
		
		$arr = array();
		$cmd = "/sbin/iptables ". ($resetcounter ? '-Z ' : '') ."-L -n -v -x  | /bin/grep all | /usr/bin/awk {'print $2 \" \" $8 \" \" $9'}";
		$buff = `$cmd`;
		$buff = str_replace(" "."0.0.0.0/0","",$buff);
		$buff = explode("\n",$buff);
		
		logit(count($buff). " clean rows retrieves from the command '$cmd' ",0);
		
		foreach($buff as $k=>$line){
			$parts = explode(" ",(trim($line)));
			$byt = trim($parts[0]);
			$ipa = trim($parts[1]);
			if (is_numeric($byt) && $ipa){
				$arr[$ipa] = $arr[$ipa] + $byt; //collect IN + OUT traffic
			}
		}
		
		if($resetcounter){
			logit("iptables counter was reset",0);
		}
		
		return $arr;
		
	}
	
	function iptAct($action="add",$ip=""){
		logit( ($action=="add" ? "adding" : "removing") ." ip address ".$ip." on iptables",0);
		
		if (!trim($action)){
			logit("No valid attributes specified for iptAct(action=".$action.",ip=".$ip.")",4);
		}
		
		$cmd = array();
		switch($action){
			
			case "remove":
				$cmd[] = "/sbin/iptables -D INPUT  -s $ip -j ACCEPT";
				$cmd[] = "/sbin/iptables -D OUTPUT -d $ip -j ACCEPT";		
			break;
			case "add":
				$cmd[] = "/sbin/iptables -A INPUT  -s $ip -j ACCEPT";
				$cmd[] = "/sbin/iptables -A OUTPUT -d $ip -j ACCEPT";
			break;
			case "flush":
				//reset iptables
				$cmd[] = "/sbin/iptables -F";
			break;		
			case "addheader":
				//enable all related connections (e.g. for PASV FTP)
				$cmd[] = "/sbin/iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT";
				//enable all output
				$cmd[] = "/sbin/iptables -A OUTPUT -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT";
			break;
			case "removeheader":
				//enable all related connections (e.g. for PASV FTP)
				$cmd[] = "/sbin/iptables -D INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT";
				//enable all output
				$cmd[] = "/sbin/iptables -D OUTPUT -m state --state NEW,ESTABLISHED,RELATED -j ACCEPT";
			break;
			case "addfooter":
				//allow ftp from everyone
				#$cmd[] = "/sbin/iptables -A INPUT -p tcp --dport 21 --syn -j ACCEPT";
				#$cmd[] = "/sbin/iptables -A INPUT -p tcp --dport 20 --syn -j ACCEPT";
				//drop all other connections (incomming that is not trusted + not ftp)
				$cmd[] = "/sbin/iptables -A INPUT -j DROP";			
			break;
			case "removefooter":
				//allow ftp from everyone
				#$cmd[] = "/sbin/iptables -D INPUT -p tcp --dport 21 --syn -j ACCEPT";
				#$cmd[] = "/sbin/iptables -D INPUT -p tcp --dport 20 --syn -j ACCEPT";
				//drop all other connections (incomming that is not trusted + not ftp)
				$cmd[] = "/sbin/iptables -D INPUT -j DROP";			
			break;
		}	
			
		foreach($cmd as $k=>$cm){
			$buff = `$cm`;
			logit("command '$cm' returns: ".$buff,0);
		}
	}
	
	function updateBandwidthUsed(){
		
		$nowday = date("Ymd");
		if(!strlen($nowday) == 8){
			logit("Could not form a good day date in updateBandwidthUsed() ('".$nowday."') ",3);
		}
		
		$arript = getIPTablesArray(true);
		
		$sql = "SELECT ip_address,backup_mounts_id FROM backup_mounts";
		$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
		//loop over all ips
		
		$i=0;
		while($row = mysql_fetch_array($res)){
			
			$ipa = $row["ip_address"];
			$bid = $row["backup_mounts_id"];
			
			//create nowday record for ip, if 
			if (!is_array(curBandwithInTable($nowday,$ipa))){
				logit("Bandwidth dayrecord for '$ipa' was not yet found",0);
				$sql3 = "
					INSERT INTO backup_bandwidth (
						backup_mounts_id,bandwidth,day,updatestamp
					)
					VALUES (
						$bid,0,$nowday,0
					)
				";
				$res3 = mysql_query($sql3) or logit(mysql_error()." [".$sql3."]",4);	
			}
			//the day record now definately exists... so update it!
			
			
			$cur = curBandwithInTable($nowday,$ipa);
			$oldb = $cur["bandwidth"];
			$newb = $oldb + $arript[$ipa];
			
			if(!$oldb){$oldb = 0;}
			if(!$newb){$newb = 0;}
			
			logit("old bandwidth for $ipa (day $nowday) was $oldb, new bandwidth will be $newb",0);
			
			//did it change?
			if($oldb != $newb){
				$sql4 = "
					UPDATE backup_bandwidth SET 
						bandwidth = ".$newb.",
						updatestamp = ".time()."
					WHERE 
						day = ".$nowday." AND
						backup_mounts_id = ".$bid."
					LIMIT 1
				";
				$res4 = mysql_query($sql4) or logit(mysql_error()." [".$sql4."]",4);
				$i++;
			}
		}
		
		logit("$i bandwidth records were changed, succesfully synchronized",0);
		return $i;
		
	}
	
	function synchronizeIPTabes(){
		/* 
			Function updates the iptables file
		*/	
		global $config;
		$recreateIPTables = !isIPTablesSecure();
		
		if($recreateIPTables){
			logit("IP Tables does not meet security characteristics. Rewriting IPTables", 2);
			logit("writing iptables secure header rules", 0);
			iptAct("flush");
			iptAct("addheader");
		}
		else{
			iptAct("removefooter");
		}
		
		$tlips = getIPTablesArray(false);
		$ri=0;
		foreach($tlips as $ip => $byt){
			$sql = "SELECT backup_mounts_id FROM backup_mounts WHERE ip_address = '".$ip."' AND status <> 'removed'";
			$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
			$num = @mysql_num_rows($res);
					
			if ($num != 1 && !in_array($ip,$config['iptables_whitelist'])){
				//not found in database & not found in whitelist array. delete it from IPTABLES!
				logit("removing $ip because it exists in iptables, but not in database, nor in the whitelist", 1);
				iptAct("remove",$ip);
				$ri++;
			}
		}
		
		logit("$ri ip addresses where removed from iptables", 0);
		
		$tlips = getIPTablesArray(false);
		$sql = "SELECT backup_mounts_id,ip_address FROM backup_mounts WHERE status <> 'removed'";
		$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
		$num = @mysql_num_rows($res);
		$ai=0;
		$dbips = array();
		while($row = mysql_fetch_array($res)){
			$ip = trim($row["ip_address"]);
			$dbips[$ip] = 666;
		}
		
		
		//add whitelist to de db_ips, so we can add INPUT records if nescessary
		foreach($config['iptables_whitelist'] as $k=>$ip){
			$dbips[$ip] = 666;
		}
			
		foreach($dbips as $ip=>$byt){
			if ( !isset($tlips[$ip]) ){
				//not found in iptables. add it to IPTABLES!
				logit("adding $ip because it exists in database, but not in iptables", 1);
				iptAct("add",$ip);
				$ai++;
			}		
		}
		
		logit("$ai ip addresses were added to iptables", 0);
		
		$li = $ai + $ri;
	
		//if($recreateIPTables){
		logit("writing iptables secure footer rules", 0);
		iptAct("addfooter");
		//}	
			
		logit("$li iptable records were changed, succesfully synchronized", 0);
			
		return $li;
	}
	
	
	function synchronizeHostsAllow(){
		/* 
			Function updates the /etc/hosts.allow & deny file
		*/
		global $config;
		
		//some important strings
		$cln = "######dynamic content below here. do not edit this comment. do not edit below this comment. kvz-22/5/05######";
		$fcl = "dynamic content below here";
		$oke = "Re-exporting hosts.allow records... done.";
		
		//load file
		$expbuf = getFile($config['hosts_allow']);
		$exparr = explode("\n",$expbuf);
		$keeparr = array();	
		
		//retrieve info we want to save
		$found=false;
		foreach ($exparr as $k=>$line){
			if(!$found){
				$keeparr[] = $line;
				logit("adding: '$line' to the keeparr",0);
			}
			if (substr_count($line,$fcl)){
				logit("special comment found on line $k, everything after this will not be remembered",0);
				$found=true;
			}
		}
		
		if (!$found){
			logit("No 'dynamic content below here' line in ".$config['hosts_allow']." was found! Creating one now!",3);
			$keeparr = $exparr;		
			$keeparr[count($keeparr)+1] = $cln;
		}
			
		//creating the new array
		//loop over all backupmounts
		$newarr = array();
		$sql = "SELECT * FROM backup_mounts WHERE status <> 'removed' ORDER BY backup_mounts_id ASC";
		$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
		
		$i=0;
		
		while($row = mysql_fetch_array($res)){
			$uid = $row["backup_mounts_id"];
			$ufq = id2fq($uid,"u");
			$ipa = $row["ip_address"];
			$hst = gethostbyname($ipa);
					
			if(!$hst){
				logit("could not resolve $ipa to a valid hostname. skipping hosts.allow export for this server",3);
			}
			else{
				//add server to array
				$full = "ALL:"."\t".$ipa;
				logit( "(re-)adding '$full' to the keeparr ",0);
				$i++;
				$keeparr[] = $full;
			}
		}
		
		//output array
		$expbuf = implode("\n",$keeparr)."\n";
		putFile ($config['hosts_allow'],$expbuf);
		//putfile ($config['hosts_deny'],"ALL:\tALL\n"); //lowlands breaks.. uncomment this later
		putfile ($config['hosts_deny'],""); //remove this line when lowlands has finished
		logit(count($keeparr)." hosts.allow records were rewritten, succesfully synchronized",0);	
		
		return $i;
	}
	
	
	function synchronizeNFSShares(){
		/* 
			Function updates the /etc/exports file
		*/
		global $config;
		
		//some important strings
		$cln = "######dynamic content below here. do not edit this comment. do not edit below this comment. kvz-22/5/05######";
		$fcl = "dynamic content below here";
		$oke = "Re-exporting directories for NFS kernel daemon... done.";
		
		//load file
		$expbuf = getFile($config['nfs_exports']);
		$exparr = explode("\n",$expbuf);
		$keeparr = array();	
		
		//retrieve info we want to save
		$found=false;
		foreach ($exparr as $k=>$line){
			if(!$found){
				$keeparr[] = $line;
				logit("adding: '$line' to the keeparr",0);
			}
			if (substr_count($line,$fcl)){
				logit("special comment found on line $k, everything after this will not be remembered",0);
				$found=true;
			}
		}
		
		if (!$found){
			logit("No 'dynamic content below here' line in ".$config['nfs_exports']." was found! Creating one now!",3);
			$keeparr = $exparr;		
			$keeparr[count($keeparr)+1] = $cln;
		}
			
		//creating the new array
		//loop over all backupmounts
		$newarr = array();
		$sql = "SELECT * FROM backup_mounts WHERE status <> 'removed' ORDER BY backup_mounts_id ASC";
		$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
		$num = mysql_num_rows($res);
		$i=0;
		
		while($row = mysql_fetch_array($res)){
			logit($i." of ".$num,0);
			$uid = $row["backup_mounts_id"];
			$ufq = id2fq($uid,"u");
			$ipa = $row["ip_address"];
			#$hst = gethostbyaddr($ipa);
			
			$gidlinux = str_replace("g000",$config['uid_prefix'], id2fq($row["relatie_id"]      ,"g") );
			$uidlinux = str_replace("u000",$config['uid_prefix'], id2fq($row["backup_mounts_id"],"u") );
			
			$opt = "(rw,all_squash,anonuid=".$uidlinux.",anongid=".$gidlinux.",sync)";
			
			#if(!$hst){
			#	logit("could not resolve $ipa to a valid hostname. skipping nfs export for this server",3);
			#}
			#else{
				//add server to array
				$map = $config['data_dir']."/".getGroupFQFromUserFQ($ufq)."/".$ufq;
				$full = $map."\t".$ipa."".$opt;
				
				logit( "(re-)adding '$full' to the keeparr ",0);
				$i++;
				$keeparr[] = $full;
			#}
		}
		
		//output array
		$expbuf = implode("\n",$keeparr)."\n";
		putFile ($config['nfs_exports'],$expbuf);
		logit(count($keeparr)." nfs export records were rewritten, succesfully synchronized",0);
		
		//reload config
		$cmd = "/etc/init.d/nfs-kernel-server reload";
		$buff = `$cmd`;
		$buff = cleanString($buff,"\t,\n, ,\r");
		$oke  = cleanString($oke ,"\t,\n, ,\r");
		
		if( strtolower($buff)  == strtolower($oke) ){
			logit("succesfully reloaded the nfs-kernel-server config",0);
		}
		else{
			logit("error while reloading the nfs-kernel-server, command: '$cmd' returned: '$buff'",3);
		}	
		
		
		return $i;
	}
	
	
	function updateBytesInUse(){
		/* 
			Function updates the field "bytes_in_use" by parsing the output of "/usr/sbin/repquota /dev/sda".
		*/
		global $config;
		$arrBytes = array();
	
		//get quota table
		$cmd = '/usr/sbin/repquota /dev/sda | /bin/egrep -v "(Report|Block grace|\-\-\-|User  |Block limits)" | /usr/bin/awk {\'print $1"="$3\'}';
		logit($cmd,0);
		$buff = `$cmd`;
		logit($buff,0);
		if ( !strlen(trim(str_replace("\n","",$buff))) ){
			logit("Could not retrieve enough valid quota data from command '".$cmd."'",4);
		}
		$buff = split( "\n", $buff); 	
		
		//get important lines & quota
		$arrBytes = array();
		foreach ( $buff AS $key => $line ) {
			if(substr($line,0,strlen("u0")) == "u0" ){
				$parts = explode("=",$line);
				$ufq = $parts[0];
				$blk = $parts[1];
				
				if(trim($ufq) && is_numeric(trim($blk))){
					//valid info
					$arrBytes[fq2id($ufq)] = block2byte($blk);
				}
			}
		}
		
		
		if(count ($arrBytes) < 2){
			//print_r ($arrBytes);
			logit("Unable to parse enough used_blocks data from the command: '$cmd'",3);
		}
		
	
		//loop over all backupmounts, and set their bytes_in_use!
		$sql = "SELECT * FROM backup_mounts ORDER BY backup_mounts_id ASC";
		$res = mysql_query($sql) or logit(mysql_error()." [".$sql."]",4);
		$i = 0;
		if (@mysql_num_rows($res)){
			while($row = mysql_fetch_array($res)){
				
				if (!$arrBytes[$row["backup_mounts_id"]]){
					$arrBytes[$row["backup_mounts_id"]] = 0;
				}
				
				logit("Upating bytes_in_use = ".$arrBytes[$row["backup_mounts_id"]]." WHERE id = ".$row["backup_mounts_id"]." ",0);
				
				$sqlu = "
					UPDATE 
						backup_mounts 
					SET 
						bytes_in_use = ".$arrBytes[$row["backup_mounts_id"]]. " 
					WHERE 
						backup_mounts_id = ".$row["backup_mounts_id"] . " LIMIT 1
				";
				$resu = mysql_query($sqlu) or logit(mysql_error()." [".$sqlu."]",4);
				$i++;
			}
			logit( $i ." hardisk usage records were changed, succesfully synchronized",0);
		}
		else{
			logit( "Error, cannot retrieve backupmounts from database",3);
		}
		
		return $i;
	}

?>