<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: ./login.php");
	die();
}
	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { die('error with user permissions'); }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
//Set the base directory the factorio servers will be stored
$base_dir="/var/www/factorio/";
include('../../getserver.php');
if(!isset($server_select)) {
	die('Error s'.__LINE__.': In server selection files.php');
}
session_write_close();

if(!isset($base_dir)) { die(); }
if(!isset($server_select)) { die(); }

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
	$file_users_path = "$base_dir$server_select/saves.json";
	if(file_exists($file_users_path)) {
		$jsonString = file_get_contents($file_users_path);
		$file_list = json_decode($jsonString, true);
	}
	
	foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
		$file_full_path = "$full_dir$file";
		$size = human_filesize("$file_full_path");
		$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
		if($user_level=="viewonly") {
			echo "$trs$tds <input type=\"checkbox\" style=\"margin: 0; padding 0;  height:13px\" /> $tdc $file $tdc $size $tdc $date $tdc ";
		} else {
			echo "$trs$tds <input type=\"checkbox\" id=\"filesCheck-$server_select-".bin2hex($file)."\" style=\"margin: 0; padding 0;  height:13px\" /> $tdc <a href=\"#\" onClick=\"Download('files.php?d=".$server_select."&download=".$file."')\">$file</a> $tdc $size $tdc $date $tdc ";
		}
		echo $file_list[$file] ?? "server";
		echo " $tde $tre
		";
	}
}

?>