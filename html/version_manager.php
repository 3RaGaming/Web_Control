<?php	
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		if( isset($_REQUEST['install']) || isset($_REQUEST['delete']) || isset($_REQUEST['show']) ) {
			die('Error: Login required');
		}
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

	function getFilename($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		$data = curl_exec($ch);

		//echo $data;
		preg_match("/Content-Disposition: .*filename=([^\n]+)/", $data, $filenames);
		preg_match("/Location: ([^\n]+)/", $data, $filelocations);
		return array($filenames[1],  $filelocations[1]);
	}

	function move_dir($oldPath,$newPath) {
		exec("mv ".escapeshellarg($oldPath)." ".escapeshellarg($newPath));
	}

	function rrmdir($src) {
		$dir = opendir($src);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' )) {
				$full = $src . '/' . $file;
				if ( is_dir($full) ) {
					rrmdir($full);
				}
				else {
					unlink($full);
				}
			}
		}
		closedir($dir);
		rmdir($src);
	}

	function install($version, $program_dir, $tmp_file) {
		global $progress_file;
		$progress_file = "/tmp/factorio-version-manager_progress.".$version.".txt";
		file_put_contents($tmp_file, json_encode(array("action" => "install", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
		if(is_dir($program_dir)) {
			unlink($tmp_file);
			return "Install failed. Directory exists.";
		} else {
			$urls = array(
				"https://www.factorio.com/download-headless",
				"https://www.factorio.com/download-headless/experimental"
			);
			foreach($urls as $url) {
				//run this script on each url in the array until a match is found
				$server_matched_versions = get_url($url);
				//if a download link is found, iterate the results
				if(isset($server_matched_versions[0])) {
					foreach($server_matched_versions[0] as $key => $value) {
						//find the verion number in the link
						preg_match('~/(.*?)/~', $server_matched_versions[1][$key], $output);
						//print_r($output);
						if($output[1]==$version) {
							$direct_url = "https://www.factorio.com/$value";
							break 2;
						}
					}
				}
			}
			
			if(isset($direct_url)) {
				//create status files periodically so other users know whats going on. Should be able to use this for active user status updates as well
				file_put_contents($tmp_file, json_encode(array("action" => "downloading", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
				
				//get's filename and download url, actually...
				$file = getFilename($direct_url);
				
				//make sure we get both in return
				if(isset($file[0])&&isset($file[1])) {
					//define the function so we can get download status as we download
					function progressCallback( $resource, $download_size, $downloaded_size, $upload_size, $uploaded_size )
					{
						global $progress_file;
						static $previousProgress = 0;
						
						if ( $download_size == 0 )
							$progress = 0;
						else
							$progress = round( $downloaded_size * 100 / $download_size );
							
						if ( $progress > $previousProgress)
						{
							$previousProgress = $progress;
							//this *should* replace the file contents on each update. ajax can check for updates for a pretty progress bar and/or percentage
							file_put_contents( $progress_file, "$progress" );
						}
					}
					
					//clean up the URL, filename and set the temporary path
					$url = trim($file[1]);
					$filename = preg_replace('/\.(?=.*\.)/', '_', preg_replace("/[^a-zA-Z0-9.-_]+/", "", $file[0]));
					$filename_loc = "/tmp/".$filename;
					
					file_put_contents( $progress_file, '0' );
					$targetFile = fopen( $filename_loc, 'w' );
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $url);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_NOPROGRESS, false );
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
					curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progressCallback' );
					curl_setopt($ch, CURLOPT_FILE, $targetFile );
					$data=curl_exec($ch);
					curl_close($ch);
					fclose( $targetFile );
					if($data === false)
					{
						return 'Curl error: ' . __LINE__ . ' ' . curl_error($ch);
					} //continue if successful
					unlink($progress_file);
					file_put_contents($tmp_file, json_encode(array("action" => "unpacking", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
					if(is_dir($program_dir)) {
						return "directory exists";
					} else {
						$fileType = mime_content_type($filename_loc);
						switch ($fileType) {
							case "application/x-xz":
								//unlink($filename_loc);
								//return "filetype not yet implemented: $fileType";
								$tar_dir = "/tmp/$version/";
								if(is_dir($tar_dir)) {
									rrmdir($tar_dir);
								}
								mkdir($tar_dir);
								exec("tar -xf $filename_loc -C $tar_dir");
								
								function is_dir_empty($dir) {
									if (!is_readable($dir)) return NULL; 
									$handle = opendir($dir);
									while (false !== ($entry = readdir($handle))) {
										if ($entry != "." && $entry != "..") {
											return FALSE;
										}
									}
									return TRUE;
								}
								unlink($filename_loc);
								if(is_dir_empty($tar_dir)) {
									return "install fail. 'tar_dir' is empty";
								} else {
									$files_dir = $tar_dir."factorio";
									move_dir($files_dir, $program_dir);
									rrmdir($tar_dir);
									if(is_dir_empty($program_dir)) {
										return "failed to move from tmp to $program_dir";
									} else {
										return "Install Successfull! $program_dir";
									}
								}
								
								
								break;
							case "application/x-gzip";
								$filename_tar = pathinfo( $filename_loc, PATHINFO_FILENAME ).".tar";
								$filepath_tar = "/tmp/$filename_tar";
								if(file_exists($filepath_tar)) {
									unlink($filepath_tar);
								}
								$p = new PharData($filename_loc);
								$p->decompress(); // creates /path/to/my.tar
								unlink($filename_loc);
								$i = 0;
								while ( $i < 8 ) {
									if(!file_exists($filepath_tar)) {
										usleep(250000);
									} else {
										$i=10;
									}
									$i++;
								}
								if(!file_exists($filepath_tar)) {
									return "unable to make tar file";
								}
								// unarchive from the tar
								try {
									$phar = new PharData($filepath_tar);
									$tar_dir = "/tmp/$version/";
									//mkdir($tar_dir);
									$phar->extractTo($tar_dir);
								} catch (Exception $e) {
									unlink($filepath_tar);
									if(is_dir($tar_dir)) rrmdir($tar_dir);
									return "tar extract failure: $e";
									// handle errors
								}
								
								function is_dir_empty($dir) {
									if (!is_readable($dir)) return NULL; 
									$handle = opendir($dir);
									while (false !== ($entry = readdir($handle))) {
										if ($entry != "." && $entry != "..") {
											return FALSE;
										}
									}
									return TRUE;
								}
								unlink($filepath_tar);
								if(is_dir_empty($tar_dir)) {
									return "install fail. Dir is empty";
								} else {
									$files_dir = $tar_dir."factorio";
									move_dir($files_dir, $program_dir);
									rmdir($tar_dir);
									if(is_dir_empty($program_dir)) {
										return "failed to move from tmp to $program_dir";
									} else {
										return "success";
									}
								}
								
								break;
							default:
								return "unsupported filetyle: $fileType";
						}
					}
				} else {
					return "issue finding remote file ".$file[0]." ".$file[1];
				}
			} else {
				return "no download found";
			}
		}
	}
	
	function delete($version, $program_dir, $tmp_file) {
		file_put_contents($tmp_file, json_encode(array("action" => "deleting", "username" => $user_name, "time" => "$date $time"), JSON_PRETTY_PRINT));
		rrmdir($program_dir);
		if(is_dir($program_dir)) {
			unlink($tmp_file);
			return "delete failed";
		} else {
			unlink($tmp_file);
			return "success";
		}
	}
	
	$date = date('Y-m-d');
	$time = date('H:i:s');
	$log_dir = "/var/www/factorio/logs";
	$log_path = "$log_dir/version-manager-$date.log";
	
	if(isset($_REQUEST)) {
		if(isset($_REQUEST['status'])&&$_REQUEST['status']!="") {
			if( $user_level == "viewonly" ) {
				die('View-only may not manage versions');
			}
			if($_REQUEST['status']!="") {
				$js_value = preg_replace('/_/', '.', $_REQUEST['status']);
				$version = preg_replace('/[^0-9.]+/', '', $js_value);
				$tmp_file = "/tmp/factorio-version-manager_progress.$version.txt";
				 //factorio-version-manager_progress.0.12.35.txt
				if(file_exists($tmp_file)) {
					$result = file_get_contents($tmp_file);
				} else {
					$result = 0;
				}
			} else {
				$result = "NVP";
			}
			echo $result;
			die();
		} if(isset($_REQUEST['install'])) {
			if( $user_level == "viewonly" ) {
				die('View-only may not manage versions');
			}
			if($_REQUEST['install']!="") {
				$js_value = preg_replace('/_/', '.', $_REQUEST['install']);
				$version = preg_replace('/[^0-9.]+/', '', $js_value);
				$program_dir = $program_dir.$version."/";
				$tmp_file = "/tmp/factorio-version-manager_status.$version.txt";
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
				}
			} else {
				$result = "No Version provided";
			}
			echo $result;
			$log_record = "$time $date $version $result : $username \xA";
			file_put_contents( $log_path, $log_record, FILE_APPEND );
			die();
		} elseif( isset( $_REQUEST['delete'] ) ) {
			if( $user_level == "viewonly" ) {
				die('View-only may not manage versions');
			}
			if( $_REQUEST['delete']!="" ) {
				$js_value = preg_replace('/_/', '.', $_REQUEST['delete']);
				$version = preg_replace( '/[^0-9.]+/', '', $js_value );
				$program_dir = $program_dir.$version."/";
				$tmp_file = "/tmp/factorio-version-manager_status.$version.txt";
				if(is_dir($program_dir)) {
					$dir_user = posix_getpwuid( fileowner( $program_dir ));
					if( isset( $dir_user['name'] ) && $dir_user['name'] != "www-data" ) {
						$result = "Invalid filesystem permissions to remove installation.";
					} else {
						if( file_exists( $tmp_file ) ) {
							$tmp_file_contents = json_decode( file_get_contents( $tmp_file ) );
							die('Action in progress: '.$tmp_file_contents->action.' by '.$tmp_file_contents->username);
						} else {
							$result = delete($version, $program_dir, $tmp_file);
						}
					}
				} else {
					$result = "Version $version not found";
				}
			} else {
				$result = "No Version provided";
			}
			echo $result;
			$log_record = "$time $date $version $result : $username \xA";
			file_put_contents($log_path, $log_record, FILE_APPEND);
			die();
		} elseif(isset($_REQUEST['show'])) {
			if($_REQUEST['show']=="true") {
				//print_r($server_installed_versions);
				echo "<br /><br />";
				$urls = array(
					"https://www.factorio.com/download-headless",
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
							if($branch=="download-headless") $branch = "stable";
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
					$js_value = preg_replace('#\.#', '_', $value);
					echo "<tr><td>$value</td><td>";
					
					if(isset($server_available_versions[$value])) {
						//display different colors for versions
						if($server_available_versions[$value][1]=="stable") {
							echo "<font color=green>";
						} elseif($server_available_versions[$value][1]=="experimental") {
							echo "<font color=orange>";
						}
						echo "<span id=\"dev-$js_value\">".$server_available_versions[$value][1]."</span></td><td>";
					} else {
						echo "<font color=red><span id=\"dev-$js_value\">depreciated</span></font></td><td>";
					}
					
					//if the server is working on installing a version, this file will exist and hold the status of the install
					$tmp_file = "/tmp/factorio-version-manager_status.$value.txt";
					if(file_exists($tmp_file)) {
						$tmp_status[$value] = file_get_contents($tmp_file);
					}
					if(isset($tmp_status[$value])) {
						echo "<span id=\"span-$js_value\">$tmp_status[$value]</span>";
					} else {
						//if tmp_file doesn't exist, general rules for if it's installed or not can be displayed
						if(isset($server_installed_versions[$value])) {
							$path = "/usr/share/factorio/$value";
							$user = posix_getpwuid( fileowner( $path ));
							if(isset($user['name'])&&$user['name']!="www-data") {
								echo "<span id=\"span-$js_value\">Installed. Invalid filesystem permissions to delete.</span>";
							} else {
								echo "<span id=\"span-$js_value\"><button id=\"button-$js_value\" onclick=\"return w_delete('$js_value')\">delete</button></span> <span id=\"status-$js_value\">- installed</span></span>";
							}
						} else {
							echo "<span id=\"span-$js_value\"><button id=\"button-$js_value\" onclick=\"return w_install('$js_value')\">install</button></span> <span id=\"status-$js_value\"></span>";
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
		function load_list(e) {
			if(e === false) return;
			$.get("version_manager.php?show=true", function(html) {
				// replace the "ajax'd" data to the table body
				$('#version_list').html(html);
				return false;
			});
		}
		load_list(false);
		
		var s_loc = window.location.pathname;
		var s_dir = s_loc.substring(0, s_loc.lastIndexOf('/'));
		var s_refreshtime=200;
		var versionwork = {};
		
		function check_status(e)
		{
			if(e === false) return;
			//var version = "status-"+e;
			if(!versionwork.hasOwnProperty(e)) versionwork[e] = 0;
			//console.log("start" + versionwork[e]);
			if(versionwork[e] >= 100) {
				$('#status-' + e ).html('finished');
				versionwork[e]=0;
			} else if(versionwork[e] != -1) {
				console.log(s_dir + "/version_manager.php?status=" + e);
				$.get(s_dir + "/version_manager.php?status=" + e, function(html) {
					//console.log("recheck" + html);
					$('#status-' + e ).html(html + "% downloaded");
					versionwork[e] = html;
				});
				setTimeout(function() { check_status(e); }, s_refreshtime);
			}
		}
		check_status(false);
		
		function w_install(e) {
			if(e === false) return;
			var version = e;
			versionwork[e]=0;
			$('#button-'+version).prop('disabled', true);
			check_status(version);
			$('#status-'+version).html('working...');
			$.get("version_manager.php?install="+version, function(html) {
				// replace the "ajax'd" data to the table body
				if(html=="success") {
					$('#button-'+version).attr('onclick', 'return w_delete(\''+version+'\')').html('delete').prop('disabled', false);
				}
				versionwork[e]=-1;
				$('#status-'+version).html(html);
				return false;
			});
		}
		w_install(false);
		
		function w_delete(e) {
			if(e === false) return;
			var version = e;
			$('#button-'+version).prop('disabled', true);
			$('#status-'+version).html('working...');
			$.get("version_manager.php?delete="+version, function(html) {
				if(html=="success") {
					var dev = $('#dev-'+version).html();
					if (dev) {
						console.log('dev set for '+version);
						if( dev == "depreciated" ) {
							$('#span-'+version).html('Deleted. Re-installation unavailable.');
							$('#status-'+version).html(' ');
						} else {
							$('#button-'+version).attr('onclick', 'return w_install(\''+version+'\')').html('install').prop('disabled', false);
							$('#status-'+version).html(html);
						}
					} else {
						console.log('dev not set for '+version);
					}
					//$('#status-'+version).html(html);
				} else {
					$('#status-'+version).html(html);
				}
				return false;
			});
			//$('#status-'+version).html("p00t");
		}
		w_delete(false);
		
<?php
		echo "\t\tvar server_select = \"";
		if(isset($server_select)) { echo $server_select; }  else { echo "servertest"; }
		echo "\";\xA";

		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		echo "\t\t\t$('#welcome_user').text(user_name);\xA";

		// This is for displaying the server name & password in an input box
		echo "\t\t\t$('#link_home').attr('href',\"index.php?d=\"+server_select);\xA";
		echo "\t\t\t$('#link_logs').attr('href',\"logs.php?d=\"+server_select+\"#server_list-\"+server_select);\xA";
		echo "\t\t\t$('#link_config').attr('href',\"server-settings.php?d=\"+server_select+\"#server_list-\"+server_select);\xA";
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
