<?php
	if(!isset($_SESSION)) { session_start(); }
	if(!isset($_SESSION['login'])) {
		header("Location: ./login.php");
		die();
	} else {
		if(isset($_SERVER["HTTPS"]) == false)
		{
			header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
			die();
		}
	}
	
	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "guest"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
	
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		header("Location: ./login.php");
		die('Error in server selection index.php');
	}
?>
</script>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		<?php if(isset($server_select)) { echo "\t\t$(\"logs\").attr(\"href\", \"./logs.php?$server_select\");\xA"; } ?>
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a guest, why upload a file, just to be told you can't do that?
<?php
		echo "\t\tvar user_level = \"$user_level\";\xA";
		echo "\t\tvar user_name = \"$user_name\";\xA";
		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		
		if(isset($server_select_dropdown)) { echo $server_select_dropdown; } 
		echo "\t\t})\xA";
?>
	</script>
	<script type="text/javascript" language="javascript" src="assets/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/console.js"></script>
	<style type="text/css">@import "assets/base.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user">..guest..</span>&nbsp;-&nbsp;

			<button onclick="update_web_control(user_level);">Update Web Control</button>
			<form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
				<input type="hidden" id="update" name="update" value="yes" />
			</form>
			<div style="float: right;">
				<select id="server_select"></select>&nbsp;-&nbsp;
				<a href="login.php?logout">Logout</a>
			</div>
		</div>
		<!-- console and chat windows -->
		<div style="width: 52%; height: 99%; float: left;">
			<textarea id="console" style="width: 98%; height: 92%;"></textarea>
		</div>
		<!-- server files -->
		<div style="width: 46%; height: 99%; float: left;">
			<table id="fileTable" class="tablesorter">
				<thead>
					<tr>
						<th>File</th>
						<th>Size</th>
						<th>Creation</th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>
</body>
</html>
