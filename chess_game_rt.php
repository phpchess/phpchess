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

  $host = $_SERVER['HTTP_HOST'];
  $self = $_SERVER['PHP_SELF'];
  $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
  $url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

  header("Content-Type: text/html; charset=utf-8");
  session_start();
  ob_start();

  $gid = trim($_GET['gameid']);  
  
  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_game_rt.php";  

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////
  
  include ($Root_Path."includes/support_chess.inc"); 
  include ($Root_Path."includes/chess.inc"); 
  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");
  require($Root_Path."bin/LanguageParser.php");

	include($config);	// To access config options in this file.


  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  ///////////////////////////////////////////////////////////////////
  // Check For the nonempty SID var
  $sid = trim($_GET['sid']);

  // Log the user on or manage the session if a SID is passed to the page
  if($sid != ""){

    $user = "";
    $id = "";
    $oR3DCQuery->ConfirmSID($sid, $user, $id);
 
    if($user != "" && $id != ""){
      $_SESSION['sid'] = $sid;
      $_SESSION['user'] = $user;
      $_SESSION['id'] = $id;

      $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
      
      $_SESSION['lcolor'] = $l;
      $_SESSION['dcolor'] = $d;

      if($oR3DCQuery->IsPlayerDisabled($id) == false){

        $clrl = $_SESSION['lcolor'];
        $clrd = $_SESSION['dcolor'];

        $oR3DCQuery->AddOnlinePlayerToGraphData($_SESSION['user']);
        $oR3DCQuery->UpdateLastLoginInfo($_SESSION['id']);
        $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);

        //Check if the game is accepted
        $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
        $gametypecode = $oR3DCQuery->GetGameTypeCode($gid);
        list($PlayerType, $status) = preg_split("/\s/ ", $IsAccepted, 2);

        if($status == "waiting" || $status == "-"){
          header("Location: ./chess_game.php?gameid=".$gid."");
        }

      }else{
        header('Location: ./chess_logout.php');
      }

    }

  }
  ///////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////
  //Check if the logged in user has access
  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){
    $_SESSION['PageRef'] = $url;
    header('Location: ./chess_login.php');
  }else{

    $oR3DCQuery->CheckSIDTimeout();

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      $_SESSION['PageRef'] = $url;
      header('Location: ./chess_login.php');
    }else{
      $_SESSION['PageRef'] = "";
      $oR3DCQuery->UpdateSIDTimeout($config, $_SESSION['sid']);
      $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);
    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////////

  ///////////////////////////////////////////////////////////////////////
  // Handle game status and type redirections
  ///////////////////////////////////////////////////////////////////////
        
  $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
  $gametypecode = $oR3DCQuery->GetGameTypeCode($gid);
  list($PlayerType, $status) = preg_split("/\s/", $IsAccepted, 2);

  if($status == "waiting" || $status == "-"){
    header("Location: ./chess_game.php?gameid=".$gid."");
  }

  ///////////////////////////////////////////////////////////////////////
	$initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";

  ///////////////////////////////////////////////////////////////////
  // Check if the game is not playable by the viewer
  if(!$oR3DCQuery->IsGameControlsViewableByPlayer($gid, $_SESSION['id'])){
    header('Location: ./chess_members.php');
  }

  //get current game info to display player data
  $oR3DCQuery->GetCurrentGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

  //get the information for the white player and put it in a div
  $image = $oR3DCQuery->GetAvatarImageName($w_player_id);

  if($image != ""){
	$image="<img src='./avatars/".$image."'>";
  }else{
	$image="<img src='./avatars/noimage.jpg'>";
  }
  
  $userid = $oR3DCQuery->GetUserIDByPlayerID($config, $w_player_id);
  
  $wins = 0;
  $loss = 0;
  $draws = 0;

  $oR3DCQuery->GetPlayerStatusrRefByPlayerID($config, $w_player_id, $wins, $loss, $draws);

  if($oR3DCQuery->ELOIsActive()){
	$points = $oR3DCQuery->ELOGetRating($w_player_id);
  }else{
	$points = $oR3DCQuery->GetPointValue($wins, $loss, $draws);
  }
  
  $whitediv="
	<div id=\"whiteclock\"></div>
	<div class=\"avatar\">$image</div>
	<div class=\"userid\">$userid</div>
	<div class=\"points\">$points</div>
	";
  
  //get the information for the white player and put it in a div
  $image = $oR3DCQuery->GetAvatarImageName($b_player_id);

  if($image != ""){
	$image="<img src='./avatars/".$image."'>";
  }else{
	$image="<img src='./avatars/noimage.jpg'>";
  }
  
  $userid = $oR3DCQuery->GetUserIDByPlayerID($config, $b_player_id);
  
  $wins = 0;
  $loss = 0;
  $draws = 0;

  $oR3DCQuery->GetPlayerStatusrRefByPlayerID($config, $b_player_id, $wins, $loss, $draws);

  if($oR3DCQuery->ELOIsActive()){
	$points = $oR3DCQuery->ELOGetRating($b_player_id);
  }else{
	$points = $oR3DCQuery->GetPointValue($wins, $loss, $draws);
  }
  
  $blackdiv="
	<div id=\"blackclock\"></div>
	<div class=\"avatar\">$image</div>
	<div class=\"userid\">$userid</div>
	<div class=\"points\">$points</div>
	";
  
  //check the current player's color. set the location of player information accordingly. i.e if the player is black, black player's information is diplayed at the bottom and vice versa
  if($oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'])){
		$player='black';
		$tdiv=$whitediv;
		$bdiv=$blackdiv;
		$imgc='b';
  }else{
		$player='white';
		$bdiv=$whitediv;
		$tdiv=$blackdiv;
		$imgc='w';
  }
  
  //get the current FEN data
  $fen=explode(" ", $oR3DCQuery->GetActualFEN($_SESSION['sid'], $gid));
  //set the turn
  if($fen[1]=='w'){
	$turn='white';
  }else{
	$turn='black';
  }
  
  //explode the board info into rows
  $board=explode("/", $fen[0]);
  
  $initiator = "";
  $w_player_id = "";
  $b_player_id = "";
  $status = "";
  $completion_status = "";
  $start_time = "";
  $next_move = "";

  $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

  if($completion_status=='B' || $completion_status=='W' || $completion_status=='D'){
		$turn="''";
  }
  
  LanguageFile::load_language_file($Root_Path . 'includes/languages/' . preg_replace('/\.txt/', '.php', $_SESSION['language']));
  
?>

<html>
<head>
<title><?php echo __l('Game') . ': ' . substr($gid, 0, 4);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<style>
	.start_tile
	{
		position: absolute;
		border: 2px solid yellow;
	}
	.end_tile
	{
		position: absolute;
		border: 2px solid red;
	}
	.game_finished_msg
	{
		width: 440px;
	}
	.player_turn
	{
		border: 3px solid yellow;
	}
	
</style>

<script type="text/javascript" src="modules/RealTimeInterface/scripts/chess.js" ></script>

<script type="text/javascript">
function PopupPGNGame(webpage){
  var url = webpage;
  var hWnd = window.open(url,"cc10085f0fa093d019eed3e4a1d9cabe","width=610,height=600,resizable=no,scrollbars=yes,status=yes");
  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name="home"; hWnd.location.href=url; }}
}
</script>


<script type="text/javascript" >

var fen;  // stores fen used to setup game.
var LANG;	// Holds language strings.

function start()
{
	var settings = {};

	//set the session id. used to retrieve game info with AJAX requests
	settings['sessionid'] = '<?php echo $_SESSION['sid']?>';
	//game id
	settings['gameid'] = '<?php echo $gid?>';
	// Set the frequency of checks for the last move (in seconds).
	settings['last_move_check_frequency'] = <?php echo $conf['last_move_check_rate']; ?>;

	settings['side'] = <?php echo ($player == 'black' ? 'SIDE.black' : 'SIDE.white'); ?>;
	
	settings['turn'] = <?php echo ($turn == 'black' ? 'SIDE.black' : 'SIDE.white');?>;
	
	settings['fen'] = '<?php echo $oR3DCQuery->GetActualFEN($_SESSION['sid'], $gid); ?>';
	
	settings['container'] = jQuery('#_chess_board_container');
	
	settings['set'] = <?php echo json_encode(array(
		'location' => './modules/RealTimeInterface/img_chess/',
		'white_files' => array('wkw.gif', 'wqw.gif', 'wbw.gif', 'wnw.gif', 'wrw.gif', 'wpw.gif'), 
		'black_files' => array('bkw.gif', 'bqw.gif', 'bbw.gif', 'bnw.gif', 'brw.gif', 'bpw.gif')
	)); ?>;
	settings['set'] = <?php echo json_encode(array(
		'location' => './modules/RealTimeInterface/img_chess/classic/',
		'white_files' => array('WKing.png', 'WQueen.png', 'WBishop.png', 'WKnight.png', 'WRook.png', 'WPawn.png'), 
		'black_files' => array('BKing.png', 'BQueen.png', 'BBishop.png', 'BKnight.png', 'BRook.png', 'BPawn.png')
	)); ?>;
	
	settings['colours'] = <?php echo json_encode(array(
		'highlighted_tile' => '#0D0', 'wtile' => $_SESSION['lcolor'], 'btile' => $_SESSION['dcolor'], 'prv_move' => '#F60', 'stile' => '#0D0'
	)); ?>;
	
	settings['request_timings'] = {chat: <?php echo $conf['chat_refresh_rate']; ?> * 1000, move: <?php echo $conf['last_move_check_rate']; ?> * 1000, game_over: <?php echo $conf['last_move_check_rate']; ?> * 2000};
	
	settings['initial_game_state'] = "<?php
	include($Root_Path."CChess2.php");
	include($Root_Path."CChessBoard.php");
	include($Root_Path."CChessBoardUtilities.php");
	include($Root_Path."CSession.php");
	CSession::initialise($config);
	ChessHelper::load_chess_game($gid);
	$str = "<RESPONSE><PGN>";
	$str .= ChessHelper::get_game_pgn();
	$str .= "</PGN>\n";
	$pieces = ChessHelper::get_captured_pieces();
	$str .= "<CAPTURED_BY_WHITE>" . join(', ', $pieces['white']) . "</CAPTURED_BY_WHITE>\n";
	$str .= "<CAPTURED_BY_BLACK>" . join(', ', $pieces['black']) . "</CAPTURED_BY_BLACK>\n";
	$str .= "<GAME_STATE>";
	$str .= ChessHelper::get_game_state();
	$str .= "</GAME_STATE>\n";
	$str .= "<GAME_RESULT>";
	$str .= ChessHelper::get_game_result();
	$str .= "</GAME_RESULT>\n";
	$str .= "<DRAWCODE>";
	$str .= $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $player == 'black' ? TRUE : FALSE);
	$str .= "</DRAWCODE>\n";
	$timeinfo = ChessHelper::get_timing_info();
	$str .= "<TIME_STARTED>" . $timeinfo['started'] . "</TIME_STARTED>\n";
	$str .= "<TIME_TYPE>" . $timeinfo['type'] . "</TIME_TYPE>\n";
	$str .= '<TIME_MODE>' . $timeinfo['mode'] . '</TIME_MODE>\n';
	if($timeinfo['mode'] == 1)
	{
		$str .= '<TIME_W_LEFT>' . $timeinfo['w_time_left'] . '</TIME_W_LEFT>';
		$str .= '<TIME_B_LEFT>' . $timeinfo['b_time_left'] . '</TIME_B_LEFT>';
		$str .= '<TIME_W_ALLOWED>' . $timeinfo['w_time_allowed'] . '</TIME_W_ALLOWED>';
		$str .= '<TIME_B_ALLOWED>' . $timeinfo['b_time_allowed'] . '</TIME_B_ALLOWED>';
	}
	else
	{
		$str .= "<TIME_DURATION>" . $timeinfo['duration'] . "</TIME_DURATION>\n";
		$str .= '<TIME_W_ALLOWED>' . $timeinfo['duration'] . '</TIME_W_ALLOWED>';
		$str .= '<TIME_B_ALLOWED>' . $timeinfo['duration'] . '</TIME_B_ALLOWED>';
	}
	$str .= "</RESPONSE>\n";
	
	echo preg_replace("/\n/", "", addslashes($str));
	?>";

	settings['last_move'] = <?php
		$lm = ChessHelper::get_last_move();
		$str = $lm['from'] . ' ' . $lm['to'];
		if(trim($str) == '') $str = FALSE;
		echo json_encode($str);
	?>;
	
	settings['chat'] = {input_ctrl: 'sendbox', send_ctrl: 'sendmsgbtn', output_ctrl: 'chatbox'};
	
	// Setup language strings
	LANG = {
		txt_promotion: '<?php echo __l('Select piece to promote pawn to') ?>', 
		txt_check: '<?php echo __l('You are in check!') ?>', 
		txt_mate: '<?php echo __l('You are in check mate') ?>', 
		txt_won: '<?php echo __l('You have won!') ?>', 
		txt_lost: '<?php echo __l('You have lost!') ?>', 
		txt_draw: '<?php echo __l('Game is a draw') ?>', 
		txt_draw_request: '<?php echo __l('%name% has requested a draw. Do you want to accept it?') ?>', 
		txt_request_draw: '<?php echo __l('Are you sure you want to request a draw?') ?>', 
		txt_self_draw: '<?php echo __l('You have requested a draw') ?>', 
		txt_draw_revoke: '<?php echo __l('Revoke Draw') ?>', 
		txt_draw_accept: '<?php echo __l('Accept Draw') ?>', 
		txt_yes: '<?php echo __l('Yes') ?>', 
		txt_no: '<?php echo __l('No') ?>', 
		txt_accept: '<?php echo __l('Accept') ?>', 
		txt_decline: '<?php echo __l('Decline') ?>', 
		txt_self_resign: '<?php echo __l('Are you sure you want to resign?') ?>',
		txt_unable_to_send_move: '<?php echo __l('Unable to send move') ?>',
		txt_unable_to_query_game_state: '<?php echo __l('Unable to query for a new game state update') ?>',
		txt_days_remaining: '<?php echo __l('Days Remaining: {d}') ?>',
		txt_time_remaining1: '<?php echo __l('Time Remaining: {h}:{m}') ?>',
		txt_time_remaining2: '<?php echo __l('Time Remaining: {m}:{s}') ?>',
		txt_game_timed_out: '<?php echo __l('Game has timed out') ?>'
	};
	
	var game = new ChessGame();
	game.init(settings);
}

</script>


<script src="./includes/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
<script src="./includes/greensock/TweenMax.min.js" type="text/javascript"></script>


<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<link rel="stylesheet" href="modules/RealTimeInterface/scripts/realtime.css" type="text/css">

</head>
<body onload="$(document).ready(function(){start();}) //initDivs(); //setup_unity_player()" >

<div id="promptwrapper"></div>
<div id="unity_player"></div>
<!--<div id="prompt">
<div style="float:left"><img onClick="sendUpgrade('Q');" style="cursor:pointer" src="modules/RealTimeInterface/img_chess/<?php echo $imgc?>qw.gif" /></div>
<div style="float:left"><img onClick="sendUpgrade('B');" style="cursor:pointer" src="modules/RealTimeInterface/img_chess/<?php echo $imgc?>bw.gif" /></div>
<div style="float:left"><img onClick="sendUpgrade('N');" style="cursor:pointer" src="modules/RealTimeInterface/img_chess/<?php echo $imgc?>nw.gif" /></div>
<div style="float:left"><img onClick="sendUpgrade('R');" style="cursor:pointer" src="modules/RealTimeInterface/img_chess/<?php echo $imgc?>rw.gif" /></div>
<div style="clear:both"><a href="javascript:cancelUpgrade()"><?php echo __l("IDS_RT_TXT_CANCEL", $config)?></a></div>
</div>-->

<?php include("./skins/".$SkinName."/layout_cfg_no_left_menu.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>