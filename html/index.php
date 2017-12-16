<?php
$currentpage = 'index';
require 'header.php';
require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/getserver.php');
require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/index-info.php');
?>

	<!--menu-->
	<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <a class="navbar-brand" href="#">Webcontrol</a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarNavDropdown">
    <ul class="navbar-nav mr-auto">
      <li class="nav-item active">
        <a class="nav-link" href="#">Home <span class="sr-only">(current)</span></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">Features</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#">Pricing</a>
      </li>
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          servers
        </a>
				<select id="server_select"></select>&nbsp;-&nbsp;

        <div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
          <a class="dropdown-item" href="#">Action</a>
        </div>
      </li>
			</ul>
			<ul class="navbar-nav">
				<li class="nav-item dropdown float-right">
	        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	          Welcome, <span id="welcome_user">..guest..</span>
	        </a>
					<div class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
					<a class="dropdown-item" href="login.php?logout">Logout</a>
				</div>
			</li>
    </ul>
  </div>
</nav>
<div class="container-fluid">
	<div class="float-left">
		<button onclick="server_sss('start')">Start</button>&nbsp; &nbsp;
		<button onclick="server_sss('status')">Status</button>&nbsp;-&nbsp;
		<button onclick="server_sss('stop')">Stop</button>&nbsp;-&nbsp;
		<input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
		<span id="link_config"><a href="./server-settings.php">config</a></span>&nbsp;-&nbsp;
		<!--<input type="text" id="server_password" name="server_password" placeholder="server password" size="14" />-->
		<button onclick="update_web_control(user_level);">Update Web Control</button>
		<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
			<input type="hidden" id="update" name="update" value="yes" />
		</form>
		<button onclick="force_kill('forcekill')">force kill</button>
		<a id="link_logs" href="./logs.php">Logs</a>
		<div style="float: right;">

		</div>
		<div id="serverload" style="float: right; margin-right: 20px;">
			<span id="cpu" style="padding: 6px;background-color: rgb(102, 255, 0);">00 %</span>
			<span id="mem" style="padding: 6px;background-color: rgb(102, 255, 0);">0.00/0.00 GB</span>
		</div>
	</div>

	<!-- console and chat windows -->
	<div style="width: 52%; height: 99%; float: left;">
		<textarea id="console" style="width: 98%; height: 46%;"></textarea>
		<textarea id="chat" style="width: 98%; height: 46%;"></textarea><br />
		<input type="text" id="command" placeholder="" style="width: 98%;" />&nbsp;
		<button id="command_button">Send</button>
	</div>
	<!-- server files -->
	<div style="width: 46%; height: 99%; float: left;">
		<div>
			<input type="file" name="upload_file" id="upload_file" style="display: none;">
			<input type="hidden" name="upload_max_filesize_m" id="upload_max_filesize_m" />
			<input type="hidden" name="upload_max_filesize_b" id="upload_max_filesize_b" />
			<button id="upload_button" name="upload_button" style="background-color: #ffffff;">Upload</button>
			<button id="Transfer" style="background-color: #ffffff;">Transfer</button>&nbsp;:&nbsp;
			<button id="archive" style="background-color: #ffffff;">Archive</button>&nbsp;:&nbsp;
			<button id="delete_files" name="delete_files" style="background-color: #ffcccc;">Delete</button>
			<a id="fileStatus"></a>
			<progress id="prog" value="0" max="100.0" style="display: none;"></progress>
		</div>
		<table id="fileTable" class="tablesorter">
			<thead>
				<tr>
					<th><input type="checkbox" style="margin: 0; padding: 0; height:13px;" checked="false" /></th>
					<th>File</th>
					<th>Size</th>
					<th>Creation</th>
					<th>Originator</th>
				</tr>
			</thead>
			<tbody>

			</tbody>
		</table>
		<iframe id="file_iframe" style="display:none;"></iframe>
	</div>
</div>
<?php require 'footer.php';?>
