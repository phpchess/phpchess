<style>
.format p {
	padding-top: 0.5em;
	padding-bottom: 0.5em;
}
.indent {
	margin-left: 1em;
	border-left: 5px solid #ddd;
	padding-left: 0.5em;
}
p.error {
	color: #CE3333;
	font-weight: bold;
}
ul.error {
	color: #CE3333;
	font-weight: bold;
}
</style>

<div class="format">
	<h3 class="title_h3">PHPCHESS INSTALLATION - Finishing</h3>

	<h3>You have now installed phpChess on your server.</h3>

	<p><b>Please see the following security aspects and make sure everything is checked as complete for secure operations:</b></p>
	
	<div class="indent">
		<?php if($g_params['showing_errors']): ?>
		<?php if($g_params['showing_all']): ?>
		<p>The 'error_reporting' setting in your php.ini file is set to E_ALL, meaning all errors and warnings possible will be displayed.</p>
		<?php endif; ?>
		<p>It is strongly suggested to only show errors and suppress warnings as phpchess will use some commands that might be deprecated in future releases.</p>
		<?php endif; ?>

		<i>For Linux Systems:</i>
		<ul>
			<li>
				The `bin` directory permissions should be set to 755.
			</li>
			<li>
				If you do not allow users to upload avatars, set the permissions for the `/avatar/USER` and `/avatar/USER/tmp` folders to 755 .<br/>
				If you are allowing uploads then the permissions for those two folders should be set to 777.
			</li>
		</ul>
	</div>
	
	<?php if($g_params['check_failed']): ?>
	
	<p class="error">
		Permission checks failed. The following errors were encountered:
	</p>
	<ul class="error">
		<?php foreach($g_params['errors'] as $error){echo '<li>' . $error . '</li>';} ?>
	</ul><br/>
	
	<?php endif; ?>
	
	<form action="" method="POST">
		<button name="check" >Check Permissions</button>
		<button name="skip" >Skip Check</button>
	</form>
	
</div>