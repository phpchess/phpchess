<style>
.info {
	margin-top: 10px;
	margin-bottom: 20px;
}
.info tr td {
	padding-left: 10px;
}
p.error {
	color: #CE3333;
	font-weight: bold;
}
p.indent {
	margin-left: 1em;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - Installation</h3>

<?php if(!$g_params['ran_install']): ?>
<p>The following settings will be used in the installation:</p>
<table class="info">
	<tr>
		<td>Database:</td><td><?php echo $_SESSION['dbcon']['db']; ?></td>
	</tr>
	<tr>
		<td>Server Name:</td><td><?php echo $_SESSION['server']['name']; ?></td>
	</tr>
	<tr>
		<td>Server URL:</td><td><?php echo $_SESSION['server']['url']; ?></td>
	</tr>
	<tr>
		<td>Root Path:</td><td><?php echo $_SESSION['server']['abs']; ?></td>
	</tr>
	<tr>
		<td>Root Path for Avatars:</td><td><?php echo $_SESSION['server']['abs_avatar']; ?></td>
	</tr>
	<tr>
		<td>Admin Username:</td><td><?php echo $_SESSION['admin']['user']; ?></td>
	</tr>
	<tr>
		<td>Registration Email:</td><td><?php echo $_SESSION['admin']['email']; ?></td>
	</tr>
</table>
<?php else: ?>



<?php endif; ?>

<form action="" method="POST">
<?php if($g_params['ran_install']): ?>
	<?php if($g_params['result']['success']): ?>
	<p>Install Progress:</p><br/>
	<p class="indent"><?php echo $g_params['result']['progress']; ?></p>
	<br/>
	<p>Installation is complete.</p><br/>
	<?php else: ?>
	<p>Install Progress:</p><br/>
	<p class="indent"><?php echo $g_params['result']['progress']; ?></p>
	<br/>
	<p class="error">Error running the install:&nbsp;&nbsp;<i><?php echo $g_params['result']['error']; ?></i></p><br/>
	<input type="submit" name="back" value="Back" />
	<?php endif; ?>
	
	<input type="submit" name="next" value="Next" />
<?php else: ?>
	<?php if($g_params['can_install']): ?>
	<p>The installer is now ready to generate a config file and create the required database tables. Please click the `Install` button.</p>
	<br/>
	<input type="submit" name="back" value="Back" /><input type="submit" name="install" value="Install"/>
	<?php else: ?>
	<p class="error">The /bin folder is not writeable. Please make sure this folder is writeable.</p>
	<br/>
	<input type="submit" name="back" value="Back" /><input type="submit" name="recheck" value="Recheck" />
	<?php endif; ?>
	
<?php endif; ?>
</form>

<script>



</script>