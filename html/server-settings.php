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
					$disabled = array('token', 'username', 'password');
					$replace_this = array('require_user_verification', 'max_upload_in_kilobytes_per_second', 'ignore_player_limit_for_returning_players', 'only_admins_can_pause_the_game', 'afk_autokick_interval', '_');
					$replace_with_that = array('verify users', 'upload kbps', 'ignore player limit', 'admin pause only', 'afk autokick', ' ');
					//$doublespan = array('name', 'description', 'tags', 'admins');
					$doublespan = array();
					echo "<form id=\"$server_select\" >";
					echo "<span id=\"error_msg\"></span>";
					echo "<table>";
					foreach($server_settings as $key => $value) {
						if(strpos($key, '_comment') === false && !in_array($key, $disabled)) {
							if(in_array($key, $doublespan)) {
								echo "<tr><td colspan=2>";
								$col = "";
							} else {
								echo "<tr><td>";
								$col = "</td><td>";
							}
							$display = str_replace($replace_this, $replace_with_that, $key);
							if(is_string($value)||is_int($value)) {
								if($key=="allow_commands") {
									if($value=="true") {
										echo "$display:$col<select name=\"$key\"><option value=admins-only>Admins Only</option><option value=true selected>True</option><option value=false>False</option></select><br />";
									} elseif($value=="false")  {
										echo "$display:$col<select name=\"$key\"><option value=admins-only>Admins Only</option><option value=true>True</option><option value=false selected>False</option></select><br />";
									} else {
										echo "$display:$col<select name=\"$key\"><option value=admins-only selected>Admins Only</option><option value=true>True</option><option value=false>False</option></select><br />";
									}
								} else {
									echo "$display:$col<input type=text name=\"$key\" value=\"$value\" size=\"".strlen($value)."\" /><br />";
								}
							} elseif(is_array($value)) {
								if($key == "visibility") {
									echo "$display:$col";
									foreach($value as $sub_key => $sub_value) {
										if($sub_value=="true") {
											echo "$sub_key: <select name=\"$key-$sub_key\"><option value=true selected>True</option><option value=false>False</option></select> ";
										} else {
											echo "$sub_key: <select name=\"$key-$sub_key\"><option value=true>True</option><option value=false selected>False</option></select> ";
										}
									}
									echo "<br />";
								} else {
									echo "$display:$col";
									$sub_value = "";
									if($value!="") {
										$sub_value = implode(", ", $value);
									}
									echo "<input type=text name=\"$key\" value=\"$sub_value\" size=\"".strlen($sub_value)."\" /> ";
									echo "<br />";
								}
							} elseif(is_bool($value)) {
								if($value==true) {
									echo "$display:$col<select name=\"$key\"><option value=true selected>True</option><option value=false>False</option></select><br />";
								} else {
									echo "$display:$col<select name=\"$key\"><option value=true>True</option><option value=false selected>False</option></select><br />";
								}
							} else {
								echo "$key:$col";
								var_dump($value);
								echo "<br />";
							}
							echo "</td>";
						}
					}
					echo "</table>";
					echo "<input type=\"hidden\" name=\"server_select\" value=\"".$server_select."\" /></form>";
					echo "<input type=\"button\" id=\"$server_select\" name=\"submit\" value=\"Save Config\" onclick=\"return validate('$server_select');\" /></form>";
					echo "<br /><span id=\"$server_select-return_output\"></span>";
					echo "<pre>";
					var_dump($server_settings);
					echo "</pre>";
				}
			}
			die();
		} elseif(isset($_REQUEST['server_select'])) {
			echo "Start!<br />";
			if(isset($_REQUEST['name'])) { echo "name - ".$_REQUEST['name']." - is set!"; } else { echo "name - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['description'])) { echo "description - ".$_REQUEST['description']." - is set!"; } else { echo "description - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['tags'])) { echo "tags - ".$_REQUEST['tags']." - is set!"; } else { echo "tags - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['max_players'])) { echo "max_players - ".$_REQUEST['max_players']." - is set!"; } else { echo "max-players - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['visibility-public'])) { echo "visibility-public - ".$_REQUEST['visibility-public']." - is set!"; } else { echo "visibility-public - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['visibility-lan'])) { echo "visibility-lan - ".$_REQUEST['visibility-lan']." - is set!"; } else { echo "visibility-lan - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['game_password'])) { echo "game_password - ".$_REQUEST['game_password']." - is set!"; } else { echo "game_password - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['require_user_verification'])) { echo "require_user_verification - ".$_REQUEST['require_user_verification']." - is set!"; } else { echo "require_user_verification - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['max_upload_in_kilobytes_per_second'])) { echo "max_upload_in_kilobytes_per_second - ".$_REQUEST['max_upload_in_kilobytes_per_second']." - is set!"; } else { echo "max_upload_in_kilobytes_per_second - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['ignore_player_limit_for_returning_players'])) { echo "ignore_player_limit_for_returning_players - ".$_REQUEST['ignore_player_limit_for_returning_players']." - is set!"; } else { echo "ignore_player_limit_for_returning_players - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['allow_commands'])) { echo "allow_commands - ".$_REQUEST['allow_commands']." - is set!"; } else { echo "allow_commands - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['autosave_slots'])) { echo "autosave_slots - ".$_REQUEST['autosave_slots']." - is set!"; } else { echo "autosave_slots - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['afk_autokick_interval'])) { echo "afk_autokick_interval - ".$_REQUEST['afk_autokick_interval']." - is set!"; } else { echo "afk_autokick_interval - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['auto_pause'])) { echo "auto_pause - ".$_REQUEST['auto_pause']." - is set!"; } else { echo "auto_pause - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['only_admins_can_pause_the_game'])) { echo "only_admins_can_pause_the_game - ".$_REQUEST['only_admins_can_pause_the_game']." - is set!"; } else { echo "only_admins_can_pause_the_game - Not Set!"; } echo "<br />";
			if(isset($_REQUEST['admins'])) { echo "admins - ".$_REQUEST['admins']." - is set!"; } else { echo "admins - Not Set!"; } echo "<br />";
//			if(isset($_REQUEST['']) { echo " - ".$_REQUEST['']." - is set!"; } else { echo " - Not Set!"; } echo "<br />";
			die();
		}
	}
?>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" >
		function validate(leForm) {
			var err = 0;
			var rdy = 0;
			var Form = document.getElementById(leForm);
			console.log(document.getElementById(leForm).elements);     
			for (var i = 0; i < Form.length; i++) {
				if (Form[i].value === "" && Form[i].name != "game_password") {
					console.log('[name="'+Form[i].name+'"]' + "it's an empty textfield");
					$('[name="'+Form[i].name+'"]').css("background-color", "red");
					err++;
				} else {
					if(Form[i].name == "max_players" || Form[i].name == "max_upload_in_kilobytes_per_second" || Form[i].name == "autosave_interval" || Form[i].name == "autosave_slots" || Form[i].name == "afk_autokick_interval") {
						if(Form[i].value >= 0 ) {
							console.log('Correct int! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
							rdy++;
						} else {
							console.log('Invalid! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
							err++;
						}
					} else if(Form[i].name == "name" || Form[i].name == "description" || Form[i].name == "tags" || Form[i].name == "admins" || Form[i].name == "game_password" || Form[i].name == "server_select") {
						if(Form[i].name == "server_select") {
							var server_select = Form[i].value;
						}
						console.log('Correct str! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if((Form[i].name == "visibility-public" || Form[i].name == "visibility-lan" || Form[i].name == "require_user_verification" || Form[i].name == "ignore_player_limit_for_returning_players" || Form[i].name == "auto_pause" || Form[i].name == "only_admins_can_pause_the_game") && (Form[i].value == "true" || Form[i].value == "false")) {
						console.log('Correct bln! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if((Form[i].name == "allow_commands") && (Form[i].value == "true" || Form[i].value == "false" || Form[i].value == "admins-only")) {
						console.log('Correct opt! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if(Form[i].name == "submit") {
						console.log('Ready! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
					} else {
						console.log('Invalid! [name="'+Form[i].name+'"]' + " - " + Form[i].value + typeof(Form[i].value));
						err++;
					}
				}
			}
			console.log('Error:'+err + ' Ready:' + rdy + '/18');
			if(err === 0) {
				console.log('No Errors! Ready to submit!');
				Form = $('#'+leForm).serialize();
				console.log(Form);
				if(user_level == "viewonly") {
					alert("You have view only access");
					return;
				}
				var http = new XMLHttpRequest();
				http.open("POST", "server-settings.php?d=" + server_select, true);
				http.setRequestHeader("Content-type","application/x-www-form-urlencoded");
				http.send(Form);
				http.onload = function() {
					if(http.responseText) {
						$('#' + server_select + '-return_output').html(http.responseText);
						//alert(http.responseText);
					}
				};
			}
		}
		function load_list(server) {
			$.get("server-settings.php?show=true&d=" + server, function(html) {
				// replace the "ajax'd" data to the table body
				$('#server_list-' + server).html(html);
				//var serverSettings = $.map(html, function(el) { return el });
				return false;
			});
			
			$('#link_home').attr('href',"index.php?d=" + server);
			$('#link_logs').attr('href',"logs.php?d=" + server + "#server_list-" + server);
		}
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a viewonly account, why upload a file, just to be told you can't do that?
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		echo "\t\t\t$('#welcome_user').text(user_name);\xA";
		if(isset($server_tab_list)) { echo $server_tab_list; }
		if(isset($_SESSION['login']['reload_report'])) {
			echo "\t\t\t$('#fileStatus').html('".$_SESSION['login']['reload_report']."');\xA";
			unset($_SESSION['login']['reload_report']);
		}

		// This is for displaying the server name & password in an input box
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
			if($server_settings != NULL) {
				//Do we have a server
				echo "\t\t\t$('#link_config').html('Config');\xA";
			} else {
				// Report file came back invalid
				echo "\t\t\t$('#alert').html('#ERROR WITH server-settings.json#');\xA";
				echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> Config');\xA";
			}
		} else {
			// Report file came back invalid
			echo "\t\t\t$('#alert').html('#ERROR WITH server-settings.json#');\xA";
			echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> Config');\xA";
		}
		echo "\t\t\t$('#link_home').attr('href',\"index.php?d=".$server_select."#server_list-".$server_select."\");\xA";
		echo "\t\t\t$('#link_logs').attr('href',\"logs.php?d=".$server_select."#server_list-".$server_select."\");\xA";
		echo "\xA\t\t\tload_list('$server_select');\xA";
		echo "\t\t});\xA";
?>
	</script>
	<script src="https://use.fontawesome.com/674cd09dad.js"></script>
	<script type="text/javascript" language="javascript" src="assets/log-ui.js"></script>
	<style type="text/css">@import "assets/log-ui.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;
			<a id="link_home" href="./index.php">Home</a>&nbsp;-&nbsp;
			<span id="link_config">Config</span>&nbsp;-&nbsp;
			<a id="link_logs" href="./logs.php">Logs</a>
			<span id="alert"></span>
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
<?php
die();
?>
