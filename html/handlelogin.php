<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
	die();
}

if(isset($_POST['token'])) {
	$token = $_POST['token'];
	
	$config_file = file_get_contents('/var/www/factorio/config.json');
	$json_config = json_decode($config_file, true);
	$bottoken = $json_config["token"];
	$guildid = $json_config["guildid"];
	$level1role = $json_config["adminrole"];
	$level2role = $json_config["modrole"];
	
	$tokenheader = array();
	$tokenheader[] = 'Authorization: Bearer '.$token;
	$botheader = array();
	$botheader[] = 'Authorization: Bot '.$bottoken;
	
	$curlrqst1 = curl_init('https://discordapp.com/api/users/@me');
	curl_setopt($curlrqst1, CURLOPT_HTTPHEADER, $tokenheader);
	curl_setopt($curlrqst1, CURLOPT_RETURNTRANSFER, true);
	$userobject = curl_exec($curlrqst1);
	$userjson = json_decode($userobject, true);
	$userid = $userjson["id"];
	curl_close($curlrqst1);
	
	$curlrqst2 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/members/'.$userid);
	curl_setopt($curlrqst2, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst2, CURLOPT_RETURNTRANSFER, true);
	$memberobject = curl_exec($curlrqst2);
	if ($memberobject == '{"code": 10007, "message": "Unknown Member"}') {
		//Redirect back to login screen with message saying that you must be a member of the Discord server
		die();
	}
	$memberjson = json_decode($memberobject, true);
	curl_close($curlrqst2);
	
	$curlrqst3 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/roles');
	curl_setopt($curlrqst3, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst3, CURLOPT_RETURNTRANSFER, true);
	$roleobject = curl_exec($curlrqst3);
	$rolejson = json_decode($roleobject, true);
	curl_close($curlrqst3);
	
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
		//Login with admin level access
	} elseif ($level2) {
		//Login with mod level access
	} else {
		//Redirect back to login screen with message stating that a guest version of this page is not yet available
		die();
	}
	
} elseif(isset($_POST['error'])) {
	$error = $_POST['error'];
	
	if ($error == "access_denied") {
		//Redirect back to login screen with message stating you must give permission to use this
	} else {
		//Redirect back to login screen with error message
	}
	die();
}

?>

<html>
	<head><title> Checking Discord Response </title></head>
	<body>
		<form method="post" name="gettoken">
			<input type="hidden" name="token" />
		</form>
		<form method="post" name="geterror">
			<input type="hidden" name="error" />
		</form>
		<script type="text/javascript">
			var checkerror = window.location.hash.split("&")[0].split("=");
			var token;
			if (checkerror[0] == "#access_token") {
				document.forms["gettoken"].elements["token"].value = checkerror[1];
				<?php if (!isset($_POST['token'])) echo 'document.forms["gettoken"].submit();'; ?>
			} else {
				document.forms["gettoken"].elements["error"] = checkerror[1];
				<?php if (!isset($_POST['token'])) echo 'document.forms["geterror"].submit();'; ?>
			}
		</script>
	</body>
</html>
