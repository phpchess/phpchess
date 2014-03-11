<?php

	if(!defined('CHECK_PHPCHESS')){
		die("Hacking attempt");
		exit;
	}
	// var_dump($__pd);
	// Create a table containing the tournaments  
?>

<style>
	.tournament_games_table 
	{
		font-size: 0.8em;
		cell-padding: 10 5 10 5px;
		width: 100%;
	}
	.success_msg
	{
		color: green;
	}
	.error_msg
	{
		color: red;
	}
</style>

<?php
	$_str_add = _T('IDS_Admin_Tournament_Games_ADD', $config);
	$_str_edit = _T('IDS_Admin_Tournament_Games_EDIT', $config);
	$_str_delete = _T('IDS_Admin_Tournament_Games_DELETE', $config);
	$_str_id = _T('IDS_Admin_Tournament_Games_ID', $config);
	$_str_white = _T('IDS_Admin_Tournament_Games_WHITE', $config);
	$_str_black = _T('IDS_Admin_Tournament_Games_BLACK', $config);
	$_str_status = _T('IDS_Admin_Tournament_Games_STATUS', $config);
	$_str_completion = _T('IDS_Admin_Tournament_Games_COMPLETION', $config);
	$_str_turn = _T('IDS_Admin_Tournament_Games_TURN', $config);
	$_str_updategame = _T('IDS_Admin_Tournament_Games_UDPATEGAME', $config);
	$_str_confirmdelete = _T('IDS_Admin_Tournament_Games_CONFIRMDELETE', $config);

?>

<div>
	<?php 
	if(isset($__pd['delete_success'])) 
		echo "<span class='success_msg'>" . $__pd['delete_msg'] . "</span>"; 
	else
		echo "<span class='error_msg'>" .  $__pd['delete_msg'] . "</span>";
	?>
</div>

<div>
	<span>&nbsp;</span><span style="float: right;"><a href="tournament_game_add.php?id=<?php echo $__pd['tournament_id']; ?>"><?php echo $_str_add;?></a>&nbsp;</span>
</div>
<table class="tournament_games_table">

<?php 	
	// $cols = array('game_id', 'white', 'black', 'status', 'completion_status', 'to_move', 'castle_status', 'started', 'fen');
	$cols = array('game_id', 'white', 'black', 'status', 'completion_status', 'to_move');
	$_headings = array('game_id' => $_str_id, 'white' => $_str_white, 'black' => $_str_black, 'status' => $_str_status, 'completion_status' => $_str_completion, 'to_move' => $_str_turn);
	echo "<tr>";
	foreach($cols as $col)
	{
		//$label = str_replace('_', ' ', $col);
		//$label = ucwords($label);
		$label = $_headings[$col];
		echo "<th class='col_$col' >$label</th>";
	}
	echo "<th>$_str_edit</th><th>$_str_delete</th>";
	echo "</tr>";
	foreach($__pd['games'] as $g)
	{
		echo "<tr>";
		foreach($cols as $col)
		{
			$val = $g[$col];
			if($col == 'game_id')
			{
				//$val = "<a href='$Root_Path/../pgnviewer/view_pgn_game.php?gameid=$val'>$val</a>";
				$val = "<span onclick=\"popup_game('$Root_Path/../pgnviewer/view_pgn_game.php?gameid=$val')\">$val</span>";
			}
			echo "<td>$val</td>";
		}
		echo "<td><a href='tournament_game_edit.php?tid=" . $__pd['tournament_id'] . "&gid=" . $g['game_id'] . "' title='$_str_updategame'>$_str_edit</a></td>";
		echo "<td><span onclick=\"if(confirm('$_str_confirmdelete')) window.location.href='tournament_games.php?id=" . $__pd['tournament_id'] . "&delete=" . $g['game_id'] . "'\">$_str_delete</span>";
		echo "</tr>";
	}
?>
	
	
</table>
<br /><br />
<a href="chess_tournament_v2.php"><?php echo _T('IDS_Form_Button_Back', $config); ?></a>

<script type="text/javascript">
	function popup_game(url)
	{
		window.open(url,"_blank", "width=580,height=420,resizable=yes,scrollbars=yes,status=yes");
	}
</script>