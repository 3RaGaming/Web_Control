<?php
$currentpage = "server-settings";
require 'header.php';
?>
		<ul class="nav nav-tabs">
			<?php navbar(); ?>
		</ul>
<?php
$server_settings = file_get_contents($base_dir.$server_select."/server-settings.json");
$settings_array = json_decode($server_settings, true);
$tags = $settings_array['tags'];
print_r($settings_array);
?>
<form class="settings-form" action="./inc/functions/func_settings.php" method="post">
<table>
	<tbody>
		<tr>
			<td>Server name:</td>
			<td><input type="text" name="name" value="<?php echo $settings_array['name']; ?>"></td>
		</tr>
		<tr>
			<td>Description:</td>
			<td><input type="text" name="description" value="<?php echo $settings_array['description']; ?>"></td>
		</tr>
		<tr>
			<td>Tags:</td>
			<td><input type="text" name="tags" value='<?php echo json_encode($tags); ?>'></td>
		</tr>
		<tr>
			<td>Max players: <br> <?php echo $settings_array['_comment_max_players'] ?></td>
			<td><input type="text" name="max_players" value="<?php echo $settings_array['max_players']; ?>"></td>
		</tr>
		<tr>
			<td>Visibility:</td>
		</tr>
		<tr>
			<td><?php echo $settings_array['_comment_visibility'][0] ?></td>
			<td>
				<select name="public">
				<option value="true" <?php if ($settings_array['visibility']['public'] == 1) { echo "selected"; } ?>>True</option>
				<option value="false" <?php if ($settings_array['visibility']['public'] != 1) { echo "selected"; } ?>>False</option>
			</select>
		</td>
		</tr>
		<tr>
			<td><?php echo $settings_array['_comment_visibility'][1] ?></td>
			<td>
				<select name="lan">
					<option value="true" <?php if ($settings_array['visibility']['lan'] == 1) { echo "selected"; } ?>>True</option>
					<option value="false" <?php if ($settings_array['visibility']['lan'] != 1) { echo "selected"; } ?>>False</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Credentials Name: <br> <?php echo $settings_array['_comment_credentials']; ?></td>
			<td><?php if (!isset($settings_array['username'][0])) {
				echo "No username in server-settings.";
			} elseif (!isset($settings_array['password'][0]) && !isset($settings_array['token'][0])) {
				echo "No password or token set.";
			} else {
				echo $settings_array['username'];
			} ?></td>
		</tr>
		<tr>
			<td>Game password:</td>
			<td><input type="text" name="game_password" value="<?php echo $settings_array['game_password']; ?>"></td>
		</tr>
		<tr>
			<td>Max players: <br> <?php echo $settings_array['_comment_max_players'] ?></td>
			<td><input type="text" name="max_players" value="<?php echo $settings_array['max_players']; ?>"></td>
		</tr>
		<tr>
			<td>Verify users: <br> <?php echo $settings_array['_comment_require_user_verification'] ?> </td>
			<td>
				<select name="verify_users">
					<option value="true" <?php if ($settings_array['require_user_verification'] == 1) { echo "selected"; } ?>>True</option>
					<option value="false" <?php if ($settings_array['require_user_verification'] != 1) { echo "selected"; } ?>>False</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Max upload in KB/s <br> <?php echo $settings_array['_comment_max_upload_in_kilobytes_per_second']; ?></td>
			<td><input type="text" name="max_upload" value="<?php echo $settings_array['max_upload_in_kilobytes_per_second']; ?>"></td>
		</tr>
		<tr>
			<td>Minimum latency in ticks: <br> <?php echo $settings_array['_comment_minimum_latency_in_ticks']; ?></td>
			<td><input type="text" name="min_latency" value="<?php echo $settings_array['minimum_latency_in_ticks']; ?>"></td>
		</tr>
		<tr>
			<td>Ignore player limit: <br> <?php echo $settings_array['_comment_ignore_player_limit_for_returning_players']; ?></td>
			<td>
				<select name="ignore_limit">
					<option value="true" <?php if ($settings_array['ignore_player_limit_for_returning_players'] == 1) { echo "selected"; } ?>>True</option>
					<option value="false" <?php if ($settings_array['ignore_player_limit_for_returning_players'] != 1) { echo "selected"; } ?>>False</option>
				</select>
			</td>
		</tr>
		<tr>
			<td>Allow commands: <br> <?php echo $settings_array['_comment_allow_commands']; ?></td>
			<td>
				<select name="allow_commands">
					<option value="admins-only" <?php if ($settings_array['allow_commands'] === 'admins-only') { echo "selected"; } ?>>Admins-only</option>
					<option value="true" <?php if ($settings_array['allow_commands'] === 1) { echo "selected"; } ?>>True</option>
					<option value="false" <?php if (!isset($settings_array['allow_commands'][0])) { echo "selected"; } ?>>False</option>
				</select>
			</td>
		</tr>
	</tbody>
</table>

</form>
		<?php
		require 'footer.php';
		die(); ?>
