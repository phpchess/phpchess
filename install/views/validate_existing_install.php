<?php

	$is_validated = (isset($g_params['validated']) ? $g_params['validated'] : FALSE);
	$errors = (isset($g_params['errors']) ? $g_params['errors'] : '');

?>
<style>
p.error {
	color: #CE3333;
	font-weight: bold;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - VALIDATE EXISTING INSTALLATION</h3>


<form action="" method="POST">
<?php if($g_params['validated']): ?>
	<p>Everything is fine. Proceed.</p>
	<br/>
	<input type="submit" name="next" value="Next">
<?php else: ?>
	<p>This step checks that the installation is valid. When no errors are found you can proceed. In particular we check that the database connection is working and that a phpchess database is present. If you only have an empty bin/config.php file have a look at the sample config.php file provided in the directory and fill in the database server connection and authorization parts.</p>
	<br>
	<?php if($errors !== ''): ?>
		<p class='error'><?php echo $errors; ?></p>
	<?php endif; ?>
	<br/>
	<input type="submit" name="check" value="Check">
<?php endif; ?>
</form>