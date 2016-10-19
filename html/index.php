<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	} else {
		if($_SERVER["HTTPS"] != "on")
		{
			header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
			die();
		}
	}
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include(getcwd().'/getserver.php');
	if(!isset($server_select)) {
		die('Error in server selection index.php');
	}
	
	if(file_exists("repo_list.txt")) {
		$server_version_dropdown = "";
		$handle = fopen("repo_list.txt", "r");
		if ($handle) {
			while (($line = fgets($handle)) !== false) {
				$server_version_dropdown = $server_version_dropdown . '<option id="'.$line.'">'.$line.'</option>';
			}
			fclose($handle);
		}
	}
?>
</script>
<html>
<head>
	<style type="text/css">@import "/assets/style_table.css";</style>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a guest, why upload a file, just to be told you can't do that?
		var user_level = "<?php if(isset($_SESSION['login']['level'])) { echo $_SESSION['login']['level']; }  else { echo "guest"; } ?>";
	</script>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/console.php?d=<?php echo $server_select; ?>"></script>
	<script type="text/javascript">
	function command_history(args) {
<?php
		if(isset($_SESSION['login']['cmd_history'][$server_select])) {
			//his_array = ["/players", "/c print(\"hello\")"];
			echo "his_array = ".json_encode($_SESSION['login']['cmd_history'][$server_select]).";\xA";
		}
?>
		if (typeof his_ind == 'undefined') {
			his_ind = -1;
		}
		if (typeof his_array == 'undefined') {
			his_array = [];
		}
		his_cou = his_array.length - 1;
		if (his_ind == -1) { sav_val = document.getElementById('command').value; }
		if (args == "up") {
			if (his_ind <= 23 && his_ind < his_cou) {
				his_ind = his_ind + 1;
			}
			if(his_ind == -1) {
				dis_val = sav_val;
			} else {
				dis_val = his_array[his_ind];
			}
			document.getElementById('command').value = dis_val;
		} else if (args == "down") {
			if (his_ind > 0) {
				his_ind = his_ind - 1;
				dis_val = his_array[his_ind];
			} else {
				his_ind = -1;
				dis_val = sav_val;
			}
			document.getElementById('command').value = dis_val;
		} else if (args == "add") {
			command_history_add();
		}
		
		//PHp injecting command history breaks this function. Haven't looked into why yet.
		function command_history_add() {
			if (args == "add") {
				his_array.unshift(document.getElementById('command').value);
				if (his_array.length > 25) {
					his_array.pop();
				}
			}
			his_ind = -1;
			document.getElementById('command').value = "";
		}
	}
	//table sorter
	<?php if($_SESSION['login']['user']!="guest") { ?>
	function Download(url) {
		document.getElementById('download_iframe').src = url;
	}
	<?php } ?>

	function start(){
		tc_console();
		$('#server_select').on('change', function() {
			window.location = "./?d=" + this.value ; // or $(this).val()
		});
	<?php if($_SESSION['login']['user']!="guest") { ?>

		document.querySelector('#upload_file').addEventListener('change', function() {
			if (this.value === "") {
				return;
			}
			var the_file;
			document.getElementById('fileStatus').innerHTML = "";
			if (this.files[0]) {
				the_file = this.files[0];
				if ( the_file.size > 31457280 ) {
					//This is also a server set limitation
					document.getElementById('fileStatus').innerHTML = "File is too big. Must be less than 30M";
					return;
				}
			} else {
				document.getElementById('fileStatus').innerHTML = "Error finding file.";
				return;
			}
			var fd = new FormData();
			fd.append("file", the_file);
			var xhr = new XMLHttpRequest();
			xhr.open('POST', 'files.php?d=<?php echo $server_select; ?>&upload', true);

			xhr.upload.addEventListener("progress", uploadProgress, false);
			xhr.addEventListener("load", uploadComplete, false);
			xhr.addEventListener("error", uploadFailed, false);
			xhr.addEventListener("abort", uploadCanceled, false);

			xhr.send(fd);
			this.value = "";
		}, false);
	<?php } ?>
	}
	<?php if($_SESSION['login']['user']!="guest") { ?>
	function uploadProgress(evt) {
		if (evt.lengthComputable) {
			var percentComplete = Math.round(evt.loaded * 100 / evt.total);
			document.getElementById('prog').value = percentComplete;
			if(document.getElementById('prog').value<100) {
				document.getElementById("prog").style.display = "block";
			} else {
				document.getElementById("prog").style.display = "none";
			}
		} else {
			document.getElementById('fileStatus').innerHTML = 'Error in percentage calculation';
		}
	}
	function uploadComplete() {
		if(evt.target.readyState == 4 && evt.target.status == 200) {
				document.getElementById('fileStatus').innerHTML = evt.target.responseText;
				if(evt.target.responseText.includes("complete")) {
					location.reload();
				}
		}
	}
	function uploadFailed() {
		document.getElementById('fileStatus').innerHTML = "There was an error attempting to upload the file.";
		document.getElementById("prog").style.display = "none";
	}
	function uploadCanceled() {
		document.getElementById('fileStatus').innerHTML = "The upload has been canceled by the user or the browser dropped the connection.";
		document.getElementById("prog").style.display = "none";
	}

	<?php } ?>
	window.addEventListener("load", start, false);

	$(document).ready(function() {
		$("#fileTable").tablesorter( {sortList: [[3,1]]} );
		$('#upload_button').on('click', function() {
			$('#upload_file').click();
		});
		$('#command').keydown(function(event) {
			if (event.keyCode == 13) document.getElementById('command_button').click();
			if (event.keyCode == 38) command_history('up');
			if (event.keyCode == 40) command_history('down');
		});
	}); 

		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		var user_level = "<?php if(isset($_SESSION['login']['level'])) { echo $_SESSION['login']['level']; }  else { echo "guest"; } ?>";
	</script>
	<style type="text/css">
		a:visited{
		  color:blue;
		}
		a:hover{
		  color:orange;
		}
	</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <?php echo $_SESSION['login']['user']; ?>&nbsp;-&nbsp;
			<button onclick="server_sss('start', server_select);">Start</button>&nbsp;-&nbsp;
			<button onclick="server_sss('status', server_select);">Status</button>&nbsp;-&nbsp;
			<button onclick="server_sss('stop', server_select);">Stop</button>&nbsp;-&nbsp;
			<input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
			<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />
			<select id="server_version"><?php if(isset($server_version_dropdown)) { echo $server_version_dropdown; } ?></select>
			<div style="float: right;">
				<select id="server_select"><?php if(isset($server_select_dropdown)) { echo $server_select_dropdown; } ?></select>&nbsp;-&nbsp;
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- console and chat windows -->
		<div style="width: 52%; height: 99%; float: left;">
			<textarea id="console" style="width: 98%; height: 46%;"></textarea>
			<textarea id="chat" style="width: 98%; height: 46%;"></textarea><br />
			<input type="text" id="command" placeholder="" style="width: 98%;" />&nbsp;
			<button id="command_button" onclick="command(server_select);">Send</button>
		</div>
		<!-- server files -->
		<div style="width: 46%; height: 99%; float: left;">
			<div>
				<input type="file" name="upload_file" id="upload_file" style="display: none;">
				<button id="upload_button" name="upload_button" style="background-color: #ffffff;">Upload</button>&nbsp;:&nbsp;
				<button id="Transfer" style="background-color: #ffffff;">Transfer</button>&nbsp;:&nbsp;
				<button id="archive" style="background-color: #ffffff;">Archive</button>&nbsp;:&nbsp;
				<button id="delete" style="background-color: #ffcccc;">Delete</button>&nbsp;:&nbsp;
				<a id="fileStatus"></a>
				<progress id="prog" value="0" max="100.0" style="display: none;"></progress>
			</div>
			<?php $cur_serv=$server_select; include(getcwd().'/files.php'); ?>
		</div>
	</div>
</body>
</html>
