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
session_write_close();

		//var_dump($_REQUEST);
if(isset($_REQUEST['d'])&&isset($_REQUEST['s'])) {
	$base_dir="/var/www/factorio/";
	$html_dir="/var/www/html";
    include('../../getserver.php');
	if(isset($server_select)) {
		if($_REQUEST['s']) {
			$screen = $_REQUEST['s'];
			$screenlog = '/var/www/factorio/'.$server_select.'/screenlog.0';
			$chatlog = '/var/www/factorio/'.$server_select.'/chatlog.0';
			$find=array("<", ">", "\\");
			$repl=array("&lt;", "&gt;", "");
			if($screen=="chat"&&file_exists($chatlog)) {
				$output = shell_exec('cat '.$chatlog.' | tail -n 75');
				$output = str_replace($find, $repl, $output);
				echo str_replace(PHP_EOL, '', $output);         //add newlines
			} elseif($screen=="console"&&file_exists($screenlog)) {
				$output = shell_exec('cat '.$screenlog.' | tail -n 75');
				$output = str_replace($find, $repl, $output);
				echo str_replace(PHP_EOL, '', $output);         //add newlines
			}
		}
	}
}

?>
