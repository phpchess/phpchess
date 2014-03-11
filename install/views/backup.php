<?php


?>
<style>
p.error {
	color: #CE3333;
	font-weight: bold;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - DATA BACKUP</h3>


<form action="" method="POST">
<?php if($g_params['did_backup']): ?>
	<p>A backup of the database has been made and is available in the '/install/myBackups' directory. Note that this file can only be downloaded using a FTP client.</p>
	<br/>
<?php else: ?>
	<?php if($g_params['error'] !== ''): ?>
		<p class='error'><?php echo $g_params['errors']; ?></p>
	<?php else: ?>
		<p>Here you can get a back-up of your database. We strongly recommend you get the back-up just in case. In the worst case scenario we can construct a working 4.2 database from an older database for you. You would basically proceed with a new installation of phpChess and then we would provide you with an sql script that would take the old games and players and enter these into the new structure. Depending on the effort we may need to charge a small fee for this. But maybe not, just sent us a mail and we'll let you know. </p>
	<?php endif; ?>
	<br>
	<input type="submit" name="do_backup" value="Get Backup">
	<input type="submit" name="next" value="Next">
<?php endif; ?>
</form>