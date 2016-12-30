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
	
	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "guest"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		die('Error in server selection index.php');
	}
?>
</script>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a guest, why upload a file, just to be told you can't do that?
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		if(isset($_SESSION['login']['reload_report'])) {
			echo "\t\t\t$('#fileStatus').html('".$_SESSION['login']['reload_report']."');\xA";
			unset($_SESSION['login']['reload_report']);
		}
		if(isset($_SESSION['login']['cmd_history'][$server_select])) {
			echo "\t\t\this_array = ".json_encode($_SESSION['login']['cmd_history'][$server_select]).";\xA";
		}
		
		// This is for displaying the server name & password in an input box
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			// 
			$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
			if($server_settings != NULL) {
				//Do we have a server
				if(isset($server_settings["name"])) {
					if($user_level == "guest" ) {
						echo "\t\t\t$('#server_name').hide();\xA";
					} else {
						$server_name = htmlspecialchars($server_settings["name"]);
						$server_name_length = strlen($server_name);
						if($server_name_length<20) {
							$server_name_length = 20;
						}
						echo "\t\t\tdocument.getElementById('server_name').value = \"".addslashes($server_name)."\";\xA";
						echo "\t\t\t$('#server_name').attr('size',$server_name_length);\xA";
					}
					/*var_dump($server_settings);*/
				}
				if(isset($server_settings["game_password"])) {
					if($user_level == "guest" ) {
						echo "\t\t\t$('#server_password').hide();\xA";
					} else {
						$server_password = $server_settings["game_password"];
						if(!empty($server_password)) {
							$server_password_length = strlen($server_password);
							if($server_password_length<14) {
								$server_password_length = 14;
							}
							echo "\t\t\tdocument.getElementById('server_password').value = \"".addslashes($server_password)."\";\xA";
							echo "\t\t\t$('#server_password').attr('size',$server_password_length);\xA";
						}
					}
				}
			} else {
				// Report file came back invalid
				echo "\t\t\tdocument.getElementById('server_name').value = \"#ERROR WITH SERVER NAME#\";\xA";
				echo "\t\t\t$('#server_name').attr('size',30);\xA"; 
			}
		} else {
			// Report server-settings missing";
			echo "\t\t\tdocument.getElementById('server_name').value = \"#ERROR: server-settings.json NOT FOUND#\";\xA";
			echo "\t\t\t$('#server_name').attr('size',40);\xA";
		}
		echo "document.getElementById(\"logs_link\").href=\"logs.php#server_list-".$server_select."\";"
		if(isset($server_select_dropdown)) { echo $server_select_dropdown; } 
		echo "\t\t})\xA";
?>
	</script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/console.js"></script>
	<style type="text/css">@import "assets/base.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;
			<button onclick="server_sss('start')">Start</button>&nbsp; &nbsp;
			<button onclick="server_sss('status')">Status</button>&nbsp;-&nbsp;
			<button onclick="server_sss('stop')">Stop</button>&nbsp;-&nbsp;
			<input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
			<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />
			<button onclick="update_web_control(user_level);">Update Web Control</button>
			<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
				<input type="hidden" id="update" name="update" value="yes" />
			</form>
			<button onclick="force_kill('forcekill')">force kill</button>
			<a href="./logs.php" id="logs_link">Logs</a>
			<div style="float: right;">
				<select id="server_select"></select>&nbsp;-&nbsp;
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
			<table id="fileTable" class="tablesorter">
				<thead>
					<tr>
						<th><input type="checkbox" style="margin: 0; padding: 0; height:13px;" checked="false" /></th>
						<th>File</th>
						<th>Size</th>
						<th>Creation</th>
						<th>Editor</th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>
</body>
</html>
