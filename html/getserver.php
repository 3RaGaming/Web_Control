<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	die('Please sign in');
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		die('Must use HTTPS');
	}
}

if(!isset($base_dir)) { die(); }
if(isset($_REQUEST['d'])) {
	$server_select_dropdown = "";
	foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
		$dir = str_replace($base_dir, '', $dir);
		if($_REQUEST['d']=="$dir") {
			$server_select = $dir;
			$server_select_dropdown = $server_select_dropdown . '<option value="' . $server_select . '" selected>' . $server_select . '</option>';
		} else {
			$server_select_dropdown = $server_select_dropdown . '<option value="' . $dir . '">' . $dir . '</option>';
		}
	}
}
?>