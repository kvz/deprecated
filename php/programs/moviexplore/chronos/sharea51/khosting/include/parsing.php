<?
	function grep($arr, $pattern,$insensitive=true,$v=false){
		$keeparr = array();
		foreach($arr as $k=>$l){
			$keep=false;
			if($insensitive && substr_count(strtolower($l),strtolower($pattern))){
				$keep=true;
			}
			elseif(!$insensitive && substr_count($l,$pattern)){
				$keep=true;
			}
			
			if($v){$keep = !$keep;}
			
			if($keep){
				$keeparr[] = $l;
			}
		}
		return $keeparr;
	}
	
	function genPass($pl = 8){
		$a = "abcdefghjkpqrstuvwxyzABCDEFGHJKPQRSTUVWXYZ23456789";
		$p = "";
		for ($i=0;$i<$pl;$i++ ){
			$p .= substr($a,rand(0,strlen($a)-2),1);
		}
		return $p;
	}
	
	function endSlash($strData){
		if (substr($strData,-1) != "/"){
			$strData = $strData."/";
		}
		return $strData;
	}	
	
	function getExtension($f){
		$image_base = explode(".",$f);
		return $image_base[count($image_base)-1];
	}
	function getBaseName($f){
		return str_replace(".".getExtension($f),"",$f);
	}
	
	
?>