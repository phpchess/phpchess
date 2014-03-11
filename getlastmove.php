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

  header("Content-Type: text/xml");
  header('Cache-Control: no-cache');
  header('Pragma: no-cache');

  echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
  echo "<SERVER>\n";

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";

  // Get variables
  $action = $_GET['action'];
  $xsid = $_GET['sid'];
  $game_id =  $_GET['gameid'];
  $playerid =  $_GET['playerid'];
  $gid = $game_id;

  // Include the requried classes
  $config = $Root_Path."bin/config.php";
  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CBilling.php");
  require($Root_Path."bin/CServMsg.php");
  require($Root_Path."bin/CFrontNews.php");
  require($Root_Path."bin/CBuddyList.php");
  require($Root_Path."bin/CAdmin.php");
  include ($Root_Path."includes/support_chess.inc"); 
  include ($Root_Path."includes/chess.inc"); 

  /**********************************************************************
  * isSessionIDValid
  *
  */
  function isSessionIDValid($config, $xsid){

    $bValid = false;

    //Instantiate the CR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $oR3DCQuery->CheckSIDTimeout();

    if($xsid != ""){

      if($oR3DCQuery->CheckLogin($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($uniq != "" && is_numeric($player_id)){

          $bValid = true;

          $oR3DCQuery->UpdateSIDTimeout($config, $xsid);
          $oR3DCQuery->SetPlayerCreditsInit($player_id);

        }

      }

    }

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }

    $oR3DCQuery->MangeGameTimeOuts();
    $oR3DCQuery->Close();
    unset($oR3DCQuery);

    return $bValid;

  }
  
if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $oR3DCQuery->GetNewMoveForMobile($_GET['gameid']);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
          $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);

          echo "<RESPONSE>\n";
          echo "<DRAWCODE>".$isdraw."</DRAWCODE>\n";
          echo "</RESPONSE>\n";
		  
		  
		  $initiator = "";
		  $w_player_id = "";
		  $b_player_id = "";
		  $status = "";
		  $completion_status = "";
		  $start_time = "";
		  $next_move = "";

		  $oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

		          
		  echo "<RESPONSE>\n";
          echo "<STATUS>".$completion_status."</STATUS>\n";
          echo "</RESPONSE>\n";
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{


          echo "<RESPONSE>\n";
          echo "<DRAWCODE></DRAWCODE>\n";
          echo "</RESPONSE>\n";
          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_GAMEID</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }
	  	  
  echo "</SERVER>\n";
?>