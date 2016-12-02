<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	} else {
		if($_SERVER["HTTPS"] != "on")
		{
			header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
			die();
		}
	}
	header( 'Content-type: text/html; charset=utf-8' );

	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "guest"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }

	if($user_level=="admin") {
		if(isset($_POST['update'])) {
			if($_POST['update']=="yes") {
				echo "<pre>Updating...\r\n";
				ob_flush();
				flush();
				$count = system('bash update.sh count');
				ob_flush();
				flush();
				for($n=1; $n<=$count; $n++) {
					system('bash update.sh '.$n.'');
					ob_flush();
					flush();
				}
				echo "Done\r\n</pre>";
				ob_flush();
				flush();
			}
		}
	} else {
		header("Location: ./login.php");
		die();
	}
?>