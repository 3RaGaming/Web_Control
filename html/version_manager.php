<?php
	//background script work in cli only
	if (php_sapi_name() == "cli") {
		//argv[1] = version argv[2] = action argv[3] = username
		//log directory for managepgm. we'll store data for user installs and deletes here.
		if(isset($argv[1])) { $version = preg_replace('/[^0-9.]+/', '', $argv[1]); } else { die('No version'); }
		if(isset($argv[2])) { $action = preg_replace("/[^a-zA-Z]+/", "", $argv[2]); } else { die('No action'); }
		if(isset($argv[3])) { $user_name = preg_replace("/[^a-zA-Z0-9]+/", "", $argv[3]); } else { $user_name = "UNKNOWN"; }
		$date = date('Y-m-d');
		$time = date('H:i:s');
		$log_dir = "/var/www/factorio/logs";
		$log_path = "$log_dir/version-manager-$date.log";
		//available exe versions
		$program_dir = "/usr/share/factorio/$version";
		$log_record = "\xA$date-$time\t".$user_name."\xA Attempting to \"$action\" version \"$version\"\xA";
		if (!is_dir($log_dir)) {
			// dir doesn't exist, make it
			mkdir($log_dir);
		}
		$tmp_file = "/tmp/$version.install";
		function delete() {
			
		}
		function install() {
			
		}
		echo "$program_dir - $tmp_file";
		sys_get_temp_dir();
		if($action=="delete") {
			if(is_dir($program_dir)) {
				if(file_exists($tmp_file)) {
					die('tmp file exists');
				} else {
					echo "delete-ing\xA";
					file_put_contents($tmp_file, "delete-ing");
					sleep(2);
					echo "pretend delete\xA";
					//delete
					sleep (2);
					if(is_dir($program_dir)) {
						echo "delete-failed\xA";
						file_put_contents($tmp_file, "delete-failed");
						sleep(10);
						unlink($tmp_file);
					} else {
						echo "delete-success\xA";
						file_put_contents($tmp_file, "delete-success");
						sleep(10);
						unlink($tmp_file);
					}
				}
				$log_record = $log_record." deleted\xA";
			} else {
				echo "no folder to delete";
				$log_record = $log_record." no folder to delete\xA";
			}
		}
		if($action=="install") {
			if(is_dir($program_dir)) {
				echo "already installed";
				$log_record = $log_record." already installed\xA";
			} else {
				echo "can install";
				$log_record = $log_record." installed\xA";
			}
		}
		file_put_contents($log_path, $log_record, FILE_APPEND);
		die();
	}
	
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		die('Error: Login required');
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
	//directory of installed
	foreach(glob("$program_dir*", GLOB_ONLYDIR) as $dir) {
		$dir = str_replace($program_dir, '', $dir);
		$server_installed_versions[$dir] = "$program_dir$dir";
		//total versions variable needed for comparing against available versions
		$total_versions[]=$dir;
		if(!isset($server_default_version)) {
			$server_default_version = $dir;
		}
	}
	//function used to get source of the download web pages for iteration.
	//regex will search for the download link for use following function call.
	function get_url($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		preg_match_all('/get-download(.*?)linux64/', $data, $matches);
		return $matches;
		curl_close($ch);
	}
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				//print_r($server_installed_versions);
				echo "<br /><br />";
				$urls = array(
					"https://www.factorio.com/download-headless/stable",
					"https://www.factorio.com/download-headless/experimental"
				);
				foreach($urls as $url) {
					//run this script on each url in the array
					$server_matched_versions = get_url($url);
					//var_dump($server_available_versions);
					//if a download link is found, iterate the results
					if(isset($server_matched_versions[0])) {
						foreach($server_matched_versions[0] as $key => $value) {
							//find the verion number in the link
							preg_match('~/(.*?)/~', $server_matched_versions[1][$key], $output);
							//var_dump($output[1]);
							//get the experimental or stable tag from the url
							$branch = substr($url, strrpos($url, '/') + 1);
							//create array to work with later
							$server_available_versions[$output[1]] = array(0=>$value,1=>$branch);
							//add to total versions to compare against installed versions
							if(!in_array($output[1], $total_versions)) {
								$total_versions[]=$output[1];
							}
						}
					}
				}
				//sort the verion numbers with a fancy smart sorting function built in to php
				natsort($total_versions);
				//var_dump($server_available_versions);
				//var_dump($total_versions);
				//display the table for installed and available versions
				echo "<table><tr><td>Version</td><td></td><td>Control</td>\xA";
				foreach($total_versions as $value) {
					echo "<tr><td>$value</td><td>";
					if(isset($server_available_versions[$value])) {
						//display different colors for versions
						if($server_available_versions[$value][1]=="stable") {
							echo "<font color=green>";
						} elseif($server_available_versions[$value][1]=="experimental") {
							echo "<font color=orange>";
						}
						echo $server_available_versions[$value][1]."</td><td>";
					} else {
						echo "<font color=red>depreciated</font></td><td>";
					}
					//if the server is working on installing a version, this file will exist and hold the status of the install
					$tmp_file = "/tmp/$value.install";
					if(file_exists($tmp_file)) {
						$tmp_status[$value] = file_get_contents($tmp_file);
					}
					if(isset($tmp_status[$value])) {
						echo "<span id=\"$value-span\">$tmp_status[$value]</span>";
					} else {
						//if tmp_file doesn't exist, general rules for if it's installed or not can be displayed
						if(isset($server_installed_versions[$value])) {
							echo "<span id=\"$value-span\"><button name=\"$value-delete\" onclick=\"return form_action(\'install\')\">delete</button> - installed</span>";
						} else {
							echo "<span id=\"$value-span\"><button name=\"$value-install\" onclick=\"return form_action(\'install\')\">install</button> - not found</span>";
						}
					}
					echo "</td></tr>\xA";
				}
				echo "</table>\xA";
			}
			die();
		}
	}
?>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" >
		function load_list() {
			$.get("version_manager.php?show=true", function(html) {
				// replace the "ajax'd" data to the table body
				$('#version_list').html(html);
				//var serverSettings = $.map(html, function(el) { return el });
				return false;
			});
			
			$('#link_home').attr('href',"index.php?d=" + server);
			$('#link_logs').attr('href',"logs.php?d=" + server + "#server_list-" + server);
		}
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		echo "\t\t\t$('#welcome_user').text(user_name);\xA";
		if(isset($server_tab_list)) { echo $server_tab_list; }
		if(isset($session['login']['reload_report'])) {
			echo "\t\t\t$('#fileStatus').html('".$session['login']['reload_report']."');\xA";
		}

		// This is for displaying the server name & password in an input box
		echo "\t\t\t$('#link_home').attr('href',\"index.php?d=".$server_select."\");\xA";
		echo "\t\t\t$('#link_logs').attr('href',\"logs.php?d=".$server_select."#server_list-".$server_select."\");\xA";
		echo "\t\t\t$('#link_config').html('<a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
		echo "\xA\t\t\tload_list();\xA";
		echo "\t\t});\xA";
?>
	</script>
	<script src="https://use.fontawesome.com/674cd09dad.js"></script>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;
			<a id="link_home" href="./index.php">Home</a>&nbsp;-&nbsp;
			<a id="link_config" href="./server-settings.php">Config</a>&nbsp;-&nbsp;
			<a id="link_logs" href="./logs.php">Logs</a>
			<span id="alert"></span>
			<!--<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />-->
			<div style="float: right;">
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- server files -->
		<div style="width: 92%; height: 99%; float: left;">
			<div id="version_list">
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
