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
		$total_versions[]=$dir;
		if(!isset($server_default_version)) {
			$server_default_version = $dir;
		}
	}
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
				echo "<pre>";
				print_r($server_installed_versions);
				echo "\n\n\n";
				$urls = array(
					"https://www.factorio.com/download-headless/stable",
					"https://www.factorio.com/download-headless/experimental"
				);
				foreach($urls as $url) {
					$server_matched_versions = get_url($url);
					//var_dump($server_available_versions);
					if(isset($server_matched_versions[0])) {
						foreach($server_matched_versions[0] as $key => $value) {
							preg_match('~/(.*?)/~', $server_matched_versions[1][$key], $output);
							//var_dump($output[1]);
							$branch = substr($url, strrpos($url, '/') + 1);
							$server_available_versions[$output[1]] = array(0=>$value,1=>$branch);
							$os = array("Mac", "NT", "Irix", "Linux");
							if(!in_array($output[1], $total_versions)) {
								$total_versions[]=$output[1];
							}
						}
					}
				}
				var_dump($server_available_versions);
				var_dump($total_versions);
			}
			echo "</pre>";
			
			
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
	<script type="text/javascript" language="javascript" src="assets/js/log-ui.js"></script>
	<style type="text/css">@import "assets/css/log-ui.css";</style>
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
