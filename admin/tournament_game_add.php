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
	$Contentpage = "cell_admin_tournament_game_add.php";  

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
	require($Root_Path."bin/LanguageParser.php");

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
	
	LanguageFile::load_language_file2($conf);
	
	// This page provides a form for creating new games.
	
	// This variable will hold data to display on the page.
	$__pd = array();
	
	// A tournament id must be provided to be able to do anything.
	if(!isset($_GET['id']))
	{
		$__pd['error'] = _T('IDS_Admin_Tournament_Games_Add_IDMISSING', $config);
	}
	else
	{
		$id = (int)$_GET['id'];
		$query = sprintf("SELECT t_id, t_name, t_comment, t_startdate FROM c4m_tournament WHERE t_id = '%s'", mysql_real_escape_string($id));
		$result = mysql_query($query, $oR3DCQuery->link);
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Games_Add_QUERYFAILED', $config));
		}
		$cnt = mysql_num_rows($result);
		if($cnt == 0)
		{
			$__pd['error'] = _T('IDS_Admin_Tournament_Games_Add_IDINVALID', $config);
		}
		else
		{
			$t = mysql_fetch_array($result, MYSQL_ASSOC);
			$__pd['tournament'] = $t;
		}
		
	}
	
	if(!isset($__pd['error']))
	{
		// Field settings. Used for validation purposes and setting-up/customising the form.
		$players = get_active_player_list($oR3DCQuery);
		$fields = array();
		$fields['w_player_id'] = array('type' => 'int', 'label' => _T('IDS_Admin_Tournament_Games_Add_WHITE', $config), 'required' => TRUE, 'width' => 40, 'items' => $players, 'render_type' => 'select');
		$fields['b_player_id'] = array('type' => 'int', 'label' => _T('IDS_Admin_Tournament_Games_Add_BLACK', $config), 'required' => TRUE, 'width' => 40, 'items' => $players, 'render_type' => 'select');
		$fields['fen'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Games_Add_FEN', $config), 'required' => TRUE, 'width' => 70,'value' => 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1', 'id' => 'FEN');
		$fields['timing_mode'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Games_Add_TIMING', $config), 'required' => TRUE, 'value' => 'C-Normal', 'items' => array('C-Snail' => _T('IDS_CREATE_GAME_OPT_5', $config), 'C-Slow' => _T('IDS_CREATE_GAME_OPT_4', $config), 'C-Normal' => _T('IDS_CREATE_GAME_OPT_3', $config), 'C-Short' => _T('IDS_CREATE_GAME_OPT_2', $config), 'C-Blitz' => _T('IDS_CREATE_GAME_OPT_1', $config)), 'render_type' => 'select');
		$fields['time_controls'] = array('type' => 'time controls', 'label' => __l('Time Controls'), 'id' => 'tc', 'render_type' => 'time controls', 'width' => 4, 'txt_moves' => __l('moves adds'), 'txt_min' => __l('minutes'));
		
		$__pd['fields'] = $fields;
		
		$result = check_form_submit($id, NULL, $fields, $oR3DCQuery);

		if($result['submit'] == FALSE) 	// No submission.
		{
			$__pd['game'] = array();
		
		}
		elseif($result['submit'] && count($result['errors']) != 0)	// Was submitted, but has errors.
		{
			$__pd['game'] = $result['new_values'];
			$__pd['errors'] = $result['errors'];
		}
		else		// Was submitted with no errors.
		{
			$__pd['game'] = array();
			$str = _T('IDS_Admin_Tournament_Games_Add_CREATED', $config);
			$__pd['success'] = preg_replace('/\{id\}/', $result['game_id'], $str);
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
		if(!isset($_POST['create']))
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
			
			if($opts['type'] == 'time controls')
			{
				$m1 = $_POST['tc_m1'];
				$m2 = $_POST['tc_m2'];
				$t1 = $_POST['tc_t1'];
				$t2 = $_POST['tc_t2'];
				$val = array('m1' => $m1, 't1' => $t1, 'm2' => $m2, 't2' => $t2);
				$new_values[$field] = $val;
				if($m1 !== '')
				{
					if($t1 === '')
					{
						$errors[$field] = _T('IDS_Form_Message_REQUIRED', $main->ChessCFGFileLocation);
						continue;
					}
					if($m2 !== '' && $t2 === '')
					{
						$errors[$field] = _T('IDS_Form_Message_REQUIRED', $main->ChessCFGFileLocation);
						continue;
					}
				}
				//var_dump($val);
			}
			else
			{
				$new_values[$field] = $val;
			}
			
			if($opts['required'] && $val == "")
			{
				$errors[$field] = _T('IDS_Form_Message_REQUIRED', $main->ChessCFGFileLocation);
				continue;
			}
			
			// Got a value. Must validate it.
			$res = validate_field($val, $opts, $main->link);
			if($res['error'])
			{
				$errors[$field] = $res['msg'];
				continue;
			}
		}
		// Custom stuff - black and white players cannot be the same.
		if($new_values['w_player_id'] == $new_values['b_player_id'])
		{
			$errors['black'] = _T('IDS_Admin_Tournament_Games_Add_SAMEPLAYER', $main->ChessCFGFileLocation);
		}
		// var_dump($errors);
		// var_dump($new_values);
		// If there were no errors, can update the object. 
		$game_id = '';
		// exit();
		if(count($errors) == 0)
		{
			// Create a record in the game table. Need to work out whose turn it is and castling status from the FEN.
			$game_id = $main->gen_unique();
			$parts = preg_split('/ /', $new_values['fen']);
			//var_dump($parts);
			$next_move = $parts[1];
			$cast_ws = strstr($parts[2], 'K') ? 1 : 0;
			$cast_wl = strstr($parts[2], 'Q') ? 1 : 0;
			$cast_bs = strstr($parts[2], 'k') ? 1 : 0;
			$cast_bl = strstr($parts[2], 'q') ? 1 : 0;
			
			$query = sprintf("INSERT INTO game (game_id, initiator, w_player_id, b_player_id, status, completion_status, start_time, next_move, cast_ws, cast_wl, cast_bs, cast_bl) VALUES('%s', '0', '%s', '%s', 'A', 'I', '%s', '%s', '%s', '%s', '%s', '%s')",
				mysql_real_escape_string($game_id),
				mysql_real_escape_string($new_values['w_player_id']),
				mysql_real_escape_string($new_values['b_player_id']),
				mysql_real_escape_string(time()),
				mysql_real_escape_string($next_move),
				mysql_real_escape_string($cast_ws),
				mysql_real_escape_string($cast_wl),
				mysql_real_escape_string($cast_bs),
				mysql_real_escape_string($cast_bl)
			);
			mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERRORINSERTGAME', $main->ChessCFGFileLocation) . '<br>' . mysql_error());
			
			// When using a custom FEN need to store it here.
			if($new_values['fen'] != 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1')
			{
				$query = $query = sprintf("INSERT INTO c4m_newgameotherfen VALUES('%s', '%s')",
					mysql_real_escape_string($game_id),
					mysql_real_escape_string($new_values['fen'])
				);
				mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERRORFEN', $main->ChessCFGFileLocation) . '<br>' . mysql_error());
			}
			
			// Store the game timing mode (snail, slow, normal, fast or blitz)
			$query = sprintf("INSERT INTO cfm_game_options VALUES('%s', 'grated', '%s', 1)",
				mysql_real_escape_string($game_id),
				mysql_real_escape_string($new_values['timing_mode'])
			);
			mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERROROPTIONS', $main->ChessCFGFileLocation) . '<br>' . mysql_error());
			
			// Store the time controls if they have been set.
			$tc = $new_values['time_controls'];
			if($tc['m1'] !== '')
			{
				$query = sprintf("INSERT INTO timed_games VALUES('%s', %s, %s, %s, %s)",
					mysql_real_escape_string($game_id),
					mysql_real_escape_string((int)$tc['m1']),
					mysql_real_escape_string((int)$tc['t1']),
					mysql_real_escape_string((int)$tc['m2']),
					mysql_real_escape_string((int)$tc['t2'])
				);
				mysql_query($query, $main->link) or die(__l('Error inserting record into timed_games table') . '<br>' . mysql_error());
			}
			
			// Associates the game with the tournament.
			$query = sprintf("INSERT INTO c4m_tournamentgames VALUES(NULL, '%s', '%s', '%s', '%s', '', '', '' )",
				mysql_real_escape_string($tid),
				mysql_real_escape_string($game_id),
				mysql_real_escape_string($new_values['w_player_id']),
				mysql_real_escape_string($new_values['b_player_id'])
			);
			mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERRORINSERTASSOC', $main->ChessCFGFileLocation) . '<br>' . mysql_error());
		}
		return array('submit' => TRUE, 'errors' => $errors, 'new_values' => $new_values, 'game_id' => $game_id);
		
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
		elseif($opts['type'] == 'time controls')
		{
			foreach($val as $v)
			{
				if($v !== '')
				{
					if(!is_numeric($v))
					{
						$error = __l('Value must be a number.');
						break;
					}
					$v = (int)$v;
					if( $v <= 0)
					{
						$error = __l('Value must be a number larger than 0.');
						break;
					}
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
		$result = mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERRORDISABLED', $main->ChessCFGFileLocation));
		$disabled = array();
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			$disabled[] = $row['player_id'];
		}
		$query = "SELECT player_id, userid FROM player ORDER BY userid Asc";
		$result = mysql_query($query, $main->link) or die(_T('IDS_Admin_Tournament_Games_Add_ERRORUSERS', $main->ChessCFGFileLocation));
		
		$players = array();
		while($row = mysql_fetch_array($result, MYSQL_ASSOC))
		{
			if(!in_array($row['player_id'], $disabled))
			{
				$players[$row['player_id']] = '(' . $row['player_id'] . ') ' . $row['userid'];
			}
		}
		return $players;
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