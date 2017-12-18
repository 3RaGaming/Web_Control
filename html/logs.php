<?php
$currentpage = 'logs';
require 'header.php';?>
<ul class="nav nav-tabs">
	<?php navbar(); ?>
</ul>
<?php $server_select = $server_select ?? "failed";
$server_dir = $base_dir . $server_select . "/";
if(isset($_REQUEST['d'])) {
	if($_REQUEST['d']=="managepgm") {
		$server_dir = $base_dir;
	} elseif($_REQUEST['d']!==$server_select||$server_select=="failed") {
		die('Error in check');
	}
}
$current_array = array("screenlog.0", "factorio-current.log");
foreach($current_array as $value) {
	if(file_exists($server_dir.$value)) {
		$file_full_path = "$server_dir/$value";
		$size = human_filesize("$file_full_path");
		$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
		echo " <a href='files.php?d=$base_dir&s=$server_select&f=$value&l=false'>$value</a> - $size - $date <br />";
	}
}
$full_dir = $server_dir . "logs";
foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
	$file_full_path = "$full_dir/$file";
	$size = human_filesize("$file_full_path");
	$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
	echo " <a href='files.php?d=$base_dir&s=$server_select&f=$file&l=true'>$file</a> - $size - $date <br />";
}
die(); ?>
<?php require 'footer.php';
die(); ?>
