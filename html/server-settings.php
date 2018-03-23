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
	if(isset($_SESSION['login']['reload_report'])) {
		$session['login']['reload_report'] = $_SESSION['login']['reload_report'];
		unset($_SESSION['login']['reload_report']);
	}
	session_write_close();
	
	if($user_level=="viewonly") {
		die('Not allowed for view only');
	}
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		$server_select = "servertest";
	}
	
	//available exe versions
	$program_dir = "/usr/share/factorio/";
	foreach(glob("$program_dir*", GLOB_ONLYDIR) as $dir) {
		$dir = str_replace($program_dir, '', $dir);
		$server_installed_versions[$dir] = "$program_dir$dir";
		if(!isset($server_default_version)) {
			$server_default_version = $dir;
		}
	}
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				$server_dir = $base_dir . $server_select . "/";
				
				$server_config_path = $server_dir . "config/config.ini";
				$server_settings_path = $server_dir . "server-settings.json";
				$server_settings_web_path = $server_dir . "server-settings-web.json";
				$server_settings_run_path = $server_dir . "running-server-settings.json";
				if(file_exists($server_settings_web_path)) {
					$server_settings_web = json_decode(file_get_contents($server_settings_web_path), true);
				} else {
					//create the file with default settings if it does not exist.
					$server_settings_web['version']=$server_default_version;
					$s_version = $server_settings_web['version'];
					$newJsonString = json_encode($server_settings_web_path, JSON_PRETTY_PRINT);
					file_put_contents($server_settings_web_path, $newJsonString);
					//also want to update the config.ini file
					if(file_exists($server_config_path)) {
						$lines = file($server_config_path);
						$new_config = array();
						foreach($lines as $line) {
							if(substr($line, 0, 10) == 'read-data=') {
								$new_config[] = "read-data=".$server_installed_versions[$s_version]."/data\n";
							} else {
								$new_config[] = $line;
							}
						}
						file_put_contents($server_config_path, $new_config);
					}
				}
				if(file_exists($server_settings_path)) {
					$server_settings = json_decode(file_get_contents($server_settings_path), true);
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
										echo "$display:$col<select name=\"$key\"><option value=admins-only>Admins Only</option><option value=true selected>True</option><option value=false>False</option></select>";
									} elseif($value=="false")  {
										echo "$display:$col<select name=\"$key\"><option value=admins-only>Admins Only</option><option value=true>True</option><option value=false selected>False</option></select>";
									} else {
										echo "$display:$col<select name=\"$key\"><option value=admins-only selected>Admins Only</option><option value=true>True</option><option value=false>False</option></select>";
									}
								} else {
									//ghetto way to add version selection to this page
									if($key == "max_players") {
										echo "Server Version:$col<select name=\"s_version\">";
										foreach($server_installed_versions as $version => $path) {
											if($server_settings_web['version'] == $version) {
												echo "<option value=\"$version\" selected>$version</option>";
											} else {
												echo "<option value=\"$version\">$version</option>";
											}
										}
										echo "</select> <a href=\"./version_manager.php?d=".$server_select."\">Version Manager</a>";
										echo "</td></tr>";
										echo "<tr><td>";
									}
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
							echo "</td></tr>";
						}
					}
					echo "</table>";
					echo "<input type=\"hidden\" name=\"server_select\" value=\"".$server_select."\" /></form>";
					echo "<input type=\"button\" id=\"$server_select\" name=\"submit\" value=\"Save Config\" onclick=\"return validate('$server_select');\" /></form>";
					echo "<br /><span id=\"$server_select-return_output\"></span>";
					//echo "<pre>";
					//echo json_encode($server_settings, JSON_PRETTY_PRINT);
					//echo "</pre>";
				}
			}
			die();
		} elseif(isset($_REQUEST['server_select'])) {
			$verified_data = [];
			$err_data["error"] = true;
			$err = 0;
			$total_array = array();
			$ignore_array = array("d","server_select");
			$settype_string = array("name","description","game_password","allow_commands");
			$settype_integers = array("max_players","max_upload_in_kilobytes_per_second","autosave_interval","autosave_slots","afk_autokick_interval","minimum_latency_in_ticks");
			$settype_boolean = array("visibility-public","visibility-lan","require_user_verification","ignore_player_limit_for_returning_players","auto_pause","only_admins_can_pause_the_game","autosave_only_on_server","non_blocking_saving");
			$settype_array = array("tags","admins");
			$check_array_admin = array("true","false","admins-only");
			foreach($_REQUEST as $key => $value) {
				$clean_key = preg_replace('/[^\da-z]_/i', '', $key);
				$clean_value = preg_replace(array("/\</", "/\>/", "/\s+/"), array("", "", " "), $value);
				if(in_array($clean_key, $settype_string) || ($clean_key == "allow_commands" && in_array($clean_value, $check_array_admin))) {
					$verified_data[$clean_key] = $clean_value;
					continue;
				} elseif(in_array($clean_key, $settype_integers)) {
					if(is_numeric($clean_value)) {
						settype($clean_value, "integer");
						$verified_data[$clean_key] = $clean_value;
					} else {
						$err_data[$clean_key]=$clean_value;
						$err++;
					}
					continue;
				} elseif(in_array($clean_key, $settype_array)) {
					//work this
					$raw_array = explode(',', $clean_value);
					$trimmed_array=array_map('trim',$raw_array);
					$verified_data[$clean_key] = $trimmed_array;
					continue;
				} elseif(in_array($clean_key, $settype_boolean)) {
					if($clean_value == "true") {
						$clean_value = true;
					} elseif($clean_value == "false") {
						$clean_value = false;
					} else {
						$err_data[$clean_key]=$clean_value;
						$err++;
						continue;
					}
					if($clean_key == "visibility-public" || $clean_key == "visibility-lan") {
						$raw_value = explode('-', $clean_key);
						$verified_data["visibility"][$raw_value[1]] = $clean_value;
					} else {
						$verified_data[$clean_key] = $clean_value;
					}
					continue;
				} elseif(!in_array($clean_key, $ignore_array)) {
					if($clean_key == "s_version") {
						$s_version = $clean_value;
						continue;
					}
					$err_data[$clean_key]=$clean_value;
					$err++;
					continue;
				}
			}

			if(isset($err) && $err > 0) {
				echo json_encode($err_data, JSON_PRETTY_PRINT);
			} else {
				$date = date('Y-m-d');
				$time = date('H:i:s');
				$server_dir = $base_dir . $server_select . "/";
				$server_config_path = $server_dir . "config/config.ini";
				$server_settings_path = $server_dir . "server-settings.json";
				$server_settings_web_path = $server_dir . "server-settings-web.json";
				$server_settings_run_path = $server_dir . "running-server-settings.json";
				$server_log_loc = $server_dir . "logs/";
				$server_log_path = $server_dir . "logs/server-settings-update-$date.log";
				
				if(isset($s_version)) {
					if(isset($server_installed_versions[$s_version])) {
						$server_settings_web['version']=$s_version;
						$newJsonString = json_encode($server_settings_web, JSON_PRETTY_PRINT);
						file_put_contents($server_settings_web_path, $newJsonString);
						//also want to update the config.ini file
						if(file_exists($server_config_path)) {
							$lines = file($server_config_path);
							$new_config = array();
							foreach($lines as $line) {
								if(substr($line, 0, 10) == 'read-data=') {
									$new_config[] = "read-data=".$server_installed_versions[$s_version]."/data\n";
								} else {
									$new_config[] = $line;
								}
							}
							file_put_contents($server_config_path, $new_config);
						}
					}
				}
				if(file_exists($server_settings_path)) {
					$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
					foreach($verified_data as $key => $value) {
						$server_settings[$key] = $verified_data[$key];
					}
					$newJsonString = json_encode($server_settings, JSON_PRETTY_PRINT);
					$newJsonStringUgly = json_encode($server_settings);
					$newRawQuery = http_build_query($_REQUEST);
					$log_record = "\xA$date-$time\t".$user_name."\xA$newJsonStringUgly\xA$newRawQuery\xA";
					if($log_record != "") {
						if (!is_dir($server_log_loc)) {
							// dir doesn't exist, make it
							mkdir($server_log_loc);
						}
						file_put_contents($server_log_path, $log_record, FILE_APPEND);
					}
					file_put_contents($server_settings_path, $newJsonString);
					$output = json_encode("Settings Updated");
					die($output);
				} else {
					$output = json_encode("No settings file found");
					die($output);
				}
			}
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
				$('[name="'+Form[i].name+'"]').css("background-color", "white");
				if (Form[i].value === "" && Form[i].name != "game_password") {
					console.log('[name="'+Form[i].name+'"]' + "it's an empty textfield");
					$('[name="'+Form[i].name+'"]').css("background-color", "red");
					err++;
				} else {
					if(Form[i].name == "max_players" || Form[i].name == "max_upload_in_kilobytes_per_second" || Form[i].name == "autosave_interval" || Form[i].name == "autosave_slots" || Form[i].name == "afk_autokick_interval" || Form[i].name == "minimum_latency_in_ticks") {
						if(Form[i].value >= 0 ) {
							console.log('Correct int! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
							rdy++;
						} else {
							console.log('Invalid! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
							$('[name="'+Form[i].name+'"]').css("background-color", "red");
							err++;
						}
					} else if(Form[i].name == "name" || Form[i].name == "description" || Form[i].name == "tags" || Form[i].name == "admins" || Form[i].name == "game_password" || Form[i].name == "server_select" || Form[i].name == "s_version") {
						if(Form[i].name == "server_select") {
							var server_select = Form[i].value;
						}
						console.log('Correct str! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if((Form[i].name == "visibility-public" || Form[i].name == "visibility-lan" || Form[i].name == "require_user_verification" || Form[i].name == "ignore_player_limit_for_returning_players" || Form[i].name == "auto_pause" || Form[i].name == "only_admins_can_pause_the_game" || Form[i].name == "autosave_only_on_server" || Form[i].name == "non_blocking_saving") && (Form[i].value == "true" || Form[i].value == "false")) {
						console.log('Correct bln! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if((Form[i].name == "allow_commands") && (Form[i].value == "true" || Form[i].value == "false" || Form[i].value == "admins-only")) {
						console.log('Correct opt! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
						rdy++;
					} else if(Form[i].name == "submit") {
						console.log('Ready! [name="'+Form[i].name+'"]' + " - " + Form[i].value);
					} else {
						console.log('Invalid! [name="'+Form[i].name+'"]' + " - " + Form[i].value + typeof(Form[i].value));
						$('[name="'+Form[i].name+'"]').css("background-color", "red");
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
			} else {
				$('#' + server_select + '-return_output').html("Error with one of the things");
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
		if(isset($session['login']['reload_report'])) {
			echo "\t\t\t$('#fileStatus').html('".$session['login']['reload_report']."');\xA";
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
	<script type="text/javascript" language="javascript" src="assets/js/log-ui.js"></script>
	<style type="text/css">@import "assets/css/log-ui.css";</style>
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