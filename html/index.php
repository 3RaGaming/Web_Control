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
	
	if(isset($_SESSION['login']['level'])) { $user_level = $_SESSION['login']['level']; }  else { $user_level = "viewonly"; }
	if(isset($_SESSION['login']['user'])) { $user_name = $_SESSION['login']['user']; }  else { $user_name = "guest"; }
	if(isset($_SESSION['login']['reload_report'])) {
		$session['login']['reload_report'] = $_SESSION['login']['reload_report'];
		unset($_SESSION['login']['reload_report']);
	}
	//Set the base directory the factorio servers will be stored
	$base_dir="/var/www/factorio/";
	include('./getserver.php');
	if(!isset($server_select)) {
		if(isset($_REQUEST['d'])&&$_REQUEST['d']=="Managepgm") {
			$server_select = "server1";
		} else {
			die('Error in server selection index.php');
		}
	}
	if(isset($_SESSION['login']['cmd_history'][$server_select])) {
		$session['login']['cmd_history'][$server_select] = $_SESSION['login']['cmd_history'][$server_select];
	}
	session_write_close();
?>
</script>
<html>
<head>
	<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
	<script type="text/javascript">
		var server_select = "<?php if(isset($server_select)) { echo $server_select; }  else { echo "error"; } ?>";
		//you can try to change this if you really want. Validations are also done server side.
		//This is just for a better graphical experience, ie: if you're a viewonly account, why upload a file, just to be told you can't do that?

        var user = {
            name:  "<?php echo $user_name ?>",
            level: "<?php echo $user_name ?>"
        };

        // TODO remove this
        // user debug to js console.
        console.log(user);
<?php
//		echo "\t\tvar user_level = \"$user_level\";\xA";
//		echo "\t\tvar user_name = \"$user_name\";\xA";



		//his_array = ["/players", "/c print(\"hello\")"];
		//Things to only start doing after the page has finished loading
		echo "\t\t$(document).ready(function() {\xA";
		if(isset($session['login']['reload_report'])) {
			echo "\t\t\t$('#fileStatus').html('".$session['login']['reload_report']."');\xA";
			unset($session['login']['reload_report']);
		}
		if(isset($session['login']['cmd_history'][$server_select])) {
			echo "\t\t\this_array = ".json_encode($session['login']['cmd_history'][$server_select]).";\xA";
		}


		// This is for displaying the server name & password in an input box
		if(file_exists("$base_dir$server_select/server-settings.json")) {
			// 
			$server_settings = json_decode(file_get_contents("$base_dir$server_select/server-settings.json"), true);
			if($server_settings != NULL) {
				//Do we have a server
				if(isset($server_settings["name"])) {
					if($user_level == "viewonly") {
						echo "\t\t\t$('#server_name').hide();\xA";
					} else {
						$server_name = htmlspecialchars($server_settings["name"]);
						$server_name_length = strlen($server_name);
						if($server_name_length<20) {
							$server_name_length = 20;
						}
						echo "\t\t\t$('#server_name').attr('value',\"".addslashes($server_name)."\");\xA";
						echo "\t\t\t$('#server_name').attr('size',$server_name_length);\xA";
					}
					/*var_dump($server_settings);*/
				}
				if( isset($server_settings["game_password"]) && !empty($server_settings["game_password"]) ) {
					echo "\t\t\t$('#link_config').html('<i class=\"fa fa-lock\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
				} else {
					echo "\t\t\t$('#link_config').html('<i class=\"fa fa-unlock\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
				}
			} else {
				// Report file came back invalid
				echo "\t\t\t$('#server_name').attr('value',\"#ERROR: WITH server-settings.json#\");\xA";
				echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
				echo "\t\t\t$('#server_name').attr('size',30);\xA"; 
			}
		} else {
			// Report server-settings missing";
			echo "\t\t\t$('#server_name').attr('value',\"#ERROR: server-settings.json NOT FOUND#\");\xA";
			echo "\t\t\t$('#link_config').html('<i class=\"fa fa-exclamation\" aria-hidden=\"true\"></i> <a href=\"./server-settings.php?d=".$server_select."#server_list-".$server_select."\">config</a>');\xA";
			echo "\t\t\t$('#server_name').attr('size',40);\xA";
		}
		echo "\t\t\t$('#link_logs').attr('href',\"logs.php?d=".$server_select."#server_list-".$server_select."\");\xA";
		if(isset($server_select_dropdown)) { echo $server_select_dropdown; } 
		echo "\t\t})\xA";
?>
	</script>
	<script type="text/javascript" language="javascript" src="assets/js/base.js"></script>
	<script type="text/javascript" language="javascript" src="assets/js/console.js"></script>
    <script type="text/javascript" language="javascript" src="assets/js/cpumeminfo.js"></script>
	<script src="https://use.fontawesome.com/674cd09dad.js"></script>
	<style type="text/css">@import "assets/css/base.css";</style>
    <style type="text/css">@import "assets/css/customalerts.css";</style>
</head>
<body>
	<div style="width: 99%; height: 99%;">
		<div style="float: left; width: 100%;">
			Welcome, <span id="welcome_user"><?php echo $user_name; ?></span>&nbsp;-&nbsp;
            <?php
                if($user_level === "admin") {
                    echo <<<ADMIN
                    <button onclick="server_sss('start')">Start</button>&nbsp; &nbsp;
                    <button onclick="server_sss('status')">Status</button>&nbsp;-&nbsp;
                    <button onclick="server_sss('stop')">Stop</button>&nbsp;-&nbsp;
                    <input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
                    <span id="link_config"><a href="./server-settings.php">config</a></span>&nbsp;-&nbsp;
                    <button onclick="update_web_control(user.level);">Update Web Control</button>
                    <form action="./update_web_control.php" method="POST" id="update_web_control" style="display: none;">
                        <input type="hidden" id="update" name="update" value="yes" />
                    </form>
                    <button onclick="force_kill('forcekill')">force kill</button>
                    <a id="link_logs" href="./logs.php">Logs</a>
ADMIN;
                } else {
                    echo <<<QUEST
                    <button onclick="server_sss('status')">Status</button>&nbsp;-&nbsp;
                    <input type="text" id="server_name" name="server_name" value="Name Here" />&nbsp;-&nbsp;
QUEST;
                }
            ?>
            <div style="float: right;">
				<select id="server_select"></select>&nbsp;-&nbsp;
				<a href="login.php?logout">Logout</a>
			</div>
            <div id="serverload" style="float: right; margin-right: 20px;">
                <span id="cpu" style="padding: 6px;background-color: rgb(102, 255, 0);">00 %</span>
                <span id="mem" style="padding: 6px;background-color: rgb(102, 255, 0);">0.00/0.00 GB</span>
            </div>

            <div style="float: right; margin-right: 20px;"><button onclick="customAlerts.show();">Alert log</button></div>

		</div>
		<!-- console and chat windows -->
		<div style="width: 52%; height: 99%; float: left;">
            <?php echo ($user_level !== "admin")? "": "<textarea id='console' style='width: 98%; height: 46%;'></textarea>"; ?>
			<textarea id="chat" style="width: 98%; height: 46%;"></textarea><br />
			<input type="text" id="command" placeholder="" style="width: 98%;" />&nbsp;
			<button id="command_button">Send</button>
		</div>
		<!-- server files -->
		<div style="width: 46%; height: 99%; float: left;">
			<div>
                <?php
                if($user_level === "admin") {
                    echo <<<ADMIN
                        <input type="file" name="upload_file" id="upload_file" style="display: none;">
                        <button id="upload_button" name="upload_button" style="background-color: #ffffff;">Upload</button>
                        <button id="Transfer" style="background-color: #ffffff;">Transfer</button>&nbsp;:&nbsp;
                        <button id="archive" style="background-color: #ffffff;">Archive</button>&nbsp;:&nbsp;
                        <button id="delete_files" name="delete_files" style="background-color: #ffcccc;">Delete</button>
                        <a id="fileStatus"></a>
                        <progress id="prog" value="0" max="100.0" style="display: none;"></progress>
ADMIN;
                } else {
                    // TODO no access to file transfer for guests?
                    echo <<<QUEST

QUEST;
                }
                ?>

			</div>
			<table id="fileTable" class="tablesorter">
				<thead>
					<tr>
						<th><input type="checkbox" style="margin: 0; padding: 0; height:13px;" checked="false" /></th>
						<th>File</th>
						<th>Size</th>
						<th>Creation</th>
						<th>Editor</th>
					</tr>
				</thead>
				<tbody>
					
				</tbody>
			</table>
			<iframe id="file_iframe" style="display:none;"></iframe>
		</div>
	</div>

    <div id="alert_modal" class="modal">
        <div id="content" class="modal-content">
            <span id="close_modal" class="close">&times;</span>
            <span id="reset_alerts" class="reset">Reset alerts</span>
            <p>Log of alerts</p>
            <div id="messages"></div>
        </div>
    </div>

    <script type="text/javascript" language="javascript" src="assets/js/customalerts.js"></script>

</body>
</html>
