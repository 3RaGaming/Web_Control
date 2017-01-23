<?php
if(!isset($_SESSION)) { session_start(); }
if(isset($_SERVER["HTTPS"]) == false)
{
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
	die();
}

?>

<html>
	<head>
		<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
		<script>
			function checkPermissions(memberobject, rolesarray) {
				var roleid;
				var allowed = false;
				for (var i = 0; i < rolesarray.length; i++) {
					if (rolesarray[i].name == "Moderators") roleid = rolesarray[i].id;
				}
				for (var i = 0; i < memberobject.roles.length; i++) {
					if (memberobject.roles[i] == roleid) allowed = true;
				}
				alert(allowed);
			}
			function getGuildRoles(memberobject, token) {
				$.ajax({
					url: '/guilds/143772809418637313/roles' + userid;
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token)},
					success: function (returndata) {checkPermissions(memberobject, returndata)}
				});
			}
			function getGuildMember(userobject, token) {
				let userid = userobject.id;
				$.ajax({
					url: 'https://discordapp.com/api/oauth2/guilds/143772809418637313/members/' + userid;
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token)},
					success: function (returndata) {getGuildRoles(returndata, token)}
				});
			}
			$(document).ready(function() {
				var checkerror = window.location.hash.split("&")[0].split("=");
				var token;
				if (checkerror[0] == "token") {
					token = checkerror[1];
				} else {
					//Ask Stud how to best to a redirect to the login screen here
				}
				$.ajax({
					url: 'https://discordapp.com/api/oauth2/users/{@me}'
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token)},
					success: function (returndata) {getGuildMember(returndata, token)}
				});
			});
		</script>
	</head>
</html>