<style>
p.error {
	color: #CE3333;
	font-weight: bold;
}
span.query {
	color: #888;
	font-style: italic;
	margin-left: 1em;
	font-weight: normal;
}
span.msg {
	font-weight: normal;
	margin-left: 1em;
}
hr {
	border: 1px solid #CE3333;
}
</style>

<div class="format">
	<h3 class="title_h3">PHPCHESS UPGRADE - DATABASE CHANGES</h3>
	<br/>
	<form action="" method="POST">
	
	<?php if($g_params['changes_required']): ?>
	
		<p>The database check has been performed. Some changes to the database are required to ensure it can work with version 4.2. This will be done for you when you click 'Update Database'. Please note that this step can also be used to fix the database structure where some tables or fields have been accidentally deleted.</p>
		<br/>
		<button name="upgrade">Update Database</button>
		
	<?php elseif($g_params['upgraded']): ?>
	
		<?php if($g_params['success']): ?>
		<p>Database was updated.</p>
		<br/>
		<button name="next">Next</button>
		<?php else: ?>
		<p class="error">Errors encountered while updating the database.</p><br/>
		<?php
		foreach($g_params['errors'] as $error) 
		{
			echo '<p class="error">ERROR:&nbsp;<span class="msg">' . $error['msg'] . '</span></p>';
			echo '<p class="error">QUERY:&nbsp;<span class="query">"' . $error['query'] . '"</span></p>';
			echo '<hr/>';
		}
		?>
		<br/>
		<p>For help, please send an email to <a href="mailto:support@phpchess.com">support@phpchess.com</a> or use the <a href="http://www.phpchess.com/?page_id=12">contact form</a>.</p>
		<br/>
		<p>The update cannot proceed any further. If you have made a backup of the database and files you can restore them to make your server functional again.</p>

		
		<?php endif; ?>
	
	<?php else: ?>
	
		<p>The database does not need to be updated. Click the `Next` button to proceed.</p>
		<br/>
		<button name="next">Next</button>
		
	<?php endif; ?>
	
	</form>
	
</div>