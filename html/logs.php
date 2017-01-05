<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	} else {
		if(isset($_SERVER["HTTPS"]) == false)
		{
			header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
			die();
		}
	}
	
	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "guest"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
	
	if($user_level=="guest") {
		die('Not allowed for guests');
	}
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		header("Location: ./login.php");
		die('Error in server selection index.php');
	}
	
	// function to print files size in human-readable form
	function human_filesize($file, $decimals = 2) {
		$bytes = filesize($file);
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				$server_dir = $base_dir . $server_select;
				$full_dir = $server_dir . "/logs";
				foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
					$file_full_path = "$full_dir/$file";
					$size = human_filesize("$file_full_path");
					$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
					echo " <a href=\"#\" onClick=\"Download('logs.php?d=".$server_select."&download=".$file."')\">$file</a> - $size - $date <br />";
				}
				die();
			}
		}
		if(isset($_REQUEST['filename'])) {
			$file_path = $base_dir . $server_select . "/" . $_REQUEST['filename'];
			if(file_exists($file_path)) {
				#something
			}
		}
	}
?>
</script>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		<?php if(isset($server_select)) { echo "\t\t$(\"logs\").attr(\"href\", \"./logs.php?$server_select\");\xA"; } ?>
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a guest, why upload a file, just to be told you can't do that?
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		
		echo "\t\t\t$('#welcome_user').text(user_name);\xA";
		if(isset($server_tab_list)) { echo $server_tab_list; } 
		echo "\t\t})\xA";
?>
	</script>
	<script type="text/javascript" language="javascript" src="assets/log-ui.js"></script>
	<style type="text/css">@import "assets/log-ui.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;
			<a href="./index.php">Home</a>
			<div style="float: right;">
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- server files -->
		<div style="width: 92%; height: 99%; float: left;">
			<div id="server_list">
				<ul>
					<li><a href="#server_list-Managepgm">Managepgm</a></li>
				</ul>
				<div id="server_list-Managepgm">Dynamic tab for Managepgm</div>
			</div>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>
</body>
</html>
