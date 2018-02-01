<?php
$currentpage = 'logs';
require 'header.php';?>
<ul class="nav nav-tabs">
	<?php navbar(); ?>
</ul>
<?php
$server_dir = $base_dir . $server_select . "/";
if(isset($_GET['d'])) {
	if($_GET['d']=="managepgm") {
		$server_dir = $base_dir;
	} elseif($_GET['d']!==$server_select||$server_select=="failed") {
		die('Error in check');
	}
}
$current_array = array("screenlog.0", "factorio-current.log");
foreach($current_array as $value) {
	if(file_exists($server_dir.$value)) {
		$file_full_path = "$server_dir/$value";
		$size = human_filesize("$file_full_path");
		$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
		echo " <a href='files.php?p=download&d=$server_select&f=$value&l=current'>$value</a> - $size - $date <br />";
	}
}
$full_dir = $server_dir . "logs";
foreach(array_diff(scandir("$full_dir"), array('..', '.')) as $file) {
	$file_full_path = "$full_dir/$file";
	$size = human_filesize("$file_full_path");
	$date = date ("Y-m.M-d H:i:s", filemtime("$file_full_path"));
	echo " <a href='files.php?p=download&d=$server_select&f=$file&l=logs'>$file</a> - $size - $date <br />";
}
require 'footer.php';
die(); ?>
