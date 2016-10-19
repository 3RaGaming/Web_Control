<?php
if(!isset($_SESSION)) { session_start(); }
if($_SERVER["HTTPS"] != "on")
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
	die();
}
//If logged in, and requested to logout... log them out and show login screen
if(isset($_SESSION['login'])) {
	if(isset($_REQUEST['logout'])) {
		unset($_SESSION['login']);
		$report = "<br />You have been logged out</br >";
	} else {
		//if not requesting to logout... Take back home
		header("Location: ./?d=server1");
	}
}

$userN="";
$passW="";
if(isset($_POST['uname'])) {
	$userN = addslashes($_POST['uname']);
}
if(isset($_POST['passw'])) {
	$passW = addslashes(md5(trim($_POST['passw'])));
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
		$_SESSION['login']['user']=$userN;
		$_SESSION['login']['level']=$userL;
		//Send home if logged in
		//header("Location: ./?d=server1");
		die($userL);
	} else {
		$report =  "<br />You have entered the wrong username or password. Please try again.<br />";
	}
} elseif(isset($_POST['submit'])) {
	$report = "<br />I don't like no input<br />";
}

?>
<html>
<head>
<link rel="stylesheet" media="all" href="assets/login.css" />
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
	<?php if(isset($report)) { echo $report; } ?>
  </div>
</div>
</body>
</html>
<?php
//End login page
?>