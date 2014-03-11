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

  $Root_Path="./";
  $config = $Root_Path."bin/config.php";

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

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/language.php");

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

  if(isset($_SESSION['sid'])){
    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
    $oR3DCQuery->Close();
    unset($oR3DCQuery);
  }
  
  $GID = $_GET['gid'];
?>

<html>
<head>
<title>RT Chat</title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>

<script language="JavaScript">

// Configure refresh interval (in seconds)
var refreshinterval=<?php echo $conf['chat_refresh_rate'];?>

// Shall the coundown be displayed inside your status bar? Say "yes" or "no" below:
var displaycountdown="no"

// Do not edit the code below
var starttime
var nowtime
var reloadseconds=0
var secondssinceloaded=0

function starttime() {
	starttime=new Date()
	starttime=starttime.getTime()
    countdown()
}

function countdown() {
	nowtime= new Date()
	nowtime=nowtime.getTime()
	secondssinceloaded=(nowtime-starttime)/1000
	reloadseconds=Math.round(refreshinterval-secondssinceloaded)
	if (refreshinterval>=secondssinceloaded) {
        var timer=setTimeout("countdown()",1000)
		if (displaycountdown=="yes") {
			window.status="Page refreshing in "+reloadseconds+ " seconds"
		}
    }
    else {
        clearTimeout(timer)
		window.location.reload(true)
    } 
}
window.onload=starttime



<?php
  ////////////
  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  $isblack = $oR3DCQuery->IsPlayerBlack($config, $GID, $_SESSION['id']);
  $isdraw = $oR3DCQuery->IsRequestDraw($config, $GID, $isblack);

  $initiator = "";
  $w_player_id = "";
  $b_player_id = "";
  $status = "";
  $completion_status = "";
  $start_time = "";
  $next_move = "";

  $oR3DCQuery->GetGameInfoByRef($config, $GID, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       
  $oR3DCQuery->ManageRealTimeGame($config, $GID);
  $isrealtime = $oR3DCQuery->IsRequestRealTime($config, $GID, $isblack);

  if($isrealtime == "IDS_REAL_TIME"){

    //Refresh the game board
    if($_SESSION['RefreashGameOnlyOnce'] == ""){
      echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."';";
      $_SESSION['RefreashGameOnlyOnce'] = "1";
    }

    //////////////////
    // Update player seconds
    $oR3DCQuery->CreatePlayerTimeIfNotEXists($GID, $isblack);

    if($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID) && $oR3DCQuery->IsTimedGameRestricted($GID)){
      $oR3DCQuery->UpdatePlayerTime($GID, $isblack, 10);
    }

    if($completion_status == "I" && $oR3DCQuery->IsTimedGameRestricted($GID)){
      $oR3DCQuery->PlayerTimeConstreached($GID, $w_player_id, $b_player_id);
    }
    /////////////////

    // Check if its your turn to play
    if($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID)){

      $LastMoveDate = $oR3DCQuery->GetLastGameMoveDate($config, $GID);

      if($_SESSION['Refreashed'] != $LastMoveDate){
        echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."';";
        //echo "parent.frames['chessboard'].location.reload();";
        $_SESSION['Refreashed'] = $LastMoveDate;
      }

    }else{
      $_SESSION['Refreashed'] == "";
    }

    // Check if a draw offer was made
    if($isdraw == "IDS_DRAW_REQUESTED"){

      if($_SESSION['DisplayedDraw'] == ""){
        //echo "parent.frames['chessboard'].location.reload();";
        echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."';";
        $_SESSION['DisplayedDraw'] = "Yes";
      }

    }

    // Check if the game has ended
    if ($completion_status != "I"){
      if($_SESSION['DisplayedGameEnded'] == ""){
        $_SESSION['RefreashGameOnlyOnce'] = "";
        //echo "parent.frames['chessboard'].location.reload();";
        echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."&rtend=1';";
        $_SESSION['DisplayedGameEnded'] = "Yes";
      }
    }

    // check if realtime has ended
    if($isrealtime == "IDS_NO_REAL_TIME"){
      if($_SESSION['DisplayedRTEnded'] == ""){
        $_SESSION['RefreashGameOnlyOnce'] = "";
        echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."&rtend=1';";
        $_SESSION['DisplayedRTEnded'] = "Yes";
      }
    }

  }else{

    // check if realtime has ended
    if($isrealtime == "IDS_NO_REAL_TIME"){
      $_SESSION['RefreashGameOnlyOnce'] = "";
      echo "parent.frames['chessboard'].location='./chess_game3.php?gameid=".$GID."&rtend=1';";
      $_SESSION['DisplayedRTEnded'] = "Yes";
    }

  }

  $oR3DCQuery->Close();
  unset($oR3DCQuery);

?>

</script>

<body>

</body>
</html>