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

function dropdown(){
	$server_select = $GLOBALS['server_select'];
	$base_dir = $GLOBALS['base_dir'];
	$dir = $GLOBALS['dir'];
	$url = url('index.php?d=', true);
	if(!isset($base_dir)) { die(); }
	if(isset($_REQUEST['d'])) {
		$temp_select=$_REQUEST['d'];
	}
	else {
		$temp_select="server2";
	}
	foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
		$dir = str_replace($base_dir, '', $dir);
		if($dir!="node_modules"&&$dir!="logs") {
			if($temp_select=="$dir") {
				$server_select = $dir;
				echo "<a class='dropdown-item active' href='$url$server_select'>$server_select</a>";
			} else {
				echo "<a class='dropdown-item' href='$url$dir'>$dir</a>";
			}
		}
	}
}


$server_tab_list = "\t\t\t$( function() { $( \"#server_list\" ).tabs(); } );";
foreach(glob("$base_dir*", GLOB_ONLYDIR) as $dir) {
	$dir = str_replace($base_dir, '', $dir);
	if($dir!="node_modules"&&$dir!="logs") {
		if($temp_select=="$dir") {
			$server_select = $dir;
		}
		$server_tab_list = $server_tab_list . '
		$("#server_list ul").append(\'<li><a href="#server_list-'.$dir.'" onClick="load_list(\\\''.$dir.'\\\');">'.$dir.'</a></li>\');
		$("#server_list").append(\'<div id="server_list-'.$dir.'"></div>\');';
	}
}
$server_tab_list = $server_tab_list . "
";

?>
