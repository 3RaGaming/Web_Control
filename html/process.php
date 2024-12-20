<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	}

	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }

	$base_dir="/var/www/factorio/";
	include(getcwd().'/getserver.php');
	if(!isset($server_select)) {
		die('Error s'.__LINE__.': In server selection files.php');
	}
	session_write_close();

if(isset($_REQUEST['start'])) {
	if($user_level=="viewonly") {
		echo "You have read only access.";
	} else {
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			$server_dir = $base_dir . $server_select . "/";
			$server_settings_path = $server_dir . "server-settings.json";
			$server_settings_web_path = $server_dir . "server-settings-web.json";
			if(file_exists($server_settings_web_path)) {
				$server_settings_web = json_decode(file_get_contents($server_settings_web_path), true);
				if(isset($server_settings_web['version'])) {
					$s_version = $server_settings_web['version'];
					//available exe versions
					$program_dir = "/usr/share/factorio/";
					foreach(glob("$program_dir*", GLOB_ONLYDIR) as $dir) {
						$dir = str_replace($program_dir, '', $dir);
						$server_available_versions[$dir] = "$program_dir$dir";
					}
					if(!isset($server_available_versions[$s_version])) {
						die('server-settings-web version '.$s_version.'  does not exist.');
					}
				}
			} else {
				die('Missing server-settings-web.json. Click "config" to attempt to generate one.');
			}
			$server_name="";
			$new_server_settings = false;
			//echo "Sending Start Server Command:\n\n";
			$output = shell_exec('bash '.$base_dir.'manage.sh "'.$server_select.'" "prestart" "'.$_SESSION['login']['user'].'"');
			if (strpos($output, 'stopped') !== false) {
				$jsonString = file_get_contents($server_settings_path);
				$data = json_decode($jsonString, true);
				if(isset($data["name"])) {
					if(isset($_REQUEST['server_name'])) {
						$server_name = $_REQUEST['server_name'];
						$server_name = trim ( $server_name , " \t\n\r\0\x0B" );
					}
					if($server_name!=""&&$server_name!=$data['name']) {
						//set new name
						$data['name']=$server_name;
						$new_server_settings = true;
					}
				} else {
					die('Invalid server-settings.json on name');
				}
				if($new_server_settings == true) {
					$newJsonString = json_encode($data, JSON_PRETTY_PRINT);
					file_put_contents($server_settings_path, $newJsonString);
				}
			}
			$output = shell_exec('bash '.$base_dir.'manage.sh "'.$server_select.'" "start" "'.$user_name.'" "'.$server_available_versions[$s_version].'"');
			echo $output;
		} else {
			die('Missing server-settings.json');
		}
	}
} elseif(isset($_REQUEST['status'])) {
	echo "Requesting Status of Servers:\n\n";
	$output = shell_exec('bash '.$base_dir.'manage.sh "'.$server_select.'" "status" "'.$user_name.'"');
	echo $output;
} elseif(isset($_REQUEST['stop'])) {
	if($user_level=="viewonly") {
		echo "You have view only access.";
	} else {
		//echo "Sending Stop Server Command:\n\n";
		$output = shell_exec('bash '.$base_dir.'manage.sh "'.$server_select.'" "stop" "'.$user_name.'"');
		echo $output;
	}
} elseif(isset($_REQUEST['forcekill'])) {
	if($user_level=="viewonly") {
		echo "You have view only access.";
	} else {
		//echo "Sending Stop Server Command:\n\n";
		$output = shell_exec('pkill -9 factorio');
		echo $output;
		$output = shell_exec('pkill -9 nodejs');
		echo $output;
		$output = shell_exec('pkill -9 managepgm');
		echo $output;
		$output = shell_exec('screen -wipe');
		#echo $output;
		echo "Servers killed. You monster.";
	}
} elseif(isset($_REQUEST['command'])) {
	if($user_level=="viewonly") {
		echo "You have view only access.";//".$_REQUEST['command'];
	} else {
		//screen -S factorio1 -X at 0 stuff 'hello\n'
		$command_decode = trim ( $_REQUEST['command'] , " \t\n\r\0\x0B" );
		if(!empty($command_decode)) {
			$command = $command_decode;
			//regex to find if server_message was hidden in a custom silent-command.
			//DOnt want anyone with command access to inject false data into a server-message
			//.*[Ss][Ee][Rr][Vv][Ee][Rr]_[Mm][Ee][Ss][Ss][Aa][Gg][Ee]\s*\(\s*".*
			//must implement this later
			if(strpos($command_decode, "\\") !== false) {
				die("Cannot use \\ in commands. Makes things angry :(");
			}
			if(substr($command_decode,0,1) != "/") {
				$command = str_replace(array("\""), array('\\\"'), $command_decode);
				$command = "/silent-command game.print(\"[WEB] ".$user_name.": ".$command."\")";
			}
			$command = str_replace(array("'", "^"), array("'\"'\"'", "\^"), $command);
			system("sudo -u www-data /usr/bin/screen -S manage -X at 0 stuff '".$server_select."\\\$".$command."\n'");

			//used for up arrow history
			$cmd_history = $command_decode;
			
			if(!isset($_SESSION)) { session_start(); }
			if(isset($_SESSION['login']['cmd_history'][$server_select])) {
				array_unshift($_SESSION['login']['cmd_history'][$server_select], $cmd_history);
				if(count($_SESSION['login']['cmd_history'][$server_select])>25) {
					array_pop($_SESSION['login']['cmd_history'][$server_select]);
				}
			} else {
				$_SESSION['login']['cmd_history'][$server_select] = array($cmd_history);
			}
			session_write_close();
		}
	}
}
