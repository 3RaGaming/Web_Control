<?php $currentpage = 'files';
require 'header.php';
if (isset($_REQUEST['p']) && $_REQUEST['p'] == 'download') {
	download();
} elseif (isset($_REQUEST['p']) && $_REQUEST['p'] == 'delete') {
	delete_files();
} elseif (isset($_REQUEST['p']) && $_REQUEST['p'] == 'latest') {
	make_latest();
}


function download(){
	$base_dir = $GLOBALS['base_dir'];
	$directory = $GLOBALS['directory'];
	if (isset($_REQUEST['s'])) {
		if ($_REQUEST['s'] == 'managepgm') {
			$server_select = '';
		}else {
			$server_select = $_REQUEST['s'].'/';
		}
		if (isset($_REQUEST['f'])) {
			$file_name = $_REQUEST['f'];
			$ext = pathinfo($_REQUEST['f'], PATHINFO_EXTENSION);
			if ($ext == '0' || $ext == 'log' || $ext == 'zip') {
				if ($_REQUEST['l'] == 'logs') {
					$folder = 'logs/';
				}elseif ($_REQUEST['l'] == 'saves') {
					$folder = 'saves/';
				}
				else {
					$folder = '';
				}
				if (!file_exists($directory.'tmp/')) {
					mkdir($directory.'tmp/', 0777);
				}
				$paste = $directory.'tmp/'.$file_name;
				$file = $base_dir . $server_select . $folder . $file_name;
				shell_exec("cp $file $paste");
				if (file_exists('./tmp/'.$file_name)){
					echo "Downloading". $file_name;
					header('Content-Description: File Transfer');
					header('Content-Type: application/octet-stream');
					header('Content-Disposition: attachment; filename='.basename('./tmp/'.$file_name));
					header('Expires: 0');
					header('Cache-Control: must-revalidate');
					header('Pragma: public');
					header('Content-Length: ' . filesize('./tmp/'.$file_name));
					ob_clean();
					flush();
					readfile('./tmp/'.$file_name);
					shell_exec("rm $paste");
					exit;
				} else {
					echo "File wasn't able to be pasted";
				}
			}else {
				echo "File not correct";
			}
		}else {
			echo "Filename not set";
		}
	}else {
		echo "Server not set";
	}}

	function delete_files(){
		if (isset($_REQUEST['d'])) {
			if (isset($_REQUEST['del'])) {
				$base_dir = $GLOBALS['base_dir'];
				$server_select = $_REQUEST['d'].'/';
				$file_array = explode(",", $_REQUEST['del']);
				$folder = 'saves/';
				foreach ($file_array as $file_name) {
					$file = $base_dir . $server_select . $folder . $file_name;
					if (file_exists($file)) {
						shell_exec("rm $file");
					}else {
						echo "The file you tried to delete does not exist";
					}
				}
				echo "this part works now?!";
			}else {
				echo "Pick a file to delete!";
			}
		} else {
			echo "Set a server!";
		}
	}

	function make_latest(){
		$directory = $GLOBALS['directory'];
		if (isset($_REQUEST['d'])) {
			if (isset($_REQUEST['latest'])) {
				$base_dir = $GLOBALS['base_dir'];
				$server_select = $_REQUEST['d'].'/';
				$file_name = $_REQUEST['latest'];
				$folder = 'saves/';
				$file = $base_dir . $server_select . $folder . $file_name;
				$paste = $directory.'tmp/'.$file_name;
				if (file_exists($file)) {
					if (!file_exists($directory.'tmp/')) {
						mkdir($directory.'tmp/', 0777);
					}
					shell_exec("cp $file $paste");
					if (file_exists($paste)) {
						shell_exec("rm $file");
						if (!file_exists($file)) {
							shell_exec("cp $paste $file");
							if (file_exists($file)) {
								shell_exec("rm $paste");
								if (file_exists($paste)) {
									echo "Wasn't able to remove temp file";
								}
							}else {
								echo "Wasn't able to copy back";
							}
						}else {
							echo "Wasn't able to remove old file";
						}
					}else {
						echo "Wasn't able to paste file";
					}
				}else {
					echo "The file you tried to delete does not exist";
				}
				echo "this part works now?!";
			}else {
				echo "Pick a file to delete!";
			}
		} else {
			echo "Set a server!";
		}
	}
	?>
