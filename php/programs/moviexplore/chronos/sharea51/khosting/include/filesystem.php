<?
	
	function getFromDir($path,$fileOrDir=3,$ext="*.*"){		
		/*
			returns Array with files according to given extension, etc.
			$fileOrDir:	1 = file
						2 = dir
						3 = both
		*/
		
		//make comma separated $extarr -> array
		$extarr = explode(",",$ext);
		
		$arrDirs = "";$arrDirs = array();
		$arrFils = "";$arrFils = array();
		
		$handle = @opendir($path);
		$i = 0;
		$j = 0;
		while ($file = @readdir ($handle)){
			if( ($file == "") || ($file == "..") || ($file == ".") ){
			}
			else{
				$filepath = endSlash($path).$file;
				
				if (is_dir($filepath)){
					$arrDirs[$i] = $file;
					$i++;
				}
				if (is_file($filepath)){
					
					$path_parts = pathinfo($filepath);
					$ok = 0;
					
					if (trim($ext) == "*.*") {
						//geen extensie opgegeven
						$ok = 1;
					}
					else{
						//extensie opgegeven
						foreach($extarr as $curext1){
							$curext2 = $curext1;
							$curext2 = str_replace("*","",$curext2);
							$curext2 = str_replace(".","",$curext2);
							if ( strtolower($path_parts["extension"]) == strtolower($curext2) ){
								//extensie goed
								$ok = 1;
							}
						}
					}
					
					if ($ok == 1){
						$arrFils[$j] = $file;
						$j++;
					}
				}
			}
		}
		@closedir($handle);
		
		$arr = array();
		if ( ($fileOrDir == 2) || ($fileOrDir == 3)){
			//dir mag
			sort ($arrDirs);
			$arr = array_merge($arr,$arrDirs);
		}
		if ( ($fileOrDir == 1) || ($fileOrDir == 3)){
			sort ($arrFils);
			$arr = array_merge($arr,$arrFils);
		}
		
		return $arr;
	}		
	
	function getFile($filename){
		$handle = fopen ($filename, "r");
		$contents = fread ($handle, filesize ($filename));
		fclose ($handle);
		return $contents;
	}
	
	function putFile($filename,$somecontent){
		//create if the file doesn't exist
		if(!is_writable($filename)){
			$cmd = "/bin/touch $filename";
			$buf = `$cmd`;
		}
		
		if (is_writable($filename)) {
			if (!$handle = fopen($filename, 'w')) {
				logit("Cannot open file ($filename)",4);
			}
			
			if($somecontent){
				if (!fwrite($handle, $somecontent)) {
					logit("Cannot write to file ($filename)",4);
				}	    	
			}
			else{
				//if we do not have input, just touch the file (cause writint nothing will return an error)
				$cmd = "/bin/touch $filename";
				$buf = `$cmd`;	    	
			}
			fclose($handle);				
		} 
		else {
			logit("The file $filename is not writable",4);
		}
	}
	
	function localGroupExists($group){
		$cmd = "/bin/cat /etc/group |/bin/grep 'g0'";
		$buff = `$cmd`;
		$buff = explode("\n",$buff);
		
		$found = false;
		foreach($buff as $k=>$ln){
			$part1 = explode(":",$ln);		
			if ($part1[0] == $group){
				$found = true;
			}
		}
		
		return $found;
	}
	
	
	function localUsersInGroup($group){
		$cmd = "/bin/cat /etc/passwd |/bin/grep '".$group."'";
		$buff = `$cmd`;
		$buff = explode("\n",$buff);
		
		$retar = array();
		
		$found = false;
		foreach($buff as $k=>$ln){
			$part1 = explode(":",$ln);
			$part1 = $part1[0];
			if(substr($part1,0,2)== "u0"){
				$retar[] = $part1;
			}
		}
		
		return $retar;
	}
	
	function deleteuser($user,$group){
		/*
		Functon to remove users from the system.
		*/
		global $config;
		$gmap = $config['data_dir'] ."/". $group;
		$umap = $config['data_dir'] ."/". $group ."/". $user;
		$gid = str_replace("g000",$config['uid_prefix'],$group);
		$uid = str_replace("u000",$config['uid_prefix'],$user);    
			
		//is this the only user in this group?
		$users = localUsersInGroup($group);
		if (count($users) == 1){
			$removegroup=true;
		}
		else{
			$removegroup=false;
		}
		
		//delete user
		exe("/usr/sbin/userdel -r ".$user);
		exe("/bin/rm -rf ".$umap);
		logit("User ".$user." deleted",1);
		if ($removegroup){
			//delete group
			exe("/usr/sbin/groupdel ".$group);
			exe("/bin/rm -rf ".$gmap);
			logit("Group ".$group." deleted",1);
		}
		else{
			$userstext = implode(",",$users);
			logit("Skipping group deletion, there are more users in this group (".$userstext.")",0);
		}
		
	}
	
	function adduser ( $user, $group ) {
		/*
		Functon to add users to the system.
		Currently very lame but maybe a read of /etc/passwd and /etc/shadow and self writing in those will make it more fool proof?
		*/
		global $config;	
		$gmap = $config['data_dir'] ."/". $group;
		$umap = $config['data_dir'] ."/". $group ."/". $user;
		$gid = str_replace("g000",$config['uid_prefix'],$group);
		$uid = str_replace("u000",$config['uid_prefix'],$user);
		
		//first check if the group exists
		if (!localGroupExists($group)){
			//create new group and map
			exe("/bin/mkdir -p ".$gmap);
			exe ("/usr/sbin/addgroup --gid ". $gid . " ". $group);
			exe("/bin/chgrp " . $group . " " . $gmap);
			logit("Group ".$group." added",1);
		}
		else{
			logit("Skipping creation of group ".$group.", already exists",0);
		}
		
		//create new user and map
		exe("/bin/mkdir -p ".$umap);
		exe("/usr/sbin/useradd -u ".$uid." -g ". $group ." -d ". $umap ." -s /sbin/noshell -m ". $user);
		exe("/bin/chown " . $user . ":" . $group . " " . $umap);
		logit("User ".$user." added",1);
	}
	
	function changepwd($user,$pass=""){
		
		/*
		Functon to change password for a user
		pass maybe empty, this function will then create a password itself
		*/
		global $config;
			
		exe( "/bin/echo \"".$user.":".$pass."\" | /usr/sbin/chpasswd");
		
		/*
		//we don't want this anymore.. passwords are only set from the frontend
		//update pwd in db
		$uid  = fq2id($user);
		$sqlu = "UPDATE `backup_mounts` SET `password` = '".$pass."' WHERE `backup_mounts_id` = ".$uid." LIMIT 1";
		$resu = mysql_query($sqlu) or logit(mysql_error()." [".$sqlu."]",3);
		*/
		
		logit( "Password for ".$user." changed into ".$pass."",1);
	}
	
	function setuserquota ($max_bytes, $user) {
		/*
			Function updates userquota on the system, and sets the group quota automatically
		*/
		global $config;
		$max_blocks = byte2block($max_bytes);	
		if(!$max_blocks){
			logit("Unable to convert user max_bytes '".$max_bytes."' to valid user max_blocks ('".$max_blocks."')",4);
		}
		
		// calculate the user quota and set it accordingly (first soft quota, then hard)
		exe("/usr/sbin/setquota -u " . $user . " " . floor ( $max_blocks * 0.9 ) . " " . $max_blocks . " 0 0 ". $config['partition']);
		logit( "User quota set to ".$max_blocks,1);	
		
		//also update the groupquota!!!
		$grp = getGroupFQFromUserFQ($user);	
		$grpquota = determineGroupQuota($grp);
		setgroupquota ( $grpquota, $grp);
	}
	
	function setgroupquota ($max_bytes, $group) {
		/*
			Function is called from setuserquota
		*/
		global $config;
		$max_blocks = byte2block($max_bytes);
		
		if(!$max_blocks){
			logit("Unable to convert group max_bytes '".$max_bytes."' to valid group max_blocks ('".$max_blocks."')",4);
		}
		// calculate the group quota and set it accordingly	
		exe("/usr/sbin/setquota -g " . $group . " " . floor ( $max_blocks * 0.9 ) . " " . $max_blocks . " 0 0 ". $config['partition']);
		logit ("Group quota set to ".$max_blocks,1);
	}

?>