<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
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
		exit();
		die();
	}
}

if(isset($_REQUEST['error'])) {
	$error = $_REQUEST['error'];
	if ($error == "guest") $report = "We do not have a guest page available at this time.";
	if ($error == "access") $report = "You must agree to provide access to your account.";
	if ($error == "member") $report = "You are not a member of the 3Ra Discord Server.";
	if ($error == "other") $report = "Unknown Error Occurred";
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
    <a href = "https://discordapp.com/oauth2/authorize?client_id=271167532340084736&scope=identify%20guilds&redirect_uri=https%3A%2F%2Ffactorio.3ragaming.com%2Fbeta-auth%2Fhandlelogin.php&response_type=token">
	  <img src="./3rabutton.png" alt="Login With Discord"/>
	</a>
	<?php if(isset($report)) { echo $report; } ?>
  </div>
</div>
</body>
</html>
<?php
//End login page
?>