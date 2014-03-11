<div id="container">
	<table id="grid" style="display: none"></table>
	<div id="form" style="display: none"></div>
</div>

<style>
	.fbutton {
		color: red;
		font-weight: bold;
	}
</style>

<script type="text/javascript">

var LANG = {
	validation_errors: '<?php echo __l('Encountered Validation Errors:') ;?>',
	field: '<?php echo __l('field') ;?>',
	value_required: '<?php echo __l('A value is required') ;?>',
	expected_positive_int: '<?php echo __l('A positive integer value is expected.') ;?>',
	expected_int: '<?php echo __l('A integer value is expected.') ;?>',
	expected_positive_number: '<?php echo __l('A positive number is expected.') ;?>',
	expected_number: '<?php echo __l('A number is expected.') ;?>',
	invalid_chars_or_format: '<?php echo __l('The value contains invalid characters or is in the wrong format') ;?>',
	expected_unique: '<?php echo __l('The value must be unique. Another record already uses this value') ;?>',
	value_too_long: '<?php echo __l('The value is too long.') ;?>',
	value_invalid_list: '<?php echo __l('The value is invalid. Please use one from this list: ') ;?>',
	unmet_db_constraint: '<?php echo __l('The value does not meet a constraint set in the database') ;?>'
}

$(document).ready(function(){
	var thetable = new Table();
	thetable.initialise('#grid', <?php echo $table_init_options !== FALSE ? $table_init_options : '{}' ?>);
});


</script>