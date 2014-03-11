<style>
p.error {
	color: #CE3333;
	font-weight: bold;
}
</style>

<div class="format">
	<h3 class="title_h3">PHPCHESS UPGRADE - UPDATE CONFIG FILE</h3>
	<br/>
	<form action="" method="POST">
	
	<?php if(!isset($g_params['ran_update'])): ?>
	
		<p>This step will update the config file. For the upgrade to version 4.2.0 of phpChess, a 'password salt' entry will be added into the config file. This value will be used to 'hash' the passwords in step 6 of the upgrade. Please make sure that after the upgrade is completed that you keep a copy of the 'password salt' in a safe place. If it is lost, the login function will no longer work.</p>
		<br/>
		<input type="submit" name="update" value="Update" />
		
	<?php else: ?>
	
		<?php if($g_params['error'] !== FALSE): ?>
		
			<p class="error">An error occurred while attempting to upgrade the config file:</p>
			<br/>
			<p class="error" style="margin-left: 2em;"><?php echo $g_params['error']; ?></p>
			<br/>
			<p class="error">Unfortunately the upgrade process cannot continue as the config file needs to be in a proper state for the next step.</p>
	
		<?php elseif($g_params['updated'] !== FALSE): ?>
			
			<?php echo '<p>' . $g_params['updated'] . '</p>'; ?>
			<br/>
			<input type="submit" name="next" value="Next" />
			
		<?php else: ?>
		
			<p>The config file did not require any updates.</p>
			<br/>
			<input type="submit" name="next" value="Next" />
			
		<?php endif; ?>
		
	<?php endif; ?>
	
	</form>
	
</div>