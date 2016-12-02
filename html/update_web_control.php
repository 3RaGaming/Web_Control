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
				echo "Updating... <br />";
				flush();
				ob_flush();
				sleep(5);
				echo "Done <br />";
			}
		}
	} else {
		header("Location: ./login.php");
		die();
	}
?>