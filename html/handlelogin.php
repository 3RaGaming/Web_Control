<?php
	if(!isset($_SESSION)) { session_start(); }
	if(isset($_SERVER["HTTPS"]) == false)
	{
		die('Must use HTTPS');
	}

	/* DEBUG */if(isset($debug)) { echo "<pre>";
		echo var_dump($_SESSION);
		echo "login\nget";
		echo var_dump($_GET);
	}
	session_write_close();
if(isset($_GET['code'])) {
	$code = $_GET['code'];
	
	$config_file = file_get_contents('/var/www/factorio/config.json');
	$json_config = json_decode($config_file, true);
	$bottoken = $json_config["token"];
	$client_id = $json_config["clientid"];
	$client_secret = $json_config["clientsecret"];
	$guildid = $json_config["guildid"];
	$level1role = $json_config["adminrole"];
	$level2role = $json_config["modrole"];
	$botheader = array();
	$botheader[] = 'Authorization: Bot '.$bottoken;
	
	$redirect_url = "https://factorio.3ragaming.com/beta-auth/login.php";
	$url = 'https://discordapp.com/api/oauth2/token?';
	$postField = 'grant_type=authorization_code&client_id='.urlencode($client_id).'&client_secret='.urlencode($client_secret).'&redirect_uri='.urlencode($redirect_url).'&code='.urlencode($code);
	//echo $postField;
	$options = array(CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_FOLLOWLOCATION => 1,
					CURLOPT_POST => true,
					CURLOPT_POSTFIELDS => $postField );
	
	$curlrqst0 = curl_init();
	curl_setopt_array($curlrqst0, $options);
	$tokenobject = curl_exec($curlrqst0);
	$tokenjson = json_decode($tokenobject, true);
	curl_close($curlrqst0);
	/* DEBUG */if(isset($debug)) { echo "tokenJson" . __LINE__ ."\n";
									var_dump($tokenjson); }
	
	if(isset($tokenjson['access_token'])) {
		$token = $tokenjson['access_token'];
		/* DEBUG */if(isset($debug)) { echo "TOKEN SET\n"; }
	} else {
		/* DEBUG */if(isset($debug)) { echo "TOKEN NOT SET\n"; }
	}
	
	$tokenheader = array();
	$tokenheader[] = 'Content-Type application/json';
	$tokenheader[] = 'Authorization: Bearer '.$token;
	
	/* DEBUG */if(isset($debug)) { echo "token header" . __LINE__ ."\n";
									var_dump($tokenheader); }
	
	$curlrqst1 = curl_init('https://discordapp.com/api/users/@me');
	curl_setopt($curlrqst1, CURLOPT_HTTPHEADER, $tokenheader);
	curl_setopt($curlrqst1, CURLOPT_RETURNTRANSFER, true);
	$userobject = curl_exec($curlrqst1);
	$userjson = json_decode($userobject, true);
	$userid = $userjson["id"];
	curl_close($curlrqst1);
			
	/* DEBUG */if(isset($debug)) { echo "UserJson" . __LINE__ ."\n";
									var_dump($userjson); }
	
	
	$curlrqst2 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/members/'.$userid);
	curl_setopt($curlrqst2, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst2, CURLOPT_RETURNTRANSFER, true);
	$memberobject = curl_exec($curlrqst2);
	if ($memberobject == '{"code": 10007, "message": "Unknown Member"}') {
		$error = "member";
	}
	if($error!="member" || isset($debug)) {
		$memberjson = json_decode($memberobject, true);
		curl_close($curlrqst2);
		
		/* DEBUG */if(isset($debug)) { echo "MemberJson" . __LINE__ ."\n";
										var_dump($memberjson); }
		
		$curlrqst3 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/roles');
		curl_setopt($curlrqst3, CURLOPT_HTTPHEADER, $botheader);
		curl_setopt($curlrqst3, CURLOPT_RETURNTRANSFER, true);
		$roleobject = curl_exec($curlrqst3);
		$rolejson = json_decode($roleobject, true);
		curl_close($curlrqst3);
		
		/* DEBUG */if(isset($debug)) { echo "RolesJson" . __LINE__ ."\n";
										var_dump($rolejson); }
		
		$level1id = null;
		$level2id = null;
		foreach($rolejson as $key => $value) {
			if($rolejson[$key]["name"] == $level1role) $level1id = $rolejson[$key]["id"];
			if($rolejson[$key]["name"] == $level2role) $level2id = $rolejson[$key]["id"];
			if($level1id !== null && $level2id !== null) break 1;
		}
		
		$level1 = false;
		$level2 = false;
		if(isset($memberjson["roles"])) {
			foreach($memberjson["roles"] as $mkey => $mvalue) {
				if($mvalue == $level1id) $level1 = true;
				if($mvalue == $level2id) $level2 = true;
				if($level1id && $level2id) break 1;
			}
		}
		
		if ($level1 || $userid == "129357924324605952") {
			/* DEBUG */if(isset($debug)) { echo "admin login verified!"; }
			$session['login']['user']=$memberjson["user"]["username"];
			$session['login']['level']="admin";
		} elseif ($level2) {
			/* DEBUG */if(isset($debug)) { echo "mod login verified!"; }
			$session['login']['user']=$memberjson["user"]["username"];
			$session['login']['level']="mod";
		} else {
			$error = "guest";
		}
	}
} elseif(isset($_GET['error'])) {	
	if ($_GET['error'] == "access_denied") {
		$error = "access";
	}
}
/* DEBUG */if(isset($debug)) { echo "</pre>"; }
?>
