<?php 
if(!isset($_SESSION)) { session_start(); }
if(!isset($_SESSION['login'])) {
	header("Location: https://" . $_SERVER["HTTP_HOST"] . "/login.php");
	die();
} else {
	if($_SERVER["HTTPS"] != "on")
	{
		header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
		exit();
		die();
	}
}

$unicodeChar = '\u30b5';
echo json_decode('"'.$unicodeChar.'"');
die();

//Set the base directory the factorio servers will be stored
$base_dir="/var/www/factorio/";
include(getcwd().'/getserver.php');
if(!isset($server_select)) {
	//die('Invalid Server');
	$server_select = "servertest";
}
?>
<html>
<head>
	<script type="text/javascript" language="javascript" src="/assets/jquery-3.1.1.min.js"></script></head><body>
	<script type="text/javascript">
		function start(){
			document.querySelector('#upload_file').addEventListener('change', function(e) {
				if (this.value == "") {
					return;
				}
				document.getElementById('fileStatus').innerHTML = "";
				if (this.files[0]) {
					var file = this.files[0];
					if ( file.size > 31457280 ) {
						document.getElementById('fileStatus').innerHTML = "File is too big. Must be less than 30M";
						return;
					}
				} else {
					document.getElementById('fileStatus').innerHTML = "Error finding file.";
					return;
				}
				var fd = new FormData();
				fd.append("file", file);
				var xhr = new XMLHttpRequest();
				xhr.open('POST', 'upload.php?d=<?php echo $server_select; ?>&upload', true);
				  
				xhr.upload.addEventListener("progress", uploadProgress, false);
				xhr.addEventListener("load", uploadComplete, false);
				xhr.addEventListener("error", uploadFailed, false);
				xhr.addEventListener("abort", uploadCanceled, false);
				
				/*xhr.onload = function() {
					if (xhr.responseText) {
						alert(xhr.responseText);
					};
				};*/
				xhr.send(fd);
				this.value = "";
			}, false);
		}

		window.addEventListener("load", start, false);

		function uploadProgress(evt) {
			if (evt.lengthComputable) {
				var percentComplete = Math.round(evt.loaded * 100 / evt.total);
				document.getElementById('prog').value = percentComplete;
				if(document.getElementById('prog').value<100) {
					document.getElementById("prog").style.display = "block";
				} else {
					document.getElementById("prog").style.display = "none";
				}
			}
			else {
				document.getElementById('fileStatus').innerHTML = 'Error in percentage calculation';
			}
		}

		function uploadComplete(evt) {
			if(evt.target.readyState == 4 && evt.target.status == 200) {
					document.getElementById('fileStatus').innerHTML = evt.target.responseText;
			}
		}

		function uploadFailed(evt) {
			document.getElementById('fileStatus').innerHTML = "There was an error attempting to upload the file.";
			document.getElementById("prog").style.display = "none";
		}

		function uploadCanceled(evt) {
			document.getElementById('fileStatus').innerHTML = "The upload has been canceled by the user or the browser dropped the connection.";
			document.getElementById("prog").style.display = "none";
		}
		
		function upload_click() {
			$('#upload_file').click();
		}
	</script>
	<div>
		<button id="upload" name="upload_button" onClick="upload_click()">Upload</button>
		<input type="file" name="upload_file" id="upload_file" style="display: none;"> <a id="fileStatus"></a> <progress id="prog" value="0" max="100.0" style="display: none;"></progress>
	</div>
	<div>
		<?php $cur_serv=$server_select; include(getcwd().'/files.php'); ?>
	</div>

</body></html>
