<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited.
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // You can find us at http://www.phpchess.com. 
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
  $Contentpage = "cell_admin_game_list.php";  

  require($Root_Path."bin/CSkins.php");
  require($Root_Path."bin/LanguageParser.php");
  
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
  require($Root_Path."bin/DataRenderers.php");
  require($Root_Path."CSession.php");
  require($Root_Path."CChessBoard.php");
  require($Root_Path."CChessBoardUtilities.php");
  require($Root_Path."CChess2.php");

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oAdmin = new CAdmin($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  LanguageFile::load_language_file2($conf);
  
  ////////////////////////////////////////////////
  //Login Processing
  ////////////////////////////////////////////////
  //Check if admin is logged in already
  if(!isset($_SESSION['LOGIN'])){
     $login = "no";
     header('Location: ./index.php');
    
  }else{

    if($_SESSION['LOGIN'] != true){

      if (isset($_SESSION['UNAME'])){
        unset($_SESSION['UNAME']);
      }

      if (isset($_SESSION['LOGIN'])) { 
        unset($_SESSION['LOGIN']);
      }

      $login = "no";
      header('Location: ./index.php');

    }else{
      $login = "yes";
    }

  }
  ////////////////////////////////////////////////

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }
  
	DB::init($config);
	$db = DB::query_getone('select database() as `db`');
	$db = $db['db'];
	
	$players = ModelManager::get_all("$db::player");
	$player_list = array();
	foreach($players as $player)
		$player_list[$player->player_id] = $player->userid;
	
	$tbl_opts = array(
		'url' => 'admin_game_list.php',
		'model' => "$db::game",
		'title' => __l('Manage Games'),
		'order' => array('game_id', 'initiator', 'w_player_id', 'b_player_id', 'status', 'completion_status', 'start_time', 'board'),
		'columns' => array(
			'game_id' => array('label' => __l('Game ID')),
			'initiator' => array('label' => __l('Initiator')),
			'start_time' => array('render_as' => 'date_time', 'width' => 150, 'label' => __l('Start Time')),
			'status' => array(
				'label' => __l('Status'),
				'values' => array(
					'A' => __l('Active'), 
					'C' => __l('Completed'), 
					'W' => __l('Waiting to be accepted'), 
					'P' => __l('Pending'), 
					'I' => 'I', 
					'T' => 'T'
				),
				'required' => TRUE
			),
			'completion_status' => array(
				'label' => __l('Completion<br/>Status'),
				'values' => array(
					'W' => __l('White Won'),
					'B' => __l('Black Won'),
					'D' => __l('Draw'),
					'A' => 'A',
					'I' => __l('Incomplete')
				)
			),
			'w_player_id' => array('label' => __l('White'), 'values' => $player_list, 'render_as' => 'select'),
			'b_player_id' => array('label' => __l('Black'), 'values' => $player_list, 'render_as' => 'select'),
			'initiator' => array('values' => $player_list, 'render_as' => 'select', 'required' => TRUE),
			'board' => array('label' => __l('View Board'), 'render_as' => 'preview_board')
		),
		// //'controller_order' => array('delete', 'create', 'update', 'view'),
		'controllers' => array(
			'create' => array('label' => __l('Add')),
			'update' => array('label' => __l('Edit'))
		),
		'form_options' => array(
			'default' => array(
				'order' => array('game_id', 'initiator', 'w_player_id', 'b_player_id', 'status', 'completion_status', 'start_time', 'next_move', 'cast_ws', 'cast_wl', 'cast_bs', 'cast_bl', 'draw_requests', 'board'),
				'fields' => array(
					'next_move' => array('label' => __l('Next Move'), 'values' => array('w' => __l('White'), 'b' => __l('Black'))),
					'cast_ws' => array('label' => __l('Castle White Short'), 'render_as' => 'tick'),
					'cast_wl' => array('label' => __l('Castle White Long'), 'render_as' => 'tick'),
					'cast_bs' => array('label' => __l('Castle Black Short'), 'render_as' => 'tick'),
					'cast_bl' => array('label' => __l('Castle Black Long'), 'render_as' => 'tick'),
					'board' => array('label' => __l('View Board'), 'render_as' => 'edit_game',
						'set' => array(
							'location' => '../modules/RealTimeInterface/img_chess/', 
							'white_files' => array('wkw.gif', 'wqw.gif', 'wbw.gif', 'wnw.gif', 'wrw.gif', 'wpw.gif'),
							'black_files' => array('bkw.gif', 'bqw.gif', 'bbw.gif', 'bnw.gif', 'brw.gif', 'bpw.gif')
						)
					)
				)
			),
			'update' => array(
				'fields' => array(
					'game_id' => array('render_as' => 'static'),
					'w_player_id' => array('render_as' => 'static'),
					'b_player_id' => array('render_as' => 'static'),
					'initiator' => array('render_as' => 'static')
				)
			),
			'create' => array(
				'order' => array('w_player_id', 'b_player_id', 'rated', 'game_time', 'fen', 'tc'),
				'fields' => array(
					'fen' => array('render_as' => 'board2fen', 'label' => 'FEN'),
					'rated' => array('label' => __l('Rate Game?'), 'values' => array('1' => __l('Yes'), '0' => __l('No')), 'value' => '1', 'render_as' => 'radio', 'required' => TRUE),
					'game_time' => array(
						'label' => 'Game Time', 'render_as' => 'select', 
						'values' => array(
							'C-Blitz' => GetStringFromStringTable("IDS_CREATE_GAME_OPT_1", $config),
							'C-Short' => GetStringFromStringTable("IDS_CREATE_GAME_OPT_2", $config),
							'C-Normal' => GetStringFromStringTable("IDS_CREATE_GAME_OPT_3", $config),
							'C-Slow' => GetStringFromStringTable("IDS_CREATE_GAME_OPT_4", $config),
							'C-Snail' => GetStringFromStringTable("IDS_CREATE_GAME_OPT_5", $config)
						)
					),
					'tc' => array('label' => 'Time Controls', 'render_as' => 'time_controls', 'value' => '')
					// ratingtype: grated => Rated, gunrated => Unrated
					// gametime: RT-Blitz => Blitz, RT-Short => Short, RT-Normal => Normal, RT-Slow => Slow
				),
				'controllers' => array(
					'create' => array(
						'callback' => create_game,
						'save' => FALSE
					)
				)
			),
			'delete' => array(
				'controllers' => array(
					'delete' => array(
						'callback' => delete_game,
						'delete' => FALSE
					)
				)
			)
		),
		'usepager' => TRUE,
		'useRp' => TRUE,
		'rp' => 15,
		'action_callback' => 'admin_game_list.php',
		'height' => 500,
		'findtext' => __l('Find'),
		'pagestat' => __l('Displaying {from} to {to} of {total} items'),
		'pagetext' => __l('Page'),
		'outof' => __l('of'),
		'findtext' => __l('Find'),
		'procmsg' => __l('Processing, please wait ...'),
		'nomsg' => __l('No items'),
		'errormsg' => __l('Connection Error')
	);
	$table = new Table();
	//$table->model_value_processors['client']['phpchess::player'] = test;
	if($_POST['page'] || $_POST['tbl_action'] || $_POST['form_action'])
	{
		header("Content-type: application/json");
		if(isset($_POST['page']))
		{
			$result = $table->handle_table_action('data', $tbl_opts);
		}
		elseif(isset($_POST['tbl_action']))
		{
			CSession::initialise($config);
			$table->model_value_processors['client']["$db::game"] = process_game_values;
			$result = $table->handle_table_action($_POST['tbl_action'], $tbl_opts);
			
		}
		echo json_encode($result);
		exit();
	}
	else
	{
		$result = $table->initialise($tbl_opts);
		if(!$result['success'])
		{
			$table_init_options = FALSE;
		}
		else
		{
			
				$table_init_options = json_encode($result['table_init_options']);
		}
	}
	
	// Board preview in the table requests the fen for a given game.
	if(isset($_POST['_request_game_fen']))
	{
		header("Content-type: application/json");
		CSession::initialise($config);
		ChessHelper::load_chess_game($_POST['_request_game_fen']);
		$fen = ChessHelper::$CB->GetFENForCurrentPosition();
		echo json_encode(array('fen' => $fen));
		exit();
	}
	
	function process_game_values($data)
	{
		$game_id = $data['instance']['game_id'];
		$data['instance']['board'] = $game_id;
		ChessHelper::load_chess_game($game_id);
		$ML = ChessHelper::$CB->GetMoveList();
		$moves_san = array();
		foreach($ML as $move)
		{
			$moves_san[] = $move->szSAN;
		}
		//echo '<pre>';var_dump($moves_san);echo '</pre>';
		$fen = ChessHelper::get_custom_fen($game_id);
		$data['instance']['board'] = array('moves_san' => $moves_san, 'fen' => $fen);
		//exit();
	}
	
	
	function create_game()
	{
		$settings = $_POST['instance'];
		//$fen = $settings['board'];
		$fen = "";
		$wplayer = (int)$settings['w_player_id'];
		$bplayer = (int)$settings['b_player_id'];
		$fen = $settings['fen'];
		$rated = $settings['rated'] ? 'grated' : 'gunrated';
		$game_time = $settings['game_time'];
		if(!in_array($game_time, array('C-Blitz', 'C-Short', 'C-Normal', 'C-Slow', 'C-Snail')))
			return array('success' => FALSE, 'error' => 'validation_failed', 'validation_errors' => array('game_time' => array('type' => 'custom', 'error_msg' => 'Game time is invalid')));
		$move1 = (int)$settings['tc']['move1'];
		$move2 = (int)$settings['tc']['move2'];
		$time1 = (int)$settings['tc']['time1'];
		$time2 = (int)$settings['tc']['time2'];
		if($move1 == 0) $move1 = NULL;
		if($move2 == 0) $move2 = NULL;
		if($time1 == 0) $time1 = NULL;
		if($time2 == 0) $time2 = NULL;

		if($wplayer == $bplayer)
			return array('success' => FALSE, 'error' => 'validation_failed', 'validation_errors' => array('w_player_id' => array('type' => 'custom', 'error_msg' => __l('Same person cannot play both sides'))));
		
		$player_id = $wplayer != 0 ? $wplayer : $bplayer;			// player id of 0 = ANYONE
		$other_player_id = $wplayer != 0 ? $bplayer : $wplayer;
		$my_color = $wplayer == $player_id ? 'w' : 'b';
		
		//echo "will pass: $player_id, $other_player_id, $my_color, $fen, $rated";
		$oR3DCQuery = new CR3DCQuery('../bin/config.php');
		$result = $oR3DCQuery->CreateGame(NULL, NULL, $player_id, $other_player_id, $my_color, $fen, $move1, $time1, $move2, $time2, TRUE, FALSE, TRUE, $rated, $game_time);
		//$oR3DCQuery->createGame(NULL, NULL, $player_id, $other_player_id, $my_color, $fen, $move1, $time1, $move2, $time2, $bRTGame, $precreate, $brealtimeposs, $Rating, $GameTime){
		if($result)
			return array('success' => TRUE);
		else
			return array('success' => FALSE, 'error' => __l('Unable to create game'));
	}
	
	function delete_game()
	{
		if(!isset($_POST['instance']['game_id']))
			return array('success' => FALSE, 'error' => __l('Cannot delete game. No game id provided.'));
		$gid = $_POST['instance']['game_id'];
		$errormsg = "";
		$cfgfile = '../bin/config.php';
		// Games table.
		if(!DB::delete("DELETE FROM `game` WHERE `game_id` = ?", array($gid)))
			$errormsg .= __l("Query to delete c4m_tournamentgames from 'game' table failed.");
		// Tournament games table.
		if(!DB::delete("DELETE FROM `c4m_tournamentgames` WHERE `tg_gameid` = ?", array($gid)))
			$errormsg .= __l("Query to delete game reference from 'c4m_tournamentgames' table failed.");
		// Timing mode for games table.
		if(!DB::delete("DELETE FROM `cfm_game_options` WHERE `o_gameid` = ?", array($gid)))
			$errormsg .=__l("Query to delete game reference from 'cfm_game_options' table failed. ");
		// Move history table.
		if(!DB::delete("DELETE FROM `move_history` WHERE `game_id` = ?", array($gid)))
			$errormsg .= __l("Query to delete game references from 'moves_history' table failed.");
		// Realtime games table.
		if(!DB::delete("DELETE FROM `cfm_gamesrealtime` WHERE `id` = ?", array($gid)))
			$errormsg .= __l("Query to delete game reference from 'cfm_gamesrealtime' table failed.");
		// Games chat table.
		if(!DB::delete("DELETE FROM `c4m_gamechat` WHERE `tgc_gameid` = ?", array($gid)))
			$errormsg .= __l("Query to delete game reference from 'c4m_gamechat' table failed.");
		// Games draw table.
		if(!DB::delete("DELETE FROM `c4m_gamedraws` WHERE `tm_gameid` = ?", array($gid)))
			$errormsg .= __l("Query to delete game reference from 'c4m_gamedraws' table failed.");
		// Custom FENs table.
		if(!DB::delete("DELETE FROM `c4m_newgameotherfen` WHERE `gameid` = ?", array($gid)))
			$errormsg .= __l("Query to delete game reference from 'c4m_newgameotherfen' table failed.");
		
		if($errormsg)
			return array('success' => FALSE, 'error' => __l('Unable to delete game or related data.'), 'error_msg' => $errormsg);
		return array('success' => TRUE);
	}
	
?>

<html>
<head>
<title><?php echo __l('Administration Page - All Games');?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<link rel="stylesheet" href="../includes/flexigrid/css/flexigrid.pack.css" type="text/css">
<link rel="stylesheet" href="../includes/jquery/cupertino/jquery-ui-1.8.16.custom.css" type="text/css">
<script type="text/javascript" src="../includes/jquery/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="../includes/jquery/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="../includes/flexigrid/js/flexigrid.js"></script>
<script type="text/javascript" src="../includes/data render/controls.js"></script>
<script type="text/javascript" src="../includes/data render/table.js"></script>
<script type="text/javascript" src="../includes/data render/form.js"></script>
<script type="text/javascript" src="../includes/data render/common.js"></script>
<script type="text/javascript" src="../includes/jhlywa_chess/chess.js"></script>
<script type="text/javascript" src="../includes/js_chess/chessboard.js"></script>
<script type="text/javascript" src="../includes/js_chess/common.js"></script>
<?php include($Root_Path."includes/javascript_admin.php");?>
</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oAdmin->Close();
  unset($oR3DCQuery);
  unset($oAdmin);
  //////////////////////////////////////////////////////////////
?>