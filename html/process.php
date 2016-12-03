<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: /login.php");
	die();
} else {
	$current_user = $_SESSION['login']['user'];
	if($_SERVER["HTTPS"] != "on")
	{
		die('Must use HTTPS');
	}
}
$base_dir="../factorio/";
include(getcwd().'/getserver.php');
if(!isset($server_select)) {
	die('Error in server selection process.php');
}

if(isset($_REQUEST['start'])) {
	if($_SESSION['login']['user']=="guest") {
		echo "Guests may not Start/Stop server";
	} else {
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			$server_settings_path = "$base_dir$server_select/server-settings.json";
			$server_name="";
			$server_password="";
			$new_server_settings = false;
			//echo "Sending Start Server Command:\n\n";
			$output = shell_exec('../factorio/manage.new.sh "'.$server_select.'" "prestart" "'.$_SESSION['login']['user'].'"');
			if (strpos($output, 'false') !== false) {
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
				if(isset($data["game_password"])) {
					if(isset($_REQUEST['server_password'])) {
						$server_password = $_REQUEST['server_password'];
						$server_password = trim ( $server_password , " \t\n\r\0\x0B" );
					}
					if($server_password!=$data['game_password']) {
						//set new password
						$data['game_password']=$server_password;
						$new_server_settings = true;
					}
				} else {
					die('Invalid server-settings.json on game_password');
				}
				if($new_server_settings == true) {
					$newJsonString = json_encode($data, JSON_PRETTY_PRINT);
					file_put_contents($server_settings_path, $newJsonString);
				}
			}
			$output = shell_exec('../factorio/manage.new.sh "'.$server_select.'" "start" "'.$_SESSION['login']['user'].'"');
			echo $output;
		} else {
			die('Missing server-settings.json');
		}
	}
} elseif(isset($_REQUEST['status'])) {
	echo "Requesting Status of Servers:\n\n";
	$output = shell_exec('../factorio/manage.new.sh "'.$server_select.'" "status" "'.$_SESSION['login']['user'].'"');
	echo $output;
} elseif(isset($_REQUEST['stop'])) {
	if($_SESSION['login']['user']=="guest") {
		echo "Guests may not Start/Stop server";
	} else {
		//echo "Sending Stop Server Command:\n\n";
		$output = shell_exec('../factorio/manage.new.sh "'.$server_select.'" "stop" "'.$_SESSION['login']['user'].'"');
		echo $output;
	}
} elseif(isset($_REQUEST['command'])) {
	if($_SESSION['login']['user']=="guest") {
		echo "Guests can't send commands (yet) :( ";//".$_REQUEST['command'];
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
				$command = "/silent-command game.print(\"[WEB]".$current_user.": \"..\"".$command."\")";
			}
			$command = str_replace(array("'", "^"), array("'\"'\"'", "\^"), $command);
			system("sudo -u www-data /usr/bin/screen -S manage -X at 0 stuff '".$server_select."\\\$".$command."\n'");

			//used for up arrow history
			$cmd_history = $command_decode;
			if(isset($_SESSION['login']['cmd_history'][$server_select])) {
				array_unshift($_SESSION['login']['cmd_history'][$server_select], $cmd_history);
				if(count($_SESSION['login']['cmd_history'][$server_select])>25) {
					array_pop($_SESSION['login']['cmd_history'][$server_select]);
				}
			} else {
				$_SESSION['login']['cmd_history'][$server_select] = array($cmd_history);
			}
		}
	}
}

?>
