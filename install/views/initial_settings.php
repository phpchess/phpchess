<style>
td.option {
	font-weight: bold;
	padding-top: 10px;
}
.values {
	margin-left: 1em;
}
.error {
	color: red;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - Initial Settings</h3>

<?php if($g_params['errors'] == NULL): ?>

<p>You now have the option to set some aspects of your phpChess site. Please select the settings as you wish to have them. You can change these later in the admin area.</p>

<form action="" method="POST">

	<table>
		<tr>
			<td class="option">Automatically Approve New Users</td>
		</tr>
		<tr>
			<td>
				<div class="values">
					<input type="radio" name="require_approval" checked="checked" value="1" size="40"/>Yes</br>
					<input type="radio" name="require_approval" value="0" size="40"/>No
				</td>
			</td>
		</tr>
		<tr>
			<td class="option">Game Timeouts (in Days)</td>
		</tr>
		<tr>
			<td>
				<div class="values">
					<table>
						<tr>
							<td>Snail</td>
							<td><input name="timeout_snail" id="timeout_snail" value="30" size="4" maxlength="4"/></td>
						</tr>
						<tr>
							<td>Slow</td>
							<td><input name="timeout_slow" id="timeout_slow" value="20" size="4" maxlength="4"/></td>
						</tr>
						<tr>
							<td>Normal</td>
							<td><input name="timeout_normal" id="timeout_normal" value="10" size="4" maxlength="4"/></td>
						</tr>
						<tr>
							<td>Fast</td>
							<td><input name="timeout_fast" id="timeout_fast" value="2" size="4" maxlength="4"/></td>
						</tr>
						<tr>
							<td>Blitz</td>
							<td><input name="timeout_blitz" id="timeout_blitz" value="0.01" size="4" maxlength="4"/></td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td class="option">Allow Avatar Uploads</td>
		</tr>
		<tr>
			<td>
				<div class="values">
					<input type="radio" name="uploads"value="1" size="40"/>Yes</br>
					<input type="radio" name="uploads" checked="checked"  value="0" size="40"/>No
				</div>
			</td>
		</tr>
		<tr>
			<td class="option">Maximum Number of Players</td>
		</tr>
		<tr>
			<td>
				<div class="values">
					<input name="max_players" id="max_players" value="1000" size="4" maxlength="4" />
				</div>
			</td>
		</tr>
		<tr>
			<td class="option">Language</td>
		</tr>
		<tr>
			<td>
				<div class="values">
					<select name="language" id="language">
					<?php 
					foreach($g_params['languages'] as $lang)
					{
						echo "<option name='language-$lang' value='$lang'>$lang</option>";
					}
					?>
					</select>
				</div>
			</td>
		</tr>
	</table>
	
	<input type="submit" name="next" id="next" value="Next"/>

</form>

<?php else: ?>

<form action="" method="POST">
	<div class="error">There was an error saving the initial settings:</div>
	<div class="error"><?php echo $g_params['errors']; ?></div>
	<p style="margin-top: 5px; margin-bottom: 5px;">Please click the 'Next' button to finish the install. You should then adjust the server settings as required.</p>
	<input type="submit" name="continue" id="continue" value="Next" />
</form>

<?php endif;?>


<script>

$(function(){
	$('#next').click(function(){ return validate()});
});


function validate()
{
	$('.error').remove();
	var valid = true;
	var fields = ['#timeout_snail', '#timeout_slow', '#timeout_normal', '#timeout_fast', '#timeout_blitz'];
	for(var i = 0; i < fields.length; i++)
	{
		if(!validate_float($(fields[i]).val()))
		{
			valid = false;
			$(fields[i]).after('<span class="error">Value must be a number.</span>');
		}
	}
	var players = $('#max_players').val();
	if(!validate_uint(players))
	{
		valid = false;
		$('#max_players').after('<span class="error">Value must be a number.</span>');
	}
	
	return valid;
}

function validate_float(value)
{
	var patt = /^\d+$|^\.\d+$|^\d+\.?\d*$/;
	value = trim(value);
	return patt.test(value); 
}

function validate_uint(value)
{
	var patt = /^\d+$/;
	value = trim(value);
	return patt.test(value);
}

function trim(value)
{
	return value.replace(/^\s+|\s+$/g, '');
};

</script>