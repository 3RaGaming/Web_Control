<?php
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	die('Please sign in');
} else {
	if(isset($_SERVER["HTTPS"]) == false)
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

$server_select_dropdown = "toSelect = document.getElementById(\"server_select\");";
foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
	$dir = str_replace($base_dir, '', $dir);
	if($dir!="node_modules"&&$dir!="log") {
		if($temp_select=="$dir") {
			$server_select = $dir;
			$server_select_dropdown = $server_select_dropdown . '
			var opt = document.createElement("option");
			opt.value = "'.$server_select.'";
			opt.innerHTML = "'.$server_select.'";
			opt.selected = true;
			toSelect.add(opt);';
		} else {
			$server_select_dropdown = $server_select_dropdown . '
			var opt = document.createElement("option");
			opt.innerHTML = "'.$dir.'";
			opt.value = "'.$dir.'";
			toSelect.appendChild(opt);';
		}
	}
}

?>
