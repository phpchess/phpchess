<style>
p.error {
	color: #CE3333;
	font-weight: bold;
}
hr {
	border: 1px solid #CE3333;
}
</style>

<div class="format">
	<h3 class="title_h3">PHPCHESS UPGRADE - HASH PASSWORDS</h3>
	<br/>
	<form action="" method="POST">
	
	<?php if(!isset($g_params['ran_hashing'])): ?>
	
		<p>The player passwords and the admin password will now be hashed.</p>
		<br/>
		<input type="submit" name="hash" value="Hash Passwords" />
		
	<?php else: ?>
	
		<?php if($g_params['success']): ?>
		<p>Passwords have been hashed.</p>
		<br/>
		<input type="submit" name="next" value="Next" />
		
		<?php else: ?>
		
			<?php if($g_params['error_type'] == 'no_salt'): ?>
			
			<p class="error">There was an error hashing the passwords:</p>
			<br/>
			<p class="error" style="margin-left: 2em">The password salt is missing from the config file.</p>
			<br/>
			<p class="error">Please rerun the upgrade script. This should add a password salt to the config file. If this problem persists, please contact us.</p>
			
			<?php else: ?>
			
			<p class="error">There was an error hashing the passwords:</p>
			<br/>
			<p class="error" style="margin-left: 2em"><?php echo $g_params['error']; ?></p>
			<br/>
			<p class="error">Please report this error to us. The password hashing process may have stopped midway through converting player passwords. Do not run the upgrade script again! If you want you can revert your phpChess install using the files and database backups you have made.</p>
			
			<?php endif; ?>
		
		<?php endif; ?>
		
	<?php endif; ?>
	
	</form>
	
</div>