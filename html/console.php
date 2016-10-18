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

		//var_dump($_REQUEST);
if(isset($_REQUEST['d'])) {
	$base_dir="/var/www/factorio/";
	$html_dir="/var/www/html";
	include($html_dir.'/getserver.php');
	if(isset($server_select)) {
		if($_REQUEST['s']) {
			$screen = $_REQUEST['s'];
			$filename = '/var/www/factorio/'.$server_select.'/screenlog.0';  //about 500MB
			$find=array("<", ">");
			$repl=array("&lt;", "&gt;");
			if($screen=="chat") {
				$output = str_replace($find, $repl, shell_exec('grep -E -i \'CHAT|shout|\\[WEB\' '.$filename.' | tail -n 75'));
				echo str_replace(PHP_EOL, '', $output);         //add newlines
			} elseif($screen=="console") {
				$output = str_replace($find, $repl, shell_exec('grep -E -v \'CHAT|shout|\\[WEB|server_message\' '.$filename.' | tail -n 75'));
				echo str_replace(PHP_EOL, '', $output);         //add newlines
			}
		}
	}
}

?>
