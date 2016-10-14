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
$base_dir="/var/www/factorio/";
$html_dir="/var/www/html";
include($html_dir.'/getserver.php');
if(!isset($server_select)) {
	//die('Invalid Server');
	$server_select = "server1";
}

$filename = '/var/www/factorio/'.$server_select.'/screenlog.0';  //about 500MB
$find=array("<", ">");
$repl=array("&lt;", "&gt;");
$output = str_replace($find, $repl, shell_exec('grep -E -v \'CHAT|shout|\[WEB\[|server_message\' '.$filename.' | tail -n 50'));  //only print last 50 lines
echo str_replace(PHP_EOL, '', $output);         //add newlines
?>
