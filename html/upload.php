<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . "/login.php");
	die();
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		exit();
		die();
	}
}
//Set the base directory the factorio servers will be stored
$base_dir="/var/www/factorio/";
include(getcwd().'/getserver.php');
if(!isset($server_select)) {
	//die('Invalid Server');
	$server_select = "servertest";
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
		die();
	}
	//no reason to carry on
	die();
} elseif(isset($_REQUEST['upload'])) {
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


?>
