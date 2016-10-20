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
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include(getcwd().'/getserver.php');
	if(!isset($server_select)) {
		die('Error in server selection index.php');
	}
	
	if(file_exists("repo_list.txt")) {
		$server_version_dropdown = "";
		$handle = fopen("repo_list.txt", "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$server_version_dropdown = $server_version_dropdown . '<option id="'.$line.'">'.$line.'</option>';
			}
			fclose($handle);
		}
	}
?>
</script>
<html>
<head>
	<style type="text/css">@import "assets/base.css";</style>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a guest, why upload a file, just to be told you can't do that?
		var user_level = "<?php if(isset($_SESSION['login']['level'])) { echo $_SESSION['login']['level']; }  else { echo "guest"; } ?>";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		$(document).ready(function() {
		<?php
		if(isset($_SESSION['login']['reload_report'])) {
			echo "$('#fileStatus').html('".$_SESSION['login']['reload_report']."')";
			unset($_SESSION['login']['reload_report']);
		}
		if(isset($_SESSION['login']['cmd_history'][$server_select])) {
			echo "his_array = ".json_encode($_SESSION['login']['cmd_history'][$server_select]).";\xA";
		}
		?>
		}
	</script>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/console.js"></script>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <?php echo $_SESSION['login']['user']; ?>&nbsp;-&nbsp;
			<button onclick="server_sss('start');">Start</button>&nbsp;-&nbsp;
			<button onclick="server_sss('status');">Status</button>&nbsp;-&nbsp;
			<button onclick="server_sss('stop');">Stop</button>&nbsp;-&nbsp;
			<input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
			<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />
			<select id="server_version"><?php if(isset($server_version_dropdown)) { echo $server_version_dropdown; } ?></select>
			<div style="float: right;">
				<select id="server_select"><?php if(isset($server_select_dropdown)) { echo $server_select_dropdown; } ?></select>&nbsp;-&nbsp;
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- console and chat windows -->
		<div style="width: 52%; height: 99%; float: left;">
			<textarea id="console" style="width: 98%; height: 46%;"></textarea>
			<textarea id="chat" style="width: 98%; height: 46%;"></textarea><br />
			<input type="text" id="command" placeholder="" style="width: 98%;" />&nbsp;
			<button id="command_button">Send</button>
		</div>
		<!-- server files -->
		<div style="width: 46%; height: 99%; float: left;">
			<div>
				<input type="file" name="upload_file" id="upload_file" style="display: none;">
				<button id="upload_button" name="upload_button" style="background-color: #ffffff;">Upload</button>
				<button id="Transfer" style="background-color: #ffffff;">Transfer</button>&nbsp;:&nbsp;
				<button id="archive" style="background-color: #ffffff;">Archive</button>&nbsp;:&nbsp;
				<button id="delete" style="background-color: #ffcccc;">Delete</button>
				<a id="fileStatus"></a>
				<progress id="prog" value="0" max="100.0" style="display: none;"></progress>
			</div>
			<?php $cur_serv=$server_select; include(getcwd().'/files.php'); ?>
		</div>
	</div>
</body>
</html>
