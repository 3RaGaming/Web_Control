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

	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }

	if($user_level=="viewonly") {
		die('Not allowed for view only');
	}
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		$server_select = "servertest";
	}
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				$server_dir = $base_dir . $server_select . "/";

				$server_settings_path = $server_dir . "server-settings.json";
				$server_settings_run_path = $server_dir . "running-server-settings.json";
				if(file_exists($server_settings_path)) {
					$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
					echo "<pre>$server_settings</pre>";
				}
				die();
			}
		}
	}
?>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a viewonly account, why upload a file, just to be told you can't do that?
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

		// This is for displaying the server name & password in an input box
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
			if($server_settings != NULL) {
				//Do we have a server
				if(isset($server_settings["name"])) {
					if($user_level == "viewonly") {
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
				} else {
					// Report file came back invalid
					echo "\t\t\tdocument.getElementById('server_name').value = \"#ERROR WITH SERVER NAME#\";\xA";
					echo "\t\t\t$('#server_name').attr('size',30);\xA"; 
				}
				if( isset($server_settings["game_password"]) && !empty($server_settings["game_password"]) ) {
					echo "\t\t\t$('#server_password').html('<i class=\"fa fa-lock\" aria-hidden=\"true\"></i>');\xA";
				} else {
					echo "\t\t\t$('#server_password').html('<i class=\"fa fa-unlock\" aria-hidden=\"true\"></i>');\xA";
				}
			} else {
				// Report file came back invalid
				echo "\t\t\tdocument.getElementById('server_name').value = \"#ERROR WITH server-settings.json#\";\xA";
				echo "\t\t\t$('#server_name').attr('size',40);\xA";
				echo "\t\t\t$('#server_password').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> config');\xA";
			}
		} else {
			// Report file came back invalid
			echo "\t\t\tdocument.getElementById('server_name').value = \"#ERROR WITH server-settings.json#\";\xA";
			echo "\t\t\t$('#server_name').attr('size',40);\xA";
			echo "\t\t\t$('#server_password').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> config');\xA";
		}
		echo "\t\t\t$('#logs_link').html('<a href=\"./logs.php#$server_select\" id=\"logs_link\">Logs</a>');\xA";
		echo "document.getElementById(\"logs_link\").href=\"logs.php#server_list-".$server_select."\";";
		if(isset($server_tab_list)) { echo $server_tab_list; }
		echo "\t\t})\xA";
?>
		function load_list(server) {
			$.get("server-settings.php?show=true&d=" + server, function(html) {
				// replace the "ajax'd" data to the table body
				$('#server_list-' + server).html(html);
				return false;
			});
		}
	</script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script src="https://use.fontawesome.com/674cd09dad.js"></script>
	<script type="text/javascript" language="javascript" src="assets/log-ui.js"></script>
	<style type="text/css">@import "assets/log-ui.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			<a href="./index.php">Home</a>&nbsp;-&nbsp;
			<input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
			<span id="server_password"></span>&nbsp;-&nbsp;
			<a href="./logs.php" id="logs_link">Logs</a>
			<!--<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />-->
			<div style="float: right;">
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- server files -->
		<div style="width: 92%; height: 99%; float: left;">
			<div id="server_list">
				<ul>
				</ul>
			</div>
		</div>
	</div>
</body>
</html>
