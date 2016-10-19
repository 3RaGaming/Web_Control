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
	</script>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/console.php?d=<?php echo $server_select; ?>"></script>
	<script type="text/javascript">
	function command_history(args) {
<?php
		if(isset($_SESSION['login']['cmd_history'][$server_select])) {
			//his_array = ["/players", "/c print(\"hello\")"];
			echo "his_array = ".json_encode($_SESSION['login']['cmd_history'][$server_select]).";\xA";
		}
?>
		if (typeof his_ind == 'undefined') {
			his_ind = -1;
		}
		if (typeof his_array == 'undefined') {
			his_array = [];
		}
		his_cou = his_array.length - 1;
		if (his_ind == -1) { sav_val = document.getElementById('command').value; }
		if (args == "up") {
			if (his_ind <= 23 && his_ind < his_cou) {
				his_ind = his_ind + 1;
			}
			if(his_ind == -1) {
				dis_val = sav_val;
			} else {
				dis_val = his_array[his_ind];
			}
			document.getElementById('command').value = dis_val;
		} else if (args == "down") {
			if (his_ind > 0) {
				his_ind = his_ind - 1;
				dis_val = his_array[his_ind];
			} else {
				his_ind = -1;
				dis_val = sav_val;
			}
			document.getElementById('command').value = dis_val;
		} else if (args == "add") {
			command_history_add();
		}
		
		//PHp injecting command history breaks this function. Haven't looked into why yet.
		function command_history_add() {
			if (args == "add") {
				his_array.unshift(document.getElementById('command').value);
				if (his_array.length > 25) {
					his_array.pop();
				}
			}
			his_ind = -1;
			document.getElementById('command').value = "";
		}
	}
	</script>
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
