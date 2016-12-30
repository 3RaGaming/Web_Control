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

$server_tab_list = "$( function() { $( \"#server_list\" ).tabs(); } );";
foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
	$dir = str_replace($base_dir, '', $dir);
	if($dir!="node_modules"&&$dir!="log") {
		if($temp_select=="$dir") {
			$server_select = $dir;
			$server_tab_list = $server_tab_list . '
			$("#server_list ul").append(\'<li><a href="#tabs-'.$server_select.'">'.$server_select.'</a></li>\');
			$("#server_list").append("<div id="'.$server_select.'">Dynamic tab for '.$server_select.'</div>");';
		} else {
			$server_tab_list = $server_tab_list . '
			$("#server_list ul").append(\'<li><a href="#tabs-'.$dir.'">'.$dir.'</a></li>\');
			$("#server_list").append("<div id="'.$dir.'">Dynamic tab for '.$dir.'</div>");';
		}
	}
}

?>
