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

$server_select_dropdown = "toSelect = document.getElementById(\"server_version\");";
foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
	$dir = str_replace($base_dir, '', $dir);
	if($dir!="node_modules") {
		if($temp_select=="$dir") {
			$server_select = $dir;
			$server_select_dropdown = $server_select_dropdown . '
			option = document.createElement("option");
			option.innerHTML = "'.$server_select.'";
			option.value = "'.$server_select.'";
			toSelect.add(option);
			toSelect.options[x.options.selectedIndex].selected = true;';
		} else {
			$server_select_dropdown = $server_select_dropdown . '
			var option = document.createElement("option");
			option.innerHTML = "'.$dir.'";
			option.value = "'.$dir.'";
			toSelect.appendChild(option);';
		}
	}
}

?>