<?php
////////////////////////////////////////////////////////////////////////////////
//
// (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
// All rights reserved. Please observe respective copyrights.
// phpChess - Chess at its best
// you can find us at http://www.phpchess.com. 
//
////////////////////////////////////////////////////////////////////////////////

// ChangeLog
// 2009-06-21  Markus
// 
// Fixed a bug where the king letter was not prepended to the pgn move string in
// get_move_history_list and get_mote_history_list2
//

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

// Class Includes
@include_once('CR3DCQuery.php');

class CChess{

  /////////////////////////////////////////////////////////////////////////////
  //Define properties
  /////////////////////////////////////////////////////////////////////////////
  var $ChessCFGFileLocation;
  var $SkinsLocation;

  /////////////////////////////////////////////////////////////////////////////
  //Define methods
  /////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CChess (Constructor)
  *
  */
  function __construct($config){

    ////////////////////////////////////////////////////////////////////////////
    // Sets the chess config file location (absolute location on the server)
    ////////////////////////////////////////////////////////////////////////////
    $this->ChessCFGFileLocation = $config;

  }

  static function mysqli_result($result, $number, $field=0) {
      mysqli_data_seek($result, $number);
      $row = mysqli_fetch_array($result);
      return $row[$field];
  }
    
  /**********************************************************************
  * GetStringFromStringTable
  *
  */
  function GetStringFromStringTable($strTag, $config){

    include($config);

    // Get Server Language
    $LanguageFile = "";

    if(isset($_SESSION['language'])){
 
      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$_SESSION['language'];
      }

    }else{

      $host = $conf['database_host'];
      $dbnm = $conf['database_name'];
      $user = $conf['database_login'];
      $pass = $conf['database_pass'];

      $link = mysqli_connect($host, $user, $pass);
      mysqli_select_db($link,$dbnm);

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysqli_query($link,$query) or die(mysqli_error($link));
      $num = mysqli_num_rows($return);

      if($num != 0){

        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".CChess::mysqli_result($return, 0, "o_languagefile");

      }

    }

    $text = "Error";

    if($LanguageFile != ""){

      // Open the language file an get the contents
      $lines = file($LanguageFile);
   
      // Search for the key
      for($x=1; $x<=sizeof($lines); $x++){
        //echo "Line $x: " . $lines[$x-1] . "<br>";

        if (preg_match("/\b".$strTag."\b/i", $lines[$x-1])){
          // We found the key
    
          list($Key, $strText, $junk) = preg_split("/\|\|/", $lines[$x-1], 3);

          $text = trim($strText);

          // Exit loop
          break;

        }

      }

    }

    //Parse tags

    $aTags = array("['avatar_image_width']", "['avatar_image_height']", "['user_name']");
    $aReplace = array($conf['avatar_image_width'], $conf['avatar_image_height'], $_SESSION['user']);
    $text = str_replace($aTags, $aReplace, $text);

    return $text;
  
  }


  /**********************************************************************
  * request_FEN
  * grabs a FEN string for a requested game id
  * Params: $game_id
  */
  function request_FEN($ConfigFile, $game_id){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "SELECT * FROM move_history WHERE game_id='".$game_id."' ORDER BY time ASC";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    return $this->process_history_result($return, $game_id);

  }


  /**********************************************************************
  * request_FEN2
  * grabs a FEN string for a requested game id and move time
  * Params: $game_id, $time
  */
  function request_FEN2($ConfigFile, $game_id, $time){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "SELECT * FROM move_history WHERE game_id='".$game_id."' AND time <='".$time."' ORDER BY time ASC";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    return $this->process_history_result($return, $game_id);

  }


  /**********************************************************************
  * check_move
  * validates a move, given the move and a game_id
  * Params: 
  */
  function check_move($move){

    return true;

  }


  /**********************************************************************
  * process_move
  * updates the move_history table
  * Params: $player_id, $gid, $move
  */
  function process_move($ConfigFile, $player_id, $gid, $move){

    if($gid == "" || $move == ""){
        return "F".$this->zero_pad($player_id,8).$move;
    }
// TODO, do timeout check for this game.
    $move_stat="F";
    $next_color="b";
    $other_player;

    if($this->check_move($move)){

      //include config file
      include($ConfigFile);

      // connect to mysql and open database
      $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
      @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

      $sti = "SELECT w_player_id, b_player_id, next_move, w_time_used, b_time_used, start_time FROM game WHERE game_id='".$gid."'";
      $stireturn = mysqli_query($db_my,$sti) or die(mysqli_error($db_my));
      $stinum = mysqli_num_rows($stireturn);

      if($stinum != 0)
	  {

        if($player_id == CChess::mysqli_result($stireturn,0,"w_player_id")){
          $next_color="b";
          $other_player=CChess::mysqli_result($stireturn,0,"b_player_id");
        }else{
          $next_color="w";
          $other_player=CChess::mysqli_result($stireturn,0,"w_player_id");
        }
		$w_time_used = (int)CChess::mysqli_result($stireturn, 0, 'w_time_used');
		$b_time_used = (int)CChess::mysqli_result($stireturn, 0, 'b_time_used');
		$start_time = (int)CChess::mysqli_result($stireturn, 0, 'start_time');

        //castling
		if($move2 = checkCastling($move, $gid, $ConfigFile)){
		  $st = "INSERT INTO move_history(game_id,player_id,move,time) VALUES('".$gid."',".$player_id.",'".$move2."',".time().")";
			  mysqli_query($db_my,$st) or die(mysqli_error($db_my)); 
		}elseif($move2 = checkPromotion($move)){
		  $st = "INSERT INTO move_history(game_id,player_id,move,time) VALUES('".$gid."',".$player_id.",'".$move2."',".time().")";
			  mysqli_query($db_my,$st) or die(mysqli_error($db_my));		
		}elseif($move2 = checkEnpassent($move)){			
		  $st = "INSERT INTO move_history(game_id,player_id,move,time) VALUES('".$gid."',".$player_id.",'".$move2."',".time().")";
			  mysqli_query($db_my,$st) or die(mysqli_error($db_my));
		}else{
		  $st = "INSERT INTO move_history(game_id,player_id,move,time) VALUES('".$gid."',".$player_id.",'".$move."',".time().")";
			  mysqli_query($db_my,$st) or die(mysqli_error($db_my));    
		}
		
		// Get the game timing mode in use, along with any time controls
		//$query = "SELECT * FROM cfm_game_options WHERE o_gameid='" . $gid . "'";
		$query = <<<qq
SELECT cfm_game_options.*, timed_games.moves1, timed_games.time1, timed_games.moves2, timed_games.time2
FROM cfm_game_options
LEFT JOIN timed_games ON cfm_game_options.o_gameid = timed_games.id
WHERE o_gameid = '$gid'
qq;
		$return = mysqli_query($db_my,$query) or die(mysqli_error($db_my));
		$num = mysqli_num_rows($return);
		
		$timing_mode = (int)CChess::mysqli_result($return, 0, "time_mode");
		$m1 = (int)@CChess::mysqli_result($return, $i, 'moves1');
		$m2 = (int)@CChess::mysqli_result($return, $i, 'moves2');
		$t1 = (int)@CChess::mysqli_result($return, $i, 'time1') * 60;
		$t2 = (int)@CChess::mysqli_result($return, $i, 'time2') * 60;
		
		//$timing_type = CChess::mysqli_result($return, 0, "o_timetype");
		$game_update = array();
		if($timing_mode == 1)	// Time recorded for both players.
		{
			$now = time();
			//$timetype = substr(trim(strtolower($timing_type)), 2);
			
			// If time controls are used, get the number of moves to work out which time control
			// applies. If a time control has been reached, remove the required time to the player's
			// 'used' time.
			if($m1)
			{
				$query = "SELECT count(*) as `count` FROM move_history WHERE game_id = '$gid' AND player_id = $player_id";
				$return = mysqli_query($db_my,$query) or die(mysqli_error($db_my));
				$move_cnt = CChess::mysqli_result($return, 0, 'count');
				if($move_cnt == $m1)	// Reached first time control
				{
					if($next_color == 'b') $w_time_used -= $t1;
					elseif($next_color == 'w') $b_time_used -= $t1;
				}
				elseif($move_cnt > $m1)		// Check if a 2nd time control was reached
				{
					if(($move_cnt - $m1) % $m2 == 0)	// 2nd time control can be applied many times.
					{
						if($next_color == 'b') $w_time_used -= $t2;
						elseif($next_color == 'w') $b_time_used -= $t2;
					}
				}
			}
			
			// Get the 2nd last move's time. Subtract the move's time from the current time to work
			// out how long it took the player to make this move. The first move made doesn't
			// attract any time usage.
			$query = "SELECT `time` FROM move_history WHERE game_id = '$gid' ORDER BY `time` DESC LIMIT 1,1";
			$return = mysqli_query($db_my,$query) or die(mysqli_error($db_my));
			$num = mysqli_num_rows($return);
			if($num != 0)
				$last_move_time = (int)trim(CChess::mysqli_result($return, 0, "time"));
			else			// For first move, update the game start time to be 'now'.
			{
				$last_move_time = $now;
				$game_update[] = "start_time = $now";
			}
			$diff = $now - $last_move_time;
			if($next_color == 'w')
				$game_update[] = "b_time_used=" . ($b_time_used + $diff);
			else
				$game_update[] = "w_time_used=" . ($w_time_used + $diff);
		}
		
		$game_update[] = "next_move='$next_color'";
		$game_update = implode(', ', $game_update);
		
        $st = "UPDATE game SET $game_update WHERE game_id='".$gid."'";
		//echo "run $st";
		//exit();
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));    

        $st = "INSERT INTO message_queue(player_id, message, posted) VALUES(".$other_player.",'".$this->add_header("M",$move_stat.$this->zero_pad($player_id,8).$gid.$move,"0")."',".time().")";
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));

        //////////////////////////////////////////////
        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($this->ChessCFGFileLocation);

        $isblack = $oR3DCQuery->IsPlayerBlack($this->ChessCFGFileLocation, $gid, $other_player);
        $isrealtime = $oR3DCQuery->IsRequestRealTime($this->ChessCFGFileLocation, $gid, $isblack);

        if($oR3DCQuery->MoveNotification($other_player) == true && $isrealtime != "IDS_REAL_TIME"){

          $requestorname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $player_id);
          $otherguysname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $other_player);

          $otheremail = $oR3DCQuery->GetEmailByPlayerID($this->ChessCFGFileLocation, $other_player);

          $subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_17", $ConfigFile);

          $aTags1 = array("['otherguysname']", "['requestorname']", "['gid']", "['move']", "['siteurl']", "['sitename']");
          $aReplaceTags1 = array($otherguysname, $requestorname, $gid, $move, $this->TrimRSlash($conf['site_url']), $conf['site_name']);

          $bodyp1 = str_replace($aTags1, $aReplaceTags1, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_18", $ConfigFile));

          $this->SendEmail($otheremail, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
        }

        unset($oR3DCQuery);
        //////////////////////////////////////////////

        //////////////////////////////////////////////
        //Check if the king was killed
        //////////////////////////////////////////////

        $FEN = $this->request_FEN($this->ChessCFGFileLocation,$gid);
        $Moves = "";
        $RestOfSentence = "";

        list($Moves, $RestOfSentence) = preg_split("/ /", $FEN);
        $nwhitek = strpos($Moves, 'k');

        if($nwhitek === false){

          $st = "UPDATE game SET status='C', completion_status='B' WHERE game_id='".$gid."'";
          mysqli_query($db_my,$st) or die(mysqli_error($db_my));

        }

        $nblackk = strpos($Moves, 'K');

        if($nblackk === false){

          $st = "UPDATE game SET status='C', completion_status='W' WHERE game_id='".$gid."'";
          mysqli_query($db_my,$st) or die(mysqli_error($db_my));

        }	

        //////////////////////////////////////////////   

        $move_stat="S";

      }

    }
	
    return $move_stat.$this->zero_pad($player_id,8).$gid.$move;

  }


  /**********************************************************************
  * process_history_result
  * creates a FEN string by using a game history thread
  * Params: $results
  */
  function process_history_result($results, $game_id){
    $next_to_move="w";

    $trans['a']=0;
    $trans['b']=1;
    $trans['c']=2;
    $trans['d']=3;
    $trans['e']=4;
    $trans['f']=5;
    $trans['g']=6;
    $trans['h']=7;

    $this->GetNewGameFEN($this->ChessCFGFileLocation, $game_id, $aChessBoard, $Other1, $Other2, $Other3, $Other4, $Round, $Error);
    if($Error != "Error"){
      $board= $aChessBoard;
    }else{

      $board= array(array('r','n','b','q','k','b','n','r'),
		    array('p','p','p','p','p','p','p','p'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('P','P','P','P','P','P','P','P'),
  		    array('R','N','B','Q','K','B','N','R'));
    }

    $moves=0;
    $i=0;
    $num = mysqli_num_rows($results);

    if($num != 0){
      
      while($i < $num){

        $moves++; 
        $move = CChess::mysqli_result($results,$i,"move");

        $start_col = substr($move,0,1);
        $start_row = substr($move,1,1);
        $finish_col = substr($move,3,1);
        $finish_row = substr($move,4,1);

	//castling
        if($move=="O-O w"){
          $board[0][6] = "k";
          $board[0][4] = "e";
          $board[0][5] = "r";
          $board[0][7] = "e";			
        }elseif($move=="O-O b"){
          $board[7][6] = "K";
          $board[7][4] = "e";
          $board[7][5] = "R";
          $board[7][7] = "e";
        }elseif($move=="O-O-O w"){		
          $board[0][2] = "k";
          $board[0][4] = "e";
          $board[0][3] = "r";
          $board[0][0] = "e";
        }elseif($move=="O-O-O b"){
          $board[7][2] = "K";
          $board[7][4] = "e";
          $board[7][3] = "R";
          $board[7][0] = "e";
	}
		//////////////
        elseif(strlen($move)==6){
		  $cur = $board[($start_row-1)][($trans[$start_col])];
		  if ($cur == 'P')
	          $board[($finish_row-1)][($trans[$finish_col])]=strtoupper(substr($move,5,1));
		  else
		  	$board[($finish_row-1)][($trans[$finish_col])]=strtolower(substr($move,5,1));		
	      $board[($start_row-1)][($trans[$start_col])]='e';
        }
		elseif(strlen($move)>6){
			$col = (int)substr($move,6,1);
			$row = (int)substr($move,7,1);
			$board[$col][$row] = 'e';
     	    $board[($finish_row-1)][($trans[$finish_col])]=$board[($start_row-1)][($trans[$start_col])];
	        $board[($start_row-1)][($trans[$start_col])]='e';
		}	
		else{
          $board[($finish_row-1)][($trans[$finish_col])]=$board[($start_row-1)][($trans[$start_col])];
          $board[($start_row-1)][($trans[$start_col])]='e';
        }
		
        if($next_to_move == "w"){
          $next_to_move="b";
        }else{
          $next_to_move="w";
        }

        $i++;

      }

    }

    $fen="";
    $j=0;

    for($i=0;$i<8;$i++){

      for($j=0;$j<8;$j++){

        if($board[$i][$j] == "e"){

          $k = 0;

          while($j<8 && $board[$i][$j] == "e"){
            $k++;
            $j++;
          }

          $fen=$fen.$k; 

          if($j<8){
           $fen=$fen.$board[$i][$j];
          }

        }else{

          $fen=$fen.$board[$i][$j];

        }

      }

      $fen=$fen."/";

    }

    list($moves_str,$crap)=preg_split("/\./", $moves/2);

    return $fen." ".$next_to_move." KQkq - 0 ".($moves_str+1)."";

  }


  /**********************************************************************
  * get_move_history_list
  * modified 28/01/09 - Updated code to properly create pgn moves (taking into account
  * if other pieces of the same type can move to a destination tile).
  * 
  * Params: 
  * $game_id - the id used to identify the game.
  *
  * Returns a string containing the game move history in PGN format.
  */
  function get_move_history_list($ConfigFile, $game_id){

    //include config file
    include($ConfigFile);

    $result="*";
    $moves=1;
    //if(!isset($noGameInfo)){
      $hist="[Event \"".$game_id."\"]\n[CustomFEN \"".$this->GetGameCustomStaringFEN($ConfigFile, $game_id)."\"]\n[Mode \"ICS\"]\n";
    //}
    $next_to_move="w";

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // before we create the pgn move string, gather info on the game itself for the PGN tags.

    $stt = "SELECT game.completion_status FROM game WHERE game_id='".$game_id."'";
    $sttreturn = mysqli_query($db_my,$stt) or die(mysqli_error($db_my));
    $sttnum = mysqli_num_rows($sttreturn);    

    $status = CChess::mysqli_result($sttreturn,0,0);; 
    
    if($status == "W"){
      $result="1-0";
    }elseif($status == "B"){
      $result="0-1";
    }elseif($status == "D"){
      $result="1/2-1/2";
    }

    $sti = "SELECT * FROM move_history WHERE game_id='".$game_id."' ORDER BY time ASC";
    $stireturn = mysqli_query($db_my,$sti) or die(mysqli_error($db_my));
    $stinum = mysqli_num_rows($stireturn); 

    $stt22 = "SELECT * FROM game WHERE game_id='".$game_id."'";
    $sttreturn22 = mysqli_query($db_my,$stt22) or die(mysqli_error($db_my));
    $sttnum22 = mysqli_num_rows($sttreturn22);   

    $ConfigFile = "";

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($this->ChessCFGFileLocation);
    $blackname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation,CChess::mysqli_result($sttreturn22, 0, "b_player_id"));
    $whitename = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation,CChess::mysqli_result($sttreturn22, 0, "w_player_id"));
    unset($oR3DCQuery);

    $stt23 = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".CChess::mysqli_result($sttreturn22, 0, "b_player_id")."";
    $sttreturn23 = mysqli_query($db_my,$stt23) or die(mysqli_error($db_my));
    $sttnum23 = mysqli_num_rows($sttreturn23);   

    if($sttnum23 != 0){
      $blackELO = CChess::mysqli_result($sttreturn23, 0, "p_selfrating");
    }

    $stt24 = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".CChess::mysqli_result($sttreturn22, 0, "w_player_id")."";
    $sttreturn24 = mysqli_query($db_my,$stt24) or die(mysqli_error($db_my));
    $sttnum24 = mysqli_num_rows($sttreturn24);   

    if($sttnum24 != 0){
      $whiteELO = CChess::mysqli_result($sttreturn24, 0, "p_selfrating");
    }

    //if(!isset($noGameInfo)){
      $hist .= "[Round \"".(($stinum+1)/2)."\"]\n[White \"".$whitename."\"]\n[Black \"".$blackname."\"]\n[WhiteELO \"".$whiteELO."\"]\n[BlackELO \"".$blackELO."\"]\n[Result \"".$result."\"]\n\n";
    //}
    
    $trans['a']=0;
    $trans['b']=1;
    $trans['c']=2;
    $trans['d']=3;
    $trans['e']=4;
    $trans['f']=5;
    $trans['g']=6;
    $trans['h']=7;

    $this->GetNewGameFEN($this->ChessCFGFileLocation, $game_id, $aChessBoard, $Other1, $Other2, $Other3, $Other4, $Round, $Error);
    if($Error != "Error"){
      $board= $aChessBoard;
    }else{

      $board= array(array('r','n','b','q','k','b','n','r'),
		    array('p','p','p','p','p','p','p','p'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('P','P','P','P','P','P','P','P'),
  		    array('R','N','B','Q','K','B','N','R'));
    }

    $hist .= "".$moves.". ";
    
    $i=0;
    while($i < $stinum){

      $pgn_move="";
      $move = CChess::mysqli_result($stireturn,$i,"move");

      $start_col = substr($move,0,1);     // A to H
      $start_row = substr($move,1,1);     // From 1 to 8
      $finish_col = substr($move,3,1);    // A to H
      $finish_row = substr($move,4,1);    // From 1 to 8 

      $pgn_move = $finish_col.$finish_row;   
      
      $start_row--;   // Adjust values to be 0 to 7
      $finish_row--;  // to access array.

      $piece = $board[$start_row][($trans[$start_col])];
      $piece1 = $piece;
      $piece = strtoupper($piece);

      // Check if the move is castling
      $isCastling = false;
      switch($move){
        case "O-O w":
          $isCastling = true;
          break;

        case "O-O-O w":
          $isCastling = true;
          break;

        case "O-O b":
          $isCastling = true;
          break;

        case "O-O-O b":
          $isCastling = true;
          break;
      }

      // For moves other than castling, need to get the PGN
      // move string. For castle, knight, queen & bishop moves
      // need to check if other pieces of the same type can
      // move to the destination tile. Moves cannot be ambiguous!
      if($isCastling == false){

        ///////////////////////////////////////////////////////////
        // Handle a chess piece capture
        ///////////////////////////////////////////////////////////      
        if($board[$finish_row][($trans[$finish_col])] != 'e'){
          $pgn_move = "x".$pgn_move."";
        }

        ///////////////////////////////////////////////////////////
        // Handle PAWN Chess Piece
        ///////////////////////////////////////////////////////////
        if($piece == "P"){

          $pos = strpos($pgn_move, "x");

          if ($pos === false){
            // No capture
          }else{
            $pgn_move=$start_col.$pgn_move;
          }

        ///////////////////////////////////////////////////////////
        // Handle KNIGHT Chess Piece
        ///////////////////////////////////////////////////////////
        }
        elseif($piece == "N")
        {
          $bSameSquare = false;
          $bSameRow = false;
          $bSameCol = false;

          // Check for knights down 2 rows
          if(($finish_row-2) >= 0 && ($finish_row-2) <= 7){

            if(($trans[$finish_col]+1) <= 7){

              if($board[($finish_row-2)][($trans[$finish_col])+1] == $piece1 && (($finish_row-2) != $start_row || ($trans[$finish_col]+1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($trans[$finish_col]-1) >= 0){

              if($board[($finish_row-2)][($trans[$finish_col])-1] == $piece1 && (($finish_row-2) != $start_row || ($trans[$finish_col]-1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }           

          }

          // Check for knights right 2 columns
          if(($trans[$finish_col]+2) >= 0 && ($trans[$finish_col]+2) <=7){

            if(($finish_row+1) <= 7){

              if($board[($finish_row+1)][($trans[$finish_col])+2] == $piece1 && (($finish_row+1) != $start_row || ($trans[$finish_col]+2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($finish_row-1) >= 0){

              if($board[($finish_row-1)][($trans[$finish_col])+2] == $piece1 && (($finish_row-1) != $start_row || ($trans[$finish_col]+2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

          }

          // Check for knights left 2 columns
          if(($trans[$finish_col]-2) >= 0 && ($trans[$finish_col]-2) <=7){
            
            if(($finish_row+1) <= 7){

              if($board[($finish_row+1)][($trans[$finish_col])-2] == $piece1 && (($finish_row+1) != $start_row || ($trans[$finish_col]-2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($finish_row-1) >= 0){

              if($board[($finish_row-1)][($trans[$finish_col])-2] == $piece1 && (($finish_row-1) != $start_row || ($trans[$finish_col]-2) != $trans[$start_col])){
                $bSameSquare = true;
                 if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }
          }

          // Check for knights up 2 rows
          if(($finish_row+2) >= 0 && ($finish_row+2) <= 7){

            if(($trans[$finish_col]+1) <= 7){

              if($board[($finish_row+2)][($trans[$finish_col])+1] == $piece1 && (($finish_row+2) != $start_row || ($trans[$finish_col]+1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($trans[$finish_col]-1) >= 0){

              if($board[($finish_row+2)][($trans[$finish_col])-1] == $piece1 && (($finish_row+2) != $start_row || ($trans[$finish_col]-1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

          }
          
          // Check if two knights could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if($bSameRow && $bSameCol)
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
            elseif($bSameRow)
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.$pgn_move;
            
          }else{
            $pgn_move=$piece.$pgn_move;
          }
        }
        ///////////////////////////////////////////////////////////
        // Handle BISHOP Chess Piece
        ///////////////////////////////////////////////////////////
        elseif($piece == "B")
        {
          // Check which direction the piece was moved.
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          if($finish_row > $start_row)
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 1; // Up-right
            else
              $dir = 4; // Up-left
          }
          else
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 2; // Down-right
            else
              $dir = 3; // Up-left
          }
          
          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Up-right search
          {
            $x = $trans[$finish_col]+1;
            $y = $finish_row+1;
            while($x < 8 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  // A bishop piece found. Check if the column or row are
                  // the same to the start position of the piece moved.
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = 8; // Stop search as a piece was hit.
              }
              $x++; $y++;
            }
          }
          if($dir != 4) // Down-right search
          {
            $x = $trans[$finish_col] + 1;
            $y = $finish_row - 1;
            while($x < 8 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = -1;
              }
              $x++; $y--;
            }
          }
          if($dir != 1) // Down-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row - 1;
            while($x > -1 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = -1;
              }
              $x--; $y--;
            }
          }
          if($dir != 2) // Up-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row + 1;
            while($x > -1 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = 8;
              }
              $x--; $y++;
            }
          }
          // Check if two (or more) bishops could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        }
       
        ///////////////////////////////////////////////////////////
        // Handle ROOK Chess Piece
        ///////////////////////////////////////////////////////////
        elseif($piece == "R")
        {
          // Check which direction the piece was moved.
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          if($finish_row > $start_row)  // Went up
            $dir = 1;
          elseif($finish_row < $start_row) // Went down
            $dir = 3;
          elseif($trans[$finish_col] > $trans[$start_col]) // Went right
            $dir = 2;
          elseif($trans[$finish_col] < $trans[$start_col]) // Went left
            $dir = 4;

          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Search up.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row + 1; $y < 8; $y++)
            {
              if($board[$y][$x] != 'e') // Found a piece
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;     // Rook is in same column as piece moved.
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = 8;   // Exit loop
              }
            }
          }
          if($dir != 1) // Search down.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row - 1; $y > -1; $y--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = -1;
              }
            }
          }
          if($dir != 4) // Search right.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] + 1; $x < 8; $x++)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8;
              }
            }
          }
          if($dir != 2) // Search left.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] - 1; $x > -1; $x--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1;
              }
            }
          }
          // Check if two (or more) rooks could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        }
        elseif($piece == "Q")
        {
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          $attackerFiles = array();  // Store attacking pieces files
          $attackerRanks = array();  // Store attacking pieces ranks
          $count = 0;   // Number of other attackers.
          
          // Check which direction the piece was moved
          // for vertical and horizontal directions.
          if($finish_row > $start_row)  // Went up
            $dir = 1;
          elseif($finish_row < $start_row) // Went down
            $dir = 3;
          elseif($trans[$finish_col] > $trans[$start_col]) // Went right
            $dir = 2;
          elseif($trans[$finish_col] < $trans[$start_col]) // Went left
            $dir = 4;

          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Search up.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row + 1; $y < 8; $y++)
            {
              if($board[$y][$x] != 'e') // Found a piece
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = 8;   // Exit loop
              }
            }
          }
          if($dir != 1) // Search down.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row - 1; $y > -1; $y--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = -1;
              }
            }
          }
          if($dir != 4) // Search right.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] + 1; $x < 8; $x++)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8;
              }
            }
          }
          if($dir != 2) // Search left.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] - 1; $x > -1; $x--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1;
              }
            }
          }
        
          // Now check which diagonal direction the piece was moved.
          if($finish_row > $start_row)
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 1; // Up-right
            else
              $dir = 4; // Up-left
          }
          else
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 2; // Down-right
            else
              $dir = 3; // Up-left
          }
          
          // Search in all diagonal directions except the one the
          // piece was moved from.
          if($dir != 3) // Up-right search
          {
            $x = $trans[$finish_col]+1;
            $y = $finish_row+1;
            while($x < 8 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = 8;
              }
              $x++; $y++;
            }
          }
          if($dir != 4) // Down-right search
          {
            $x = $trans[$finish_col] + 1;
            $y = $finish_row - 1;
            while($x < 8 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = -1;
              }
              $x++; $y--;
            }
          }
          if($dir != 1) // Down-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row - 1;
            while($x > -1 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = -1;
              }
              $x--; $y--;
            }
          }
          if($dir != 2) // Up-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row + 1;
            while($x > -1 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = 8;
              }
              $x--; $y++;
            }
          }
          
          // Check if two (or more) queens could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        } // End Queen Move Check.
        elseif($piece == "K")
        {
          $pgn_move=$piece.$pgn_move;
        }
        
      } // End non castling move checks.

      // Depending on the move type, move the right piece.
      
      // Castling
      if($move == "O-O w"){

        $board[0][6] = "k";
        $board[0][4] = "e";
        $board[0][5] = "r";
        $board[0][7] = "e";			
        $pgn_move = "O-O";

      }elseif($move == "O-O b"){	
	
        $board[7][6] = "K";
        $board[7][4] = "e";
        $board[7][5] = "R";
        $board[7][7] = "e";
        $pgn_move = "O-O";

      }elseif($move == "O-O-O w"){	
	
        $board[0][2] = "k";
        $board[0][4] = "e";
        $board[0][3] = "r";
        $board[0][0] = "e";
        $pgn_move = "O-O-O";

      }elseif($move == "O-O-O b"){

        $board[7][2] = "K";
        $board[7][4] = "e";
        $board[7][3] = "R";
        $board[7][0] = "e";
        $pgn_move = "O-O-O";

      }elseif(strlen($move)==6){ // Promotion

        // Need to make sure the piece is upper or lowercase
        // depending on whose turn it is.
        if($next_to_move == "w")
          $board[$finish_row][($trans[$finish_col])]=strtolower(substr($move,5,1));
        else
          $board[$finish_row][($trans[$finish_col])]=substr($move,5,1);
        $pgn_move .= "=".strtoupper(substr($move,5,1));
        $board[$start_row][($trans[$start_col])]='e';

      }elseif(strlen($move)>6) { //

      	$pg_move = substr($move,0,5);
      	$col = (int)substr($move,6,1);
      	$row = (int)substr($move,7,1);
      	$board[$col][$row] = 'e';
        $board[$finish_row][($trans[$finish_col])]=$board[$start_row][($trans[$start_col])];
      	$board[$start_row][($trans[$start_col])]='e';

      }else{ // All other moves

        $board[$finish_row][($trans[$finish_col])]=$board[$start_row][($trans[$start_col])];
        $board[$start_row][($trans[$start_col])]='e';

      }
    
      $hist .= "".$pgn_move." "; 

      if($next_to_move == "w"){
        $next_to_move="b";
      }else{
        $next_to_move="w";
        $moves++;
        $hist .= "$moves. ";
      }
      $i++;
    } // end loop through all moves

    $hist .= $result;

    return $hist;

  }

/**********************************************************************
  * get_move_history_list2 
  * - modified 28/01/09 - Updated code to properly create pgn moves (taking into account
  * if other pieces of the same type can move to a destination tile).
  * 
  * Params: $game_id
  */
 // function get_move_history_list2($ConfigFile, $game_id){
 //   return get_move_history_list($ConfigFile, $game_id, 1);
 // }
  

  /**********************************************************************
  * get_move_history_list2
  * 
  * Params: $game_id
  */
  function get_move_history_list2($ConfigFile, $game_id){

    //include config file
    include($ConfigFile);

    $result="*";
    $moves=1;
    //$hist="[Event \"".$game_id."\"]\n[Site \"".$conf['site_name']."\"]\n[Mode \"ICS\"]\n";
    $next_to_move="w";

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // before we create the PGN move string, gather info on the game itself for the PGN tags.

    $stt = "SELECT game.completion_status FROM game WHERE game_id='".$game_id."'";
    $sttreturn = mysqli_query($db_my,$stt) or die(mysqli_error($db_my));
    $sttnum = mysqli_num_rows($sttreturn);    

    $status = CChess::mysqli_result($sttreturn,0,0);; 
    
    if($status == "W"){
      $result="1-0";
    }elseif($status == "B"){
      $result="0-1";
    }elseif($status == "D"){
      $result="1/2-1/2";
    }

    $sti = "SELECT * FROM move_history WHERE game_id='".$game_id."' ORDER BY time ASC";
    $stireturn = mysqli_query($db_my,$sti) or die(mysqli_error($db_my));
    $stinum = mysqli_num_rows($stireturn); 

    $stt22 = "SELECT * FROM game WHERE game_id='".$game_id."'";
    $sttreturn22 = mysqli_query($db_my,$stt22) or die(mysqli_error($db_my));
    $sttnum22 = mysqli_num_rows($sttreturn22);   

    $ConfigFile = "";

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($this->ChessCFGFileLocation);
    $blackname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation,CChess::mysqli_result($sttreturn22, 0, "b_player_id"));
    $whitename = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation,CChess::mysqli_result($sttreturn22, 0, "w_player_id"));
    unset($oR3DCQuery);

    $stt23 = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".CChess::mysqli_result($sttreturn22, 0, "b_player_id")."";
    $sttreturn23 = mysqli_query($db_my,$stt23) or die(mysqli_error($db_my));
    $sttnum23 = mysqli_num_rows($sttreturn23);   

    if($sttnum23 != 0){
      $blackELO = CChess::mysqli_result($sttreturn23, 0, "p_selfrating");
    }

    $stt24 = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".CChess::mysqli_result($sttreturn22, 0, "w_player_id")."";
    $sttreturn24 = mysqli_query($db_my,$stt24) or die(mysqli_error($db_my));
    $sttnum24 = mysqli_num_rows($sttreturn24);   

    if($sttnum24 != 0){
      $whiteELO = CChess::mysqli_result($sttreturn24, 0, "p_selfrating");
    }

    //$hist .= "[Round \"".($stinum/2)."\"]\n[White \"".$whitename."\"]\n[Black \"".$blackname."\"]\n[WhiteELO \"".$whiteELO."\"]\n[BlackELO \"".$blackELO."\"]\n[Result \"".$result."\"]\n\n";

    $trans['a']=0;
    $trans['b']=1;
    $trans['c']=2;
    $trans['d']=3;
    $trans['e']=4;
    $trans['f']=5;
    $trans['g']=6;
    $trans['h']=7;

    $this->GetNewGameFEN($this->ChessCFGFileLocation, $game_id, $aChessBoard, $Other1, $Other2, $Other3, $Other4, $Round, $Error);
    if($Error != "Error"){
      $board= $aChessBoard;
    }else{

      $board= array(array('r','n','b','q','k','b','n','r'),
		    array('p','p','p','p','p','p','p','p'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('e','e','e','e','e','e','e','e'),
  		    array('P','P','P','P','P','P','P','P'),
  		    array('R','N','B','Q','K','B','N','R'));
    }

    $hist .= "".$moves.". ";

    $i=0;
    while($i < $stinum){

      $pgn_move="";
      $move = CChess::mysqli_result($stireturn,$i,"move");

      $start_col = substr($move,0,1);     // A to H
      $start_row = substr($move,1,1);     // From 1 to 8
      $finish_col = substr($move,3,1);    // A to H
      $finish_row = substr($move,4,1);    // From 1 to 8 

      $pgn_move = $finish_col.$finish_row;   
      
      $start_row--;   // Adjust values to be 0 to 7
      $finish_row--;  // to access array.

      $piece = $board[$start_row][($trans[$start_col])];
      $piece1 = $piece;
      $piece = strtoupper($piece);

      // Check if the move is castling
      $isCastling = false;
      switch($move){
        case "O-O w":
          $isCastling = true;
          break;

        case "O-O-O w":
          $isCastling = true;
          break;

        case "O-O b":
          $isCastling = true;
          break;

        case "O-O-O b":
          $isCastling = true;
          break;
      }

      // For moves other than castling, need to get the PGN
      // move string. For castle, knight, queen & bishop moves
      // need to check if other pieces of the same type can
      // move to the destination tile. Moves cannot be ambiguous!
      if($isCastling == false){

        ///////////////////////////////////////////////////////////
        // Handle a chess piece capture
        ///////////////////////////////////////////////////////////      
        if($board[$finish_row][($trans[$finish_col])] != 'e'){
          $pgn_move = "x".$pgn_move."";
        }

        ///////////////////////////////////////////////////////////
        // Handle PAWN Chess Piece
        ///////////////////////////////////////////////////////////
        if($piece == "P"){

          $pos = strpos($pgn_move, "x");

          if ($pos === false){
            // No capture
          }else{
            $pgn_move=$start_col.$pgn_move;
          }

        ///////////////////////////////////////////////////////////
        // Handle KNIGHT Chess Piece
        ///////////////////////////////////////////////////////////
        }
        elseif($piece == "N")
        {
          $bSameSquare = false;
          $bSameRow = false;
          $bSameCol = false;

          // Check for knights down 2 rows
          if(($finish_row-2) >= 0 && ($finish_row-2) <= 7){

            if(($trans[$finish_col]+1) <= 7){

              if($board[($finish_row-2)][($trans[$finish_col])+1] == $piece1 && (($finish_row-2) != $start_row || ($trans[$finish_col]+1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($trans[$finish_col]-1) >= 0){

              if($board[($finish_row-2)][($trans[$finish_col])-1] == $piece1 && (($finish_row-2) != $start_row || ($trans[$finish_col]-1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }           

          }

          // Check for knights right 2 columns
          if(($trans[$finish_col]+2) >= 0 && ($trans[$finish_col]+2) <=7){

            if(($finish_row+1) <= 7){

              if($board[($finish_row+1)][($trans[$finish_col])+2] == $piece1 && (($finish_row+1) != $start_row || ($trans[$finish_col]+2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($finish_row-1) >= 0){

              if($board[($finish_row-1)][($trans[$finish_col])+2] == $piece1 && (($finish_row-1) != $start_row || ($trans[$finish_col]+2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

          }

          // Check for knights left 2 columns
          if(($trans[$finish_col]-2) >= 0 && ($trans[$finish_col]-2) <=7){
            
            if(($finish_row+1) <= 7){

              if($board[($finish_row+1)][($trans[$finish_col])-2] == $piece1 && (($finish_row+1) != $start_row || ($trans[$finish_col]-2) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($finish_row-1) >= 0){

              if($board[($finish_row-1)][($trans[$finish_col])-2] == $piece1 && (($finish_row-1) != $start_row || ($trans[$finish_col]-2) != $trans[$start_col])){
                $bSameSquare = true;
                 if($finish_row - 1 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 2 == $trans[$start_col]) $bSameCol = true;
              }

            }
          }

          // Check for knights up 2 rows
          if(($finish_row+2) >= 0 && ($finish_row+2) <= 7){

            if(($trans[$finish_col]+1) <= 7){

              if($board[($finish_row+2)][($trans[$finish_col])+1] == $piece1 && (($finish_row+2) != $start_row || ($trans[$finish_col]+1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] + 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

            if(($trans[$finish_col]-1) >= 0){

              if($board[($finish_row+2)][($trans[$finish_col])-1] == $piece1 && (($finish_row+2) != $start_row || ($trans[$finish_col]-1) != $trans[$start_col])){
                $bSameSquare = true;
                if($finish_row + 2 == $start_row) $bSameRow = true;
                if($trans[$finish_col] - 1 == $trans[$start_col]) $bSameCol = true;
              }

            }

          }
          
          // Check if two knights could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if($bSameRow && $bSameCol)
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
            elseif($bSameRow)
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
        }
        ///////////////////////////////////////////////////////////
        // Handle BISHOP Chess Piece
        ///////////////////////////////////////////////////////////
        elseif($piece == "B")
        {
          // Check which direction the piece was moved.
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          if($finish_row > $start_row)
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 1; // Up-right
            else
              $dir = 4; // Up-left
          }
          else
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 2; // Down-right
            else
              $dir = 3; // Up-left
          }
          
          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Up-right search
          {
            $x = $trans[$finish_col]+1;
            $y = $finish_row+1;
            while($x < 8 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  // A bishop piece found. Check if the column or row are
                  // the same to the start position of the piece moved.
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = 8; // Stop search as a piece was hit.
              }
              $x++; $y++;
            }
          }
          if($dir != 4) // Down-right search
          {
            $x = $trans[$finish_col] + 1;
            $y = $finish_row - 1;
            while($x < 8 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = -1;
              }
              $x++; $y--;
            }
          }
          if($dir != 1) // Down-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row - 1;
            while($x > -1 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = -1;
              }
              $x--; $y--;
            }
          }
          if($dir != 2) // Up-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row + 1;
            while($x > -1 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = 8;
              }
              $x--; $y++;
            }
          }
          // Check if two (or more) bishops could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        }
       
        ///////////////////////////////////////////////////////////
        // Handle ROOK Chess Piece
        ///////////////////////////////////////////////////////////
        elseif($piece == "R")
        {
          // Check which direction the piece was moved.
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          if($finish_row > $start_row)  // Went up
            $dir = 1;
          elseif($finish_row < $start_row) // Went down
            $dir = 3;
          elseif($trans[$finish_col] > $trans[$start_col]) // Went right
            $dir = 2;
          elseif($trans[$finish_col] < $trans[$start_col]) // Went left
            $dir = 4;

          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Search up.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row + 1; $y < 8; $y++)
            {
              if($board[$y][$x] != 'e') // Found a piece
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;     // Rook is in same column as piece moved.
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = 8;   // Exit loop
              }
            }
          }
          if($dir != 1) // Search down.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row - 1; $y > -1; $y--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = -1;
              }
            }
          }
          if($dir != 4) // Search right.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] + 1; $x < 8; $x++)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8;
              }
            }
          }
          if($dir != 2) // Search left.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] - 1; $x > -1; $x--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1;
              }
            }
          }
          // Check if two (or more) rooks could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        }
        elseif($piece == "Q")
        {
          $dir = 0;
          $bSameSquare = false;
          $bSameCol = false;
          $bSameRow = false;
          
          // Check which direction the piece was moved
          // for vertical and horizontal directions.
          if($finish_row > $start_row)  // Went up
            $dir = 1;
          elseif($finish_row < $start_row) // Went down
            $dir = 3;
          elseif($trans[$finish_col] > $trans[$start_col]) // Went right
            $dir = 2;
          elseif($trans[$finish_col] < $trans[$start_col]) // Went left
            $dir = 4;

          // Search in a line from all directions except the one the
          // piece was moved from.
          if($dir != 3) // Search up.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row + 1; $y < 8; $y++)
            {
              if($board[$y][$x] != 'e') // Found a piece
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;     // Rook is in same column as piece moved.
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = 8;   // Exit loop
              }
            }
          }
          if($dir != 1) // Search down.
          {
            $x = $trans[$finish_col];
            for($y = $finish_row - 1; $y > -1; $y--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameCol = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $y = -1;
              }
            }
          }
          if($dir != 4) // Search right.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] + 1; $x < 8; $x++)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8;
              }
            }
          }
          if($dir != 2) // Search left.
          {
            $y = $finish_row;
            for($x = $trans[$finish_col] - 1; $x > -1; $x--)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  //$bSameRow = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1;
              }
            }
          }
        
          // Now check which diagonal direction the piece was moved.
          if($finish_row > $start_row)
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 1; // Up-right
            else
              $dir = 4; // Up-left
          }
          else
          {
            if($trans[$finish_col] > $trans[$start_col]) 
              $dir = 2; // Down-right
            else
              $dir = 3; // Up-left
          }
          
          // Search in all diagonal directions except the one the
          // piece was moved from.
          if($dir != 3) // Up-right search
          {
            $x = $trans[$finish_col]+1;
            $y = $finish_row+1;
            while($x < 8 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = 8;
              }
              $x++; $y++;
            }
          }
          if($dir != 4) // Down-right search
          {
            $x = $trans[$finish_col] + 1;
            $y = $finish_row - 1;
            while($x < 8 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = 8; $y = -1;
              }
              $x++; $y--;
            }
          }
          if($dir != 1) // Down-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row - 1;
            while($x > -1 && $y > -1)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = -1;
              }
              $x--; $y--;
            }
          }
          if($dir != 2) // Up-left search
          {
            $x = $trans[$finish_col] - 1;
            $y = $finish_row + 1;
            while($x > -1 && $y < 8)
            {
              if($board[$y][$x] != 'e')
              {
                if($board[$y][$x] == $piece1)
                {
                  $bSameSquare = true;
                  if($x == $trans[$start_col]) $bSameCol = true;
                  if($y == $start_row) $bSameRow = true;
                }
                $x = -1; $y = 8;
              }
              $x--; $y++;
            }
          }
          
          // Check if two (or more) queens could have moved to the same tile.
          // If so, need to check if the row/col or both need to be included in the move.
          if($bSameSquare){
            if((!$bSameCol && !$bSameRow) || (!$bSameCol && $bSameRow))
              $pgn_move=$piece.$start_col.$pgn_move;
            elseif($bSameCol && !$bSameRow)
              $pgn_move=$piece.($start_row+1).$pgn_move;
            else
              $pgn_move=$piece.$start_col.($start_row+1).$pgn_move;
          }else{
            $pgn_move=$piece.$pgn_move;
          }
          
        } // End Queen Move Check.
        elseif($piece == "K")
        {
          $pgn_move=$piece.$pgn_move;
        }
      } // End non castling move checks.

      // Depending on the move type, move the right piece.
      
      // Castling
      if($move == "O-O w"){

        $board[0][6] = "k";
        $board[0][4] = "e";
        $board[0][5] = "r";
        $board[0][7] = "e";			
        $pgn_move = "O-O";

      }elseif($move == "O-O b"){	
	
        $board[7][6] = "K";
        $board[7][4] = "e";
        $board[7][5] = "R";
        $board[7][7] = "e";
        $pgn_move = "O-O";

      }elseif($move == "O-O-O w"){	
	
        $board[0][2] = "k";
        $board[0][4] = "e";
        $board[0][3] = "r";
        $board[0][0] = "e";
        $pgn_move = "O-O-O";

      }elseif($move == "O-O-O b"){

        $board[7][2] = "K";
        $board[7][4] = "e";
        $board[7][3] = "R";
        $board[7][0] = "e";
        $pgn_move = "O-O-O";

      }elseif(strlen($move)==6){ // Promotion

        // Need to make sure the piece is upper or lowercase
        // depending on whose turn it is.
        if($next_to_move == "w")
          $board[$finish_row][($trans[$finish_col])]=strtolower(substr($move,5,1));
        else
          $board[$finish_row][($trans[$finish_col])]=substr($move,5,1);
        $pgn_move .= "=".strtoupper(substr($move,5,1));
        $board[$start_row][($trans[$start_col])]='e';

      }elseif(strlen($move)>6) { //

      	$pg_move = substr($move,0,5);
      	$col = (int)substr($move,6,1);
      	$row = (int)substr($move,7,1);
      	$board[$col][$row] = 'e';
        $board[$finish_row][($trans[$finish_col])]=$board[$start_row][($trans[$start_col])];
      	$board[$start_row][($trans[$start_col])]='e';

      }else{ // All other moves

        $board[$finish_row][($trans[$finish_col])]=$board[$start_row][($trans[$start_col])];
        $board[$start_row][($trans[$start_col])]='e';

      }
    
      $hist .= "".$pgn_move." "; 

      if($next_to_move == "w"){
        $next_to_move="b";
      }else{
        $next_to_move="w";
        $moves++;
        $hist .= "$moves. ";
      }
      $i++;
    } // end loop through all moves

    $hist .= $result;

    return $hist;

  }


  /**********************************************************************
  * create_game
  * 
  * Params: $requestor, $other, $req_color
  */
  function create_game($ConfigFile, $requestor, $other, $req_color, $FEN){

    if($other !== ""){

      if($requestor == $other){
        return "";
      }

      // verify the validity of both players
      $w_player_id = "";
      $b_player_id = "";

      if($req_color != "" && $req_color == "w"){
        $w_player_id=$requestor;
        $b_player_id=$other;
      }else{
        $w_player_id=$other;
        $b_player_id=$requestor;
      }

      //include config file
      include($ConfigFile);

      // create a new game in the db
      $game_id = $this->gen_unique();

      // connect to mysql and open database
      $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
      @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

      $st = "INSERT INTO game(game_id, initiator, w_player_id, b_player_id, start_time) VALUES('".$game_id."',".$requestor.",".$w_player_id.",".$b_player_id.",".time().")";
      mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      //if fen is included insert it
      if(trim($FEN) != ""){

         $st = "INSERT INTO c4m_newgameotherfen VALUES('".$game_id."', '".trim($FEN)."')";
         mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      }
  
      // immediately update the status of the requestor
      $st = "UPDATE player SET status='E' WHERE player_id=".$requestor."";
      mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      //////////////////////////////////////////////
      // notify the challenged
      $st = "INSERT INTO message_queue(player_id, message, posted) VALUES(".$other.",'".$this->add_header("G",$game_id.$this->zero_pad($requestor,8),"C")."',".time().")";
      mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      //Instantiate theCR3DCQuery Class
      $oR3DCQuery = new CR3DCQuery($this->ChessCFGFileLocation);

      if($oR3DCQuery->ChallangeNotification($other) == true){

        $requestorname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $requestor);
        $otherguysname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $other);

        $otheremail = $oR3DCQuery->GetEmailByPlayerID($this->ChessCFGFileLocation, $other);

        $subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_19", $ConfigFile);


        $aTags1 = array("['otherguysname']", "['requestorname']", "['game_id']", "['siteurl']", "['sitename']");
        $aReplaceTags = array($otherguysname, $requestorname, $game_id, $this->TrimRSlash($conf['site_url']), $conf['site_name']);

        $bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_20", $ConfigFile));

        $this->SendEmail($otheremail, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
      }

      unset($oR3DCQuery);
      //////////////////////////////////////////////


      return $game_id.$this->zero_pad($w_player_id,8).$this->zero_pad($b_player_id,8); 

    }else{
      return "";
    }

  }


  /**********************************************************************
  * SendEmail
  * 
  * Params: $to, $fromemail, $fromname, $subject, $body
  */  
  function SendEmail($to, $fromemail, $fromname, $subject, $body){

    //include config file
    include($this->ChessCFGFileLocation);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // Advanced email configuration
    $query1 = "SELECT * FROM server_email_settings WHERE o_id='1'";
    $return1 = mysqli_query($db_my,$query1) or die(mysqli_error($db_my));
    $num1 = mysqli_num_rows($return1);

    $query2 = "SELECT * FROM smtp_settings WHERE o_id='1'";
    $return2 = mysqli_query($db_my,$query2) or die(mysqli_error($db_my));
    $num2 = mysqli_num_rows($return2);

    $bOld = true;

    $xsmtp = "";
    $xport = "";
    $xuser = "";
    $xpass = "";
    $xdomain= "";

    if($num1 != 0){
      $smtp = trim(CChess::mysqli_result($return1,0,"o_smtp"));
      $port = trim(CChess::mysqli_result($return1,0,"o_smtp_port"));

      $user = "";
      $pass = "";
      $domain = "";

      if($num2 != 0){
        $user = trim(CChess::mysqli_result($return2,0,"o_user"));
        $pass = trim(CChess::mysqli_result($return2,0,"o_pass"));
        $domain = trim(CChess::mysqli_result($return2,0,"o_domain"));

      }

      if($smtp != "" && $port != "" && $user == "" && $pass == ""){

        ini_set("SMTP", $smtp); 
        ini_set("smtp_port", $port); 
        ini_set("sendmail_from", $fromemail); 

      }

      if($smtp != "" && $port != "" && $user != "" && $pass != ""){
        $xsmtp = $smtp;
        $xport = $port;
        $xuser = $user;
        $xpass = $pass;
        $xdomain = $domain;
        $bOld = false;
      }

    }

    if($bOld){

      $headers1 .= "MIME-Version: 1.0\n";
      $headers1 .= "Content-type: text/html; charset=iso-8859-1\n";
      $headers1 .= "X-Priority: 1\n";
      $headers1 .= "X-MSMail-Priority: High\n";
      $headers1 .= "X-Mailer: php\n";
      $headers1 .= "From: \"".$fromname."\" <".$fromemail.">\n";
 
      // Now we send the message
      $send_check = @mail($to,$subject,$body,$headers1);

    }else{

      require_once($conf['absolute_directory_location']."includes/phpmailer/class.phpmailer.php");

      $mail = new PHPMailer();
      //$mail->IsSMTP(); // set mailer to use SMTP
      $mail->SMTPAuth = true;

      $mail->Host = $xsmtp;
      $mail->SMTPAuth = true;
      $mail->Username = $xuser;
      $mail->Password = $xpass;
      $mail->From = $fromemail;
      $mail->FromName = $fromname;
      $mail->AddAddress($to);
      $mail->AddReplyTo($xdomain);

      $mail->WordWrap = 50;
      $mail->IsHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;

      if(!$mail->Send()){
        $insert = "INSERT INTO email_log VALUES(NULL, '".$to."', '".$fromemail."', '".$fromname."', '".addslashes($subject)."', '".addslashes($body)."', '".addslashes($mail->ErrorInfo)."', NOW())";
        mysqli_query($db_my,$insert) or die(mysqli_error($db_my));
      }

    }

  }


  /**********************************************************************
  * register
  * 
  * Params: $userid, $email
  */
  function register($ConfigFile, $userid, $email){

    if($userid == "" || $email == ""){
      return array('success' => FALSE, 'msg' => "One or more required fields was left blank!");
    }

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");
 
    $st = "SELECT player_id FROM player WHERE userid='".$userid."'";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num>0){
      return array('success' => FALSE, 'msg' => "That userid is taken, please reregister with a different id!");
    }else{

    }

    $salt = $conf['password_salt'];
	$pass = substr($this->gen_unique(), 0, 8);
	$hash = md5($salt . $pass);
 
    $st = "INSERT INTO player(userid, password, signup_time, email) VALUES('".$userid."','".$hash."',".time().",'".$email."')";
    mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    // send an email about registration...

    // To The User
    $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_21", $ConfigFile));

    $aTags = array("['sitename']", "['userid']", "['pass']", "['siteurl']");
    $aReplaceTags = array($conf['site_name'], $userid, $pass, $this->TrimRSlash($conf['site_url']));
    $body= str_replace($aTags, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_22", $ConfigFile));

    $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);

    // To The Admin
    $subject1 = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_23", $ConfigFile));

    $aTags1 = array("['userid']", "['email']");
    $aReplaceTags1 = array($userid, $email);
    $body1= str_replace($aTags1, $aReplaceTags1, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_24", $ConfigFile));


    $this->SendEmail($conf['registration_email'], $conf['registration_email'], $conf['site_name'], $subject1, $body1);

    return array('success' => TRUE, 'msg' => "Your account has been created. An initial password will be emailed to the address you specified.");

  }


  /**********************************************************************
  * UserNameExists
  * 
  * Params: $userid
  */
  function UserNameExists($ConfigFile, $userid){

    //include config file
    include($ConfigFile);

    $bExists = false;

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // check to see if that user id is in use...
    $st = "SELECT player_id FROM player WHERE userid='".$userid."'";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num > 0){
     $bExists = true;
    }else{

      // check to see if that user id is in use...
      $st = "SELECT player_id FROM pendingplayer WHERE userid='".$userid."'";
      $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
      $num = mysqli_num_rows($return); 

      if($num >0){
        $bExists = true;
      }

    }

    return $bExists;
  }


  /**********************************************************************
  * register2
  * 
  * Params: $userid, $email
  */
  function register2($ConfigFile, $userid, $email){

    if($userid == "" || $email == ""){
      return array('success' => FALSE, 'msg' => "One or more required fields was left blank!");
    }

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // check to see if that user id is in use...
    $st = "SELECT player_id FROM player WHERE userid='".$userid."'";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num >0){
     return array('success'=> FALSE, 'msg' => "That userid is taken, please reregister with a different id");
    }else{

      // check to see if that user id is in use...
      $st = "SELECT player_id FROM pendingplayer WHERE userid='".$userid."'";
      $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
      $num = mysqli_num_rows($return); 

      if($num >0){
        return array('success' => FALSE, 'msg' => "That userid is taken, please reregister with a different id!");
      }else{

		$salt = $conf['password_salt'];
		$pass = substr($this->gen_unique(), 0, 8);
        $hash = md5($salt . $pass);
 
        $st = "INSERT INTO pendingplayer(userid, password, signup_time, email) VALUES('".$userid."','".$hash."',".time().",'".$email."')";
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));

        // send an email about registration...

        // To The User
        $subject = $conf['site_name']." Chess Registration";

        $body= "Thank you for signing up for ".$conf['site_name']." Online!<br><br>
               Your account will be enabled when the administrator approves it.<br><br>
               <a href='".$conf['site_url']."'>".$conf['site_url']."</a> <br>The home of ".$conf['site_name']."<br>";

        $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);

        // To The Admin
        $subject1 = $conf['site_name']." New Chess Registration";

        $body1 = "The following user has signed up for an account:<br><br>
                  User Name: ".$userid."<br>
                  Email: ".$email."<br><br>
                  Please login and accept or decline the new user."; 

        $this->SendEmail($conf['registration_email'], $conf['registration_email'], $conf['site_name'], $subject1, $body1);


        return array('success' => TRUE, 'msg' => "Your account has been created. Your account will be enabled when the administrator approves it.");

      }

    }

  }


  /**********************************************************************
  * authenticate
  * 
  * Params: $userid, $pw
  */
  function authenticate($ConfigFile, $userid, $pw){ 

    $session="";
    //$this->housekeep($ConfigFile);

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database-".$conf['database_name']);

    $st = "SELECT player_id FROM player WHERE userid='".$userid."' AND password='".$pw."'";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num != 0){
      
      $player_id = CChess::mysqli_result($return,0,0);

      //Instantiate theCR3DCQuery Class
      //$oR3DCQuery = new CR3DCQuery($ConfigFile);

      //if($oR3DCQuery->IsPlayerDisabled($player_id) == false){
   
        // they succeeded security checks, so update info
        // set status to online
        $st = "UPDATE player SET status='N' WHERE player_id=".$player_id."";
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));

        //and create a new session
        $the_time = time();
        $session = "".$this->gen_unique()."|".$player_id;
        $session = base64_encode($session);
 
        $st = "INSERT INTO active_sessions(session, player_id, session_time) VALUES('".$session."',".$player_id.",".$the_time.")";
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      //}

      //$oR3DCQuery->Close();
      //unset($oR3DCQuery);

    }

    return $session;
  }


  /**********************************************************************
  * check_session
  * 
  * Params: $orig_session
  */
  function check_session($orig_session){ 

    $ret=1;
    $online_status="F";

    if($orig_session != ""){
      return 0;
    }

    if($orig_session != 1){

      $session = base64_decode($orig_session);
      list($uniq,$player_id) = preg_split("/\|/", $session);

      // connect to mysql and open database
      $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
      @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

      $st = "SELECT session_time FROM active_sessions WHERE session LIKE '".$orig_session."%' and player_id=".$player_id." ORDER BY session_time ASC";
      $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
      $num = mysqli_num_rows($return); 

      if($num != 0){

        $time = CChess::mysqli_result($return,0,0);

        if((time() - $this->$conf['session_timeout_sec']) > $time){

          // the session has timed out, so remove it and return failure (0)
          $st = "DELETE FROM active_sessions WHERE session='".$orig_session."'";
          mysqli_query($db_my,$st) or die(mysqli_error($db_my));

          $ret=0;

        }else{

          // update the session time (like a touch)
          $st = "UPDATE active_sessions SET session_time=".time()." WHERE session LIKE '".$orig_session."%'";
          mysqli_query($db_my,$st) or die(mysqli_error($db_my));

          $online_status="N";

        }

        $st = "UPDATE player SET status='".$online_status."' WHERE player_id=".$player_id."";
        mysqli_query($db_my,$st) or die(mysqli_error($db_my));

      }else{
        $ret=0;
      }

    }

    return $ret;
  }


  /**********************************************************************
  * delete_session
  * 
  * Params: $orig_session
  */
  function delete_session($ConfigFile, $orig_session){ 

    $this->housekeep($ConfigFile);

    if($orig_session == ""){
      return 0;
    }

    //include config file
    include($ConfigFile);

    $session = base64_decode($orig_session);

    list($uniq, $player_id) = preg_split("/\|/", $session);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "DELETE FROM active_sessions WHERE session LIKE '".$orig_session."%'";
    mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    $st = "UPDATE player SET status='F' WHERE player_id=".$player_id."";
    mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    return 1;
  }


  /**********************************************************************
  * accept_game
  * what a client calls when accepting a game posed by another client
  * Params: $game_id, $player_id
  */
  function accept_game($ConfigFile, $game_id, $player_id){ 

    $ret=0;

    $conf = $this->conf;

    // connect to mysql and open database

    // verify this is the person whom should be playing...
    $st = "SELECT game_id FROM game where game_id='".$game_id."' AND (w_player_id=".$player_id." OR b_player_id=".$player_id.") AND initiator!=".$player_id."";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num != 0){

      $stg = "UPDATE game SET status='A', start_time=".time()." WHERE game_id='".$game_id."'";
      mysqli_query($db_my,$stg) or die(mysqli_error($db_my));

      $ret=1;
    }

    return $ret;

  }

  /**********************************************************************
  * accept_openchallange_game
  * what a client calls when accepting a game posed by another client
  * Params: $game_id, $player_id
  */
  function accept_oc_game($ConfigFile, $game_id, $player_id){ 

    $ret=0;

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // verify this is the person whom should be playing...
    //modified for OC    $st = "SELECT game_id FROM game where game_id='".$game_id."' AND (w_player_id=".$player_id." OR b_player_id=".$player_id.") AND initiator!=".$player_id."";
    $st = "SELECT game_id, w_player_id, b_player_id FROM game where game_id='".$game_id."' AND (w_player_id=".$player_id." OR b_player_id=".$player_id." OR w_player_id=0 OR b_player_id=0) AND (initiator!=".$player_id." OR w_player_id=0 or b_player_id=0)";
    $return = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return); 

    if($num != 0){

      $stg = "UPDATE game SET status='A', start_time=".time()." WHERE game_id='".$game_id."'";
      mysqli_query($db_my,$stg) or die(mysqli_error($db_my));

	//cb try shortcut to setting the right variable
      $stg = "UPDATE game SET w_player_id='".$player_id."' WHERE game_id='".$game_id."' AND w_player_id=0";
      mysqli_query($db_my,$stg) or die(mysqli_error($db_my));

      $stg = "UPDATE game SET b_player_id='".$player_id."' WHERE game_id='".$game_id."' AND b_player_id=0";
      mysqli_query($db_my,$stg) or die(mysqli_error($db_my));

    }

    return $ret;

  }


  /**********************************************************************
  * status_game
  * 
  * Params: $game_id
  */
  function status_game($game_id){

    $status = "";
    $next_move = "w";

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "SELECT initiator, w_player_id, b_player_id, status, completion_status, start_time, next_move FROM game WHERE game_id='".$game_id."'";
    $streturn = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $stnum = mysqli_num_rows($streturn); 

    if($stnum != 0){

      $sg = "SELECT time FROM move_history WHERE game_id='".$game_id."' ORDER BY time DESC LIMIT 1";
      $sgreturn = mysqli_query($db_my,$sg) or die(mysqli_error($db_my));
      $sgnum = mysqli_num_rows($sgreturn);
 
      $lm_time = 0;

      if($sgnum != 0){
        $lm_time = CChess::mysqli_result($sgreturn,0,0);
      }

      $status="".$game_id.$this->zero_pad(CChess::mysqli_result($streturn,0,0),8).$this->zero_pad(CChess::mysqli_result($streturn,0,1),8).$this->zero_pad(CChess::mysqli_result($streturn,0,2),8).CChess::mysqli_result($streturn,0,3).CChess::mysqli_result($streturn,0,4).CChess::mysqli_result($streturn,0,6).CChess::mysqli_result($streturn,0,5).$this->zero_pad($lm_time,10);

    }

    return $status;

  }


  /**********************************************************************
  * messages
  * 
  * Params: $player_id, $msg_max, $peek
  */
  function messages($ConfigFile, $player_id, $msg_max, $peek){

    $limit = "";

    if($msg_max != ""){
      $limit = " LIMIT ".$msg_max.""; 
    }

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "SELECT mid,message FROM message_queue WHERE player_id='".$player_id."' ORDER BY posted ASC ".$limit."";
    $streturn = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $stnum = mysqli_num_rows($streturn); 

    $del_str="DELETE FROM message_queue WHERE 1=0 ";

    $i=0;
    while($i < $stnum){
      //print CChess::mysqli_result($streturn,$i,1)."\n";
      $del_str=$del_str."OR mid='".CChess::mysqli_result($streturn,$i,0)."' ";
      $i++;
    }

    if($peek == "y"){
    }else{
      $sto = $del_str;
      mysqli_query($db_my,$sto) or die(mysqli_error($db_my));
    }

  }


  /**********************************************************************
  * chat
  * 
  * Params: $for_player_id, $sender_id, $msg
  */
  function chat($ConfigFile, $for_player_id, $sender_id, $msg){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $stp = "SELECT userid FROM player WHERE player_id=".$sender_id."";
    $stpreturn = mysqli_query($db_my,$stp) or die(mysqli_error($db_my));
    $stpnum = mysqli_num_rows($stpreturn); 

    if($stpnum != 0){

      $message = $this->add_header("C",CChess::mysqli_result($stpreturn,0,0)."-".$msg,"0"); 

      $st = "INSERT INTO message_queue (player_id, message, posted) VALUES(".$for_player_id.",'".$message."',".time().")";
      mysqli_query($db_my,$st) or die(mysqli_error($db_my));

    }

  }


  /**********************************************************************
  * get_uid
  * 
  * Params: $player_id
  */
  function get_uid($player_id){

    $user_id = "";

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "SELECT userid FROM player WHERE player_id='".$player_id."'";
    $streturn = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $stnum = mysqli_num_rows($streturn); 

    if($stnum != 0){

      $user_id = CChess::mysqli_result($streturn,0,0);

    }

    return $user_id;

  }


  /**********************************************************************
  * add_header
  * create a message header
  * Params: $msg_type, $mesg, $sub_type
  */
  function add_header($msg_type, $mesg, $sub_type){

    if($sub_type == ""){
      $sub_type = "0";
    }

    $header=$msg_type.$sub_type.$this->zero_pad(strlen($mesg),8).$mesg;

    return $header;

  }


  /**********************************************************************
  * zero_pad
  * 
  * Params: $val, $length
  */
  function zero_pad($val, $length){

    if(strlen($val) < $length){
      $zero = $length-strlen($val);
      
      for($i=0;$i<$zero;$i++){
       $val="0".$val;
      }

    }

    return $val;
  }


  /**********************************************************************
  * right_space_pad
  * 
  * Params: $val,$length
  */
  function right_space_pad($val,$length){

    if(strlen($val) < $length){
      $len = $length - strlen($val);
      for($i=0;$i<$len;$i++){
        $val=$val." ";
      }
    }

    return $val;

  }


  /**********************************************************************
  * gen_unique
  *
  * Params: 
  */
  function gen_unique(){

    $char[0]="0";
    $char[1]="1";
    $char[2]="2";
    $char[3]="3";
    $char[4]="4";
    $char[5]="5";
    $char[6]="6";
    $char[7]="7";
    $char[8]="8";
    $char[9]="9";
    $char[10]="A";
    $char[11]="B";
    $char[12]="C";
    $char[13]="D";
    $char[14]="E";
    $char[15]="F";

    $name="";

    for($i=0;$i<32;$i++){
      $name=$name.$char[rand(0,15)];
    }

    return $name;

  }


  /**********************************************************************
  * housekeep
  * runs various house-keeping functions to keep the tables
  * Params: 
  */
  function housekeep($ConfigFile){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // delete sessions that have timed out and not logged out or 
    // deleted by check_session() and mark the user offline 
    $st = "SELECT session FROM active_sessions WHERE session_time<=".(time() - $conf['session_timeout_sec'])."";
    $streturn = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $stnum = mysqli_num_rows($streturn); 

    $i=0;
    while($i < $stnum){
      $this->check_session(CChess::mysqli_result($streturn,0,0)); 

      $i++;
    }
  
  }


  /**********************************************************************
  * CheckSIDTimeout
  *
  */
  function CheckSIDTimeout($ConfigFile){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    // delete sessions that have timed out and not logged out or 
    // deleted by check_session() and mark the user offline 
    $st = "SELECT session FROM active_sessions WHERE session_time<=".(time() - $conf['session_timeout_sec'])."";
    $streturn = mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    $stnum = mysqli_num_rows($streturn); 

    $i=0;
    while($i < $stnum){

      $orig_session = CChess::mysqli_result($streturn, $i, "session");

      // the session has timed out, so remove it and return failure (0)
      $st = "DELETE FROM active_sessions WHERE session='".$orig_session."'";
      mysqli_query($db_my,$st) or die(mysqli_error($db_my)); 


      $i++;
    }

  }


  /**********************************************************************
  * UpdateSIDTimeout
  *
  */
  function UpdateSIDTimeout($ConfigFile, $orig_session){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $st = "UPDATE active_sessions SET session_time =".time()." WHERE session='".$orig_session."'";
    mysqli_query($db_my,$st) or die(mysqli_error($db_my));
    
  }


  /**********************************************************************
  * GetGameCustomStaringFEN
  * grabs a manual FEN string for a requested game id
  * Params: $game_id
  */
  function GetGameCustomStaringFEN($ConfigFile, $game_id){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $query = "SELECT * FROM c4m_newgameotherfen WHERE gameid = '".$game_id."'";
    $return = mysqli_query($db_my,$query) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return);

    $Error = "";

    if($num != 0){
      $fen = CChess::mysqli_result($return, 0, "fen");
    }

    return $fen;

  }


  /**********************************************************************
  * GetNewGameFEN
  * grabs a manual FEN string for a requested game id
  * Params: $game_id
  */
  function GetNewGameFEN($ConfigFile, $game_id, &$aChessBoard, &$Other1, &$Other2, &$Other3, &$Other4, &$Round, &$Error){

    //include config file
    include($ConfigFile);

    // connect to mysql and open database
    $db_my = mysqli_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysqli_select_db($db_my,$conf['database_name']) or die("Unable to select database");

    $query = "SELECT * FROM c4m_newgameotherfen WHERE gameid = '".$game_id."'";
    $return = mysqli_query($db_my,$query) or die(mysqli_error($db_my));
    $num = mysqli_num_rows($return);

    $Error = "";

    if($num != 0){

      $fen = CChess::mysqli_result($return, 0, "fen");

      // Format/Decode the FEN
      list($one, $Other1, $Other2, $Other3, $Other4, $Round) = explode(" ", $fen, 6);

      //echo "<b>1</b>|".$one."|<br>";

      $LENGTH = strlen(trim($one));

      // Check if there is a slash at the end of the fen
      if(substr($one, -1, 1) == "/"){
        $one = substr($one, 0, $LENGTH - 1);
      }

      //echo "<b>2</b>|".$one."|<br>";

      $LENGTH = strlen(trim($one));

      $colpos = 0;
      $totalcolpos = 0;
      $rowpos = 0;

      $COL_MAX = 8;
      $ROW_MAX = 8;

      $bExit = false;

      while($rowpos < $ROW_MAX){

        $colpos = 0;
        $bExit = false;

        while($colpos < $COL_MAX + 1 && $bExit == false && $totalcolpos < $LENGTH){

          //Get the position of the chess piece
          $char = substr($one, $totalcolpos, 1);

          if($char == "/"){
            $bExit = true;
          }else{

            // Check if the char is numeric
            if(is_numeric($char) == TRUE){

              // Create the emply board spaces
              $count = (int) $char;
              $i = 0;

              while($i < $count){
                $aChessBoard[$rowpos][$colpos] = "e";
                $colpos++;

                $i++;
              }

            }else{
              $aChessBoard[$rowpos][$colpos] = $char;
              $colpos++;
            }

          }

          $totalcolpos++;

        }

        $rowpos++;

      }

    }else{
      $Error = "Error";
    }

  }


  /**********************************************************************
  * TrimRSlash
  *
  */
  function TrimRSlash($URL){

    $nLength = strlen($URL);
    return ($URL{$nLength - 1} == '/') ? substr($URL, 0, $nLength - 1) : $URL;

  }


} //end of class definition


class ChessHelper
{
	static $CB;
	static $game_id;
	static $CBU;
	static $last_move_time;
	
	static function load_chess_game($game_id)
	{
		ChessHelper::$game_id = $game_id;
		ChessHelper::$CB = new ChessBoard2();
		ChessHelper::$CBU = new ChessBoardUtilities();
		ChessHelper::$last_move_time = NULL;
		$moves = array();
		
		try{
			// TODO: set starting FEN
			$dbh = CSession::$dbh;
			$stmt = $dbh->prepare("SELECT `move`, `time` FROM `move_history` WHERE `game_id` = ? ORDER BY time ASC");
			$stmt->bind_param('s', $game_id);
			if($stmt->execute())
			{
				$results = ChessHelper::get_results($stmt);
				foreach($results as $result)
				{
					$moves[] = $result['move'];
					ChessHelper::$last_move_time = $result['time'];
				}
			}
			else
			{
				echo "<ERROR>Database Error</ERROR>\n";
				return false;
			}
			//echo "<pre>"; var_dump($moves); echo '</pre>';
			$fen = ChessHelper::get_custom_fen($game_id);
			if($fen)
				ChessHelper::$CB->SetupBoardWithFEN($fen);
		}
		catch(mysqli_sql_exception $e)
		{
			echo "<ERROR>Database Connection Error</ERROR>\n";
			return false;
		}
		//$CBU = new ChessBoardUtilities();
		foreach($moves as $move)
		{
			$promo = '';
			// Castle moves are stored with the side that made the move.
			if($move == 'O-O w')
			{
				$from = 'e1'; $to = 'g1';
			}
			else if($move == 'O-O-O w')
			{
				$from = 'e1'; $to = 'c1';
			}
			else if($move == 'O-O b')
			{
				$from = 'e8'; $to = 'g8';
			}
			else if($move == 'O-O-O b')
			{
				$from = 'e8'; $to = 'c8';
			}
			else	// Normal move (from, to) or en passant (from, to tile_num)
			{
				// En passant moves are stored as "from,to tile_num". Don't care about the tile_num part.
				$move = substr($move, 0, 6);
				// Get possible promotion piece.
				if(strlen($move) == 6)
				{
					if($move[5] == 'Q')
						$promo = PIECE_TYPE::QUEEN;
					else if($move[5] == 'B')
						$promo = PIECE_TYPE::BISHOP;
					else if($move[5] == 'N')
						$promo = PIECE_TYPE::KNIGHT;
					else if($move[5] == 'R')
						$promo = PIECE_TYPE::ROOK;
				}
				// Now only want 5 chars to get from and to tiles.
				$move = substr($move, 0, 5);
				list($from, $to) = explode(',', $move);
			}

			$from = ChessHelper::$CBU->ConvertAlgebraicNotationTileToInteger($from);
			$to = ChessHelper::$CBU->ConvertAlgebraicNotationTileToInteger($to);
			
			//echo "$from -> $to<br/>";
			ChessHelper::$CB->MakeMove($from, $to, $promo, $moveType, true, true);
			//echo "<br/>move type: $moveType";
			//echo "<br/>Board status is: " . $CB->GetGameBoardStatus();
			// ChessHelper::$CB->debug_bitboards();
		}
		
		return true;
	}

	/***********************************************************************************
	 *	Obtains the PGN for the currently loaded game.
	 *  Returns a PGN string.
	 */
	static function get_game_pgn()
	{
		$result = '*';
		
		$info = "[Event \"$game_id\"]\n[CustomFEN \"" . ChessHelper::get_custom_fen(ChessHelper::$game_id) . "\"]\n[Mode \"ICS\"]\n";

		$stt = "SELECT `completion_status` FROM `game` WHERE `game_id`='" . ChessHelper::$game_id . "'";
		$sttreturn = mysqli_query(CSession::$db_link,$stt) or die(mysqli_error(CSession::$db_link));
		$sttnum = mysqli_num_rows($sttreturn);

		$status = CChess::mysqli_result($sttreturn, 0, 0);
		
		if($status == "W"){
		  $result = "1-0";
		}elseif($status == "B"){
		  $result = "0-1";
		}elseif($status == "D"){
		  $result = "1/2-1/2";
		}

		// $sti = "SELECT * FROM move_history WHERE game_id='" . $game_id . "' ORDER BY time ASC";
		// $stireturn = mysqli_query(CSession::$db_link,$sti) or die(mysqli_error(CSession::$db_link));
		// $stinum = mysqli_num_rows($stireturn); 

		$stt22 = "SELECT `w_player_id`, `b_player_id` FROM `game` WHERE `game_id`='" . ChessHelper::$game_id . "'";
		$sttreturn22 = mysqli_query(CSession::$db_link,$stt22) or die(mysqli_error(CSession::$db_link));
		$sttnum22 = mysqli_num_rows($sttreturn22);
		$w_player_id = CChess::mysqli_result($sttreturn22, 0, 'w_player_id');
		$b_player_id = CChess::mysqli_result($sttreturn22, 0, 'b_player_id');
		
		$query = "SELECT `userid` FROM `player` WHERE `player_id` IN ($w_player_id, $b_player_id)";
		$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
		$num = mysqli_num_rows($return);
		if($num == 2)
		{
			$whitename = trim(CChess::mysqli_result($return, 0, 'userid'));
			$blackname = trim(CChess::mysqli_result($return, 1, 'userid'));
		}
		
		// Self rating may not exist. Should something else be used?
		// $query = "SELECT `p_selfrating` FROM c4m_personalinfo WHERE p_playerid IN ($w_player_id, $b_player_id)";
		// $return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
		// if($num == 2)
		// {
			// $whiteELO = CChess::mysqli_result($return, 0, 'p_selfrating');
			// $blackELO = CChess::mysqli_result($return, 1, 'p_selfrating');
		// }
		$whiteELO = $blackELO = 0;
		$info .= "[Round \"" . ChessHelper::$CB->GetFullMoves() . "\"]\n[White \"$whitename\"]\n[Black \"$blackname\"]\n[WhiteELO \"$whiteELO\"]\n[BlackELO \"$blackELO\"]\n[Result \"$result\"]\n\n";
		
		// Now construct the movelist.
		$moves = ChessHelper::$CB->GetMoveList();
		$moveCounter = ChessHelper::$CB->GetStartingMoveNumber() + 1;
		$movestr = '';
		foreach($moves as $move)
		{
			if($move->nSideMoved == PLAYER_SIDE::BLACK) 
			{
				$movestr .= $move->szSAN . ' ';
				$moveCounter++;
			}
			else
			{
				$movestr .= "$moveCounter. " . $move->szSAN . ' ';
			}
		}
		if(count($moves) != 0)
		{
			$movestr .= "$result";
		}
		else
		{
			$movestr .= "$moveCounter. $result";
		}
		
		$info .= "$movestr";
		
		return $info;
		
	}

	/**********************************************************************
	 * GetGameCustomStaringFEN
	 * grabs a manual FEN string for a requested game id
	 */
	static function get_custom_fen($game_id)
	{
		$query = "SELECT * FROM c4m_newgameotherfen WHERE gameid = '".$game_id."'";
		$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
		$num = mysqli_num_rows($return);

		$fen = "";

		if($num != 0){
			$fen = CChess::mysqli_result($return, 0, "fen");
			// Fix up the board section of the FEN. For some reason the board ranks are stored 
			// the opposite way they should be in the database. To make things worse the piece
			// colours are swapped too. Need to turn white into black and vice versa.
			$remap = array('k' => 'K', 'K' => 'k', 'q' => 'Q', 'Q' => 'q', 'b' => 'B', 'B' => 'b', 'n' => 'N', 'N' => 'n', 'r' => 'R', 'R' => 'r', 'p' => 'P', 'P' => 'p');
			$parts = preg_split('/\s/', $fen);
			$board = $parts[0];
			$ranks = preg_split('/\//', $board);
			$fixed = array();
			for($i = 7; $i > -1; $i--)
			{
				$str = $ranks[$i];
				for($c = 0; $c < strlen($str); $c++)
				{
					if(isset($remap[$str[$c]]))
					{
						$str[$c] = $remap[$str[$c]];
					}
				}
				$fixed[] = $str;
			}
			$parts[0] = implode('/', $fixed);
			$fen = implode(' ', $parts);
		}

		return $fen;
	}
	
	static function get_game_state()
	{
		return ChessHelper::$CB->GetGameBoardStatus();
	}
	
	static function get_game_result()
	{
		$stt = "SELECT `completion_status` FROM `game` WHERE `game_id`='" . ChessHelper::$game_id . "'";
		$sttreturn = mysqli_query(CSession::$db_link,$stt) or die(mysqli_error(CSession::$db_link));
		$sttnum = mysqli_num_rows($sttreturn);

		$status = CChess::mysqli_result($sttreturn, 0, 0);
		if($status == "W"){
			return 1; //CHESS_GAME_RESULT::WHITE;
		}elseif($status == "B"){
			return 2; //CHESS_GAME_RESULT::BLACK;
		}elseif($status == "D"){
			return 3; //CHESS_GAME_RESULT::DRAW;
		}
		return 0; //CHESS_GAME_RESULT::UNKNOWN;
	}
	
	static function get_last_move()
	{
		$coords = ChessHelper::$CB->GetLastMoveAsToFromCoords();
		$return = array(
			'SAN' => ChessHelper::$CB->GetLastMoveMade(),
			'from' => ChessHelper::$CBU->ConvertIntegerTileToAlgebraicNotation($coords['from']),
			'to' => ChessHelper::$CBU->ConvertIntegerTileToAlgebraicNotation($coords['to'])
		);
		return $return;
	}
	
	/**********************************************************************************************
	 *  Returns list of captured pieces for both sides.
	 */
	static function get_captured_pieces()
	{
		$moves = ChessHelper::$CB->GetMoveList();
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
		return array('white' => $captured_white, 'black' => $captured_black);
	}
	
	static function get_timing_info()
	{
		$result = array('started' => NULL, 'type' => 'UNKNOWN', 'duration' => NULL);
		$timeouts = array('snail' => NULL, 'slow' => NULL, 'normal' => NULL, 'short' => NULL, 'blitz' => NULL);
		$query = "SELECT * FROM admin_game_options WHERE o_id = 1";
		$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
		$num = mysqli_num_rows($return);

		if($num != 0)
		{
			$timeouts['snail'] = trim(CChess::mysqli_result($return,0,"o_snail"));
			$timeouts['slow'] = trim(CChess::mysqli_result($return,0,"o_slow"));
			$timeouts['normal'] = trim(CChess::mysqli_result($return,0,"o_normal"));
			$timeouts['short'] = trim(CChess::mysqli_result($return,0,"o_short"));
			$timeouts['blitz'] = trim(CChess::mysqli_result($return,0,"o_blitz"));
		}
	
		$query = "SELECT * FROM cfm_game_options WHERE o_gameid='" . ChessHelper::$game_id . "'";
		$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
		$num = mysqli_num_rows($return);

		if($num == 0)
			return $result;
		
		$timing_mode = (int)CChess::mysqli_result($return, 0, "time_mode");
		$timing_type = CChess::mysqli_result($return, 0, "o_timetype");
		
		if($timing_mode == 1)	// Time recorded for both players.
		{
			$timetype = substr(trim(strtolower($timing_type)), 2);
			$result['type'] = $timetype;
			$result['mode'] = 1;
			
			$duration = (int)($timeouts[$timetype] * 86400);
			// Find out the time taken by both players so far and calculate the time remaining.
			$query = "SELECT start_time, w_time_used, b_time_used, next_move FROM game WHERE game_id = '" . ChessHelper::$game_id . "'";
			$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
			$result['started'] = (int)trim(CChess::mysqli_result($return, 0, "start_time"));
			$w_time_left = $duration - (int)trim(CChess::mysqli_result($return, 0, "w_time_used"));
			$b_time_left = $duration - (int)trim(CChess::mysqli_result($return, 0, "b_time_used"));
			$turn = CChess::mysqli_result($return, 0, "next_move");
			if($turn == NULL) $turn = 'w';	// initially games have the player turn set to NULL.
			// Get the time since the last move (elapsed time for the current player). If no move has
			// been made then no time has elapsed. 
			if(ChessHelper::$last_move_time === NULL)
			{
				$elapsed = 0;
			}
			else
			{
				//ChessHelper::$last_move_time = $result['started'];
				$elapsed = ChessHelper::get_seconds_since_last_move('@' . ChessHelper::$last_move_time);
			}
			if($turn == 'w')
				$w_time_left -= $elapsed;
			else
				$b_time_left -= $elapsed;
			$result['w_time_left'] = $w_time_left;
			$result['b_time_left'] = $b_time_left;
			$result['w_time_allowed'] = $duration;
			$result['b_time_allowed'] = $duration;
		}
		else	// Time counts for whole game.
		{
			$timetype = substr(trim(strtolower($timing_type)), 2);
			$result['type'] = $timetype;
			$result['mode'] = 0;
			
			$duration = $timeouts[$timetype];
			$result['duration'] = $duration * 86400;
			
			$query = "SELECT start_time FROM game WHERE game_id = '" . ChessHelper::$game_id . "'";
			$return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
			$result['started'] = trim(CChess::mysqli_result($return, 0, "start_time"));
			
			//old
			// $query = "SELECT * FROM move_history WHERE game_id='" . ChessHelper::$game_id . "' ORDER BY move_id DESC";
			// $return = mysqli_query(CSession::$db_link,$query) or die(mysqli_error(CSession::$db_link));
			// $move_cnt = mysqli_num_rows($return);

			// if($move_cnt != 0)
			// {
				// $move_time = trim(CChess::mysqli_result($return, 0, "time"));
				// $time_diff = strtotime("+" . $duration * 86400 . " sec", $move_time) - time();
				// $result['remaining'] = $time_diff;
			// }
			// else
			// {
				// $query = "SELECT start_time FROM game WHERE game_id = '" . ChessHelper::$game_id . "'";
				// $return = mysqli_query($db_my,$query, CSession::$db_link) or die(mysqli_error($db_my));
				// if(mysqli_num_rows($return))
				// {
					// $time_started = trim(CChess::mysqli_result($return, 0, "start_time"));
					// $time_diff = strtotime("+" . $duration * 86400 . " sec", $time_started) - time();
					// $result['remaining'] = $time_diff;
				// }
			// }
		}

		return $result;
	}
	
	static function get_results($stmt)
	{
		$meta = $stmt->result_metadata();
		while ($field = $meta->fetch_field())
		{
			$params[] = &$row[$field->name];
		}

		call_user_func_array(array($stmt, 'bind_result'), $params);

		$result = array();
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

	static function get_seconds_since_last_move($last_move_time)
	{
		$date = new DateTime($last_move_time);
		$now = new DateTime(null, new DateTimeZone('UTC'));
		$date = $date->format('U');
		$now = $now->format('U');
		$diff = $now - $date;
		return $diff;
	}
}


?>