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
	if(!isset($server_select)&&!isset($_REQUEST['d'])) {
		$server_select = "servertest";
	}
	session_write_close();

	// function to print files size in human-readable form
	function human_filesize($file, $decimals = 2) {
		$bytes = filesize($file);
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

	if(isset($_REQUEST)) {
		if(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				$server_select = $server_select ?? "failed";
				$server_dir = $base_dir . $server_select . "/";
				if(isset($_REQUEST['d'])) {
					if($_REQUEST['d']=="Managepgm") {
						$server_select="Managepgm";
						$server_dir = $base_dir;
					} elseif($_REQUEST['d']!==$server_select||$server_select=="failed") {
						die('Error in check');
					}
				}
				$current_array = array("screenlog.0", "factorio-current.log");
				foreach($current_array as $value) {
					if(file_exists($server_dir.$value)) {
						$file_full_path = $server_dir.$value;
						$size = human_filesize("$file_full_path");
						$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
						echo " <a href=\"#\" onClick=\"Download('logs.php?d=".$server_select."&download=$value')\">$value</a> - $size - $date <br />";
					}
				}
				$full_dir = $server_dir . "logs";
				foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
					$file_full_path = "$full_dir/$file";
					$size = human_filesize("$file_full_path");
					$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
					echo " <a href=\"#\" onClick=\"Download('logs.php?d=".$server_select."&download=".$file."')\">$file</a> - $size - $date <br />";
				}
				die();
			}
		}
		if(isset($_REQUEST['download'])&&isset($_REQUEST['d'])) {
			$server_select = $server_select ?? "failed";
			$server_dir = $base_dir . $server_select . "/";
			if(isset($_REQUEST['d'])) {
				if($_REQUEST['d']=="Managepgm") {
					$server_select="Managepgm";
					$server_dir = $base_dir;
				} elseif($_REQUEST['d']!==$server_select||$server_select=="failed") {
					die('Error in check');
				}
			}
			//Current running log file, or archived log file?
			if($_REQUEST['download']=="screenlog.0"||$_REQUEST['download']=="factorio-current.log") {
				//um... how can this be done better?
			} else {
				$server_dir = $server_dir . "logs/";
			}
			if(file_exists($server_dir)) {
				$file_path = $server_dir . $_REQUEST['download'];
				if(file_exists($file_path)) {
					if($user_level=="viewonly") {
						die('You have read only access.\nVisit our archive for file downloads\nwww.3ragaming.com/archive/factorio');
					}
					// file download found on http://www.media-division.com/php-download-script-with-resume-option/
					// get the file request, throw error if nothing supplied

					// hide notices
					@ini_set('error_reporting', E_ALL & ~ E_NOTICE);

					//- turn off compression on the server
					if(function_exists( 'apache_setenv')) {
					    @apache_setenv('no-gzip', 1);
                    }
					@ini_set('zlib.output_compression', 'Off');

					// sanitize the file request, keep just the name and extension
					// also, replaces the file location with a preset one ('./myfiles/' in this example)
					$path_parts = pathinfo($file_path);
					$file_name  = $path_parts['filename'];
					$file_ext   = $path_parts['extension'];
					// allow a file to be streamed instead of sent as an attachment
					$is_attachment = isset($_REQUEST['stream']) ? false : true;
					// make sure the file exists
					if (is_file($file_path))
					{
						$file_size  = filesize($file_path);
						$file = @fopen($file_path,"rb");
						if ($file)
						{
							// set the headers, prevent caching
							header("Pragma: public");
							header("Expires: -1");
							header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
							header("Content-Disposition: attachment; filename=\"$server_select-$file_name.$file_ext\"");

							// set appropriate headers for attachment or streamed file
							if ($is_attachment) {
								header("Content-Disposition: attachment; filename=\"$server_select-$file_name.$file_ext\"");
							} else {
								header('Content-Disposition: inline;');
							}

							header("Content-Type: text/plain");
							//check if http_range is sent by browser (or download manager)
							if(isset($_SERVER['HTTP_RANGE'])) {
								list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
								if ($size_unit == 'bytes') {
									//multiple ranges could be specified at the same time, but for simplicity only serve the first range
									//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
									list($range, $extra_ranges) = explode(',', $range_orig, 2);
								} else {
									$range = '';
									header('HTTP/1.1 416 Requested Range Not Satisfiable');
									exit;
								}
							} else {
								$range = '';
							}
							//figure out download piece from range (if set)
							list($seek_start, $seek_end) = explode('-', $range, 2);

							//set start and end based on range (if set), else set defaults
							//also check for invalid ranges.
							$seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)),($file_size - 1));
							$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

							//Only send partial content header if downloading a piece of the file (IE workaround)
							if ($seek_start > 0 || $seek_end < ($file_size - 1)) {
								header('HTTP/1.1 206 Partial Content');
								header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
								header('Content-Length: '.($seek_end - $seek_start + 1));
							} else
							header("Content-Length: $file_size");
							header('Accept-Ranges: bytes');
							set_time_limit(0);
							fseek($file, $seek_start);
							while(!feof($file)) {
								print(@fread($file, 1024*8));
								ob_flush();
								flush();
								if (connection_status()!=0) {
									@fclose($file);
									exit;
								}
							}
							// file save was a success
							@fclose($file);
							exit;
						} else {
							// file couldn't be opened
							header("HTTP/1.0 500 Internal Server Error");
							exit;
						}
					} else {
						// file does not exist
						header("HTTP/1.0 404 Not Found");
						die('dead');
						exit;
					}
					/*   END OF FILE DOWNLOAD */
					//no reason to continue
					die();
				} else {
					echo "NOT exists $file_path $server_select";
					die();
				}
			}
			die();
		}
	}
?>
</script>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		function load_list(server) {
			$.get("logs.php?show=true&d=" + server, function(html) {
				// replace the "ajax'd" data to the table body
				$('#server_list-' + server).html(html);
				return false;
			});
			$('#link_home').attr("href", "./index.php?d=" + server);
			$('#link_config').attr("href", "./server-settings.php?d=" + server + "#server_list-" + server);
		}
		function Download(url) {
			if (user_level == "viewonly") { return; }
			document.getElementById('file_iframe').src = url;
		}
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		<?php if(isset($server_select)) { echo "\t\t$(\"logs\").attr(\"href\", \"./logs.php?$server_select\");\xA"; } ?>
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you have view only access, why upload a file, just to be told you can't do that?
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		echo "\t\t\t$('#welcome_user').text(user_name);\xA";
		echo "\t\t\t$('#link_home').attr('href',\"index.php?d=".$server_select."\");\xA";
		echo "\t\t\t$('#link_config').attr('href',\"logs.php?d=".$server_select."#server_list-".$server_select."\");\xA";
		echo "\xA\t\t\tload_list('$server_select');;\xA";
		if(isset($server_tab_list)) { echo $server_tab_list; }
		//echo "\xA\t\t\t setTimeout(load_list('$server_select'), 500);\xA";
		echo "\t\t})\xA";
?>
	</script>
	<script type="text/javascript" language="javascript" src="assets/js/log-ui.js"></script>
	<style type="text/css">@import "assets/css/log-ui.css";</style>
</head>
<body onLoad="load_list(server_select)">
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;
			<a id="link_home" href="./index.php">Home</a>&nbsp;-&nbsp;
			<a id="link_config" href="./server-settings.php">Config</a>&nbsp;-&nbsp;
			Logs</a>&nbsp;&nbsp;
			<span id="alert"></span>
			<div style="float: right;">
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- server files -->
		<div style="width: 92%; height: 99%; float: left;">
			<div id="server_list">
				<ul>
					<li><a href="#server_list-Managepgm" onclick="load_list('Managepgm');">Managepgm</a></li>
				</ul>
				<div id="server_list-Managepgm">Dynamic tab for Managepgm</div>
			</div>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>
</body>
</html>
<?php
die();
