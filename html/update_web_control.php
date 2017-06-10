<?php
	if(session_status()!=2) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	} else {
		$session = $_SESSION;
		session_write_close();
		if($_SERVER["HTTPS"] != "on")
		{
			header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
			die();
		}
	}

	header( 'Content-type: text/html; charset=utf-8' );

	if(isset($session['login']['level'])) { $user_level = $session['login']['level']; }  else { $user_level = "viewonly"; }
	if(isset($session['login']['user'])) { $user_name = $session['login']['user']; }  else { $user_name = "guest"; }

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
				$file_name = "";
				for($n=1; $n<=$count[0]; $n++) {
					if($n == 2) {
						exec('bash update.sh '.$n.'', $file_name);
						$file_name = str_replace('.zip', '', $file_name[0]);
						if($file_name == "404") {
							echo "Repo not found. Halting update.\r\n";
							echo "\r\n-----------\r\n\r\n";
							break;
						} else {
							echo "filename:$file_name.zip downloaded\r\n";
							echo "\r\n-----------\r\n\r\n";
						}
					} else {
						system('bash update.sh '.$n.' '.$file_name.'');
					}
					ob_flush();
					flush();
				}
				echo "Done\r\n</pre>\r\n\r\n";
				echo "<script>document.getElementById(\"result\").innerHTML = \"Done! <a href=\\\"javascript:window.location = document.referrer;\\\">Go Back</a>\";</script>";
				ob_flush();
				flush();
			}
		} else {
			die('No post data sent');
		}
	} else {
		die('Not Admin');
	}
?>
