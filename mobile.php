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
  
  include($Root_Path."CChess2.php");
  include($Root_Path."CChessBoard.php");
  include($Root_Path."CChessBoardUtilities.php");
  include($Root_Path."CSession.php");

	/**********************************************************************
	* isSessionIDValid
	*
	*/
	function isSessionIDValid($config, $xsid)
	{
		$bValid = false;

		//Instantiate the CR3DCQuery Class
		$oR3DCQuery = new CR3DCQuery($config);
		//$oR3DCQuery->CheckSIDTimeout();
		CSession::initialise($config);
		CSession::CheckSIDTimeout();

		if($xsid != "")
		{
			//if($oR3DCQuery->CheckLogin($config, $xsid))
			if(CSession::CheckLogin($xsid))
			{
				$session = base64_decode($xsid);
				// list($uniq, $player_id) = preg_split("/\|/", $session);
				list($uniq, $player_id) = explode("|", $session);

				if($uniq != "" && is_numeric($player_id))
				{
					$bValid = true;

					//$oR3DCQuery->UpdateSIDTimeout($config, $xsid);
					CSession::UpdateSIDTimeout($xsid);
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
  
  // function isSessionIDValid($config, $xsid){

    // $bValid = false;

    // //Instantiate the CR3DCQuery Class
    // $oR3DCQuery = new CR3DCQuery($config);
    // $oR3DCQuery->CheckSIDTimeout();

    // if($xsid != ""){

      // if($oR3DCQuery->CheckLogin($config, $xsid)){

        // $session = base64_decode($xsid);
        // list($uniq, $player_id) = preg_split("/\|/", $session);

        // if($uniq != "" && is_numeric($player_id)){

          // $bValid = true;

          // $oR3DCQuery->UpdateSIDTimeout($config, $xsid);
          // $oR3DCQuery->SetPlayerCreditsInit($player_id);

        // }

      // }

    // }

    // if($oR3DCQuery->ELOIsActive()){
      // $oR3DCQuery->ELOCreateRatings();
    // }

    // $oR3DCQuery->MangeGameTimeOuts();
    // $oR3DCQuery->Close();
    // unset($oR3DCQuery);

    // return $bValid;

  // }

  // Main Application Code
  switch($action){

    ////////////////////////////////////////////////////////////////////////////////////////////
    // LOGIN
    // Params: action, user, pass
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "login":

      if($_GET['user'] != "" && $_GET['pass'] != ""){

        //Instantiate the CR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $sid = $oR3DCQuery->Login($_GET['user'], $_GET['pass']);

        if($sid != ""){

          $session = base64_decode($sid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          /////////////////////////////////////////////
          // Point caching
          /////////////////////////////////////////////
          $oR3DCQuery->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $x_wins, $x_loss, $x_draws);
          $xPoints=0;

          if($oR3DCQuery->ELOIsActive()){
            $xPoints = $oR3DCQuery->ELOGetRating($player_id);
          }else{
            $xPoints = $oR3DCQuery->GetPointValue($x_wins, $x_loss, $x_draws);
          }

          $oR3DCQuery->SetChessPointCacheData($player_id, $xPoints);

          /////////////////////////////////////////////

          echo "<RESPONSE>\n";
          echo "<SID>".$sid."</SID>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<SID></SID>\n";
          echo "</RESPONSE>\n";

        }

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<SID></SID>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // LOGOUT
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "logout":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate the CChess Class
        $oChess = new CChess($config);
        $sid = $oChess->delete_session($config, $xsid);
        unset($oChess);

        echo "<RESPONSE>\n";
        echo "<SID></SID>\n";
        echo "</RESPONSE>\n";

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // FEN
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "fen":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != ""){

          //Instantiate the CR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $fen = $oR3DCQuery->GetActualFEN($xsid, $_GET['gameid']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<FEN>".$fen."</FEN>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<FEN></FEN>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>ID_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // MOVE
    // Params: action, sid, gameid, from, to
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "moveold":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != "" && $_GET['from'] != "" && $_GET['to'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);
   
          $movefrom = $_GET['from'];
          $moveto = $_GET['to'];

          $bmove_error = false;


          if($movefrom != "" && $moveto != ""){

            //Create Move String
            $movestr = $movefrom.",".$moveto;
            $movestr2 = $movefrom."-".$moveto;


            // get the fen for the game
            $fen = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
            //$fen3 = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
            $bturn = $oR3DCQuery->IsPlayersTurn($config, $player_id, $_GET['gameid']);

            if($bturn){

              //check to see if the move is valid
              if(is_Move_legal($fen, $movestr2)){ 

                // if ! promotion screen
      	        if(!($move_promotion_figur && strlen($movestr2)==5)){

                  $rtncode = $oR3DCQuery->CurrentGameMovePiece($config, $_GET['gameid'], $xsid, $player_id, $movestr);

                  $initiator = "";
                  $w_player_id = "";
                  $b_player_id = "";
                  $status = "";
                  $completion_status = "";
                  $start_time = "";
                  $next_move = "";
		
                  $oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move); 
		
	          //checkmate
                  if(get_GameState() == 1){
 
                    //if($w_player_id == $player_id){
                    if($next_move == 'w'){

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
                      $bmove_error = false;
              
                    }else{

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
                      $bmove_error = false;
                    }

                    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
                    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);
         
                  }

                  if(get_GameState() == 4){ //3

                    //if($w_player_id == $player_id){
                    if($next_move == 'w'){
                    
                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
                      $bmove_error = false;
              
                    }else{
 
                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
                      $bmove_error = false;
                    }
            
                    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
                    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

                  }

                  //draw
                  if(get_GameState() == 2){

                    ///////////////////////////////////////////////////////////////////////
                    //ELO Point Calculation
                    if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                    $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");
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

                  $oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

                  //checkmate
                  if(get_GameState() == 1){

                    //if($w_player_id == $player_id){
                    if($next_move == 'w'){

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
                      $bmove_error = false;
              
                    }else{

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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
  
                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
                      $bmove_error = false;
                    }
           
                    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
                    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

                  }

                  if(get_GameState() == 4){ //3

                    //if($w_player_id == $player_id){
                    if($next_move == 'w'){

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
                      $bmove_error = false;
              
                    }else{

                      ///////////////////////////////////////////////////////////////////////
                      //ELO Point Calculation
                      if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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
  
                      $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
                      $bmove_error = false;
                    }
         
                    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
                    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

                  }
 
                  //draw
                  if(get_GameState() == 2){

                    ///////////////////////////////////////////////////////////////////////
                    //ELO Point Calculation
                    if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

                    $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");
                    $bmove_error = false;

                    $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
                    $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

                  }
		  
                }

              }

            }else{
              $bmove_error = true;
            }

            if($bmove_error){
              echo "<RESPONSE>\n";
              echo "<MOVE>false</MOVE>\n";
              echo "</RESPONSE>\n";
            }else{
              echo "<RESPONSE>\n";
              echo "<MOVE>true</MOVE>\n";
              echo "</RESPONSE>\n";
            }

          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<MOVE>false</MOVE>\n";
          echo "</RESPONSE>\n";

        }

        /////////////////////////////////////////////////////////////////////////////////
        //50 move rule
        /////////////////////////////////////////////////////////////////////////////////
        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $amoves = $oR3DCQuery->CheckFiftyMoveRule($config, $_GET['gameid']);

        if($amoves[0] == 50 && $amoves[1] == 50 && $_GET['gameid'] != ""){

          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

          ///////////////////////////////////////////////////////////////////////
          //ELO Point Calculation
          if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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

          $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");

          $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
          $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

        }

        unset($oR3DCQuery);

        /////////////////////////////////////////////////////////////////////////////////
        //3 repetition rule
        /////////////////////////////////////////////////////////////////////////////////
        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $arep = $oR3DCQuery->CheckRepetitionRule($config, $_GET['gameid']);

        if(($arep[0] == 3 && $_GET['gameid'] != "")|| ($arep[1] == 3 && $_GET['gameid'] != "")){
  
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);       

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
          $oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");

          $oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
          $oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);

        }

        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

	case "move":
	
		if(isSessionIDValid($config, $xsid))
		{
			mobile_move($config);
		}
		else
		{
			echo "<RESPONSE>\n";
			echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
			echo "</RESPONSE>\n";
		}
		break;
	  
    ////////////////////////////////////////////////////////////////////////////////////////////
    // gamelist
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "gamelist":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        //echo $oR3DCQuery->GetCurrentGamesByPlayerIDForMobile($config, $player_id);
        echo $oR3DCQuery->GetCurrentGamesByPlayerIDForMobile2($config, $player_id);
		echo "<test>" . mobile_get_recent_games($player_id, $oR3DCQuery) . "</test>\n";
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // openchallenges
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "openchallenges":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        //echo $oR3DCQuery->GetCurrentOpenChallengeGamesForMobile($config, $player_id);
        echo $oR3DCQuery->GetCurrentGameChallengesByPlayerID($config, $player_id);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;


    ////////////////////////////////////////////////////////////////////////////////////////////
    // gamelistAllActivegames
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "gamelistcomplete":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        echo $oR3DCQuery->GetCurrentGamesAllForMobile($config, $player_id);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;


    ////////////////////////////////////////////////////////////////////////////////////////////
    // acceptgame
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "acceptgame":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $player_id, $_GET['gameid']);
          list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

          if(($status == "waiting" || $status == "-") && $PlayerType == "o"){

            $oR3DCQuery->AcceptGame($xsid, $_GET['gameid'], $player_id);

            echo "<RESPONSE>\n";
            echo "<ACCEPTGAME>";
            echo "true";
            echo "</ACCEPTGAME>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ACCEPTGAME>";
            echo "false";
            echo "</ACCEPTGAME>\n";
            echo "</RESPONSE>\n";
 
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ACCEPTGAME>";
          echo "false";
          echo "<ACCEPTGAME>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // acceptopenchallange
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "acceptopenchallange":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $player_id, $_GET['gameid']);
          list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

          if(($status == "waiting" || $status == "-") && $PlayerType == "o"){

            $oR3DCQuery->AcceptOCGame($xsid, $_GET['gameid'], $player_id);

            echo "<RESPONSE>\n";
            echo "<ACCEPTGAME>";
            echo "true";
            echo "</ACCEPTGAME>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ACCEPTGAME>";
            echo "false";
            echo "</ACCEPTGAME>\n";
            echo "</RESPONSE>\n";
 
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ACCEPTGAME>";
          echo "false";
          echo "<ACCEPTGAME>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;


    ////////////////////////////////////////////////////////////////////////////////////////////
    // revokegame
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "revokegame":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $player_id, $_GET['gameid']);
          list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

          if($status == "waiting" || $status == "-"){

            if($PlayerType == "i"){
              $oR3DCQuery->RevokeGame2($_GET['gameid'], $player_id);
            }else{
              $oR3DCQuery->RevokeGame($_GET['gameid'], $player_id);
            }

            echo "<RESPONSE>\n";
            echo "<REVOKEGAME>";
            echo "true";
            echo "</REVOKEGAME>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<REVOKEGAME>";
            echo "false";
            echo "</REVOKEGAME>\n";
            echo "</RESPONSE>\n";
 
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<REVOKEGAME>";
          echo "false";
          echo "</REVOKEGAME>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // register
    // Params: action, userid, email
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "register":

      if($_GET['userid'] != "" && $_GET['email'] != ""){

        //Instantiate the CBilling Class
        $oBilling = new CBilling($config);
        $RequiresPayment = $oBilling->IsPaymentEnabled();
        $oBilling->Close();
        unset($oBilling);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $bRequiresApproval = $oR3DCQuery->NewUserRequiresApproval();

        $ReturnStatus = "";

        if($_GET['userid'] != "" && $_GET['email'] != ""){

          if($bRequiresApproval == false && $RequiresPayment == false){

            //Add the new user
            $ReturnStatus = $oR3DCQuery->RegisterNewPlayer($_GET['userid'], $_GET['email']);

          }elseif($bRequiresApproval == true  && $RequiresPayment == false){

            //Add the new user
            $ReturnStatus = $oR3DCQuery->RegisterNewPlayer2($_GET['userid'], $_GET['email']);

          }

        }

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

        echo "<RESPONSE>\n";
        echo "<REGISTER>";
        echo $ReturnStatus;
        echo "</REGISTER>\n";
        echo "</RESPONSE>\n";

      }else{

        echo "<RESPONSE>\n";
        echo "<REGISTER>";
        echo "false";
        echo "</REGISTER>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // playersonline
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "playersonline":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate the CR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        $oR3DCQuery->GetOnlinePlayersForMobile();
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // servermsg
    // Params: action
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "servermsg":

      //Instantiate CServMsg Class
      $oServMsg = new CServMsg($config);

      echo "<RESPONSE>\n";
      $oServMsg->GetMessagesForMobile();
      echo "</RESPONSE>\n";

      $oServMsg->Close();
      unset($oServMsg);

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // news
    // Params: action
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "news":

      //Instantiate the CFrontNews Class
      $oFrontNews = new CFrontNews($config);

      echo "<RESPONSE>\n";
      $oFrontNews->GetFrontNewsForMobile();
      echo "</RESPONSE>\n";

      $oFrontNews->Close();
      unset($oFrontNews);

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // playerstats
    // Params: action, sid, playerid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "playerstats":

      if(isSessionIDValid($config, $xsid)){

        if(is_numeric($playerid)){

          //Instantiate the CR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          echo "<RESPONSE>\n";
          $oR3DCQuery->GetPlayerStatusInformationForMobile($playerid);
          echo "</RESPONSE>\n";

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{
          echo "<RESPONSE>\n";
          echo "</RESPONSE>\n";
        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // pgn
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "pgn":

          echo "<RESPONSE>\n";
      if(isSessionIDValid($config, $xsid)){

        if($_GET['gameid'] != ""){

          //Instantiate the CChess Class
          // $oChess = new CChess($config);
          // $pgn = $oChess->get_move_history_list($config, $_GET['gameid']);
          // unset($oChess);

          // echo "<PGN>".$pgn."</PGN>\n";
		  
		  mobile_get_pgn($_GET['gameid']);
		  //mobile_get_captured_pieces($config);

        }else{

          echo "<ERROR>IDS_GAME_ID_INVALID</ERROR>\n";

        }

      }else{

        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";

      }
        echo "</RESPONSE>\n";

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // sendmsg
    // Params: action, sid, rplayerid, message
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "sendmsg":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['rplayerid'] != "" && $_GET['message']){

          $txtmsg = $_GET['message'];

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);
  
          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $aToReplace = array("<", ">", "\'", "\\\"", "", "", "", "", "", "");
          $aReplaceWith = array("&lt;", "&gt;", "&#x27;", "&#x22;", "&#x201C;", "&#x201D;", "&#x201E;", "&#x2018;", "&#x2019;", "&#x201A;");

          $txtmsg = str_replace($aToReplace, $aReplaceWith, $txtmsg);
          $oR3DCQuery->SendMessage($_GET['rplayerid'], $xsid, $player_id, $txtmsg);        
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<SENT>true</SENT>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<SENT>false</SENT>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // playerid
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "playerid":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);
  
        echo "<RESPONSE>\n";
        echo "<PID>".$player_id."</PID>\n";
        echo "</RESPONSE>\n";

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getmsg
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getmsg":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $oR3DCQuery->DownloadNewMessages($config, $player_id, $xsid);

        echo "<RESPONSE>\n";
        $oR3DCQuery->GetInboxForMobile($config, $player_id);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);
  
      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // playerlist
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "playerlist":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        $oR3DCQuery->ListAvailablePlayersAForMobile($config, $player_id);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);
  
      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // playerlist2
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "playerlist2":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        $oR3DCQuery->ListAvailablePlayersAForMobile2($config, $player_id);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);
  
      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;



    ////////////////////////////////////////////////////////////////////////////////////////////
    // gamelistall
    // Params: action, sid, playerid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "gamelistall":

      if(isSessionIDValid($config, $xsid)){

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        $PID = $_GET['playerid'];

        if($PID == ""){
          $PID = $player_id;
        }

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

        echo "<RESPONSE>\n";
        echo $oR3DCQuery->GetAllGamesByPlayerIDForMobile($config, $PID);
        echo "</RESPONSE>\n";

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // postclientip
    // Params: action, sid, ip
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "postclientip":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['ip'] != ""){

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          if($oR3DCQuery->PostMobileClientIP($config, $player_id, $_GET['ip'])){

            echo "<RESPONSE>\n";
            echo "<ADDED>";
            echo "true";
            echo "</ADDED>\n";
            echo "</RESPONSE>\n";

          }else{
            echo "<RESPONSE>\n";
            echo "<ADDED>";
            echo "false";
            echo "</ADDED>\n";
            echo "</RESPONSE>\n";
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{
          echo "<RESPONSE>\n";
          echo "<ADDED>";
          echo "false";
          echo "</ADDED>\n";
          echo "</RESPONSE>\n";
        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getclientip
    // Params: action, sid, playerid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getclientip":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['playerid'] != ""){

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $oR3DCQuery->GetMobileClientIPByPlayerID($config, $_GET['playerid']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{
          echo "<RESPONSE>\n";
          echo "</RESPONSE>\n";
        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // creategame
    // Params: action, sid, oplayerid, mypiececolor, fen, move1, time1, move2, time2
    //         brtGame, precreate, brealtimeposs, ratingtype, gametime
    //
    // Param values: mypiececolor{w,b} brtGame{0,1} brealtimeposs{0,1} ratingtype{grated, gunrated} 
    //               gametime{C-Normal,C-Blitz,C-Short,C-Slow,C-Snail}(Pasv RT/Normal)
    //               gametime{RT-Custom,RT-Blitz,RT-Short,RT-Normal,RT-Slow}(Active RT)
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "creategame":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        $strErrorLine = "";
        $fen = $_GET['fen'];
        $precreate = $_GET['precreate'];

        if($_GET['oplayerid'] == $player_id){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_OPLAYERID_CHALLENGING_SELF</ERROR>\n";
        }

        // oplayerid
        if(!is_numeric($_GET['oplayerid'])){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_OPLAYERID</ERROR>\n";
        }

        // mypiececolor
        if($_GET['mypiececolor'] != 'w' && $_GET['mypiececolor'] != 'b'){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_MYPIECECOLOR</ERROR>\n";
        }

        // fen
        if(trim($fen) != ""){

          // validate the fen
          if(preg_match('/^([rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/\s[wb]{1}\s[KQkq-]{1,4}\s-\s[0-9]{1,4}\s[0-9]{1,4})$/', trim($fen))){
            $precreate = 0;
          }elseif(preg_match('/^([rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\s[wb]{1}\s[KQkq-]{1,4}\s-\s[0-9]{1,4}\s[0-9]{1,4})$/', trim($fen))){

            // fen is valid
            list($part1, $part2, $part3, $part4, $part5) = explode(" ", $fen,5);
            $fen = "$part1/ $part2 $part3 $part4 $part5";
            $precreate = 0;

          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_FEN</ERROR>\n";
          }

        }

        // move1
        if($_GET['move1'] != ""){

          if(is_numeric($_GET['move1'])){

            if(strpos($_GET['move1'], ".") === false){
              // no error
            }else{
              $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_MOVE1</ERROR>\n";
            }

          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_MOVE1</ERROR>\n";
          }

        }

        // time1
        if($_GET['time1'] != ""){

          if(is_numeric($_GET['time1'])){

            if(strpos($_GET['time1'], ".") === false){
              // no error
            }else{
              $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIME1</ERROR>\n";
            }

          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIME1</ERROR>\n";
          }

        }
        
        // move2
        if($_GET['move2'] != ""){

          if(is_numeric($_GET['move2'])){

            if(strpos($_GET['move2'], ".") === false){
              // no error
            }else{
              $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_MOVE2</ERROR>\n";
            }

          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_MOVE2</ERROR>\n";
          }

        }
        
        // time2
        if($_GET['time2'] != ""){

          if(is_numeric($_GET['time2'])){

            if(strpos($_GET['time2'], ".") === false){
              // no error
            }else{
              $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIME2</ERROR>\n";
            }

          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIME2</ERROR>\n";
          }

        }

        // brtGame
        if($_GET['brtGame'] != true && $_GET['brtGame'] != false){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_BRTGAME</ERROR>\n";
        }

        // precreate
        if(is_numeric($precreate)){

          if(strpos($precreate, ".") === false){
            // no error
          }else{
            $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_PRECREATE</ERROR>\n";
          }

        }else{
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_PRECREATE</ERROR>\n";
        }

        // brealtimeposs
        if($_GET['brealtimeposs'] != true && $_GET['brealtimeposs'] != false){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_BREALTIMEPOSS</ERROR>\n";
        }

        // ratingtype
        if($_GET['ratingtype'] != "grated" && $_GET['ratingtype'] != "gunrated"){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_RATINGTYPE</ERROR>\n";
        }

        // gametime
        if($_GET['gametime'] != "C-Normal" && $_GET['gametime'] != "C-Blitz" && $_GET['gametime'] != "C-Short" && $_GET['gametime'] != "C-Slow" && $_GET['gametime'] != "C-Snail" && $_GET['gametime'] != "RT-Custom" && $_GET['gametime'] != "RT-Blitz" && $_GET['gametime'] != "RT-Short" && $_GET['gametime'] != "RT-Normal" && $_GET['gametime'] != "RT-Slow"){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_GAMETIME</ERROR>\n";
        }

        // Real Time Game check
        if($_GET['brealtimeposs'] == true && $_GET['brtGame'] == true){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_BREALTIMEPOSS_AND_BRTGAME_BOTH_TRUE</ERROR>\n";
        }

        // Normal/Pasv game timeout check
        if((($_GET['brtGame'] == false && $_GET['brealtimeposs'] == true) || ($_GET['brtGame'] == false && $_GET['brealtimeposs'] == false)) && $_GET['gametime'] != "C-Normal" && $_GET['gametime'] != "C-Blitz" && $_GET['gametime'] != "C-Short" && $_GET['gametime'] != "C-Slow" && $_GET['gametime'] != "C-Snail"){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIMEOUT_NORMAL_PASVRT_GAME</ERROR>\n";
        }
        
        // Active Real-time timeout check
        if(($_GET['brtGame'] == true && $_GET['brealtimeposs'] == false) && $_GET['gametime'] != "RT-Custom" && $_GET['gametime'] != "RT-Blitz" && $_GET['gametime'] != "RT-Short" && $_GET['gametime'] != "RT-Normal" && $_GET['gametime'] != "RT-Slow"){
          $strErrorLine = $strErrorLine."<ERROR>IDS_INVALID_VAR_TIMEOUT_ACTIVERT_GAME</ERROR>\n";
        }

        if($strErrorLine == ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $txtgid = $oR3DCQuery->CreateGame($config, $xsid, $player_id, $_GET['oplayerid'], $_GET['mypiececolor'], $fen, $_GET['move1'], $_GET['time1'], $_GET['move2'], $_GET['time2'], $_GET['brtGame'], $precreate, $_GET['brealtimeposs'], $_GET['ratingtype'], $_GET['gametime']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);
       
          echo "<RESPONSE>\n";
          echo "<CREATEGAMECODE>".$txtgid."</CREATEGAMECODE>\n";
          echo "</RESPONSE>\n";

        }else{
          echo "<RESPONSE>\n";
          echo $strErrorLine;
          echo "</RESPONSE>\n";
        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // gamedrawstatus
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "gamedrawstatus":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
          $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<DRAWCODE>".$isdraw."</DRAWCODE>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<DRAWCODE></DRAWCODE>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // drawgame
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "drawgame":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);

          if($isblack){
            $oR3DCQuery->DrawGame($config, $_GET['gameid'], "b");  
          }else{
            $oR3DCQuery->DrawGame($config, $_GET['gameid'], "w");
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<DRAW>true</DRAW>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<DRAW>false</DRAW>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // acceptdrawgame
	// Accepts a draw request. Checks that the draw request is still current. If not, then
	// it does nothing (returning draw status none).
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "acceptdrawgame":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
		  $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
		  if($isdraw == 'IDS_DRAW_REQUESTED')
		  {
			  if($isblack){
				$oR3DCQuery->DrawGame($config, $_GET['gameid'], "b");  
			  }else{
				$oR3DCQuery->DrawGame($config, $_GET['gameid'], "w");
			  }
		  }
		  $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
          
		  $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<DRAW>true</DRAW>\n";
		  echo "<DRAWCODE>$isdraw</DRAWCODE>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<DRAW>false</DRAW>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // revokedrawgame
	// Revokes a draw, unless the other player has already accepted it.
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "revokedrawgame":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

		  $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
		  $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
		  if($isdraw !== 'IDS_DRAW')
		  {
			$oR3DCQuery->RevokeDrawGame($config, $_GET['gameid'], $player_id);
          }
		  $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<DRAW>true</DRAW>\n";
		  echo "<DRAWCODE>$isdraw</DRAWCODE>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<DRAW>false</DRAW>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // resigngame
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "resigngame":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

           $gid = $_GET['gameid'];

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);

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

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<RESIGN>true</RESIGN>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<RESIGN>false</RESIGN>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // sendgamechat
    // Params: action, sid, gameid, msg
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "sendgamechat":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != "" && $_GET['msg'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $message = "<".$oR3DCQuery->GetUserIDByPlayerID($config, $player_id)."> ".$_GET['msg'];

          $aToReplace = array("<", ">", "\'", "\\\"", "", "", "", "", "", "");
          $aReplaceWith = array("&lt;", "&gt;", "&#x27;", "&#x22;", "&#x201C;", "&#x201D;", "&#x201E;", "&#x2018;", "&#x2019;", "&#x201A;");
          
          // Added the urlencode and htmlentities
	  htmlentities(urlencode($message));
          $message = str_replace($aToReplace, $aReplaceWith, $message);

          $oR3DCQuery->SendGChat($config, $_GET['gameid'], $message);

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<SENTMSG>true</SENTMSG>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<SENTMSG>false</SENTMSG>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getgamechat
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getgamechat":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $message = $oR3DCQuery->GetGChatForMobile($config, $_GET['gameid']);

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          $aToReplace = array("<", ">", "\'", "\"", "", "", "", "", "", "");
          $aReplaceWith = array("&lt;", "&gt;", "&#x27;", "&#x22;", "&#x201C;", "&#x201D;", "&#x201E;", "&#x2018;", "&#x2019;", "&#x201A;");
          $message = str_replace($aToReplace, $aReplaceWith, $message);

          echo "<RESPONSE>\n";
          echo "<MSG>".$message."</MSG>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<MSG></MSG>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // switchrealtime
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "switchrealtime":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);

          if($isblack){
            $oR3DCQuery->RealTimeGame($config, $_GET['gameid'], "b");  
          }else{
            $oR3DCQuery->RealTimeGame($config, $_GET['gameid'], "w");
          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<SETRT>true</SETRT>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<SETRT>false</SETRT>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // exitrealtime
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "exitrealtime":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);

          $oR3DCQuery->ExitRealTimeGame($config, $_GET['gameid']);

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<EXITRT>true</EXITRT>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<EXITRT>false</EXITRT>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // realtimestatus
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "realtimestatus":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          $isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
          $status = $oR3DCQuery->IsRequestRealTime($ConfigFile, $_GET['gameid'], $isblack);

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          echo "<RESPONSE>\n";
          echo "<RTSTATUS>".$status."</RTSTATUS>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<RTSTATUS></RTSTATUS>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getallmoves
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getallmoves":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $oR3DCQuery->GetAllMovesForMobile($_GET['gameid']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_GAMEID</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getnewmove
    // Params: action, sid, gameid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getnewmove":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if($_GET['gameid'] != ""){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $oR3DCQuery->GetNewMoveForMobile($_GET['gameid']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_GAMEID</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;
	  
	case "get_game_update_on_state_change":
	
		echo "<RESPONSE>\n";
		if(isSessionIDValid($config, $xsid))
			mobile_get_game_update_on_state_change($xsid);
		else
			echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
		echo "</RESPONSE>\n";
		
		break;
	  
	  
    ////////////////////////////////////////////////////////////////////////////////////////////
    // addtobuddylist
    // Params: action, sid, buddyid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "addtobuddylist":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if(is_numeric($_GET['buddyid'])){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $bBuddyExists = $oR3DCQuery->DoesPlayerIDExists((int)$_GET['buddyid']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          if($bBuddyExists){

            //Instantiate the CBuddyList Class
            $oBuddyList = new CBuddyList($config);
            $oBuddyList->AddBuddyToList($config, $player_id, (int)$_GET['buddyid']);
            $oBuddyList->Close();
            unset($oBuddyList);

            echo "<RESPONSE>\n";
            echo "<BUDDYLIST>true</BUDDYLIST>\n";
			echo "<BUDDYID>" . (int)$_GET['buddyid'] . "</BUDDYID>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ERROR>ID_INVALID_BUDDYID</ERROR>\n";
			echo "<BUDDYID>" . $_GET['buddyid'] . "</BUDDYID>\n";
            echo "</RESPONSE>\n";

          }

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_BUDDYID</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;
    
    ////////////////////////////////////////////////////////////////////////////////////////////
    // getbuddylist
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getbuddylist":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate the CBuddyList Class
        $oBuddyList = new CBuddyList($config);

        echo "<RESPONSE>\n";
        $oBuddyList->GetBuddyListForMobile($player_id);
        echo "</RESPONSE>\n";

        $oBuddyList->Close();
        unset($oBuddyList);
  
      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // deletefrombuddylist
    // Params: action, sid, buddyid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "deletefrombuddylist":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if(is_numeric($_GET['buddyid'])){
      
          //Instantiate the CBuddyList Class
          $oBuddyList = new CBuddyList($config);
          $oBuddyList->DeleteBuddyFromBuddyList($config, $player_id, (int)$_GET['buddyid']);
          $oBuddyList->Close();
          unset($oBuddyList);

          echo "<RESPONSE>\n";
          echo "<BUDDYLIST>true</BUDDYLIST>\n";
		  echo "<BUDDYID>" . (int)$_GET['buddyid'] . "</BUDDYID>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_BUDDYID</ERROR>\n";
		  echo "<BUDDYID>" . $_GET['buddyid'] . "</BUDDYID>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // getplayeravatar
    // Params: action, sid, playerid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "getplayeravatar":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        if(is_numeric($_GET['playerid'])){

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          echo "<RESPONSE>\n";
          echo "<AVATAR>".$oR3DCQuery->GetPlayerAvatarImageURL($_GET['playerid'])."</AVATAR>\n";
          echo "</RESPONSE>\n";

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>ID_INVALID_PLAYERID</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;



    ////////////////////////////////////////////////////////////////////////////////////////////
    // VERSION
    // Params: action
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "version":

      //Instantiate theCR3DCQuery Class
      $oR3DCQuery = new CR3DCQuery($config);

      echo "<RESPONSE>\n";

      echo "<SERVERTYPE>";
      echo $oR3DCQuery->GetServerTypeName();
      echo "</SERVERTYPE>";

      echo "<VERSION>";
      echo $oR3DCQuery->GetServerVersion();
      echo "</VERSION>\n";
      echo "</RESPONSE>\n";

      $oR3DCQuery->Close();
      unset($oR3DCQuery);

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // deletemsg
    // Params: action, sid, messageid, all
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "deletemsg":

      if(isSessionIDValid($config, $xsid)){

        if(is_numeric($_GET['messageid']) && is_numeric($_GET['all'])){

          $nMsgID = $_GET['messageid'];
          $nDelAll = $_GET['all'];

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);
  
          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);

          if($nDelAll == 0){

            $oR3DCQuery->DeleteMessageFromInboxForMobile($config, $player_id, $nMsgID);

            echo "<RESPONSE>\n";
            echo "<DELETE>true</DELETE>\n";
            echo "</RESPONSE>\n";

          }elseif($nDelAll == 1){

            $oR3DCQuery->DeleteMessageFromInboxForMobile($config, $player_id, 0);

            echo "<RESPONSE>\n";
            echo "<DELETE>true</DELETE>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ERROR>IDS_INVALID_PARAM_PASSED</ERROR>\n";
            echo "</RESPONSE>\n";

          }
      
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_INVALID_PARAM_PASSED</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // closeaccount
    // Params: action, sid, user, pass
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "closeaccount":

      if(isSessionIDValid($config, $xsid)){

        if($_GET['user'] != "" && $_GET['pass'] != ""){

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          $bAuth = $oR3DCQuery->CheckLoginCredentialsForMobile($ConfigFile, $_GET['user'], $_GET['pass']);
          $oR3DCQuery->Close();
          unset($oR3DCQuery);

          if($bAuth){

            // Instantiate the CAdmin Class
            $oAdmin = new CAdmin($config);
            $oAdmin->DisablePlayer($player_id);
            $oAdmin->Close();
            unset($oAdmin);

            //Instantiate the CChess Class
            $oChess = new CChess($config);
            $sid = $oChess->delete_session($config, $xsid);
            unset($oChess);

            echo "<RESPONSE>\n";
            echo "<CLOSED>true</CLOSED>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ERROR>IDS_INVALID_PASS_OR_USER</ERROR>\n";
            echo "</RESPONSE>\n";

          }

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_INVALID_PARAM_PASSED</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // createclub
    // Params: action, sid, clubname
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "createclub":

      if(isSessionIDValid($config, $xsid)){

        if(trim($_GET['clubname']) != ""){

          $strClubName = trim($_GET['clubname']);

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          
          if(!$oR3DCQuery->IsUserInClub($player_id)){

            $oR3DCQuery->CreateChessClub($strClubName, $player_id);

            echo "<RESPONSE>\n";
            echo "<CLUBCREATED>true</CLUBCREATED>\n";
            echo "</RESPONSE>\n";

          }else{

            echo "<RESPONSE>\n";
            echo "<ERROR>IDS_USER_ALREADY_IN_CLUB</ERROR>\n";
            echo "</RESPONSE>\n";

          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_INVALID_PARAM_PASSED</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // joinclub
    // Params: action, sid, clubid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "joinclub":

      if(isSessionIDValid($config, $xsid)){

        if(is_numeric($_GET['clubid']) != ""){

          $nClubID = $_GET['clubid'];

          $session = base64_decode($xsid);
          list($uniq, $player_id) = preg_split("/\|/", $session);

          //Instantiate theCR3DCQuery Class
          $oR3DCQuery = new CR3DCQuery($config);
          
          if($oR3DCQuery->CheckChessClubExistsByID($nClubID)){

            if(!$oR3DCQuery->IsUserInClub($player_id)){

              $oR3DCQuery->JoinChessClub($nClubID, $player_id);

              echo "<RESPONSE>\n";
              echo "<CLUBJOINED>true</CLUBJOINED>\n";
              echo "</RESPONSE>\n";

            }else{

              echo "<RESPONSE>\n";
              echo "<ERROR>IDS_USER_ALREADY_IN_CLUB</ERROR>\n";
              echo "</RESPONSE>\n";

            }

          }else{

            echo "<RESPONSE>\n";
            echo "<ERROR>IDS_CLUB_DOES_NOT_EXIST</ERROR>\n";
            echo "</RESPONSE>\n";

          }

          $oR3DCQuery->Close();
          unset($oR3DCQuery);

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_INVALID_PARAM_PASSED</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // leaveclub
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "leaveclub":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
          
        if($oR3DCQuery->IsUserInClub($player_id)){

          $oR3DCQuery->LeaveClub($player_id);

          echo "<RESPONSE>\n";
          echo "<LEAVECLUB>true</LEAVECLUB>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_USER_NOT_IN_CLUB</ERROR>\n";
          echo "</RESPONSE>\n";

        }

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // clubmembers
    // Params: action, sid
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "clubmembers":

      if(isSessionIDValid($config, $xsid)){

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $oR3DCQuery->GetClubMemberlistForMobile($player_id);
        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

    ////////////////////////////////////////////////////////////////////////////////////////////
    // uploadclubpage
    // Params: action, sid, html
    ////////////////////////////////////////////////////////////////////////////////////////////
    case "uploadclubpage":

      if(isSessionIDValid($config, $xsid)){

        $HTML = $_GET['html'];

        $session = base64_decode($xsid);
        list($uniq, $player_id) = preg_split("/\|/", $session);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
          
        if($oR3DCQuery->IsUserClubLeader($player_id)){

          if(trim($HTML) != ""){
            $HTML = base64_decode($HTML);
            $HTML = addslashes($HTML);
          }else{
            $HTML = "";
          }

          $oR3DCQuery->UpdateClubPageHTML($player_id, $HTML);

          echo "<RESPONSE>\n";
          echo "<CLUBPAGEUPDATE>true</CLUBPAGEUPDATE>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_USER_NOT_CLUB_LEADER</ERROR>\n";
          echo "</RESPONSE>\n";

        }

        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

      }

      break;

	// Returns the PGN, taken pieces lists and current game state.
	case "get_full_game_update":
	    echo "<RESPONSE>\n";
		if(isSessionIDValid($config, $xsid))
			mobile_get_full_game_update();
		else
			echo "<ERROR>IDS_SESSION_ID_INVALID</ERROR>\n";
        echo "</RESPONSE>\n";

	  
  }

  echo "</SERVER>\n";

  
function mobile_move($config)
{
	if($_GET['gameid'] != "" && $_GET['from'] != "" && $_GET['to'] != "")
	{
		//Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

		$xsid = $_GET['sid'];
		$session = base64_decode($xsid);
		list($uniq, $player_id) = preg_split("/\|/", $session);

		$movefrom = $_GET['from'];
		$moveto = $_GET['to'];

		$bmove_error = false;

		//Create Move String
		$movestr = $movefrom.",".$moveto;
		$movestr2 = $movefrom."-".$moveto;

		// get the fen for the game
		$fen = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
		//$fen3 = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
		$bturn = $oR3DCQuery->IsPlayersTurn($config, $player_id, $_GET['gameid']);

		if($bturn)
		{
			//check to see if the move is valid
			if(is_Move_legal($fen, $movestr2))
			{
				$oR3DCQuery->CurrentGameMovePiece($config, $_GET['gameid'], $xsid, $player_id, $movestr);

				$initiator = "";
				$w_player_id = "";
				$b_player_id = "";
				$status = "";
				$completion_status = "";
				$start_time = "";
				$next_move = "";

				$oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move); 

				//checkmate
				if(get_GameState() == 1)
				{
					//if($w_player_id == $player_id){
					if($next_move == 'w')
					{
						///////////////////////////////////////////////////////////////////////
						//ELO Point Calculation
						if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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
						$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
					}
					else
					{
						///////////////////////////////////////////////////////////////////////
						//ELO Point Calculation
						if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid']))
						{
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
						$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
					}

					$oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
					$oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);
				}

				//draw
				else if(get_GameState() == 2)
				{

					///////////////////////////////////////////////////////////////////////
					//ELO Point Calculation
					if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid']))
					{
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
					$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");
					$bmove_error = false;

					$oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
					$oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);
				}
			}
			else
			{
				$bmove_error = true;
			}

		}
		else
		{
			$bmove_error = true;
		}

		// If a draw offer was made, cancel it.
		$isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
        $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
		if($isdraw != 'IDS_DRAW' && $isdraw != 'IDS_NO_DRAW')
		{
			$oR3DCQuery->RevokeDrawGame($config, $_GET['gameid'], $player_id);
		}
		
		$oR3DCQuery->Close();
		unset($oR3DCQuery);
	}
	else
	{
		$bmove_error = true;
	}

	if($bmove_error)
	{
		echo "<RESPONSE>\n";
		echo "<MOVE>false</MOVE>\n";
		echo "</RESPONSE>\n";
	}
	else
	{
		echo "<RESPONSE>\n";
		echo "<MOVE>true</MOVE>\n";
		mobile_get_full_game_update();
		$move = ChessHelper::get_last_move();
		echo "<MOVE_SAN>" . $move['SAN'] . "</MOVE_SAN>\n";
		echo "<MOVE_FROM>" . $move['from'] . "</MOVE_FROM>\n";
		echo "<MOVE_TO>" . $move['to'] . "</MOVE_TO>\n";
		echo "</RESPONSE>\n";
	}

} 

function mobile_movehmm($config)
{
	if($_GET['gameid'] != "" && $_GET['from'] != "" && $_GET['to'] != "")
	{
		//Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);

		$xsid = $_GET['sid'];
		$session = base64_decode($xsid);
		list($uniq, $player_id) = preg_split("/\|/", $session);

		$movefrom = $_GET['from'];
		$moveto = $_GET['to'];

		$bmove_error = false;

		//Create Move String
		$movestr = $movefrom.",".$moveto;
		$movestr2 = $movefrom."-".$moveto;

		// get the fen for the game
		$fen = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
		//$fen3 = $oR3DCQuery->GetHackedFEN($xsid, $_GET['gameid']);
		$bturn = $oR3DCQuery->IsPlayersTurn($config, $player_id, $_GET['gameid']);
		echo "FEN: $fen<br/>";
		ChessHelper::load_chess_game($_GET['gameid']);
		$turn = 
		$fen = ChessHelper::$CB->GetFENForCurrentPosition();
		echo "FEN: $fen<br/>";

		if($bturn)
		{
			//check to see if the move is valid
			if(is_Move_legal($fen, $movestr2))
			{
				$oR3DCQuery->CurrentGameMovePiece($config, $_GET['gameid'], $xsid, $player_id, $movestr);

				$initiator = "";
				$w_player_id = "";
				$b_player_id = "";
				$status = "";
				$completion_status = "";
				$start_time = "";
				$next_move = "";

				$oR3DCQuery->GetGameInfoByRef($config, $_GET['gameid'], $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move); 

				//checkmate
				if(get_GameState() == 1)
				{
					//if($w_player_id == $player_id){
					if($next_move == 'w')
					{
						///////////////////////////////////////////////////////////////////////
						//ELO Point Calculation
						if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid'])){
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
						$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "B");
					}
					else
					{
						///////////////////////////////////////////////////////////////////////
						//ELO Point Calculation
						if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid']))
						{
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
						$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "W");
					}

					$oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
					$oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);
				}

				//draw
				else if(get_GameState() == 2)
				{

					///////////////////////////////////////////////////////////////////////
					//ELO Point Calculation
					if($oR3DCQuery->ELOIsActive() && $oR3DCQuery->IsGameRated($_GET['gameid']))
					{
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
					$oR3DCQuery->UpdateGameStatus($config, $_GET['gameid'], "C", "D");
					$bmove_error = false;

					$oR3DCQuery->CachePlayerPointsByPlayerID($b_player_id);
					$oR3DCQuery->CachePlayerPointsByPlayerID($w_player_id);
				}
			}
			else
			{
				$bmove_error = true;
			}

		}
		else
		{
			$bmove_error = true;
		}


		$oR3DCQuery->Close();
		unset($oR3DCQuery);
	}
	else
	{
		$bmove_error = true;
	}

	if($bmove_error)
	{
		echo "<RESPONSE>\n";
		echo "<MOVE>false</MOVE>\n";
		echo "</RESPONSE>\n";
	}
	else
	{
		echo "<RESPONSE>\n";
		echo "<MOVE>true</MOVE>\n";
		mobile_get_full_game_update();
		echo "<LASTMOVE>" . ChessHelper::get_last_move() . "</LASTMOVE>";
		echo "</RESPONSE>\n";
	}

} 
 

  
function mobile_get_pgn()
{
	//$CB = new ChessBoard2();
	echo '<PGN>';
	ChessHelper::load_chess_game($_GET['gameid']);
	echo ChessHelper::get_game_pgn();
	echo '</PGN>';
}

function mobile_get_full_game_update()
{
	if($_GET['gameid'] != "")
	{
		$config = $Root_Path."bin/config.php";
		$session = base64_decode($_GET['sid']);
		list($uniq, $player_id) = preg_split("/\|/", $session);
		$oR3DCQuery = new CR3DCQuery($config);
		ChessHelper::load_chess_game($_GET['gameid']);
		echo "<PGN>";
		echo ChessHelper::get_game_pgn();
		echo "</PGN>\n";
		$pieces = ChessHelper::get_captured_pieces();
		echo "<CAPTURED_BY_WHITE>" . join(', ', $pieces['white']) . "</CAPTURED_BY_WHITE>\n";
		echo "<CAPTURED_BY_BLACK>" . join(', ', $pieces['black']) . "</CAPTURED_BY_BLACK>\n";
		echo "<GAME_STATE>";
		echo ChessHelper::get_game_state();
		echo "</GAME_STATE>\n";
		echo "<GAME_RESULT>";
		echo ChessHelper::get_game_result();
		echo "</GAME_RESULT>\n";
		$isblack = $oR3DCQuery->IsPlayerBlack($config, $_GET['gameid'], $player_id);
        $isdraw = $oR3DCQuery->IsRequestDraw($config, $_GET['gameid'], $isblack);
		echo "<DRAWCODE>";
		echo $isdraw;
		echo "</DRAWCODE>\n";
		$timeinfo = ChessHelper::get_timing_info();
		$str = "<TIME_STARTED>" . $timeinfo['started'] . "</TIME_STARTED>";
		$str .= "<TIME_TYPE>" . $timeinfo['type'] . "</TIME_TYPE>";
		$str .= '<TIME_MODE>' . $timeinfo['mode'] . '</TIME_MODE>';
		if($timeinfo['mode'] == 1)
		{
			$str .= '<TIME_W_LEFT>' . $timeinfo['w_time_left'] . '</TIME_W_LEFT>';
			$str .= '<TIME_B_LEFT>' . $timeinfo['b_time_left'] . '</TIME_B_LEFT>';
			$str .= '<TIME_W_ALLOWED>' . $timeinfo['w_time_allowed'] . '</TIME_W_ALLOWED>';
			$str .= '<TIME_B_ALLOWED>' . $timeinfo['b_time_allowed'] . '</TIME_B_ALLOWED>';
		}
		else
		{
			$str .= "<TIME_DURATION>" . $timeinfo['duration'] . "</TIME_DURATION>";
			$str .= '<TIME_W_ALLOWED>' . $timeinfo['duration'] . '</TIME_W_ALLOWED>';
			$str .= '<TIME_B_ALLOWED>' . $timeinfo['duration'] . '</TIME_B_ALLOWED>';
		}
		echo $str;
	}
	else
	{
		echo "<ERROR>IDS_GAME_ID_INVALID</ERROR>\n";
	}
}

function mobile_get_game_update_on_state_change($xsid)
{
	//$session = base64_decode($xsid);
	//list($uniq, $player_id) = preg_split("/\|/", $session);
	if($_GET['gameid'] != "")
	{
		$game_id = $_GET['gameid'];
		try{
			$dbh = CSession::$dbh;
			
			$side_to_move = $_GET['side_to_move'];
			$get_game_over = (bool)$_GET['get_game_over'];
			$get_new_move = (bool)$_GET['get_new_move'];
			$with_full_update = (bool)$_GET['with_full_update'];
			$require_full_update = FALSE;
			$new_move = FALSE;
			
			// Work out whose turn it is.
			$player_w = -1; $player_b = -1; $next_move = '';
			$stmt = $dbh->prepare("SELECT `w_player_id`,`b_player_id`,`next_move` FROM `game` WHERE `game_id` = ?");
			$stmt->bind_param('s', $game_id);
			if($stmt->execute())
			{
				$stmt->bind_result($player_w, $player_b, $next_move);
				$result = $stmt->fetch();
				if($result)
				{
					if($next_move == NULL) $next_move = 'w';	// Game creation does not initially set a next move value. Assume white to move as custom game setup isn't yet implemented.
				}
				$stmt->close();
			}
			else
			{
				echo "<ERROR>Database Error</ERROR>\n";
				return false;
			}
			
			if($get_new_move)
			{
			//echo "next $next_move , side $side_to_move";
				if($next_move != $side_to_move)
				{
					echo "<NEW_MOVE>true</NEW_MOVE>\n";
					$new_move = TRUE;
					$require_full_update = TRUE;
				}
				else
					echo "<NEW_MOVE>false</NEW_MOVE>\n";
			}
			
			if($get_game_over)
			{
				// See if the game is over (because the opponent resigned, there was a draw or a player won).
				$game_result = 0;
				$stmt = $dbh->prepare("SELECT `completion_status` FROM `game` WHERE `game_id` = ?");
				$stmt->bind_param('s', $game_id);
				if($stmt->execute())
				{
					$stmt->bind_result($status);
					$result = $stmt->fetch();
					if($result)
					{
						if($status == "W")
							$game_result = 1;
						elseif($status == "B")
							$game_result = 2;
						elseif($status == "D")
							$game_result = 3;
					}
					$stmt->close();
				}
				else
				{
					echo "<ERROR>Database Error</ERROR>\n";
					return false;
				}
				
				if($game_result != 0)
				{
					echo "<GAME_OVER>true</GAME_OVER>\n";
					$require_full_update = TRUE;
				}
				else
					echo "<GAME_OVER>false</GAME_OVER>\n";
			}
			
			if($require_full_update && $with_full_update)	// There might be cases where we only want to know if a move was made or the game is over without the details of the game state.
				mobile_get_full_game_update();
			
			if($new_move)
			{
				$move = ChessHelper::get_last_move();
				echo "<MOVE_SAN>" . $move['SAN'] . "</MOVE_SAN>\n";
				echo "<MOVE_FROM>" . $move['from'] . "</MOVE_FROM>\n";
				echo "<MOVE_TO>" . $move['to'] . "</MOVE_TO>\n";
			}
			
			// Return the draw status.
			$session = base64_decode($xsid);
			list($uniq, $player_id) = preg_split("/\|/", $session);
			$oR3DCQuery = new CR3DCQuery($Root_Path."bin/config.php");
			$isblack = $oR3DCQuery->IsPlayerBlack($Root_Path."bin/config.php", $game_id, $player_id);
			$isdraw = $oR3DCQuery->IsRequestDraw($Root_Path."bin/config.php", $game_id, $isblack);
			echo "<DRAWCODE>";
			echo $isdraw;
			echo "</DRAWCODE>\n";
		}
		catch(mysqli_sql_exception $e)
		{
			echo "<ERROR>Database Connection Error</ERROR>\n";
			return false;
		}
	}
	else
	{
		echo "<ERROR>IDS_GAME_ID_INVALID</ERROR>\n";
	}
}


// Returns a list of captured pieces by side, for a given game.
// gameid: the id of the game
function mobile_get_captured_pieces($config)
{
	if(empty($_GET['gameid']))
	{
		echo "<ERROR>IDS_GAME_ID_INVALID</ERROR>\n";
	}
	
	$game_id = $_GET['gameid'];
	$CB = new ChessBoard2();
	mobile_load_chess_game($game_id, $CB, $config);
	$moves = $CB->GetMoveList();
	$captured_white = array();
	$captured_black = array();
	$map = array(1 => 'K', 2 => 'Q', 3 => 'B', 4 => 'N', 5 => 'R', 6 => 'P');
	foreach($moves as $move)
	{
		if($move->moveType == MOVE_TYPE::CAPTURED || $move->moveType == MOVE_TYPE::ENPASSANT){
			//echo "Side: $move->nSideMoved Move: $move->szSAN Taken Piece Type: $move->takenPieceType ->  ";
			if($move->nSideMoved == PLAYER_SIDE::WHITE)
				$captured_white[] = $map[$move->takenPieceType];
			else
				$captured_black[] = $map[$move->takenPieceType];
		}
	}
	echo "<CAPTURED_BY_WHITE>" . join(', ', $captured_white) . "</CAPTURED_BY_WHITE>\n";
	echo "<CAPTURED_BY_BLACK>" . join(', ', $captured_black) . "</CAPTURED_BY_BLACK>\n";
}

// Outputs game information on recently played games for the specified player.
function mobile_get_recent_games($player_id, $oR3DCQuery)
{
	try{
		$dbh = CSession::$dbh;
		
		// Select the player's games which have been completed. Games are ordered by the most recent move made.
		$query = <<<qq
SELECT
	`game`.* 
FROM (
	SELECT 
		MAX(`time`) as 'lastmovetime', `game_id` as 'gameid'
	FROM 
		`move_history`
	GROUP BY 
		`game_id`
	) as subq, `game`
WHERE
	`game_id` = `gameid` AND 
	(`w_player_id` = ? OR `b_player_id` = ?) AND 
	(`completion_status` <> "A" AND `completion_status` <> "I")
ORDER BY
	`lastmovetime` DESC
LIMIT
	5
qq;
		$stmt = $dbh->prepare($query);
		$stmt->bind_param('ii', $player_id, $player_id);
		if($stmt->execute())
		{
			$results = get_results($stmt);
			foreach($results as $result)
			{
				$next_move = $result['next_move'];
				$completion_status = $result['completion_status'];
				$game_id = $result['game_id'];
				$start_time = $result['start_time'];
				$w_player_id = $result['w_player_id'];
				$b_player_id = $result['b_player_id'];
				$initiator = $result['initiator'];
				
				echo "<GAMES>\n";

				echo "<STATUS>C";
				echo "</STATUS>\n";

				echo "<COMPLETIONSTATUS>";
				echo $completion_status;
				echo "</COMPLETIONSTATUS>\n";

				$gametypecode = $oR3DCQuery->GetGameTypeCode($game_id);
				$strGameType = "GT_NORMAL_GAME";

				switch($gametypecode)
				{
					case 2:
						$strGameType = "GT_PASV_RT_GAME";
						break;
					case 3:
						$strGameType = "GT_ACTIVE_RT_GAME";
						break;
				}

				echo "<GAMETYPE>";
				echo $strGameType;
				echo "</GAMETYPE>\n";

				$oR3DCQuery->GetGamePlayTypeInfoForMobile($game_id);
				$oR3DCQuery->TimedGameStatsForMobile($game_id);

				echo "<TIMECREATED>";
				echo $start_time;
				echo "</TIMECREATED>\n";

				echo "<DESCRIPTION>";

				echo $oR3DCQuery->GetUserIDByPlayerID($ConfigFile, $w_player_id);
				echo " VS ";
				echo $oR3DCQuery->GetUserIDByPlayerID($ConfigFile, $b_player_id);

				echo "</DESCRIPTION>\n";

				echo "<INITIATOR>";
				echo $initiator;
				echo "</INITIATOR>\n";

				echo "<WHITE>";
				echo $w_player_id;
				echo "</WHITE>\n";

				echo "<BLACK>";
				echo $b_player_id;
				echo "</BLACK>\n";

				echo "<NEXTMOVE>";
				echo $next_move;
				echo "</NEXTMOVE>\n";

				echo "<GAMEID>";
				echo $game_id;
				echo "</GAMEID>\n";

				echo "<GAMEFEN>";
				echo $oR3DCQuery->GetFEN3('', $game_id);
				echo "</GAMEFEN>\n";

				echo "</GAMES>\n";
			}
		}
	}
	catch(mysqli_sql_exception $e)
	{
		echo "<ERROR>Database Error</ERROR>\n";
	}
}


function get_results($stmt)
{
	$meta = $stmt->result_metadata();
    while ($field = $meta->fetch_field())
    {
        $params[] = &$row[$field->name];
    }

    call_user_func_array(array($stmt, 'bind_result'), $params);

    while ($stmt->fetch()) {
        foreach($row as $key => $val)
        {
            $c[$key] = $val;
        }
        $result[] = $c;
    }
   
    $stmt->close();
	return $result;
}

?>
