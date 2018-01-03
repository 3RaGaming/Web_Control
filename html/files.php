<?php $currentpage = 'files';
require 'header.php';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_REQUEST['p'] == 'upload') {
	upload();
} elseif (isset($_REQUEST['p']) && $_REQUEST['p'] == 'download') {
	download();
} elseif (isset($_REQUEST['p']) && $_REQUEST['p'] == 'delete') {
	delete_files();
} elseif (isset($_REQUEST['p']) && $_REQUEST['p'] == 'latest') {
	make_latest();
}


function upload(){
	$base_dir = $GLOBALS['base_dir'];
	$directory = $GLOBALS['directory'];
	if ($_REQUEST['d'] == 'managepgm') {
		$server_select = '';
	}else {
		$server_select = $_REQUEST['d'].'/';
	}
	//Valdidate name
	if(isset($_FILES['file']['name'])) {
		$filename = strtolower($_FILES['file']['name']);
	} else {
		die('Error n'.__LINE__.': Invalid File');
	}

	//Validate size
	if(isset($_FILES['file']['size'])) {
	} else {
		die('Error s'.__LINE__.': Invalid File');
	}

	if(isset($_FILES['file']['type'])) {
		$fileType = $_FILES['file']['type'];
		if( $fileType == "application/zip" || $fileType == "application/x-zip-compressed" || ($fileType == "application/octet-stream" && pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) == "zip") ) {
			//we good
		} else {
			die($fileType.'Invalid File Type');
		}
	} else {
		die('Error t'.__LINE__.': Invalid File');
	}

	if(isset($_FILES['file']['tmp_name'])) {
		$fileTmp = $_FILES['file']['tmp_name'];
		$zip = new ZipArchive();
		$res = $zip->open($fileTmp, ZipArchive::CHECKCONS);
		if ($res !== TRUE) {
			switch($res) {
				case ZipArchive::ER_NOZIP:
				unlink($fileTmp);
				die('Error z'.__LINE__.': Not a zip archive');
				case ZipArchive::ER_INCONS :
				unlink($fileTmp);
				die('Error z'.__LINE__.': Zip consistency check failed');
				case ZipArchive::ER_CRC :
				unlink($fileTmp);
				die('Error z'.__LINE__.': Zip checksum failed');
				default:
				unlink($fileTmp);
				die('Error z'.__LINE__.': Zip error ' . $res);
			}
		}
	} else {
		die('Error t'.__LINE__.': Invalid File');
	}

	$filename = preg_replace('/\s+/', '_', $filename);
	$full_file_path = $base_dir.$server_select."/saves/".$filename;
	////This didn't work. The fopen stream was adding strange data to the file, which would corrupt the zip archive somehow.
	//$fh = fopen('php://input','r') or die("Error opening the file");
	//$blob = fgets($fh, 5);
	//if (strpos($blob, 'PK') !== false) {
	//looks like it is a zip file
	//} else {
	//fclose($fh);
	//die( "invalid zip file" );
	//}
	$file_users_path = "$base_dir$server_select/saves.json";
	if(file_exists($file_users_path)) {
		$jsonString = file_get_contents($file_users_path);
		$file_list = json_decode($jsonString, true);
		$file_list_prehash = md5(serialize($file_list));
		if(isset($file_list[$filename])) {
			$session['login']['reload_report']='File "'.$filename.'" was replaced';
		}
	}
	$file_list[$filename] = 'user';

	if ($_FILES["file"]["error"] == UPLOAD_ERR_OK) {
		$move_uploaded_file = move_uploaded_file($fileTmp, $full_file_path);
		$file_list_prehash = null;
		if($move_uploaded_file == true) {
			$file_users_path = "$base_dir$server_select/saves.json";
			if(file_exists($file_users_path)) {
				//Grab file list json and put into array
				$jsonString = file_get_contents($file_users_path);
				$file_list = json_decode($jsonString, true);
				//md5 hash to check if it changes
				$file_list_prehash = md5(serialize($file_list));
				if(isset($file_list[$filename])) {
					$session['login']['reload_report']='File "'.$filename.'" was replaced';
				}
			}
			$file_list[$filename] = 'user';
			//if hash changes, a user over writ someones previous file, or a file has been aded
			if($file_list_prehash !== md5(serialize($file_list))) {
				$newJsonString = json_encode($file_list, JSON_PRETTY_PRINT);
				file_put_contents($file_users_path, $newJsonString);
			}
			//does echo do anything here?
			echo "complete";
		} else {
			$session['login']['reload_report']='Error u251: File failed to upload.';
		}
	} else {
		$session['login']['reload_report']='Error u254: '.$_FILES["file"]["error"];
	}
	if(isset($session['login']['reload_report'])) {
		if(!isset($_SESSION)) { session_start(); }
		$_SESSION['login']['reload_report'] = $session['login']['reload_report'];
		session_write_close();
	}
	//$pre = file_put_contents($full_file_path, $fh);
	//fwrite($fh, $pre);
	//fclose($fh);
	//no reason to carry on
	die();

}


function download(){
	$base_dir = $GLOBALS['base_dir'];
	$directory = $GLOBALS['directory'];
	if (isset($_REQUEST['d'])) {
		if ($_REQUEST['d'] == 'managepgm') {
			$server_select = '';
		}else {
			$server_select = $_REQUEST['d'].'/';
		}
		if (isset($_REQUEST['f'])) {
			$file_name = $_REQUEST['f'];
			$ext = pathinfo($_REQUEST['f'], PATHINFO_EXTENSION);
			if ($ext == '0' || $ext == 'log' || $ext == 'zip') {
				if ($_REQUEST['l'] == 'logs') {
					$folder = 'logs/';
				}elseif ($_REQUEST['l'] == 'saves') {
					$folder = 'saves/';
				}
				else {
					$folder = '';
				}
				if (!file_exists($directory.'tmp/')) {
					mkdir($directory.'tmp/', 0777);
				}
				$paste = $directory.'tmp/'.$file_name;
				$file = $base_dir . $server_select . $folder . $file_name;
				shell_exec("cp $file $paste");
				if (file_exists('./tmp/'.$file_name)){
					echo "Downloading". $file_name;
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename='.basename('./tmp/'.$file_name));
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize('./tmp/'.$file_name));
					ob_clean();
					flush();
					readfile('./tmp/'.$file_name);
					shell_exec("rm $paste");
					exit;
				} else {
					echo "File wasn't able to be pasted";
				}
			}else {
				echo "File not correct";
			}
		}else {
			echo "Filename not set";
		}
	}else {
		echo "Server not set";
	}}

	function delete_files(){
		if (isset($_REQUEST['d'])) {
			if (isset($_REQUEST['del'])) {
				$base_dir = $GLOBALS['base_dir'];
				$server_select = $_REQUEST['d'].'/';
				$file_array = explode(",", $_REQUEST['del']);
				$folder = 'saves/';
				foreach ($file_array as $file_name) {
					$file = $base_dir . $server_select . $folder . $file_name;
					if (file_exists($file)) {
						shell_exec("rm $file");
					}else {
						echo "The file you tried to delete does not exist";
					}
				}
				echo "this part works now?!";
			}else {
				echo "Pick a file to delete!";
			}
		} else {
			echo "Set a server!";
		}
	}

	function make_latest(){
		$directory = $GLOBALS['directory'];
		if (isset($_REQUEST['d'])) {
			if (isset($_REQUEST['latest'])) {
				$base_dir = $GLOBALS['base_dir'];
				$server_select = $_REQUEST['d'].'/';
				$file_name = $_REQUEST['latest'];
				$folder = 'saves/';
				$file = $base_dir . $server_select . $folder . $file_name;
				if (file_exists($file)) {
					shell_exec("touch $file");
				}else {
					echo "The file you tried to delete does not exist";
				}
				echo "this part works now?!";
			}else {
				echo "Pick a file to delete!";
			}
		} else {
			echo "Set a server!";
		}
	}
	?>
