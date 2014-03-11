<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

	define('CHECK_PHPCHESS', true);

	header("Content-Type: text/html; charset=utf-8");
	ini_set("output_buffering","1");
	session_start();  

	$isappinstalled = 0;
	include("../includes/install_check2.php");

	if($isappinstalled == 0){
		header("Location: ../not_installed.php");
	}
	ini_set('error_log', "error_log.log");

	// This is the vairable that sets the root path of the website
	$Root_Path = "../";
	$config = $Root_Path."bin/config.php";
	$Contentpage = "cell_admin_tournament_games.php";  

	require($Root_Path."bin/CSkins.php");

	//Instantiate the CSkins Class
	$oSkins = new CSkins($config);
	$SkinName = $oSkins->getskinname();
	$oSkins->Close();
	unset($oSkins);

	//////////////////////////////////////////////////////////////
	//Skin - standard includes
	//////////////////////////////////////////////////////////////

	$SSIfile = "../skins/".$SkinName."/standard_cfg.php";
	if(file_exists($SSIfile)){
		include($SSIfile);
	}
	//////////////////////////////////////////////////////////////

	require($Root_Path."bin/CR3DCQuery.php");
	require($Root_Path."bin/CAdmin.php");
	require($Root_Path."bin/config.php");
	require($Root_Path."includes/siteconfig.php");
	require($Root_Path."includes/language.php");
	require($Root_Path."includes/xml.php");

	//////////////////////////////////////////////////////////////
	//Instantiate the CR3DCQuery Class
	$oR3DCQuery = new CR3DCQuery($config);
	$bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
	//////////////////////////////////////////////////////////////

	////////////////////////////////////////////////
	//Login Processing
	////////////////////////////////////////////////
	//Check if admin is logged in already
	if(!isset($_SESSION['LOGIN']))
	{
		$login = "no";
		header('Location: ./index.php');
	}
	else
	{
		if($_SESSION['LOGIN'] != true){
			if(isset($_SESSION['UNAME']))
				unset($_SESSION['UNAME']);
			if(isset($_SESSION['LOGIN']))
				unset($_SESSION['LOGIN']);
			$login = "no";
			header('Location: ./index.php');
		}
		else
		{
			$login = "yes";
		}
	}
	////////////////////////////////////////////////

	if(!$bCronEnabled)
	{
		if($oR3DCQuery->ELOIsActive())
			$oR3DCQuery->ELOCreateRatings();
		$oR3DCQuery->MangeGameTimeOuts();
	}
	
	// This page lists games for a selected tournament. New games can be added
	// and existing games can be updated.
	
	// This variable will hold data to display on the page.
	$__pd = array();
	
	// A tournament id must be provided to be able to do anything.
	if(!isset($_GET['id']))
	{
		exit(_T('IDS_Admin_Tournament_Games_IDMISSING', $config));
	}
	else
	{
		$id = (int)$_GET['id'];
		$query = sprintf("SELECT t_id FROM c4m_tournament WHERE t_id = '%s'", mysql_real_escape_string($id));
		$result = mysql_query($query, $oR3DCQuery->link);
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Games_IDQUERYFAILED', $config));
		}
		$cnt = mysql_num_rows($result);
		if($cnt == 0)
		{
			exit(_T('IDS_Admin_Tournament_Games_IDINVALID', $config));
		}
		$__pd['tournament_id'] = $id;
	}
	
	if(!isset($__pd['error']))
	{
		// Check if a game delete request happened.
		if(isset($_GET['delete']))
		{
			$gid = $_GET['delete'];
			$result = delete_game($id, $gid, $oR3DCQuery);
			if($result['success'])
			{
				$str = _T('IDS_Admin_Tournament_Games_GAMEDELETED', $config);
				$__pd['delete_msg'] = preg_replace('/\{id\}/', $gid, $str);
			}
			else
			{
				$str = _T('IDS_Admin_Tournament_Games_GAMEDELETEERROR', $config);
				$__pd['delete_msg'] = preg_replace(array('/\{id\}/', '/\{error\}/'), array($gid, $result['errormsg']), $str);
			}
			$__pd['delete_success'] = $result['success'];
		}
		// Get the games data.
		$__pd['games'] = __get_games($__pd['tournament_id'], $oR3DCQuery, $config);
	}

	function __get_games($id, $main, $config)
	{
		// Get player list, because we want to show the player names.
		$result = mysql_query("SELECT player_id, userid FROM player", $main->link);
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$players[$row['player_id']] = $row['userid'];
		}
		// Now get all the games for this tournament.
		$query = sprintf("SELECT game.* FROM game, c4m_tournamentgames WHERE tg_tmid = '%s' AND game.game_id = c4m_tournamentgames.tg_gameid", mysql_real_escape_string($id));
		$result = mysql_query($query, $main->link) or die(mysql_error());
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Games_GAMESQUERYFAILED', $config));
		}
		$num = mysql_numrows($result);
		$games = array();
		$statuses = get_all_status_values($config);
		$completion_statuses = get_all_completion_status_values($config);
		if($num != 0){
			$i = 0;
			while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
				$g = array();
				$g['game_id'] = $row['game_id'];
				$g['white'] = $players[$row['w_player_id']] . '(' . $row['w_player_id'] . ')';
				$g['black'] = $players[$row['b_player_id']] . '(' . $row['b_player_id'] . ')';
				$g['status'] = $statuses[$row['status']];
				$g['completion_status'] = $completion_statuses[$row['completion_status']];
				$g['to_move'] = $row['next_move'] == 'w' ? 'W' : 'B';
				$castle = "";
				if($row['cast_ws']) $castle .= "%White: Short %";
				if($row['cast_wl']) $castle .= "%and Long,%";
				if($row['cast_bs']) $castle .= "% Black: Short%";
				if($row['cast_bl']) $castle .= "% and Long%";
				$g['castle_status'] = $castle;
				$g['started'] = date('M-d-Y H:i:s', $row['start_time']);
				$g['fen'] = $main->GetInitialGameFEN($config, $row['game_id']);
				$games[] = $g;
			}
		}
		return $games;
	}
  
	function delete_game($tid, $gid, $main)
	{
		// Ensure the game exists for the tournament.
		$query = sprintf("SELECT * FROM game, c4m_tournamentgames WHERE game.game_id = '%s' AND c4m_tournamentgames.tg_gameid = game.game_id AND c4m_tournamentgames.tg_tmid = '%s'",
			mysql_real_escape_string($gid),
			mysql_real_escape_string($tid));
		$result = mysql_query($query, $main->link);
		if($result === false)
		{
			$errormsg = _T('IDS_Admin_Tournament_Games_GAMEQUERYFAILED', $main->ChessCFGFileLocation);
		}
		$cnt = mysql_num_rows($result);
		$errormsg = "";
		if($cnt == 0)
		{
			$errormsg = _T('IDS_Admin_Tournament_Games_GAMEIDINVALID', $main->ChessCFGFileLocation);
		}
		else // Delete the game and any references to it.
		{
			// Games table.
			$query = sprintf("DELETE FROM game WHERE game_id = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR1', $main->ChessCFGFileLocation);
			// Tournament games table.
			$query = sprintf("DELETE FROM c4m_tournamentgames WHERE tg_gameid = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR2', $main->ChessCFGFileLocation);
			// Timing mode for games table.
			$query = sprintf("DELETE FROM cfm_game_options WHERE o_gameid = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR3', $main->ChessCFGFileLocation);
			// Move history table.
			$query = sprintf("DELETE FROM move_history WHERE game_id = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR4', $main->ChessCFGFileLocation);// Move history table.
			// Realtime games table.
			$query = sprintf("DELETE FROM cfm_gamesrealtime WHERE id = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR5', $main->ChessCFGFileLocation);// Realtime games table.
			// Games chat table.
			$query = sprintf("DELETE FROM c4m_gamechat WHERE tgc_gameid = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR6', $main->ChessCFGFileLocation);
			// Games draw table.
			$query = sprintf("DELETE FROM c4m_gamedraws WHERE tm_gameid = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR7', $main->ChessCFGFileLocation);
			// Custom FENs table.
			$query = sprintf("DELETE FROM c4m_newgameotherfen WHERE gameid = '%s'", mysql_real_escape_string($gid));
			$result = mysql_query($query, $main->link);
			if($result === false)
				$errormsg .= _T('IDS_Admin_Tournament_Games_QUERYERROR8', $main->ChessCFGFileLocation);
			
		}
		return array('success' => $errormsg == "" ? TRUE : FALSE, 'errormsg' => $errormsg);
	}
  
	function get_all_status_values($config)
	{
		return array('A' => _T('IDS_Game_Status_ACTIVE', $config), 'C' => _T('IDS_Game_Status_COMPLETED', $config), 'W' => _T('IDS_Game_Status_WAITING', $config), 'P' => _T('IDS_Game_Status_PENDING', $config));
	}
	
	function get_all_completion_status_values($config)
	{
		return array('B' => _T('IDS_GAME_CompletionStatus_BLACK', $config), 'W' => _T('IDS_GAME_CompletionStatus_WHITE', $config), 'D' => _T('IDS_GAME_CompletionStatus_DRAW', $config), 'I' => _T('IDS_GAME_CompletionStatus_INCOMPLETE', $config));
	}
	//var_dump($__pd);
  
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_62", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript_admin.php");?>
</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>