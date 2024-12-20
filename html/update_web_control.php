<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	}
	header( 'Content-type: text/html; charset=utf-8' );

	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
	session_write_close();
	
	if($user_level=="admin") {
		if(isset($_POST['update'])) {
			echo "<span id=\"result\"></span>";
			if($_POST['update']=="yes") {
				echo "<pre>Updating...\r\n";
				ob_flush();
				flush();
				//retrieve $count to know how mant times to loop later
				exec('bash update.sh count', $count);
				ob_flush();
				flush();
				//we loop here so we can flush the output and view the update progress in the web control.
				for($n=1; $n<=$count[0]; $n++) {
					system('bash update.sh '.$n.'');
					ob_flush();
					flush();
				}
				echo "Done\r\n</pre>\r\n\r\n";
				echo "<script>document.getElementById(\"result\").innerHTML = \"Done! <a href=\\\"javascript:window.location = document.referrer;\\\">Go Back</a>\";</script>";
				ob_flush();
				flush();
			}
		} else {
			header("Location: ./login.php?POSTnotset");
			die();
		}
	} else {
		header("Location: ./login.php?notadmin");
		die();
	}
