<?php	
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
	
	function install($version, $program_dir, $tmp_file) {
		echo "installing\xA";
		file_put_contents($tmp_file, json_encode(array("action" => "installing", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
		echo "install\xA";
		if(is_dir($program_dir)) {
			unlink($tmp_file);
			return "Install failed. Directory exists.";
		} else {
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
						//create array to work with later
						print_r($output);
						if($output[1]==$version) {
							$direct_url = "https://www.factorio.com/$value";
							break 2;
						}
						//add to total versions to compare against installed versions
					}
				}
			}
			print_r($server_matched_versions);
			
			if(isset($direct_url)) {
				return $direct_url;
			} else {
				return "no download found";
			}
			return "install success";
		}
	}
	
	function delete($version, $program_dir, $tmp_file) {
		echo "delete-ing\xA";
		file_put_contents($tmp_file, json_encode(array("action" => "deleting", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
		echo "delete\xA";
		//rrmdir($program_dir);
		if(is_dir($program_dir)) {
			unlink($tmp_file);
			return "delete failed";
		} else {
			unlink($tmp_file);
			return "delete success";
		}
	}
	
	$date = date('Y-m-d');
	$time = date('H:i:s');
	$log_dir = "/var/www/factorio/logs";
	$log_path = "$log_dir/version-manager-$date.log";
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['install'])&&$_REQUEST['install']!="") {
			if($_REQUEST['install']!="") {
				$version = preg_replace('/[^0-9.]+/', '', $_REQUEST['install']);
				$program_dir = $program_dir.$version."/";
				$tmp_file = "/tmp/factorio-version-manager.$version.txt";
				if(is_dir($program_dir)) {
					$result = "Install failed. Directory exists.";
				} else {
					if(file_exists($tmp_file)) {
						$tmp_file_contents = json_decode(file_get_contents($tmp_file));
						die('Action in progress: '.$tmp_file_contents->action.' by '.$tmp_file_contents->username);
					} else {
						$result = install($version, $program_dir, $tmp_file);
						unlink($tmp_file);
					}
					//$log_record = $log_record." deleted\xA";
				}
			} else {
				$result = "No Version provided";
			}
			echo $result;
			//file_put_contents($log_path, $log_record, FILE_APPEND);
			die();
		} elseif(isset($_REQUEST['delete'])) {
			if($_REQUEST['delete']!="") {
				$version = preg_replace('/[^0-9.]+/', '', $_REQUEST['delete']);
				$program_dir = $program_dir.$version."/";
				$tmp_file = "/tmp/factorio-version-manager.$version.txt";
				if(is_dir($program_dir)) {
					if(file_exists($tmp_file)) {
						$tmp_file_contents = json_decode(file_get_contents($tmp_file));
						die('Action in progress: '.$tmp_file_contents->action.' by '.$tmp_file_contents->username);
					} else {
						$result = delete($version, $program_dir, $tmp_file);
						
					}
					//$log_record = $log_record." deleted\xA";
				} else {
					$result = "Invalid Version $version";
				}
			} else {
				$result = "No Version provided";
			}
			echo $result;
			//file_put_contents($log_path, $log_record, FILE_APPEND);
			die();
		} elseif(isset($_REQUEST['show'])) {
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
							echo "<span id=\"$value-span\"><button name=\"$value-delete\" onclick=\"return form_action(\'delete\')\">delete</button> - installed</span>";
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
<?php
		echo "\t\tvar server_select = \"";
		if(isset($server_select)) { echo $server_select; }  else { echo "error"; }
		echo "\";\xA";

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
