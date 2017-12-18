<?php
$currentpage = 'index';
require 'header.php';
?>
<!--server restart and info nav-->
<nav class="navbar navbar-expand-md navbar-dark bg-dark">
	<ul class="navbar-nav mr-auto">
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('start')">Start</button></li>
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('status')">Status</button></li>
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('stop')">Stop</button></li>
	</ul>
	<ul class="navbar-nav">
		<li class="nav-item nav-link">cpu usage:<span id="cpu" class="server-info">00 %</span></li>
		<li class="nav-item nav-link">memory usage:<span id="mem" class="server-info">0.00/0.00 GB</span></li>
		<li class="nav-item nav-link"><button class="btn" onclick="force_kill('forcekill')">force kill</button></li>
	</ul>
</nav>
<div class="container-fluid">
	<!-- console and chat windows -->
	<div class="row">
		<div class="col-md-6 console-container">
			<textarea id="console" class="console-info"></textarea>
			<textarea id="chat" class="chat-info"></textarea><br />
			<input type="text" id="command" placeholder="Type a message/command here" style="width: 98%;" />&nbsp;
			<button class="btn btn-dark" id="command_button">Send</button>
		</div>
		<!-- server files -->
		<div class="col-md-6 console-container">
			<div>
				<input type="file" name="upload_file" id="upload_file" style="display: none;">
				<button class="btn btn-room btn-light" id="upload_button" name="upload_button">Upload</button>
				<button class="btn btn-room btn-light" id="Transfer">Transfer</button>
				<button class="btn btn-room btn-light" id="archive">Archive</button>
				<button class="btn btn-room btn-danger" id="delete_files" name="delete_files">Delete</button>
				<a id="fileStatus"></a>
				<progress id="prog" value="0" max="100.0" style="display: none;"></progress>
			</div>
			<table class="tablesorter">
				<thead>
					<tr>
						<th><input type="checkbox" style="margin: 0; padding: 0; height:13px;" checked="false" /></th>
						<th><a href="index.php?d=<?php echo $server_select ?>&sort=name" id="file"><h5>File</h5><a></th>
							<th><a href="index.php?d=<?php echo $server_select ?>&sort=size" id="size"><h5>Size</h5><a></th>
								<th><a href="index.php?d=<?php echo $server_select ?>&sort=date" id="date"><h5>Creation</h5><a></th>
								</tr>
							</thead>
							<tbody>
								<?php include './inc/functions/func_filetable.php'; ?></td>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
				<input type="hidden" id="update" name="update" value="yes" />
			</form>
			<?php require 'footer.php';?>
