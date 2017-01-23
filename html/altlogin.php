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

?>
<html>
<head>
<link rel="stylesheet" media="all" href="assets/login.css" />
</head>
<body>
<div class="login-page">
  <div class="form">
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