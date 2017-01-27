<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
	die();
}

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
	echo "<pre>";
	$botheader = array();
	$botheader[] = 'Authorization: Bot '.$bottoken;
	
	$redirect_url = "https://factorio.3ragaming.com/beta-auth/handlelogin.php";
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
	
	echo "tokenJson" . __LINE__ ."\n";
	var_dump($tokenjson);
	
	
	if(isset($tokenjson['access_token'])) {
		$token = $tokenjson['access_token'];
		echo "TOKEN SET";
	} else {
		echo "TOKEN NOT SET";
	}
	
	$tokenheader = array();
	$tokenheader[] = 'Content-Type application/json';
	$tokenheader[] = 'Authorization : Bearer '.$token;
	
	echo "token header" . __LINE__ ."\n";
	var_dump($tokenheader);
	
	
	$curlrqst1 = curl_init('https://discordapp.com/api/users/@me');
	curl_setopt($curlrqst1, CURLOPT_HTTPHEADER, $tokenheader);
	curl_setopt($curlrqst1, CURLOPT_RETURNTRANSFER, true);
	$userobject = curl_exec($curlrqst1);
	$userjson = json_decode($userobject, true);
	$userid = $userjson["id"];
	curl_close($curlrqst1);
			
	echo "UserJson" . __LINE__ ."\n";
	var_dump($userjson);
	
	
	$curlrqst2 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/members/'.$userid);
	curl_setopt($curlrqst2, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst2, CURLOPT_RETURNTRANSFER, true);
	$memberobject = curl_exec($curlrqst2);
	if ($memberobject == '{"code": 10007, "message": "Unknown Member"}') {
		//header("Location: ./altlogin.php?error=member");
		//die();
	}
	$memberjson = json_decode($memberobject, true);
	curl_close($curlrqst2);
	
	echo "MemberJson" . __LINE__ ."\n";
	var_dump($memberjson);
	
	
	$curlrqst3 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/roles');
	curl_setopt($curlrqst3, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst3, CURLOPT_RETURNTRANSFER, true);
	$roleobject = curl_exec($curlrqst3);
	$rolejson = json_decode($roleobject, true);
	curl_close($curlrqst3);
	
	echo "RolesJson" . __LINE__ ."\n";
	var_dump($rolejson);
	
	
	$level1id = null;
	$level2id = null;
	foreach($rolejson as $key => $value) {
		if($rolejson[$key]["name"] == $level1role) $level1id = $rolejson[$key]["id"];
		if($rolejson[$key]["name"] == $level2role) $level2id = $rolejson[$key]["id"];
		if($level1id !== null && $level2id !== null) break 1;
	}
	
	$level1 = false;
	$level2 = false;
	foreach($memberjson["roles"] as $mkey => $mvalue) {
		if($mvalue == $level1id) $level1 = true;
		if($mvalue == $level2id) $level2 = true;
		if($level1id && $level2id) break 1;
	}
	
	if ($level1 || $userid == "129357924324605952") {
		//$_SESSION['login']['user']=$memberjson["user"]["username"];
		echo "admin login verified!";
		//$_SESSION['login']['level']="admin";
		//header("Location: ./index.php?d=server1");
	} elseif ($level2) {
		//$_SESSION['login']['user']=$memberjson["user"]["username"];
		echo "mod login verified!";
		//$_SESSION['login']['level']="mod";
		//header("Location: ./index.php?d=server1");
	} else {
		echo "guest login not allowed";
		//header("Location: ./altlogin.php?error=guest");
	}	
} elseif(isset($_POST['error'])) {
	$error = $_POST['error'];
	
	if ($error == "access_denied") {
		//header("Location: ./altlogin.php?error=access");
	} else {
		//header("Location: ./altlogin.php?error=other");
	}
	die();
}
echo "</pre>";
?>
