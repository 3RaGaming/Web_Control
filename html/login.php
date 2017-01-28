<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false) {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
	die();
}
//If logged in, and requested to logout... log them out and show login screen
if(isset($_SESSION['login'])) {
	if(isset($_REQUEST['logout'])) {
		unset($_SESSION['login']);
		$report = "<br />You have been logged out</br >";
	} else {
		//if not requesting to logout... Take back home
		//header("Location: ./?d=server1");
		die();
	}
}
if(isset($_REQUEST['debug'])) {
	echo "requestDebug";
	if($_REQUEST['debug']==true) {
		$_SESSION['debug'] = true;
		$debug = true;
		
	echo "sessionSetDebug";
	} else {
		unset($_SESSION['debug']);
		
	echo "sessionUnsetDebug";
	}
} elseif(isset($_SESSION['debug'])) {
	$debug = true;
	
	echo "SessionFoundDebug";
}
	var_dump($_SESSION);

include('./handlelogin.php');

if(isset($error)) {
	if ($error == "guest") { $report = "We do not have a guest page available at this time."; }
	elseif ($error == "access") { $report = "You must agree to provide access to your account."; }
	elseif ($error == "member") { $report = "You are not a member of the 3Ra Discord Server.";}
	else{ $report = "Unknown Error Occurred - $error"; }
} elseif(isset($session['login']['user'])&&isset($session['login']['level'])) {
	//if(isset($debug)) {
		//echo "With debug disabled, Session will be created here.";
		var_dump($session);
	//} else {
		$_SESSION['login']['user'] = $session['login']['user'];
		$_SESSION['login']['level'] = $session['login']['level'];
		//header("Location: ./index.php?d=server1");
		var_dump($_SESSION['login']['user']);
		var_dump($_SESSION['login']['level']);
		var_dump($session['login']['user']);
		var_dump($session['login']['level']);
	//}
}
//session_write_close();

if(!isset($clientid)) {
	$config_file = file_get_contents('/var/www/factorio/config.json');
	$json_config = json_decode($config_file, true);
	$clientid = $json_config['clientid'];
}

?>
<html>
<head>
<link rel="stylesheet" media="all" href="assets/login.css" />
</head>
<body>
<div class="login-page">
  <div class="form">
	<?php
	//TODO - Fix the styling issue here so that the button looks good
	?>
    <a href = "https://discordapp.com/oauth2/authorize?client_id=<?PHP echo $clientid; ?>&scope=identify%20guilds&redirect_uri=https%3A%2F%2Ffactorio.3ragaming.com%2Fbeta-auth%2Flogin.php&response_type=code">
	  <img style="width: 100%;" src="./3rabutton.png" alt="Login With Discord"/>
	</a>
	<?php 	if(isset($report)) { echo "<br />".$report; }
			if(isset($debug)) { echo "<br />debug enabled";}?>
  </div>
</div>
</body>
</html>
<?php
//End login page
?>