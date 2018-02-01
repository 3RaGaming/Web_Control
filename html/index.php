<?php
$currentpage = 'index';
require 'header.php';
?>
<!--server restart and info nav-->
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
	<ul class="navbar-nav mr-auto">
		<li class="nav-item nav-link"><button class="btn btn-success" onclick="server_sss('start')"><i class="fa fa-play" aria-hidden="true"></i> Start</button></li>
		<li class="nav-item nav-link"><button class="btn btn-danger" onclick="server_sss('stop')"><i class="fa fa-pause" aria-hidden="true"></i> Stop</button></li>
		<li class="nav-item nav-link"><button class="btn btn-danger" onclick="force_kill('forcekill')"><i class="fa fa-stop" aria-hidden="true"></i> force kill</button></li>
		<li class="nav-item nav-link"><button class="btn btn-info" onclick="server_sss('status')"><i class="fa fa-question" aria-hidden="true"></i> Status</button></li>
	</ul>
	<ul class="navbar-nav">
		<li class="nav-item nav-link">cpu usage: <span id="cpu" class="server-info">00 %</span></li>
		<li class="nav-item nav-link">memory usage: <span id="mem" class="server-info">0.00/0.00 GB</span></li>
	</ul>
</nav>
<div class="container-fluid">
	<!-- console and chat windows -->
	<div class="row">
		<div class="col-md-6 console-container">
			<textarea id="console" class="console-info"></textarea>
			<textarea id="chat" class="chat-info"></textarea>
			<div class="input-group">
				<input type="text" id="command" placeholder="Type a message/command here" class="form-control" />
				<div class="input-group-append">
					<button class="btn btn-success" id="command_button" type="button"><i class="fa fa-angle-double-right" aria-hidden="true"></i></button>
				</div>
			</div>
		</div>
		<!-- server files -->
		<div class="col-md-6 console-container">
			<div class="btn-group w-100" role="group" aria-label="File Button Group">
				<button class="btn btn-secondary w-25" id="upload_button" name="upload_button"><i class="fa fa-upload" aria-hidden="true"></i> Upload</button>
				<button class="btn btn-secondary w-25" id="make_latest_"><i class="far fa-clock"></i> Make latest</button>
				<button class="btn btn-secondary w-25" id="archive"><i class="fa fa-archive" aria-hidden="true"></i> Archive</button>
				<button class="btn btn-danger w-25" id="delete_files_" name="delete_files"><i class="fa fa-trash" aria-hidden="true"></i> Delete</button>
			</div>
			<input type="file" name="upload_file" id="upload_file" style="display: none;">
			<a id="fileStatus"></a><br/>
			<progress class="progress-bar bg-success mt-2" id="prog" value="0" max="100.0" style="display: none;"></progress>
			<table class="tablesorter table table-dark">
				<thead>
					<tr>
						<th><input onclick="toggle()" type="checkbox" class="form-check-input" checked="false" /></th>
						<th><a href="index.php?d=<?php echo $server_select ?>&sort=name" id="file"><h5>File</h5><a></th>
						<th><a href="index.php?d=<?php echo $server_select ?>&sort=size" id="size"><h5>Size</h5><a></th>
						<th><a href="index.php?d=<?php echo $server_select ?>&sort=date" id="date"><h5>Creation</h5><a></th>
					</tr>
				</thead>
				<tbody id="file">
					<?php include './inc/functions/func_filetable.php'; ?></td>
				</tbody>
			</table>
		</div>
	</div>
	<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
		<input type="hidden" id="update" name="update" value="yes" />
	</form>
</div>
<?php require 'footer.php';?>
