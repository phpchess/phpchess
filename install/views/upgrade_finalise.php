<style>
.format p {
	padding-top: 0.5em;
	padding-bottom: 0.5em;
	text-align: initial;
}
p.error {
	color: red;
}
p.warning {
	color: #E29101;
	border: 1px solid #FFA300;
	padding: 10px 5px 10px 5px;
}
</style>

<div class="format">
<form action="" method="POST" id="form">
	<h3 class="title_h3">PHPCHESS UPGRADE - Finalising</h3>

	<?php if(isset($g_params['validated'])): ?>
		<p>
			<?php echo $g_params['result']; ?>
		</p>
		<?php if(isset($g_params['errors'])): ?>
		<p class="error">
			<?php echo $g_params['errors']; ?>
		</p>
		<?php endif; ?>
		<?php if(isset($g_params['warnings'])): ?>
		<p class="warning">
			<?php echo $g_params['warnings']; ?>
		</p>
		<?php endif; ?>
		<p>
			<input type="submit" name="next" value="Next" />
		</p>
	<?php else: ?>
		<p>
			This step will upgrade the config file if it needs to, as well as validating the upgraded installation. Click the 'Validate' button to proceed.
		</p>
		<p>
			<input type="submit" name="validate" value="Validate" />
		</p>
	<?php endif; ?>
	
</form>
</div>