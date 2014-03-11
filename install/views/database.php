<?php
	$is_ok = "../skins/default/images/button_ok.png";
	$not_ok = "../skins/default/images/button_ok_grey.png";
	$info = "../skins/default/images/documentinfo.png";
	
	$host = isset($g_params['host']) ? $g_params['host'] : '';
	$user = isset($g_params['user']) ? $g_params['user'] : '';
	$pass = isset($g_params['pass']) ? $g_params['pass'] : '';
	$db = isset($g_params['db']) ? $g_params['db'] : '';
?>

<style>
.error {
	color: #CE3333;
	font-weight: bold;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - Database</h3>

<br/>

<form action="" method="POST">
	<?php if(!$g_params['connection_established']): ?>
	<p>The database settings entered here must match your MySQL server address. In many cases with local database the server address is "localhost". However there is no guarantee this is the case. If in doubt ask your technical support staff or hosting provider what server address should be used to connect to the database server.</p>
	<p>phpChess does not permit the remote creation of a database, so this must be done by you. Please contact your support staff to accomplish this. There are usually youtube videos available on how this is done.</p>
	<p>The username and password must match the MySQL user name. Please use the database specific user and password and try to avoid the master username and password.</p>
	<br/>
	<b>MySQL Connection Information</b>
	<br/><br/>
	<table id="form_table">
		<tr>
			<td>Server Address</td>
			<td><input id="hostname" name="hostname" type="text" value="<?php echo $host; ?>" /><br/><span style="font-style: italic; font-size: 0.8em;">Eg: localhost</span></td>
			<td><img id="hostname_ok" src="<?php echo ($g_params['host_ok'] ? $is_ok : $not_ok); ?>" /></td>
			<!--<td><img id="hostname_info" src="<?php echo $info; ?>" title="[INFO]" /></td>-->
		</tr>
		<tr>
			<td>User Name</td>
			<td><input id="username" name="username" type="text" value="<?php echo $user; ?>" /></td>
			<td><img id="username_ok" src="<?php echo ($g_params['user_ok'] ? $is_ok : $not_ok); ?>" /></td>
			<!--<td><img id="username_info" src="<?php echo $info; ?>" title="[INFO]" /></td>-->
		</tr>
		<tr>
			<td>Password</td>
			<td><input id="password" name="password" type="password" value="<?php echo $pass; ?>" /></td>
			<td><img id="password_ok" src="<?php echo ($g_params['pass_ok'] ? $is_ok : $not_ok); ?>" /></td>
			<!--<td><img id="password_info" src="<?php echo $info; ?>" title="[INFO]" /></td>-->
		</tr>
		<tr>
			<td>Database</td>
			<td><input id="database" name="database" type="text" value="<?php echo $db; ?>" /><br/><span style="font-style: italic; font-size: 0.8em;">Eg: phpchess</span></td>
			<td><img id="database_ok" src="<?php echo ($g_params['db_ok'] ? $is_ok : $not_ok); ?>" /></td>
			<!--<td><img id="database_info" src="<?php echo $info; ?>" title="[INFO]" /></td>-->
		</tr>
	</table>
	<br/><span class="error"><?php echo $g_stage_message; ?></span><br/><br/>
	<input type="submit" name="test" value="Test Connection" />
	
	<?php else: ?>
	
	<br/><?php echo $g_stage_message; ?><br/><br/>
	<input type="submit" name="next" value="Next" />
	
	<?php endif; ?>
</form>

<script>

$(document).ready(function(){
	
	//$('#hostname_info, #username_info, #password_info, #database_info').tooltip();
	
	//$('#hostname, #username, #password, #database').keyup(has_value).change(has_value);
});

// function has_value()
// {
	// var id = $(this).attr('id');
	// if($(this).val() != '')
		// $('#' + id + '_ok').attr('src', '<?php echo $supplied; ?>');
	// else
		// $('#' + id + '_ok').attr('src', '<?php echo $required; ?>');
// }

</script>