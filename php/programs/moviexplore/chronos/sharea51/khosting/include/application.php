<?
	function checkAllDependencies(){
		global $config;
		
		$failed = kFailingUnixLocationDependencies(true);
		$succes = kFailingUnixLocationDependencies(false);
		$all = count($failed)+count($succes);
		
		if(count($succes)){
			foreach($succes as $k=>$f){
				logit($f." dependency mett",0);
			}
		}	
		
		if(count($failed)){
			foreach($failed as $k=>$f){
				logit($f." dependency failed! cannot locate file!",3);
			}
		}
		
		return $all;
	}
	
	function kFailingUnixLocationDependencies($returnfailed = true,$whichfile = ""){
		//this function wil check the script it is called from,
		//to see if all file referces exist.
		//especially usefull when calling commands by their full path like:
		//e.g. /usr/bin/grep
		$buf = getFile( ($whichfile ? $whichfile : $_SERVER["PHP_SELF"]));
		$buf = explode("\n",$buf);	
		$buf = grep($buf,"/",true,false);
		$buf = grep($buf,"//",true,true);
		$buf = grep($buf,"/*",true,true);
		$buf = grep($buf,"*/",true,true);
		
		$keeparr = array();
		$filearr = array();
		foreach($buf as $k=>$l){
			$bfl = trim($l);
			$co = true;
			
			if(substr($bfl,0,1) == "#" || substr($bfl,0,2) == "//"){
				$co = false;
			}
			//allowed to add line, cause its no comment
			if($co){
				$wds = explode(" ",$bfl);
				foreach($wds as $wc=>$wd){
					if(substr_count($wd,"/") > 1 && substr_count($wd,"$") < 1 ){
						//scan word, which probably is a filepath
						$bfw = trim($wd);
						
						//cleanup filepath
						$bfw = str_replace("|","",$bfw);
						$bfw = str_replace('"','',$bfw);
						$bfw = str_replace("'","",$bfw);
						$bfw = str_replace(":","",$bfw);
						$bfw = str_replace(";","",$bfw);
						$bfw = str_replace("(","",$bfw);
						$bfw = str_replace(")","",$bfw);
						$bfw = str_replace("{","",$bfw);
						$bfw = str_replace("}","",$bfw);
						$bfw = str_replace("[","",$bfw);
						$bfw = str_replace("]","",$bfw);
						
						$bfw = substr($bfw,strpos($bfw,"/"),strlen($bfw));
						
						//validate root entry in filepath, to make sure this really is a path
						$cmdp = explode("/",$bfw);
						$rt = $cmdp[1];
						
						if(!is_numeric($rt) && strtolower($rt) != strtolower("dev") && trim($bfw)){
							//add to filepath array
							$filearr[] = trim($bfw);
						}
					}
				}
			}
		}
		
		$filearr = array_unique($filearr);
		sort($filearr);
		$reportarr = array();
		
		foreach($filearr as $k=>$fl){
			if (!file_exists($fl)){
				if ($returnfailed){
					$reportarr[]=$fl;
				}
			}
			else{
				if (!$returnfailed){
					$reportarr[]=$fl;
				}			
			}
		}
		
		return $reportarr;
	}
	

	function goMail($body="",$subject="",$from="",$to=""){
		global $config;
		
		$mail = array();
	
		
		if(!$to){
			$to = $config['mail_alert'];
		}
		if(!$subject){
			$subject = "Logentry from ".$config['backup_server_name'];
		}
		if(!$from){
			$from = str_replace('"','',$config['mail_from']);
		}
	
		$headers = "From: ".$from."\r\n";
		$headers .= "X-Sender: <".$from.">\r\n";
		$headers .= "Return-Path: <".$from.">\r\n";
		
		$to = str_replace(";",",",$to);
		$to = str_replace(" ","",$to);		
		$ms = explode(",",$to);
		foreach ($ms as $k=>$to){		
			mail(trim($to), $subject, $body, $headers);
		}
	}
	
	function logit( $message ,$errlevel=1 ) {
		/*
		Generic function to notify us about the errors that might occur
		This function has 5 levels of debugging, and will check wether the level
		is set for display, or mail. It will also check if the errors have to be
		written to the stdout or a specific log file.
		*/
		global $config;
		
		$arrLogLevels = $config['arrLogLevels'];
		
		$logstring = strtolower("[".date("Y-m-d H:i:s")."] " . str_pad($arrLogLevels[$errlevel],8," ",STR_PAD_LEFT) .": " . $message);
		$logstring = addslashes($logstring);
		
		if( $errlevel >= $config["skipdisplay"] ){
			//check if this level should be logged to the screen
			echo $logstring."\n";
		}
		if( $errlevel >= $config["skiplogging"] ){
			//check if this level should be logged to the file
			system ("echo \"".$logstring."\" >> ".$config['logfile']);
		}
		if( $errlevel >= $config["skipmailing"] ){
			//check if this level should be logged to the mailaddress
			goMail ($logstring);
		}
		
		if ($errlevel == 4){
			die();
		}
	}

	function exe($cmd){
		system ($cmd,$code);
		logit($cmd.":(".$code.")", 0);
	}
	
			
?>