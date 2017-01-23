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
		<title> Checking Discord Response </title>
		<script type="text/javascript" language="javascript" src="assets/jquery-3.1.1.min.js"></script>
		<script type="text/javascript">
			function checkPermissions(memberobject, rolesarray) {
				alert("Checking permissions");
				var roleid;
				var allowed = false;
				for (var i = 0; i < rolesarray.length; i++) {
					if (rolesarray[i].name == "Moderators") roleid = rolesarray[i].id;
				}
				for (var i = 0; i < memberobject.roles.length; i++) {
					if (memberobject.roles[i] == roleid) allowed = true;
				}
				alert("User allowed: " + allowed);
			}
			function getGuildRoles(memberobject, token) {
				alert("Getting list of roles");
				$.ajax({
					url: '/guilds/143772809418637313/roles',
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token);},
					success: function (returndata) {
						alert("Roles successfully retrieved");
						checkPermissions(memberobject, returndata);
					}
				});
			}
			function getGuildMember(userobject, token) {
				let userid = userobject.id;
				alert("Getting Guild Member of User ID " + userid);
				$.ajax({
					url: 'https://discordapp.com/api/oauth2/guilds/143772809418637313/members/' + userid,
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token);},
					success: function (returndata) {
						alert("Successfully retrieved Guild Member");
						getGuildRoles(returndata, token);
					}
				});
			}
			function onPageLoad() {
				alert("On Load Running");
				var checkerror = window.location.hash.split("&")[0].split("=");
				var token;
				if (checkerror[0] == "access_token") {
					token = checkerror[1];
				} else {
					//Ask Stud how to best to a redirect to the login screen here
				}
				alert("Token is " + token);
				$.ajax({
					url: 'https://discordapp.com/api/oauth2/users/{@me}',
					type: 'GET',
					dataType: 'json',
					beforeSend: function (xhr) {xhr.setRequestHeader("Authorization", "Bearer " + token);},
					success: function (returndata) {
						alert("Retrieved User ID");
						getGuildMember(returndata, token);
					}
				});
			}
			$(document).ready(onPageLoad());
		</script>
	</head>
	<body onLoad = "onPageLoad()">
		<p> "Nothing important here" </p>
	</body>
</html>