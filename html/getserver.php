<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	echo "Please sign in";
	die();
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		echo "Must use HTTPS";
		exit();
		die();
	}
}

if(!isset($base_dir)) { exit(); die(); }
if(isset($_REQUEST['d'])) {
	//echo "!!".$_REQUEST['d']."!!";
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
if(file_exists("repo_list.txt")) {
	$server_version_dropdown = "";
	$handle = fopen("repo_list.txt", "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $server_version_dropdown = $server_version_dropdown . '<option id="'.$line.'">'.$line.'</option>';
    }
    fclose($handle);
}
?>