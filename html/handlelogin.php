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
	$adminrole = $json_config["adminrole"];
	
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
	$memberjson = json_decode($memberobject, true);
	curl_close($curlrqst2);
	
	$curlrqst3 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/roles');
	curl_setopt($curlrqst3, CURLOPT_HTTPHEADER, $botheader);
	curl_setopt($curlrqst3, CURLOPT_RETURNTRANSFER, true);
	$roleobject = curl_exec($curlrqst3);
	$rolejson = json_decode($roleobject, true);
	curl_close($curlrqst3);
	
	$allowed = false;
	foreach($rolejson as $key => $value) {
		echo $key.' '.$value;
		if($rolejson[$key]["name"] == $adminrole) {
			echo 'Match Found';
			foreach($memberjson["roles"] as $mkey => $mvalue) {
				echo $mkey.' '.$mvalue;
				if($mvalue == $rolejson[$key]["id"]) {
					$allowed = true;
					break 2;
				}
			}
		}
	}
}

?>

<html>
	<head><title> Checking Discord Response </title></head>
	<body>
		<form method="post" name="gettoken">
			<input type="hidden" name="token" value="null" />
			<input type="hidden" name="error" value="null" />
		</form>
		<script type="text/javascript">
			var checkerror = window.location.hash.split("&")[0].split("=");
			var token;
			if (checkerror[0] == "#access_token") {
				document.forms["gettoken"].elements["token"].value = checkerror[1];
			} else {
				alert("Error occured");
				document.forms["gettoken"].elements["error"] = checkerror[1];
			}
			<?php if (!isset($_POST['token'])) echo 'document.forms["gettoken"].submit();'; ?>
		</script>
		<?php if (isset($_POST['token'])) {
			echo 'User Object: '.$userobject;
			echo '<br /><br />';
			echo 'User ID: '.$userid;
			echo '<br /><br />';
			echo 'Member Object: '.$memberobject;
			echo '<br /><br />';
			echo 'Role Object: '.$roleobject;
			echo '<br /><br />';
			if ($allowed) echo 'Accepted? True';
			if (!$allowed) echo 'Accepted? False';
		}
		?>
	</body>
</html>
