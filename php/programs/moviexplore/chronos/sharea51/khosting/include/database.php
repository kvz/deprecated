<?
	function getServerName($sid,$short=false){
		$sql3 = "SELECT name FROM backup_server WHERE backup_server_id = ".$sid;
		$res3 = @mysql_query($sql3);
		$row3 = @mysql_fetch_array($res3);
		
		if($short){
			$p = explode(".",$row3["name"]);
			return $p[0];
		}
		else{
			return $row3["name"];
		}
	}
?>