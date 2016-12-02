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
	$temp_select=$_REQUEST['d'];
} else {
	$temp_select="servertest";
}

$server_select_dropdown = "var x = document.getElementById(\"server_version\");";
foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
	$dir = str_replace($base_dir, '', $dir);
	if($dir!="node_modules") {
		if($temp_select=="$dir") {
			$server_select_dropdown = $server_select_dropdown . '
			var option = document.createElement("option");
			option.text = "'.$dir.'";
			option.value = "'.$dir.'";
			x.add(option);
			x.options[x.options.selectedIndex].selected = true;';
		} else {
			$server_select_dropdown = $server_select_dropdown . '
			var option = document.createElement("option");
			option.text = "'.$dir.'";
			option.value = "'.$dir.'";
			x.add(option);';
		}
	}
}

?>