<?php

	if(!defined('CHECK_PHPCHESS')){
		die("Hacking attempt");
		exit;
	}
	//var_dump($__pd);
	// Create a table containing the tournaments  
	
	
	
	
?>

<style>
	.tournaments_table 
	{
		font-size: 0.8em;
	}
	.tournaments_table tr
	{
		max-height: 1em;
	}
	.col_name
	{
		min-width: 200px;
	}
	.no_records
	{
		width: 100%;
		height: 50px;
		text-align: center;
		font-size: 1.5em;
		margin-top: 25px;
	}
</style>

<?php echo $_str_filter; ?>: <select id="status_filter">
	<?php
		foreach($__pd['available_status_filters'] as $code => $desc)
		{
			$selected = ($code == $__pd['active_filter']) ? " selected='selected' " : '';
			echo "<option value='$code' $selected>$desc</option>";
		}
	?>
</select>

<div>
	<span>&nbsp;</span><span style="float: right;"><a href="tournament_add.php"><?php echo $_str_add; ?></a></span>
</div>
<table style="width: 100%;" class="tournaments_table">

<?php

	$cols = array('id', 'name', 'type', 'count', 'cutoff', 'start', 'comment', 'status');
	$_headings = array('id' => $_str_id, 'name' => $_str_name, 'type' => $_str_type, 'count' => $_str_count, 'cutoff' => $_str_cutoff, 'start' => $_str_start, 'comment' => $_str_comment, 'status' => $_str_status);
	echo "<tr>";
	foreach($cols as $col)
	{
		echo "<th>" . $_headings[$col] . "</th>";
	}
	echo "<th>$_str_edit</th><th>$_str_games</th>";
	echo "</tr>";

	foreach($__pd['tournaments'] as $t)
	{
		echo "<tr>";
		foreach($cols as $col)
		{
			$val = $t[$col];
			$title = "";
			if($col == 'comment')
			{
				$title = $val;
				if(strlen($val) > 50)
					$val = substr($val, 0, 50) . '...';
			}
			echo "<td class='col_$col' title='$title'>$val</td>";
		}
		echo "<td><a href='tournament_edit.php?id=" . $t['id'] . "' title='$_str_manage_info'>$_str_edit</a></td>";
		echo "<td><a href='tournament_games.php?id=" . $t['id'] . "' title='$_str_manage_games'>$_str_games</a></td>";
		echo "</tr>";
	}
	if(count($__pd['tournaments']) == 0)
	{
		echo "<tr><td colspan='" . (count($cols) + 2) . "'><div class='no_records'>$_str_no_records</div></td></tr>";
	}
?>
	
	
</table>

<script>

	$(document).ready(
		function()
		{
			$("#status_filter").change(changed_status);
		}
	);
	
	function changed_status()
	{
		window.location.href = "<?php echo "chess_tournament_v2.php?status="; ?>" + this.value;
	};

</script>