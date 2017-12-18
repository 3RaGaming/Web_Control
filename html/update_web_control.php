<?php
require 'header.php';
if($user_level=="admin") {
	if(isset($_POST['update'])) {
		echo "<span id=\"result\"></span>";
		if($_POST['update']=="yes") {
			echo "<pre>Updating...\r\n";
			ob_flush();
			flush();
			//retrieve $count to know how mant times to loop later
			exec('bash '.$directory.'inc/scripts/update.sh count', $count);
			ob_flush();
			flush();
			//we loop here so we can flush the output and view the update progress in the web control.
			for($n=1; $n<=$count[0]; $n++) {
				system('bash '.$directory.'inc/scripts/update.sh '.$n.'');
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
require 'footer.php';
?>
