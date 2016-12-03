<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: ./login.php");
	die();
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		die();
	}
}
if(!isset($_SESSION['login']['level'])) {
	die('error with user permissions');
}
//Set the base directory the factorio servers will be stored
$base_dir= __DIR__ . "/../../factorio/";
include( __DIR__ . '/../getserver.php');
if(!isset($server_select)) {
	die('Error in server selection files.php');
}

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

$trs='<tr>';
$tre='</tr>';
$tds='<td>';
$tdc='</td><td>';
$tde='</td>';

if(isset($server_select)) {
	$full_dir="$base_dir$server_select/saves/";
	$file_users_path = "$base_dir$server_select/saves.txt";
	if(file_exists($file_users_path)) {
		$filelist = file($file_users_path);
		foreach ($filelist as $fileuser) {
			$user_details = explode('|', $fileuser);
			if(isset($user_details[0])&&isset($user_details[1])) {
				$file_list[$user_details[0]]=$user_details[1];
			}
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
}

?>