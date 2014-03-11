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
  $Contentpage = "cell_game2.php";  

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

  $idc = trim($_GET['idc']); 
  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 
  $movefrom = trim($_GET['txtmovefrom']);
  $moveto = trim($_GET['txtmoveto']);
  $cmdMove = trim($_GET['cmdMove']);
  $cmdAccept = trim($_GET['cmdAccept']);
  $txtChatMessage = trim($_GET['txtChatMessage']);
  $cmdChat = trim($_GET['cmdChat']);
  $bmove_error = false;


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
        list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

        if($status == "waiting" || $status == "-"){
          header("Location: ./chess_game.php?gameid=".$gid."");
        }else{

          //Redirect to the game.
          if($gametypecode == 1){
            Header("Location: ./chess_game1.php?gameid=".$gid);
          }elseif($gametypecode == 3){
            Header("Location: ./chess_game3.php?gameid=".$gid);
          }

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
      $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
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
  list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

  if($status == "waiting" || $status == "-"){
    header("Location: ./chess_game.php?gameid=".$gid."");
  }else{

    //Redirect to the game.
    if($gametypecode == 1){
      Header("Location: ./chess_game1.php?gameid=".$gid);
    }elseif($gametypecode == 3){
      Header("Location: ./chess_game3.php?gameid=".$gid);
    }

  }

  ///////////////////////////////////////////////////////////////////////

  ///////////////////////////////////////////////////////////////////
  // Check if the game is not playable by the viewer
  if(!$oR3DCQuery->IsGameControlsViewableByPlayer($gid, $_SESSION['id'])){
    header('Location: ./chess_members.php');
  }

  // Make move
  if($movefrom != "" && $moveto != "" && $cmdMove !=""){

    //Create Move String
    $movestr = $movefrom.",".$moveto;
    $movestr2 = $movefrom."-".$moveto;

    // get the fen for the game
    $fen = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    //$fen3 = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    $bturn = $oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $gid);

    if($bturn){

      //check to see if the move is valid
      if(is_Move_legal($fen, $movestr2)){ 

        $_SESSION['GAME_MOVE'] = $movestr;

        // if ! promotion screen
	if(!($move_promotion_figur && strlen($movestr2)==5)){

    	  $oR3DCQuery->CurrentGameMovePiece($config, $gid, $_SESSION['sid'], $_SESSION['id'], $movestr);

          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move); 
		
	  //checkmate
          if(get_GameState() == 1){

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
             $bmove_error = false;
              
            }else{

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");
              $bmove_error = false;
            }
         
            $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
            $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
             $bmove_error = false;
              
            }else{

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");
              $bmove_error = false;
            }
         
            $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
            $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }

          //draw
          if(get_GameState() == 2){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");
             $bmove_error = false;

             $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
             $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }
	 
        }

      }else{
        $bmove_error = true;

        if(set_FEN($fen)){		
		  
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

	  //checkmate
          if(get_GameState() == 1){

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
             $bmove_error = false;
              
            }else{

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");
              $bmove_error = false;
            }
         
            $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
            $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
             $bmove_error = false;
              
            }else{

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");
              $bmove_error = false;
            }
         
            $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
            $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }

          //draw
          if(get_GameState() == 2){

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
               $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
               $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

               //update points
               $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
               $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");
             $bmove_error = false;

             $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
             $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

          }
		  
         
        }

      }

    }

  }


  /////////////////////////////////////////////////////////////////////////////////
  //50 move rule
  /////////////////////////////////////////////////////////////////////////////////
  $amoves = $oR3DCQuery->CheckFiftyMoveRule($config, $gid);

  if($amoves[0] == 50 && $amoves[1] == 50 && $gid != ""){

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $status = "";
    $completion_status = "";
    $start_time = "";
    $next_move = "";

    $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

    ///////////////////////////////////////////////////////////////////////
    //ELO Point Calculation
    if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
      $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
      $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

      //Calculate black player
      $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

      //Calculate white player
      $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

      //update points
      $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
      $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

    }
    ///////////////////////////////////////////////////////////////////////

    $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

  }
  /////////////////////////////////////////////////////////////////////////////////


  /////////////////////////////////////////////////////////////////////////////////
  //3 repetition rule
  /////////////////////////////////////////////////////////////////////////////////
  $arep = $oR3DCQuery->CheckRepetitionRule($config, $gid);

  if(($arep[0] == 3 && $gid != "")|| ($arep[1] == 3 && $gid != "")){

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $status = "";
    $completion_status = "";
    $start_time = "";
    $next_move = "";

    $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

    ///////////////////////////////////////////////////////////////////////
    //ELO Point Calculation
    if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
      $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
      $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

      //Calculate black player
      $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

      //Calculate white player
      $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

      //update points
      $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
      $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

    }
    ///////////////////////////////////////////////////////////////////////
    $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

  }
  /////////////////////////////////////////////////////////////////////////////////


  //////////////////////////////////////////////
  //Accept game
  if ($cmdAccept != "" && $gid != ""){
    $oR3DCQuery->AcceptGame($_SESSION['sid'], $gid, $_SESSION['id']);   
  }

  $cmdRevoke = $_GET['cmdRevoke'];

  $brevoked = false;
  //////////////////////////////////////////////
  //Revoke game
  if ($cmdRevoke != "" && $gid != ""){
    $oR3DCQuery->RevokeGame($gid, $_SESSION['id']);
    $brevoked = true;
  }

  $cmdRevokeChlng = $_GET['cmdRevokeChlng'];


  if($cmdRevokeChlng != "" && $gid != ""){
    $oR3DCQuery->RevokeGame2($gid, $_SESSION['id']);
    $brevoked = true;
  }

  //Check if the game is accepted
  $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
  $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id']);

  $cmdResign = $_GET['cmdResign'];
  if($cmdResign != ""){
    if($isblack){

      $initiator = "";
      $w_player_id = "";
      $b_player_id = "";
      $status = "";
      $completion_status = "";
      $start_time = "";
      $next_move = "";

      $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

      ///////////////////////////////////////////////////////////////////////
      //ELO Point Calculation
      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
        $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
        $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

        //Calculate black player
        $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

        //Calculate white player
        $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

        //update points
        $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
        $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

      }
      ///////////////////////////////////////////////////////////////////////

      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");  

      $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
      $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

    }else{

      $initiator = "";
      $w_player_id = "";
      $b_player_id = "";
      $status = "";
      $completion_status = "";
      $start_time = "";
      $next_move = "";

      $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

      ///////////////////////////////////////////////////////////////////////
      //ELO Point Calculation
      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($gid)){
        $bcurpoints = $oR3DCQuery->ELOGetRating($b_player_id);
        $wcurpoints = $oR3DCQuery->ELOGetRating($w_player_id);

        //Calculate black player
        $bnewpoints = $oR3DCQuery->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $oR3DCQuery->GetPlayerGameCount($b_player_id));

        //Calculate white player
        $wnewpoints = $oR3DCQuery->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $oR3DCQuery->GetPlayerGameCount($w_player_id));

        //update points
        $oR3DCQuery->ELOUpdateRating($b_player_id, $bnewpoints);
        $oR3DCQuery->ELOUpdateRating($w_player_id, $wnewpoints);

      }
      ///////////////////////////////////////////////////////////////////////

      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

      $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
      $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

    }
  }


///////////////
  $cmdRevokeDraw = $_GET['cmdRevokeDraw'];

  if($cmdRevokeDraw != ""){
    $oR3DCQuery->RevokeDrawGame($ConfigFile, $gid, $_SESSION['id']);
  }


  $cmdDraw = $_GET['cmdDraw'];
  if($cmdDraw != ""){

    if($isblack){
      $oR3DCQuery->DrawGame($config, $gid, "b");  
    }else{
      $oR3DCQuery->DrawGame($config, $gid, "w");

    }

  }


  $isexitrealtime = false;

  $isdraw = $oR3DCQuery->IsRequestDraw($config, $gid, $isblack);

  $cmdExitRealtime = $_GET['cmdExitRealtime'];

  if($cmdExitRealtime != ""){
    $oR3DCQuery->ExitRealTimeGame($config, $gid);

      $isexitrealtime = true;
      $_SESSION['RealTimeDoOnce'] = 0;
  }


  $rtend = $_GET['rtend'];

  if($rtend == 1){

      $isexitrealtime = true;
      $_SESSION['RealTimeDoOnce'] = 0;

  }

  $cmdSwitchRealtime = $_GET['cmdSwitchRealtime'];

  $oR3DCQuery->ManageRealTimeGame($config, $gid);

  if($cmdSwitchRealtime != ""){

    if($isblack){
      $oR3DCQuery->RealTimeGame($config, $gid, "b");  
    }else{
      $oR3DCQuery->RealTimeGame($config, $gid, "w");

    }

  }

  $isrealtime = $oR3DCQuery->IsRequestRealTime($config, $gid, $isblack);

  if($txtChatMessage != "" && $cmdChat != ""){

    if($_SESSION['CHAT_MESSAGE'] != $txtChatMessage){
      $txtChatMessage = str_replace ("\'","'",$txtChatMessage);
      $txtChatMessage = str_replace ("\`","'",$txtChatMessage);
      $message = "<".$_SESSION['user']."> ".$txtChatMessage;
      $oR3DCQuery->SendGChat($ConfigFile, $gid, $message);
      $_SESSION['CHAT_MESSAGE'] = $txtChatMessage;

    }

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_23", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>

</head>
<body>

<?php include("./skins/".$SkinName."/layout_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>