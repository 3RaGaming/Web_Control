<?php
$currentpage = 'login';
require 'header.php';
require($_SERVER['DOCUMENT_ROOT'].'/'.$folder.'inc/functions/func_login.php');
?>
<script>
function show_hide(v_show, v_hide) {
	document.getElementById(v_show).style.display="block";
	document.getElementById(v_hide).style.display="none";
}
<?php
echo "\t\t$(document).ready(function() {\xA";
    if (isset($_POST['submit']) || (isset($clientid) && $clientid == "PUT_YOUR_BOT_CLIENT_ID_HERE") || !isset($clientid)) {
        echo "\t\t\t\tshow_hide('login-alt','login-discord');\xA";
    }
    echo "\t\t\t\t";
    echo(empty($userN)?'$("#uname").attr("placeholder","username");':'$("#uname").val("'.$userN.'");');
    echo "\xA";
    echo "\t\t\t});\xA";
    ?>
	</script>
	<div class="login-page">
		<div class="form">
			<span id="login-discord">
				<a href="#" onClick="show_hide('login-alt','login-discord');">Alternate Login</a><br /><br />
				<!-- legacy button
				<a id="" href="https://discordapp.com/oauth2/authorize?client_id=<?php echo $clientid; ?>&scope=identify%20guilds&redirect_uri=<?php echo $redirect_url; ?>&response_type=code">
					<img class="img-fluid" src="./assets/img/3rabutton.png" alt="Login With Discord"/>
				</a> -->
				<a href="https://discordapp.com/oauth2/authorize?client_id=<?php echo $clientid; ?>&scope=identify%20guilds&redirect_uri=<?php echo $redirect_url; ?>&response_type=code" class="btn btn-block text-white login-discord" onClick="show_hide('login-discord','login-alt');"><img class="login-logo" src="<?php themepath(); ?>/img/Discord-Logo-White.png" alt="Login With Discord"/> Login with Discord</a>

			</span>
			<span id="login-alt" style="display: none;">
				<a href="#" class="btn btn-block text-white login-discord" onClick="show_hide('login-discord','login-alt');"><img class="login-logo" src="<?php themepath(); ?>/img/Discord-Logo-White.png" alt="Login With Discord"/> Login with Discord</a><br/>
				<form class="login-form" name="login" method="post">
					<input type="hidden" name="login" value="submit" />
					<input type="text" id="uname" name="uname" />
					<input type="password" name="passw" placeholder="password"/>
					<button onclick="document.login.submit();">login</button>
				</form>
			</span>
			<?php 	if (isset($report)) {
        echo "<br /><br />".$report;
    }
            if (isset($debug)) {
                echo "<br />debug enabled";
            }?>
		</div>
	</div>
	<div class="debug_text text-center">
		<?php debug_text(); ?>
	</div>
	<?php require 'footer.php'; ?>
