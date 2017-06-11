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

	// This is the vairable that sets the root path of the website
	$Root_Path = "../";
	$config = $Root_Path."bin/config.php";
	$Contentpage = "cell_admin_tournament_game_edit.php";  

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
	
	
	
	
	// This page provides a form for updating existing tournament games.
	
	// This variable will hold data to display on the page.
	$__pd = array();

	// A tournament id must be provided to be able to do anything..
	if(!isset($_GET['tid']))
	{
		$__pd['error'] = _T('IDS_Admin_Tournament_Games_Edit_IDMISSING', $config);
	}
	else
	{
		$tid = (int)$_GET['tid'];
		$query = sprintf("SELECT t_id, t_name, t_comment, t_startdate FROM c4m_tournament WHERE t_id = '%s'", mysqli_real_escape_string($oR3DCQuery->link,$tid));
		$result = mysqli_query($oR3DCQuery->link,$query);
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Games_Edit_QUERYFAILED', $config));
		}
		$cnt = mysqli_num_rows($result);
		if($cnt == 0)
		{
			$__pd['error'] = _T('IDS_Admin_Tournament_Games_Edit_IDINVALID', $config);
		}
		else
		{
			$t = mysqli_fetch_array($result, MYSQLI_ASSOC);
			$__pd['tournament'] = $t;
		}
		
	}
	
	// The game id must be valid and the game must belong to this tournament.
	$game = array();
	if(!isset($__pd['error']))
	{
		$gid = $_GET['gid'];
		$query = sprintf("SELECT * FROM game, c4m_tournamentgames WHERE game.game_id = '%s' AND c4m_tournamentgames.tg_gameid = game.game_id AND c4m_tournamentgames.tg_tmid = '%s'",
			mysqli_real_escape_string($oR3DCQuery->link,$gid),
			mysqli_real_escape_string($oR3DCQuery->link,$tid));
		$result = mysqli_query($oR3DCQuery->link,$query);
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Games_Edit_GAMEIDQUERYFAILED', $config));
		}
		$cnt = mysqli_num_rows($result);
		if($cnt == 0)
		{
			$__pd['error'] = _T('IDS_Admin_Tournament_Games_Edit_GAMEIDINVALID', $config);
		}
		else
		{
			$game = mysqli_fetch_array($result, MYSQLI_ASSOC);
		}
	}
	
	if(!isset($__pd['error']))
	{
		// Field settings. Used for validation purposes and setting-up/customising the form.
		$players = get_active_player_list($oR3DCQuery);
		$fields = array();
		$fields['status'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Games_Edit_STATUS', $config), 'items' =>  get_all_status_values($config), 'render_type' => 'select');
		$fields['completion_status'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Games_Edit_COMPLETION', $config), 'items' => get_all_completion_status_values($config), 'render_type' => 'select');
		
		$__pd['fields'] = $fields;
		
		$result = check_form_submit($tid, $gid, $fields, $oR3DCQuery);

		if($result['submit'] == FALSE) 	// No submission.
		{
			$__pd['game'] = $game;
			$__pd['game']['fen'] = $oR3DCQuery->GetInitialGameFEN($config, $gid);
		}
		elseif($result['submit'] && count($result['errors']) != 0)	// Was submitted, but has errors.
		{
			$__pd['game'] = $result['new_values'];
			$__pd['errors'] = $result['errors'];
		}
		else		// Was submitted with no errors.
		{
			$__pd['game'] = $result['new_values'];
			$__pd['game']['fen'] = $oR3DCQuery->GetInitialGameFEN($config, $gid);
			$__pd['success'] = _T('IDS_Admin_Tournament_Games_Edit_UPDATED', $config);
		}
	}
	
	// Checks if a form submit occurred. This would be an update request.
	// Must validate all values before saving. If there are errors, they are
	// returned, along with all the new values entered.
	// tid - the id of the tournament record that the game will be associated with.
	// gid - the id of the game record (when editing a game).
	// fields - array of field options.
	// main - the oR3DCQuery object. Needed to access the db link.
	function check_form_submit($tid, $gid, $fields, $main)
	{
		if(!isset($_POST['update']))
		{
			return array('submit' => FALSE);
		}
		
		$new_values = array();
		$errors = array();
		
		foreach($fields as $field => $opts)
		{
			$val = "";
			if(isset($_POST[$field]))
				$val = trim($_POST[$field]);
			
			if($opts['type'] == 'date')
			{
				$val = $val . " 00:00:00";	// Don't care about time component.
				//$date = DateTime::createFromFormat("M-d-Y H:i:s", $val);
                //if($date) $val = $date->format("Y-m-d H:i:s");
			}
			
			$new_values[$field] = $val;
			
			if($opts['required'] && $val == "")
			{
				$errors[$field] = _T('IDS_Form_Message_REQUIRED', $main->ChessCFGFileLocation);
				continue;
			}
			
			// Got a value. Must validate it.
			$res = validate_field($val, $opts, $main->ChessCFGFileLocation);
			if($res['error'])
			{
				$errors[$field] = $res['msg'];
				continue;
			}
		}
		
		//var_dump($errors);
		//var_dump($new_values);
		// If there were no errors, can update the object. 
		if(count($errors) == 0)
		{
			// Update record in game table.
			$query = sprintf("UPDATE game SET status = '%s', completion_status = '%s' WHERE game_id = '%s'",
				mysqli_real_escape_string($main->link,$new_values['status']),
				mysqli_real_escape_string($main->link,$new_values['completion_status']),
				mysqli_real_escape_string($main->link,$gid)
			);
			$str = _T('IDS_Admin_Tournament_Games_Edit_ERRORUPDATING', $main->ChessCFGFileLocation);
			mysqli_query($main->link,$query) or die(preg_replace("/\{mysql_err\}/", mysqli_error($main->link, $str));
		}
		
		return array('submit' => TRUE, 'errors' => $errors, 'new_values' => $new_values);
		
	}
	
	function validate_field($val, $opts, $config)
	{
		$error = FALSE;
		if($opts['type'] == 'int')
		{
			if(preg_match('/^[!0-9]+$/', $val) != 0)
			{
				//$error = "Value is not an integer";
			}
		}
		elseif($opts['type'] == 'string')
		{
			if(isset($opts['max_length']))
			{
				if(strlen($val) > $opts['max_length'])
				{
					$error = _T('IDS_Form_Message_LENGTH', $config);
					$error = preg_replace("/\{max_length\}/", $opts['max_length'], $error);
				}
			}
		}
		
		if($error !== FALSE)
		{
			return array('error' => TRUE, 'msg' => $error);
		}
		return array('error' => FALSE);
	}
	
	
	function get_active_player_list($main)
	{
		// Get disabled players and then all players. Any player that is disabled will be ignored.
		// Is there a way to do this using one query?
		$query = "SELECT player_id FROM player2";
		$result = mysqli_query($main->link,$query) or die(_T('IDS_Admin_Tournament_Games_Edit_ERRORDISABLED', $main->ChessCFGFileLocation));
		$disabled = array();
		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			$disabled[] = $row['player_id'];
		}
		$query = "SELECT player_id, userid FROM player ORDER BY userid Asc";
		$result = mysqli_query($main->link,$query) or die(_T('IDS_Admin_Tournament_Games_Edit_ERRORUSERS', $main->ChessCFGFileLocation));
		
		$players = array();
		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
		{
			if(!in_array($row['player_id'], $disabled))
			{
				$players[$row['player_id']] = $row['userid'];
			}
		}
		return $players;
	}
	
	function get_all_status_values($config)
	{
		return array('A' => _T('IDS_Game_Status_ACTIVE', $config), 'C' => _T('IDS_Game_Status_COMPLETED', $config), 'W' => _T('IDS_Game_Status_WAITING', $config), 'P' => _T('IDS_Game_Status_PENDING', $config));
	}
	
	function get_all_completion_status_values($config)
	{
		return array('B' => _T('IDS_GAME_CompletionStatus_BLACK', $config), 'W' => _T('IDS_GAME_CompletionStatus_WHITE', $config), 'D' => _T('IDS_GAME_CompletionStatus_DRAW', $config), 'I' => _T('IDS_GAME_CompletionStatus_INCOMPLETE', $config));
	}
	
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