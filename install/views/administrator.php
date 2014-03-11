<style>
#form p {
	margin-top: 10px;
	margin-bottom: 10px;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - Administrator</h3>

<form action="" method="POST" id="form">

	<p>Here you enter your site administration user and password. Please note that if you are on a publicly accessible server the phpchess administration area gives access to usernames and your phpchess configuration. So make sure that your password is secure and your user name is not easy to guess (so try to stay away from admin/pass and make it something more secure).</p>
	<p>The notification e-mail address informs the administrator if new people joined the server. If the server fails to notify you of new players registering on your site, then you will have to configure the mail server parts manually (see the settings options in the administration area).</p>

	<table>
		<tr>
			<td>Admin Username</td>
			<td><input id="username" name="username" type="text" value="<?php echo $g_params['user']; ?>" /></td>
		</tr>
		<tr>
			<td>Password</td>
			<td><input id="password" name="password" type="password" value="<?php echo $g_params['pass']; ?>" /></td>
		</tr>
		<tr>
			<td>Confirm Password</td>
			<td><input id="password_confirm" name="password_confirm" type="password" value="<?php echo $g_params['pass']; ?>" /><span id="pass_match"></span></td>
		</tr>
		<tr>
			<td>Registration Notification Email</td>
			<td><input id="email" name="email" type="text" value="<?php echo $g_params['email']; ?>" /></td>
		</tr>
	</table>
	
	<input type="submit" name="back" value="Back" /><input type="submit" name="next" value="Next" onclick="return check_fields()"/>

</form>


<script>

$(function(){
	$('#password_confirm').change(confirm_password);
});

function confirm_password()
{
	var val = $('#password_confirm').val();
	if(val != $('#password').val())
	{
		$('#pass_match').text('Passwords do not match!');
		return false;
	}
	$('#pass_match').empty();
	return true;
}

function check_fields()
{
	if($('#username').val() == '')
	{
		alert('You must fill in the `username` field');
		return false;
	}
	else if($('#password').val() == '')
	{
		alert('You must fill in the `password` field');
		return false;
	}
	else if($('#password_confirm').val() == '')
	{
		alert('You must fill in the `confirm password` field');
		return false;
	}
	else if($('#email').val() == '')
	{
		alert('You must fill in the `email` field');
		return false;
	}
	
	if(!confirm_password())
	{
		alert('Value of `password` and `confirm password` fields must match');
		return false;
	}
	
	return true;
}

</script>