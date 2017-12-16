<?php
$currentpage = 'index';
require 'header.php';
require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/getserver.php');
require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/index-info.php');
?>

<!--menu-->
<nav class="navbar navbar-expand-xl navbar-dark bg-dark">
	<a class="navbar-brand" href="#">Webcontrol</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
		<span class="navbar-toggler-icon"></span>
	</button>
	<div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav mr-auto">
			<li class="nav-item">
				<a class="nav-link" href="<?php url('server-settings.php'); ?>">
					<button class="btn" type="button" name="button">config</button>
				</a>
			</li>
			<li class="nav-item">
				<a class="nav-link" href="#">
					<button class="btn" type="button" onclick="update_web_control(user_level);">Update Web Control</button>
				</a>
			</li>
			<li class="nav-item">
				<div class="nav-link server-name">
					<input type="text" id="server_name" name="server_name" value="Name Here" />
				</div>
			</li>
			<li class="nav-item">
				<a class="nav-link" id="link-logs" href="./logs.php">
					<button class="btn" type="button" name="button">logs</button>
				</a>
			</li>
		</ul>
		<ul class="navbar-nav">
			<li class="nav-item dropdown float-right">
				<a class="nav-link" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<button class="btn dropdown-toggle" type="button" name="button">Welcome, <span id="welcome_user">..guest..</span></button>
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
					<a class="dropdown-item" href="login.php?logout">Logout</a>
				</div>
			</li>
			<li class="nav-item dropdown">
				<a class="nav-link" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<button class="btn dropdown-toggle" type="button" name="button">servers</button>
				</a>
				<div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
					<?php dropdown(); ?>
				</div>
			</li>
		</ul>
	</div>
</nav>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
	<ul class="navbar-nav mr-auto">
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('start')">Start</button></li>
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('status')">Status</button></li>
		<li class="nav-item nav-link"><button class="btn" onclick="server_sss('stop')">Stop</button></li>
	</ul>
	<ul class="navbar-nav">
		<li class="nav-item nav-link">cpu usage:<span id="cpu" class="server-info">00 %</span></li>
		<li class="nav-item nav-link">memory usage:<span id="mem" class="server-info">0.00/0.00 GB</span></li>
		<li class="nav-item"><button class="btn" onclick="force_kill('forcekill')">force kill</button></li>
	</ul>
</nav>
<div class="container-fluid">
	<!-- console and chat windows -->
	<div class="row">
		<div class="col-md-6 console-container">
			<textarea id="console" class="game-info"></textarea>
			<textarea id="chat" class="game-info"></textarea><br />
			<input type="text" id="command" placeholder="Type a message/command here" style="width: 98%;" />&nbsp;
			<button class="btn btn-dark" id="command_button">Send</button>
		</div>
		<!-- server files -->
		<div class="col-md-6 console-container">
			<div>
				<input type="file" name="upload_file" id="upload_file" style="display: none;">
				<input type="hidden" name="upload_max_filesize_m" id="upload_max_filesize_m" />
				<input type="hidden" name="upload_max_filesize_b" id="upload_max_filesize_b" />
				<button class="btn btn-room btn-light" id="upload_button" name="upload_button">Upload</button>
				<button class="btn btn-room btn-light" id="Transfer">Transfer</button>
				<button class="btn btn-room btn-light" id="archive">Archive</button>
				<button class="btn btn-room btn-danger" id="delete_files" name="delete_files">Delete</button>
				<a id="fileStatus"></a>
				<progress id="prog" value="0" max="100.0" style="display: none;"></progress>
			</div>
			<table id="fileTable" class="tablesorter">
				<thead>
					<tr>
						<th><input type="checkbox" style="margin: 0; padding: 0; height:13px;" checked="false" /></th>
						<th><h5>File</h5></th>
						<th><h5>Size</h5></th>
						<th><h5>Creation</h5></th>
						<th><h5>Originator</h5></th>
					</tr>
				</thead>
				<tbody>

				</tbody>
			</table>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>
</div>
<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
	<input type="hidden" id="update" name="update" value="yes" />
</form>
<?php require 'footer.php';?>
