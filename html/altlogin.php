<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
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
/* DEBUG */if(isset($debug)) {
	$debug[] = print_r($_SESSION, true);
	$debug[] = "login-get";
	$debug[] = print_r($_REQUEST, true);
}

$userN="";
$passW="";
if(isset($_POST['uname'])) {
	$userN = addslashes($_POST['uname']);
}
if(isset($_POST['passw'])) {
	$passW = addslashes(md5(trim($_POST['passw'])));
}
 /* DEBUG */ if(isset($debug)) {
	$debug[] = "$userN - $passW";
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
			if(session_status()!=2) { session_start(); }
			$_SESSION['login']['user']=$userN;
			$_SESSION['login']['level']=trim($userL);
			//Send home if logged in
			header("Location: ./index.php?d=server1");
			die();
		}
	} else {
		$report =  "<br />You have entered the wrong username or password. Please try again.<br />";
	}
} elseif(isset($_POST['submit'])) {
	$report = "<br />I don't like no input<br />";
}

?>
<html>
<head>
<link rel="stylesheet" media="all" href="assets/css/login.css" />
</head>
<body>
<div class="login-page">
	<div class="form">
		<form class="login-form" name="login" method="post">
			<input type="hidden" name="login" value="submit" />
			<input type="text" name="uname" <?php echo (empty($userN)?'placeholder="username"':'value="'.$userN.'"'); ?> />
			<input type="password" name="passw" placeholder="password"/>
			<button onclick="document.login.submit();">login</button>
		</form>
	<?php 	if(isset($report)) { echo "\t\t<br /><br />\t\t".$report; }
			if(isset($debug)) { echo "\t\t<br />\t\tdebug enabled";}?>
  </div>
</div>
</body>
<?php
	if(isset($debug)) {
		echo "<pre>";
		print_r($debug);
		echo "</pre>";
	}
die();
//End login page
?>