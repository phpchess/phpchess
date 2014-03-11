<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<style>

.tournaments_tbl
{
	border-collapse: collapse;
}
.tournaments_tbl td, .tournaments_tbl th
{
	border: 1px solid #b9d7a0;
}

.tournaments_tbl tr.tournament_row:hover
{
	background-color: #5ea4b3;
	cursor: pointer;
}

.games_tbl
{
	border-collapse: collapse;
}
.games_tbl td, .games_tbl th
{
	border: 1px solid #b9d7a0;
}

.games_tbl tr.game_row:hover
{
	background-color: #5ea4b3;
	cursor: pointer;
}


</style>

<?php

	$user_id = $_SESSION['id'];

	$_str_my_tour = _T('IDS_CHESS_MEMBER_MY_TOURNAMENTS', $config);  	// My Tournaments
	$_str_all_tour = _T('IDS_PlayerTournament_ALL_TOURNAMENTS', $config);	// All tournaments
	$_str_name = _T('IDS_PlayerTournament_Tournaments_NAME', $config);  // name
	$_str_cutoff = _T('IDS_PlayerTournament_Tournaments_CUTOFF', $config);  	// cutoff
	$_str_start = _T('IDS_PlayerTournament_Tournaments_START', $config);  		// start
	$_str_comment = _T('IDS_PlayerTournament_Tournaments_COMMENT', $config);  	// comment
	$_str_click_view_games = _T('IDS_PlayerTournament_Tournaments_VIEW_GAMES', $config);  	// Click to view games
	$_str_white = _T("IDS_PlayerTournament_TGAMES_WHITE", $config);		// White
	$_str_black = _T("IDS_PlayerTournament_TGAMES_BLACK", $config);		// Black
	$_str_status = _T("IDS_PlayerTournament_TGAMES_STATUS", $config);	// Status
	$_str_click_play = _T('IDS_PlayerTournament_TGAMES_CLICK_PLAY', $config);  	// Click to play game
	$_str_click_view = _T('IDS_PlayerTournament_TGAMES_CLICK_VIEW', $config);  	// Click to view game
	$_str_click_replay = _T('IDS_PlayerTournament_TGAMES_CLICK_REPLAY', $config);  	// Click to replay game
	$_str_started = _T('IDS_Tournament_Status_STARTED', $config);  	// Started
	$_str_accepted = _T('IDS_Tournament_Status_ACCEPTED', $config);  	// Accepted
	$_str_new = _T('IDS_Tournament_Status_NEW', $config);  	// New
	$_str_planned = _T('IDS_Tournament_Status_PLANNED', $config);  	// Planned
	$_str_completed = _T('IDS_Tournament_Status_COMPLETED', $config);  	// Completed
	$_str_finalised = _T('IDS_Tournament_Status_FINALISED', $config);  	// Finalised
	
	// white win, black win, draw, incomplete.
	$comp_status_mapping = array('W' => GetStringFromStringTable("IDS_GAME_TXT_4", $config), 'B' => GetStringFromStringTable("IDS_GAME_TXT_5", $config), 'D' => GetStringFromStringTable("IDS_GAME_TXT_6", $config), 'I' => GetStringFromStringTable("IDS_CR3DCQUERY_TXT_128", $config));
	$turn_mapping = array('w' => $_str_white, 'b' => $_str_black);
	
	$view_mode = 'default';
	
	if(isset($_GET['view_tournament']))		// View games in a tournament.
	{
		$view_mode = 'view_tournament';
		$tid = $_GET['view_tournament'];
		
		$query = "SELECT player_id, userid FROM player";
		$return = mysql_query($query, $oR3DCQuery->link) or die(mysql_error());
		$players = array();
		while($row = mysql_fetch_assoc($return))
		{
			$players[$row['player_id']] = $row['userid'];
		}
		
		$query = sprintf("SELECT * FROM c4m_tournamentgames LEFT JOIN game ON game.game_id = c4m_tournamentgames.tg_gameid WHERE c4m_tournamentgames.tg_tmid = '%s'",
					mysql_real_escape_string($tid));
		$return = mysql_query($query, $oR3DCQuery->link) or die(mysql_error());
		$num = mysql_numrows($return);
		
		// echo "<tr class='header_row' ><th>White</th><th>Black</th><th>Status</th><th>Turn</th><th>Started</th></tr>";
		// echo "<table class='games_tbl'>";
		echo "<table width='100%' cellpadding='10'><tr><td align='center'>";
		echo '<table class="forumline games_tbl" width="600px" cellspacing="1" cellpadding="3" border="0" >';
		echo "<tr class='header_row'><th>$_str_white</th><th>$_str_black</th><th>" . _T("IDS_PlayerTournament_TGAMES_TURN", $config) . "</th><th>$_str_status</th><th width='1%'>" . _T("IDS_PlayerTournament_TGAMES_STARTED", $config) . "</th></tr>";
		for($i = 0; $i < $num; $i++)
		{
			$id = mysql_result($return, $i, "game_id");
			$initiator = trim(mysql_result($return, $i, "initiator"));
			$white = $players[trim(mysql_result($return, $i, "w_player_id"))];
			$black = $players[trim(mysql_result($return, $i, "b_player_id"))];
			//$status = mysql_result($return, $i, 'status');
			$completion_status = mysql_result($return, $i, 'completion_status');
			$start = trim(mysql_result($return, $i, "start_time"));
			$start = date('Y-m-d', $start);
			$turn = $completion_status == 'I' ? trim(mysql_result($return, $i, "next_move")) : '';
			$turn = $turn_mapping[$turn];
			$game_id = mysql_result($return, $i, "tg_gameid");
			
			$onclick_action = '';
			$title = '';
			
			// If the game is finished, direct the user to the game replay page.
			// If the game is active and the user is a participant then direct to the realtime
			// game play page. For non participants show the active game view page.
			if($completion_status == 'I') // unfinished
			{
				//echo "user_id, white, black: $user_id, $white, $black<br/>";
				$username = $_SESSION['user'];
				if($username == $white || $username == $black)	// is taking part in game
				{
					$onclick_action = "window.location=\"chess_game_rt.php?gameid=$game_id\"";
					$title = "$_str_click_play";
				}
				else
				{
					
					$onclick_action = "window.location=\"active_game_viewer.php?gid=$game_id\"";
					$title = "$_str_click_view";
				}
			}
			else	// finished
			{
				$onclick_action = "window.location=\"./pgnviewer/view_pgn_game.php?gameid=$game_id\"";
				$title = "$_str_click_replay";
			}
			
			echo "<tr class='game_row' onclick='$onclick_action' title='$title' ><td>$white</td><td>$black</td><td>$turn</td><td>".$comp_status_mapping[$completion_status]."</td><td>$start</td></tr>";
		}
		echo "</table>";
		echo "</td></tr></table>";
		
		$tournaments_label = empty($user_id) ? $_str_all_tour : $_str_my_tour;

	}
	else	// Display all tournaments the user is in, or all tournaments when not logged in.
	{
		//$query = "SELECT * FROM player LEFT JOIN player2 ON player.player_id = player2.player_id WHERE player2.player_id IS NULL ORDER BY player.userid Asc";
		
		if(empty($user_id))
		{
			$query = "SELECT * FROM c4m_tournament";
			$tournaments_label = $_str_all_tour;
		}
		else
		{
			$sub_query = "SELECT DISTINCT tg_tmid FROM c4m_tournamentgames WHERE tg_playerid = $user_id OR tg_otherplayerid = $user_id";
			$query = "SELECT * FROM c4m_tournament WHERE c4m_tournament.t_id IN ($sub_query)";
			$tournaments_label = $_str_my_tour;
		}
		//$query = "SELECT * FROM c4m_tournament LEFT JOIN c4m_tournamentgames ON t_id = c4m_tournamentgames.tg_tmid WHERE c4m_tournament.t_id IN ($sub_query)";
		$return = mysql_query($query, $oR3DCQuery->link) or die(mysql_error());
		$num = mysql_numrows($return);
		
		// // Get all tournament games for the tournaments the user is in.
		// $tournament_ids = array();
		// for($i = 0; $i < $num; $i++)
		// {
			// $tournament_ids[] = mysql_result($return, $i, "t_id");
		// }
		
		
		$i=0;
		$tment_status_map = array('N' => $_str_new, 'P' => $_str_planned, 'A' => $_str_accepted, 'C' => $_str_completed, 'F' => $_str_finalised, 'S' => $_str_started);
		echo "<h3>$tournaments_label</h3>";
		echo "<table width='100%' cellpadding='10'><tr><td align='center'>";
		echo '<table class="forumline tournaments_tbl" width="900px" cellspacing="1" cellpadding="3" border="0" >';
		echo "<tr class='header_row' ><th>$_str_name</th><th>$_str_status</th><th>$_str_cutoff</th><th>$_str_start</th><th>$_str_comment</th></tr>";
		while($i < $num)
		{
			$id = mysql_result($return, $i, "t_id");
			$name = trim(mysql_result($return, $i, "t_name"));
			//$type = trim(mysql_result($return, $i, "t_type"));
			$players = trim(mysql_result($return, $i, "t_playernum"));
			$cutoff = trim(mysql_result($return, $i, "t_cutoffdate"));
			$cutoff = date_format(date_create($cutoff), 'Y-m-d');
			$start = trim(mysql_result($return, $i, "t_startdate"));
			$start = date_format(date_create($start), 'Y-m-d');
			$comment = trim(mysql_result($return, $i, "t_comment"));
			$status = $tment_status_map[trim(mysql_result($return, $i, "t_status"))];
			echo "<tr class='tournament_row' title='$_str_click_view_games' onclick='window.location.href=(\"./chess_tournament_status.php?view_tournament=$id\")'><td>$name</td><td>$status</td><td>$cutoff</td><td>$start</td><td width='400'>$comment</td></tr>";
			$i++;
		}
		echo "</table>";
		echo "</td></tr></table>";
	}

?>

<br><br>
<center>
<?php if($view_mode == 'view_tournament'): ?>
<input type='button' name='btnViewTournaments' value='<?php echo "$tournaments_label"; ?>' class='mainoption' onclick="javascript:window.location = './chess_tournament_status.php';" />
<?php endif; ?>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';" />
</center>
<br>