<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . "/login.php");
	die();
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		die();
	}
}
//Set the base directory the factorio servers will be stored
$base_dir="/var/www/factorio/";
include(getcwd().'/getserver.php');
if(!isset($server_select)) {
	//We want to know exactly which fie to download. If we don't have one, die!
	die('Invalid Server');
}

if(isset($_REQUEST['archive'])) {
	die('this feature not ready yet.');
	try
	{
		$a = new PharData('archive.tar');
		
		// ADD FILES TO archive.tar FILE
		$a->addFile('data.xls');
		$a->addFile('index.php');
		
		// COMPRESS archive.tar FILE. COMPRESSED FILE WILL BE archive.tar.gz
		$a->compress(Phar::GZ);
		
		// NOTE THAT BOTH FILES WILL EXISTS. SO IF YOU WANT YOU CAN UNLINK archive.tar
		unlink('archive.tar');
	} 
	catch (Exception $e) 
	{
		echo "Exception : " . $e;
	}
	die();
} elseif(isset($_REQUEST['download'])) {
	if(empty($_REQUEST['download']))
	{
		header("HTTP/1.0 400 Bad Request");
		exit;
	}
	//file download requested.

	// file download found on http://www.media-division.com/php-download-script-with-resume-option/
	// get the file request, throw error if nothing supplied
	 
	// hide notices
	@ini_set('error_reporting', E_ALL & ~ E_NOTICE);
	 
	//- turn off compression on the server
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 'Off');
	 
	// sanitize the file request, keep just the name and extension
	// also, replaces the file location with a preset one ('./myfiles/' in this example)
	$file_path = $_REQUEST['download'];
	$path_parts = pathinfo($file_path);
	$file_name  = $path_parts['basename'];
	$file_ext   = $path_parts['extension'];
	$file_path  = $base_dir . $server_select . "/saves/" . $file_name;
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
			header("Content-Disposition: attachment; filename=\"$file_name\"");
	 
			// set appropriate headers for attachment or streamed file
			if ($is_attachment)
					header("Content-Disposition: attachment; filename=\"$file_name\"");
			else
					header('Content-Disposition: inline;');
	 
			// set the mime type based on extension, add yours if needed.
			$ctype_default = "application/octet-stream";
			$content_types = array(
					"exe" => "application/octet-stream",
					"zip" => "application/zip",
					"tar.gz" => "application/tar+gzip"
					);
			$ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
			header("Content-Type: " . $ctype);
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
	
} elseif(isset($_REQUEST['upload'])) {
	if($_SESSION['login']['user']=="guest") {
		die('Guests may not upload files');
	} else {
		//Valdidate name
		if(isset($_FILES['file']['name'])) {
			$filename = strtolower($_FILES['file']['name']);
		} else { 
			die('Error n: Invalid File'); 
		}
		
		//Validate size
		if(isset($_FILES['file']['size'])) {
			if($_FILES['file']['size']<31457280) {
				$filesize = $_FILES['file']['size'];
			} else {
				die('File must be less than 30M'); 
			}
		} else { 
			die('Error s: Invalid File'); 
		}
		
		if(isset($_FILES['file']['type'])) {
			$fileType = $_FILES['file']['type'];
			if( $fileType == "application/zip" || $fileType == "application/x-zip-compressed" ) {
				//we good
			} else {
				die($fileType.'Invalid File Type');
			}
		} else {
			die('Error t: Invalid File');
		}
		
		if(isset($_FILES['file']['tmp_name'])) {
			$fileTmp = $_FILES['file']['tmp_name'];
			$zip = new ZipArchive();
			$res = $zip->open($fileTmp, ZipArchive::CHECKCONS);
			if ($res !== TRUE) {
				switch($res) {
					case ZipArchive::ER_NOZIP:
						unlink($fileTmp);
						die('Not a zip archive');
					case ZipArchive::ER_INCONS :
						unlink($fileTmp);
						die('zip consistency check failed');
					case ZipArchive::ER_CRC :
						unlink($fileTmp);
						die('zip checksum failed');
					default:
						unlink($fileTmp);
						die('zip error ' . $res);
				}
			}
		} else {
			die('Error t: Invalid File');
		}

		$filename = preg_replace('/\s+/', '_', $filename);
		$full_file_path = $base_dir.$server_select."/saves/".$filename;
		//file already exists check
		if(is_file($base_dir.$server_select."/saves/".$filename)) {
			die('file already exists');
		}
		
		//$fh = fopen('php://input','r') or die("Error opening the file");
		//$blob = fgets($fh, 5);
		//if (strpos($blob, 'PK') !== false) {
			//looks like it is a zip file
		//} else {
			//fclose($fh);
			//die( "invalid zip file" );
		//}
		$file_users_path = "$base_dir$server_select/saves.txt";
		if( strpos(file_get_contents($file_users_path),$filename) == false) {
			$file_users = fopen($file_users_path, 'a+');
			$line_to_write = $filename . "|" . $_SESSION['login']['user'] . "\n";
			fwrite($file_users, $line_to_write);
			fclose($file_users);
		}
		if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
			move_uploaded_file($fileTmp, $full_file_path);
			echo "complete";
		} else {
			die($_FILES["file"]["error"]);
		}
		//$pre = file_put_contents($full_file_path, $fh);
		//fwrite($fh, $pre);
		//fclose($fh);
		
		//no reason to carry on
		die();
	}
} else {
	/* THIS IS FOR FILE LIST AND SUCH */
	//This part is included from index.php
	if(!isset($base_dir)) { exit(); die(); }
	if(!isset($server_select)) { exit(); die(); }

	// function to print files size in human-readable form
	function human_filesize($file, $decimals = 2) {
		$bytes = filesize($file);
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}

	//I should make this cleaner...
	$tables='<table id="fileTable" class="tablesorter">';
	$tablee='</tbody></table>';
	$trs='<tr>';
	$tre='</tr>';
	$tds='<td>';
	$tdc='</td><td>';
	$tde='</td>';

	if(isset($server_select)) {
		$full_dir="$base_dir$server_select/saves/";
		if($_SESSION['login']['user']!="guest") {
			echo '<input type="file" name="upload_file" id="upload_file" style="display: none;"> <progress id="prog" value="0" max="100.0" style="display: none;"></progress>';
			echo '
			<button id="upload" name="upload_button" onClick="upload_click()" style="background-color: #ffffff;">Upload</button>
			<button id="Transfer" style="background-color: #ffffff;">Transfer</button>
			:
			<button id="archive" style="background-color: #ffffff;">Archive</button>
			:										
			<button id="delete" style="background-color: #ffcccc;">Delete</button>
			';
			echo '<a id="fileStatus"></a>';
		}
		echo $tables;
		echo "<thead><tr><th><input type=\"checkbox\" style=\"margin: 0; padding 0;  height:13px\" checked=\"false\" /></th><th>File</th><th>Size</th><th>Creation</th><th>Editor</th></tr></thead>
		<tbody>
		";
		$file_users_path = "$base_dir$server_select/saves.txt";
		if(file_exists($file_users_path)) {
			$filelist = file($file_users_path);
			foreach ($filelist as $fileuser) {
				$user_details = explode('|', $fileuser);
				$file_list[$user_details[0]]=$user_details[1];
			}
		}
		
		foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
			$file_full_path = "$full_dir$file";
			$size = human_filesize("$file_full_path");
			$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
			if($_SESSION['login']['user']=="guest") {
				echo "$trs$tds <input type=\"checkbox\" style=\"margin: 0; padding 0;  height:13px\" /> $tdc $file $tdc $size $tdc $date $tdc ";
			} else {
				echo "$trs$tds <input type=\"checkbox\" style=\"margin: 0; padding 0;  height:13px\" /> $tdc <a href=\"#\" onClick=\"Download('files.php?d=".$server_select."&download=".$file."')\">$file</a> $tdc $size $tdc $date $tdc ";
			}
			if(isset($file_list[$file])) { echo $file_list[$file]; } else { echo "server"; }
			echo " $tde $tre
			";
		}
		echo $tablee;
	
		// This is for displaying the server name in an input box
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			// 
			$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
			if($server_settings != NULL) {
				//Do we have a server
				if(isset($server_settings["name"])) {
					$server_name = htmlspecialchars($server_settings["name"]);
					$server_name_length = strlen($server_name);
					if($server_name_length<20) {
						$server_name_length = 20;
					}
					//$server_name = str_replace(array("'"), array("'\"'\"'"), htmlspecialchars($server_name));
					echo '<script>document.getElementById(\'server_name\').value = "'.addslashes($server_name).'";$(\'#server_name\').attr(\'size\','.$server_name_length.');</script>';
					/*var_dump($server_settings);*/
					echo '<iframe id="download_iframe" style="display:none;"></iframe>';
				}
				if(isset($server_settings["game_password"])) {
					$server_password = $server_settings["game_password"];
					if(!empty($server_password)) {
						$server_password_length = strlen($server_password);
						if($server_password_length<14) {
							$server_password_length = 14;
						}
						echo '<script>document.getElementById(\'server_password\').value = "'.addslashes($server_password).'";$(\'#server_password\').attr(\'size\','.$server_password_length.');</script>';
					}
				}
			} else {
				// Report file came back invalid
				echo '<script>document.getElementById(\'server_name\').value = "#ERROR WITH SERVER NAME#";$(\'#server_name\').attr(\'size\',30);</script>'; 
			}
		} else {
			// Report server-settings missing";
			echo '<script>document.getElementById(\'server_name\').value = "#ERROR: server-settings.json NOT FOUND#";$(\'#server_name\').attr(\'size\',40);</script>'; 
		}
	}
}


?>
