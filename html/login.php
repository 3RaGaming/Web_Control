<?php
if(session_status()!=2) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	die();
}
//set debug?
if(isset($_REQUEST['debug'])) {
	if($_REQUEST['debug']=="true") {
		$_SESSION['debug'] = true;
		$debug = array();
		$debug[] = true;
	} else {
		unset($_SESSION['debug']);
	}
} elseif(isset($_SESSION['debug'])) {
	$debug = array();
	$debug[] = true;
}
//If logged in, and requested to logout... log them out and show login screen
if(isset($_SESSION['login'])) {
	if(isset($_REQUEST['logout'])) {
		unset($_SESSION['login']);
		$error = "logged_out";
	} else {
		if(isset($debug)) {
			$error = "logged_in";
		} else {
			//if not requesting to logout... Take back home
			header("Location: ./?d=server1");
			die();
		}
	}
}
session_write_close();
$redirect_url = urlencode("https://" .$_SERVER["HTTP_HOST"] . $_SERVER["SCRIPT_NAME"]);

	/* DEBUG */if(isset($debug)) {
		$debug[] = print_r($_SESSION, true);
		$debug[] = "login-get";
		$debug[] = print_r($_REQUEST, true);
	}

if(isset($_GET['code'])) {
	$code = $_GET['code'];

	$config_file = file_get_contents('/var/www/factorio/config.json');
	$json_config = json_decode($config_file, true);
	if($json_config) {
		$bottoken = $json_config["token"] ?? $error="json_missing_value";
		$client_id = $json_config["clientid"] ?? $error="json_missing_value";
		$client_secret = $json_config["clientsecret"] ?? $error="json_missing_value";
		$guildid = $json_config["guildid"] ?? $error="json_missing_value";
		$level1role = $json_config["adminrole"] ?? $error="json_missing_value";
		$level2role = $json_config["modrole"] ?? $error="json_missing_value";
	}
	if(!isset($error)) {
		$botheader = array();
		$botheader[] = 'Authorization: Bot '.$bottoken;

		$url = 'https://discordapp.com/api/oauth2/token?';
		$postField = 'grant_type=authorization_code&client_id='.urlencode($client_id).'&client_secret='.urlencode($client_secret).'&redirect_uri='.$redirect_url.'&code='.urlencode($code);
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

		/* DEBUG */if(isset($debug)) { 	$debug[] = "tokenJson" . __LINE__;
										$debug[] = print_r($tokenjson, true);
										$debug[] = curl_error($curlrqst0); }

		curl_close($curlrqst0);

		if(isset($tokenjson['access_token'])) {
			$token = $tokenjson['access_token'];
			/* DEBUG */if(isset($debug)) { $debug[] = "TOKEN SET"; }
		} else {
			$error = "access_token";
			/* DEBUG */if(isset($debug)) { $debug[] = "TOKEN NOT SET"; }
		}
		if(!isset($error)) {
			$tokenheader = array();
			$tokenheader[] = 'Content-Type application/json';
			$tokenheader[] = 'Authorization: Bearer '.$token;

			/* DEBUG */if(isset($debug)) {  $debug[] = "token header" . __LINE__;
											$debug[] = print_r($tokenheader, true); }

			$curlrqst1 = curl_init('https://discordapp.com/api/users/@me');
			curl_setopt($curlrqst1, CURLOPT_HTTPHEADER, $tokenheader);
			curl_setopt($curlrqst1, CURLOPT_RETURNTRANSFER, true);
			$userobject = curl_exec($curlrqst1);
			$userjson = json_decode($userobject, true);

			/* DEBUG */if(isset($debug)) {  $debug[] = "UserJson" . __LINE__;
											$debug[] = print_r($userjson, true);
											$debug[] = curl_error($curlrqst1); }

			curl_close($curlrqst1);

			if(isset($userjson["id"])) {
				$userid = $userjson["id"];
			} else {
				$error = "user_json_id";
			}

			$curlrqst2 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/members/'.$userid);
			curl_setopt($curlrqst2, CURLOPT_HTTPHEADER, $botheader);
			curl_setopt($curlrqst2, CURLOPT_RETURNTRANSFER, true);
			$memberobject = curl_exec($curlrqst2);
			$memberjson = json_decode($memberobject, true);
			/* DEBUG */if(isset($debug)) {  $debug[] = "MemberJson" . __LINE__;
											$debug[] = print_r($memberjson, true);
											$debug[] = curl_error($curlrqst2); }
			curl_close($curlrqst2);
			if (isset($memberjson['code'])&&($memberjson['code']==10007)) {
				$error = "member_no_exist";
			} elseif(!isset($memberjson["user"]["username"]) || !isset($memberjson["roles"])) {
				$error = "member_data_invalid";
			}

			if(!isset($error)) {
				$curlrqst3 = curl_init('https://discordapp.com/api/guilds/'.$guildid.'/roles');
				curl_setopt($curlrqst3, CURLOPT_HTTPHEADER, $botheader);
				curl_setopt($curlrqst3, CURLOPT_RETURNTRANSFER, true);
				$roleobject = curl_exec($curlrqst3);
				$rolejson = json_decode($roleobject, true);

				/* DEBUG */if(isset($debug)) {  $debug[] = "RolesJson" . __LINE__;
												$debug[] = print_r($rolejson, true);
												$debug[] = curl_error($curlrqst3); }

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
				if(isset($memberjson["roles"])) {
					foreach($memberjson["roles"] as $mkey => $mvalue) {
						if($mvalue == $level1id) $level1 = true;
						if($mvalue == $level2id) $level2 = true;
						if($level1 && $level2) break 1;
					}
				}

				if ($level1 || $userid == "129357924324605952" /* zacks id */) {
					/* DEBUG */if(isset($debug)) { $debug[] = "admin login verified!"; }
					$session['login']['user']=$memberjson["user"]["username"];
					$session['login']['level']="admin";
				} elseif ($level2) {
					/* DEBUG */if(isset($debug)) { $debug[] = "mod login verified!"; }
					$session['login']['user']=$memberjson["user"]["username"];
					$session['login']['level']="mod";
				} elseif($userid == "264805254758006801" ) {
					$session['login']['user']=$memberjson["user"]["username"];
					$session['login']['level']="guest";
				} else {
					$error = "unauthorized";
				}
			}
		}
	}
} /* DEBUG */elseif(isset($debug)) {
	$debug[] = "no CODE parameter found."; }

/**** Alternate Login Processing Below ****/
$userN="";
$passW="";
if(isset($_POST['uname'])) {
	$userN = addslashes($_POST['uname']);
}
if(isset($_POST['passw'])) {
	$passW = addslashes(md5(trim($_POST['passw'])));
}
if(isset($_POST['submit'])) {
	/* DEBUG */ if(isset($debug)) {
		$debug[] = "Alt-login post data triggered";
		$debug[] = "username:'$userN' - password:'$passW'";
	}
}
if(!empty($userN) && !empty($passW)) {
	$userlist = file ('/var/www/users.txt');
	$success = false;
	foreach ($userlist as $user) {
		$user_details = explode('|', $user);
		if ((strtolower($user_details[0]) == strtolower($userN)) && trim($user_details[1]) == $passW) {
			$userN = $user_details[0];
			$userL = $user_details[2];
			$success = true;
			break;
		}
	}
	if ($success) {
		if(isset($debug)) {
			$report = "With debug disabled, Session would have been created.";
			$debug[] = print_r($session, true);
		} else {
			$session['login']['user']=$userN;
			$session['login']['level']=trim($userL);
			//Allow login
		}
	} else {
		$error = "password";
	}
} elseif(isset($_POST['submit'])) {
	$error = "form_empty";
}
 /**** Handle Error Messages ****/
if(isset($error)) {
	switch($error) {
		case "unauthorized":
			$report = "You are not authorized to access this page";
			break;
		case "access":
			$report = "You must agree to provide access to your account";
			break;
		case "access_token":
			$report = "Error with discord API";
			break;
		case "member":
			$report = "You are not a member of the Discord Server";
			break;
		case "logged_out":
			$report = "You have been logged out";
			break;
		case "logged_in":
			$report = "You are already logged in";
			break;
		case "password";
			$report =  "Invalid username or password";
			break;
		case "form_empty";
			$report = "<br />I don't like no input<br />";
			break;
		default:
			$report = "Unknown Error Occurred - $error";
	}
} elseif(isset($session['login']['user'])&&isset($session['login']['level'])) {
	echo "attempted";
	if(isset($debug)) {
		$report = "With debug disabled, Session would have been created.";
		$debug[] = print_r($session, true);
	} else {
		if(session_status()!=2) { session_start(); }
		$_SESSION['login']['user'] = $session['login']['user'];
		$_SESSION['login']['level'] = $session['login']['level'];
		header("Location: ./index.php?d=server1");
		die();
	}
}
$config_file = file_get_contents('/var/www/factorio/config.json');
$json_config = json_decode($config_file, true);
$clientid = $json_config['clientid'];
/* DEBUG */ if(isset($debug)) {
	if(( isset($clientid) && $clientid == "PUT_YOUR_BOT_CLIENT_ID_HERE" )) {
		$debug[] = "Default JSON['clientid'] being used. Discord Auth unavailable.";
	}
}
?>
<html>
	<head>
		<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
		<link rel="stylesheet" media="all" href="assets/css/login.css" />
		<script>
			function show_hide(v_show, v_hide) {
				document.getElementById(v_show).style.display="block";
				document.getElementById(v_hide).style.display="none";
			}
			<?php
				echo "\t\t$(document).ready(function() {\xA";
				if( isset($_POST['submit']) || ( isset($clientid) && $clientid == "PUT_YOUR_BOT_CLIENT_ID_HERE" ) || !isset($clientid) ) {
					echo "\t\t\t\tshow_hide('login-alt','login-discord');\xA";
				}
				echo "\t\t\t\t";
				echo (empty($userN)?'$("#uname").attr("placeholder","username");':'$("#uname").val("'.$userN.'");');
				echo "\xA";
				echo "\t\t\t});\xA";
			?>
		</script>
	</head>
	<body>
		<div class="login-page">
			<div class="form">
				<span id="login-discord">
					<a href="#" onClick="show_hide('login-alt','login-discord');">Alternate Login</a><br /><br />
					<a id="" href="https://discordapp.com/oauth2/authorize?client_id=<?php echo $clientid; ?>&scope=identify%20guilds&redirect_uri=<?php echo $redirect_url; ?>&response_type=code">
					<img style="width: 100%;" src="./assets/img/3rabutton.png" alt="Login With Discord"/>
					</a>
				</span>
				<span id="login-alt" style="display: none;">
					<a href="#" onClick="show_hide('login-discord','login-alt');">Discord Login</a><br /><br />
					<form class="login-form" name="login" method="post">
						<input type="hidden" name="login" value="submit" />
						<input type="text" id="uname" name="uname" />
						<input type="password" name="passw" placeholder="password"/>
						<button onclick="document.login.submit();">login</button>
					</form>
				</span>
				<?php 	if(isset($report)) { echo "<br /><br />".$report; }
						if(isset($debug)) { echo "<br />debug enabled";}?>
			</div>
		</div>
	</body>
<?php
	if(isset($debug)) {
		echo "<pre>";
		print_r($debug);
		echo "</pre>";
	}
?>
</html>
