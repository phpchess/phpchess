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

  $tid = $_GET['tid'];
  $type = $_GET['type'];
  $gid = trim($_GET['gameid']);
  
  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_game1.php";  

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
  $cmdMovexx = trim($_GET['cmdMovexx']);

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

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
    }

  }
  ///////////////////////////////////////////////////////////////////////

  // Update tournament game login
  $oR3DCQuery->v2UpdateGameLoginAndPlayStatus($gid, $_SESSION['id'], $type, $tid);

  $bPlayerHasAccess = false;
  $bPrimaryPlayerHasAccess = false;

  // Check if the user is logged in and has access to the man player tournament console
  if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){

    if(is_numeric($_SESSION['id'])){

      if($oR3DCQuery->v2IsUserPlayer($tgc, $_SESSION['id'], $type, $tid)){

        if($oR3DCQuery->v2IsUserPrimaryPlayer($_SESSION['id'], $type, $tid) == false){

          $oR3DCQuery->v2ClearTournamentGameQueue($_SESSION['id'], $type, $tid);
          $bPlayerHasAccess = true;

        }elseif($oR3DCQuery->v2IsUserPrimaryPlayer($_SESSION['id'], $type, $tid)){

          $oR3DCQuery->v2ClearTournamentGameQueue($_SESSION['id'], $type, $tid);
          $bPrimaryPlayerHasAccess = true;

        }

      }

    }

  }

  // Make move
  if($movefrom != "" && $moveto != "" && $cmdMove !="" && $bPrimaryPlayerHasAccess && !$bPlayerHasAccess){

    //Create Move String
    $movestr = $movefrom.",".$moveto;
    $movestr2 = $movefrom."-".$moveto;

    // get the fen for the game
    $fen = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    //$fen3 = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    $bturn = $oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $gid, true);

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

          $bUpdateT = true;

	  //checkmate
          if(get_GameState() == 1){

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
            $bUpdateT = false;

          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }

            $bUpdateT = false;
         
          }

          //draw
          if(get_GameState() == 2){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
             $bUpdateT = false;

          }

          if($bUpdateT){

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

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

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
          }

          //draw
          if(get_GameState() == 2){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
          }
		  
         
        }

      }

    }

  }


  // Make move
  if($movefrom != "" && $moveto != "" && $cmdMove !="" && !$bPrimaryPlayerHasAccess && $bPlayerHasAccess){

    //Create Move String
    $movestr = $movefrom.",".$moveto;
    $movestr2 = $movefrom."-".$moveto;

    // get the fen for the game
    $fen = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    //$fen3 = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    $bturn = $oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $gid, true);

    if($bturn){

      //check to see if the move is valid
      if(is_Move_legal($fen, $movestr2)){ 

        $_SESSION['GAME_MOVE'] = $movestr;

        // if ! promotion screen
	if(!($move_promotion_figur && strlen($movestr2)==5)){
    	  $oR3DCQuery->v2AddTournamentGameMoveVote($gid, $_SESSION['id'], $movestr."|".$movestr2);

	  $bmove_error = false;
        }

      }else{
        $bmove_error = true;

      }

    }

  }

  // Make move (vote move)
  if($movefrom != "" && $moveto != "" && $cmdMovexx !=""){

    //Create Move String
    $movestr = $movefrom.",".$moveto;
    $movestr2 = $movefrom."-".$moveto;

    // get the fen for the game
    $fen = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    //$fen3 = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $gid);
    $bturn = true; //$oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $gid, true);

    if($bturn){

      //check to see if the move is valid
      if(is_Move_legal($fen, $movestr2)){ 

        $_SESSION['GAME_MOVE'] = $movestr;

        // if ! promotion screen
	if(!($move_promotion_figur && strlen($movestr2)==5)){

    	  $oR3DCQuery->CurrentGameMovePiece($config, $gid, $_SESSION['sid'], 0, $movestr, true);

          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move); 

          $bUpdateT = true;

	  //checkmate
          if(get_GameState() == 1){

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
            $bUpdateT = false;

          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }

            $bUpdateT = false;
         
          }

          //draw
          if(get_GameState() == 2){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
             $bUpdateT = false;

          }

          if($bUpdateT){

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

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

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
          }

          if(get_GameState() == 4){ //3

            //if($w_player_id == $_SESSION['id']){
            if($next_move == 'w'){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
              
            }else{

              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");

              $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
              if($isblack){
                $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
              }else{
                $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
              }

              $bmove_error = false;
            }
         
          }

          //draw
          if(get_GameState() == 2){

             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

             $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
             if($isblack){
               $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
             }else{
               $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
             }

             $bmove_error = false;
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

    $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

    $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
    if($isblack){
      $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
    }else{
      $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
    }

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

    $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");

    $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);
    if($isblack){
      $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
    }else{
      $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
    }

  }
  /////////////////////////////////////////////////////////////////////////////////


  //////////////////////////////////////////////////////////////
  // [x]
  if ($cmdAccept != "" && $gid != ""){
    $oR3DCQuery->AcceptGame($_SESSION['sid'], $gid, $_SESSION['id']);
  }

  //////////////////////////////////////////////////////////////
  // [x]
  $cmdRevoke = $_GET['cmdRevoke'];
  $brevoked = false;
  if ($cmdRevoke != "" && $gid != ""){
    $oR3DCQuery->RevokeGame($gid, $_SESSION['id']);
    $brevoked = true;
  }

  $cmdRevokeChlng = $_GET['cmdRevokeChlng'];
  if($cmdRevokeChlng != "" && $gid != ""){
    $oR3DCQuery->RevokeGame2($gid, $_SESSION['id']);
    $brevoked = true;
  }
  /////////////////////////////////////////////////////////////


  //Check if the game is accepted
  $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
  $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id'], true);

  /////////////////////////////////////////////////////////////
  // Resign Game
  $cmdResign = $_GET['cmdResign'];
  if($cmdResign != ""){

    if($isblack){

      $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);
      $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid);
      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");  

    }else{

      $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       
      $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");

    }

  }

  //////////////////////////////////////////////////////////////
  // Cancel the draw
  $cmdRevokeDraw = $_GET['cmdRevokeDraw'];
  if($cmdRevokeDraw != ""){
    $oR3DCQuery->RevokeDrawGame($ConfigFile, $gid, $_SESSION['id']);
    $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

    if($isblack){
      $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
    }else{
      $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
    }

  }

  /////////////////////////////////////////////////////////////
  // draw the game
  $cmdDraw = $_GET['cmdDraw'];
  if($cmdDraw != ""){
    $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

    if($isblack){
      $oR3DCQuery->DrawGame($config, $gid, "b");
      $oR3DCQuery->v2AddTournamentGameQueue($w_player_id, $type, $tid, $gid); 
    }else{
      $oR3DCQuery->DrawGame($config, $gid, "w");
      $oR3DCQuery->v2AddTournamentGameQueue($b_player_id, $type, $tid, $gid);
    }

  }

  $isexitrealtime = false;
  $isdraw = $oR3DCQuery->IsRequestDraw($config, $gid, $isblack);

  ////////////////////////////////////////////////////////////
  // [x]
  $cmdExitRealtime = $_GET['cmdExitRealtime'];
  if($cmdExitRealtime != ""){
    $oR3DCQuery->ExitRealTimeGame($config, $gid);
    $isexitrealtime = true;
    $_SESSION['RealTimeDoOnce'] = 0;
  }


  ////////////////////////////////////////////////////////////
  // [x]
  $rtend = $_GET['rtend'];
  if($rtend == 1){
    $isexitrealtime = true;
    $_SESSION['RealTimeDoOnce'] = 0;
  }

  ////////////////////////////////////////////////////////////
  // [x]
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

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">

<script language="JavaScript"><!--
var nav = window.Event ? true : false;
if (nav) {
   window.captureEvents(Event.KEYDOWN);
   window.onkeydown = NetscapeEventHandler_KeyDown;
} else {
   document.onkeydown = MicrosoftEventHandler_KeyDown;
}

function NetscapeEventHandler_KeyDown(e) {
  if (e.which == 13 && e.target.type != 'textarea' && e.target.type != 'submit') { return false; }
  return true;
}

function MicrosoftEventHandler_KeyDown() {
  if (event.keyCode == 13 && event.srcElement.type != 'textarea' && event.srcElement.type != 'submit')
    return false;
  return true;
}

function PopupHelpWin(webpage) {
     var url = webpage;
     var hWnd = window.open(url,"Help","width=500,height=400,resizable=no,scrollbars=yes,status=yes");
     if (hWnd != null) {     if (hWnd.opener == null) { hWnd.opener = self; window.name = "?"; hWnd.location.href=url; } }
}
//-->
</script>

<script Language="JavaScript">
<!--
function PopupWindowRT(webpage) {
     var url = webpage;
     var hWnd = window.open(url,"<?php echo md5(time()."".$_SESSION['id']);?>","width=600,height=580,resizable=no,scrollbars=yes,status=yes");
     if (hWnd != null) {     if (hWnd.opener == null) { hWnd.opener = self; window.name = "home"; hWnd.location.href=url; } }
}

function PopupWindow(webpage) {
     var url = webpage;
     var hWnd = window.open(url,"Chess4Me","width=500,height=400,resizable=no,scrollbars=yes,status=yes");
     if (hWnd != null) {     if (hWnd.opener == null) { hWnd.opener = self; window.name = "home"; hWnd.location.href=url; } }
}

function PopupPGNGame(webpage) {
     var url = webpage;
     var hWnd = window.open(url,"<?php echo md5(time());?>","width=560,height=540,resizable=no,scrollbars=yes,status=yes");
     if (hWnd != null) {     if (hWnd.opener == null) { hWnd.opener = self; window.name = "home"; hWnd.location.href=url; } }
}
//-->
</script> 
</head>
<body>

<?php
/////////////////////////////////////////////////////////////////////////
// Used to get the last move
/////////////////////////////////////////////////////////////////////////

$aMoveElm = $oR3DCQuery->GetGamesLastMove($gid);
$lmclr = "#666699";

/////////////////////////////////////////////////////////////////////////
?>

<script language="javascript"> 

function ClearCoordinates(){

  document.frmcolor.txtmovefrom.value = "";
  document.frmcolor.txtmoveto.value = "";

  document.getElementById('8-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('7-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('6-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('5-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('4-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('3-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('2-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('1-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-8').style.background = "<?php echo $clrl;?>";

  <?php
  ///////////////////////////////////////////////////////////////////////
  $lmcount = count($aMoveElm);

  if($lmcount != 0){
    $iz = 0;
    while($iz < $lmcount){
      echo "document.getElementById('".$aMoveElm[$iz]."').style.background = \"".$lmclr."\";\n";
      $iz++;
    }
  }
  ///////////////////////////////////////////////////////////////////////
  ?>

}

function GetCoordinate(coordinate){

  var aCoord = coordinate.split("-");
  var Col1 = '';

  switch(aCoord[1]){
    case '1':
      Col1 = 'a';
      break;
    case '2':
      Col1 = 'b';
      break;
    case '3':
      Col1 = 'c';
      break;
    case '4':
      Col1 = 'd';
      break;
    case '5':
      Col1 = 'e';
      break;
    case '6':
      Col1 = 'f';
      break;
    case '7':
      Col1 = 'g';
      break;
    case '8':
      Col1 = 'h';
      break;
  }

  return Col1 + "" + aCoord[0];

}

function ProcessMove(target){

  // This checks if the browser is an MSIE browser or Netscape browser.
  var browserVer=parseInt(navigator.appVersion); 
  if((navigator.appName == "Microsoft Internet Explorer" && browserVer >= 4) || navigator.appName == "Netscape" && browserVer >= 4){
  
   if(document.frmcolor.txtmovefrom != null && document.frmcolor.txtmoveto != null){

     document.getElementById(target).style.background = "#9999cc";

     if(document.frmcolor.txtmovefrom.value == "" && document.frmcolor.txtmoveto.value == ""){
 
       document.frmcolor.txtmovefrom.value = GetCoordinate(target);

     }else{

       if(document.frmcolor.txtmovefrom.value != "" && document.frmcolor.txtmoveto.value == ""){
 
         document.frmcolor.txtmoveto.value = GetCoordinate(target);

       }else{

         if(document.frmcolor.txtmovefrom.value != "" && document.frmcolor.txtmoveto.value != ""){
           // clear selection
           ClearCoordinates();
         }

       }

     }
     
     //alert(target);
   }


  } 

}
</script>


<?php
////////////////////////////////////////////////////////////////////////
// Get Game Status
////////////////////////////////////////////////////////////////////////
list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

if($status == "waiting" || $status == "-"){

  if($PlayerType == "i"){
?>

<form name='frmAccept' method='get' action='./tv2mp_game.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_GAME_TXT_1", $config);?>
<?php $oR3DCQuery->TimedGameStats($gid);?>
</td>
</tr>

<tr>
<td class='row1'>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
<Input type='submit' name='cmdRevokeChlng' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
</td>
</tr>
</table>
</form>

<?php
  }

  if($PlayerType == "o"){
?>

<form name='frmAccept' method='get' action='./tv2mp_game.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_GAME_TXT_2", $config);?>
<?php $oR3DCQuery->TimedGameStats($gid);?>
</td>
</tr>
<tr>
<td class='row1'>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
<input type='hidden' name='tid' value='<?php echo $tid;?>'>
<input type='hidden' name='type' value='<?php echo $type;?>'>
<Input type='submit' name='cmdAccept' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_ACCEPT_CHALLANGE", $config);?>' class='mainoption'>
<Input type='submit' name='cmdRevoke' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
</td>
</tr>
</table>
</form>

<?php
  }

}else{

  if($move_promotion_figur && strlen($movestr2)==5){

    $url = "tv2mp_game.php?tid=".$tid."&type=".$type."&txtmovefrom=".$movefrom."&txtmoveto=".$moveto;

    if(trim(get_turn()) == "b"){
      //black
      $queen_img = "08.gif";
      $rook_img = "09.gif";
      $bishop_img = "10.gif";
      $knight_img = "11.gif";
    }else{
      //white
      $queen_img = "02.gif";
      $rook_img = "03.gif";
      $bishop_img = "04.gif";
      $knight_img = "05.gif";
    }
?>

<table border='0' cellpadding='2' cellsapcing='0' width='80%' align='center'>
<tr><td colspan='4' class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_3", $config);?></td></tr>
<tr>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>Q&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $queen_img; ?>' border='0' alt='Queen' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>R&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $rook_img; ?>' border='0' alt='Rook' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>B&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $bishop_img; ?>' border='0' alt='Bishop' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>N&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $knight_img; ?>' border='0' alt='Knight' width='36' height='36'></a></td>
</tr>
</table>

<?php
  }elseif($idc != "" && $gid != ""){

    echo "<form name='frmcolor' method='get' action='./tv2mp_game.php'>";
    $oR3DCQuery->GetPrevGameStatus($config, $gid, $_SESSION['sid'], $_SESSION['id'], $idc, $clrl, $clrd);
    echo "<input type='hidden' name='gameid' value='".$gid."'>";
    echo "<input type='hidden' name='tid' value='".$tid."'>";
    echo "<input type='hidden' name='type' value='".$type."'>";
    echo "</form>";

  }else{

    if($gid != ""){
      $completion_status = get_completion_status();

      if($completion_status == 'W'){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_4", $config);?></td>
</tr>
</table>
<br>

<?php	  
      }

      if($completion_status == 'B'){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_5", $config);?></td>
</tr>
</table>
<br>

<?php
      }

      if($completion_status == 'D') {
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_6", $config);?></td>
</tr>
</table>
<br>

<?php
      }
	
      if($bmove_error == true){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_7", $config);?></td>
</tr>
</table>
<br>

<?php
      }

      if($isdraw == "IDS_USER_REQUESTED_DRAW"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_8", $config);?></td>
</tr>
</table>

<?php
      }

      if($isdraw == "IDS_DRAW_REQUESTED"){
?>

<form name='frmRevokeDraw' method='get' action='./tv2mp_game.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_9", $config);?> 
<input type='submit' name='cmdRevokeDraw' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_DRAW", $config);?>' class='mainoption'>
<input type='submit' name='cmdDraw' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_ACCEPT_DRAW", $config);?>' class='mainoption'>
</td>
</tr>
</table>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
<input type='hidden' name='tid' value='<?php echo $tid;?>'>
<input type='hidden' name='type' value='<?php echo $type;?>'>
</form>

<?php
      }

      if($isrealtime == "IDS_REAL_TIME"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_10", $config);?></td>
</tr>
</table>

<?php
        if($_SESSION['RealTimeDoOnce'] == 0){
          $_SESSION['RefreashGameOnlyOnce'] = "1";
?>

<script language="javascript">
<!-- 
location.replace('./r_index.php?gid=<?php echo $gid;?>&pn=<?php echo $_SESSION['user'];?>');
-->
</script>

<?php
          $_SESSION['RealTimeDoOnce'] = 1;
        }

      }

      if($isrealtime == "IDS_USER_REQUESTED_REAL_TIME"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_11", $config);?></td>
</tr>
</table>

<?php
        if($_SESSION['RealTimeDoOnce'] == 0){
          $_SESSION['RefreashGameOnlyOnce'] = "";
?>

<script language="javascript">
<!-- 
location.replace('./r_index.php?gid=<?php echo $gid;?>&pn=<?php echo $_SESSION['user'];?>');
-->
</script>

<?php
          $_SESSION['RealTimeDoOnce'] = 1;
        }

      }

      if($isrealtime == "IDS_REALTIME_REQUESTED"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_12", $config);?></td>
</tr>
</table>

<?php
      }

      echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";
      echo "<tr><td valign='top'>";

      echo "<form name='frmcolor' method=\"GET\" action='./tv2mp_game.php'>";
      $oR3DCQuery->V2GetGameStatus($config, $gid, $_SESSION['sid'], $_SESSION['id'], get_completion_status(), $clrl, $clrd, $oR3DCQuery->GetBoardStyleByUserID($_SESSION['id']), 1, 'dd');
      echo "<input type='hidden' name='gameid' value='".$gid."'>";
      echo "<input type='hidden' name='tid' value='".$tid."'>";
      echo "<input type='hidden' name='type' value='".$type."'>";
      echo "</form>";

      echo "</td><td valign='top'>";
      $oR3DCQuery->v2GetTournamentGameMoveVote($gid);
      echo "</td></tr>";
      echo "</table>";

    }else{
?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_GAME_CHESS_GAME_TABLE_HEADER", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_GAME_CHESS_GAME_TABLE_TXT_1", $config);?></td>
</tr>
</table>
<?php
    }

  }

}

/////////////////////////////////////////////////////////////////////////
// Used to get the last move
/////////////////////////////////////////////////////////////////////////
$lmcount = count($aMoveElm);

if($lmcount != 0){

  echo "<script language=\"JavaScript\">\n";
  echo "function SetLastMoveOnChessboard(){\n";
  echo "var browserVer=parseInt(navigator.appVersion);\n"; 
  echo "if((navigator.appName == \"Microsoft Internet Explorer\" && browserVer >= 4) || navigator.appName == \"Netscape\" && browserVer >= 4){\n";

  $iz = 0;
  while($iz < $lmcount){
    echo "document.getElementById('".$aMoveElm[$iz]."').style.background = \"".$lmclr."\";\n";
    $iz++;
  }

  echo "}\n";
  echo "}\n";

  if(!$oR3DCQuery->isBoardCustomerSettingDragDrop($_SESSION['id'])){
    echo "SetLastMoveOnChessboard();\n";
  }

  echo "</script>\n";
}

/////////////////////////////////////////////////////////////////////////


// Used for the vote move
echo "<form name='frmMoveVote' method=\"GET\" action='./tv2mp_game.php'>";
echo "<input type='hidden' name='txtmovefrom' class='post' size='3'>";
echo "<input type='hidden' name='txtmoveto' class='post' size='3'>";
echo "<input type='hidden' name='cmdMovexx' value=''>";

echo "<input type='hidden' name='gameid' value='".$gid."'>";
echo "<input type='hidden' name='tid' value='".$tid."'>";
echo "<input type='hidden' name='type' value='".$type."'>";
echo "</form>";


?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>