<?php
////////////////////////////////////////////////////////////////////////////////
//
// (c) phpChess Limited, 2004-2006, in association with Goliath Systems.
// All rights reserved. Please observe respective copyrights.
// phpChess - Chess at its best
// you can find us at http://www.phpchess.com.
//
////////////////////////////////////////////////////////////////////////////////

  if(!defined('CHECK_PHPCHESS')){   
    die("Hacking attempt");
    exit;
  }

// Class Includes
include_once('CChess.php');

class CR3DCQuery{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $link;
  var $SkinsLocation;
  var $adl;
  var $ChessCFGFileLocation;
  var $SiteURL;
  var $SiteName;
  var $RegistrationEmail;
  var $conf = array();
  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CR3DCQuery (Constructor)
  *
  */
  function CR3DCQuery($ConfigFile){

    ////////////////////////////////////////////////////////////////////////////
    // Sets the chess config file location (absolute location on the server)
    ////////////////////////////////////////////////////////////////////////////
    $this->ChessCFGFileLocation = $ConfigFile;
    ////////////////////////////////////////////////////////////////////////////

    include($ConfigFile);
    $this->conf = $conf;
    $this->host = $conf['database_host'];
    $this->dbnm = $conf['database_name'];
    $this->user = $conf['database_login'];
    $this->pass = $conf['database_pass'];
    $this->adl = $conf['absolute_directory_location'];
    $this->SiteURL = $conf['site_url'];
    $this->SiteName = $conf['site_name'];
    $this->RegistrationEmail = $conf['registration_email'];
    if(!defined(CONNECTED)) {
      $this->link = mysql_connect($this->host, $this->user, $this->pass);
      mysql_select_db($this->dbnm);
      define('CONNECTED',1);
    }
    if(!$this->link){
      die("CR3DCQuery.php: ".mysql_error());
    }

    mysql_set_charset('utf8', $this->link);

    $query = "SELECT * FROM c4m_skins LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $name = mysql_result($return,$i,"name");

    }

    $this->SkinsLocation = $name;

  }


  /**********************************************************************
  * GetStringFromStringTable
  *
  */
  function GetStringFromStringTable($strTag){

    $conf = $this->conf;
    // Get Server Language
    $LanguageFile = "";

    if(isset($_SESSION['language'])){

      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$_SESSION['language'];
      }

    }else{

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".mysql_result($return, 0, "o_languagefile");

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
          list($Key, $strText, $junk) = explode("||", $lines[$x-1], 3);
	  $strText = utf8_encode($strText);
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
  * GetNewPlayers
  *
  */
  function GetNewPlayers($ConfigFile){

    $query = "SELECT * FROM player ORDER BY signup_time DESC LIMIT 5";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_GETNEWPLAYERS_TABLE1_WIDTH') && defined('CFG_GETNEWPLAYERS_TABLE1_BORDER') && defined('CFG_GETNEWPLAYERS_TABLE1_CELLPADDING') && defined('CFG_GETNEWPLAYERS_TABLE1_CELLSPACING') && defined('CFG_GETNEWPLAYERS_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETNEWPLAYERS_TABLE1_BORDER."' align='".CFG_GETNEWPLAYERS_TABLE1_ALIGN."' cellpadding='".CFG_GETNEWPLAYERS_TABLE1_CELLPADDING."' cellspacing='".CFG_GETNEWPLAYERS_TABLE1_CELLSPACING."' width='".CFG_GETNEWPLAYERS_TABLE1_WIDTH."'>";
    }else{
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
    }

    if($num != 0){

      $i = 0;
      while($i < $num){
        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signuptime  = trim(mysql_result($return,$i,"signup_time"));

        if($this->IsPlayerDisabled($player_id) == false){

          echo "<tr>";
          echo "<td><font class='menubulletcolor'>";
          echo "&#8226; &nbsp;<a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a>";
          echo "</font>";
          echo "</td>";
          echo "<td>";
          echo "<span class='gensmall'>".date("m-d-Y",$signuptime)."</span>";
          echo "</td>";
          echo "</tr>";

        }

        $i++;
      }

    }

    echo "<tr>";
    echo "<td colspan='2'><font class='menubulletcolor'>";
    echo "&#8226; &nbsp;<a href='./chess_view_player_list.php'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_1")."</a>";
    echo "</font>";
    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * Login
  *
  */
  function Login($UserID, $Pass){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $sid = $oChess->authenticate($this->ChessCFGFileLocation, $UserID, $this->hash_password($Pass));
    unset($oChess);

    // Remove old sessions
    if($sid != ""){

      $playerID = $this->GetIDByUserID($ConfigFile, $UserID);
      $this->PurgeOldUserSessionData($playerID, $sid);

    }

    return $sid;

  }

  // Hashes the password string using the salt from the config file.
  public function hash_password($pass)
  {
	include('./bin/config.php');
	$salt = $conf['password_salt'];
	$hash = md5($salt . $pass);
	//echo "salt: $salt, pass: $pass, hash: $hash";
	return $hash;
  }

  /**********************************************************************
  * PurgeOldUserSessionData
  *
  */
  function PurgeOldUserSessionData($PlayerID, $newSID){

    $delete = "DELETE FROM active_sessions WHERE session!='".$newSID."' AND player_id=".$PlayerID;
    mysql_query($delete, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * CheckLogin
  *
  */
  function CheckLogin($ConfigFile, $SID){

    $bIsLoggedIn = false;

    $query = "SELECT * FROM active_sessions WHERE session Like '".$SID."%'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bIsLoggedIn = true;
    }

    return $bIsLoggedIn;

  }


  /**********************************************************************
  * GetIDByUserID
  *
  */
  function GetIDByUserID($ConfigFile, $UserID){

    $query = "SELECT * FROM player WHERE userid = '".$UserID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $player_id = "";

    if($num != 0){
       $player_id  = trim(mysql_result($return,0,"player_id"));
    }

    return $player_id;

  }


  /**********************************************************************
  * GetUserIDByPlayerID
  *
  */
  function GetUserIDByPlayerID($ConfigFile, $ID){

    $query = "SELECT * FROM player WHERE player_id = ".$ID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $user_id = "";

    if($ID == 0){
      $user_id = "Tournament";
    }else{
      $user_id = "Unknown";
    }

    if($num != 0){
       $user_id  = trim(mysql_result($return,0,"userid"));
    }

    return $user_id;

  }


  /**********************************************************************
  * GetCurrentGamesByPlayerID
  *
  */
  function GetCurrentGamesByPlayerID($ConfigFile, $ID){

    //Get game where the player is white
    $queryw = "SELECT * FROM game WHERE w_player_id = ".$ID." AND completion_status IN('A','I')";
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    //get all the games from the c4m_tournamentgames table
    $queryt = "SELECT tg_gameid FROM c4m_tournamentgames";
    $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    $numt = mysql_numrows($returnt);

    echo "<br>";

    if($numw != 0){

      $i = 0;
      echo "<table width='195' cellpadding='0' cellspacing='0' border='0' align='center'>";

      while($i < $numw){

        $game_id = trim(mysql_result($returnw,$i,"game_id"));
        $initiator = trim(mysql_result($returnw,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnw,$i,"next_move"));

        $status = trim(mysql_result($returnw,$i,"status"));
        $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

        $Gtypecode = $this->GetGameTypeCode($game_id);
        $IsAccepted = $this->CheckGameAccepted($ConfigFile, $ID, $game_id);
        $bturn = $this->IsPlayersTurn($ConfigFile, $ID, $game_id);

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        //Check to see if its the players turn and/or if the game is in a waiting state
        if($bexit == false){

          list($PlayerType1, $status1) = explode(" ", $IsAccepted, 2);

          if($status1 != "waiting" && $Gtypecode == 1){
            if($bturn != true){
              $bexit = true;
            }
          }

        }

        if($bexit == false){

          echo "<tr>";
          echo "<td>";

          if($next_move == "w" || $next_move == "w"){
            if($Gtypecode != 1){
              echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{

              if($bturn != true){
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder.gif' width='25' height='25'>";
              }else{
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
              }

            }
          }

          if($next_move == "B" || $next_move == "b"){
            if($Gtypecode != 1){
             echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{
              if($bturn != true){
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder.gif' width='25' height='25'>";
              }else{
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
              }
            }
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "<img src='./skins/".$this->SkinsLocation."/images/folder_lock.gif' width='25' height='25'>";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            if($Gtypecode != 1){
              echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{
              echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
            }
          }

          echo "</td>";
          echo "<td>";

          echo "<a href='./chess_game_rt.php?gameid=".$game_id."' class='menulinks'>";
          echo $this->GetUserIDByPlayerID($ConfigFile,$initiator)."";
          echo " VS ";

          if($w_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
          }

          if($b_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);
          }

          echo "</a>";
          echo "</td>";

          echo "</tr>";

        }

        $i++;

      }

      echo "</table>";
    }

    //Get game where the player is black
    $queryb = "SELECT * FROM game WHERE b_player_id = ".$ID." AND completion_status IN('A','I')";
    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    if($numb != 0){

      $i = 0;
      echo "<table width='195' cellpadding='0' cellspacing='0' border='0' align='center'>";

      while($i < $numb){

        $game_id = trim(mysql_result($returnb,$i,"game_id"));
        $initiator = trim(mysql_result($returnb,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnb,$i,"next_move"));

        $status = trim(mysql_result($returnb,$i,"status"));
        $completion_status = trim(mysql_result($returnb,$i,"completion_status"));

        $Gtypecode = $this->GetGameTypeCode($game_id);
        $IsAccepted = $this->CheckGameAccepted($ConfigFile, $ID, $game_id);
        $bturn = $this->IsPlayersTurn($ConfigFile, $ID, $game_id);

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        //Check to see if its the players turn and/or if the game is in a waiting state
        if($bexit == false){

          list($PlayerType1, $status1) = explode(" ", $IsAccepted, 2);

          if($status1 != "waiting" && $Gtypecode == 1){
            if($bturn != true){
              $bexit = true;
            }
          }

        }

        if($bexit == false){

          echo "<tr>";
          echo "<td>";

          if($next_move == "w" || $next_move == "w"){
            if($Gtypecode != 1){
              echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{

              if($bturn != true){
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder.gif' width='25' height='25'>";
              }else{
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
              }

            }
          }

          if($next_move == "B" || $next_move == "b"){
            if($Gtypecode != 1){
             echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{
              if($bturn != true){
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder.gif' width='25' height='25'>";
              }else{
                echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
              }
            }
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "<img src='./skins/".$this->SkinsLocation."/images/folder_lock.gif' width='25' height='25'>";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            if($Gtypecode != 1){
              echo "<img src='./skins/".$this->SkinsLocation."/images/rt.gif' width='25' height='25'>";
            }else{
              echo "<img src='./skins/".$this->SkinsLocation."/images/folder_new.gif' width='25' height='25'>";
            }
          }

          echo "</td>";
          echo "<td>";

          echo "<a href='./chess_game_rt.php?gameid=".$game_id."' class='menulinks'>";
          echo $this->GetUserIDByPlayerID($ConfigFile,$initiator)."";
          echo " VS ";

          if($w_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
          }

          if($b_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);
          }

          echo "</a>";
          echo "</td>";

          echo "</tr>";
        }

        $i++;

      }

      echo "</table>";
    }
    echo "<br>";

  }


  /**********************************************************************
  * GetPlayerStatusByPlayerID
  *
  */
  function GetPlayerStatusByPlayerID($ConfigFile, $ID){

    $wins = 0;
    $loss = 0;
    $draws = 0;

    $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $ID, $wins, $loss, $draws);

    //Display the results
    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_2")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$wins."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_3")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$loss."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_4")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$draws."</span>";
    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetPlayerStatusrRefByPlayerID
  *
  */
  function GetPlayerStatusrRefByPlayerID($ConfigFile, $ID, &$wins, &$loss, &$draws){

    $wins = 0;
    $loss = 0;
    $draws = 0;

    /////////////////////////////////////
    //WINS

    $query = "SELECT COUNT(*) FROM game WHERE (w_player_id=".$ID." AND completion_status='W') OR (b_player_id=".$ID." AND completion_status='B')";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $wins = mysql_result($return,0,0);
    }

    /////////////////////////////////////
    //LOSS

    $query2 = "SELECT COUNT(*) FROM game WHERE (w_player_id=".$ID." AND completion_status='B') OR (b_player_id=".$ID." AND completion_status='W')";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $loss = mysql_result($return2,0,0);
    }

    /////////////////////////////////////
    //DRAWS

    $query4 = "SELECT COUNT(*) FROM game WHERE (w_player_id=".$ID." AND completion_status='D') OR (b_player_id=".$ID." AND completion_status='D')";
    $return4 = mysql_query($query4, $this->link) or die(mysql_error());
    $num4 = mysql_numrows($return4);

    if($num4 != 0){
      $draws = mysql_result($return4,0,0);
    }

  }


  /**********************************************************************
  * GetChessboardColorsHTML
  *
  */
  function GetChessboardColorsHTML($clrl, $clrd){

    $this->ChessBoardColors($clrl, $clrd);

  }


  /**********************************************************************
  * GetFEN
  *
  */
  function GetFEN($sid, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->request_FEN($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    return "0000000000".$returncode;

  }


  /**********************************************************************
  * GetFEN2
  *
  */
  function GetFEN2($sid, $GameID, $time){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->request_FEN2($this->ChessCFGFileLocation, $GameID, $time);
    unset($oChess);

    return "0000000000".$returncode;

  }


  /**********************************************************************
  * GetFEN3
  *
  */
  function GetFEN3($sid, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->request_FEN($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    return $returncode;

  }


  /**********************************************************************
  * GetHackedFEN
  *
  */
  function GetHackedFEN($sid, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->request_FEN($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $returncode ="0000000000".$returncode;

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = explode("/", substr(str_replace(strrchr($returncode, "/"), "", $returncode),10), 8);

    //Get the rest of the fen string
    list($j1, $j2, $j3, $j4, $j5, $j6, $j7) = explode(" ", $returncode, 7);

    $backfen = trim($j2." ".$j3." ".$j4." ".$j5." ".$j6." ".$j7);

    //convert the fen
    $one = $this->ConvertFenRow($one);
    $two= $this->ConvertFenRow($two);
    $three= $this->ConvertFenRow($three);
    $four= $this->ConvertFenRow($four);
    $five= $this->ConvertFenRow($five);
    $six= $this->ConvertFenRow($six);
    $seven= $this->ConvertFenRow($seven);
    $eight= $this->ConvertFenRow($eight);

    $returncode = $eight."/".$seven."/".$six."/".$five."/".$four."/".$three."/".$two."/".$one." ".$backfen;

    return $returncode;

  }


  /**********************************************************************
  * ConvertFenRow
  *
  */
  function ConvertFenRow($row){

    $nlen = strlen(trim($row));
    $newPOS = "";

    $i=0;
    while($i < $nlen){

      $letter = substr($row,$i, 1 );

      if(!is_integer($letter)){

        if (ctype_upper($letter)) {
          //convert to lower
          $newPOS = $newPOS."".strtolower($letter);
        } else {
          $newPOS = $newPOS."".strtoupper($letter);
        }

      }else{
        $newPOS = $newPOS."".$letter;
      }

      $i++;
    }

    return $newPOS;

  }


  /**********************************************************************
  * IsPlayerBlack
  *
  */
  function IsPlayerBlack($ConfigFile, $GameID, $player_id, $bTournament=false){

    $bReturn = false;

    if($bTournament){

      $query = "SELECT * FROM game WHERE game_id = '".$GameID."' AND w_player_id != ".$player_id;
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){
        $bReturn = true;
      }

    }else{

      $query = "SELECT * FROM game WHERE game_id = '".$GameID."' AND b_player_id = ".$player_id;
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){
        $bReturn = true;
      }

    }

    return $bReturn;

  }


  /**********************************************************************
  * GetCurrentGameInfoByRef
  *
  */
  function GetCurrentGameInfoByRef($ConfigFile, $ID, &$initiator, &$w_player_id, &$b_player_id, &$next_move, &$start_time){

    //Get game info
    $query = "SELECT * FROM game WHERE game_id = '".$ID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $game_id = trim(mysql_result($return,$i,"game_id"));
      $initiator = trim(mysql_result($return,$i,"initiator"));
      $w_player_id = trim(mysql_result($return,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($return,$i,"b_player_id"));
      $next_move = trim(mysql_result($return,$i,"next_move"));
      $start_time = trim(mysql_result($return,$i,"start_time"));

    }

  }


  /**********************************************************************
  * GetGameStatusRT
  *
  */
  function GetGameStatusRT($ConfigFile, $GameID, $sid, $player_id, $Gamestatus, $clrl, $clrd){

    // retieve data
    $fen = $this->GetFEN($sid, $GameID);
    $isblack = $this->IsPlayerBlack($ConfigFile, $GameID, $player_id);

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";

    $this->GetCurrentGameInfoByRef($ConfigFile, $GameID, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

    echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";
    if($isblack){

      echo "<tr>";
      echo "<td valign='top'>";
      $this->CreateChessBoard($fen, $clrl, $clrd, true, "b");
      echo "</td>";

    }else{

      echo "<tr>";
      echo "<td valign='top'>";
      $this->CreateChessBoard($fen, $clrl, $clrd, true, "w");
      echo "</td>";

    }
    echo "</table>";
    echo "<br>";
    echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";

    //////////////////////////////////////////////////////////////////
    // Move/game status options
    //////////////////////////////////////////////////////////////////
    echo "<tr>";
    echo "<td valign='top' class='row2' colspan='4'>";
    if($this->IsPlayersTurn($ConfigFile, $player_id, $GameID) == true){
      echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_5")." <input type='text' name='txtmovefrom' class='post' size='4'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_6")." <input type='text' name='txtmoveto' class='post' size='4'> <input type='submit' name='cmdMove' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_PM")."' class='mainoption'>";
    }
    if($Gamestatus == "I"){
      echo "<input type='submit' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption'>";
      echo "<input type='submit' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption'>";
    }else{

      echo "<input type='button' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_1")."');\">";
      echo "<input type='button' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_2")."');\">";

    }
    echo "</td>";
    echo "</tr>";
    //////////////////////////////////////////////////////////////////

    echo "</table>";

  }


  /**********************************************************************
  * GetAvatarImageName
  * Params: Player ID
  * Return: retieves the users avatar image name
  */
  function GetAvatarImageName($pid){

    $strImageName = "";

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
       $strImageName = mysql_result($return, 0, "a_imgname");
    }

    return $strImageName;

  }


  /**********************************************************************
  * GetGameStatus
  *
  */
  function GetGameStatus($ConfigFile, $GameID, $sid, $player_id, $Gamestatus, $clrl, $clrd, $ViewMode, $GameType, $BoardType){

    // retieve data
    $fen = $this->GetFEN($sid, $GameID);
    $isblack = $this->IsPlayerBlack($ConfigFile, $GameID, $player_id);

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";

    $this->GetCurrentGameInfoByRef($ConfigFile, $GameID, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

    // Build the table
    echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";

    if($ViewMode == 1){

      echo "<tr>";
      echo "<td valign='top' colspan='2' class='row2'>";

      echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' class='forumline'>";
      echo "<tr>";
      echo "<td valign='top' align='left' class='row2'>";

      $image = $this->GetAvatarImageName($w_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "<td valign='top' align='left' class='row2'>";
      echo "White:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);

      if($this->IsPlayerOnline($ConfigFile, $w_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $w_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($w_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."<br>";
      echo "<a href='./chess_statistics.php?playerid=".$w_player_id."&name=".$userid."'>Statistics</a>";

      echo "</td>";
      echo "<td valign='top' align='center' class='row2'>";
      echo "<b>VS</b><br>";
      echo date("m-d-Y",$start_time)."<br><br><br><br>";

      if($Gamestatus == "I" && $GameType != 1){

        if($this->IsPlayerOnline($ConfigFile, $w_player_id) && $this->IsPlayerOnline($ConfigFile, $b_player_id)){

          if($this->IsRequestRealTime($ConfigFile, $GameID, $isblack) == "IDS_REAL_TIME"){

            if($GameType == 3){
              echo "<input type='button' name='cmdExitRealtime' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_EFR")."' class='mainoption' onclick=\"javascript:alert('You are not allowed to exit real time mode during a timed game.');\">";
            }else{
              echo "<input type='submit' name='cmdExitRealtime' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_EFR")."' class='mainoption' >";
            }

          }else{
            echo "<input type='submit' name='cmdSwitchRealtime' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_STR")."' class='mainoption' >";
          }

        }else{
          echo "<input type='button' name='cmdSwitchRealtime' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_STR")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_4")."');\">";
        }

      }else{

        if($GameType != 1){
          echo "<input type='button' name='cmdSwitchRealtime' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_STR")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_5")."');\">";
        }
      }

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";
      echo "Black:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

      if($this->IsPlayerOnline($ConfigFile, $b_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $b_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($b_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."<br>";
      echo "<a href='./chess_statistics.php?playerid=".$b_player_id."&name=".$userid."'>Statistics</a>";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";

      $image = $this->GetAvatarImageName($b_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "</tr>";

      if($this->IsRequestRealTime($ConfigFile, $GameID, $isblack) == "IDS_REAL_TIME"){

        echo "<tr>";
        echo "<td valign='top' align='left' class='row2'>";
        echo "<input type='text' size='12' name='clockwhite' class='post'>";
        echo "</td>";
        echo "<td valign='top' align='left' class='row2'>";
        echo "</td>";
        echo "<td valign='top' align='center' class='row2'>";
        echo "</td>";
        echo "<td valign='top' align='right' class='row2'>";
        echo "</td>";
        echo "<td valign='top' align='right' class='row2'>";

        echo "<input type='text' size='12' name='clockblack' class='post'>";

        echo "</td>";
        echo "</tr>";

      }

      ////////////////////////////////////////////////////////////////////////////////////////
      //timeout countdown
      if($GameType != 3){

        echo "<tr>";
        echo "<td valign='top' align='left' class='row2' colspan='5'>";
        echo "Time left before game timeout:<br>";

        if($this->IsPlayersTurn($ConfigFile, $w_player_id, $GameID) == true){
          echo "<b>White:</b> ".$this->GetGameTimeoutByGameRelation($w_player_id, $GameID);
        }

        if($this->IsPlayersTurn($ConfigFile, $b_player_id, $GameID) == true){
          echo "<b>Black:</b> ".$this->GetGameTimeoutByGameRelation($b_player_id, $GameID);
        }

        echo "</td>";
        echo "</tr>";

      }

      echo "</table>";

      echo "</td>";
      echo "</tr>";

    }

    echo "<tr>";
    echo "<td valign='top' colspan='2'>";

    echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";

    // Move ///////////////////////////////////////////////////
    echo "<tr>";
    echo "<td valign='top' class='row2' colspan='4'>";

    if($GameType == 3 && $this->IsRequestRealTime($ConfigFile, $GameID, $isblack) == "IDS_REAL_TIME"){

      if($this->IsPlayersTurn($ConfigFile, $player_id, $GameID) == true){

        if($this->isBoardCustomerSettingDragDrop($player_id)){
          echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_231")." <input type='hidden' name='txtmovefrom' class='post' size='3'><input type='hidden' name='txtmoveto' class='post' size='3'> <input type='hidden' name='cmdMove' value=''>&nbsp;";
        }else{
          echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_230")." <input type='text' name='txtmovefrom' class='post' size='3'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_6")." <input type='text' name='txtmoveto' class='post' size='3'> <input type='submit' name='cmdMove' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_PM")."' class='mainoption'>&nbsp;";
        }

      }

    }else{

      if($this->IsPlayersTurn($ConfigFile, $player_id, $GameID) == true && $GameType != 3){

        if($this->isBoardCustomerSettingDragDrop($player_id)){
          echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_231")." <input type='hidden' name='txtmovefrom' class='post' size='3'><input type='hidden' name='txtmoveto' class='post' size='3'> <input type='hidden' name='cmdMove' value=''>&nbsp;";
        }else{
          echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_230")." <input type='text' name='txtmovefrom' class='post' size='3'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_6")." <input type='text' name='txtmoveto' class='post' size='3'> <input type='submit' name='cmdMove' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_PM")."' class='mainoption'>&nbsp;";
        }

      }

    }

    if($Gamestatus == "I"){
      echo "<input type='submit' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption' onclick=\"return (confirm('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_233")."') == true)? true : false\" >";
      echo "<input type='submit' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption' onclick=\"return (confirm('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_232")."') == true)? true : false\" >";
    }else{

      echo "<input type='button' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_1")."');\">";
      echo "<input type='button' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_2")."');\">";

    }
    if($ViewMode == 1 || $ViewMode == 2){
      echo "<input type='button' name='btnSavePGN' value='PGN/FEN' class='mainoption' onclick=\"javascript:PopupWindow('./view_PGN.php?gid=".$GameID."')\">";
    }

    echo "</td>";
    echo "</tr>";

    echo "</table>";

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top'>";

    if($this->isBoardCustomerSettingDragDrop($player_id)){

      if($GameType == 3 && $this->IsRequestRealTime($ConfigFile, $GameID, $isblack) != "IDS_REAL_TIME"){

        if($isblack){
          $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "b", false);
        }else{
          $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "w", false);
        }

      }else{

        if($isblack){
          $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "b", $this->IsPlayersTurn($ConfigFile, $player_id, $GameID));
        }else{
          $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "w", $this->IsPlayersTurn($ConfigFile, $player_id, $GameID));
        }

      }

    }else{

      if($isblack){
        $this->CreateChessBoard($fen, $clrl, $clrd, true, "b");
      }else{
        $this->CreateChessBoard($fen, $clrl, $clrd, true, "w");
      }

    }

    echo "<table width='325' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr>";
    echo "<td valign='top' colspan='2' class='row1'>";
    echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_10")."";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' colspan='2' class='row2' align='center'>";

    echo "<textarea cols='48' rows='15' class='post' readonly>";
    $this->GetGChat($ConfigFile, $GameID);
    echo "</textarea>";

    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td valign='top' colspan='2' class='row2' align='left'>";

    echo "<input type='text' name='txtChatMessage' class='post' size='35'> ";

    if($Gamestatus == "I"){
      echo "<input type='submit' name='cmdChat' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_SND")."' class='mainoption'>";
    }else{
      echo "<input type='button' name='btnChat' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_SND")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_3")."');\">";
    }

    echo "</td>";
    echo "</tr>";
    echo "</table>";


    echo "</td>";
    echo "<td valign='top' align='right'>";
    if($ViewMode == 1 || $ViewMode == 2){
      $this->GetGameMoveHistory($ConfigFile, $GameID, $sid);
    }
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * CreateChessBoardDragDrop
  *
  */
  function CreateChessBoardDragDrop($fen, $clrl, $clrd, $bJavaScript, $BoardSetup, $isturn){

    $playerturn = 0;
    $bJavaScript = false;

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight ) = explode("/", substr(str_replace(strrchr($fen, "/"), "", $fen),10), 8);

    if($isturn){
      $playerturn = 1;
    }

    // Skin config
    if(defined('CFG_CHESSBOARD_TABLE_SIZE')){
      echo "<table border='0' width='".CFG_CHESSBOARD_TABLE_SIZE."' cellpadding='0' cellspacing='0' align='center' class='forumline'>";
    }else{
      echo "<table border='0' width='325' cellpadding='0' cellspacing='0' align='center' class='forumline'>";
    }

    if($BoardSetup == "w" || $BoardSetup = ""){
      echo "<tr><td class='row1' align='center'>-</td><td width='36' class='row1' align='center'>a</td><td width='36' class='row1' align='center'>b</td><td width='36' class='row1' align='center'>c</td><td width='36' class='row1'  align='center'>d</td><td width='36' class='row1' align='center'>e</td><td width='36' class='row1' align='center'>f</td><td width='36' class='row1' align='center'>g</td><td width='36' class='row1' align='center'>h</td></tr>";

      echo "<tr><td height='36' class='row1'>8</td><td colSpan='9' rowSpan='8' class='row2'>";

      if($isturn){
        $strTextCode = $this->GetChessboardDragDropText($fen, 'w');

        // Skin config
        if(defined('CFG_CHESSBOARD_IFRAME_SIZE')){
          echo "<iframe src='./inc_chessboard.php?turn=".$playerturn."&color=w&scode=".$strTextCode."' width='".CFG_CHESSBOARD_IFRAME_SIZE."' height='".CFG_CHESSBOARD_IFRAME_SIZE."' scrolling='no'></iframe>";
        }else{
          echo "<iframe src='./inc_chessboard.php?turn=".$playerturn."&color=w&scode=".$strTextCode."' width='309' height='309' scrolling='no'></iframe>";
        }

      }else{

        //Create the chess board
        echo "<table border='1' cellpadding='0' cellspacing='0' align='center'>";
        $this->GenChessBoardRows($eight, 0, $clrl, $clrd, 8, $bJavaScript);
        $this->GenChessBoardRows($seven, 1, $clrl, $clrd, 7, $bJavaScript);
        $this->GenChessBoardRows($six, 0, $clrl, $clrd, 6, $bJavaScript);
        $this->GenChessBoardRows($five, 1, $clrl, $clrd, 5, $bJavaScript);
        $this->GenChessBoardRows($four, 0, $clrl, $clrd, 4, $bJavaScript);
        $this->GenChessBoardRows($three, 1, $clrl, $clrd, 3, $bJavaScript);
        $this->GenChessBoardRows($two, 0, $clrl, $clrd, 2, $bJavaScript);
        $this->GenChessBoardRows($one, 1, $clrl, $clrd, 1, $bJavaScript);
        echo "</table>";

      }

      echo "</td></tr>";
      echo "<tr><td height='36' class='row1'>7</td></tr>";
      echo "<tr><td height='36' class='row1'>6</td></tr>";
      echo "<tr><td height='36' class='row1'>5</td></tr>";
      echo "<tr><td height='36' class='row1'>4</td></tr>";
      echo "<tr><td height='36' class='row1'>3</td></tr>";
      echo "<tr><td height='36' class='row1'>2</td></tr>";
      echo "<tr><td height='36' class='row1'>1</td></tr>";

    }else{
      echo "<tr><td class='row1' align='center'>-</td><td width='36' class='row1' align='center'>h</td><td width='36' class='row1' align='center'>g</td><td width='36' class='row1' align='center'>f</td><td width='36' class='row1'  align='center'>e</td><td width='36' class='row1' align='center'>d</td><td width='36' class='row1' align='center'>c</td><td width='36' class='row1' align='center'>b</td><td width='36' class='row1' align='center'>a</td></tr>";

      echo "<tr><td height='36' class='row1'>1</td><td colSpan='9' rowSpan='8' class='row2'>";

      if($isturn){
        $strTextCode = $this->GetChessboardDragDropText($fen, 'b');

        // Skin config
        if(defined('CFG_CHESSBOARD_IFRAME_SIZE')){
          echo "<iframe src='./inc_chessboard.php?turn=".$playerturn."&color=b&scode=".$strTextCode."' width='".CFG_CHESSBOARD_IFRAME_SIZE."' height='".CFG_CHESSBOARD_IFRAME_SIZE."' scrolling='no'></iframe>";
        }else{
          echo "<iframe src='./inc_chessboard.php?turn=".$playerturn."&color=b&scode=".$strTextCode."' width='309' height='309' scrolling='no'></iframe>";
        }

      }else{

        //Create the chess board
        echo "<table border='1' cellpadding='0' cellspacing='0' align='center'>";
        $this->GenChessBoardRows2($one, 0, $clrl, $clrd, 1, $bJavaScript);
        $this->GenChessBoardRows2($two, 1, $clrl, $clrd, 2, $bJavaScript);
        $this->GenChessBoardRows2($three, 0, $clrl, $clrd, 3, $bJavaScript);
        $this->GenChessBoardRows2($four, 1, $clrl, $clrd, 4, $bJavaScript);
        $this->GenChessBoardRows2($five, 0, $clrl, $clrd, 5, $bJavaScript);
        $this->GenChessBoardRows2($six, 1, $clrl, $clrd, 6, $bJavaScript);
        $this->GenChessBoardRows2($seven, 0, $clrl, $clrd, 7, $bJavaScript);
        $this->GenChessBoardRows2($eight, 1, $clrl, $clrd, 8, $bJavaScript);
        echo "</table>";

      }

      echo "</td></tr>";
      echo "<tr><td height='36' class='row1'>2</td></tr>";
      echo "<tr><td height='36' class='row1'>3</td></tr>";
      echo "<tr><td height='36' class='row1'>4</td></tr>";
      echo "<tr><td height='36' class='row1'>5</td></tr>";
      echo "<tr><td height='36' class='row1'>6</td></tr>";
      echo "<tr><td height='36' class='row1'>7</td></tr>";
      echo "<tr><td height='36' class='row1'>8</td></tr>";

    }

    echo "</table>";

  }


  /**********************************************************************
  * GetChessboardDragDropText
  *
  */
  function GetChessboardDragDropText($fen, $color){

    $nImageSize = 38;
    if(defined('CFG_CHESSBOARD_IMG_SIZE')){
      $nImageSize = CFG_CHESSBOARD_IMG_SIZE;
    }

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight ) = explode("/", substr(str_replace(strrchr($fen, "/"), "", $fen),10), 8);

    $strTextCode = "";

    if($color == 'w'){

      $strTextCode = $strTextCode."".$this->DecodeDragDrop($eight, "".($nImageSize * 0)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($seven, "".($nImageSize * 1)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($six, "".($nImageSize * 2)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($five, "".($nImageSize * 3)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($four, "".($nImageSize * 4)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($three, "".($nImageSize * 5)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($two, "".($nImageSize * 6)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop($one, "".($nImageSize * 7)."");

    }else{

      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($one, "".($nImageSize * 0)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($two, "".($nImageSize * 1)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($three, "".($nImageSize * 2)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($four, "".($nImageSize * 3)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($five, "".($nImageSize * 4)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($six, "".($nImageSize * 5)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($seven, "".($nImageSize * 6)."");
      $strTextCode = $strTextCode."".$this->DecodeDragDrop2($eight, "".($nImageSize * 7)."");

    }

    return $strTextCode;

  }


  /**********************************************************************
  * DecodeDragDrop2
  *
  */
  function DecodeDragDrop2($rowfen, $rownum){

    $nImageSize = 38;
    if(defined('CFG_CHESSBOARD_IMG_SIZE')){
      $nImageSize = CFG_CHESSBOARD_IMG_SIZE;
    }

    // Vars used for the function
    $strCode = "";

    $LENGTH = strlen(trim($rowfen)); // Get the length of the string
    $pos = 0; // Set the initial position of the row

    $ncolcount = 1;

    //Decode Row
    if($rowfen != ""){

       while($pos < $LENGTH){

         //Get the position of the chess piece
         $char = substr($rowfen, $pos, 1);

         // Check if the char is numeric
         if(is_numeric($char) == TRUE){

           // Create the emply board spaces
           $count = (int) $char;
           $i = 0;

           while($i < $count){

             // do nothing
             $ncolcount++;

             $i++;
           }

         }else{

           // get the piece number
           $strPiece = "";
           switch($char){

             case 'R':
               $strPiece = "6";
               break;

             case 'N':
               $strPiece = "3";
               break;

             case 'B':
               $strPiece = "1";
               break;

             case 'Q':
               $strPiece = "5";
               break;

             case 'K':
               $strPiece = "2";
               break;

             case 'P':
               $strPiece = "4";
               break;

             case 'r':
               $strPiece = "12";
               break;

             case 'n':
               $strPiece = "9";
               break;

             case 'b':
               $strPiece = "7";
               break;

             case 'q':
               $strPiece = "11";
               break;

             case 'k':
               $strPiece = "8";
               break;

             case 'p':
               $strPiece = "10";
               break;

           }

           // get the col number
           $strx = "";
           switch($ncolcount){

             case 8:
               $stry = "".($nImageSize * 0)."";
               break;

             case 7:
               $stry = "".($nImageSize * 1)."";
               break;

             case 6:
               $stry = "".($nImageSize * 2)."";
               break;

             case 5:
               $stry = "".($nImageSize * 3)."";
               break;

             case 4:
               $stry = "".($nImageSize * 4)."";
               break;

             case 3:
               $stry = "".($nImageSize * 5)."";
               break;

             case 2:
               $stry = "".($nImageSize * 6)."";
               break;

             case 1:
               $stry = "".($nImageSize * 7)."";
               break;

           }

           $strCode = $strCode."".$strPiece."|".$stry."|".$rownum."_";

           $ncolcount++;

         }

         $pos++;

       }

    }

    return $strCode;

  }


  /**********************************************************************
  * DecodeDragDrop
  *
  */
  function DecodeDragDrop($rowfen, $rownum){

    $nImageSize = 38;
    if(defined('CFG_CHESSBOARD_IMG_SIZE')){
      $nImageSize = CFG_CHESSBOARD_IMG_SIZE;
    }

    // Vars used for the function
    $strCode = "";

    $LENGTH = strlen(trim($rowfen)); // Get the length of the string
    $pos = 0; // Set the initial position of the row

    $ncolcount = 1;

    //Decode Row
    if($rowfen != ""){

       while($pos < $LENGTH){

         //Get the position of the chess piece
         $char = substr($rowfen, $pos, 1);

         // Check if the char is numeric
         if(is_numeric($char) == TRUE){

           // Create the emply board spaces
           $count = (int) $char;
           $i = 0;

           while($i < $count){

             // do nothing
             $ncolcount++;

             $i++;
           }

         }else{

           // get the piece number
           $strPiece = "";
           switch($char){

             case 'R':
               $strPiece = "6";
               break;

             case 'N':
               $strPiece = "3";
               break;

             case 'B':
               $strPiece = "1";
               break;

             case 'Q':
               $strPiece = "5";
               break;

             case 'K':
               $strPiece = "2";
               break;

             case 'P':
               $strPiece = "4";
               break;

             case 'r':
               $strPiece = "12";
               break;

             case 'n':
               $strPiece = "9";
               break;

             case 'b':
               $strPiece = "7";
               break;

             case 'q':
               $strPiece = "11";
               break;

             case 'k':
               $strPiece = "8";
               break;

             case 'p':
               $strPiece = "10";
               break;

           }

           // get the col number
           $strx = "";
           switch($ncolcount){

             case 1:
               $stry = "".($nImageSize * 0)."";
               break;

             case 2:
               $stry = "".($nImageSize * 1)."";
               break;

             case 3:
               $stry = "".($nImageSize * 2)."";
               break;

             case 4:
               $stry = "".($nImageSize * 3)."";
               break;

             case 5:
               $stry = "".($nImageSize * 4)."";
               break;

             case 6:
               $stry = "".($nImageSize * 5)."";
               break;

             case 7:
               $stry = "".($nImageSize * 6)."";
               break;

             case 8:
               $stry = "".($nImageSize * 7)."";
               break;

           }

           $strCode = $strCode."".$strPiece."|".$stry."|".$rownum."_";

           $ncolcount++;

         }

         $pos++;

       }

    }

    return $strCode;

  }


  /**********************************************************************
  * SavePGN
  *
  */
  function SavePGN($ConfigFile, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    return $PGN;

  }


  /**********************************************************************
  * SavePGN2
  *
  */
  function SavePGN2($ConfigFile, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list2($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    return $PGN;

  }


  /**********************************************************************
  * GetPrevGameStatus
  *
  */
  function GetPrevGameStatus($ConfigFile, $GameID, $sid, $player_id, $idcount, $clrl, $clrd){

    $nmoveid = $this->GetMoveHistoryFirstMoveid($ConfigFile, $GameID);
    $nidcount = (int)$idcount - 1;
    $movedate = $this->GetMoveDateByID($ConfigFile, $GameID, $nidcount);

    $fen = $this->GetFEN2($sid, $GameID, $movedate);

    echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";
    echo "<tr>";

    if($this->IsPlayerBlack($ConfigFile, $GameID, $player_id)){

      echo "<td valign='top'>";
      $this->CreateChessBoard($fen, $clrl, $clrd, false, "b");
      echo "</td>";

    }else{

      echo "<td valign='top'>";
      $this->CreateChessBoard($fen, $clrl, $clrd, false, "w");
      echo "</td>";

    }

    echo "<td valign='top'>";
    $this->GetGameMoveHistory($ConfigFile, $GameID, $sid);
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td colspan='2' valign='top'>";
    $this->GetCurrentGameInfoByGameID($ConfigFile, $GameID, $fen);
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * CurrentGameMove
  *
  */
  function CurrentGameMove(){

    echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr>";
    echo "<td valign='top' class='row1'>";
    echo "<font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_11")."</font>";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top' class='row2'>";
    echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_5")." <input type='text' name='txtmovefrom' class='post' size='15'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_6")." <input type='text' name='txtmoveto' class='post' size='15'> <input type='submit' name='cmdMove' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_PM")."' class='mainoption'>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * CurrentGameMovePiece
  *
  */
  function CurrentGameMovePiece($ConfigFile, $GameID, $SID, $PID, $Move){

    $returncode = "";

    // Delete previous moves from inbox (other player);
    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $status = "";
    $completion_status = "";
    $start_time = "";
    $next_move = "";

    $this->GetGameInfoByRef($ConfigFile, $GameID, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

    if($w_player_id != $PID){
      $this->PurgeOldMovesFromInbox($ConfigFile, $w_player_id, $GameID);
    }

    if($b_player_id != $PID){
      $this->PurgeOldMovesFromInbox($ConfigFile, $b_player_id, $GameID);
    }

	// Look for game timeouts now. Ideally should only check if this specific game has timed out.
	$this->MangeGameTimeOuts();
    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->process_move($this->ChessCFGFileLocation, $PID, $GameID, $Move);
    unset($oChess);

    return $returncode;
  }


  /**********************************************************************
  * ChessBoardColors
  *
  */
  function ChessBoardColors($clrl, $clrd){

    echo "<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>";
    echo "<tr>";
    echo "<td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_12")."</font><b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_13")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_14")."</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td bgcolor='".$clrl."'>&nbsp;<br></td><td bgcolor='".$clrd."'>&nbsp;<br></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top' class='row2'>";

    echo "<input type='text' name='txtl' value='".$clrl."' class='post' size='18' >";
    echo " <A HREF=\"#\" onClick=\"cp.select(document.forms[0].txtl,'pick');return false;\" NAME=\"pick\" ID=\"pick\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_15")."</a>";

    echo "</td>";
    echo "<td valign='top' class='row2'>";

    echo "<input type='text' name='txtd' value='".$clrd."' class='post' size='18' >";
    echo " <A HREF=\"#\" onClick=\"cp.select(document.forms[0].txtd,'pick');return false;\" NAME=\"pick\" ID=\"pick\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_15")."</a>";

    echo "</td>";
    echo "</tr>";
    echo "<td colspan='2' valign='top' class='row1' align='right'>";
    echo "<input type='submit' name='cmdchgcolor' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_CNGCLR")."' class='mainoption'>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * GetChessBoardColors
  *
  */
  function GetChessBoardColors($ConfigFile, $player_id, &$clrl, &$clrd){

    $query = "SELECT * FROM c4m_chessboardcolors WHERE player_id = ".$player_id;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $clrl = trim(mysql_result($return, 0, "cc_lcolor"));
      $clrd = trim(mysql_result($return, 0, "cc_dcolor"));

    }else{

      if($player_id != ""){

        $Insert = "INSERT INTO c4m_chessboardcolors VALUES(NULL, $player_id, '#957A01', '#FFFFFF')";
        mysql_query($Insert, $this->link) or die(mysql_error());

        $clrl = "#FFFFFF";
        $clrd = "#957A01";

      }

    }

  }


  /**********************************************************************
  * UpdateChessBoardColors
  *
  */
  function UpdateChessBoardColors($ConfigFile, $player_id, $clrl, $clrd){

    $query = "UPDATE c4m_chessboardcolors SET cc_dcolor = '".$clrd."', cc_lcolor = '".$clrl."' WHERE player_id = ".$player_id;
    mysql_query($query, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetMoveHistoryFirstMoveid
  *
  */
  function GetMoveHistoryFirstMoveid($ConfigFile, $GameID){

    $move_id = 0;

    //Who made the first move
    $query1 = "SELECT * FROM move_history WHERE game_id = '".$GameID."' ORDER BY move_id ASC";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $move_id = trim(mysql_result($return1, 0, "move_id"));
    }

    return $move_id;

  }


  /**********************************************************************
  * GetMoveDateByID
  *
  */
  function GetMoveDateByID($ConfigFile, $GameID, $moveidcount){

    $time = 0;

    //Who made the first move
    $query1 = "SELECT * FROM move_history WHERE game_id = '".$GameID."' ORDER BY move_id ASC";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $i = 0;
      $MAX = $moveidcount+1;
      while($i < $MAX){
        $time = trim(mysql_result($return1, $moveidcount, "time"));
        $i++;
      }

    }

    return $time;

  }


  /**********************************************************************
  * GetFirstMovePlayer
  *
  */
  function GetFirstMovePlayer($ConfigFile, $GameID, &$Player, &$color){

    $playerid = 0;

    //Who made the first move
    $query1 = "SELECT * FROM move_history WHERE game_id = '".$GameID."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $playerid = mysql_result($return1, 0, "player_id");
      $Player = $this->GetUserIDByPlayerID($ConfigFile, $playerid);

    }

    //Get game info
    $query2 = "SELECT * FROM game WHERE game_id = '".$GameID."'";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $wbplayerid = mysql_result($return2, 0, "w_player_id");
      $bplayerid = mysql_result($return2, 0, "b_player_id");

      if($playerid == $wbplayerid){
        $color = "w";
      }else{
        $color = "b";
      }

    }

  }


  /**********************************************************************
  * ParsePGNMove
  *
  */
  function ParsePGNMove($PGN, &$aTags, &$aPGNMoves){

    //Remove the first few bytes of the pgn (garbage)
    $pos = strpos($PGN, "[");
    $len = strlen($PGN);

    //Remove the junk
    $PGNFiltered = substr($PGN, $pos);

    //split the $PGN text and place it in an array
    $line = explode("\n", $PGNFiltered);

    // break down the items of the pgn and place it in the appropriate array
    foreach($line as $item){

      //Check if item is a tag
      if(trim(substr($item, 0, 1)) == "["){
        //echo $item."<br>";
        array_push($aTags, $item);

      }

      //Check if the item is not blank a space or [
      if($item != "" && $item != " " && trim(substr($item, 0, 1)) != "["){
        //echo $item."<br>";
        array_push($aPGNMoves, $item);

      }

    }

  }


  /**********************************************************************
  * GetChessPieceImage
  *
  */
  function GetChessPieceImage($chessPieceType){

    $image = "";

    switch($chessPieceType){
      case 'p':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/06.gif' border='0'>";
        break;
      case 'r':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/03.gif' border='0'>";
        break;
      case 'b':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/04.gif' border='0'>";
        break;
      case 'q':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/02.gif' border='0'>";
        break;
      case 'k':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/01.gif' border='0'>";
        break;
      case 'n':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/05.gif' border='0'>";
        break;
      case 'P':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/12.gif' border='0'>";
        break;
      case 'R':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/09.gif' border='0'>";
        break;
      case 'B':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/10.gif' border='0'>";
        break;
      case 'Q':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/08.gif' border='0'>";
        break;
      case 'K':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/07.gif' border='0'>";
        break;
      case 'N':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/11.gif' border='0'>";
        break;
      case 'blnk':
        $image = "<img src='./skins/".$this->SkinsLocation."/images/chess/blank.gif' border='0'>";
        break;
    }

    return $image;

  }


  /**********************************************************************
  * GenChessBoardRows
  *
  */
  function GenChessBoardRows($rowfen, $SQColorid, $clrl, $clrd, $RowID, $bJavaScript){

    // Vars used for the function
    $LENGTH = strlen(trim($rowfen)); // Get the length of the string
    $pos = 0; // Set the initial position of the row

    $ncolcount = 1;

    $colorswitcher = $SQColorid;

    $color1 = $clrl;
    $color2 = $clrd;
    $color = "";

    //Decode Row
    if($rowfen != ""){

       echo "<tr>";

       while($pos < $LENGTH){

         //Get the position of the chess piece
         $char = substr($rowfen, $pos, 1);

         // Check if the char is numeric
         if(is_numeric($char) == TRUE){

           // Create the emply board spaces
           $count = (int) $char;
           $i = 0;

           while($i < $count){

             //color manip
             if($colorswitcher == 0){
               $color = $color1;
             }else{
               $color = $color2;
             }

             $colorswitcher++;

             if($colorswitcher > 1){
               $colorswitcher = 0;
             }

             if($bJavaScript == false){
               echo "<td id=\"".$RowID."-".$ncolcount."\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim("blnk"))."</td>";
             }else{
               echo "<td id=\"".$RowID."-".$ncolcount."\" onClick=\"javascript:ProcessMove(this.id);\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim("blnk"))."</td>";
             }

             $ncolcount++;

             $i++;
           }

         }else{

           //color manip
           if($colorswitcher == 0){
             $color = $color1;
           }else{
             $color = $color2;
           }

           $colorswitcher++;

           if($colorswitcher > 1){
             $colorswitcher = 0;
           }

           // Place the piece on the row
           if($bJavaScript == false){
             echo "<td id=\"".$RowID."-".$ncolcount."\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim($char))."</td>";
           }else{
             echo "<td id=\"".$RowID."-".$ncolcount."\" onClick=\"javascript:ProcessMove(this.id);\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim($char))."</td>";
           }

           $ncolcount++;
         }

         $pos++;
       }

       echo "</tr>\n";
    }

  }


  /**********************************************************************
  * GenChessBoardReverseCol
  *
  */
  function GenChessBoardReverseCol($Col){

    $newcol = 1;

    switch($Col){

      case 8:
        $newcol = 1;
        break;

      case 7:
        $newcol = 2;
        break;

      case 6:
        $newcol = 3;
        break;

      case 5:
        $newcol = 4;
        break;

      case 4:
        $newcol = 5;
        break;

      case 3:
        $newcol = 6;
        break;

      case 2:
        $newcol = 7;
        break;

      case 1:
        $newcol = 8;
        break;
    }

    return $newcol;

  }


  /**********************************************************************
  * GenChessBoardRows2
  *
  */
  function GenChessBoardRows2($rowfen, $SQColorid, $clrl, $clrd, $RowID, $bJavaScript){

    // Vars used for the function
    $LENGTH = strlen(trim($rowfen)); // Get the length of the string
    $pos = 0; // Set the initial position of the row

    $ncolcount = 1;

    $colorswitcher = $SQColorid;

    $color1 = $clrl;
    $color2 = $clrd;
    $color = "";

    $rowfen = strrev($rowfen);

    //Decode Row
    if($rowfen != ""){

       echo "<tr>";

       while($pos < $LENGTH){

         //Get the position of the chess piece
         $char = substr($rowfen, $pos, 1);

         // Check if the char is numeric
         if(is_numeric($char) == TRUE){

           // Create the emply board spaces
           $count = (int) $char;
           $i = 0;

           while($i < $count){

             //color manip
             if($colorswitcher == 0){
               $color = $color1;
             }else{
               $color = $color2;
             }

             $colorswitcher++;

             if($colorswitcher > 1){
               $colorswitcher = 0;
             }

             if($bJavaScript == false){
               echo "<td id=\"".$RowID."-".$this->GenChessBoardReverseCol($ncolcount)."\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim("blnk"))."</td>";
             }else{
               echo "<td id=\"".$RowID."-".$this->GenChessBoardReverseCol($ncolcount)."\" onClick=\"javascript:ProcessMove(this.id);\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim("blnk"))."</td>";
             }

             $ncolcount++;

             $i++;
           }

         }else{

           //color manip
           if($colorswitcher == 0){
             $color = $color1;
           }else{
             $color = $color2;
           }

           $colorswitcher++;

           if($colorswitcher > 1){
             $colorswitcher = 0;
           }

           // Place the piece on the row
           if($bJavaScript == false){
             echo "<td id=\"".$RowID."-".$this->GenChessBoardReverseCol($ncolcount)."\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim($char))."</td>";
           }else{
             echo "<td id=\"".$RowID."-".$this->GenChessBoardReverseCol($ncolcount)."\" onClick=\"javascript:ProcessMove(this.id);\" bgcolor='".$color."' width='36' height='36'>".$this->GetChessPieceImage(trim($char))."</td>";
           }

           $ncolcount++ ;
         }

         $pos++;
       }

       echo "</tr>\n";
    }

  }


  /**********************************************************************
  * CreateChessBoard
  *
  */
  function CreateChessBoard($fen, $clrl, $clrd, $bJavaScript, $BoardSetup){

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight ) = explode("/", substr(str_replace(strrchr($fen, "/"), "", $fen),10), 8);

    echo "<table border='0' width='325' cellpadding='0' cellspacing='0' align='center' class='forumline'>";

    if($BoardSetup == "w" || $BoardSetup = ""){
    echo "<tr><td class='row1' align='center'>-</td><td width='36' class='row1' align='center'>a</td><td width='36' class='row1' align='center'>b</td><td width='36' class='row1' align='center'>c</td><td width='36' class='row1'  align='center'>d</td><td width='36' class='row1' align='center'>e</td><td width='36' class='row1' align='center'>f</td><td width='36' class='row1' align='center'>g</td><td width='36' class='row1' align='center'>h</td></tr>";

      echo "<tr><td height='36' class='row1'>8</td><td colSpan='9' rowSpan='8' class='row2'>";

      //Create the chess board
      echo "<table border='1' cellpadding='0' cellspacing='0' align='center'>";
      $this->GenChessBoardRows($eight, 0, $clrl, $clrd, 8, $bJavaScript);
      $this->GenChessBoardRows($seven, 1, $clrl, $clrd, 7, $bJavaScript);
      $this->GenChessBoardRows($six, 0, $clrl, $clrd, 6, $bJavaScript);
      $this->GenChessBoardRows($five, 1, $clrl, $clrd, 5, $bJavaScript);
      $this->GenChessBoardRows($four, 0, $clrl, $clrd, 4, $bJavaScript);
      $this->GenChessBoardRows($three, 1, $clrl, $clrd, 3, $bJavaScript);
      $this->GenChessBoardRows($two, 0, $clrl, $clrd, 2, $bJavaScript);
      $this->GenChessBoardRows($one, 1, $clrl, $clrd, 1, $bJavaScript);
      echo "</table>";

      echo "</td></tr>";
      echo "<tr><td height='36' class='row1'>7</td></tr>";
      echo "<tr><td height='36' class='row1'>6</td></tr>";
      echo "<tr><td height='36' class='row1'>5</td></tr>";
      echo "<tr><td height='36' class='row1'>4</td></tr>";
      echo "<tr><td height='36' class='row1'>3</td></tr>";
      echo "<tr><td height='36' class='row1'>2</td></tr>";
      echo "<tr><td height='36' class='row1'>1</td></tr>";

    }else{
      echo "<tr><td class='row1' align='center'>-</td><td width='36' class='row1' align='center'>h</td><td width='36' class='row1' align='center'>g</td><td width='36' class='row1' align='center'>f</td><td width='36' class='row1'  align='center'>e</td><td width='36' class='row1' align='center'>d</td><td width='36' class='row1' align='center'>c</td><td width='36' class='row1' align='center'>b</td><td width='36' class='row1' align='center'>a</td></tr>";
      echo "<tr><td height='36' class='row1'>1</td><td colSpan='9' rowSpan='8' class='row2'>";

      //Create the chess board
      echo "<table border='1' cellpadding='0' cellspacing='0' align='center'>";
      $this->GenChessBoardRows2($one, 0, $clrl, $clrd, 1, $bJavaScript);
      $this->GenChessBoardRows2($two, 1, $clrl, $clrd, 2, $bJavaScript);
      $this->GenChessBoardRows2($three, 0, $clrl, $clrd, 3, $bJavaScript);
      $this->GenChessBoardRows2($four, 1, $clrl, $clrd, 4, $bJavaScript);
      $this->GenChessBoardRows2($five, 0, $clrl, $clrd, 5, $bJavaScript);
      $this->GenChessBoardRows2($six, 1, $clrl, $clrd, 6, $bJavaScript);
      $this->GenChessBoardRows2($seven, 0, $clrl, $clrd, 7, $bJavaScript);
      $this->GenChessBoardRows2($eight, 1, $clrl, $clrd, 8, $bJavaScript);
      echo "</table>";

      echo "</td></tr>";
      echo "<tr><td height='36' class='row1'>2</td></tr>";
      echo "<tr><td height='36' class='row1'>3</td></tr>";
      echo "<tr><td height='36' class='row1'>4</td></tr>";
      echo "<tr><td height='36' class='row1'>5</td></tr>";
      echo "<tr><td height='36' class='row1'>6</td></tr>";
      echo "<tr><td height='36' class='row1'>7</td></tr>";
      echo "<tr><td height='36' class='row1'>8</td></tr>";

    }

    echo "</table>";

  }


  /**********************************************************************
  * GetCurrentGameInfoByGameID
  *
  */
  function GetCurrentGameInfoByGameID($ConfigFile, $ID, $FEN){

    //Get game info
    $query = "SELECT * FROM game WHERE game_id = '".$ID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $game_id = trim(mysql_result($return,$i,"game_id"));
        $initiator = trim(mysql_result($return,$i,"initiator"));
        $w_player_id = trim(mysql_result($return,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($return,$i,"b_player_id"));
        $next_move = trim(mysql_result($return,$i,"next_move"));
        $start_time = trim(mysql_result($return,$i,"start_time"));

        echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
        echo "<tr><td class='row1' colspan='1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_16")." </td><td class='row2' colspan='3'>".date("m-d-Y",$start_time)."</td></tr>";

        echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_17")."</td><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_18")."</td></tr>";

        if($this->IsPlayerOnline($ConfigFile, $w_player_id)){
          echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td><td class='row2'><font color='green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_19")."</font></td>";

        }else{
          echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td><td class='row2'><font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_20")."</font></td>";
        }

        if($this->IsPlayerOnline($ConfigFile, $b_player_id)){
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td><td class='row2'><font color='green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_19")."</font></td></tr>";

        }else{
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td><td class='row2'><font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_20")."</font></td></tr>";
        }

        if($FEN != ""){
          echo "<tr><td class='row1' colspan='4'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_21")." </td></tr>";
          echo "<tr><td class='row2' colspan='4'><span class='gensmall' style='word-wrap: break-word;'>".substr($FEN,10)."</span></td></tr>";
          echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_22")." </td><td class='row2' colspan='3'><input type='button' name='btnSavePGN' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_SVPGN")."' class='mainoption' onclick=\"javascript:PopupWindow('./view_PGN.php?gid=".$game_id."')\"></td></tr>";
        }

        echo "</table>";

        $i++;
      }

    }

  }


  /**********************************************************************
  * GetGameMoveHistory
  *
  */
  function GetGameMoveHistory($ConfigFile, $GameID, $sid){

    // Set up the move table
    $aTags = array();
    $aPGNMoves = array();

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $this->ParsePGNMove($PGN, $aTags, $aPGNMoves);

    $Player = "";
    $color = "";

    $this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);

    echo "<table  border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td colspan='3' class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_26");
    echo " <input type='button' name='cmdReplay' value='R' class='mainoption' onclick=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$GameID."');\">";
    echo "</td></tr>";

    //Check who made the first move
    if($Player != ""){

      if($color == "w"){
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_23")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_24")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_25")."</td></tr>";
      }else{
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_23")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_25")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_24")."</td></tr>";
      }

    }else{
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_23")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_24")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_25")."</td></tr>";
    }

    if(isset($aPGNMoves)){

       $PGNMoveList= explode(" ", $aPGNMoves[0]);
       $itemcount = count($PGNMoveList);

       $rowswitch = 0;
       $rowstart = true;
       $itemcounter = 0;

       $mod = $itemcount % 3;

       $idcount = 0;
       $idswitch = 0;

       foreach($PGNMoveList as $item1){

         $itemcounter++;
         $idswitch++;

         if($rowstart == true){
           echo "\n<tr>";
           $rowstart = false;
         }

         if($rowswitch == 3){
           echo "</tr>\n<tr>";
           $rowswitch = 0;
         }

         if($idswitch > 3){
           $idswitch = 1;
         }

         if($mod != 0 && $itemcounter == $itemcount){
           echo "<td class='row2' colspan='2'>$item1</td>";
         }else{

           if($idswitch == 2 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
             $idcount++;
             echo "<td class='row2'><a href='./chess_game.php?gameid=".$GameID."&idc=".$idcount."'>".$item1."</a></td>";

           }else{
             if($idswitch == 3 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
               $idcount++;
               echo "<td class='row2'><a href='./chess_game.php?gameid=".$GameID."&idc=".$idcount."'>".$item1."</a></td>";

             }else{

               echo "<td class='row2'>".$item1."</td>";
             }

           }

         }

         $rowswitch++;

         if($itemcounter == $itemcount){
           echo "</tr>\n";
         }

       }

    }

    echo "</table>";

  }


  /**********************************************************************
  * ListAvailablePlayersA
  *
  */
  function ListAvailablePlayersA($ConfigFile){

    //$aLetters = array('','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z');

    $aLetters = array($this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_1"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_2"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28"));

    $aLettersCount = count($aLetters);

    $a=0;

    echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_27")."</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_31")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    while($a < $aLettersCount){

      // Set up the query string
      if($aLetters[$a] != "" && ucwords($aLetters[$a]) != ""){
        $query = "SELECT * FROM player WHERE (LEFT(userid,1) = '".$aLetters[$a]."' OR LEFT(userid,1) = '".ucwords($aLetters[$a])."') ORDER BY userid ASC";
      }else{

        $query = "SELECT * FROM player
                  WHERE (LEFT(userid,1)
                  NOT IN('".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28")."')
                  OR LEFT(userid,1)
                  NOT IN('".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28")."'))
                  ORDER BY userid ASC";

      }

      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      // Place the results in the array
      if($num != 0){

        $PlayerPoints = array();

        $i = 0;
        $ia = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          if($this->IsPlayerDisabled($player_id) == false){
            $wins = 0;
            $loss = 0;
            $draws = 0;

            $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);

            if($this->ELOIsActive()){
              $points = $this->ELOGetRating($player_id);
            }else{
              $points = $this->GetPointValue($wins, $loss, $draws);
            }

            $this->GetPointRanking($points, $wins);

            $PlayerPoints[$ia]['PlayerID'] = $player_id;
            $PlayerPoints[$ia]['UserID'] = $userid;
            $PlayerPoints[$ia]['SignUpTime'] = $signup_time;
            $PlayerPoints[$ia]['Email'] = $email;
            $PlayerPoints[$ia]['Points'] = $points;

            $ia++;

          }

          $i++;

        }

        if(count($PlayerPoints) != 0){

          // Sort the array
          $PlayerPoints = $this->array_csort($PlayerPoints,'Points',SORT_DESC, SORT_NUMERIC);

          echo "<tr><td class='row1' colspan='6'>".ucwords($aLetters[$a])."</td></tr>";

          $ncount = count($PlayerPoints);
          $ii = 0;

          while($ii < $ncount){

            echo "<tr>";
            echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$PlayerPoints[$ii]['PlayerID']."&name=".$PlayerPoints[$ii]['UserID']."'>".$PlayerPoints[$ii]['UserID']."</a></td>";
            echo "<td class='row2'>".date("m-d-Y",$PlayerPoints[$ii]['SignUpTime'])."</td>";
            echo "<td class='row2'><a href='mailto:".$PlayerPoints[$ii]['Email']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";

            echo "<td class='row2'>".$PlayerPoints[$ii]['Points']."</td>";

            echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$PlayerPoints[$ii]['PlayerID']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
            echo "<td class='row2'>";

            if($this->IsPlayerOnline($ConfigFile, $PlayerPoints[$ii]['PlayerID'])){
              echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
            }else{
              echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
            }

            echo "</td>";
            echo "</tr>";

            $ii++;

          }

        }

      }

      $a++;

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * ListAvailablePlayers
  *
  */
  function ListAvailablePlayers($ConfigFile){

    //Get game info
    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_27")."</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_31")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td></tr>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){

          echo "<tr>";
          echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
          echo "<td class='row2'>".date("m-d-Y",$signup_time)."</td>";
          echo "<td class='row2'><a href='mailto:".$email."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";
          echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
          echo "<td class='row2'>";

          if($this->IsPlayerOnline($ConfigFile, $player_id)){
            echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
          }else{
            echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
          }

          echo "</td>";
          echo "</tr>";

        }

        $i++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * ListAvailablePlayersEmail
  *
  */
  function ListAvailablePlayersEmail($ConfigFile){

    //Get game info
    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $aEmail = array();

    if($num != 0){

      $i = 0;
      $ii = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){
          $aEmail[$ii] = $email;
          $ii++;
        }

        $i++;

      }

    }

    return $aEmail;

  }


  /**********************************************************************
  * ListAvailablePlayers2
  *
  */
  function ListAvailablePlayers2($ConfigFile, $strSkinName, $strHTMLPage, $action, $index){

    $nMaxList = 20;

    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Check if the index is numeric
    if(!is_numeric($index)){
      $index = 1;
    }

    $nNumberOfPages = ceil($num / $nMaxList);
    $nPrevPage = $index - 1;
    $nNextPage = $index + 1;

    $nIndex = ($nMaxList * $index) - $nMaxList;

    if($nIndex < 0){
      $nIndex = 0;
    }

    // Skin table settings
    if(defined('CFG_LISTAVAILABLEPLAYERS2_TABLE1_WIDTH') && defined('CFG_LISTAVAILABLEPLAYERS2_TABLE1_BORDER') && defined('CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLPADDING') && defined('CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLSPACING') && defined('CFG_LISTAVAILABLEPLAYERS2_TABLE1_ALIGN')){
      echo "<table border='".CFG_LISTAVAILABLEPLAYERS2_TABLE1_BORDER."' align='".CFG_LISTAVAILABLEPLAYERS2_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLPADDING."' cellspacing='".CFG_LISTAVAILABLEPLAYERS2_TABLE1_CELLSPACING."' width='".CFG_LISTAVAILABLEPLAYERS2_TABLE1_WIDTH."'>";
    }else{
      echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_27")."</font></td></tr>";

    echo "<tr>";
    echo "<td class='row2' colspan='5'>";

    echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
    echo "<tr>";

    // Display the previous page arrow
    if($nPrevPage >= 1){
      echo "<td class='row2' align='left'><div align='left'><a href='./".$strHTMLPage."?action=bck&index=".$nPrevPage."'><img src='../skins/".$strSkinName."/images/back.gif' border='0'></a></div></td>";
    }else{
      echo "<td class='row2'></td>";
    }

    echo "<td class='row2'></td>";
    echo "<td class='row2'>";
    echo "<center>";

    // Display the page numbers
    $ix = 1;
    while($ix <= $nNumberOfPages){

      if($index == $ix){
        echo "[<a href='./".$strHTMLPage."?action=nxt&index=".$ix."'>$ix</a>] ";
      }else{
        echo "<a href='./".$strHTMLPage."?action=nxt&index=".$ix."'>$ix</a> ";
      }

      $ix++;

    }

    echo "</center>";
    echo "</td>";
    echo "<td class='row2'></td>";

    // Display the next page arrow
    if($nNextPage <= $nNumberOfPages){
      echo "<td class='row2' align='right'><div align='right'><a href='./".$strHTMLPage."?action=nxt&index=".$nNextPage."'><img src='../skins/".$strSkinName."/images/next.gif' border='0'></a></div></td>";
    }else{
      echo "<td class='row2'></td>";
    }

    echo "</tr>";
    echo "</table>";

    echo "</td>";
    echo "</tr>";

    echo "<tr><td class='row1'> - </td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    if($num != 0){

      $i = $nIndex;
      $ii = 0;
      while($i < $num && $ii < $nMaxList){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id)){

          echo "<tr>";
          echo "<td class='row2'><input type='radio' name='rdodlt' value='".$player_id."'></td>";
          echo "<td class='row2'><strike>".$userid."</strike></td>";
          echo "<td class='row2'><strike>".date("m-d-Y",$signup_time)."</strike></td>";
          echo "<td class='row2'><strike>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</strike></td>";
          echo "<td class='row2'>";
          echo "<strike>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</strike>";
          echo "</td>";
          echo "</tr>";

        }else{

          echo "<tr>";
          echo "<td class='row2'><input type='radio' name='rdodlt' value='".$player_id."'></td>";

          if($this->IsPlayerTournTemp($userid) == true){
            echo "<td class='row2'><img src='../images/'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
          }else{
            echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
          }

          echo "<td class='row2'>".date("m-d-Y",$signup_time)."</td>";
          echo "<td class='row2'><a href='mailto:".$email."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";
          echo "<td class='row2'>";

          if($this->IsPlayerOnline($ConfigFile, $player_id)){
            echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
          }else{
            echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
          }

          echo "</td>";
          echo "</tr>";

        }

        $i++;
        $ii++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * IsPlayerTournTemp
  *
  */
  function IsPlayerTournTemp($Name){

    //Get game info
    $query = "SELECT * FROM player3 WHERE userid='".$Name."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $isTemp = false;

    if($num != 0){
      $isTemp = true;
    }

    return $isTemp;

  }


  /**********************************************************************
  * GetAllPlayers
  *
  */
  function GetAllPlayers($ConfigFile, $nSelected=0){

    //Get game info
    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select name='slctUsers'>";
    echo "<option value='-'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_38")."</option>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){

          if($nSelected == $player_id){
            echo "<option value='".$player_id."' SELECTED>".$userid."</option>";
          }else{
            echo "<option value='".$player_id."'>".$userid."</option>";
          }

        }

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * IsPlayerOnline
  *
  */
  function IsPlayerOnline($ConfigFile, $ID){

    $bOnline = false;

    //Get game info
    $query = "SELECT * FROM active_sessions WHERE player_id =".$ID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bOnline = true;
    }

    return $bOnline;

  }


  /**********************************************************************
  * GetNewMessages
  *
  */
  function GetNewMessages($ConfigFile, $ID){

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE player_id =".$ID." ORDER BY msg_posted DESC LIMIT 5";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          $msg = substr($message, 10);
          LIST($name, $message) = explode("-", $msg);

          echo "<tr><td><font class='menubulletcolor'>&nbsp; &#8226; &nbsp<a href='./chess_msg_center.php?type=read&id=".$inbox_id."' class='menulinks'>".$name."</a></font> - ".date("m-d-Y",$posted)."</td></tr>";

        }

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);
          echo "<tr><td><font class='menubulletcolor'>&nbsp; &#8226; &nbsp<a href='./chess_msg_center.php?type=read&id=".$inbox_id."' class='menulinks'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></font> - ".date("m-d-Y",$posted)."</td></tr>";

        }

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $GameId = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          echo "<tr><td><font class='menubulletcolor'>&nbsp; &#8226; &nbsp<a href='./chess_msg_center.php?type=read&id=".$inbox_id."' class='menulinks'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></font> - ".date("m-d-Y",$posted)."</td></tr>";

        }

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));
          $tname = $this->GetTournamentName($ConfigFile,$tid);
          echo "<tr><td><font class='menubulletcolor'>&nbsp; &#8226; &nbsp<a href='./chess_msg_center.php?type=read&id=".$inbox_id."' class='menulinks'>[".$tname."]</a></font> - ".date("m-d-Y",$posted)."</td></tr>";
        }

        $i++;

      }

      echo "</table>";
      echo "<br>";

    }else{

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

      echo "<tr><td>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_39")."</td></tr>";

      echo "</table>";
      echo "<br>";

    }


  }


  /**********************************************************************
  * DownloadNewMessages
  *
  */
  function DownloadNewMessages($ConfigFile, $ID, $SID){

    //Get game info
    $query = "SELECT * FROM message_queue WHERE player_id =".$ID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $mid = trim(mysql_result($return,$i,"mid"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"posted"));

        //insert messages
        $query = "INSERT INTO c4m_msginbox VALUES(NULL, ".$ID.", '".$message."', ".$posted.")";
        mysql_query($query, $this->link) or die(mysql_error());

        $i++;

      }

      //Instantiate the CChess Class
      $oChess = new CChess($this->ChessCFGFileLocation);
      $oChess->messages($this->ChessCFGFileLocation, $ID, "", "n");
      unset($oChess);

    }

  }


  /**********************************************************************
  * GetInbox
  *
  */
  function GetInbox($ConfigFile, $ID, $SkinName){

    $strImage = "<img src='./skins/".$SkinName."/images/notify.gif' width='15' height='15'>";

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE player_id =".$ID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Text Messages
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_40")."</font></td></tr>";

      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          $msg = substr($message, 10);
          LIST($name, $message) = explode("-", $msg);

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$inbox_id."'>".$strImage."&nbsp;<a href='./chess_msg_center.php?type=read&id=".$inbox_id."'>".$name."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Move Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_41")."</font></td></tr>";

      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);
          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$inbox_id."'>".$strImage."&nbsp;<a href='./chess_msg_center.php?type=read&id=".$inbox_id."'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Challenge Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_42")."</font></td></tr>";
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $GameId = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$inbox_id."'>".$strImage."&nbsp;<a href='./chess_msg_center.php?type=read&id=".$inbox_id."'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Tournament Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_43")."</font></td></tr>";
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));
          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$inbox_id."'>".$strImage."&nbsp;<a href='./chess_msg_center.php?type=read&id=".$inbox_id."'>".$this->GetTournamentName($ConfigFile,$tid)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

    }else{

      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_39")."</td></tr>";
      echo "</table>";

    }

  }


  /**********************************************************************
  * SendMessage
  *
  */
  function SendMessage($PlayerID, $SID, $ID, $Msg){

    $Message = rawurlencode($Msg);

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $oChess->chat($this->ChessCFGFileLocation, $PlayerID, $ID, $Message);
    unset($oChess);

  }


  /**********************************************************************
  * ReadMessage
  *
  */
  function ReadMessage($ConfigFile, $InboxID){

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE inbox_id =".$InboxID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = rawurldecode(trim(mysql_result($return,$i,"message")));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          // Format Message
          $msg = substr($message, 10);
          LIST($name, $msg) = explode("-", $msg);
          $TotalMessage = $name." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_44")."<br><i>".$msg."</i>";
          echo "<tr><td>".$TotalMessage."</td></tr>";

        }

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);
          $Move = substr($message, (strlen($message)-5),5);
          $gameid = substr($message, 19, (strlen($message)-24));

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_45")." <a href='./chess_game.php?gameid=".$gameid."'>".$gameid."</a> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_46")." <i>".$Move."</i>";

          echo "<tr><td>".$TotalMessage."</td></tr>";

        }

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $gameid = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_47")."<br>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_48")." <a href='./chess_game.php?gameid=".$gameid."'>".$gameid."</a>";

          echo "<tr><td>".$TotalMessage."</td></tr>";

        }

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));
          $tname = $this->GetTournamentName($ConfigFile,$tid);

          $TotalMessage = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_49")." \"<a href='./chess_view_tournament_proposal.php?tid=".trim($tid)."'>".$tname."</a>\" ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_50")."";

          echo "<tr><td>".$TotalMessage."</td></tr>";
        }

        echo "<input type='hidden' name='txtIbID' value='".$inbox_id."'>";

        $i++;

      }

      echo "</table>";
      echo "<br>";

    }else{

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_51")."</td></tr>";
      echo "</table>";
      echo "<br>";

    }

  }


  /**********************************************************************
  * SaveMessageFromInbox
  *
  */
  function SaveMessageFromInbox($ConfigFile, $InboxID){

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE inbox_id =".$InboxID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id  = trim(mysql_result($return,$i,"player_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $msg_posted = trim(mysql_result($return,$i,"msg_posted"));

        //insert messages
        $query = "INSERT INTO c4m_msgsaved VALUES(NULL, ".$player_id.", '".$message."', ".$msg_posted.")";
        mysql_query($query, $this->link) or die(mysql_error());

        // Remove Item
        $query = "DELETE FROM c4m_msginbox WHERE inbox_id =".$InboxID."";
        mysql_query($query, $this->link) or die(mysql_error());

        $i++;

      }

    }

  }


  /**********************************************************************
  * DeleteMessageFromInbox
  *
  */
  function DeleteMessageFromInbox($ConfigFile, $InboxID){

    // Remove Item
    $query = "DELETE FROM c4m_msginbox WHERE inbox_id =".$InboxID."";
    mysql_query($query, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetSavedMessages
  *
  */
  function GetSavedMessages($ConfigFile, $ID, $SkinName){

    $strImage = "<img src='./skins/".$SkinName."/images/notify.gif' width='15' height='15'>";

    //Get game info
    $query = "SELECT * FROM c4m_msgsaved WHERE player_id =".$ID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Text Messages
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_40")."</font></td></tr>";
      while($i < $num){

        $saved_id = trim(mysql_result($return,$i,"saved_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          $msg = substr($message, 10);
          LIST($name, $message) = explode("-", $msg);

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$saved_id."'>".$strImage."&nbsp;<a href='./chess_msg_center_saved.php?type=read&id=".$saved_id."'>".$name."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Move Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_41")."</font></td></tr>";
      while($i < $num){

        $saved_id = trim(mysql_result($return,$i,"saved_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$saved_id."'>".$strImage."&nbsp;<a href='./chess_msg_center_saved.php?type=read&id=".$saved_id."'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Challenge Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_42")."</font></td></tr>";
      while($i < $num){

        $saved_id = trim(mysql_result($return,$i,"saved_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $GameId = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$saved_id."'>".$strImage."&nbsp;<a href='./chess_msg_center_saved.php?type=read&id=".$saved_id."'>".$this->GetUserIDByPlayerID($ConfigFile,$player_id)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

      // Tournament Message
      /////////////////////////////////////

      $i = 0;

      echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row1' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_43")."</font></td></tr>";
      while($i < $num){

        $saved_id = trim(mysql_result($return,$i,"saved_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));

          echo "<tr><td class='row2' width='80%'><input type='checkbox' name='chkMessage[]' value='".$saved_id."'>".$strImage."&nbsp;<a href='./chess_msg_center_saved.php?type=read&id=".$saved_id."'>".$this->GetTournamentName($ConfigFile,$tid)."</a></td><td class='row2'>&nbsp;".date("m-d-Y",$posted)."</td></tr>";

        }

        $i++;

      }

      echo "</table>";

    }else{

      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_52")."</td></tr>";
      echo "</table>";

    }

  }


  /**********************************************************************
  * DeleteSavedMessage
  *
  */
  function DeleteSavedMessage($ConfigFile, $SavedID){

    // Remove Item
    $query = "DELETE FROM c4m_msgsaved WHERE saved_id =".$SavedID."";
    mysql_query($query, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * ReadMessageSaved
  *
  */
  function ReadMessageSaved($ConfigFile, $SavedID){

    //Get game info
    $query = "SELECT * FROM c4m_msgsaved WHERE saved_id =".$SavedID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

      while($i < $num){

        $saved_id = trim(mysql_result($return,$i,"saved_id"));
        $message = rawurldecode(trim(mysql_result($return,$i,"message")));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          // Format Message
          $msg = substr($message, 10);
          LIST($name, $msg) = explode("-", $msg);
          $TotalMessage = $name." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_44")."<br><i>".$msg."</i>";
          echo "<tr><td>".$TotalMessage."</td></tr>";

        }

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);
          $Move = substr($message, (strlen($message)-5),5);
          $gameid = substr($message, 19, (strlen($message)-24));

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_45")." <a href='./chess_game.php?gameid=".$gameid."'>".$gameid."</a> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_46")." <i>".$Move."</i>";

          echo "<tr><td>".$TotalMessage."</td></tr>";

        }

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $gameid = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_47")."<br>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_48")." <a href='./chess_game.php?gameid=".$gameid."'>".$gameid."</a>";

          echo "<tr><td>".$TotalMessage."</td></tr>";
        }

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));
          $tname = $this->GetTournamentName($ConfigFile,$tid);

          $TotalMessage = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_49")." \"<a href='./chess_view_tournament_proposal.php?tid=".trim($tid)."'>".$tname."</a>\" ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_50")."";

          echo "<tr><td>".$TotalMessage."</td></tr>";
        }

        echo "<input type='hidden' name='txtSavedID' value='".$saved_id."'>";

        $i++;

      }

      echo "</table>";
      echo "<br>";

    }else{

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_51")."</td></tr>";
      echo "</table>";
      echo "<br>";

    }

  }


  /**********************************************************************
  * GetPreviousGames
  *
  */
  function GetPreviousGames($ConfigFile, $Player_ID){

    //Get game info for white player
    $query = "SELECT * FROM game WHERE w_player_id =".$Player_ID." AND completion_status IN ('W','B','D','A','I')";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    //get all the games from the c4m_tournamentgames table
    $queryt = "SELECT tg_gameid FROM c4m_tournamentgames";
    $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    $numt = mysql_numrows($returnt);

    echo "<br>";
    echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_53")."</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_54")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_55")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_56")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_57")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_58")."</td></tr>";

    if($num != 0){

      $i = 0;

      while($i < $num){

        $game_id = trim(mysql_result($return,$i,"game_id"));
        $initiator = trim(mysql_result($return,$i,"initiator"));
        $w_player_id = trim(mysql_result($return,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($return,$i,"b_player_id"));
        $status = trim(mysql_result($return,$i,"status"));
        $completion_status = trim(mysql_result($return,$i,"completion_status"));
        $start_time = trim(mysql_result($return,$i,"start_time"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<tr>";
          echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_59")."</a></td>";
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
          echo "<td class='row2'>".$completion_status."</td>";
          echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
          echo "</tr>";

        }

        $i++;
      }

    }

    //Get game info for Black player
    $query1 = "SELECT * FROM game WHERE b_player_id =".$Player_ID." AND completion_status IN ('W','B','D','A','I')";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $ii = 0;

      while($ii < $num1){

        $game_id = trim(mysql_result($return1,$ii,"game_id"));
        $initiator = trim(mysql_result($return1,$ii,"initiator"));
        $w_player_id = trim(mysql_result($return1,$ii,"w_player_id"));
        $b_player_id = trim(mysql_result($return1,$ii,"b_player_id"));
        $status = trim(mysql_result($return1,$ii,"status"));
        $completion_status = trim(mysql_result($return1,$ii,"completion_status"));
        $start_time = trim(mysql_result($return1,$ii,"start_time"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<tr>";
          echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_59")."</a></td>";
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
          echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
          echo "<td class='row2'>".$completion_status."</td>";
          echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
          echo "</tr>";

        }

        $ii++;
      }

    }

    if($num == 0 && $num1 == 0){

        echo "<tr>";
        echo "<td class='row2' colspan='5'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_60")."</td>";
        echo "</tr>";

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * SearchPlayers
  *
  */
  function SearchPlayers($ConfigFile, $SearchText){

    if($SearchText == "*"){
      $query = "SELECT * FROM player WHERE userid REGEXP '^[0-9]'";
    }else{

      if($SearchText == "%"){
      $query = "SELECT * FROM player WHERE userid REGEXP '^[!@#$%^&*()_<>?,.;:|]'";

      }else{

        if(strlen($SearchText) == 1){
          $query = "SELECT * FROM player WHERE userid REGEXP '^[".$SearchText."]'";
        }else{
          $query = "SELECT * FROM player WHERE userid LIKE '%".$SearchText."%'";
        }

      }

    }

    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_SEARCHPLAYERS_TABLE1_WIDTH') && defined('CFG_SEARCHPLAYERS_TABLE1_BORDER') && defined('CFG_SEARCHPLAYERS_TABLE1_CELLPADDING') && defined('CFG_SEARCHPLAYERS_TABLE1_CELLSPACING') && defined('CFG_SEARCHPLAYERS_TABLE1_ALIGN')){
      echo "<table width='".CFG_SEARCHPLAYERS_TABLE1_WIDTH."' border='".CFG_SEARCHPLAYERS_TABLE1_BORDER."' cellpadding='".CFG_SEARCHPLAYERS_TABLE1_CELLPADDING."' cellspacing='".CFG_SEARCHPLAYERS_TABLE1_CELLSPACING."' align='".CFG_SEARCHPLAYERS_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_61")."</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){

          echo "<tr>";
          echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
          echo "<td class='row2'>".date("m-d-Y",$signup_time)."</td>";
          echo "<td class='row2'><a href='./chess_msg_center.php?type=newmsg&slctUsers=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";
          echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
          echo "<td class='row2'>";

          if($this->IsPlayerOnline($ConfigFile, $player_id)){

            echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";

          }else{
            echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
          }

          echo "</td>";
          echo "</tr>";

        }

        $i++;

      }

    }else{

      echo "<tr>";
      echo "<td class='row2' colspan='5'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_62")." <i>".$SearchText."</i></td>";
      echo "</tr>";

    }

    echo "</table>";

  }


  /**********************************************************************
  * GetPlayerStatusInformation
  *
  */
  function GetPlayerStatusInformation($ConfigFile, $ID, $name, $bInAdmin=false){

    $wins = 0;
    $loss = 0;
    $draws = 0;

    $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $ID, $wins, $loss, $draws);

    //Display the results
    echo "<table cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";

    if($name == ""){
      echo "<tr><td class='tableheadercolor' colspan='3'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_63")."</font></td></tr>";
    }else{
      echo "<tr><td class='tableheadercolor' colspan='3'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_64")." ".$name."</font></td></tr>";
    }

    echo "<tr>";
    echo "<td class='row1'>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_65")."</span>";
    echo "</td>";
    echo "<td class='row2'>";
    echo "<span class='gensmall'>".$wins."</span>";
    echo "</td>";
    echo "<td rowspan='3'  class='row2'>";

    $filename = "./bin/BarChart.php";

    if(file_exists($filename)){
      echo "<img src='./bin/BarChart.php?win=".$wins."&loss=".$loss."&draw=".$draws."'>";

    }else{
      echo "<img src='../bin/BarChart.php?win=".$wins."&loss=".$loss."&draw=".$draws."'>";
    }

    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1'>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_66")."</span>";
    echo "</td>";
    echo "<td class='row2'>";
    echo "<span class='gensmall'>".$loss."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1'>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_67")."</span>";
    echo "</td>";
    echo "<td class='row2'>";
    echo "<span class='gensmall'>".$draws."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1' colspan='3'>";
    echo "<span class='gensmall'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_68")." ";

    if($this->ELOIsActive()){
      $points = $this->ELOGetRating($ID);
    }else{
      $points = $this->GetPointValue($wins, $loss, $draws);
    }

    $this->SetChessPointCacheData($ID, $points);
    echo $this->GetPointRanking($points,$wins);

    echo "</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1' colspan='3'>";
    echo "<span class='gensmall'>";
    echo $this->GetMemberDateTimeInfo($ConfigFile, $ID);
    echo "</span>";
    echo "</td>";
    echo "</tr>";

    $aDetails = array();
    $this->GetClubMembershipDetails($ID, $aDetails);

    if(count($aDetails) > 0){

      echo "<tr>";
      echo "<td class='row2' colspan='3'>";
      echo "<span class='gensmall'>";

      $strClubMem = "";

      if($aDetails[2] == 'y'){
        $strClubMem = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_236")." ";
      }else{
        $strClubMem = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_235")." ";
      }

      echo $strClubMem;

      if(!$bInAdmin){
        echo "<a href='./chess_club_page.php?clubid=".$aDetails[0]."'>";
      }

      echo $aDetails[1];

      if(!$bInAdmin){
        echo "</a>";
      }

      echo "</span>";
      echo "</td>";
      echo "</tr>";

    }

    echo "<tr>";
    echo "<td class='row1' colspan='3'>";
    echo "<span class='gensmall'>";
    echo $this->GetMemberMiscMoveDateTimeInfo($ConfigFile, $ID);
    echo "</span>";
    echo "</td>";
    echo "</tr>";

    if(!$bInAdmin){
      echo "<tr>";
      echo "<td class='row1' colspan='3'>";
      echo "<input type='button' name='btnChallenge' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_69")."' OnClick=\"location.href='./chess_create_game_ar.php?othpid=".$ID."';\" class='mainoption'>";
      echo "</td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetMemberMiscMoveDateTimeInfo
  *
  */
  function GetMemberMiscMoveDateTimeInfo($ConfigFile, $ID){

    $returntxt = "";

    // Date Of last login
    $query = "SELECT * FROM player_last_login WHERE o_playerid = '".$ID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $date = mysql_result($return,0,"o_date");
      $returntxt = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_155")." ".$date;

    }else{

      $returntxt = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_155")." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_157");

    }

    // Date of last move
    $query = "SELECT * FROM move_history WHERE player_id = '".$ID."' ORDER BY move_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $time1 = mysql_result($return,0,"time");
      $date = date("Y-m-d G:i:s", $time1);
      $returntxt = $returntxt."<br>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_156")." ".$date;

    }else{

      $returntxt = $returntxt."<br>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_156")." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_157");

    }

    return $returntxt;

  }


  /**********************************************************************
  * GetMemberDateTimeInfo
  *
  */
  function GetMemberDateTimeInfo($ConfigFile, $ID){

    $query = "SELECT * FROM player WHERE player_id = ".$ID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $signup_time = mysql_result($return,0,"signup_time");

      // Get difference between the dates.
      $firstdate = time();
      $seconddate = $signup_time; // Current Date

      $difference = $firstdate - $seconddate;
      $diffday = $difference/86400;

      //format the difference
      $hours = date("H",$difference);
      $minutes = date("m",$difference);
      $seconds = date("s",$difference);

      $days = (int) $diffday;

      if($days > 1){

        $timestr = $days." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_150");

      }else{

        if($days == 0){
          $timestr = $days." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_150");
        }else{
          $timestr = $days." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_151");
        }

      }

      $return = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_70")." ".date("m-d-Y",$signup_time).". <br>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_71")." ".$timestr;

      return $return;

    }

  }


  /**********************************************************************
  * GetPointValue
  *
  */
  function GetPointValue($wins, $loss, $draws){

    //Generate points
    $Total = $this->GetDBPointValue();

    $Total = $Total + ($wins * 10) - ($loss * 5);

    return $Total;

  }


  /**********************************************************************
  * GetPointRanking
  *
  */
  function GetPointRanking($points, $wins){

    //Generate points
    $Ranking = "";

    if($wins == 0){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_72")." ".$points." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_75")."";
    }

    if($wins > 0 && $wins <= 25){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_73")." ".$points." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_75")."";
    }

    if($wins > 25){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_74")." ".$points." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_75")."";
    }

    return $Ranking;

  }


  /**********************************************************************
  * GetOngoingGameCount
  *
  */
  function GetOngoingGameCount($ConfigFile, $ID){

    $PersonalGame = 0;
    $AllGames = 0;
    $TGames = 0;

    // Get personal game count
    /////////////////////////////////////

    //count black player//////////////////////////////////////////////////////
    $query = "SELECT count(*) FROM game WHERE b_player_id = ".$ID." AND completion_status IN('I')";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $PersonalGame = $PersonalGame + mysql_result($return,0,0);
    }

    //count white player//////////////////////////////////////////////////////
    $query1 = "SELECT count(*) FROM game WHERE w_player_id = ".$ID." AND completion_status  IN('I')";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $PersonalGame = $PersonalGame + mysql_result($return1,0,0);
    }

    // Get All game count
    /////////////////////////////////////

    $query2 = "SELECT count(*) FROM game WHERE completion_status IN('I')";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $AllGames = $AllGames + mysql_result($return2,0,0);
    }

    // Get Tournament Count
    /////////////////////////////////////
    $query3 = "SELECT count(*) FROM c4m_tournament WHERE t_status IN('S')";
    $return3 = mysql_query($query3, $this->link) or die(mysql_error());
    $num3 = mysql_numrows($return3);

    if($num3 != 0){
      $TGames = $TGames + mysql_result($return3,0,0);
    }

    echo "<br>";

    // Skin table settings
    if(defined('CFG_GETONGOINGGAMECOUNT_TABLE1_WIDTH') && defined('CFG_GETONGOINGGAMECOUNT_TABLE1_BORDER') && defined('CFG_GETONGOINGGAMECOUNT_TABLE1_CELLPADDING') && defined('CFG_GETONGOINGGAMECOUNT_TABLE1_CELLSPACING') && defined('CFG_GETONGOINGGAMECOUNT_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETONGOINGGAMECOUNT_TABLE1_WIDTH."' cellpadding='".CFG_GETONGOINGGAMECOUNT_TABLE1_CELLPADDING."' cellspacing='".CFG_GETONGOINGGAMECOUNT_TABLE1_CELLSPACING."' border='".CFG_GETONGOINGGAMECOUNT_TABLE1_BORDER."' align='".CFG_GETONGOINGGAMECOUNT_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='3'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_76")."</font></td></tr>";
    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_77")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_78")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_79")."</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>".$PersonalGame."</td><td class='row2'>".$AllGames."</td><td class='row2'>".$TGames."</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * GetOngoingGameCount2
  *
  */
  function GetOngoingGameCount2($ConfigFile, &$AllGames, &$TGames){

    $AllGames = 0;
    $TGames = 0;

    // Get All game count
    /////////////////////////////////////

    $query2 = "SELECT count(*) FROM game WHERE completion_status IN('I')";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){

      $AllGames = $AllGames + mysql_result($return2,0,0);

    }

    // Get Tournament Count
    /////////////////////////////////////
    $query3 = "SELECT count(*) FROM c4m_tournament WHERE t_status IN('S')";
    $return3 = mysql_query($query3, $this->link) or die(mysql_error());
    $num3 = mysql_numrows($return3);

    if($num3 != 0){

      $TGames = $TGames + mysql_result($return3,0,0);

    }

  }


  /**********************************************************************
  * GetPlayerCount
  *
  */
  function GetPlayerCount($ConfigFile, &$PCount){

    $PCount = 0;

    $query1 = "SELECT count(*) FROM player";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $query2 = "SELECT count(*) FROM player2";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num1 != 0 && $num2 >= 0){
      $PCount = $PCount + (mysql_result($return1,0,0) - mysql_result($return2,0,0));
    }

  }


  /**********************************************************************
  * GetMessageCount
  *
  */
  function GetMessageCount($ConfigFile, $PlayerID){

    $TextMsg = 0;
    $MoveMsg = 0;
    $ChallengeMsg = 0;
    $TournamentMsg = 0;

    // Text Message count
    /////////////////////////////////////

    $query1 = "SELECT count(*) FROM c4m_msginbox WHERE message LIKE 'C0%' AND player_id =".$PlayerID;
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $TextMsg = mysql_result($return1,0,0);
    }

    // Move Message count
    /////////////////////////////////////

    $query2 = "SELECT count(*) FROM c4m_msginbox WHERE message LIKE 'M0%' AND player_id =".$PlayerID;
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $MoveMsg = mysql_result($return2,0,0);
    }

    // Challenge Message count
    /////////////////////////////////////

    $query3 = "SELECT count(*) FROM c4m_msginbox WHERE message LIKE 'GC%' AND player_id =".$PlayerID;
    $return3 = mysql_query($query3, $this->link) or die(mysql_error());
    $num3 = mysql_numrows($return3);

    if($num3 != 0){
      $ChallengeMsg = mysql_result($return3,0,0);
    }

    //
    //TODO: count tournament messages
    //

    echo "<br>";

    // Skin table settings
    if(defined('CFG_GETMESSAGECOUNT_TABLE1_WIDTH') && defined('CFG_GETMESSAGECOUNT_TABLE1_BORDER') && defined('CFG_GETMESSAGECOUNT_TABLE1_CELLPADDING') && defined('CFG_GETMESSAGECOUNT_TABLE1_CELLSPACING') && defined('CFG_GETMESSAGECOUNT_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETMESSAGECOUNT_TABLE1_WIDTH."' cellpadding='".CFG_GETMESSAGECOUNT_TABLE1_CELLPADDING."' cellspacing='".CFG_GETMESSAGECOUNT_TABLE1_CELLSPACING."' border='".CFG_GETMESSAGECOUNT_TABLE1_BORDER."' align='".CFG_GETMESSAGECOUNT_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='4'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_80")."</font></td></tr>";
    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_81")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_82")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_83")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_84")."</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row2'>".$TextMsg."</td>";
    echo "<td class='row2'>".$MoveMsg."</td>";
    echo "<td class='row2'>".$ChallengeMsg."</td>";
    echo "<td class='row2'>".$TournamentMsg."</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * GetTopPlayers
  *
  */
  function GetTopPlayers($ConfigFile, $bShowPlayersList=false){

    if($bShowPlayersList){

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

      $query = "SELECT * FROM cfm_point_caching ORDER BY points DESC LIMIT 10";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $i = 0;
        while($i < $num){

          $nPlayerID = mysql_result($return, $i, "player_id");
          $nPoints = mysql_result($return, $i, "points");

          $strUserID = $this->GetUserIDByPlayerID($ConfigFile, $nPlayerID);

          echo "<tr>";
          echo "<td><font class='menubulletcolor'>";
          echo "&#8226; &nbsp;<a href='./chess_statistics.php?playerid=".$nPlayerID."&name=".$strUserID."'>".$strUserID."</a>";
          echo "</font>";
          echo "</td>";
          echo "<td>";
          echo "<span class='gensmall'>".$nPoints."</span>";
          echo "</td>";
          echo "</tr>";

          $i++;

        }



      }

      echo "</table>";
      echo "<br>";

    }else{

      echo "<br><table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'><tr><td><font class='menubulletcolor'>&nbsp; &#8226; &nbsp<a href='./chess_topten.php' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_234")."</a><br></font></td></tr></table><br>";

    }

  }


  /**********************************************************************
  * array_csort
  *
  */
  function array_csort(){

    $args = func_get_args();
    $marray = array_shift($args);

    $msortline = "return(array_multisort(";

    foreach ($args as $arg){

      $i++;
      if(is_string($arg)){

          foreach($marray as $row){
              $sortarr[$i][] = $row[$arg];
          }

      }else{
        $sortarr[$i] = $arg;
      }

      $msortline .= "\$sortarr[".$i."],";
    }

    $msortline .= "\$marray));";

    eval($msortline);

    return $marray;

  }


  /**********************************************************************
  * ChangePassword
  *
  */
  function ChangePassword($ConfigFile, $player_id, $New, $Retype){

    //Check if the new pass word matches the retypr password
    if($New == $Retype){

      //Change the password
	  $New = $this->hash_password($New);
      $update = "UPDATE player SET password = '".$New."' WHERE player_id = ".$player_id."";
      mysql_query($update, $this->link) or die(mysql_error());

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_85")."</td></tr>";
      echo "</table>";
      echo "<br>";

    }else{

      echo "<br>";
      echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      echo "<tr><td class='row2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_86")."</td></tr>";
      echo "</table>";
      echo "<br>";

    }

  }


  /**********************************************************************
  * CreateGame
  *
  */
  function CreateGame($ConfigFile, $SID, $player_id, $other_player_id, $my_color, $fen, $move1, $time1, $move2, $time2, $bRTGame, $precreate, $brealtimeposs, $Rating, $GameTime){

    $returncode = "";

    // if($bRTGame == true && $move1 == "" && $time1 == ""){
      // $brealtimeposs = true;
      // $bRTGame = false;
      // $GameTime = "C-Normal";
    // }elseif($bRTGame == true && $move1 != "" && $time1 == ""){
      // $brealtimeposs = true;
      // $bRTGame = false;
      // $GameTime = "C-Normal";
    // }elseif($bRTGame == true && $move1 == "" && $time1 != ""){
      // $brealtimeposs = true;
      // $bRTGame = false;
      // $GameTime = "C-Normal";
    // }

	$this->GetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode);
	
    if($fen != ""){

      // format the fen
      $fen = $this->FormatInputedFEN($fen);

      //Instantiate the CChess Class
      $oChess = new CChess($this->ChessCFGFileLocation);
      $returncode = $oChess->create_game($this->ChessCFGFileLocation, $player_id, $other_player_id, $my_color, $fen);
      unset($oChess);

      $GameID = substr($returncode, 0, 32);

      //if(!$bRTGame){
        $insert11 = "INSERT INTO cfm_game_options VALUES('".$GameID."', '".$Rating."', '".$GameTime."', " . $timing_mode .")";
      //}else{
      //  $insert11 = "INSERT INTO cfm_game_options VALUES('".$GameID."', '".$Rating."', '-', " . $timing_mode . ")";
     // }
      mysql_query($insert11, $this->link) or die(mysql_error());

      if($move1 != "" && $time1 != "" && $move2 != "" && $time2 != ""){

        if(is_numeric($move1) && is_numeric($time1) && is_numeric($move2) && is_numeric($time2)){

          if(is_int((int)$move1) && is_int((int)$time1) && is_int((int)$move2) && is_int((int)$time2)){
            $insert = "INSERT INTO timed_games VALUES('".$GameID."', ".$move1.", ".$time1.", ".$move2.", ".$time2.")";
            mysql_query($insert, $this->link) or die(mysql_error());
          }

        }

      }else{

        if($move1 != "" && $time1 != ""){

          if(is_numeric($move1) && is_numeric($time1)){

            if(is_int((int)$move1) && is_int((int)$time1)){
              $insert = "INSERT INTO timed_games VALUES('".$GameID."', ".$move1.", ".$time1.", 0, 0)";
              mysql_query($insert, $this->link) or die(mysql_error());
            }

          }

        }else{

          if($brealtimeposs){
            $insert = "INSERT INTO cfm_gamesrealtime VALUES('".$GameID."', NOW())";
            mysql_query($insert, $this->link) or die(mysql_error());
          }

        }

      }

    }else{

      //Instantiate the CChess Class
      $oChess = new CChess($this->ChessCFGFileLocation);
      $returncode = $oChess->create_game($this->ChessCFGFileLocation, $player_id, $other_player_id, $my_color, "");
      unset($oChess);

      $GameID = substr($returncode, 0, 32);

     //if(!$bRTGame){
        $insert11 = "INSERT INTO cfm_game_options VALUES('".$GameID."', '".$Rating."', '".$GameTime."', " . $timing_mode .")";
      //}else{
      //  $insert11 = "INSERT INTO cfm_game_options VALUES('".$GameID."', '".$Rating."', '-', " . $timing_mode . ")";
      //}
      mysql_query($insert11, $this->link) or die(mysql_error());

      if($move1 != "" && $time1 != "" && $move2 != "" && $time2 != ""){

        if(is_numeric($move1) && is_numeric($time1) && is_numeric($move2) && is_numeric($time2)){

          if(is_int((int)$move1) && is_int((int)$time1) && is_int((int)$move2) && is_int((int)$time2)){
            $insert = "INSERT INTO timed_games VALUES('".$GameID."', ".$move1.", ".$time1.", ".$move2.", ".$time2.")";
            mysql_query($insert, $this->link) or die(mysql_error());
          }

        }

      }else{

        if($move1 != "" && $time1 != ""){

          if(is_numeric($move1) && is_numeric($time1)){

            if(is_int((int)$move1) && is_int((int)$time1)){
              $insert = "INSERT INTO timed_games VALUES('".$GameID."', ".$move1.", ".$time1.", 0, 0)";
              mysql_query($insert, $this->link) or die(mysql_error());
            }

          }

        }else{

          if($brealtimeposs){
            $insert = "INSERT INTO cfm_gamesrealtime VALUES('".$GameID."', NOW())";
            mysql_query($insert, $this->link) or die(mysql_error());
          }

        }

      }

      //Create the new game with the pre-created moves
      if($precreate != 0 && $fen == ""){

        // Set Player Color
        $w_player_id1 = "";
        $b_player_id1 = "";

        if($req_color != "" && $req_color == "w"){
          $w_player_id1=$player_id;
          $b_player_id1=$other_player_id;
        }else{
          $w_player_id1=$other_player_id;
          $b_player_id1=$player_id;
        }

        $query = "SELECT * FROM cfm_creategamefen WHERE o_id =".$precreate;
        $return = mysql_query($query, $this->link) or die(mysql_error());
        $num = mysql_numrows($return);

        if($num != 0){

          $query2 = "SELECT * FROM cfm_creategamefen_moves WHERE o_cgfid =".$precreate." ORDER BY o_id ASC";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 != 0){

            $ii = 0;
            while($ii < $num2){

              $o_move = trim(mysql_result($return2,$ii,"o_move"));

              if($ii == 0){
                $insert1 = "INSERT INTO move_history VALUES(NULL, ".time().", ".$w_player_id1.", '".$o_move."','".$GameID."')";
                mysql_query($insert1, $this->link) or die(mysql_error());
              }else{

                if($ii % 2 == 0 ){
                  $insert1 = "INSERT INTO move_history VALUES(NULL, ".time().", ".$w_player_id1.", '".$o_move."','".$GameID."')";
                  mysql_query($insert1, $this->link) or die(mysql_error());
                }else{
                  $insert1 = "INSERT INTO move_history VALUES(NULL, ".time().", ".$b_player_id1.", '".$o_move."','".$GameID."')";
                  mysql_query($insert1, $this->link) or die(mysql_error());
                }

              }


              $ii++;
            }

          }

        }

      }

    }

    return $returncode;

  }


  /**********************************************************************
  * CheckGameAccepted
  *
  */
  function CheckGameAccepted($ConfigFile, $player_id, $GID){

    $query = "SELECT * FROM game WHERE game_id ='".$GID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $returncode = "- -";

    if($num != 0){

      $initiator = trim(mysql_result($return,0,"initiator"));
      $status = trim(mysql_result($return,0,"status"));
      $completion_status = trim(mysql_result($return,0,"completion_status"));

      // Handle Initiator
      if($initiator == $player_id){

        if($status == "W" && $completion_status = "I"){

          $returncode = "i waiting";

        }else{
          $returncode = "i accepted";
        }

      }else{

        // Handle other player
        if($status == "W" && $completion_status = "I"){

          $returncode = "o waiting";

        }else{
          $returncode = "o accepted";
        }

      }

    }

    return $returncode;

  }


  /**********************************************************************
  * AcceptGame
  *
  */
  function AcceptGame($SID, $GID, $PID){

    $returncode = "";

    // Remove game challange from inbox
    $this->PurgeOldGameChallangesFromInbox($this->ChessCFGFileLocation, $PID, $GID);

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->accept_game($this->ChessCFGFileLocation, $GID, $PID);
    unset($oChess);

    return $returncode;

  }





  /**********************************************************************
  * AcceptOpenChallangeGame
  *
  */
  function AcceptOCGame($SID, $GID, $PID){

    $returncode = "";

    // Remove game challange from inbox
    $this->PurgeOldGameChallangesFromInbox($this->ChessCFGFileLocation, $PID, $GID);

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->accept_oc_game($this->ChessCFGFileLocation, $GID, $PID);
    unset($oChess);

    return $returncode;

  }

  /**********************************************************************
  * IsPlayersTurn
  *
  */
  function IsPlayersTurn($ConfigFile, $player_id, $GID, $bTournament=false){

    $bTurn = false;

    $query = "SELECT * FROM game WHERE game_id ='".$GID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $initiator = trim(mysql_result($return,0,"initiator"));
      $w_player_id = mysql_result($return,0,"w_player_id");
      $b_player_id = mysql_result($return,0,"b_player_id");
      $next_move = trim(mysql_result($return,0,"next_move"));
      $status = trim(mysql_result($return,0,"status"));
      $completion_status = trim(mysql_result($return,0,"completion_status"));

      //check if the game is active and incomplete
      if($status == "A" && $completion_status == "I"){

        if($bTournament){

          //Check if the player is white and has the turn
          if($w_player_id == $player_id && $next_move == 'w'){
            $bTurn = true;
          }else{

            //Check if the player is black and has the turn
            if($w_player_id != $player_id && $next_move == 'b'){
              $bTurn = true;
            }else{

              // Check if the game was just started
              if($w_player_id == $player_id && $next_move == ""){
                $bTurn = true;
              }

            }

          }

        }else{

          //Check if the player is white and has the turn
          if($w_player_id == $player_id && $next_move == 'w'){
            $bTurn = true;
          }else{

            //Check if the player is black and has the turn
            if($b_player_id == $player_id && $next_move == 'b'){
              $bTurn = true;
            }else{

              // Check if the game was just started
              if($w_player_id == $player_id && $next_move == ""){
                $bTurn = true;
              }

            }

          }

        }

      }

    }

    return $bTurn;

  }

  
  /**********************************************************************
  * UserNameExists
  *
  */
  function UserNameExists($Name){

    $returncode = "";

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->UserNameExists($this->ChessCFGFileLocation, $Name);
    unset($oChess);

    return $returncode;

  }


  /**********************************************************************
  * RegisterNewPlayer
  *
  */
  function RegisterNewPlayer($Name, $Email){

    $returncode = "";

    // Check the player name.
    $bInvalidSymbols = false;
    $aInvalidSymbols = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "+", "=", "{", "}", "[", "]", "|", "\\", ":", ";", "\"", "'", "<", ">", "?", "/", ",", " ");

    $nMAX = count($aInvalidSymbols);
    for($i=0; $i < $nMAX; $i++){

      $pos = strpos($Name, $aInvalidSymbols[$i]);
      if($pos !== false){
        $bInvalidSymbols = true;
        break;
      }

    }

    // check the length of the player name
    $bNameLengthInvalid = true;
    $nMaxLength = 11;
    if(strlen($Name) <= $nMaxLength){
      $bNameLengthInvalid = false;
    }

    // Add the player or display an error
    if(!$bInvalidSymbols && !$bNameLengthInvalid){

      //Instantiate the CChess Class
      $oChess = new CChess($this->ChessCFGFileLocation);
      $returncode = $oChess->register($this->ChessCFGFileLocation, $Name, $Email);
      unset($oChess);

    }else{

      if($bInvalidSymbols){
        $returncode = array('success' => FALSE, 'msg' => "Invalid characters detected.");
      }elseif($bNameLengthInvalid){
        $returncode = array('success' => FALSE, 'msg' => "Name must be less than or equal to 11 characters.");
      }

    }

    return $returncode;

  }


  /**********************************************************************
  * RegisterNewPlayer2
  *
  */
  function RegisterNewPlayer2($Name, $Email){

    $returncode = "";

    // Check the player name.
    $bInvalidSymbols = false;
    $aInvalidSymbols = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "+", "=", "{", "}", "[", "]", "|", "\\", ":", ";", "\"", "'", "<", ">", "?", "/", ",", " ");

    $nMAX = count($aInvalidSymbols);
    for($i=0; $i < $nMAX; $i++){

      $pos = strpos($Name, $aInvalidSymbols[$i]);
      if($pos !== false){
        $bInvalidSymbols = true;
        break;
      }

    }
	
	$invalid_chars = array('<', '>');
	for($i = 0; $i < count($invalid_chars); $i++)
	{
		$pos = strpos($Email, $invalid_chars[$i]);
		if($pos !== false)
		{
			return array('success' => FALSE, 'msg' => 'Invalid character in email');
		}
	}

    // check the length of the player name
    $bNameLengthInvalid = true;
    $nMaxLength = 11;
    if(strlen($Name) <= $nMaxLength){
      $bNameLengthInvalid = false;
    }

    // Add the player or display an error
    if(!$bInvalidSymbols && !$bNameLengthInvalid){

      //Instantiate the CChess Class
      $oChess = new CChess($this->ChessCFGFileLocation);
      $returncode = $oChess->register2($this->ChessCFGFileLocation, $Name, $Email);
      unset($oChess);

    }else{

      if($bInvalidSymbols){
        $returncode = array('success' => FALSE, 'msg' => "Invalid characters detected.");
      }elseif($bNameLengthInvalid){
        $returncode = array('success' => FALSE, 'msg' => "Name must be less than or equal to 11 characters.");
      }

    }

    return $returncode;

  }


  /**********************************************************************
  * GetPlayerListSelectBox
  *
  */
  function GetPlayerListSelectBox($ConfigFile){

    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select NAME='lstPlayers[]' multiple size='15' style='width:170'>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){
          echo "<option VALUE='".$player_id."'>".$userid."</option>";
        }

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * CreateTournament
  *
  */
  function CreateTournament($ConfigFile, $TournName, $GameType, $PlayerNumPerGroup, $CDateMonth, $CDateDay, $CDYear, $CDTime, $SDateMonth, $SDateDay, $SDYear, $SDTime, $Comments){

    $TournamentID = 0;

    list($CHour, $CMin, $CSec) = explode(":", $CDTime, 3);
    list($SHour, $SMin, $SSec) = explode(":", $SDTime, 3);

    $cDate = date("Y-m-d G:i:s", mktime($CHour, $CMin, $CSec, $CDateMonth, $CDateDay, $CDYear));
    $sDate = date("Y-m-d G:i:s", mktime($SHour, $SMin, $SSec, $SDateMonth, $SDateDay, $SDYear));

    $insert = "INSERT INTO c4m_tournament VALUES(NULL, '".$TournName."', '".$GameType."', ".$PlayerNumPerGroup.", '".$cDate."', '".$sDate."', '".$Comments."', 'P')";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $TournamentID = trim(mysql_result($return,0,0));
    }

    return (int)$TournamentID;

  }


  /**********************************************************************
  * AddTournamentPlayer
  *
  */
  function AddTournamentPlayer($ConfigFile, $TID, $PID){

    $insert = "INSERT INTO c4m_tournamentplayers VALUES(NULL, ".$TID.", ".$PID.", 'P')";
    mysql_query($insert, $this->link) or die(mysql_error());

    // Message the player

    $message = "T0|".$TID."";

    $insert = "INSERT INTO message_queue(player_id, message, posted) VALUES(".$PID.",'".$message."',".time().")";
    mysql_query($insert, $this->link) or die(mysql_error());
  }


  /**********************************************************************
  * GetTournamentInvites
  *
  */
  function GetTournamentInvites($ConfigFile, $PID){

    $query = "SELECT * FROM c4m_tournamentplayers WHERE tp_playerid=".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    if($num != 0){

      $i=0;
      while($i < $num){
        $tp_id = trim(mysql_result($return,$i,"tp_id"));
        $tp_tournamentid = trim(mysql_result($return,$i,"tp_tournamentid"));
        $tp_playerid = trim(mysql_result($return,$i,"tp_playerid"));
        $tp_status = trim(mysql_result($return,$i,"tp_status"));

        $query1 = "SELECT * FROM c4m_tournament WHERE t_id=".$tp_tournamentid." AND t_cutoffdate >= NOW() AND t_status NOT IN('S','R','C')";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){
          $t_name = trim(mysql_result($return1,0,"t_name"));

          echo "<tr><td><font class='menubulletcolor'>";
          echo "&nbsp; &#8226; &nbsp<a href='./chess_view_tournament_proposal.php?tid=".$tp_tournamentid."'>".$t_name."</a>";
          echo "</font></td></tr>";

        }

        $i++;

      }

    }

    $this->v2GetClosedTournamentInvites($PID);

    echo "<tr><td><font class='menubulletcolor'>";
    echo "&nbsp; &#8226; &nbsp<a href='./chess_tournament_status.php' class='menulinks'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_76")."</a><br>";
    echo "</font></td></tr>";
    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetTournamentInfo
  *
  */
  function GetTournamentInfo($ConfigFile, $TID, $PID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $t_id = trim(mysql_result($return,0,"t_id"));
      $t_name = trim(mysql_result($return,0,"t_name"));
      $t_type = trim(mysql_result($return,0,"t_type"));
      $t_playernum = trim(mysql_result($return,0,"t_playernum"));
      $t_cutoffdate = trim(mysql_result($return,0,"t_cutoffdate"));
      $t_startdate = trim(mysql_result($return,0,"t_startdate"));
      $t_comment = trim(mysql_result($return,0,"t_comment"));
      $t_status = trim(mysql_result($return,0,"t_status"));

      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
      echo "<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$t_name."</font><b></td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_88")."</td>";
      echo "<td class='row2'>";

      switch($t_type){
       case 0:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_89")."";
         break;
       case 1:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_90")."";
         break;
       case 2:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_91")."";
         break;
       case 3:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_92")."";
         break;
      }

      echo "</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_93")."</td><td class='row2'>".$t_cutoffdate."</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_94")."</td><td class='row2'>".$t_startdate."</td></tr>";
      echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_95")."</td></tr>";
      echo "<tr><td class='row2' colspan='2'><span>".$t_comment."</span></td></tr>";
      echo "</table><br>";

      $query1 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $npcount = $t_playernum;
        $ncount = 0;
        $ngroupcount = 1;
        $nswitch = 0;

        if($npcount != 0 && $t_type != 0 && $t_type != 3){

          while($ncount < $num1){

            echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
            echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_96")."".$ngroupcount."</td></tr>";

            while($nswitch < $npcount && $ncount < $num1){

              $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
              $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

              echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
              echo "<td class='row2' width='300'>";

              switch($tp_status){
                case P:
                  if($PID == $tp_playerid){
                    echo "<input type='hidden' name='tid' value='".$TID."'>";
                    echo "<input type='hidden' name='txtplayerid' value='".$tp_playerid."'>";
                    echo "<input type='submit' name='cmdAccept' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_97")."' class='mainoption'>";
                  }else{
                    echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                  }
                  break;
                case A:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                  break;
              }

              echo "</td></tr>";

              $nswitch++;
              $ncount++;

            }

            echo "</table><br>";

            $nswitch=0;
            $ngroupcount++;
          }

        }else{

          $ncount = 0;

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
          echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_100")."</td></tr>";

          while($ncount < $num1){

            $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
            $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

            echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
            echo "<td class='row2' width='300'>";

            switch($tp_status){
              case P:
                if($PID == $tp_playerid){
                  echo "<input type='hidden' name='tid' value='".$TID."'>";
                  echo "<input type='hidden' name='txtplayerid' value='".$tp_playerid."'>";
                  echo "<input type='submit' name='cmdAccept' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_97")."' class='mainoption'>";
                }else{
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                }
                break;
              case A:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                break;
            }

            echo "</td></tr>";

            $ncount++;
          }

          echo "</table><br>";

        }

      }

    }

  }


  /**********************************************************************
  * AcceptTournament
  *
  */
  function AcceptTournament($ConfigFile, $TID, $PID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID." AND t_cutoffdate >= NOW()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE c4m_tournamentplayers SET tp_status='A' WHERE tp_tournamentid=".$TID." AND tp_playerid=".$PID;
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetTournamentInfo
  *
  */
  function GetTournamentProposal($ConfigFile){

    $query = "SELECT * FROM c4m_tournament WHERE t_status='P'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    if($num != 0){

      $i = 0;
      while($i < $num){
        $t_id = trim(mysql_result($return, $i, "t_id"));
        $t_name = trim(mysql_result($return, $i, "t_name"));
        $t_type = trim(mysql_result($return, $i, "t_type"));
        $t_playernum = trim(mysql_result($return, $i, "t_playernum"));
        $t_cutoffdate = trim(mysql_result($return, $i, "t_cutoffdate"));
        $t_startdate = trim(mysql_result($return, $i, "t_startdate"));
        $t_comment = trim(mysql_result($return, $i, "t_comment"));
        $t_status = trim(mysql_result($return, $i, "t_status"));

        echo "<tr><td><a href='./admin_view_proposal.php?tid=".$t_id."'>".$t_name."</a></td></tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * ViewTournamentProposal
  *
  */
  function ViewTournamentProposal($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $t_id = trim(mysql_result($return,0,"t_id"));
      $t_name = trim(mysql_result($return,0,"t_name"));
      $t_type = trim(mysql_result($return,0,"t_type"));
      $t_playernum = trim(mysql_result($return,0,"t_playernum"));
      $t_cutoffdate = trim(mysql_result($return,0,"t_cutoffdate"));
      $t_startdate = trim(mysql_result($return,0,"t_startdate"));
      $t_comment = trim(mysql_result($return,0,"t_comment"));
      $t_status = trim(mysql_result($return,0,"t_status"));

      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
      echo "<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$t_name."</font><b></td></tr>";

      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_88")."</td>";
      echo "<td class='row2'>";
      switch($t_type){
       case 0:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_89")."";
         break;
       case 1:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_90")."";
         break;
       case 2:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_91")."";
         break;
       case 3:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_92")."";
         break;
      }

      echo "</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_93")."</td><td class='row2'>".$t_cutoffdate."</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_94")."</td><td class='row2'>".$t_startdate."</td></tr>";
      echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_95")."</td></tr>";
      echo "<tr><td class='row2' colspan='2'><span>".$t_comment."</span></td></tr>";
      echo "</table><br>";

      $query1 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $npcount = $t_playernum;
        $ncount = 0;
        $ngroupcount = 1;
        $nswitch = 0;

        if($npcount != 0 && $t_type != 0 && $t_type != 3){

          while($ncount < $num1){
            echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
            echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_96")."".$ngroupcount."</td></tr>";

            while($nswitch < $npcount && $ncount < $num1){
              $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
              $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

              echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
              echo "<td class='row2' width='300'>";

              switch($tp_status){
                case P:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                  break;
                case A:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                  break;
                case L:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_101")."";
                  break;
                case D:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_102")."";
                  break;
              }

              echo "</td></tr>";

              $nswitch++;
              $ncount++;

            }

            echo "</table><br>";

            $nswitch=0;
            $ngroupcount++;
          }

        }else{

          $ncount = 0;

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
          echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_100")."</td></tr>";

          while($ncount < $num1){
            $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
            $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

            echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
            echo "<td class='row2' width='300'>";

            switch($tp_status){
              case P:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                break;
              case A:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                break;
              case L:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_101")."";
                break;
              case D:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_102")."";
                break;
            }

            echo "</td></tr>";

            $ncount++;
          }

          echo "</table><br>";

        }

        $query3 = "SELECT COUNT(*) FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID;
        $return3 = mysql_query($query3, $this->link) or die(mysql_error());
        $num3 = mysql_numrows($return3);

        $roundNumber = 0;

        if($num3 != 0){
          $roundNumber = mysql_result($return3, 0, 0);
        }

        $query4 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID." ORDER BY tm_id DESC";
        $return4= mysql_query($query4, $this->link) or die(mysql_error());
        $num4 =  mysql_numrows($return4);

        $endtime = "";
        $starttime = "";

        if($num4 != 0){
          $endtime = trim(mysql_result($return4, 0, "tm_endtime"));
          $starttime = trim(mysql_result($return4, 0, "tm_starttime"));
        }

        $queryt = "SELECT NOW()";
        $returnt= mysql_query($queryt, $this->link) or die(mysql_error());
        $numt =  mysql_numrows($returnt);

        $ctime1 = "";

        if($numt != 0){
          $ctime1 = trim(mysql_result($returnt, 0, 0));
        }

        if($endtime != "" && $starttime != "" && $ctime1 != ""){

          list($edate, $etime) = explode(" ", $endtime,2);
          list($sdate, $stime) = explode(" ", $starttime,2);
          list($cdate, $ctime) = explode(" ", $ctime1,2);

          list($ehr, $emin, $esec) = explode(":", $etime,3);
          list($shr, $smin, $ssec) = explode(":", $stime,3);
          list($chr, $cmin, $csec) = explode(":", $ctime,3);

          //cur time
          echo "<input type='hidden' name='chr' value='".$chr."'>";
          echo "<input type='hidden' name='cmin' value='".$cmin."'>";
          echo "<input type='hidden' name='csec' value='".$csec."'>";

          //End time
          echo "<input type='hidden' name='ehr' value='".$ehr."'>";
          echo "<input type='hidden' name='emin' value='".$emin."'>";
          echo "<input type='hidden' name='esec' value='".$esec."'>";

        }else{

          //start time
          echo "<input type='hidden' name='shr' value='0'>";
          echo "<input type='hidden' name='smin' value='0'>";
          echo "<input type='hidden' name='ssec' value='0'>";

          //End time
          echo "<input type='hidden' name='ehr' value='0'>";
          echo "<input type='hidden' name='emin' value='0'>";
          echo "<input type='hidden' name='esec' value='0'>";

        }

        if($t_status == "A"){

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
          echo "<input type='hidden' name='txtcommand'>";
          echo "<tr><td class='row1' align='right'><input type='hidden' name='time'><input type='button' name='cmdStart' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_103")."' class='mainoption' onclick=\"javascript:ExecuteCommand('S');\"></td></tr>";
          echo "</table>";

        }else{

          if($t_status == "S"){

            echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
            echo "<tr><td class='row2' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_104")."</td></tr>";
            echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_105")."</td>";
            echo "<td class='row1'>";

            echo "<select size='1' name='slctTMonth'>";
            $this->selectmonths();
            echo "</select>";

            echo "<select size='1' name='slctTDay'>";
            echo "<option value=''>--</option>";
            echo "<option value='01'>01</option>";
            echo "<option value='02'>02</option>";
            echo "<option value='03'>03</option>";
            echo "<option value='04'>04</option>";
            echo "<option value='05'>05</option>";
            echo "<option value='06'>06</option>";
            echo "<option value='07'>07</option>";
            echo "<option value='08'>08</option>";
            echo "<option value='09'>09</option>";
            echo "<option value='10'>10</option>";
            echo "<option value='11'>11</option>";
            echo "<option value='12'>12</option>";
            echo "<option value='13'>13</option>";
            echo "<option value='14'>14</option>";
            echo "<option value='15'>15</option>";
            echo "<option value='16'>16</option>";
            echo "<option value='17'>17</option>";
            echo "<option value='18'>18</option>";
            echo "<option value='19'>19</option>";
            echo "<option value='20'>20</option>";
            echo "<option value='21'>21</option>";
            echo "<option value='22'>22</option>";
            echo "<option value='23'>23</option>";
            echo "<option value='24'>24</option>";
            echo "<option value='25'>25</option>";
            echo "<option value='26'>26</option>";
            echo "<option value='27'>27</option>";
            echo "<option value='28'>28</option>";
            echo "<option value='29'>29</option>";
            echo "<option value='30'>30</option>";
            echo "<option value='31'>31</option>";
            echo "</select>";

            echo "<select size='1' name='slctTYear'>";
            $this->selectyear();
            echo "</select>";
            echo "</td></tr>";

            echo "<tr><td class='row1'>Start Time</td><td class='row2'><input type='text' name='txtTStartTime' class='post'></td></tr>";
            echo "<tr><td class='row2' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_106")."</td></tr>";
            echo "</table>";

            echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
            echo "<input type='hidden' name='txtcommand'>";
            echo "<tr><td class='row1' align='right'><input type='hidden' name='time' class='post'>".$endtime." <input type='button' name='cmdStart' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_107")."".($roundNumber+1)."' class='mainoption' onclick=\"javascript:ExecuteCommand('S');\"></td></tr>";
            echo "</table>";

          }else{

            if($t_status == "C"){

              echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
              echo "<input type='hidden' name='txtcommand'>";
              echo "<tr><td class='row1' align='right'><input type='hidden' name='time'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_108")."</td></tr>";
              echo "</table>";

            }else{

              echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
              echo "<input type='hidden' name='txtcommand'>";
              echo "<tr><td class='row1' align='right'><input type='hidden' name='time'><input type='button' name='cmdaccept' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_109")."' class='mainoption' onclick=\"javascript:ExecuteCommand('A');\"> <input type='button' name='cmdrevoke' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_110")."' class='mainoption' onclick=\"javascript:ExecuteCommand('R');\"></td></tr>";
              echo "</table>";

            }

          }

        }

      }

    }

  }


  /**********************************************************************
  * selectmonths
  *
  */
  function selectmonths(){

     //$aMonth = array("January","February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
     $aMonth = array($this->GetStringFromStringTable("IDS_SELECT_MONTH_1"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_2"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_3"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_4"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_5"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_6"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_7"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_8"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_9"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_10"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_11"),
                     $this->GetStringFromStringTable("IDS_SELECT_MONTH_12"));

     $ncount = 0;

     echo "<option value=''>--</option>";

     while ($ncount < 12){
        echo "<option value='".($ncount+1)."'>".$aMonth[$ncount]."</option>";
        $ncount++;
     }

  }


  /**********************************************************************
  * selectyear
  *
  */
  function selectyear(){
    $today = getdate();
    $date = $today['year'] + 50;

    $ncount = $today['year'];

    echo "<option value=''>--</option>";

     while ($ncount <= $date){
        echo "<option value='".$ncount."'>".$ncount."</option>";
        $ncount++;
     }
  }


  /**********************************************************************
  * TeamVSTeamPlayerOrganizer
  *
  */
  function TeamVSTeamPlayerOrganizer($ConfigFile, $TID, $TIME){

    //////////////////////////////////////////////////////
    $time = time();

    if($TIME != ""){
      $time = $TIME;
    }

    $mtime = date("Y-m-d H:i:s", $time);
    //////////////////////////////////////////////////////

    $query = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID.""; // AND c4m_tournamentplayers.tp_status='A'
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $query1 = "SELECT * FROM c4m_tournament WHERE t_id =".$TID."";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $PlayerPerGroup = 0;
    if($num1 != 0){
      $PlayerPerGroup = mysql_result($return1,0,"t_playernum");
    }

    $ListOfTeams = array();
    $ListOfTeams = $this->GetTournamentGameTVTTeamList($ConfigFile, $TID);
    $npp = count($ListOfTeams);

    //echo "#of teams: ".$num."<br>";

    if($npp > 1 || $npp == 0  && $num != 0){

      $query2 = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID."";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      //echo $num2." c4m_tournamentteams <br>";

      if($num2 == 0){

        //create the teams
        $nGroupSwitch = 0;
        $nGroupNum = 1;

        $i = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          $tp_status = trim(mysql_result($return,$i,"tp_status"));

          if($nGroupSwitch < $PlayerPerGroup){

            if($tp_status != "P"){
              //echo $nGroupNum." ".$userid."<br>";

              $insert = "INSERT INTO c4m_tournamentteams VALUES(NULL, $nGroupNum, $TID, $player_id)";
              mysql_query($insert, $this->link) or die(mysql_error());

              //create the team points record if needed
              $queryttp = "SELECT * FROM c4m_tournamentteampoints WHERE ttp_tournamentid =".$TID." AND ttp_teamid=".$nGroupNum;
              $returnttp = mysql_query($queryttp, $this->link) or die(mysql_error());
              $numttp = mysql_numrows($returnttp);

              if($numttp == 0){

                $insertttp = "INSERT INTO c4m_tournamentteampoints VALUES(NULL, $nGroupNum, $TID, 0, 0)";
                mysql_query($insertttp, $this->link) or die(mysql_error());

              }

            }

          }else{

            $nGroupNum++;
            $nGroupSwitch = 0;

            if($tp_status != "P"){
              //echo $nGroupNum." ".$userid."<br>";

              $insert = "INSERT INTO c4m_tournamentteams VALUES(NULL, $nGroupNum, $TID, $player_id)";
              mysql_query($insert, $this->link) or die(mysql_error());
            }
          }

          $nGroupSwitch++;
          $i++;

        }

      }

      //create the new match (if needed)
      $query3 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." AND tm_status='A'";
      $return3 = mysql_query($query3, $this->link) or die(mysql_error());
      $num3 = mysql_numrows($return3);

      $tgtmid = 0;

      if($num3 == 0){

        $insertmatch = "INSERT INTO c4m_tournamentmatches VALUES(NULL, $TID, 'A', '".$mtime."', DATE_ADD('".$mtime."', INTERVAL 5 MINUTE))";
        mysql_query($insertmatch, $this->link) or die(mysql_error());

        $queryid = "SELECT last_insert_id()";
        $returnid = mysql_query($queryid, $this->link) or die(mysql_error());
        $numid = mysql_numrows($returnid);

        if($numid != 0){
          $tgtmid = mysql_result($returnid,0,0);

          //reset the team points
          $insertmatch = "UPDATE c4m_tournamentteampoints SET ttp_wins=0, ttp_loss=0 WHERE ttp_tournamentid=".$TID;
          mysql_query($insertmatch, $this->link) or die(mysql_error());
        }

      }else{
        $tgtmid = mysql_result($return3,0,0);

        //echo "match created: ".$tgtmid;

        //Update the match times
        $insertmatch = "UPDATE c4m_tournamentmatches SET tm_starttime = DATE_ADD('".$mtime."', INTERVAL 1 MINUTE), tm_endtime = DATE_ADD('".$mtime."', INTERVAL 6 MINUTE) WHERE tm_id=".$tgtmid;
        mysql_query($insertmatch, $this->link) or die(mysql_error());

      }

      //Create the games
      //Get all the prvious games
      $query4 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid =".$tgtmid." AND tg_status='C'";
      $return4 = mysql_query($query4, $this->link) or die(mysql_error());
      $num4 = mysql_numrows($return4);

      // Get group Count
      $query5 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID;
      $return5 = mysql_query($query5, $this->link) or die(mysql_error());
      $num5 = mysql_numrows($return5);

      //Create the team list array
      $TeamsList = array();

      if($num5 != 0){

        $nTeamCount = 0;

        $nTtmpCount = 0;
        while($nTtmpCount < $num5){

          $TeamID =  mysql_result($return5, $nTtmpCount,"tt_teamid");

          // We need to check if the team is still allowed to play
          $querypid = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID." AND tt_teamid=".$TeamID." LIMIT 1";
          $returnpid = mysql_query($querypid, $this->link) or die(mysql_error());
          $numpid = mysql_numrows($returnpid);

          $tt_playerid = mysql_result($returnpid, 0,"tt_playerid");

          //echo $tt_playerid." ::: ";

          $query7 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID." AND tp_playerid=".$tt_playerid."";
          $return7 = mysql_query($query7, $this->link) or die(mysql_error());
          $num7 = mysql_numrows($return7);

          if($num7 != 0){

            $tp_status = mysql_result($return7, 0,"tp_status");

            //echo "".$tp_status."<br>";

            if($tp_status != "L"){

              $TeamsList[$nTeamCount] = $TeamID;
              $nTeamCount++;

            }

          }

          $nTtmpCount++;

         }

      }

      //create the games now
      //$TeamsList

      //print_r($TeamsList);
      //echo "<br>";

      $nTeamCount = count($TeamsList);
      //echo $nTeamCount."<br>";

      $nCount = 0;

      while($nCount < $nTeamCount){

        //Check if in game
        $query6 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return6 = mysql_query($query6, $this->link) or die(mysql_error());
        $num6 = mysql_numrows($return6);

        $query66 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_otherteamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return66 = mysql_query($query66, $this->link) or die(mysql_error());
        $num66 = mysql_numrows($return66);

        if($num6 == 0 && $num66 == 0){

          $query8 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='C'";
          $return8 = mysql_query($query8, $this->link) or die(mysql_error());
          $num8 = mysql_numrows($return8);

          $playedTeams = $TeamsList[$nCount];

          if($num8 !=0){
            $countplayed = 0;

            while($countplayed < $num8){

              //Create the played teams list
              if($playedTeams == ""){

              }else{
                $playedTeams = $playedTeams.",".mysql_result($return8, $countplayed,"ttvt_otherteamid");
              }

              $countplayed++;
            }

          }

          //echo $playedTeams."<br>";

          $TeamsListcounter = 0;

          while($TeamsListcounter < $nTeamCount){

            if($TeamsList[$nCount] != $TeamsList[$TeamsListcounter]){

              //check if the team is alreadey in a new game
              $query9 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return9 = mysql_query($query9, $this->link) or die(mysql_error());
              $num9 = mysql_numrows($return9);

              $query99 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_otherteamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return99 = mysql_query($query99, $this->link) or die(mysql_error());
              $num99 = mysql_numrows($return99);

              if($num9 == 0 && $num99 == 0){
                //Check if we already played the team in current match

                $query10 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_otherteamid =".$TeamsList[$TeamsListcounter]." AND ttvt_status='C'";
                $return10 = mysql_query($query10, $this->link) or die(mysql_error());
                $num10 = mysql_numrows($return10);

                $query11 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_otherteamid =".$TeamsList[$nCount]." AND ttvt_status='C'";
                $return11 = mysql_query($query11, $this->link) or die(mysql_error());
                $num11 = mysql_numrows($return11);

                if($num10 == 0 && $num11 == 0){

                  //echo "<br>".$TeamsList[$nCount]." VS ".$TeamsList[$TeamsListcounter]."<br>";

                  $insert1 = "INSERT INTO c4m_tournamentteamvsteam VALUES(NULL, $tgtmid, $TeamsList[$nCount], $TeamsList[$TeamsListcounter], 'A')";
                  mysql_query($insert1, $this->link) or die(mysql_error());

                  //create the games for each team member
                  $query11 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$TeamsList[$nCount]." AND tt_tournamentid=".$TID;
                  $return11 = mysql_query($query11, $this->link) or die(mysql_error());
                  $num11 = mysql_numrows($return11);

                  $query12 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$TeamsList[$TeamsListcounter]." AND tt_tournamentid=".$TID;
                  $return12 = mysql_query($query12, $this->link) or die(mysql_error());
                  $num12 = mysql_numrows($return12);

                  if($num11 != 0 && $num12 != 0){

                     //Create the arrays
                     $PlayerPoints1 = array();
                     $PlayerPoints2 = array();

                     $nGroup1Count = 0;
                     while($nGroup1Count < $num11){

                       $tt_teamid = mysql_result($return11, $nGroup1Count,"tt_teamid");
                       $tt_tournamentid = mysql_result($return11, $nGroup1Count,"tt_tournamentid");
                       $tt_playerid = mysql_result($return11, $nGroup1Count,"tt_playerid");

                       $wins = 0;
                       $loss = 0;
                       $draws = 0;

                       $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $tt_playerid, $wins, $loss, $draws);

                       if($this->ELOIsActive()){
                         $points = $this->ELOGetRating($tt_playerid);
                       }else{
                         $points = $this->GetPointValue($wins, $loss, $draws);
                       }

                       $this->GetPointRanking($points, $wins);

                       $PlayerPoints1[$nGroup1Count]['PlayerID'] = $tt_playerid;
                       $PlayerPoints1[$nGroup1Count]['TID'] = $tt_tournamentid;
                       $PlayerPoints1[$nGroup1Count]['Points'] = $points;

                       $nGroup1Count++;
                     }

                     $nGroup2Count = 0;
                     while($nGroup2Count < $num12){

                       $tt_teamid = mysql_result($return12, $nGroup2Count,"tt_teamid");
                       $tt_tournamentid = mysql_result($return12, $nGroup2Count,"tt_tournamentid");
                       $tt_playerid = mysql_result($return12, $nGroup2Count,"tt_playerid");

                       $wins = 0;
                       $loss = 0;
                       $draws = 0;

                       $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $tt_playerid, $wins, $loss, $draws);

                       if($this->ELOIsActive()){
                         $points = $this->ELOGetRating($tt_playerid);
                       }else{
                         $points = $this->GetPointValue($wins, $loss, $draws);
                       }

                       $this->GetPointRanking($points, $wins);

                       $PlayerPoints2[$nGroup2Count]['PlayerID'] = $tt_playerid;
                       $PlayerPoints2[$nGroup2Count]['TID'] = $tt_tournamentid;
                       $PlayerPoints2[$nGroup2Count]['Points'] = $points;

                       $nGroup2Count++;
                     }

                     $PlayerPoints1 = $this->array_csort($PlayerPoints1,'Points',SORT_DESC, SORT_NUMERIC);
                     $PlayerPoints2 = $this->array_csort($PlayerPoints2,'Points',SORT_DESC, SORT_NUMERIC);

                     $nLoopCount = 0;
                     //echo "<br><br>";
                     While( $nLoopCount < $nGroup1Count && $nLoopCount < $nGroup2Count){

                       //Create the tournament game
                       $gameid = $this->gen_unique();

                       $initiatorid = $PlayerPoints1[$nLoopCount]['PlayerID'];
                       $otherid = $PlayerPoints2[$nLoopCount]['PlayerID'];

                       $insertgame = "INSERT INTO game VALUES('".$gameid."', $initiatorid, $initiatorid, $otherid, 'A', 'I', ".$time.", NULL, 1, 1, 1, 1)";
                       mysql_query($insertgame, $this->link) or die(mysql_error());

                       $insertgamematch = "INSERT INTO c4m_tournamentgames VALUES(NULL, ".$tgtmid.", '".$gameid."', ".$initiatorid.", ".$otherid.", 'N', 'N', 'I' )";
                       mysql_query($insertgamematch, $this->link) or die(mysql_error());

                       //echo $PlayerPoints1[$nLoopCount]['PlayerID']." VS ".$PlayerPoints2[$nLoopCount]['PlayerID']." -- ".$gameid."<br>";

                       //////////////////////////////////////////////////////////////////////////
                       // Send email to user that he/she is in a game

    $conf = $this->conf;

                       $plre1 = $this->GetEmailByPlayerID($ConfigFile, $PlayerPoints1[$nLoopCount]['PlayerID']);
                       $plre2 = $this->GetEmailByPlayerID($ConfigFile, $PlayerPoints2[$nLoopCount]['PlayerID']);

                       $plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $PlayerPoints1[$nLoopCount]['PlayerID']);
                       $plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $PlayerPoints2[$nLoopCount]['PlayerID']);

                       $bodyp1 = "Hello, ".$plrn1.".<br>
                                  Your tournament game is as follows:<br>
                                  Game Type: Team vs Team<br>
                                  Game ID  : ".$gameid."<br>
                                  ".$plrn1." vs ".$plrn2."<br><br>
                                  ".$conf['site_url']." <br>The home of ".$conf['site_name']."<br>";

                       $bodyp2 = "Hello, ".$plrn2.".<br>
                                  Your tournament game is as follows:<br>
                                  Game Type: Team vs Team<br>
                                  Game ID  : ".$gameid."<br>
                                  ".$plrn1." vs ".$plrn2."<br><br>
                                  <a href='".$conf['site_url']."'>".$conf['site_url']."</a> <br>The home of ".$conf['site_name']."<br>";

                       $subject = $conf['site_name']." Tournament Game";

                       if($this->ChallangeNotification($PlayerPoints1[$nLoopCount]['PlayerID'])){
                         $this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
                       }

                       if($this->ChallangeNotification($PlayerPoints2[$nLoopCount]['PlayerID'])){
                         $this->SendEmail($plre2, $conf['registration_email'], $conf['site_name'], $subject, $bodyp2);
                       }
                       //////////////////////////////////////////////////////////////////////////

                       $nLoopCount++;
                     }

                  }

                  $TeamsListcounter = $nTeamCount;
                }

              }

            }

            $TeamsListcounter++;
          }

        }

        $nCount++;
      }

      $update = "UPDATE c4m_tournament SET t_status='S' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      //echo "Team #".$ListOfTeams[0]." Is the winner!!!!<br>";

      $update = "UPDATE c4m_tournament SET t_status='C' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());

      //email the team players that won the tournament
      $query13 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$ListOfTeams[0]." AND tt_tournamentid=".$TID;
      $return13 = mysql_query($query13, $this->link) or die(mysql_error());
      $num13 = mysql_numrows($return13);

      if($num13 != 0){

        $i = 0;
        while($i < $num13){

          $pid = mysql_result($return13, $i, "tt_playerid");

          ////////////////////////////////////////////////////////////////
          // Send email to users that he/she is in a game

    $conf = $this->conf;

          $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

          $name = $this->GetUserIDByPlayerID($ConfigFile, $pid);
          $email = $this->GetEmailByPlayerID($ConfigFile, $pid);

          $aTags = array("['name']", "['siteurl']", "['sitename']");
          $aReplaceTags = array($name, $this->TrimRSlash($conf['site_url']), $conf['site_name']);

          $body = str_replace($aTags, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_2"));

          if($this->ChallangeNotification($pid)){
            $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);
          }
          ////////////////////////////////////////////////////////////////

          $i++;

        }

      }

    }

  }


  /**********************************************************************
  * GetTournamentGameTVTTeamList
  *
  */
  function GetTournamentGameTVTTeamList($ConfigFile, $TID){

    // Get group Count
    $query5 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID;
    $return5 = mysql_query($query5, $this->link) or die(mysql_error());
    $num5 = mysql_numrows($return5);

    //Create the team list array
    $TeamsList = array();

    if($num5 != 0){

      $nTeamCount = 0;
      $nTtmpCount = 0;

      while($nTtmpCount < $num5){

        $TeamID =  mysql_result($return5, $nTtmpCount,"tt_teamid");

        //We need to check if the team is still allowed to play
        $querypid = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID." AND tt_teamid=".$TeamID." LIMIT 1";
        $returnpid = mysql_query($querypid, $this->link) or die(mysql_error());
        $numpid = mysql_numrows($returnpid);

        $tt_playerid = mysql_result($returnpid, 0,"tt_playerid");

        $query7 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID." AND tp_playerid=".$tt_playerid."";
        $return7 = mysql_query($query7, $this->link) or die(mysql_error());
        $num7 = mysql_numrows($return7);

        if($num7 != 0){

          $tp_status = mysql_result($return7, 0,"tp_status");

          if($tp_status != "L"){

            $TeamsList[$nTeamCount] = $TeamID;
            $nTeamCount++;

          }

        }

        $nTtmpCount++;

       }

    }

    return $TeamsList;

  }


  /**********************************************************************
  * UpdatePreviousTournamentgameTVT
  *
  */
  function UpdatePreviousTournamentgameTVT($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." ORDER BY tm_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      //Get the first item from the list (This is the last match)
      $tm_id = trim(mysql_result($return,0,"tm_id"));
      $tm_tournamentid = trim(mysql_result($return,0,"tm_tournamentid"));
      $tm_status = trim(mysql_result($return,0,"tm_status"));
      $tm_starttime = trim(mysql_result($return,0,"tm_starttime"));
      $tm_endtime = trim(mysql_result($return,0,"tm_endtime"));

      //Update the player and game status
      $query1 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." AND tg_status = 'I'";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $i=0;
        while($i < $num1){

          $tg_id = trim(mysql_result($return1,$i,"tg_id"));
          $tg_tmid = trim(mysql_result($return1,$i,"tg_tmid"));
          $tg_gameid = trim(mysql_result($return1,$i,"tg_gameid"));
          $tg_playerid = trim(mysql_result($return1,$i,"tg_playerid"));
          $tg_otherplayerid = trim(mysql_result($return1,$i,"tg_otherplayerid"));
          $tg_playerloggedin = trim(mysql_result($return1,$i,"tg_playerloggedin"));
          $tg_otherplayerloggedin = trim(mysql_result($return1,$i,"tg_otherplayerloggedin"));
          $tg_status = trim(mysql_result($return1,$i,"tg_status"));

          //Check if the players logged in for the game
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $this->GetGameInfoByRef($ConfigFile, $tg_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

          $GID = $tg_gameid;

          /////////////////////////////////////////////////////////////////////////
          //set the game as a draw
          if($tg_playerloggedin == "N" && $tg_otherplayerloggedin == "N"){

               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////

            $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the first player
          if($tg_playerloggedin=="N" && $tg_otherplayerloggedin == "Y"){

            if($tg_playerid == $w_player_id){

               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////

              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }else{

               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////

              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the second player
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "N"){

            if($tg_otherplayerid == $b_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////

              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //check who won the game if both players logged in
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "Y"){

            if($completion_status == "W"){
              //white player won

             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($this->ELOIsActive()){
               $bcurpoints = $this->ELOGetRating($b_player_id);
               $wcurpoints = $this->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

               //update points
               $this->ELOUpdateRating($b_player_id, $bnewpoints);
               $this->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////

              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
            }

            if($completion_status == "B"){
              //black player won
             ///////////////////////////////////////////////////////////////////////
             //ELO Point Calculation
             if($this->ELOIsActive()){
               $bcurpoints = $this->ELOGetRating($b_player_id);
               $wcurpoints = $this->ELOGetRating($w_player_id);

               //Calculate black player
               $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

               //Calculate white player
               $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

               //update points
               $this->ELOUpdateRating($b_player_id, $bnewpoints);
               $this->ELOUpdateRating($w_player_id, $wnewpoints);

             }
             ///////////////////////////////////////////////////////////////////////
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
              $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
            }

            if($completion_status == "I"){

              //check if the white player/ black player has moved

              $query2 = "SELECT * FROM move_history WHERE player_id=".$w_player_id." AND game_id ='".$GID."'";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              $query3 = "SELECT * FROM move_history WHERE player_id=".$b_player_id." AND game_id ='".$GID."'";
              $return3 = mysql_query($query3, $this->link) or die(mysql_error());
              $num3 = mysql_numrows($return3);

              if($num2 == 0 && $num3 != 0){
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 == 0){
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              }

              if($num2 == 0 && $num3 == 0){
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdateTeamPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 != 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
              }

            }

          }


          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

          $i++;
        }

        //update tournamentgames status to complete
        $update1 = "UPDATE c4m_tournamentgames SET tg_status='C' WHERE tg_tmid=".$tm_id;
        mysql_query($update1, $this->link) or die(mysql_error());

        //update c4m_tournamentteamvsteam status to complete
        $update2 = "UPDATE c4m_tournamentteamvsteam SET ttvt_status='C' WHERE ttvt_tmid=".$tm_id;
        mysql_query($update2, $this->link) or die(mysql_error());

      }

      //Check to see if we need to create a new match and remove a team
      // Get group Count
      $query11 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID;
      $return11 = mysql_query($query11, $this->link) or die(mysql_error());
      $num11 = mysql_numrows($return11);

      //Create the team list array
      $TeamsList = array();

      if($num11 != 0){

        $nTeamCount = 0;

        $nTtmpCount = 0;
        while($nTtmpCount < $num11){

          $TeamID =  mysql_result($return11, $nTtmpCount,"tt_teamid");

          //We need to check if the team is still allowed to play
          $querypid = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID." AND tt_teamid=".$TeamID." LIMIT 1";
          $returnpid = mysql_query($querypid, $this->link) or die(mysql_error());
          $numpid = mysql_numrows($returnpid);

          $tt_playerid = mysql_result($returnpid, 0,"tt_playerid");

          //echo $tt_playerid." ::: ";

          $query22 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID." AND tp_playerid=".$tt_playerid."";
          $return22 = mysql_query($query22, $this->link) or die(mysql_error());
          $num22 = mysql_numrows($return22);

          if($num22 != 0){

            $tp_status = mysql_result($return22, 0,"tp_status");

            //echo "".$tp_status."<br>";

            if($tp_status != "L"){

              $TeamsList[$nTeamCount] = $TeamID;
              $nTeamCount++;

            }

          }

          $nTtmpCount++;

         }

      }

      //create the games now
      //$TeamsList

      //print_r($TeamsList);
      //echo "<br>";

      $GamesToBePlayed = 0;

      $nTeamCount = count($TeamsList);
      $nCount = 0;

      while($nCount < $nTeamCount){

        //Check if in game
        $query33 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return33 = mysql_query($query33, $this->link) or die(mysql_error());
        $num33 = mysql_numrows($return33);

        $query44 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_otherteamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return44 = mysql_query($query44, $this->link) or die(mysql_error());
        $num44 = mysql_numrows($return44);

        if($num33 == 0 && $num44 == 0){

          $query55 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='C'";
          $return55 = mysql_query($query55, $this->link) or die(mysql_error());
          $num55 = mysql_numrows($return55);

          $playedTeams = $TeamsList[$nCount];

          if($num55 !=0){
            $countplayed = 0;

            while($countplayed < $num55){

              //Create the played teams list
              if($playedTeams == ""){

              }else{
                $playedTeams = $playedTeams.",".mysql_result($return55, $countplayed,"ttvt_otherteamid");
              }

              $countplayed++;
            }

          }

          $TeamsListcounter = 0;

          while($TeamsListcounter < $nTeamCount){

            if($TeamsList[$nCount] != $TeamsList[$TeamsListcounter]){

              //check if the team is already in a new game
              //echo $TeamsList[$TeamsListcounter]."<br>";
              $query66 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return66 = mysql_query($query66, $this->link) or die(mysql_error(). "15465");
              $num66 = mysql_numrows($return66);

              $query77 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_otherteamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return77 = mysql_query($query77, $this->link) or die(mysql_error());
              $num77 = mysql_numrows($return77);

              if($num66 == 0 && $num77 == 0){
                //Check if we already played the team in current match

                $query88 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_otherteamid =".$TeamsList[$TeamsListcounter]." AND ttvt_status='C'";
                $return88 = mysql_query($query88, $this->link) or die(mysql_error());
                $num88 = mysql_numrows($return88);

                $query99 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_otherteamid =".$TeamsList[$nCount]." AND ttvt_status='C'";
                $return99 = mysql_query($query99, $this->link) or die(mysql_error());
                $num99 = mysql_numrows($return99);

                if($num88 == 0 && $num99 == 0){

                  $GamesToBePlayed++;

                  $TeamsListcounter = $nTeamCount;
                }

              }

            }

            $TeamsListcounter++;

          }

        }

        $nCount++;

      }

      // update the game match/ remove a team
      //echo "Games To Be Played ".$GamesToBePlayed."<br>---------------------------------<br>";

      if($GamesToBePlayed == 0 ){

         //set game match to complete
         $update2= "UPDATE c4m_tournamentmatches SET tm_status='C' WHERE tm_id=".$tm_id;
         $return = mysql_query($update2, $this->link) or die(mysql_error());

         // remove a team
         $nTCount = count($TeamsList);
         $nTmpCount = 0;

         $TeamPoints = array();

         //get the teams points
         While($nTmpCount < $nTCount){

          $query100 = "SELECT * FROM c4m_tournamentteampoints WHERE ttp_teamid=".$TeamsList[$nTmpCount]." AND ttp_tournamentid=".$TID;
          $return100 = mysql_query($query100, $this->link) or die(mysql_error());
          $num100 = mysql_numrows($return100);

          if($num100 != 0){

            $ttp_id = mysql_result($return100, 0, "ttp_id");
            $ttp_wins = mysql_result($return100, 0, "ttp_wins");
            $ttp_loss = mysql_result($return100, 0, "ttp_loss");

            $points = $this->GetPointValue($ttp_wins, $ttp_loss, 0);
            $this->GetPointRanking($points, $ttp_wins);

            $TeamPoints[$nTmpCount]['TeamID'] = $TeamsList[$nTmpCount];
            $TeamPoints[$nTmpCount]['Wins'] = $ttp_wins;
            $TeamPoints[$nTmpCount]['Points'] = $points;

          }

           $nTmpCount++;

         }

         $TeamPoints = $this->array_csort($TeamPoints,'Points',SORT_DESC, SORT_NUMERIC);

         //echo "<br><pre>";
         //print_r($TeamPoints);
         //echo "</pre><br>";

         $nTmpCount = 0;
         $nLargestValue = $TeamPoints[0]['Points'];
         $nCanDelete = 0;

         While($nTmpCount < $nTCount){

           if($TeamPoints[$nTmpCount]['Points'] != $nLargestValue){
             $nCanDelete++;
           }

           $nTmpCount++;
         }

         if($nCanDelete != 0){

           $query101 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$TeamPoints[($nTCount-1)]['TeamID']." AND tt_tournamentid=".$TID;
           $return101 = mysql_query($query101, $this->link) or die(mysql_error());
           $num101 = mysql_numrows($return101);

           if($num101 != 0){

             $nteamplayercnt= 0;
             while($nteamplayercnt < $num101){

               $tt_playerid = mysql_result($return101, $nteamplayercnt,"tt_playerid");

               $update = "UPDATE c4m_tournamentplayers SET tp_status='L' WHERE tp_tournamentid=".$TID." AND tp_playerid=".$tt_playerid;
               mysql_query($update, $this->link) or die(mysql_error());

               $nteamplayercnt++;

             }

           }

         }

      }

    }

  }


  /**********************************************************************
  * UpdateTeamPoints
  *
  */
  function UpdateTeamPoints($ConfigFile, $TID, $PID, $Loss, $Win){

    //get the team that the player is a member of
    $query = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid=".$TID." AND tt_playerid=".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $tt_id = trim(mysql_result($return, 0,"tt_id"));
      $tt_teamid = trim(mysql_result($return, 0,"tt_teamid"));
      $tt_tournamentid = trim(mysql_result($return, 0,"tt_tournamentid"));
      $tt_playerid = trim(mysql_result($return, 0,"tt_playerid"));

      //Update the team points:

      //get the team that the player is a member of
      $query1 = "SELECT * FROM c4m_tournamentteampoints WHERE ttp_tournamentid=".$TID." AND ttp_teamid=".$tt_teamid;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $ttp_id = trim(mysql_result($return1, 0,"ttp_id"));
        $ttp_wins = trim(mysql_result($return1, 0,"ttp_wins"));
        $ttp_loss = trim(mysql_result($return1, 0,"ttp_loss"));

        $wins = (int) $ttp_wins + $Win;
        $losses = (int) $ttp_loss + $Loss;

        $update  = "UPDATE c4m_tournamentteampoints SET ttp_wins=".$wins.", ttp_loss=".$losses." WHERE ttp_id=".$ttp_id;
        mysql_query($update, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * RoundRobinPlayerOrganizer
  *
  */
  function RoundRobinPlayerOrganizer($ConfigFile, $TID, $TIME){

    //////////////////////////////////////////////////////
    $time = time();

    if($TIME != ""){
      $time = $TIME;
    }

    $mtime = date("Y-m-d H:i:s", $time);
    //////////////////////////////////////////////////////

    $query = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID." AND c4m_tournamentplayers.tp_status='A'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num > 1 && $num != 0){

      //remove a random player if the player count is odd
      $remainder = $num % 2;
      $randrow = -1;
      if($remainder > 0){
        $randrow = rand(0, ($num-1));
      }

      //Create the arrays
      $PlayerPoints = array();

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($i != $randrow){

          $wins = 0;
          $loss = 0;
          $draws = 0;

          $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);


          if($this->ELOIsActive()){
            $points = $this->ELOGetRating($player_id);
          }else{
            $points = $this->GetPointValue($wins, $loss, $draws);
          }

          $this->GetPointRanking($points, $wins);

          $PlayerPoints[$i]['PlayerID'] = $player_id;
          $PlayerPoints[$i]['UserID'] = $userid;
          $PlayerPoints[$i]['Points'] = $points;

        }else{
          //////////////////////////////////////////////////////////////////////////
          // Send email to user that he/she was dropped
          $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

          $aTags = array("['userid']", "['sitename']", "['siteurl']");
          $aReplaceTags = array($userid, $conf['site_name'], $this->TrimRSlash($conf['site_url']));

          $body = str_replace($aTags, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_3"));

          if($this->ChallangeNotification($player_id)){
            $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);
          }

          $this->UpdateTournamentPlayerStatus($ConfigFile, $TID, $player_id, "D");
          //////////////////////////////////////////////////////////////////////////
        }

        $i++;
      }

      // Sort the array
      $PlayerPoints = $this->array_csort($PlayerPoints,'Points',SORT_DESC, SORT_NUMERIC);

      //create the new match
      $insertmatch = "INSERT INTO c4m_tournamentmatches VALUES(NULL, $TID, 'A', '".$mtime."', DATE_ADD('".$mtime."', INTERVAL 5 MINUTE))";
      mysql_query($insertmatch, $this->link) or die(mysql_error());

      $queryid = "SELECT last_insert_id()";
      $returnid = mysql_query($queryid, $this->link) or die(mysql_error());
      $numid = mysql_numrows($returnid);

      $tgtid = 0;
      if($numid != 0){
        $tgtid = mysql_result($returnid,0,0);
      }

      ////////////////////////////////////////////////////////////////
      //Create the games
      $nPlayerCount = count($PlayerPoints);
      $iinc = 0;
      $idec = count($PlayerPoints);

      while($iinc < (int)$nPlayerCount/2){

        $gameid = $this->gen_unique();

        $initiatorid = $PlayerPoints[$iinc]['PlayerID'];
        $otherid = $PlayerPoints[($idec-1)]['PlayerID'];

        //echo $initiatorid." vs ".$otherid." -- ";
        //echo $gameid. " -- ". time();
        //echo "<br>";

        $insertgame = "INSERT INTO game VALUES('".$gameid."', $initiatorid, $initiatorid, $otherid, 'A', 'I', ".$time.", NULL, 1, 1, 1, 1)";
        mysql_query($insertgame, $this->link) or die(mysql_error());

        $insertgamematch = "INSERT INTO c4m_tournamentgames VALUES(NULL, ".$tgtid.", '".$gameid."', ".$initiatorid.", ".$otherid.", 'N', 'N', 'I' )";
        mysql_query($insertgamematch, $this->link) or die(mysql_error());

        ////////////////////////////////////////////////////////////////
        // Send email to users that he/she is in a game

    $conf = $this->conf;

        $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

        $initname = $this->GetUserIDByPlayerID($ConfigFile, $initiatorid);
        $othername = $this->GetUserIDByPlayerID($ConfigFile, $otherid);

        $aTags1 = array("['initname']", "['gameid']", "['othername']", "['siteurl']", "['sitename']");
        $aReplaceTags1 = array($initname, $gameid, $othername, $this->TrimRSlash($conf['site_url']), $conf['site_name']);

        $bodyinit = str_replace($aTags1, $aReplaceTags1, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_4"));
        $bodyoth = str_replace($aTags1, $aReplaceTags1, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_5"));

        $email1 = $this->GetEmailByPlayerID($ConfigFile, $initiatorid);
        $email2 = $this->GetEmailByPlayerID($ConfigFile, $otherid);

        if($email1 != "Unknown" && $this->ChallangeNotification($initiatorid)){
          $this->SendEmail($email1, $conf['registration_email'], $conf['site_name'], $subject, $bodyinit);
        }

        if($email2 != "Unknown" && $this->ChallangeNotification($otherid)){
          $this->SendEmail($email2, $conf['registration_email'], $conf['site_name'], $subject, $bodyoth);
        }

        ////////////////////////////////////////////////////////////////

        $idec--;
        $iinc++;
      }

      $update = "UPDATE c4m_tournament SET t_status='S' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      // Select the winner
      if($num == 1){
        $player_id = trim(mysql_result($return,0,"player_id"));
        $userid = trim(mysql_result($return,0,"userid"));
        $signup_time  = trim(mysql_result($return,0,"signup_time"));
        $email = trim(mysql_result($return,0,"email"));

        //echo $userid." Is the winner";

        //////////////////////////////////////////////////////////////////////////
        // Send email to user that he/she won

    $conf = $this->conf;

        $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

        $aTags2 = array("['userid']", "['siteurl']", "['sitename']");
        $aReplaceTags2 = array($userid, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
        $body = str_replace($aTags2, $aReplaceTags2, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_6"));

        if($this->ChallangeNotification($player_id)){
          $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);
        }
        //////////////////////////////////////////////////////////////////////////

        $update = "UPDATE c4m_tournament SET t_status='C' WHERE t_id=".$TID;
        mysql_query($update, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * GetEmailByPlayerID
  *
  */
  function GetEmailByPlayerID($ConfigFile, $ID){

    $query = "SELECT * FROM player WHERE player_id = ".$ID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $email = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_111");

    if($num != 0){

       $email  = trim(mysql_result($return,0,"email"));

    }

    return $email;

  }


  /**********************************************************************
  * SendEmail
  *
  * Params: $to, $fromemail, $fromname, $subject, $body
  */
  function SendEmail($to, $fromemail, $fromname, $subject, $body){

    // Advanced email configuration
    $query1 = "SELECT * FROM server_email_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $query2 = "SELECT * FROM smtp_settings WHERE o_id='1'";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    $bOld = true;

    $xsmtp = "";
    $xport = "";
    $xuser = "";
    $xpass = "";
    $xdomain= "";

    if($num1 != 0){
      $smtp = trim(mysql_result($return1,0,"o_smtp"));
      $port = trim(mysql_result($return1,0,"o_smtp_port"));

      $user = "";
      $pass = "";
      $domain = "";

      if($num2 != 0){
        $user = trim(mysql_result($return2,0,"o_user"));
        $pass = trim(mysql_result($return2,0,"o_pass"));
        $domain = trim(mysql_result($return2,0,"o_domain"));
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
	else	// No email settings, therefore do nothing.
	{
		return FALSE;
	}

    if($bOld){

      $headers1 .= "MIME-Version: 1.0\n";
      $headers1 .= "Content-type: text/html; charset=iso-8859-1\n";
      $headers1 .= "X-Priority: 1\n";
      $headers1 .= "X-MSMail-Priority: High\n";
      $headers1 .= "X-Mailer: php\n";
      $headers1 .= "From: \"".$fromname."\" <".$fromemail.">\n";

      // Now we send the message
      $send_check=mail($to,$subject,$body,$headers1);

    }else{

      require_once($this->adl."includes/phpmailer/class.phpmailer.php");

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
        mysql_query($insert, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * gen_unique
  *
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
  * LeagueAPlayerOrganizer
  *
  */
  function LeagueAPlayerOrganizer($ConfigFile, $TID, $TIME){

    //////////////////////////////////////////////////////
    $time = time();

    if($TIME != ""){
      $time = $TIME;
    }

    $mtime = date("Y-m-d H:i:s", $time);
    //////////////////////////////////////////////////////

    $query = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID." AND c4m_tournamentplayers.tp_status='A'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num > 1 && $num != 0){

      //Create the player list array
      //Create the arrays
      $PlayerList = array();

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        $PlayerList[$i] = $player_id;

        $i++;
      }

      //echo "-----------------------------<br>";
      //print_r($PlayerList);

      //create the new match (if needed)
      $query2 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." AND tm_status='A'";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      $tgtmid = 0;

      if($num2 == 0){

        $insertmatch = "INSERT INTO c4m_tournamentmatches VALUES(NULL, $TID, 'A', '".$mtime."', DATE_ADD('".$mtime."', INTERVAL 5 MINUTE))";
        mysql_query($insertmatch, $this->link) or die(mysql_error());

        $queryid = "SELECT last_insert_id()";
        $returnid = mysql_query($queryid, $this->link) or die(mysql_error());
        $numid = mysql_numrows($returnid);

        if($numid != 0){
           $tgtmid = mysql_result($returnid,0,0);
        }

        //echo "match Created: ".$tgtmid;

      }else{
        $tgtmid = mysql_result($return2,0,0);

        //echo "match Already created: ".$tgtmid;

        //Update the match times
        $insertmatch = "UPDATE c4m_tournamentmatches SET tm_starttime = DATE_ADD('".$mtime."', INTERVAL 1 MINUTE), tm_endtime = DATE_ADD('".$mtime."', INTERVAL 6 MINUTE) WHERE tm_id=".$tgtmid;
        mysql_query($insertmatch, $this->link) or die(mysql_error());

      }

      //Create the players match points record
      $query3 = "SELECT * FROM c4m_tournamentplayerpoints WHERE tpp_tournamentid=".$TID;
      $return3 = mysql_query($query3, $this->link) or die(mysql_error());
      $num3 = mysql_numrows($return3);

      if($num3 == 0){

        $nplrcount = count($PlayerList);
        $ii=0;
        while($ii < $nplrcount){

          $insert = "INSERT INTO c4m_tournamentplayerpoints VALUES(NULL, ".$PlayerList[$ii].", ".$TID.", 0, 0 )";
          mysql_query($insert, $this->link) or die(mysql_error());

          $ii++;

        }

      }

      //Create the games
      $nplrcount = count($PlayerList);
      $iii=0;
      while($iii < $nplrcount){

        //Check if in game
        $query4 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$iii]." AND tpvp_status='A'";
        $return4 = mysql_query($query4, $this->link) or die(mysql_error());
        $num4 = mysql_numrows($return4);

        $query5 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_otherplayerid=".$PlayerList[$iii]." AND tpvp_status='A'";
        $return5 = mysql_query($query5, $this->link) or die(mysql_error());
        $num5 = mysql_numrows($return5);

        if($num4 == 0 && $num5 == 0){

          $playerListcounter = 0;

          while($playerListcounter < $nplrcount){

            if($PlayerList[$iii] != $PlayerList[$playerListcounter]){

              //check if the player is already in a new game
              $query6 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$playerListcounter]." AND tpvp_status='A'";
              $return6 = mysql_query($query6, $this->link) or die(mysql_error());
              $num6 = mysql_numrows($return6);

              $query7 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_otherplayerid=".$PlayerList[$playerListcounter]." AND tpvp_status='A'";
              $return7 = mysql_query($query7, $this->link) or die(mysql_error());
              $num7 = mysql_numrows($return7);

              if($num6 == 0 && $num7 == 0){

                //Check if we already played the player in current match
                $query8 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$iii]." AND tpvp_otherplayerid =".$PlayerList[$playerListcounter]." AND tpvp_status='C'";
                $return8 = mysql_query($query8, $this->link) or die(mysql_error());
                $num8 = mysql_numrows($return8);

                $query9 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$playerListcounter]." AND tpvp_otherplayerid =".$PlayerList[$iii]." AND tpvp_status='C'";
                $return9 = mysql_query($query9, $this->link) or die(mysql_error());
                $num9 = mysql_numrows($return9);

                if($num8 == 0 && $num9 == 0){

                  $insert1 = "INSERT INTO c4m_tournamentplayervsplayer VALUES(NULL, $tgtmid, $PlayerList[$iii], $PlayerList[$playerListcounter], 'A')";
                  mysql_query($insert1, $this->link) or die(mysql_error());

                  //Create the tournament game
                  $gameid = $this->gen_unique();

                  $initiatorid = $PlayerList[$iii];
                  $otherid = $PlayerList[$playerListcounter];

                  $insertgame = "INSERT INTO game VALUES('".$gameid."', $initiatorid, $initiatorid, $otherid, 'A', 'I', ".$time.", NULL, 1, 1, 1, 1)";
                  mysql_query($insertgame, $this->link) or die(mysql_error());

                  $insertgamematch = "INSERT INTO c4m_tournamentgames VALUES(NULL, ".$tgtmid.", '".$gameid."', ".$initiatorid.", ".$otherid.", 'N', 'N', 'I' )";
                  mysql_query($insertgamematch, $this->link) or die(mysql_error());

                  //echo "<br>".$PlayerList[$iii]." VS ".$PlayerList[$playerListcounter]." -- ".$gameid."<br>";

                  //////////////////////////////////////////////////////////////////////////
                  // Send email to user that he/she is in a game

    $conf = $this->conf;

                  $plre1 = $this->GetEmailByPlayerID($ConfigFile, $initiatorid);
                  $plre2 = $this->GetEmailByPlayerID($ConfigFile, $otherid);

                  $plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $initiatorid);
                  $plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $otherid);

                  $aTags1 = array("['plrn1']", "['gameid']", "['plrn2']", "['siteurl']", "['sitename']");
                  $aReplaceTags = array($plrn1, $gameid, $plrn2, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
                  $bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_7"));
                  $bodyp2 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_8"));

                  $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

                  if($this->ChallangeNotification($initiatorid)){
                    $this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
                  }

                  if($this->ChallangeNotification($otherid)){
                    $this->SendEmail($plre2, $conf['registration_email'], $conf['site_name'], $subject, $bodyp2);
                  }
                  //////////////////////////////////////////////////////////////////////////

                  $playerListcounter = $nplrcount;
                }

              }

            }

            $playerListcounter++;

          }

        }

        $iii++;

      }

      $update = "UPDATE c4m_tournament SET t_status='S' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      //Tournament Completed
      // Select the winner
      if($num == 1){
        $player_id = trim(mysql_result($return,0,"player_id"));
        $userid = trim(mysql_result($return,0,"userid"));
        $signup_time  = trim(mysql_result($return,0,"signup_time"));
        $email = trim(mysql_result($return,0,"email"));

        //echo $userid." Is the winner";

        $update = "UPDATE c4m_tournament SET t_status='C' WHERE t_id=".$TID;
        mysql_query($update, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * UpdatePreviousTournamentgameLA
  *
  */
  function UpdatePreviousTournamentgameLA($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." ORDER BY tm_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $TMID = 0;

    if($num != 0){

      //Get the first item from the list (This is the last match)
      $tm_id = trim(mysql_result($return,0,"tm_id"));
      $tm_tournamentid = trim(mysql_result($return,0,"tm_tournamentid"));
      $tm_status = trim(mysql_result($return,0,"tm_status"));
      $tm_starttime = trim(mysql_result($return,0,"tm_starttime"));
      $tm_endtime = trim(mysql_result($return,0,"tm_endtime"));

      //Update the player and game status
      $query1 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." AND tg_status = 'I'";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $i=0;
        while($i < $num1){

          $tg_id = trim(mysql_result($return1,$i,"tg_id"));
          $tg_tmid = trim(mysql_result($return1,$i,"tg_tmid"));
          $tg_gameid = trim(mysql_result($return1,$i,"tg_gameid"));
          $tg_playerid = trim(mysql_result($return1,$i,"tg_playerid"));
          $tg_otherplayerid = trim(mysql_result($return1,$i,"tg_otherplayerid"));
          $tg_playerloggedin = trim(mysql_result($return1,$i,"tg_playerloggedin"));
          $tg_otherplayerloggedin = trim(mysql_result($return1,$i,"tg_otherplayerloggedin"));
          $tg_status = trim(mysql_result($return1,$i,"tg_status"));

          $TMID = $tg_tmid;

          //Check if the players logged in for the game
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $this->GetGameInfoByRef($ConfigFile, $tg_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

          $GID = $tg_gameid;

          /////////////////////////////////////////////////////////////////////////
          //set the game as a draw
          if($tg_playerloggedin == "N" && $tg_otherplayerloggedin == "N"){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
            $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the first player
          if($tg_playerloggedin=="N" && $tg_otherplayerloggedin == "Y"){

            if($tg_playerid == $w_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the second player
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "N"){

            if($tg_otherplayerid == $b_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //check who won the game if both players logged in
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "Y"){

            if($completion_status == "W"){
              //white player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
            }

            if($completion_status == "B"){
              //black player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
            }

            if($completion_status == "I"){

              //check if the white player/ black player has moved

              $query2 = "SELECT * FROM move_history WHERE player_id=".$w_player_id." AND game_id ='".$GID."'";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              $query3 = "SELECT * FROM move_history WHERE player_id=".$b_player_id." AND game_id ='".$GID."'";
              $return3 = mysql_query($query3, $this->link) or die(mysql_error());
              $num3 = mysql_numrows($return3);

              if($num2 == 0 && $num3 != 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 == 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              }

              if($num2 == 0 && $num3 == 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 != 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
              }

            }

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

          $i++;

        }

        $tgtmid = $TMID;

        //update tournamentgames status to complete
        $update1 = "UPDATE c4m_tournamentgames SET tg_status='C' WHERE tg_tmid=".$tm_id;
        mysql_query($update1, $this->link) or die(mysql_error());

        //update c4m_tournamentplayervsplayer status to complete
        $update2 = "UPDATE c4m_tournamentplayervsplayer SET tpvp_status='C' WHERE tpvp_tmid=".$tm_id;
        mysql_query($update2, $this->link) or die(mysql_error());

      }

      $tgtmid = $TMID;

      ////////////////////////////////////////////////////////////////////////////
      //Check to see if we need to create a new match and remove a player

      $query11 = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID." AND c4m_tournamentplayers.tp_status='A'";
      $return11 = mysql_query($query11, $this->link) or die(mysql_error());
      $num11 = mysql_numrows($return11);

      //Create the arrays
      $PlayerList = array();

      if($num11 != 0){

        //Create the player list array
        $i = 0;
        while($i < $num11){

          $player_id = trim(mysql_result($return11,$i,"player_id"));
          $userid = trim(mysql_result($return11,$i,"userid"));
          $signup_time  = trim(mysql_result($return11,$i,"signup_time"));
          $email = trim(mysql_result($return11,$i,"email"));

          $PlayerList[$i] = $player_id;

          $i++;

        }

        //print_r($PlayerList);

      }

      // check to see if there are games still left in the match
      $GamesToBePlayed = 0;

      //echo "Games To Be Played ".$GamesToBePlayed."<br>";

      $nplrcount = count($PlayerList);
      $iii=0;

      while($iii < $nplrcount){

        //Check if in game
        $query12 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$iii]." AND tpvp_status='A'";
        $return12 = mysql_query($query12, $this->link) or die(mysql_error());
        $num12 = mysql_numrows($return12);

        $query13 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_otherplayerid=".$PlayerList[$iii]." AND tpvp_status='A'";
        $return13 = mysql_query($query13, $this->link) or die(mysql_error());
        $num13 = mysql_numrows($return13);

        if($num12 == 0 && $num13 == 0){

          $playerListcounter = 0;

          while($playerListcounter < $nplrcount){

            if($PlayerList[$iii] != $PlayerList[$playerListcounter]){

              //check if the player is already in a new game
              $query14 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$playerListcounter]." AND tpvp_status='A'";
              $return14 = mysql_query($query14, $this->link) or die(mysql_error());
              $num14 = mysql_numrows($return14);

              $query15 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_otherplayerid=".$PlayerList[$playerListcounter]." AND tpvp_status='A'";
              $return15 = mysql_query($query15, $this->link) or die(mysql_error());
              $num15 = mysql_numrows($return15);

              if($num14 == 0 && $num15 == 0){

                //Check if we already played the player in current match
                $query16 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$iii]." AND tpvp_otherplayerid =".$PlayerList[$playerListcounter]." AND tpvp_status='C'";
                $return16 = mysql_query($query16, $this->link) or die(mysql_error());
                $num16 = mysql_numrows($return16);

                $query17 = "SELECT * FROM c4m_tournamentplayervsplayer WHERE tpvp_tmid=".$tgtmid." AND tpvp_playerid=".$PlayerList[$playerListcounter]." AND tpvp_otherplayerid =".$PlayerList[$iii]." AND tpvp_status='C'";
                $return17 = mysql_query($query17, $this->link) or die(mysql_error());
                $num17 = mysql_numrows($return17);

                if($num16 == 0 && $num17 == 0){
                  $GamesToBePlayed++;
                  $playerListcounter = $nplrcount;
                }

              }

            }

            $playerListcounter++;

          }

        }

        $iii++;

      }

      //kill the current match and or a player
      //echo "Games To Be Played ".$GamesToBePlayed."<br>";

      if($GamesToBePlayed == 0 ){

         //set game match to complete
         $update2= "UPDATE c4m_tournamentmatches SET tm_status='C' WHERE tm_id=".$tm_id;
         $return = mysql_query($update2, $this->link) or die(mysql_error());

         //Remove a player
         $nPCount = count($PlayerList);
         $nTmpCount = 0;

         $PlayerPoints = array();

         //get the players points
         While($nTmpCount < $nPCount){

           $query18 = "SELECT * FROM c4m_tournamentplayerpoints WHERE tpp_playerid=".$PlayerList[$nTmpCount]." AND tpp_tournamentid=".$TID;
           $return18 = mysql_query($query18, $this->link) or die(mysql_error());
           $num18 = mysql_numrows($return18);

           if($num18 != 0){

             $tpp_id = mysql_result($return18, 0, "tpp_id");
             $tpp_wins = mysql_result($return18, 0, "tpp_wins");
             $tpp_loss = mysql_result($return18, 0, "tpp_loss");

             $points = $this->GetPointValue($tpp_wins, $tpp_loss, 0);
             $this->GetPointRanking($points, $tpp_wins);

             $PlayerPoints[$nTmpCount]['PlayerID'] = $PlayerList[$nTmpCount];
             $PlayerPoints[$nTmpCount]['Wins'] = $tpp_wins;
             $PlayerPoints[$nTmpCount]['Points'] = $points;

           }

           $nTmpCount++;
         }

         $PlayerPoints = $this->array_csort($PlayerPoints,'Points',SORT_DESC, SORT_NUMERIC);

         //echo "<br><pre>";
         //print_r($PlayerPoints);
         //echo "</pre><br>";

         $nTmpCount = 0;
         $nLargestValue = $PlayerPoints[0]['Points'];
         $nCanDelete = 0;

         While($nTmpCount < $nPCount){

           if($PlayerPoints[$nTmpCount]['Points'] != $nLargestValue){
             $nCanDelete++;
           }

           $nTmpCount++;
         }

         if($nCanDelete != 0){

           //echo "Deleted ".$PlayerPoints[($nPCount-1)]['PlayerID']."<br>";

           $update3 = "UPDATE c4m_tournamentplayers SET tp_status='L' WHERE tp_playerid=".$PlayerPoints[($nPCount-1)]['PlayerID']." AND tp_tournamentid=".$TID;
           mysql_query($update3, $this->link) or die(mysql_error());

         }

      }

    }

  }


  /**********************************************************************
  * UpdatePlayerPoints
  *
  */
  function UpdatePlayerPoints($ConfigFile, $TID, $PID, $Loss, $Win){

    $update = "UPDATE c4m_tournamentplayerpoints SET tpp_wins=".$Win.", tpp_loss=".$Loss." WHERE tpp_playerid=".$PID." AND tpp_tournamentid=".$TID;
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * LeagueBPlayerOrganizer
  *
  */
  function LeagueBPlayerOrganizer($ConfigFile, $TID, $TIME){

    //////////////////////////////////////////////////////
    $time = time();

    if($TIME != ""){
      $time = $TIME;
    }

    $mtime = date("Y-m-d H:i:s", $time);
    //////////////////////////////////////////////////////

    $query = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID.""; // AND c4m_tournamentplayers.tp_status='A'
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $query1 = "SELECT * FROM c4m_tournament WHERE t_id =".$TID."";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $PlayerPerGroup = 0;
    if($num1 != 0){
      $PlayerPerGroup = mysql_result($return1,0,"t_playernum");
    }

    $aTeams = array();
    $aTeams = $this->GetLeagueBTeams($ConfigFile, $TID);
    $nCTeams = count($aTeams);

    //echo "Team count".$nCTeams."<br>";

    if($nCTeams > 1 || $nCTeams == 0 && $num != 0){

      $query2 = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID."";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num2 == 0){
        //create the teams
        $nGroupSwitch = 0;
        $nGroupNum = 1;

        $i = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          $tp_status = trim(mysql_result($return,$i,"tp_status"));

          if($nGroupSwitch < $PlayerPerGroup){

            if($tp_status != "P" && $tp_status != "L"){
              //echo $nGroupNum." ".$userid."<br>";

              $insert = "INSERT INTO c4m_tournamentteams VALUES(NULL, $nGroupNum, $TID, $player_id)";
              mysql_query($insert, $this->link) or die(mysql_error());

              //create the player points record if needed
              $queryttp = "SELECT * FROM c4m_tournamentplayerpoints WHERE tpp_tournamentid=".$TID." AND tpp_playerid=".$player_id;
              $returnttp = mysql_query($queryttp, $this->link) or die(mysql_error());
              $numttp = mysql_numrows($returnttp);

              if($numttp == 0){
                $insert = "INSERT INTO c4m_tournamentplayerpoints VALUES(NULL, ".$player_id.", ".$TID.", 0, 0 )";
                mysql_query($insert, $this->link) or die(mysql_error());
              }

            }

          }else{

            $nGroupNum++;
            $nGroupSwitch = 0;

            if($tp_status != "P" && $tp_status != "L"){
              //echo $nGroupNum." ".$userid."<br>";

              $insert = "INSERT INTO c4m_tournamentteams VALUES(NULL, $nGroupNum, $TID, $player_id)";
              mysql_query($insert, $this->link) or die(mysql_error());

              //create the player points record if needed
              $queryttp = "SELECT * FROM c4m_tournamentplayerpoints WHERE tpp_tournamentid=".$TID." AND tpp_playerid=".$player_id;
              $returnttp = mysql_query($queryttp, $this->link) or die(mysql_error());
              $numttp = mysql_numrows($returnttp);

              if($numttp == 0){
                $insert = "INSERT INTO c4m_tournamentplayerpoints VALUES(NULL, ".$player_id.", ".$TID.", 0, 0 )";
                mysql_query($insert, $this->link) or die(mysql_error());
              }

            }

          }

          $nGroupSwitch++;
          $i++;

        }

      }

      //create the new match (if needed)
      $query3 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." AND tm_status='A'";
      $return3 = mysql_query($query3, $this->link) or die(mysql_error());
      $num3 = mysql_numrows($return3);

      $tgtmid = 0;

      if($num3 == 0){

        $insertmatch = "INSERT INTO c4m_tournamentmatches VALUES(NULL, $TID, 'A', '".$mtime."', DATE_ADD('".$mtime."', INTERVAL 5 MINUTE))";
        mysql_query($insertmatch, $this->link) or die(mysql_error());

        $queryid = "SELECT last_insert_id()";
        $returnid = mysql_query($queryid, $this->link) or die(mysql_error());
        $numid = mysql_numrows($returnid);

        if($numid != 0){
           $tgtmid = mysql_result($returnid,0,0);
        }

        //echo " match created: ".$tgtmid;

      }else{
        $tgtmid = mysql_result($return3,0,0);

        //echo " match already created: ".$tgtmid;

        //Update the match times
        $insertmatch = "UPDATE c4m_tournamentmatches SET tm_starttime = DATE_ADD('".$mtime."', INTERVAL 1 MINUTE), tm_endtime = DATE_ADD('".$mtime."', INTERVAL 6 MINUTE) WHERE tm_id=".$tgtmid;
        mysql_query($insertmatch, $this->link) or die(mysql_error());

      }

      // Get group Count
      $query5 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID;
      $return5 = mysql_query($query5, $this->link) or die(mysql_error());
      $num5 = mysql_numrows($return5);

      //Create the team list array
      $TeamsList = array();

      if($num5 != 0){

        $nTeamCount = 0;

        $nTtmpCount = 0;
        while($nTtmpCount < $num5){

          $TeamID =  mysql_result($return5, $nTtmpCount,"tt_teamid");

          $TeamsList[$nTeamCount] = $TeamID;
          $nTeamCount++;

          $nTtmpCount++;

         }

      }

      //print_r($TeamsList);
      //echo "<br>";

      //create the games now
      $nTeamCount = count($TeamsList);
      $nCount = 0;

      while($nCount < $nTeamCount){

        //Check if in game
        $query6 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return6 = mysql_query($query6, $this->link) or die(mysql_error());
        $num6 = mysql_numrows($return6);

        $query66 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_otherteamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return66 = mysql_query($query66, $this->link) or die(mysql_error());
        $num66 = mysql_numrows($return66);

        if($num6 == 0 && $num66 == 0){

          $query8 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='C'";
          $return8 = mysql_query($query8, $this->link) or die(mysql_error());
          $num8 = mysql_numrows($return8);

          $playedTeams = $TeamsList[$nCount];

          if($num8 !=0){
            $countplayed = 0;

            while($countplayed < $num8){

              //Create the played teams list
              if($playedTeams == ""){

              }else{
                $playedTeams = $playedTeams.",".mysql_result($return8, $countplayed,"ttvt_otherteamid");
              }

              $countplayed++;
            }

          }

          //echo $playedTeams."<br>";
          $TeamsListcounter = 0;

          while($TeamsListcounter < $nTeamCount){

            if($TeamsList[$nCount] != $TeamsList[$TeamsListcounter]){

              //check if the team is alreadey in a new game
              $query9 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return9 = mysql_query($query9, $this->link) or die(mysql_error());
              $num9 = mysql_numrows($return9);

              $query99 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_otherteamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return99 = mysql_query($query99, $this->link) or die(mysql_error());
              $num99 = mysql_numrows($return99);

              if($num9 == 0 && $num99 == 0){

                //Check if we already played the team in current match
                $query10 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_otherteamid =".$TeamsList[$TeamsListcounter]." AND ttvt_status='C'";
                $return10 = mysql_query($query10, $this->link) or die(mysql_error());
                $num10 = mysql_numrows($return10);

                $query11 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tgtmid." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_otherteamid =".$TeamsList[$nCount]." AND ttvt_status='C'";
                $return11 = mysql_query($query11, $this->link) or die(mysql_error());
                $num11 = mysql_numrows($return11);

                if($num10 == 0 && $num11 == 0){

                  //echo "<br>".$TeamsList[$nCount]." VS ".$TeamsList[$TeamsListcounter]."<br>";

                  $insert1 = "INSERT INTO c4m_tournamentteamvsteam VALUES(NULL, $tgtmid, $TeamsList[$nCount], $TeamsList[$TeamsListcounter], 'A')";
                  mysql_query($insert1, $this->link) or die(mysql_error());

                  //create the games for each team member
                  $query11 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$TeamsList[$nCount]." AND tt_tournamentid=".$TID;
                  $return11 = mysql_query($query11, $this->link) or die(mysql_error());
                  $num11 = mysql_numrows($return11);

                  $query12 = "SELECT * FROM c4m_tournamentteams WHERE tt_teamid=".$TeamsList[$TeamsListcounter]." AND tt_tournamentid=".$TID;
                  $return12 = mysql_query($query12, $this->link) or die(mysql_error());
                  $num12 = mysql_numrows($return12);

                  if($num11 != 0 && $num12 != 0){

                     //Create the arrays
                     $PlayerPoints1 = array();
                     $PlayerPoints2 = array();

                     $nGroup1Count = 0;
                     while($nGroup1Count < $num11){

                       $tt_teamid = mysql_result($return11, $nGroup1Count,"tt_teamid");
                       $tt_tournamentid = mysql_result($return11, $nGroup1Count,"tt_tournamentid");
                       $tt_playerid = mysql_result($return11, $nGroup1Count,"tt_playerid");

                       $wins = 0;
                       $loss = 0;
                       $draws = 0;

                       $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $tt_playerid, $wins, $loss, $draws);

                       if($this->ELOIsActive()){
                         $points = $this->ELOGetRating($tt_playerid);
                       }else{
                         $points = $this->GetPointValue($wins, $loss, $draws);
                       }
                       $this->GetPointRanking($points, $wins);

                       $PlayerPoints1[$nGroup1Count]['PlayerID'] = $tt_playerid;
                       $PlayerPoints1[$nGroup1Count]['TID'] = $tt_tournamentid;
                       $PlayerPoints1[$nGroup1Count]['Points'] = $points;


                       $nGroup1Count++;
                     }

                     $nGroup2Count = 0;
                     while($nGroup2Count < $num12){

                       $tt_teamid = mysql_result($return12, $nGroup2Count,"tt_teamid");
                       $tt_tournamentid = mysql_result($return12, $nGroup2Count,"tt_tournamentid");
                       $tt_playerid = mysql_result($return12, $nGroup2Count,"tt_playerid");

                       $wins = 0;
                       $loss = 0;
                       $draws = 0;

                       $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $tt_playerid, $wins, $loss, $draws);
                       if($this->ELOIsActive()){
                         $points = $this->ELOGetRating($tt_playerid);
                       }else{
                         $points = $this->GetPointValue($wins, $loss, $draws);
                       }
                       $this->GetPointRanking($points, $wins);

                       $PlayerPoints2[$nGroup2Count]['PlayerID'] = $tt_playerid;
                       $PlayerPoints2[$nGroup2Count]['TID'] = $tt_tournamentid;
                       $PlayerPoints2[$nGroup2Count]['Points'] = $points;

                       $nGroup2Count++;
                     }

                     //$PlayerPoints1 = $this->array_csort($PlayerPoints1,'Points',SORT_DESC, SORT_NUMERIC);
                     //$PlayerPoints2 = $this->array_csort($PlayerPoints2,'Points',SORT_DESC, SORT_NUMERIC);

                     $nLoopCount = 0;
                     //echo "<br><br>";
                     While( $nLoopCount < $nGroup1Count && $nLoopCount < $nGroup2Count){

                       //Create the tournament game
                       $gameid = $this->gen_unique();

                       $initiatorid = $PlayerPoints1[$nLoopCount]['PlayerID'];
                       $otherid = $PlayerPoints2[$nLoopCount]['PlayerID'];


                       $insertgame = "INSERT INTO game VALUES('".$gameid."', $initiatorid, $initiatorid, $otherid, 'A', 'I', ".$time.", NULL, 1, 1, 1, 1)";
                       mysql_query($insertgame, $this->link) or die(mysql_error());

                       $insertgamematch = "INSERT INTO c4m_tournamentgames VALUES(NULL, ".$tgtmid.", '".$gameid."', ".$initiatorid.", ".$otherid.", 'N', 'N', 'I' )";
                       mysql_query($insertgamematch, $this->link) or die(mysql_error());

                       //echo $PlayerPoints1[$nLoopCount]['PlayerID']." VS ".$PlayerPoints2[$nLoopCount]['PlayerID']." -- ".$gameid."<br>";

                       //////////////////////////////////////////////////////////////////////////
                       // Send email to user that he/she is in a game

    $conf = $this->conf;

                       $plre1 = $this->GetEmailByPlayerID($ConfigFile, $PlayerPoints1[$nLoopCount]['PlayerID']);
                       $plre2 = $this->GetEmailByPlayerID($ConfigFile, $PlayerPoints2[$nLoopCount]['PlayerID']);

                       $plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $PlayerPoints1[$nLoopCount]['PlayerID']);
                       $plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $PlayerPoints2[$nLoopCount]['PlayerID']);


                       $aTags1 = array("['plrn1']", "['gameid']", "['plrn2']", "['siteurl']", "['sitename']");
                       $aReplaceTags = array($plrn1, $gameid, $plrn2, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
                       $bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_9"));
                       $bodyp2 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_10"));

                       $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_1"));

                       if($this->ChallangeNotification($PlayerPoints1[$nLoopCount]['PlayerID'])){
                         $this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
                       }

                       if($this->ChallangeNotification($PlayerPoints2[$nLoopCount]['PlayerID'])){
                         $this->SendEmail($plre2, $conf['registration_email'], $conf['site_name'], $subject, $bodyp2);
                       }
                       //////////////////////////////////////////////////////////////////////////

                       $nLoopCount++;
                     }

                  }

                  $TeamsListcounter = $nTeamCount;

                }

              }

            }

            $TeamsListcounter++;

          }

        }

        $nCount++;

      }

      $update = "UPDATE c4m_tournament SET t_status='S' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      //echo "Team #".$aTeams[0]." Is the winner!!!!<br>";


      $update = "UPDATE c4m_tournament SET t_status='C' WHERE t_id=".$TID;
      mysql_query($update, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * GetLeagueBTeams
  *
  */
  function GetLeagueBTeams($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournamentplayers, player WHERE c4m_tournamentplayers.tp_playerid = player.player_id AND c4m_tournamentplayers.tp_tournamentid =".$TID.""; // AND c4m_tournamentplayers.tp_status='A'
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $query1 = "SELECT * FROM c4m_tournament WHERE t_id =".$TID."";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $PlayerPerGroup = 0;
    if($num1 != 0){
      $PlayerPerGroup = mysql_result($return1,0,"t_playernum");
    }

    $query2 = "SELECT * FROM c4m_tournamentteams_temp WHERE tt_tournamentid =".$TID."";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 == 0){

      //create the teams
      $nGroupSwitch = 0;
      $nGroupNum = 1;

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        $tp_status = trim(mysql_result($return,$i,"tp_status"));

        if($nGroupSwitch < $PlayerPerGroup){

          if($tp_status != "P" && $tp_status != "L"){
            //echo $nGroupNum." ".$userid."<br>";

            $insert = "INSERT INTO c4m_tournamentteams_temp VALUES(NULL, $nGroupNum, $TID, $player_id)";
            mysql_query($insert, $this->link) or die(mysql_error());

          }

        }else{
          $nGroupNum++;
          $nGroupSwitch = 0;

          if($tp_status != "P" && $tp_status != "L"){
            //echo $nGroupNum." ".$userid."<br>";

            $insert = "INSERT INTO c4m_tournamentteams_temp VALUES(NULL, $nGroupNum, $TID, $player_id)";
            mysql_query($insert, $this->link) or die(mysql_error());

          }

        }

        $nGroupSwitch++;
        $i++;

      }

    }

    // Get group Count
    $query5 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams_temp WHERE tt_tournamentid =".$TID;
    $return5 = mysql_query($query5, $this->link) or die(mysql_error());
    $num5 = mysql_numrows($return5);

    //Create the team list array
    $TeamsList = array();

    if($num5 != 0){

      $nTeamCount = 0;
      $nTtmpCount = 0;

      while($nTtmpCount < $num5){

        $TeamID =  mysql_result($return5, $nTtmpCount,"tt_teamid");
        $TeamsList[$nTeamCount] = $TeamID;
        $nTeamCount++;
        $nTtmpCount++;

       }

    }

    $delete = "DELETE FROM c4m_tournamentteams_temp WHERE tt_tournamentid =".$TID;
    mysql_query($delete, $this->link) or die(mysql_error());

    return $TeamsList;

  }


  /**********************************************************************
  * UpdatePreviousTournamentgameLB
  *
  */
  function UpdatePreviousTournamentgameLB($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." ORDER BY tm_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $TMID = 0;

    if($num != 0){

      //Get the first item from the list (This is the last match)
      $tm_id = trim(mysql_result($return,0,"tm_id"));
      $tm_tournamentid = trim(mysql_result($return,0,"tm_tournamentid"));
      $tm_status = trim(mysql_result($return,0,"tm_status"));
      $tm_starttime = trim(mysql_result($return,0,"tm_starttime"));
      $tm_endtime = trim(mysql_result($return,0,"tm_endtime"));

      //Update the player and game status
      $query1 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." AND tg_status = 'I'";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $i=0;
        while($i < $num1){

          $tg_id = trim(mysql_result($return1,$i,"tg_id"));
          $tg_tmid = trim(mysql_result($return1,$i,"tg_tmid"));
          $tg_gameid = trim(mysql_result($return1,$i,"tg_gameid"));
          $tg_playerid = trim(mysql_result($return1,$i,"tg_playerid"));
          $tg_otherplayerid = trim(mysql_result($return1,$i,"tg_otherplayerid"));
          $tg_playerloggedin = trim(mysql_result($return1,$i,"tg_playerloggedin"));
          $tg_otherplayerloggedin = trim(mysql_result($return1,$i,"tg_otherplayerloggedin"));
          $tg_status = trim(mysql_result($return1,$i,"tg_status"));

          $TMID = $tg_tmid;

          //Check if the players logged in for the game
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $this->GetGameInfoByRef($ConfigFile, $tg_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

          $GID = $tg_gameid;

          /////////////////////////////////////////////////////////////////////////
          //set the game as a draw
          if($tg_playerloggedin == "N" && $tg_otherplayerloggedin == "N"){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
            $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the first player
          if($tg_playerloggedin=="N" && $tg_otherplayerloggedin == "Y"){

            if($tg_playerid == $w_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the second player
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "N"){

            if($tg_otherplayerid == $b_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_otherplayerid, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $tg_playerid, 0, 1);
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //check who won the game if both players logged in
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "Y"){

            if($completion_status == "W"){
              //white player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
            }

            if($completion_status == "B"){
              //black player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
              $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
            }

            if($completion_status == "I"){

              //check if the white player/ black player has moved

              $query2 = "SELECT * FROM move_history WHERE player_id=".$w_player_id." AND game_id ='".$GID."'";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              $query3 = "SELECT * FROM move_history WHERE player_id=".$b_player_id." AND game_id ='".$GID."'";
              $return3 = mysql_query($query3, $this->link) or die(mysql_error());
              $num3 = mysql_numrows($return3);

              if($num2 == 0 && $num3 != 0){
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 == 0){
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              }

              if($num2 == 0 && $num3 == 0){
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $w_player_id, 1, 0);
                $this->UpdatePlayerPoints($ConfigFile, $tm_tournamentid, $b_player_id, 0, 1);
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 != 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
              }

            }

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

          $i++;

        }

        $tgtmid = $TMID;

        //update tournamentgames status to complete
        $update1 = "UPDATE c4m_tournamentgames SET tg_status='C' WHERE tg_tmid=".$tm_id;
        mysql_query($update1, $this->link) or die(mysql_error());

        //update c4m_tournamentteamvsteam status to complete
        $update2 = "UPDATE c4m_tournamentteamvsteam SET ttvt_status='C' WHERE ttvt_tmid=".$tm_id;
        mysql_query($update2, $this->link) or die(mysql_error());

      }

      $tgtmid = $TMID;

      // Get group Count
      $query5 = "SELECT DISTINCT tt_teamid FROM c4m_tournamentteams WHERE tt_tournamentid =".$TID;
      $return5 = mysql_query($query5, $this->link) or die(mysql_error());
      $num5 = mysql_numrows($return5);

      //Create the team list array
      $TeamsList = array();

      if($num5 != 0){

        $nTeamCount = 0;

        $nTtmpCount = 0;
        while($nTtmpCount < $num5){

          $TeamID =  mysql_result($return5, $nTtmpCount,"tt_teamid");

          $TeamsList[$nTeamCount] = $TeamID;
          $nTeamCount++;

          $nTtmpCount++;

         }

      }

      //print_r($TeamsList);
      //echo "<br>";

      $GamesToBePlayed = 0;

      $nTeamCount = count($TeamsList);
      $nCount = 0;

      while($nCount < $nTeamCount){

        //Check if in game
        $query33 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return33 = mysql_query($query33, $this->link) or die(mysql_error());
        $num33 = mysql_numrows($return33);

        $query44 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_otherteamid=".$TeamsList[$nCount]." AND ttvt_status='A'";
        $return44 = mysql_query($query44, $this->link) or die(mysql_error());
        $num44 = mysql_numrows($return44);

        if($num33 == 0 && $num44 == 0){

          $query55 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_status='C'";
          $return55 = mysql_query($query55, $this->link) or die(mysql_error());
          $num55 = mysql_numrows($return55);

          $playedTeams = $TeamsList[$nCount];

          if($num55 !=0){
            $countplayed = 0;

            while($countplayed < $num55){

              //Create the played teams list
              if($playedTeams == ""){

              }else{
                $playedTeams = $playedTeams.",".mysql_result($return55, $countplayed,"ttvt_otherteamid");
              }

              $countplayed++;

            }

          }

          $TeamsListcounter = 0;

          while($TeamsListcounter < $nTeamCount){

            if($TeamsList[$nCount] != $TeamsList[$TeamsListcounter]){

              //check if the team is already in a new game
              //echo $TeamsList[$TeamsListcounter]."<br>";
              $query66 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return66 = mysql_query($query66, $this->link) or die(mysql_error(). "15465");
              $num66 = mysql_numrows($return66);

              $query77 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_otherteamid=".$TeamsList[$TeamsListcounter]." AND ttvt_status='A'";
              $return77 = mysql_query($query77, $this->link) or die(mysql_error());
              $num77 = mysql_numrows($return77);

              if($num66 == 0 && $num77 == 0){

                //Check if we already played the team in current match
                $query88 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$nCount]." AND ttvt_otherteamid =".$TeamsList[$TeamsListcounter]." AND ttvt_status='C'";
                $return88 = mysql_query($query88, $this->link) or die(mysql_error());
                $num88 = mysql_numrows($return88);

                $query99 = "SELECT * FROM c4m_tournamentteamvsteam WHERE ttvt_tmid=".$tm_id." AND ttvt_teamid=".$TeamsList[$TeamsListcounter]." AND ttvt_otherteamid =".$TeamsList[$nCount]." AND ttvt_status='C'";
                $return99 = mysql_query($query99, $this->link) or die(mysql_error());
                $num99 = mysql_numrows($return99);

                if($num88 == 0 && $num99 == 0){

                  $GamesToBePlayed++;
                  $TeamsListcounter = $nTeamCount;

                }

              }

            }

            $TeamsListcounter++;

          }

        }

        $nCount++;

      }

      // update the game match/ remove players
      //echo "Games To Be Played ".$GamesToBePlayed."<br>---------------------------------<br>";

      if($GamesToBePlayed == 0 ){

         //set game match to complete
         $update2= "UPDATE c4m_tournamentmatches SET tm_status='C' WHERE tm_id=".$tm_id;
         $return = mysql_query($update2, $this->link) or die(mysql_error());

         // remove players
         $nActiveTeams = count($TeamsList);
         $nCounter = 0;

         while($nCounter < $nActiveTeams){

           //Get team players
           $query = "SELECT * FROM c4m_tournamentteams WHERE tt_tournamentid=".$TID." AND tt_teamid=".$TeamsList[$nCounter]."";
           $return = mysql_query($query, $this->link) or die(mysql_error());
           $num = mysql_numrows($return);

           if($num != 0){

             $TeamPlayerPoints = array();

             $i=0;
             while($i < $num){

               $tt_playerid = mysql_result($return, $i, "tt_playerid");

               $query2 = "SELECT * FROM c4m_tournamentplayerpoints WHERE tpp_playerid=".$tt_playerid." AND tpp_tournamentid=".$TID;
               $return2 = mysql_query($query2, $this->link) or die(mysql_error());
               $num2 = mysql_numrows($return2);

               if($num2 != 0){

                 $tpp_id = mysql_result($return2, 0, "tpp_id");
                 $tpp_wins = mysql_result($return2, 0, "tpp_wins");
                 $tpp_loss = mysql_result($return2, 0, "tpp_loss");

                 $points = $this->GetPointValue($tpp_wins, $tpp_loss, 0);
                 $this->GetPointRanking($points, $tpp_wins);

                 $TeamPlayerPoints[$i]['PID']= $tt_playerid;
                 $TeamPlayerPoints[$i]['Wins']= $tpp_wins;
                 $TeamPlayerPoints[$i]['Points']= $points;

               }

               $i++;

             }

             $TeamPlayerPoints = $this->array_csort($TeamPlayerPoints,'Points',SORT_DESC, SORT_NUMERIC);

             //echo "<br><pre>";
             //print_r($TeamPlayerPoints);
             //echo "</pre><br>";

             if($i != 0){

               //remove the worst player
               //echo "<br>---".$num2." [*] ".$TeamPlayerPoints[$i-1]['Points']."---<br>";
               if($num2 == 1 && $TeamPlayerPoints[$i-1]['Points'] >= 0){
                 //keep the player
               }else{

                 $update = "UPDATE c4m_tournamentplayers SET tp_status='L' WHERE tp_tournamentid=".$TID." AND tp_playerid=".$TeamPlayerPoints[$i-1]['PID'];
                 mysql_query($update, $this->link) or die(mysql_error());

               }

             }

           }

           $nCounter++;

         }

         //Remove players from team list
         $update = "DELETE FROM c4m_tournamentteams WHERE tt_tournamentid=".$TID;
         mysql_query($update, $this->link) or die(mysql_error());

         //remove points
         $update = "DELETE FROM c4m_tournamentplayerpoints WHERE tpp_tournamentid=".$TID;
         mysql_query($update, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * StartTournamentGameRound
  *
  */
  function StartTournamentGameRound($ConfigFile, $TID, $TIME){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $t_type = trim(mysql_result($return,0,"t_type"));

      //Update previous game rounds/remove loosing players
      $query1 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        /////////////////////////////////////////////////////////////
        //League A
        if($t_type == 0){
          $this->UpdatePreviousTournamentgameLA($ConfigFile, $TID);
        }

        /////////////////////////////////////////////////////////////
        //League B
        if($t_type == 1){
          $this->UpdatePreviousTournamentgameLB($ConfigFile, $TID);
        }

        /////////////////////////////////////////////////////////////
        //Team vs. Team
        if($t_type == 2){
          $this->UpdatePreviousTournamentgameTVT($ConfigFile, $TID);
        }
        /////////////////////////////////////////////////////////////
        //Round Robin
        if($t_type == 3){
          $this->UpdatePreviousTournamentgameRR($ConfigFile, $TID);
        }

      }

      /////////////////////////////////////////////////////////////
      //League A
      if($t_type == 0){
        $this->LeagueAPlayerOrganizer($ConfigFile, $TID, $TIME);
      }

      /////////////////////////////////////////////////////////////
      //League B
      if($t_type == 1){
        $this->LeagueBPlayerOrganizer($ConfigFile, $TID, $TIME);
      }

      /////////////////////////////////////////////////////////////
      //Team vs. Team
      if($t_type == 2){
        $this->TeamVSTeamPlayerOrganizer($ConfigFile, $TID, $TIME);
      }

      /////////////////////////////////////////////////////////////
      //Round Robin
      if($t_type == 3){
        $this->RoundRobinPlayerOrganizer($ConfigFile, $TID, $TIME);
      }

    }

  }


  /**********************************************************************
  * UpdatePreviousTournamentgameRR
  *
  */
  function UpdatePreviousTournamentgameRR($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid =".$TID." ORDER BY tm_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      //Get the first item from the list (This is the last match)
      $tm_id = trim(mysql_result($return,0,"tm_id"));
      $tm_tournamentid = trim(mysql_result($return,0,"tm_tournamentid"));
      $tm_status = trim(mysql_result($return,0,"tm_status"));
      $tm_starttime = trim(mysql_result($return,0,"tm_starttime"));
      $tm_endtime = trim(mysql_result($return,0,"tm_endtime"));

      //Update the player and game status
      $query1 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $i=0;
        while($i < $num1){

          $tg_id = trim(mysql_result($return1,$i,"tg_id"));
          $tg_tmid = trim(mysql_result($return1,$i,"tg_tmid"));
          $tg_gameid = trim(mysql_result($return1,$i,"tg_gameid"));
          $tg_playerid = trim(mysql_result($return1,$i,"tg_playerid"));
          $tg_otherplayerid = trim(mysql_result($return1,$i,"tg_otherplayerid"));
          $tg_playerloggedin = trim(mysql_result($return1,$i,"tg_playerloggedin"));
          $tg_otherplayerloggedin = trim(mysql_result($return1,$i,"tg_otherplayerloggedin"));
          $tg_status = trim(mysql_result($return1,$i,"tg_status"));

          //Check if the players logged in for the game
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $this->GetGameInfoByRef($ConfigFile, $tg_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);
          $GID = $tg_gameid;

          /////////////////////////////////////////////////////////////////////////
          //set the game as a draw
          if($tg_playerloggedin == "N" && $tg_otherplayerloggedin == "N"){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
            $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the first player
          if($tg_playerloggedin=="N" && $tg_otherplayerloggedin == "Y"){

            if($tg_playerid == $w_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $tg_playerid, "L");
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $tg_playerid, "L");
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //set the game as a loss for the second player
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "N"){

            if($tg_otherplayerid == $b_player_id){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $tg_otherplayerid, "L");
            }else{
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $tg_otherplayerid, "L");
            }

          }

          /////////////////////////////////////////////////////////////////////////
          //check who won the game if both players logged in
          if($tg_playerloggedin=="Y" && $tg_otherplayerloggedin == "Y"){

            if($completion_status == "W"){
              //white player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $b_player_id, "L");
            }

            if($completion_status == "B"){
              //black player won
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
              $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $w_player_id, "L");
            }

            if($completion_status == "I"){

              //check if the white player/ black player has moved

              $query2 = "SELECT * FROM move_history WHERE player_id=".$w_player_id." AND game_id ='".$GID."'";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              $query3 = "SELECT * FROM move_history WHERE player_id=".$b_player_id." AND game_id ='".$GID."'";
              $return3 = mysql_query($query3, $this->link) or die(mysql_error());
              $num3 = mysql_numrows($return3);

              if($num2 == 0 && $num3 != 0){
                $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $w_player_id, "L");
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 == 0){
                $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $b_player_id, "L");
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "W");
              }

              if($num2 == 0 && $num3 == 0){
                $this->UpdateTournamentPlayerStatus($ConfigFile, $tm_tournamentid, $w_player_id, "L");
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "B");
              }

              if($num2 != 0 && $num3 != 0){
               ///////////////////////////////////////////////////////////////////////
               //ELO Point Calculation
               if($this->ELOIsActive()){
                 $bcurpoints = $this->ELOGetRating($b_player_id);
                 $wcurpoints = $this->ELOGetRating($w_player_id);

                 //Calculate black player
                 $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

                 //Calculate white player
                 $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

                 //update points
                 $this->ELOUpdateRating($b_player_id, $bnewpoints);
                 $this->ELOUpdateRating($w_player_id, $wnewpoints);

               }
               ///////////////////////////////////////////////////////////////////////
                $this->UpdateGameStatus($ConfigFile, $GID, "C", "D");
              }

            }

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

          $i++;
        }

        //update tournamentgames status to complete
        $update1 = "UPDATE c4m_tournamentgames SET tg_status='C' WHERE tg_tmid=".$tm_id;
        mysql_query($update1, $this->link) or die(mysql_error());

      }

      $update2= "UPDATE c4m_tournamentmatches SET tm_status='C' WHERE tm_id=".$tm_id;
      $return = mysql_query($update2, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * UpdateTournamentPlayerStatus
  *
  */
  function UpdateTournamentPlayerStatus($ConfigFile, $TID, $PID, $status){

    $update = "UPDATE c4m_tournamentplayers SET tp_status='".$status."' WHERE tp_tournamentid=".$TID." AND tp_playerid=".$PID;
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * UpdateGameStatus
  *
  */
  function UpdateGameStatus($ConfigFile, $GID, $status, $completion_status){

    $update = "UPDATE game SET status='".$status."', completion_status='".$completion_status."' WHERE game_id='".$GID."'";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetGameInfoByRef
  *
  */
  function GetGameInfoByRef($ConfigFile, $GID, &$initiator, &$w_player_id, &$b_player_id, &$status, &$completion_status, &$start_time, &$next_move){

    $query = "SELECT * FROM game WHERE game_id='".$GID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $game_id = trim(mysql_result($return,0,"game_id"));
      $initiator = trim(mysql_result($return,0,"initiator"));
      $w_player_id = trim(mysql_result($return,0,"w_player_id"));
      $b_player_id = trim(mysql_result($return,0,"b_player_id"));
      $status = trim(mysql_result($return,0,"status"));
      $completion_status = trim(mysql_result($return,0,"completion_status"));
      $start_time = trim(mysql_result($return,0,"start_time"));
      $next_move = trim(mysql_result($return,0,"next_move"));

    }

  }
  
   
  function GetGameInfo($gameid)
  {
	$query = "SELECT * FROM `game` WHERE `game_id` = '$gameid'";
	$result = mysql_query($query, $this->link) or die(mysql_error());
	if(mysql_numrows($result) != 0)
		return mysql_fetch_assoc($result);
	return FALSE;
  }


  /**********************************************************************
  * AcceptTournamentproposal
  *
  */
  function AcceptTournamentproposal($ConfigFile, $TID){

    $update = "UPDATE c4m_tournament SET t_status='A' WHERE t_id=".$TID;
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * RevokeTournamentproposal
  *
  */
  function RevokeTournamentproposal($ConfigFile, $TID){

    $update = "UPDATE c4m_tournament SET t_status='R' WHERE t_id=".$TID;
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetAcceptedTournamentProposal
  *
  */
  function GetAcceptedTournamentProposal($ConfigFile){

    $query = "SELECT * FROM c4m_tournament WHERE t_status='A'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    if($num != 0){

      $i = 0;
      while($i < $num){
        $t_id = trim(mysql_result($return, $i, "t_id"));
        $t_name = trim(mysql_result($return, $i, "t_name"));
        $t_type = trim(mysql_result($return, $i, "t_type"));
        $t_playernum = trim(mysql_result($return, $i, "t_playernum"));
        $t_cutoffdate = trim(mysql_result($return, $i, "t_cutoffdate"));
        $t_startdate = trim(mysql_result($return, $i, "t_startdate"));
        $t_comment = trim(mysql_result($return, $i, "t_comment"));
        $t_status = trim(mysql_result($return, $i, "t_status"));

        echo "<tr><td><a href='./admin_view_proposal.php?tid=".$t_id."'>".$t_name."</a></td></tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetStartedTournamentProposal
  *
  */
  function GetStartedTournamentProposal($ConfigFile){

    $query = "SELECT * FROM c4m_tournament WHERE t_status='S'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    if($num != 0){

      $i = 0;
      while($i < $num){
        $t_id = trim(mysql_result($return, $i, "t_id"));
        $t_name = trim(mysql_result($return, $i, "t_name"));
        $t_type = trim(mysql_result($return, $i, "t_type"));
        $t_playernum = trim(mysql_result($return, $i, "t_playernum"));
        $t_cutoffdate = trim(mysql_result($return, $i, "t_cutoffdate"));
        $t_startdate = trim(mysql_result($return, $i, "t_startdate"));
        $t_comment = trim(mysql_result($return, $i, "t_comment"));
        $t_status = trim(mysql_result($return, $i, "t_status"));

        echo "<tr><td><a href='./admin_view_proposal.php?tid=".$t_id."'>".$t_name."</a></td></tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetClientTournamentGameList
  *
  */
  function GetClientTournamentGameList($ConfigFile, $Status, $Title){

    switch($Status){
      case A:
        $query = "SELECT * FROM c4m_tournament WHERE t_status NOT IN('R', 'C', 'P')";
        break;
      case R:
        $query = "SELECT * FROM c4m_tournament WHERE t_status NOT IN('A', 'C', 'P', 'S')";
        break;
      case C:
        $query = "SELECT * FROM c4m_tournament WHERE t_status NOT IN('R', 'A', 'P', 'S')";
        break;
      case P:
        $query = "SELECT * FROM c4m_tournament WHERE t_status NOT IN('R', 'C', 'A', 'S')";
        break;
    }

    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETCLIENTTOURNAMENTLIST_TABLE1_WIDTH') && defined('CFG_GETCLIENTTOURNAMENTLIST_TABLE1_BORDER') && defined('CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLPADDING') && defined('CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLSPACING') && defined('CFG_GETCLIENTTOURNAMENTLIST_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETCLIENTTOURNAMENTLIST_TABLE1_BORDER."' align='".CFG_GETCLIENTTOURNAMENTLIST_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLPADDING."' cellspacing='".CFG_GETCLIENTTOURNAMENTLIST_TABLE1_CELLSPACING."' width='".CFG_GETCLIENTTOURNAMENTLIST_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
    }

    echo "<tr><td colspan='5' class='tableheadercolor'><b><font class='sitemenuheader'>".$Title."</font><b></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_112")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_113")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_114")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_115")."</td></tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $t_id = trim(mysql_result($return,$i,"t_id"));
        $t_name = trim(mysql_result($return,$i,"t_name"));
        $t_type = trim(mysql_result($return,$i,"t_type"));
        $t_playernum = trim(mysql_result($return,$i,"t_playernum"));
        $t_cutoffdate = trim(mysql_result($return,$i,"t_cutoffdate"));
        $t_startdate = trim(mysql_result($return,$i,"t_startdate"));
        $t_comment = trim(mysql_result($return,$i,"t_comment"));
        $t_status = trim(mysql_result($return,$i,"t_status"));

        echo "<tr><td class='row2'><a href='./chess_tournament_status.php?tid=".$t_id."'>".$t_name."</a></td>";
        echo "<td class='row2'>";
        switch($t_type){
          case 0:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_89")."";
            break;
          case 1:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_90")."";
            break;
          case 2:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_91")."";
            break;
          case 3:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_92")."";
            break;
        }
        echo "</td>";
        echo "<td class='row2'>".$t_startdate."</td>";
        echo "<td class='row2'>";
        switch($t_status){
          case A:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_116")."";
            break;
          case S:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_117")."";
            break;
          case C:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_118")."";
            break;
          case R:
            echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_119")."";
            break;
        }
        echo "</td></tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * ViewTournamentGameStatus
  *
  */
  function ViewTournamentGameStatus($ConfigFile, $TID, $PID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $t_id = trim(mysql_result($return,0,"t_id"));
      $t_name = trim(mysql_result($return,0,"t_name"));
      $t_type = trim(mysql_result($return,0,"t_type"));
      $t_playernum = trim(mysql_result($return,0,"t_playernum"));
      $t_cutoffdate = trim(mysql_result($return,0,"t_cutoffdate"));
      $t_startdate = trim(mysql_result($return,0,"t_startdate"));
      $t_comment = trim(mysql_result($return,0,"t_comment"));
      $t_status = trim(mysql_result($return,0,"t_status"));

      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
      echo "<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$t_name."</font><b></td></tr>";

      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_88")."</td>";
      echo "<td class='row2'>";
      switch($t_type){
       case 0:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_89")."";
         break;
       case 1:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_90")."";
         break;
       case 2:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_91")."";
         break;
       case 3:
         echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_92")."";
         break;
      }
      echo "</td></tr>";

      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_93")."</td><td class='row2'>".$t_cutoffdate."</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_94")."</td><td class='row2'>".$t_startdate."</td></tr>";
      echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_120")."</td><td class='row2'><input type='button' name='btnviewplayers' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_121")."' class='mainoption' onclick=\"javascript:PopupWindow('./chess_view_player.php?tid=".$TID."');\"></td></tr>";
      echo "<tr><td class='row1' colspan='2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_95")."</td></tr>";
      echo "<tr><td class='row2' colspan='2'><span>".$t_comment."</span></td></tr>";
      echo "</table><br>";

      $query1 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID." ORDER BY tm_id ASC";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 !=0){

        echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
        echo "<tr><tdclass='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_122")."</font><b></td></tr>";
        echo "<tr><td class='row1'>";

        $i=0;
        while($i < $num1){

          $tm_id = trim(mysql_result($return1,$i,"tm_id"));
          $tm_tournamentid = trim(mysql_result($return1,$i,"tm_tournamentid"));
          $tm_status = trim(mysql_result($return1,$i,"tm_status"));
          $tm_starttime = trim(mysql_result($return1,$i,"tm_starttime"));
          $tm_endtime = trim(mysql_result($return1,$i,"tm_endtime"));

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='97%'>";
          echo "<tr><tdclass='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_123")."".($i+1)."</font><b></td></tr>";
          echo "<tr><td class='row2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_124")."</td><td class='row1'>".$tm_starttime."</td><td class='row2'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_125")."</td><td class='row1'>".$tm_endtime."</td></tr>";
          echo "<tr><td class='row2' colspan='4'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_126")." ";

          switch($tm_status){
            case I:
              echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_127")."";
              break;
            case A:
              echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_128")."";
              break;
            case C:
              echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_129")."";
              break;
          }

          echo "</td></tr>";

          echo "<tr><td class='row2' colspan='4'>";

          //Get the games
          $query2 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." ORDER BY tg_id ASC";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 !=0){
            echo "<table border='0' align='center' cellpadding='3' cellspacing='1' width='100%'>";
            echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_133")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_130")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_131")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_132")."</td></tr>";

            $ii=0;
            while($ii < $num2){

              $tg_id = trim(mysql_result($return2,$ii,"tg_id"));
              $tg_tmid = trim(mysql_result($return2,$ii,"tg_tmid"));
              $tg_gameid = trim(mysql_result($return2,$ii,"tg_gameid"));
              $tg_playerid = trim(mysql_result($return2,$ii,"tg_playerid"));
              $tg_otherplayerid = trim(mysql_result($return2,$ii,"tg_otherplayerid"));
              $tg_playerloggedin = trim(mysql_result($return2,$ii,"tg_playerloggedin"));
              $tg_otherplayerloggedin = trim(mysql_result($return2,$ii,"tg_otherplayerloggedin"));
              $tg_status = trim(mysql_result($return2,$ii,"tg_status"));

              echo "<tr><td class='row1'>";

              if($tg_status == "A" || $tg_status == "I"){
                echo "<a href=\"javascript:PopupWindow('./t_index.php?gid=".$tg_gameid."')\">View/Play</a>";
              }else{
                echo "-";
              }
              echo "</td><td class='row1'>".$this->GetUserIDByPlayerID($ConfigFile, $tg_playerid)."</td><td class='row1'>".$this->GetUserIDByPlayerID($ConfigFile, $tg_otherplayerid)."</td>";
              echo "<td class='row1'>";
              switch($tg_status){
                case I:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_127")."";
                  break;
                case A:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_128")."";
                  break;
                case C:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_129")."";
                  break;
              }
              echo "</td></tr>";

              $ii++;

            }

            echo "</table><br>";
          }

          echo "</td></tr>";
          echo "</table><br>";

          $i++;

        }
        echo "</td></tr>";
        echo "</table><br>";

        //Get the winner list
        $query3 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID." AND tm_status='C' ORDER BY tm_id ASC";
        $return3 = mysql_query($query3, $this->link) or die(mysql_error());
        $num3 = mysql_numrows($return3);

        if($num1 == $num3){

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
          echo "<tr><tdclass='tableheadercolor'><b><font class='sitemenuheader'>Winner(s):</font><b></td></tr>";
          echo "<tr><td class='row2'>";

          $query4 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID." AND tp_status='A'";
          $return4 = mysql_query($query4, $this->link) or die(mysql_error());
          $num4 = mysql_numrows($return4);

          if($num4 !=0){

            $iii=0;
            while($iii < $num4){
              $tp_playerid = mysql_result($return4,$iii,"tp_playerid");

              echo "<img src='./skins/".$this->SkinsLocation."/images/winner.gif' align='middle'> ".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."<br>";

              $iii++;

            }

          }

          echo "</td></tr>";
          echo "</table><br>";

        }

      }else{

        echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
        echo "<tr><tdclass='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_134")."</font><b></td></tr>";
        echo "<tr><td class='row2'>".str_replace("['t_startdate']", $t_startdate, $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_135"))."</td></tr>";
        echo "</table><br>";

      }

    }

  }


  /**********************************************************************
  * ViewTournamentGamePlayers
  *
  */
  function ViewTournamentGamePlayers($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $t_id = trim(mysql_result($return,0,"t_id"));
      $t_name = trim(mysql_result($return,0,"t_name"));
      $t_type = trim(mysql_result($return,0,"t_type"));
      $t_playernum = trim(mysql_result($return,0,"t_playernum"));
      $t_cutoffdate = trim(mysql_result($return,0,"t_cutoffdate"));
      $t_startdate = trim(mysql_result($return,0,"t_startdate"));
      $t_comment = trim(mysql_result($return,0,"t_comment"));
      $t_status = trim(mysql_result($return,0,"t_status"));

      $query1 = "SELECT * FROM c4m_tournamentplayers WHERE tp_tournamentid=".$TID;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $npcount = $t_playernum;
        $ncount = 0;
        $ngroupcount = 1;
        $nswitch = 0;

        if($npcount != 0 && $t_type != 0 && $t_type != 3){

          while($ncount < $num1){
            echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
            echo "<tr><td class='row1' colspan='3'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_96")."".$ngroupcount."</td></tr>";

            while($nswitch < $npcount && $ncount < $num1){
              $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
              $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

              echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
              echo "<td class='row2' >";

              switch($tp_status){
                case P:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                  break;
                case A:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                  break;
                case L:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_101")."";
                  break;
                case D:
                  echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_102")."";
                  break;
              }

              echo "</td>";

              echo "<td class='row2' >";

              if($this->IsPlayerOnline($ConfigFile, $tp_playerid)){
                echo "<font color='green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
              }else{
                echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
              }

              echo "</td></tr>";

              $nswitch++;
              $ncount++;

            }

            echo "</table><br>";

            $nswitch=0;
            $ngroupcount++;
          }

        }else{

          $ncount = 0;

          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
          echo "<tr><td class='row1' colspan='3'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_100")."</td></tr>";

          while($ncount < $num1){
            $tp_playerid = trim(mysql_result($return1, $ncount, "tp_playerid"));
            $tp_status = trim(mysql_result($return1, $ncount, "tp_status"));

            echo "<tr><td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile, $tp_playerid)."</td>";
            echo "<td class='row2' width='300'>";

            switch($tp_status){
              case P:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_98")."";
                break;
              case A:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_99")."";
                break;
              case L:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_101")."";
                break;
              case D:
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_102")."";
                break;
            }

            echo "</td>";

            echo "<td class='row2' >";

            if($this->IsPlayerOnline($ConfigFile, $tp_playerid)){
              echo "<font color='green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
            }else{
              echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
            }

            echo "</td></tr>";

            $ncount++;
          }

          echo "</table><br>";

        }

      }

    }

  }


  /**********************************************************************
  * GetTGameBoard
  *
  */
  function GetTGameBoard($ConfigFile, $GameID, $sid, $player_id, $clrl, $clrd){

    if($sid == ""){
      $fen = $this->GetFEN($sid, $GameID);
    }else{
      $fen = $this->GetFEN($sid, $GameID);
    }

    $isblack = false;

    if($player_id != 0){
      $isblack = $this->IsPlayerBlack($ConfigFile, $GameID, $player_id);
    }

    echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";
    echo "<tr>";
    echo "<td valign='top'>";
    if($isblack){
      $this->CreateChessBoard($fen, $clrl, $clrd, false, "b");
    }else{
      $this->CreateChessBoard($fen, $clrl, $clrd, false, "w");
    }

    echo "</td>";
    echo "</table>";

  }


  /**********************************************************************
  * SendTChat
  *
  */
  function SendTChat($ConfigFile, $GameID, $message){

    $message = rawurlencode($message);
    // Replace French, Latin characters with HTML equivalents
    $aToReplace = array("%E0","%E1","%E2","%E3","%E4","%E5","%E6","%E7","%E8","%E9","%EA","%EB","%EC","%ED","%EE","%EF","%F0","%F1","%F2","%F3","%F4","%F5","%F6","%F7","%F8","%F9","%FA","%FB","%FC","%FD","%FE","%FF","%20AC","201C","201D","%AB","%BB","A6","%C1","%C0","%C2","%C3","%C4","%C5","%C6","%C7","%C8","%C9","%CA","%CB","%CC","%CD","%CE","%CF","%D0","%D1","%D2","%D3","%D4","%D5","%D6","%D7","%D8","%D9","%DA","%DB","%DC","%DD","%DE","%DF");
    $aReplaceWith = array("&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;","&#8364;","&#8220;","&#8221;","&#171;","&#187;","&#166;","&#193;","&#192;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;");
    $message = str_replace($aToReplace, $aReplaceWith, $message);

    $insert = "INSERT INTO c4m_tournamentgamechat VALUES(NULL, '".$GameID."', '".$message."', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetTChat
  *
  */
  function GetTChat($ConfigFile, $GameID){

    $query = "SELECT * FROM c4m_tournamentgamechat WHERE tgc_gameid='".$GameID."' ORDER BY tgc_date DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $Message = $this->FilterChatText(mysql_result($return, $i, "tgc_message"));
	$Message = rawurldecode($Message);
        echo $Message."\n\n";

        $i++;
      }

    }

  }


  /**********************************************************************
  * GetTGCanPlay
  *
  */
  function GetTGCanPlay($ConfigFile, $GameID, $playerID){

    $query = "SELECT * FROM game WHERE game_id='".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bCanPlay = 0;

    if($num != 0){

      //Check if the user can play
      $query1 = "SELECT * FROM game WHERE game_id='".$GameID."' AND w_player_id=".$playerID;
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){
        $bCanPlay = 1;
      }

      $query2 = "SELECT * FROM game WHERE game_id='".$GameID."' AND b_player_id=".$playerID;
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num2 != 0){
        $bCanPlay = 1;
      }

    }

    return $bCanPlay;

  }


  /**********************************************************************
  * PlayerLoginForTGame
  *
  */
  function PlayerLoginForTGame($ConfigFile, $GameID, $playerID){

    $query = "SELECT * FROM c4m_tournamentgames WHERE tg_gameid='".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $tg_playerid = mysql_result($return, 0, "tg_playerid");
      $tg_otherplayerid = mysql_result($return, 0, "tg_otherplayerid");

      if($playerID == $tg_playerid){
        $update = "UPDATE c4m_tournamentgames SET tg_playerloggedin='Y' WHERE tg_gameid='".$GameID."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

      if($playerID == $tg_otherplayerid){
        $update = "UPDATE c4m_tournamentgames SET tg_otherplayerloggedin='Y' WHERE tg_gameid='".$GameID."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

    }

  }


  /**********************************************************************
  * TimeForTGame
  *
  */
  function TimeForTGame($ConfigFile, $GameID){

    $query = "SELECT * FROM c4m_tournamentgames WHERE tg_gameid='".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bTimeToPlay = false;

    if($num != 0){

      $tg_tmid = mysql_result($return, 0, "tg_tmid");

      $query1 = "SELECT * FROM c4m_tournamentmatches WHERE tm_id=".$tg_tmid." AND tm_starttime <= NOW() AND tm_endtime >= NOW()";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $bTimeToPlay = true;

      }

    }

    return $bTimeToPlay;

  }


  /**********************************************************************
  * RetrievedLostPass
  *
  */
  function RetrievedLostPass($ConfigFile, $UName, $Email){

    $query = "SELECT * FROM player WHERE userid='".$UName."' AND email='".$Email."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

    $conf = $this->conf;

      $email = mysql_result($return, 0, email);
      $password = mysql_result($return, 0, password);

      $aTags1 = array("['UName']", "['password']", "['siteurl']", "['sitename']");
      $aReplaceTags = array($UName, $password, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
      $body = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_12"));

      $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_11"));

      $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);

    }

  }
  
  /**********************************************************************
  * ResetPassword - Resets the user's password by generating a new one and
  * sends an email with the new password.
  */
  function ResetPassword($ConfigFile, $UName, $Email){

    $query = "SELECT * FROM player WHERE userid='".$UName."' AND email='".$Email."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

    $conf = $this->conf;

      $email = mysql_result($return, 0, email);
      $password = $this->gen_unique();
	  $hash = $this->hash_password($password);
	  $query = "UPDATE player SET password='$hash' WHERE userid='$UName'";
	  //echo $query;
	  if(!mysql_query($query))
		die("Unable to reset password. DB Error.");

      $aTags1 = array("['UName']", "['password']", "['siteurl']", "['sitename']");
      $aReplaceTags = array($UName, $password, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
      $body = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_12"));

      $subject = str_replace("['sitename']", $conf['site_name'], $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_11"));

      $this->SendEmail($email, $conf['registration_email'], $conf['site_name'], $subject, $body);

    }

  }


  /**********************************************************************
  * CheckSIDTimeout
  *
  */
  function CheckSIDTimeout(){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $oChess->CheckSIDTimeout($this->ChessCFGFileLocation);
    unset($oChess);

  }


  /**********************************************************************
  * UpdateSIDTimeout
  *
  */
  function UpdateSIDTimeout($ConfigFile, $orig_session){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $oChess->UpdateSIDTimeout($this->ChessCFGFileLocation, $orig_session);
    unset($oChess);

  }


  /**********************************************************************
  * SavePersonalInformation
  *
  */
  function SavePersonalInformation($ConfigFile, $RealName, $Location, $Age, $SelfRating, $Comment, $ChessPlayer, $EmailAddress, $PID){

    $query = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $UPDATE = "UPDATE c4m_personalinfo SET p_fullname='".$RealName."', p_location='".$Location."', p_age='".$Age."', p_selfrating='".$SelfRating."', p_commentmotto='".$Comment."', p_favouritechessplayer='".$ChessPlayer."' WHERE p_playerid=".$PID."";
      mysql_query($UPDATE, $this->link) or die(mysql_error());

      if($EmailAddress != ""){
        $update = "UPDATE player SET email='".$EmailAddress."' WHERE player_id='".$PID."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

    }else{

      $INSERT = "INSERT INTO c4m_personalinfo VALUES(".$PID.", '".$RealName."', '".$Location."', '".$Age."', '".$SelfRating."', '".$Comment."', '".$ChessPlayer."')";
      mysql_query($INSERT, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetPersonalInformation
  *
  */
  function GetPersonalInformation($ConfigFile, &$RealName, &$Location, &$Age, &$SelfRating, &$Comment, &$ChessPlayer, $PID){

    $query = "SELECT * FROM c4m_personalinfo WHERE p_playerid=".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $RealName = mysql_result($return, 0, "p_fullname");
      $Location = mysql_result($return, 0, "p_location");
      $Age = mysql_result($return, 0, "p_age");
      $SelfRating = mysql_result($return, 0, "p_selfrating");
      $Comment = mysql_result($return, 0, "p_commentmotto");
      $ChessPlayer = mysql_result($return, 0, "p_favouritechessplayer");

    }

  }


  /**********************************************************************
  * SaveNotification
  *
  */
  function SaveNotification($ConfigFile, $move, $challange, $PID){

    $query = "SELECT * FROM c4m_notification WHERE p_playerid=".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $UPDATE = "UPDATE c4m_notification SET p_move='".$move."', p_challange='".$challange."' WHERE p_playerid=".$PID."";
      mysql_query($UPDATE, $this->link) or die(mysql_error());

    }else{

      $INSERT = "INSERT INTO c4m_notification VALUES(".$PID.", '".$move."', '".$challange."')";
      mysql_query($INSERT, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetNotification
  *
  */
  function GetNotification($ConfigFile, &$move, &$challange, $PID){

    $query = "SELECT * FROM c4m_notification WHERE p_playerid=".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $move = mysql_result($return, 0, "p_move");
      $challange = mysql_result($return, 0, "p_challange");

    }else{

      $move = "y";
      $challange = "y";

    }

  }


  /**********************************************************************
  * RevokeDrawGame
  *
  */
  function RevokeDrawGame($ConfigFile, $gid, $PID){

	$query = "UPDATE `game` SET `draw_requests` = NULL WHERE `game_id` = '$gid'";
	mysql_query($query, $this->link) or die(mysql_error());
  
    //$delete = "DELETE FROM c4m_gamedraws WHERE tm_gameid='".$gid."'";
    //mysql_query($delete, $this->link) or die(mysql_error());

    $conf = $this->conf;

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";

    $this->GetCurrentGameInfoByRef($ConfigFile, $gid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

    $plre1 = $this->GetEmailByPlayerID($ConfigFile, $w_player_id);
    $plre2 = $this->GetEmailByPlayerID($ConfigFile, $b_player_id);
    $plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $w_player_id);
    $plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $b_player_id);
    $plrn3 = $this->GetUserIDByPlayerID($ConfigFile, $PID);

    // Send email to the players about the draw revoke
    $subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_13");

    $aTags1 = array("['plrn1']", "['plrn2']", "['plrn3']", "['gid']", "['siteurl']", "['sitename']");
    $aReplaceTags = array($plrn1, $plrn2, $plrn3, $gid, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
    $bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_14"));

    if($this->MoveNotification($w_player_id)){
      $this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
    }

    if($this->MoveNotification($b_player_id)){
      $this->SendEmail($plre2, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
    }

  }


  /**********************************************************************
  * DrawGame
  *
  */
  function DrawGame($ConfigFile, $gid, $color){

    $conf = $this->conf;

    // $initiator = "";
    // $w_player_id = "";
    // $b_player_id = "";
    // $next_move = "";
    // $start_time = "";

    //$this->GetCurrentGameInfoByRef($ConfigFile, $gid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

	$info = $this->GetGameInfo($gid);
	if(!$info) return FALSE;
	$initiator = $info['initiator'];
	$w_player_id = $info['w_player_id'];
	$b_player_id = $info['b_player_id'];
	$next_move = $info['next_move'];
	$start_time = $info['start_time'];
	$draw_requests = $info['draw_requests'];
	
    // // Check if game draw record exists in db
    // $query = "SELECT * FROM c4m_gamedraws WHERE tm_gameid='".$gid."'";
    // $return = mysql_query($query, $this->link) or die(mysql_error());
    // $num = mysql_numrows($return);

    // if($num == 0){

      // $insert = "INSERT INTO c4m_gamedraws VALUES('".$gid."', 0, 0)";
      // mysql_query($insert, $this->link) or die(mysql_error());

    // }

    // Set the draw according to player color
    if($color == "b")
	{
      //$update = "UPDATE c4m_gamedraws SET tm_b=1 WHERE tm_gameid='".$gid."'";
		if($draw_requests == NULL) $draw_requests = 'black';
		elseif($draw_requests == 'white') $draw_requests = 'both';
		$update = "UPDATE `game` SET `draw_requests` = '$draw_requests' WHERE `game_id` = '$gid'";
		
		// Send email to the white player about the draw
		if($this->MoveNotification($w_player_id)){

			$plre1 = $this->GetEmailByPlayerID($ConfigFile, $w_player_id);
			$plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $w_player_id);
			$plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $b_player_id);

			$subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_15");

			$aTags1 = array("['plrn1']", "['plrn2']", "['gid']", "['siteurl']", "['sitename']");
			$aReplaceTags = array($plrn1, $plrn2, $gid, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
			$bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_16"));

			$this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);

		}
    }
	else
	{
		//$update = "UPDATE c4m_gamedraws SET tm_w=1 WHERE tm_gameid='".$gid."'";
		if($draw_requests == NULL) $draw_requests = 'white';
		elseif($draw_requests == 'black') $draw_requests = 'both';
		$update = "UPDATE `game` SET `draw_requests` = '$draw_requests' WHERE `game_id` = '$gid'";
		
		// Send email to the black player about the draw
		if($this->MoveNotification($b_player_id)){

			$plre1 = $this->GetEmailByPlayerID($ConfigFile, $b_player_id);
			$plrn1 = $this->GetUserIDByPlayerID($ConfigFile, $b_player_id);
			$plrn2 = $this->GetUserIDByPlayerID($ConfigFile, $w_player_id);

			$subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_15");

			$aTags1 = array("['plrn1']", "['plrn2']", "['gid']", "['siteurl']", "['sitename']");
			$aReplaceTags = array($plrn1, $plrn2, $gid, $this->TrimRSlash($conf['site_url']), $conf['site_name']);
			$bodyp1 = str_replace($aTags1, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_16"));

			$this->SendEmail($plre1, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);

		}
    }

    mysql_query($update, $this->link) or die(mysql_error());

    // Set the game to draw if both parties consent
    // $query = "SELECT * FROM c4m_gamedraws WHERE tm_gameid='".$gid."'";
    // $return = mysql_query($query, $this->link) or die(mysql_error());
    // $num = mysql_numrows($return);

    //if($num != 0){

      // $black = mysql_result($return,0,"tm_b");
      // $white = mysql_result($return,0,"tm_w");

      // if($black == 1 && $white == 1){
	  if($draw_requests == 'both')
	  {
        ///////////////////////////////////////////////////////////////////////
        //ELO Point Calculation
        if($this->ELOIsActive()){
          $bcurpoints = $this->ELOGetRating($b_player_id);
          $wcurpoints = $this->ELOGetRating($w_player_id);

          //Calculate black player
          $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0.5, 1, $this->GetPlayerGameCount($b_player_id));

          //Calculate white player
          $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0.5, 1, $this->GetPlayerGameCount($w_player_id));

          //update points
          $this->ELOUpdateRating($b_player_id, $bnewpoints);
          $this->ELOUpdateRating($w_player_id, $wnewpoints);

        }
        ///////////////////////////////////////////////////////////////////////

        //$this->UpdateGameStatus($ConfigFile, $gid, "C", "D");
		$update = "UPDATE `game` SET `status` = 'C', `completion_status` = 'D' WHERE `game_id` = '$gid'";
		mysql_query($update, $this->link) or die(mysql_error());
		
        $this->CachePlayerPointsByPlayerID($b_player_id);
        $this->CachePlayerPointsByPlayerID($w_player_id);

      }

    //}

  }


  /**********************************************************************
  * IsRequestDraw
  *
  */
  function IsRequestDraw($ConfigFile, $gid, $isblack){

    $strStatus = "IDS_NO_DRAW";

    //$query = "SELECT * FROM c4m_gamedraws WHERE tm_gameid='".$gid."'";
	$query = "SELECT `draw_requests` FROM `game` WHERE `game_id` = '$gid'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

		$val = mysql_result($return, 0, 'draw_requests');
		if($val == 'both')
			$strStatus = "IDS_DRAW";
		elseif(($val == 'white' && $isblack) || ($val == 'black' && !$isblack)) 
			$strStatus = "IDS_DRAW_REQUESTED";
		elseif(($val == 'white' && !$isblack) || ($val == 'black' && $isblack)) 
			$strStatus = "IDS_USER_REQUESTED_DRAW";
	
      // $black = mysql_result($return,0,"tm_b");
      // $white = mysql_result($return,0,"tm_w");

      // if($black == 1 && $white == 1){

        // $strStatus = "IDS_DRAW";

      // }elseif($black == 1 && $isblack){

        // $strStatus = "IDS_USER_REQUESTED_DRAW";

      // }elseif($black == 1 && !$isblack){

        // $strStatus = "IDS_DRAW_REQUESTED";

      // }elseif($white == 1 && !$isblack){

        // $strStatus = "IDS_USER_REQUESTED_DRAW";

      // }elseif($white == 1 && $isblack){

        // $strStatus = "IDS_DRAW_REQUESTED";
      // }

    }

    return $strStatus;

  }


  /**********************************************************************
  * RealTimeGame
  *
  */
  function RealTimeGame($ConfigFile, $gid, $color){

    // Check if game realtime request record exists in db
    $query = "SELECT * FROM c4m_gamerealtime WHERE gr_gameid='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num == 0){

      $insert = "INSERT INTO c4m_gamerealtime VALUES('".$gid."', 0, 0)";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

    // Set the draw according to player color
    if($color=="b"){
      $update = "UPDATE c4m_gamerealtime SET gr_b=1 WHERE gr_gameid='".$gid."'";
    }else{
      $update = "UPDATE c4m_gamerealtime SET gr_w=1 WHERE gr_gameid='".$gid."'";
    }

    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * IsRequestRealTime
  *
  */
  function IsRequestRealTime($ConfigFile, $gid, $isblack){

    $strStatus = "IDS_NO_REAL_TIME";

    $query = "SELECT * FROM c4m_gamerealtime WHERE gr_gameid='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $black = mysql_result($return,0,"gr_b");
      $white = mysql_result($return,0,"gr_w");

      if($black == 1 && $white == 1){
        $strStatus = "IDS_REAL_TIME";
      }elseif($black == 1 && $isblack){
        $strStatus = "IDS_USER_REQUESTED_REAL_TIME";
      }elseif($black == 1 && !$isblack){
        $strStatus = "IDS_REALTIME_REQUESTED";
      }elseif($white == 1 && !$isblack){
        $strStatus = "IDS_USER_REQUESTED_REAL_TIME";
      }elseif($white == 1 && $isblack){
        $strStatus = "IDS_REALTIME_REQUESTED";
      }

    }

    return $strStatus;

  }


  /**********************************************************************
  * ExitRealTimeGame
  *
  */
  function ExitRealTimeGame($ConfigFile, $gid){

    $query = "SELECT * FROM c4m_gamerealtime WHERE gr_gameid='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE c4m_gamerealtime SET gr_b=0, gr_w=0 WHERE gr_gameid='".$gid."'";
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * ManageRealTimeGame
  *
  */
  function ManageRealTimeGame($ConfigFile, $gid){

    $query = "SELECT * FROM c4m_gamerealtime WHERE gr_gameid='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $black = mysql_result($return,0,"gr_b");
      $white = mysql_result($return,0,"gr_w");

      $initiator = "";
      $w_player_id = "";
      $b_player_id = "";
      $next_move = "";
      $start_time = "";

      $this->GetCurrentGameInfoByRef($ConfigFile, $gid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

      if($this->IsPlayerOnline($ConfigFile, $w_player_id) == false || $this->IsPlayerOnline($ConfigFile, $b_player_id) == false){
        $update = "UPDATE c4m_gamerealtime SET gr_b=0, gr_w=0 WHERE gr_gameid='".$gid."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

    }

  }


  /**********************************************************************
  * RevokeGame
  *
  */
  function RevokeGame($gid, $player_id){

    // Remove game challange from inbox
    $this->PurgeOldGameChallangesFromInbox($this->ChessCFGFileLocation, $player_id, $gid);

    $query = "SELECT * FROM game WHERE game_id ='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $black = mysql_result($return,0,"b_player_id");
      $white = mysql_result($return,0,"w_player_id");

      if($black == $player_id || $white == $player_id){
        $delete = "DELETE FROM game WHERE game_id ='".$gid."'";
        mysql_query($delete, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * RevokeGame2
  *
  */
  function RevokeGame2($gid, $player_id){

    $query = "SELECT * FROM game WHERE game_id ='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $black = mysql_result($return,0,"b_player_id");
      $white = mysql_result($return,0,"w_player_id");

      // Remove game challange from inbox
      if($black != $player_id){
        $this->PurgeOldGameChallangesFromInbox($this->ChessCFGFileLocation, $black, $gid);
      }else{
        $this->PurgeOldGameChallangesFromInbox($this->ChessCFGFileLocation, $white, $gid);
      }

      if($black == $player_id || $white == $player_id){
        $delete = "DELETE FROM game WHERE game_id ='".$gid."'";
        mysql_query($delete, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * MoveNotification
  *
  */
  function MoveNotification($player_id){

    $bCanNotify = true;

    $query = "SELECT * FROM c4m_notification WHERE p_playerid =".$player_id."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if(mysql_result($return, 0, "p_move") == "n"){
        $bCanNotify = false;
      }

    }

    return $bCanNotify;

  }


  /**********************************************************************
  * ChallangeNotification
  *
  */
  function ChallangeNotification($player_id){

   $bCanNotify = true;

    $query = "SELECT * FROM c4m_notification WHERE p_playerid =".$player_id."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if(mysql_result($return, 0, "p_challange") == "n"){
        $bCanNotify = false;
      }

    }

    return $bCanNotify;

  }


  /**********************************************************************
  * TGameStatus
  *
  */
  function TGameStatus($gid){

    $query = "SELECT * FROM game WHERE game_id ='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bReturn = true;

    if($num != 0){

      $status = mysql_result($return,0,"status");
      $completion_status = mysql_result($return,0,"completion_status");

      if($status == "A" && $completion_status == "I"){
        $bReturn = true;
      }elseif($status == "C" && $completion_status == "W"){
        $bReturn = false;
      }elseif($status == "C" && $completion_status == "B"){
        $bReturn = false;
      }elseif($status == "C" && $completion_status == "D"){
        $bReturn = false;
      }

    }

    return $bReturn;

  }


  /**********************************************************************
  * GetTournamentName
  *
  */
  function GetTournamentName($ConfigFile, $TID){

    $query = "SELECT * FROM c4m_tournament WHERE t_id=".$TID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);


    $t_name = "";

    if($num != 0){

      $t_name = trim(mysql_result($return,0,"t_name"));

    }

    return $t_name;

  }


  /**********************************************************************
  * PurgeOldMovesFromInbox
  *
  */
  function PurgeOldMovesFromInbox($ConfigFile, $PID, $GID){

    //Get Inbox info
    $query = "SELECT * FROM c4m_msginbox WHERE player_id =".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $i = 0;

    while($i < $num){

      $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
      $message = trim(mysql_result($return,$i,"message"));
      $posted = trim(mysql_result($return,$i,"msg_posted"));

      if(substr($message, 0, 2) == "M0"){

        $player_id = substr($message, 11,8);
        $Move = substr($message, (strlen($message)-5),5);
        $gameid = substr($message, 19, (strlen($message)-24));

        if($gameid == $GID){
          $this->DeleteMessageFromInbox($ConfigFile, $inbox_id);
        }

      }

      $i++;

    }

  }


  /**********************************************************************
  * PurgeOldGameChallangesFromInbox
  *
  */
  function PurgeOldGameChallangesFromInbox($ConfigFile, $PID, $GID){

    //Get Inbox info
    $query = "SELECT * FROM c4m_msginbox WHERE player_id =".$PID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $i = 0;

    while($i < $num){

      $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
      $message = trim(mysql_result($return,$i,"message"));
      $posted = trim(mysql_result($return,$i,"msg_posted"));

      if(substr($message, 0, 2) == "GC"){

        $TextCount = substr($message, 2,8);
        $GameId = substr($message, 10,((int)$TextCount-8));
        $player_id = substr($message, (strlen($message)-8), 8);

        if($GameId == $GID){
          $this->DeleteMessageFromInbox($ConfigFile, $inbox_id);
        }

      }

      $i++;

    }

  }


  /**********************************************************************
  * SearchGames
  *
  */
  function SearchGames($PID, $Search, $SearchOPT){

    // Build Search query (When player is white)
    if($SearchOPT == "1"){
      $queryw = "SELECT * FROM game, player WHERE player.player_id = game.b_player_id AND player.userid like '%".$Search."%' AND game.w_player_id =".$PID."";
    }elseif($SearchOPT == "2"){
      $queryw = "SELECT * FROM game WHERE game_id like '%".$Search."%' AND w_player_id =".$PID."";
    }elseif($SearchOPT == "3"){
      $queryw = "SELECT * FROM game WHERE start_time like '%".$Search."%' AND w_player_id =".$PID."";
    }elseif($SearchOPT == "4"){
      $queryw = "SELECT * FROM game, c4m_gamerealtime WHERE game.game_id = c4m_gamerealtime.gr_gameid AND c4m_gamerealtime.gr_b=1 AND c4m_gamerealtime.gr_w=1 AND game.w_player_id =".$PID."";
    }

    // Build Search query (When player is black)
    if($SearchOPT == "1"){
      $queryb = "SELECT * FROM game, player WHERE player.player_id = game.w_player_id AND player.userid like '%".$Search."%' AND game.b_player_id =".$PID."";
    }elseif($SearchOPT == "2"){
      $queryb = "SELECT * FROM game WHERE game_id like '%".$Search."%' AND b_player_id =".$PID."";
    }elseif($SearchOPT == "3"){
      $queryb = "SELECT * FROM game WHERE start_time like '%".$Search."%' AND b_player_id =".$PID."";
    }elseif($SearchOPT == "4"){
      $queryb = "SELECT * FROM game, c4m_gamerealtime WHERE game.game_id = c4m_gamerealtime.gr_gameid AND c4m_gamerealtime.gr_b=1 AND c4m_gamerealtime.gr_w=1 AND game.b_player_id =".$PID."";
    }

    // Query the database
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_SEARCHGAMES_TABLE1_WIDTH') && defined('CFG_SEARCHGAMES_TABLE1_BORDER') && defined('CFG_SEARCHGAMES_TABLE1_CELLPADDING') && defined('CFG_SEARCHGAMES_TABLE1_CELLSPACING') && defined('CFG_SEARCHGAMES_TABLE1_ALIGN')){
      echo "<table width='".CFG_SEARCHGAMES_TABLE1_WIDTH."' border='".CFG_SEARCHGAMES_TABLE1_BORDER."' cellpadding='".CFG_SEARCHGAMES_TABLE1_CELLPADDING."' cellspacing='".CFG_SEARCHGAMES_TABLE1_CELLSPACING."' align='".CFG_SEARCHGAMES_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_61")." (".($numw+$numb)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_154").")</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_136")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_137")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_138")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_139")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_140")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_141")."</td></tr>";

    $i = 0;
    while($i < $numw){

      $game_id = trim(mysql_result($returnw,$i,"game_id"));
      $initiator = trim(mysql_result($returnw,$i,"initiator"));
      $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
      $start_time = trim(mysql_result($returnw,$i,"start_time"));
      $status = trim(mysql_result($returnw,$i,"status"));
      $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

      echo "<tr>";
      echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_142")."</a></td>";
      echo "<td class='row2'><a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_143")."</a></td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
      echo "<td class='row2'>".$completion_status."</td>";
      echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
      echo "</tr>";

      $i++;
    }

    $i = 0;
    while($i < $numb){

      $game_id = trim(mysql_result($returnb,$i,"game_id"));
      $initiator = trim(mysql_result($returnb,$i,"initiator"));
      $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
      $status = trim(mysql_result($returnb,$i,"status"));
      $completion_status = trim(mysql_result($returnb,$i,"completion_status"));
      $start_time = trim(mysql_result($returnb,$i,"start_time"));

      echo "<tr>";
      echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_142")."</a></td>";
      echo "<td class='row2'><a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_143")."</a></td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
      echo "<td class='row2'>".$completion_status."</td>";
      echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
      echo "</tr>";

      $i++;
    }

    echo "</table>";
    echo "<br>";

  }

  /**********************************************************************
  * SearchOpenGames
  *
  */
  function SearchOpenGames($PID){

    $queryw = "SELECT * FROM game WHERE (w_player_id = '0' OR b_player_id = '0') AND status = 'W'";

    // Query the database
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_SEARCHGAMES_TABLE1_WIDTH') && defined('CFG_SEARCHGAMES_TABLE1_BORDER') && defined('CFG_SEARCHGAMES_TABLE1_CELLPADDING') && defined('CFG_SEARCHGAMES_TABLE1_CELLSPACING') && defined('CFG_SEARCHGAMES_TABLE1_ALIGN')){
      echo "<table width='".CFG_SEARCHGAMES_TABLE1_WIDTH."' border='".CFG_SEARCHGAMES_TABLE1_BORDER."' cellpadding='".CFG_SEARCHGAMES_TABLE1_CELLPADDING."' cellspacing='".CFG_SEARCHGAMES_TABLE1_CELLSPACING."' align='".CFG_SEARCHGAMES_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='550' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_61")." (".($numw+$numb)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_154").")</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_136")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_137")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_138")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_139")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_140")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_141")."</td></tr>";

    $i = 0;
    while($i < $numw){

      $game_id = trim(mysql_result($returnw,$i,"game_id"));
      $initiator = trim(mysql_result($returnw,$i,"initiator"));
      $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
      $start_time = trim(mysql_result($returnw,$i,"start_time"));
      $status = trim(mysql_result($returnw,$i,"status"));
      $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

      echo "<tr>";
	if($w_player_id==$PID || $b_player_id==$PID){
      	echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."&cmdRevoke=OC'>".$this->GetStringFromStringTable("IDS_GAME_TXT_OC_REVOKE")."</a></td>";
	}else{
      	echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."&cmdAccept=OC'>".$this->GetStringFromStringTable("IDS_GAME_TXT_OC_ACCEPT")."</a></td>";
      }
	echo "<td class='row2'><a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_143")."</a></td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
      echo "<td class='row2'>".$completion_status."</td>";
      echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
      echo "</tr>";

      $i++;
    }


    echo "</table>";
    echo "<br>";

  }



  /**********************************************************************
  * GetLastGameMoveDate
  *
  */
  function GetLastGameMoveDate($ConfigFile, $GameID){

    $query = "SELECT * FROM move_history WHERE game_id='".$GameID."' ORDER BY move_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $LastMove = "";

    if($num != 0){
      $LastMove = trim(mysql_result($return,0,"time"));
    }

    return $LastMove;
  }


  /**********************************************************************
  * SendGChat
  *
  */
  function SendGChat($ConfigFile, $GameID, $message){

    $message = rawurlencode($message); 
// Replace French, Latin characters with HTML equivalents
    $aToReplace = array("%E0","%E1","%E2","%E3","%E4","%E5","%E6","%E7","%E8","%E9","%EA","%EB","%EC","%ED","%EE","%EF","%F0","%F1","%F2","%F3","%F4","%F5","%F6","%F7","%F8","%F9","%FA","%FB","%FC","%FD","%FE","%FF","%20AC","201C","201D","%AB","%BB","A6","%C1","%C0","%C2","%C3","%C4","%C5","%C6","%C7","%C8","%C9","%CA","%CB","%CC","%CD","%CE","%CF","%D0","%D1","%D2","%D3","%D4","%D5","%D6","%D7","%D8","%D9","%DA","%DB","%DC","%DD","%DE","%DF");
    $aReplaceWith = array("&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;","&#8364;","&#8220;","&#8221;","&#171;","&#187;","&#166;","&#193;","&#192;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;");
    $message = str_replace($aToReplace, $aReplaceWith, $message);

    $insert = "INSERT INTO c4m_gamechat VALUES(NULL, '".$GameID."', '".$message."', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetGChat
  *
  */
  function GetGChat($ConfigFile, $GameID){

    $query = "SELECT * FROM c4m_gamechat WHERE tgc_gameid='".$GameID."' ORDER BY tgc_date DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $Message = $this->FilterChatText(mysql_result($return, $i, "tgc_message"));
	$Message = rawurldecode($Message);
        echo $Message."\n\n";
        $i++;
      }

    }

  }


  /**********************************************************************
  * FilterChatText
  *
  */
  function FilterChatText($Text){

    //Get the filter list
    $swear_list = array();
    $this->GetFilteredWordArray($swear_list);
    $nMaxItems = count($swear_list);

    $i = 0;

    while($i < $nMaxItems){

      $ToReplace = $swear_list[$i];
      $nLength = strlen($ToReplace);

      $ReplaceWith = "";
      $ii=0;

      while($ii < $nLength){
        $ReplaceWith = $ReplaceWith."*";
        $ii++;
      }

      $Text = str_replace($ToReplace, $ReplaceWith, $Text);

      $i++;
    }

    return $Text;

  }


  /**********************************************************************
  * IsPlayerDisabled
  *
  */
  function IsPlayerDisabled($PID){

    $query = "SELECT * FROM player2 WHERE player_id = ".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bDisabled = false;

    if($num != 0){
      $bDisabled = true;
    }

    return $bDisabled;

  }


  /**********************************************************************
  * IsPlayerDisabled2
  *
  */
  function IsPlayerDisabled2($UID){

    $query = "SELECT * FROM player2 WHERE userid = '".$UID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bDisabled = false;

    if($num != 0){
      $bDisabled = true;
    }

    return $bDisabled;

  }


  /**********************************************************************
  * NewUserRequiresApproval
  *
  */
  function NewUserRequiresApproval(){

    $query = "SELECT * FROM c4m_userregistration WHERE a_requiresreg = '0' LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bReturn = true;

    if($num != 0){
      $bReturn = false;
    }

    return $bReturn;

  }


  /**********************************************************************
  * UpdateUserRequiresApproval
  *
  */
  function UpdateUserRequiresApproval($Approve){

    $query = "SELECT * FROM c4m_userregistration LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $a_id = mysql_result($return,0,"a_id");

      $update = "UPDATE c4m_userregistration SET a_requiresreg = '".$Approve."' WHERE a_id=".$a_id;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO c4m_userregistration Values(NULL, '".$Approve."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * NewTournamentRequiresApproval
  *
  */
  function NewTournamentRequiresApproval(){

    $query = "SELECT * FROM c4m_autoaccepttournament WHERE a_requiresreg = '0' LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bReturn = true;

    if($num != 0){
      $bReturn = false;
    }

    return $bReturn;

  }


  /**********************************************************************
  * UpdateTournamentRequiresApproval
  *
  */
  function UpdateTournamentRequiresApproval($Approve){

    $query = "SELECT * FROM c4m_autoaccepttournament LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $a_id = mysql_result($return,0,"a_id");

      $update = "UPDATE c4m_autoaccepttournament SET a_requiresreg = '".$Approve."' WHERE a_id=".$a_id;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{
      $insert = "INSERT INTO c4m_autoaccepttournament Values(NULL, '".$Approve."')";
      mysql_query($insert, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * GetMainLink
  *
  */
  function GetMainLink(){

    $query = "SELECT * FROM c4m_mainlink LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $link = "n/a";

    if($num != 0){

      $a_name = mysql_result($return,0,"a_name");
      $a_url = mysql_result($return,0,"a_url");

      $link = "&nbsp;<a href='".$a_url."'>".$a_name."</a>&nbsp; &#8226;&nbsp;";
    }

    return $link;

  }


  /**********************************************************************
  * GetMainLinkByRef
  *
  */
  function GetMainLinkByRef(&$name, &$url){

    $query = "SELECT * FROM c4m_mainlink LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $name = mysql_result($return,0,"a_name");
      $url = mysql_result($return,0,"a_url");

    }

  }


  /**********************************************************************
  * UpdateMainLink
  *
  */
  function UpdateMainLink($name, $url){

    $query = "SELECT * FROM c4m_mainlink LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $a_id = mysql_result($return,0,"a_id");

      $update = "UPDATE c4m_mainlink SET a_name = '".$name."', a_url = '".$url."' WHERE a_id=".$a_id;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO c4m_mainlink Values(NULL, '".$name."', '".$url."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * LoginBilling
  *
  */
  function LoginBilling($UserID, $Pass){

    $query = "SELECT * FROM player WHERE userid='".$UserID."' AND password='".$Pass."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bLoggedIn = false;

    if($num != 0){
      $bLoggedIn = true;
    }

    return $bLoggedIn;

  }


  /**********************************************************************
  * PaypalIsEnabled
  *
  */
  function PaypalIsEnabled(){

    $query = "SELECT * FROM c4m_paypal WHERE a_requirespayment = '0' LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bReturn = true;

    if($num != 0){
      $bReturn = false;
    }

    return $bReturn;

  }


  /**********************************************************************
  * UpdatePaypalIsEnabled
  *
  */
  function UpdatePaypalIsEnabled($Approve){

    $query = "SELECT * FROM c4m_paypal LIMIT 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $a_id = mysql_result($return,0,"a_id");

      $update = "UPDATE c4m_paypal SET a_requirespayment = '".$Approve."' WHERE a_id=".$a_id;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO c4m_paypal Values(NULL, '".$Approve."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }


  }


  /**********************************************************************
  * GetCommandConfig
  *
  */
  function GetCommandConfig(&$userlimit, &$enabletournament, &$enableplayerimport){

    $query = "SELECT * FROM c4m_commandconfig";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $userlimit =  mysql_result($return,0,"o_userlimit");
      $enabletournament =  mysql_result($return,0,"o_enabletournament");
      $enableplayerimport =  mysql_result($return,0,"o_enableplayerimport");
    }

  }


  /**********************************************************************
  * CurUserLimit
  *
  */
  function CurUserLimit(){

    // Get current user count
    $query = "SELECT * FROM player";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $nUserCount = 0;

    $i=0;
    while($i < $num){

      $id = mysql_result($return,$i,"player_id");

      if($this->IsPlayerDisabled($id) == false){
        $nUserCount++;
      }

      $i++;
    }

    return $nUserCount;

  }


  /**********************************************************************
  * UserLimit
  *
  */
  function UserLimit(){

    $query = "SELECT * FROM c4m_commandconfig";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $nUserCountMAX = 0;

    if($num != 0){
      $nUserCountMAX = mysql_result($return,0,"o_userlimit");
    }

    return $nUserCountMAX;

  }


  /**********************************************************************
  * IsUserLimitReached
  *
  */
  function IsUserLimitReached(){

    $nUserCount = $this->CurUserLimit();
    $nUserCountMAX = $this->UserLimit();

    $isLimit = false;

    if($nUserCount >= $nUserCountMAX){
      $isLimit = true;
    }

    return $isLimit;
  }


  /**********************************************************************
  * IsTournamentEnabled
  *
  */
  function IsTournamentEnabled(){

    $this->GetCommandConfig($userlimit, $enabletournament, $enableplayerimport);

    $isenabled = false;

    if($enabletournament == 'y'){
      $isenabled = true;
    }

    return $isenabled;
  }


  /**********************************************************************
  * GetEmailSettings()
  *
  */
  function GetEmailSettings(&$strTermOver){

    $query = "SELECT * FROM c4m_emailmessageconfig";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $strTermOver = mysql_result($return,0,"o_regover");
    }

  }


  /**********************************************************************
  * SetEmailSettings()
  *
  */
  function SetEmailSettings($strTermOver){

    $query = "SELECT * FROM c4m_emailmessageconfig";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // remove quotes
    $strTermOver = str_replace("\"", "", $strTermOver);
    $strTermOver = str_replace("'", "", $strTermOver);

    if($num != 0){
      $o_id = mysql_result($return,0,"o_id");

      $update = "UPDATE c4m_emailmessageconfig SET o_regover='".$strTermOver."' WHERE o_id=".$o_id;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO c4m_emailmessageconfig VALUES(NULL, '".$strTermOver."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * AddMultiUserRedemptionCode()
  *
  */
  function AddMultiUserRedemptionCode($code){

    $insert = "INSERT INTO c4m_multiuserredemptioncode VALUES(NULL, '".$code."', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

  }

  /**********************************************************************
  * GetMultiUserRedemptionCodes()
  *
  */
  function GetMultiUserRedemptionCodes(){

    $query = "SELECT * FROM c4m_multiuserredemptioncode";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $i=0;

    // Skin table settings
    if(defined('CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_WIDTH') && defined('CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_BORDER')){
      echo "<table border='".CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_BORDER."' width='".CFG_GETMULTIUSERREDEMPTIONCODES_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' width='100%'>";
    }

    while($i < $num){

      $o_id = mysql_result($return,$i,"o_id");
      $o_redemptioncode = mysql_result($return,$i,"o_redemptioncode");
      $o_date = mysql_result($return,$i,"o_date");

      echo "<tr>";
      echo "<td class='row2'><input type='radio' name='rdoID' value='".$o_id."'></td>";
      echo "<td class='row2'>".$o_redemptioncode."</td>";
      echo "<td class='row2'>".$o_date."</td>";
      echo "</tr>";

      $i++;
    }

    echo "</table>";

  }


  /**********************************************************************
  * DeleteMultiUserRedemptionCode()
  *
  */
  function DeleteMultiUserRedemptionCode($ID){

    $delete = "DELETE FROM c4m_multiuserredemptioncode WHERE o_id=".$ID;
    mysql_query($delete, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetPlayerInfoByID2
  *
  */
  function GetPlayerInfoByID2($player_id, &$userid, &$password, &$email){

    $query = "SELECT * FROM player WHERE player_id = ".$player_id;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

       $player_id = trim(mysql_result($return,0,"player_id"));
       $userid = trim(mysql_result($return,0,"userid"));
       $password = trim(mysql_result($return,0,"password"));
       $email = trim(mysql_result($return,0,"email"));

    }

  }


  /**********************************************************************
  * InsertTournamentPlayer()
  *
  */
  function InsertTournamentPlayer($userid, $password, $email){

    $insert = "INSERT INTO player VALUES(NULL, '".$userid."', '".$password."', ".time().", 'F', '".$email."')";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

       $player_id = trim(mysql_result($return,0,0));

       $insert = "INSERT INTO player3 VALUES(".$player_id.", '".$userid."')";
       mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * IsPlayerImportEnabled
  *
  */
  function IsPlayerImportEnabled(){

    $this->GetCommandConfig($userlimit, $enabletournament, $enableplayerimport);

    $isenabled = false;

    if($enableplayerimport == 'y'){
      $isenabled = true;
    }

    return $isenabled;

  }


  /**********************************************************************
  * GetPointRankingWords
  *
  */
  function GetPointRankingWords($points, $wins){

    //Generate points
    $Ranking = "";

    if($wins == 0){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_72")."";
    }

    if($wins > 0 && $wins <= 25){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_73")."";
    }

    if($wins > 25){
      $Ranking = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_74")."";
    }

    return $Ranking;

  }


  /**********************************************************************
  * GetPlayerListByPoints
  *
  */
  function GetPlayerListByPoints($config, $ID){

    $this->GetPlayerStatusrRefByPlayerID($config, $ID, $wins, $loss, $draws);

    if($this->ELOIsActive()){
      $points = $this->ELOGetRating($ID);
    }else{
      $points = $this->GetPointValue($wins, $loss, $draws);
    }

    $ppoints = $this->GetPointRankingWords($points, $wins);

    echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>Players By Point Relation</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_31")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    $query = "SELECT * FROM player ORDER BY userid ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

      // Place the results in the array
      if($num != 0){

        $PlayerPoints = array();

        $i = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          if($this->IsPlayerDisabled($player_id) == false){
            $wins = 0;
            $loss = 0;
            $draws = 0;

            $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);

            if($this->ELOIsActive()){
              $points = $this->ELOGetRating($player_id);
            }else{
              $points = $this->GetPointValue($wins, $loss, $draws);
            }

            $pointrank = $this->GetPointRankingWords($points, $wins);

            $PlayerPoints[$i]['PlayerID'] = $player_id;
            $PlayerPoints[$i]['UserID'] = $userid;
            $PlayerPoints[$i]['SignUpTime'] = $signup_time;
            $PlayerPoints[$i]['Email'] = $email;
            $PlayerPoints[$i]['PointRank'] = $pointrank;
            $PlayerPoints[$i]['Points'] = $points;

          }

          $i++;

        }

        $ncount = count($PlayerPoints);
        $ii = 0;
        $iii = 0;
        while($ii < $ncount){

          if($PlayerPoints[$ii]['PointRank'] == $ppoints){

            $iii++;
            echo "<tr>";
            echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$PlayerPoints[$ii]['PlayerID']."&name=".$PlayerPoints[$ii]['UserID']."'>".$PlayerPoints[$ii]['UserID']."</a></td>";
            echo "<td class='row2'>".date("m-d-Y",$PlayerPoints[$ii]['SignUpTime'])."</td>";
            echo "<td class='row2'><a href='mailto:".$PlayerPoints[$ii]['Email']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";

            echo "<td class='row2'>".$PlayerPoints[$ii]['Points']."</td>";

            echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$PlayerPoints[$ii]['PlayerID']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
            echo "<td class='row2'>";

            if($this->IsPlayerOnline($ConfigFile, $PlayerPoints[$ii]['PlayerID'])){
              echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
            }else{
              echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
            }

            echo "</td>";
            echo "</tr>";
          }

          if($iii >= 5){
            $ii = $ncount;
          }


          $ii++;

        }

    }

    echo "</table>";

  }


  /**********************************************************************
  * ViewTournamentGameStatusCalendar
  *
  */
  function ViewTournamentGameStatusCalendar($ConfigFile, $TID, $month, $day, $year, $index){

    //Find the days in the current month
    $nDays = date("t", mktime(0,0,0, $month, $day, $year));

    // Get current month text
    $strmonth = date("F", mktime(0,0,0, $month, $day, $year));

    // Get current date
    $today = date("n.j.Y");
    list($monthc, $dayc, $yearc) = preg_split('/[\/.-]/', $today, 3);

    // Get the start day text of the month
    $tDay = date("l", mktime(0,0,0, $month, $day, $year));

    $startcol = 0;

    switch($tDay){

      case "Saturday":
        $startcol = '6';
        break;

      case "Friday":
        $startcol = '5';
        break;

      case "Thursday":
        $startcol = '4';
        break;

      case "Wednesday":
        $startcol = '3';
        break;

      case "Tuesday":
        $startcol = '2';
        break;

      case "Monday":
        $startcol = '1';
        break;

      case "Sunday":
        $startcol = '0';
        break;

    }

    // Generate the calendar
    echo "<table border='0' width='500' cellspacing='0' align='center'>";
    echo "<tr>";

    if(($index - 1) >= 0){
      echo "<td width='21' class='tableheadercolor'><p align='left'><a href='./chess_tournament_status.php?tid=".$TID."&cmonth=".($index - 1)."'><img border='0' src='./skins/".$this->SkinsLocation."/images/back.gif' width='13' height='13'></a></p></td>";
    }else{
      echo "<td width='21' class='tableheadercolor'></td>";
    }

    echo "<td class='tableheadercolor'><p align='center'>".$strmonth." ".$year."</p></td>";

    echo "<td width='21' class='tableheadercolor'><p align='right'><a href='./chess_tournament_status.php?tid=".$TID."&cmonth=".($index + 1)."'><img border='0' src='./skins/".$this->SkinsLocation."/images/next.gif' width='13' height='13'></a></p></td>";
    echo "</tr>";
    echo "</table>";

    echo "<table border='1' cellspacing='0' width='500' align='center'>";
    echo "<tr>";
    echo "<td class='row1' align='center'>Sun</td><td class='row1' align='center'>Mon</td><td class='row1' align='center'>Tues</td><td class='row1' align='center'>Wed</td><td class='row1' align='center'>Thur</td><td class='row1' align='center'>Fri</td><td class='row1' align='center'>Sat</td>";
    echo "</tr>";

    $ncol = 0;
    $nscol = 0;
    $nswitchcol = 6;
    $ncdate = 1;

    echo "<tr>";

    while($ncdate < $nDays + 1){

      if($nscol > $nswitchcol){
        echo "</tr><tr>";
        $nscol = 0;
      }

      if($ncol >= $startcol){

        if($monthc == $month && $dayc == $ncdate && $yearc == $year){

          echo "<td class='textcurrent' align='center' valign='top'>";
          echo $ncdate."<br>";

          $query1 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID." AND tm_starttime like '".date("Y-m-d", mktime(0,0,0, $month, $ncdate, $year))."%' ORDER BY tm_id ASC";
          $return1 = mysql_query($query1, $this->link) or die(mysql_error());
          $num1 = mysql_numrows($return1);

          if($num1 !=0){

            $i=0;
            while($i < $num1){

              $tm_id = trim(mysql_result($return1,$i,"tm_id"));
              $tm_tournamentid = trim(mysql_result($return1,$i,"tm_tournamentid"));
              $tm_status = trim(mysql_result($return1,$i,"tm_status"));
              $tm_starttime = trim(mysql_result($return1,$i,"tm_starttime"));
              $tm_endtime = trim(mysql_result($return1,$i,"tm_endtime"));

              //Get the games
              $query2 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." ORDER BY tg_id ASC";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              if($num2 !=0){

                $ii=0;
                while($ii < $num2){

                  $tg_id = trim(mysql_result($return2,$ii,"tg_id"));
                  $tg_tmid = trim(mysql_result($return2,$ii,"tg_tmid"));
                  $tg_gameid = trim(mysql_result($return2,$ii,"tg_gameid"));
                  $tg_playerid = trim(mysql_result($return2,$ii,"tg_playerid"));
                  $tg_otherplayerid = trim(mysql_result($return2,$ii,"tg_otherplayerid"));
                  $tg_playerloggedin = trim(mysql_result($return2,$ii,"tg_playerloggedin"));
                  $tg_otherplayerloggedin = trim(mysql_result($return2,$ii,"tg_otherplayerloggedin"));
                  $tg_status = trim(mysql_result($return2,$ii,"tg_status"));

                  if($tg_status == "A" || $tg_status == "I"){
                    echo "<a href=\"javascript:PopupWindow('./t_index.php?gid=".$tg_gameid."')\">".$this->GetUserIDByPlayerID($ConfigFile, $tg_playerid)." VS ".$this->GetUserIDByPlayerID($ConfigFile, $tg_otherplayerid)."</a><br>";
                  }else{
                    echo $this->GetUserIDByPlayerID($ConfigFile, $tg_playerid)." VS ".$this->GetUserIDByPlayerID($ConfigFile, $tg_otherplayerid)."<br>";
                  }

                  $ii++;

                }

              }


              $i++;

            }

          }

          echo "</td>";

        }else{

          echo "<td class='row2' align='center' valign='top'>";
          echo $ncdate."<br>";

          $query1 = "SELECT * FROM c4m_tournamentmatches WHERE tm_tournamentid=".$TID." AND tm_starttime like '".date("Y-m-d", mktime(0,0,0, $month, $ncdate, $year))."%' ORDER BY tm_id ASC";
          $return1 = mysql_query($query1, $this->link) or die(mysql_error());
          $num1 = mysql_numrows($return1);

          if($num1 !=0){

            $i=0;
            while($i < $num1){

              $tm_id = trim(mysql_result($return1,$i,"tm_id"));
              $tm_tournamentid = trim(mysql_result($return1,$i,"tm_tournamentid"));
              $tm_status = trim(mysql_result($return1,$i,"tm_status"));
              $tm_starttime = trim(mysql_result($return1,$i,"tm_starttime"));
              $tm_endtime = trim(mysql_result($return1,$i,"tm_endtime"));

              //Get the games
              $query2 = "SELECT * FROM c4m_tournamentgames WHERE tg_tmid=".$tm_id." ORDER BY tg_id ASC";
              $return2 = mysql_query($query2, $this->link) or die(mysql_error());
              $num2 = mysql_numrows($return2);

              if($num2 !=0){

                $ii=0;
                while($ii < $num2){

                  $tg_id = trim(mysql_result($return2,$ii,"tg_id"));
                  $tg_tmid = trim(mysql_result($return2,$ii,"tg_tmid"));
                  $tg_gameid = trim(mysql_result($return2,$ii,"tg_gameid"));
                  $tg_playerid = trim(mysql_result($return2,$ii,"tg_playerid"));
                  $tg_otherplayerid = trim(mysql_result($return2,$ii,"tg_otherplayerid"));
                  $tg_playerloggedin = trim(mysql_result($return2,$ii,"tg_playerloggedin"));
                  $tg_otherplayerloggedin = trim(mysql_result($return2,$ii,"tg_otherplayerloggedin"));
                  $tg_status = trim(mysql_result($return2,$ii,"tg_status"));

                  if($tg_status == "A" || $tg_status == "I"){
                    echo "<a href=\"javascript:PopupWindow('./t_index.php?gid=".$tg_gameid."')\">".$this->GetUserIDByPlayerID($ConfigFile, $tg_playerid)." VS ".$this->GetUserIDByPlayerID($ConfigFile, $tg_otherplayerid)."</a><br>";
                  }else{
                    echo $this->GetUserIDByPlayerID($ConfigFile, $tg_playerid)." VS ".$this->GetUserIDByPlayerID($ConfigFile, $tg_otherplayerid)."<br>";
                  }

                  $ii++;

                }

              }

              $i++;

            }

          }

          echo "</td>";

        }

        $ncol++;
        $nscol++;
        $ncdate++;

      }else{
        echo "<td class='row2' align='center'>&nbsp;</td>";
        $ncol++;
        $nscol++;
      }

    }

    // fill the remaining cols if any
    if($nscol < $nswitchcol + 1){

      while($nscol < $nswitchcol + 1){
        echo "<td class='row2' align='center'>&nbsp;</td>";
        $nscol++;
      }

    }

    echo "</tr>";
    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetPlayerGameCount
  *
  */
  function GetPlayerGameCount($PlayerID){

    $PersonalGame = 0;

    //count black player//////////////////////////////////////////////////////
    $query = "SELECT count(*) FROM game WHERE b_player_id = ".$PlayerID." AND completion_status IN('C')";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $PersonalGame = $PersonalGame + mysql_result($return,0,0);
    }

    //count white player//////////////////////////////////////////////////////
    $query1 = "SELECT count(*) FROM game WHERE w_player_id = ".$PlayerID." AND completion_status  IN('C')";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $PersonalGame = $PersonalGame + mysql_result($return1,0,0);
    }

    return $PersonalGame;

  }


  /**********************************************************************
  * ELOCalculation
  *
  */
  function ELOCalculation($PlayerRating, $oPlayerRating, $GamePoints, $GameCount, $PlayerTotalGameCount){

    ////////////////////////////////////////////////////////////////////////
    //Rn =  Ro + K (W - We)
    ////////////////////////////////////////////////////////////////////////

    //Players old rating
    $Ro = $PlayerRating;

    //Determine the players constant
    if($PlayerRating < 2400 && $PlayerTotalGameCount >= 30){
      $K = 15;
    }elseif($PlayerRating >= 2400 && $PlayerTotalGameCount >= 30){
      $K = 10;
    }else{
      $K = 25;
    }

    //Game or event score 1=win 0=loss 0.5=draw
    $W = $GamePoints;

    ////////////////////////////////////////////////////////////////////////
    //we = We = 1 / (10^(-dr/400) + 1)
    ////////////////////////////////////////////////////////////////////////

    //Rating difference
    $dr = $PlayerRating - $oPlayerRating;

    //The expected score (Win Expectancy)
    $We = 1/(pow(10, -$dr/400)+1);

    //Calculate the players new score
    $Rn = $Ro + round($K*($W - $We), 0);

    return $Rn;

  }


  /**********************************************************************
  * ELOUpdateRating
  *
  */
  function ELOUpdateRating($playerid, $newrating){

    $oldrating = $this->ELOGetRating($playerid);

    $update = "UPDATE elo_points SET cpoints=".$newrating.", opoints=".$oldrating." WHERE player_id=".$playerid;
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * ELOGetRating
  *
  */
  function ELOGetRating($playerid){

    $Rating = 0;

    $query1 = "SELECT * FROM elo_points WHERE player_id=".$playerid;
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $Rating = mysql_result($return1,0,"cpoints");

    }

    return $Rating;

  }


  /**********************************************************************
  * ELOIsActive
  *
  */
  function ELOIsActive(){

    $active = false;

    $query1 = "SELECT * FROM elo_points";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $active = true;
    }

    return $active;

  }


  /**********************************************************************
  * ELOCreateRatings
  *
  */
  function ELOCreateRatings(){

    //Select all the players in the game
    $query = "SELECT * FROM player";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $playerid = mysql_result($return,$i,"player_id");

        //create an ELO rating record if the player does not exist yet
        $query1 = "SELECT * FROM elo_points WHERE player_id = ".$playerid;
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

        }else{
          $this->GetPlayerStatusrRefByPlayerID($this->ChessCFGFileLocation, $playerid, $wins, $loss, $draws);
          $playerrating = $this->GetPointValue($wins, $loss, $draws);

          $insert = "INSERT INTO elo_points VALUES(".$playerid.", ".$playerrating.", ".$playerrating.")";
          mysql_query($insert, $this->link);
        }

        $i++;
      }

    }

  }


  /**********************************************************************
  * ELODeleteRatings
  *
  */
  function ELODeleteRatings(){

    //Removes all players from the ELO rating system
    $delete = "DELETE FROM elo_points";
    mysql_query($delete, $this->link) or die(mysql_error());

  }

  /**********************************************************************
  * SetDBPointValue
  *
  */
  function SetDBPointValue($pointvalue){

    $update = "UPDATE chess_point_value SET o_points='".$pointvalue."' WHERE o_id=1";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetDBPointValue()
  *
  */
  function GetDBPointValue(){

    $query = "SELECT * FROM chess_point_value WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $points = mysql_result($return,0,"o_points");
    }

    return $points;
  }


  /**********************************************************************
  * CheckFiftyMoveRule()
  *
  */
  function CheckFiftyMoveRule($ConfigFile, $GameID){

    // Set up the move table
    $aTags = array();
    $aPGNMoves = array();

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $this->ParsePGNMove($PGN, $aTags, $aPGNMoves);

    $Player = "";
    $color = "";

    $this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);

    if(isset($aPGNMoves)){

       $PGNMoveList= explode(" ", $aPGNMoves[0]);
       $itemcount = count($PGNMoveList);

       $rowswitch = 0;
       $rowstart = true;
       $itemcounter = 0;

       $mod = $itemcount % 3;

       $idcount = 0;
       $idswitch = 0;

       $Moves1 = 0;
       $Moves2 = 0;

       foreach($PGNMoveList as $item1){

         $itemcounter++;
         $idswitch++;

         if($rowswitch == 3){
           //echo "</tr>\n<tr>";
           $rowswitch = 0;
         }

         if($idswitch > 3){
           $idswitch = 1;
         }

         if($mod != 0 && $itemcounter == $itemcount){
           //echo "<br>$item1";
         }else{

           if($idswitch == 2 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
             $idcount++;
             //echo "<br>".$item1;

             // Check if a capture or a pawn move has been made
             if(preg_match("/[RNBQK]/",$item1)){

               if(preg_match("/[x]/",$item1)){
                 $Moves1 = 0;
                 $Moves2 = 0;
               }else{
                 $Moves1++;
               }

             }else{
               $Moves1 = 0;
               $Moves2 = 0;
             }

             //echo " {".$Moves1." ".$Moves2."]";

           }else{
             if($idswitch == 3 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
               $idcount++;
               //echo "<br>".$item1;

               // Check if a capture or a pawn move has been made
               if(preg_match("/[RNBQK]/",$item1)){

                 if(preg_match("/[x]/",$item1)){
                   $Moves1 = 0;
                   $Moves2 = 0;
                 }else{
                   $Moves2++;
                 }

               }else{
                 $Moves1 = 0;
                 $Moves2 = 0;
               }

               //echo " {".$Moves1." ".$Moves2."]";

             }else{

               //echo "<br>".$item1;
             }

           }

         }

         $rowswitch++;

       }

    }

    //display move count
    //echo "<br>".$Moves1." ".$Moves2;

    return array($Moves1, $Moves2);

  }

   /*  Checks to see if the current board status has occurred twice before (three fold repetition)
        game_id - the id of the game to check.
        Returns an array indicating if white or black's position has occurred three times
        eg: array(3,0) - white 3 fold, array(0,3) - black 3 fold or array (0,0) - none 3 fold.
    */
  function CheckRepetitionRule($ConfigFile, $GameID)
  {
    $oChess = new CChess($this->ChessCFGFileLocation);
    // Need to get the board setup for the game.
    $oChess->GetNewGameFEN($this->ChessCFGFileLocation, $GameID, $aChessBoard, $Other1, $Other2, $Other3, $Other4, $Round, $Error);
    unset($oChess);
    //$color = "";
    //$this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);  # Need to know the side that started.
    $board = "";
    if($Error != "Error"){
      $board= $aChessBoard;
    }else{ // Use default on error?
      $board= array(array('r','n','b','q','k','b','n','r'),
              array('p','p','p','p','p','p','p','p'),
              array('e','e','e','e','e','e','e','e'),
              array('e','e','e','e','e','e','e','e'),
              array('e','e','e','e','e','e','e','e'),
              array('e','e','e','e','e','e','e','e'),
              array('P','P','P','P','P','P','P','P'),
              array('R','N','B','Q','K','B','N','R'));
    }

    // mapping to translate the rows into numbers to access the board.
    $trans['a']=0;$trans['b']=1;$trans['c']=2;$trans['d']=3;$trans['e']=4;$trans['f']=5;$trans['g']=6;$trans['h']=7;

    $rgBoards = array();  // Will store all board states + en passant status + castling statuses for both sides.
    $wCK = false; $wCQ = false; $bCK = false; $bCQ = false;   // Vars to store castleing states.
    
    // Get move history for game.
    $query = "SELECT * FROM move_history WHERE game_id='".$GameID."' ORDER BY time ASC";
    $return = mysql_query($query) or die(mysql_error());
    $moveCnt = mysql_numrows($return);   
    
    // Need to find out if it is now white's or black's turn.
    // to find out from which move to start from.
    // $i = 0;
    // if($color == "w")
    // {
      // $i = $moveCnt % 2;
    // }
    // else
    // {
      // $i = ($moveCnt + 1) % 2;
    // }
    // echo "curturn = " . $i;

    // Loop through moves.
    while($i < $moveCnt){

      $move = mysql_result($return, $i, "move");

      $start_col = substr($move,0,1);
      $start_row = substr($move,1,1);
      $finish_col = substr($move,3,1);
      $finish_row = substr($move,4,1);
      
      $enpassant = false;
      
      // Check if the move is castling. They need to be treated individually in adjusting the board.
      if($move == "O-O w"){
        $board[0][6] = "k";
        $board[0][4] = "e";
        $board[0][5] = "r";
        $board[0][7] = "e";    
      }elseif($move == "O-O b"){    
        $board[7][6] = "K";
        $board[7][4] = "e";
        $board[7][5] = "R";
        $board[7][7] = "e";
      }elseif($move == "O-O-O w"){
        $board[0][2] = "k";
        $board[0][4] = "e";
        $board[0][3] = "r";
        $board[0][0] = "e";
      }elseif($move == "O-O-O b"){
        $board[7][2] = "K";
        $board[7][4] = "e";
        $board[7][3] = "R";
        $board[7][0] = "e";
      }
      // Non castling moves. Move piece on start tile to end tile.
      // Also checks if the move is an en-passant move which requires
      // the pawn to be removed.
      elseif(strlen($move)>6) // hopefully this handles en passant... will see sooner or later.
      {
        $col = (int)substr($move,6,1);
        $row = (int)substr($move,7,1);
        $board[$col][$row] = 'e';
        $board[($finish_row-1)][($trans[$finish_col])]=$board[($start_row-1)][($trans[$start_col])];
        $board[($start_row-1)][($trans[$start_col])]='e';
        $enpassant = true;
      }
      else{
        $board[($finish_row-1)][$trans[$finish_col]] = $board[($start_row-1)][($trans[$start_col])];
        $board[($start_row-1)][($trans[$start_col])] = 'e';
      }
      
      // Record board in list.
      $rgBoards[$i] = array($board, $enpassant);
      $i++;
    }
    $counter = 0; // Counts how many times a board status has occurred repeatedly.
    // Now compare every board status with the current one
    $last = $moveCnt - 1;
    //echo "last is: ".$last;
    for($i = ($moveCnt - 1) & 1; $i < $moveCnt - 2; $i += 2) // Check boards for the player whose turn it was.
    {
    //echo "loop\n";
      $same = true;
      $tmp = $rgBoards[$i][0];
      //echo "<br />". var_dump($tmp);
      for($r = 0; $r < 8; $r++)
      {
        for($c = 0; $c < 8; $c++)
        {
          if($tmp[$r][$c] != $board[$r][$c])
            $same = false;
        }
      }
      //echo "here it is: " .$same."<br />";
      if($rgBoards[$i][1] != $rgBoards[$last][1])  // En passant status comparison.
        $same = false;
        //echo "here it is: " .$same."<br />";
      if($same)
      {
        $counter++;
      //  echo "counter is now: ".$counter;
      }
      //echo "counter is now: ".$counter;
    }
    
    // Two previous occurrances indicate 3 fold repetition applies.
    $color = "";
    $this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);  # Need to know the side that started.

    // Need to find out if it is now white's or black's turn
    // to find out which side claims the draw. Not that it matters.
    $i = 0;
    if($color == "w")
    {
      $i = $moveCnt % 2;
    }
    else
    {
      $i = ($moveCnt + 1) % 2;
    }
    //echo "curturn = " . $i;
    
    if($counter >= 2)
    {
      if($i == 0)
        return array(3,0);
      else
        return array(0,3);
    }
    
    return array(0,0);
  }
  
  /**********************************************************************
  * CheckRepetitionRuleOLD()
  *
  */
  function CheckRepetitionRuleOLD($ConfigFile, $GameID){

    // Set up the move table
    $aTags = array();
    $aPGNMoves = array();

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $this->ParsePGNMove($PGN, $aTags, $aPGNMoves);

    $Player = "";
    $color = "";

    $this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);

    if(isset($aPGNMoves)){

       $PGNMoveList= explode(" ", $aPGNMoves[0]);
       $itemcount = count($PGNMoveList);

       $rowswitch = 0;
       $rowstart = true;
       $itemcounter = 0;

       $mod = $itemcount % 3;

       $idcount = 0;
       $idswitch = 0;

       $aMoves1 = array();
       $aMoves2 = array();

       foreach($PGNMoveList as $item1){

         $itemcounter++;
         $idswitch++;

         if($rowswitch == 3){
           //echo "</tr>\n<tr>";
           $rowswitch = 0;
         }

         if($idswitch > 3){
           $idswitch = 1;
         }

         if($mod != 0 && $itemcounter == $itemcount){
           //echo "<br>$item1";
         }else{

           if($idswitch == 2 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
             $idcount++;
             array_push($aMoves1, $item1);

           }else{
             if($idswitch == 3 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
               $idcount++;
               array_push($aMoves2, $item1);

             }else{

               //echo "<br>".$item1;
             }

           }

         }

         $rowswitch++;

       }

    }

    ////////////////////////////////////////////////////////////////////////////////
    //check the moves for repetitions
    ////////////////////////////////////////////////////////////////////////////////
    $nmov1cnt = count($aMoves1);
    $nmov2cnt = count($aMoves2);

    $repcountmv1 = 1;
    $repcountmv2 = 1;

    //Check 3 rep rule for first player
    $switch = 0;
    $compstr = "";
    $i = $nmov1cnt-1;
    $bexitloop = false;
    while($i != -1 && !$bexitloop){

      if($switch > 1){
        $switch = 0;
      }

      if($switch == 0){

        if($compstr == ""){
          $compstr = $aMoves1[$i];
        }else{
          if($compstr == $aMoves1[$i]){
            //echo $compstr." ==".$aMoves1[$i];
            $repcountmv1++;
          }else{
           $bexitloop = true;
          }
        }

        $ii++;
      }

      $switch++;
      $i--;
    }

    //echo "<br>".$repcountmv1."<br>";

    //Check 3 rep rule for second player
    $switch = 0;
    $compstr = "";
    $i = $nmov2cnt-1;
    $bexitloop = false;
    while($i != -1 && !$bexitloop){

      if($switch > 1){
        $switch = 0;
      }

      if($switch == 0){

        if($compstr == ""){
          $compstr = $aMoves2[$i];
        }else{
          if($compstr == $aMoves2[$i]){
            //echo $compstr." ==".$aMoves2[$i];
            $repcountmv2++;
          }else{
           $bexitloop = true;
          }
        }

        $ii++;
      }

      $switch++;
      $i--;
    }

    //echo "<br>".$repcountmv2."<br>";

    return array($repcountmv1, $repcountmv2);

  }


  /**********************************************************************
  * GetBoardStyleByUserID
  *
  */
  function GetBoardStyleByUserID($playerid){

    $style = 1;

    $query1 = "SELECT * FROM chess_boardstyle WHERE id=".$playerid;
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $style = mysql_result($return1,0,"style");
    }

    return $style;

  }


  /**********************************************************************
  * SetBoardStyleByUserID
  *
  */
  function SetBoardStyleByUserID($playerid, $Style){

    $query1 = "SELECT * FROM chess_boardstyle WHERE id=".$playerid;
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $update = "UPDATE chess_boardstyle SET style=".$Style." WHERE id=".$playerid;
      mysql_query($update, $this->link) or die(mysql_error());
    }else{
      $Insert = "INSERT INTO chess_boardstyle VALUES(".$playerid.", ".$Style.")";
      mysql_query($Insert, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * FindPlayersByPoints
  *
  */
  function FindPlayersByPoints($config, $ID, $Above, $Below){

    $this->GetPlayerStatusrRefByPlayerID($config, $ID, $wins, $loss, $draws);

    if($this->ELOIsActive()){
      $points = $this->ELOGetRating($ID);
    }else{
      $points = $this->GetPointValue($wins, $loss, $draws);
    }

    $ppoints = $points; //$this->GetPointRankingWords($points, $wins);

    // Skin table settings
    if(defined('CFG_FINDPLAYERSBYPOINTS_TABLE1_WIDTH') && defined('CFG_FINDPLAYERSBYPOINTS_TABLE1_BORDER') && defined('CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLPADDING') && defined('CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLSPACING') && defined('CFG_FINDPLAYERSBYPOINTS_TABLE1_ALIGN')){
      echo "<table width='".CFG_FINDPLAYERSBYPOINTS_TABLE1_WIDTH."' border='".CFG_FINDPLAYERSBYPOINTS_TABLE1_BORDER."' cellpadding='".CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLPADDING."' cellspacing='".CFG_FINDPLAYERSBYPOINTS_TABLE1_CELLSPACING."' align='".CFG_FINDPLAYERSBYPOINTS_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>Players By Point Relation</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    $query = "SELECT * FROM player ORDER BY userid ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

      // Place the results in the array
      if($num != 0){

        $PlayerPoints = array();

        $i = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          if($this->IsPlayerDisabled($player_id) == false){
            $wins = 0;
            $loss = 0;
            $draws = 0;

            $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);

            if($this->ELOIsActive()){
              $points = $this->ELOGetRating($player_id);
            }else{
              $points = $this->GetPointValue($wins, $loss, $draws);
            }

            $pointrank = $this->GetPointRankingWords($points, $wins);

            $PlayerPoints[$i]['PlayerID'] = $player_id;
            $PlayerPoints[$i]['UserID'] = $userid;
            $PlayerPoints[$i]['SignUpTime'] = $signup_time;
            $PlayerPoints[$i]['Email'] = $email;
            $PlayerPoints[$i]['PointRank'] = $pointrank;
            $PlayerPoints[$i]['Points'] = $points;

          }

          $i++;

        }

        $ncount = count($PlayerPoints);
        $ii = 0;
        $iii = 0;
        while($ii < $ncount){

          if(($PlayerPoints[$ii]['Points'] <= ($ppoints + $Above)) && ($PlayerPoints[$ii]['Points'] >= ($ppoints - $Below))){
            //echo $PlayerPoints[$ii]['Points']." <= ".($ppoints + $Above)." ".$PlayerPoints[$ii]['Points']." >= ".($ppoints - $Below);
            $iii++;
            echo "<tr>";
            echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$PlayerPoints[$ii]['PlayerID']."&name=".$PlayerPoints[$ii]['UserID']."'>".$PlayerPoints[$ii]['UserID']."</a></td>";
            echo "<td class='row2'>".date("m-d-Y",$PlayerPoints[$ii]['SignUpTime'])."</td>";
            echo "<td class='row2'><a href='./chess_msg_center.php?type=newmsg&slctUsers=".$PlayerPoints[$ii]['PlayerID']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";

            //echo "<td class='row2'>".$PlayerPoints[$ii]['Points']."</td>";

            echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$PlayerPoints[$ii]['PlayerID']."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
            echo "<td class='row2'>";

            if($this->IsPlayerOnline($ConfigFile, $PlayerPoints[$ii]['PlayerID'])){
              echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
            }else{
              echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
            }

            echo "</td>";
            echo "</tr>";
          }

          if($iii >= 5){
            $ii = $ncount;
          }

          $ii++;

        }

    }

    echo "</table>";

  }


  /**********************************************************************
  * IsTimedGameRestricted
  *
  */
  function IsTimedGameRestricted($gid){

    $benabled = false;

    $query1 = "SELECT * FROM timed_games WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $benabled = true;
    }

    return $benabled;

  }


  /**********************************************************************
  * TimedGameStats
  *
  */
  function TimedGameStats($gid){

    $query1 = "SELECT * FROM timed_games WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $moves1 = mysql_result($return1,0,"moves1");
      $time1 = mysql_result($return1,0,"time1");
      $moves2 = mysql_result($return1,0,"moves2");
      $time2 = mysql_result($return1,0,"time2");

      echo "<table width='210' border='0'>";
      echo "<tr>";
      echo "<td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_146")."</b></td>";
      echo "</tr>";

      $aKeys1 = array("['moves1']", "['time1']");
      $aReplace1 = array($moves1, $time1);

      echo "<tr>";
      echo "<td>".str_replace($aKeys1, $aReplace1, $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_147"))."</td>";
      echo "</tr>";

      if($moves2 != 0 && $time2 != 0){

        $aKeys2 = array("['moves2']", "['time2']");
        $aReplace2 = array($moves2, $time2);

        echo "<tr>";
        echo "<td>".str_replace($aKeys2, $aReplace2, $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_148"))."</td>";
        echo "</tr>";
      }

      echo "</table>";

    }

  }


  /**********************************************************************
  * UpdatePlayerTime
  *
  */
  function UpdatePlayerTime($gid, $isblack, $Xseconds){

    $query1 = "SELECT * FROM timed_game_stats WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      //update user minutes
      if($isblack){
        $xtime = mysql_result($return1,0,"blacktime");
      }else{
        $xtime = mysql_result($return1,0,"whitetime");
      }

      list($hours1, $mins1, $seconds1) = explode(":", $xtime, 3);

      $seconds = (int)$seconds1 + (int)$Xseconds;
      $mins = $mins1;
      $hours = $hours1;

      if($seconds >= 60){
        $seconds = $seconds - 60;
        $mins = $mins + 1;
      }

      if($mins >= 60){
        $mins = $mins - 60;
        $hours = $hours + 1;
      }

      $xtime = "".trim($hours)." : ".trim($mins)." : ".trim($seconds)."";

      //update user minutes
      if($isblack){
        $update = "UPDATE timed_game_stats SET blacktime='".$xtime."' WHERE id='".$gid."'";
      }else{
        $update = "UPDATE timed_game_stats SET whitetime='".$xtime."' WHERE id='".$gid."'";
      }

      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      //Get Game Stats
      $query2 = "SELECT * FROM timed_games WHERE id='".$gid."'";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num2 != 0){

        $time1 = mysql_result($return2,0,"time1");

        $sec1 = 0;
        $min1 = (int)$time1;
        $hour1 = 0;

        $tmpmin = $min1/60;

        //list($hours, $mins) = split('.', (string)$tmpmin);
        $hour1 = (int)$tmpmin;
        $min1 = (($min1/60) - $hour1)*60;

        //Total Time White
        $whitett = "".trim($hour1)." : ".trim($min1)." : ".trim($sec1)."";

        //Total Time Black
        $Blacktt = "".trim($hour1)." : ".trim($min1)." : ".trim($sec1)."";

        $insert = "INSERT INTO timed_game_stats VALUES('".$gid."', '".$whitett."', '".$Blacktt."', '00: 00 : 00', '00: 00 : 00', ".time().", ".strtotime("+".$time1." minute", time()).", 0, 0)";
        mysql_query($insert, $this->link) or die(mysql_error());
      }

    }

  }


  /**********************************************************************
  * GetPlayerTimeRT
  *
  */
  function GetPlayerTimeRT($gid, $color){

    $hours1 = 0;
    $mins1 = 0;
    $seconds1 = 0;

    $query1 = "SELECT * FROM timed_game_stats WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      if($color == "white"){
        $time = mysql_result($return1,0,"whitetime");
      }else{
        $time = mysql_result($return1,0,"blacktime");
      }

      list($hours1, $mins1, $seconds1) = explode(":", $time, 3);

    }

    return array((int)$hours1, (int)$mins1, (int)$seconds1);

  }


  /**********************************************************************
  * GetPlayerMoveCount
  *
  */
  function GetPlayerMoveCount($gid, $pid){

    $count = 0;

    $query1 = "SELECT COUNT(*) FROM move_history WHERE game_id='".$gid."' AND player_id='".$pid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $count = mysql_result($return1,0,0);
    }

    return $count;

  }


  /**********************************************************************
  * PlayerTimeConstreached
  *
  */
  function PlayerTimeConstreached($gid, $w_player_id, $b_player_id){

    // Get real time information
    $query1 = "SELECT * FROM timed_game_stats WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $query2 = "SELECT * FROM timed_games WHERE id='".$gid."'";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    // Check if there are records for both queries
    if($num1 != 0 && $num2 !=0){

      $endtimew = mysql_result($return1,0,"endtimew");
      $endtimeb = mysql_result($return1,0,"endtimeb");
      $whitetime = mysql_result($return1,0,"whitetime");
      $blacktime = mysql_result($return1,0,"blacktime");
      $starttime = mysql_result($return1,0,"starttime");
      $endtime = mysql_result($return1,0,"endtime");
      $wtimectrl = mysql_result($return1,0,"wtimectrl");
      $btimectrl = mysql_result($return1,0,"btimectrl");

      // Get time info and convert it to seconds
      list($hours1, $mins1, $seconds1) = explode(":", $whitetime, 3);
      $playersecondsw = ((int)$hours1 * 3600 ) + ((int)$mins1 * 60) + (int)$seconds1;
      $wmovecnt = $this->GetPlayerMoveCount($gid, $w_player_id);

      list($hours2a, $mins2a, $seconds2a) = explode(":", $endtimew, 3);
      $wgameseconds = ((int)$hours2a * 3600) + ((int)$mins2a * 60) + (int)$seconds2a;

      list($hours2b, $mins2b, $seconds2b) = explode(":", $endtimeb, 3);
      $bgameseconds = ((int)$hours2b * 3600) + ((int)$mins2b * 60) + (int)$seconds2b;

      list($hours3, $mins3, $seconds3) = explode(":", $blacktime, 3);
      $playersecondsb = ((int)$hours3 * 3600) + ((int)$mins3 * 60) + (int)$seconds3;
      $bmovecnt = $this->GetPlayerMoveCount($gid, $b_player_id);

      //Get the time control information
      $moves1 = mysql_result($return2,0,"moves1");
      $time1 = mysql_result($return2,0,"time1");
      $moves2 = mysql_result($return2,0,"moves2");
      $time2 = mysql_result($return2,0,"time2");

      // update time control2 if values are specified and player meets the requirement
      if($moves2 != 0 && $time2 != 0){

        // Update to time control 2
        if($playersecondsw <= $wgameseconds && $wmovecnt >= $moves1){

          // white time control
          if($wtimectrl == 0){

            $wgameseconds = $wgameseconds + ($time2 * 60);

            $xsecs = 0;
            $xmins = 0;
            $xhours = 0;

            $i = 0;
            while($i < $wgameseconds){

              $xsecs++;

              if($xsecs == 60){
                $xsecs = 0;
                $xmins++;
              }

              if($xmins == 60){
                $xmins = 0;
                $xhours++;
              }

              $i++;
            }

            $xtime = "".trim($xhours)." : ".trim($xmins)." : ".trim($xsecs)."";
            $update = "UPDATE timed_game_stats SET endtimew='".$xtime."', wtimectrl='1' WHERE id='".$gid."'";
            mysql_query($update, $this->link) or die(mysql_error());

          }

        }

        if($playersecondsb <= $bgameseconds && $bmovecnt >= $moves1){

          // black time control
          if($btimectrl == 0){

            $bgameseconds = $bgameseconds + ($time2 * 60);

            $xsecs = 0;
            $xmins = 0;
            $xhours = 0;

            $i = 0;
            while($i < $bgameseconds){

              $xsecs++;

              if($xsecs == 60){
                $xsecs = 0;
                $xmins++;
              }

              if($xmins == 60){
                $xmins = 0;
                $xhours++;
              }

              $i++;
            }

            $xtime = "".trim($xhours)." : ".trim($xmins)." : ".trim($xsecs)."";
            $update = "UPDATE timed_game_stats SET endtimeb='".$xtime."', btimectrl='1' WHERE id='".$gid."'";
            mysql_query($update, $this->link) or die(mysql_error());

          }

        }

      }

      // Check if the players have reached their timeout time
      if($playersecondsw >= $wgameseconds){

        // White player time reached

        if($o_rating == "grated"){

          ///////////////////////////////////////////////////////////////////////
          //ELO Point Calculation
          if($this->ELOIsActive()){
            $bcurpoints = $this->ELOGetRating($b_player_id);
            $wcurpoints = $this->ELOGetRating($w_player_id);

            //Calculate black player
            $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

            //Calculate white player
            $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

            //update points
            $this->ELOUpdateRating($b_player_id, $bnewpoints);
            $this->ELOUpdateRating($w_player_id, $wnewpoints);

          }
          ///////////////////////////////////////////////////////////////////////

        }

        $this->UpdateGameStatus($config, $gid, "C", "B");

      }elseif($playersecondsb >= $bgameseconds){

        // black player time reached

        if($o_rating == "grated"){

          ///////////////////////////////////////////////////////////////////////
          //ELO Point Calculation
          if($this->ELOIsActive()){
            $bcurpoints = $this->ELOGetRating($b_player_id);
            $wcurpoints = $this->ELOGetRating($w_player_id);

            //Calculate black player
            $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

            //Calculate white player
            $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

            //update points
            $this->ELOUpdateRating($b_player_id, $bnewpoints);
            $this->ELOUpdateRating($w_player_id, $wnewpoints);

          }
          ///////////////////////////////////////////////////////////////////////

        }

        $this->UpdateGameStatus($config, $gid, "C", "W");

        $this->CachePlayerPointsByPlayerID($b_player_id);
        $this->CachePlayerPointsByPlayerID($w_player_id);

      }

    }

  }


  /**********************************************************************
  * CreatePlayerTimeIfNotEXists
  *
  */
  function CreatePlayerTimeIfNotEXists($gid, $isblack){

    $query1 = "SELECT * FROM timed_game_stats WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

    }else{

      //Get Game Stats
      $query2 = "SELECT * FROM timed_games WHERE id='".$gid."'";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num2 != 0){

        $time1 = mysql_result($return2,0,"time1");

        $sec1 = 0;
        $min1 = (int)$time1;
        $hour1 = 0;

        $tmpmin = $min1/60;

        //list($hours, $mins) = split('.', (string)$tmpmin);
        $hour1 = (int)$tmpmin;
        $min1 = (($min1/60) - $hour1)*60;

        //Total Time White
        $whitett = "".trim($hour1)." : ".trim($min1)." : ".trim($sec1)."";

        //Total Time Black
        $Blacktt = "".trim($hour1)." : ".trim($min1)." : ".trim($sec1)."";

        $insert = "INSERT INTO timed_game_stats VALUES('".$gid."', '".$whitett."', '".$Blacktt."', '00: 00 : 00', '00: 00 : 00', ".time().", ".strtotime("+".$time1." minute", time()).", 0, 0)";
        mysql_query($insert, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * GetGameTypeCode
  *
  */
  function GetGameTypeCode($gid){

    $gamecode = 1;

    //Check if it's a realtime game
    $query1 = "SELECT * FROM cfm_gamesrealtime WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $gamecode = 2;
    }

    //Check if it's a timed game
    $query2 = "SELECT * FROM timed_games WHERE id='".$gid."'";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $gamecode = 3;
    }

    return $gamecode;

  }


  /**********************************************************************
  * CastlingCheckMoves
  *
  */
  function CastlingCheckMoves($ConfigFile, $GameID){

    // Set up the move table
    $aTags = array();
    $aPGNMoves = array();

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $PGN = $oChess->get_move_history_list($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $this->ParsePGNMove($PGN, $aTags, $aPGNMoves);

    $Player = "";
    $color = "";

    $this->GetFirstMovePlayer($ConfigFile, $GameID, $Player, $color);

    if(isset($aPGNMoves)){

       $PGNMoveList= explode(" ", $aPGNMoves[0]);
       $itemcount = count($PGNMoveList);

       $rowswitch = 0;
       $rowstart = true;
       $itemcounter = 0;

       $mod = $itemcount % 3;

       $idcount = 0;
       $idswitch = 0;

       $aMoves1 = array();
       $aMoves2 = array();

       foreach($PGNMoveList as $item1){

         $itemcounter++;
         $idswitch++;

         if($rowswitch == 3){
           //echo "</tr>\n<tr>";
           $rowswitch = 0;
         }

         if($idswitch > 3){
           $idswitch = 1;
         }

         if($mod != 0 && $itemcounter == $itemcount){
           //echo "<br>$item1";
         }else{

           if($idswitch == 2 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
             $idcount++;
             array_push($aMoves1, $item1);

           }else{
             if($idswitch == 3 && $item1 != "*" && $item1 != "0-1" && $item1 !="1-0" && $item1 != "1/2-1/2"){
               $idcount++;
               array_push($aMoves2, $item1);

             }else{

               //echo "<br>".$item1;
             }

           }

         }

         $rowswitch++;

       }

    }

    ////////////////////////////////////////////////////////////////////////////////
    //check the moves
    ////////////////////////////////////////////////////////////////////////////////
    $nmov1cnt = count($aMoves1);
    $nmov2cnt = count($aMoves2);

    $nmove1ii = 0;
    $nmove2ii = 0;

    $bGood1 = 1;
    $bGood2 = 1;

    // Check white moves
    while($nmove1ii < $nmov1cnt){

      //Check if the king have made a move
      $pos = strrpos($aMoves1[$nmove1ii], "K");
      if($pos === false){
        //do nothing
      }else{
        $bGood1 = 0;
      }

      //Check if castling has been done before
      $pos = strrpos($aMoves1[$nmove1ii], "O-O-O");
      if($pos === false){
        //do nothing
      }else{
        $bGood1 = 0;
      }

      $pos = strrpos($aMoves1[$nmove1ii], "O-O");
      if($pos === false){
        //do nothing
      }else{
        $bGood1 = 0;
      }

      $nmove1ii++;
    }

    //check black moves
    while($nmove2ii < $nmov2cnt){

      //Check if the king have made a move
      $pos = strrpos($aMoves2[$nmove2ii], "K");
      if($pos === false){
        //do nothing
      }else{
        $bGood2 = 0;
      }

      //Check if castling has been done before
      $pos = strrpos($aMoves2[$nmove2ii], "O-O-O");
      if($pos === false){
        //do nothing
      }else{
        $bGood2 = 0;
      }

      $pos = strrpos($aMoves2[$nmove2ii], "O-O");
      if($pos === false){
        //do nothing
      }else{
        $bGood2 = 0;
      }

      $nmove2ii++;
    }

    return array($bGood1, $bGood2);

  }


  /**********************************************************************
  * GetInitialGameFEN
  *
  */
  function GetInitialGameFEN($ConfigFile, $GameID){

    $query1 = "SELECT * FROM c4m_newgameotherfen WHERE gameid='".$GameID."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $FEN = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";

    if($num1 != 0){
      $FEN = mysql_result($return1,0,"fen");

      $returncode = $FEN;

      // Format/Decode the FEN
      list($one, $two, $three, $four, $five, $six, $seven, $eight) = explode("/", str_replace(strrchr($returncode, "/"), "", $returncode), 8);

      //Get the rest of the fen string
      list($j1, $j2, $j3, $j4, $j5, $j6, $j7) = explode(" ", $returncode, 7);

      $j2 = "w";

      if($j6 == "0"){
        $j6 = "1";
      }

      $backfen = trim($j2." ".$j3." ".$j4." ".$j5." ".$j6." ".$j7);

      //$backfen = "w KQkq - 0 1";

      //convert the fen
      $one = $this->ConvertFenRow($one);
      $two= $this->ConvertFenRow($two);
      $three= $this->ConvertFenRow($three);
      $four= $this->ConvertFenRow($four);
      $five= $this->ConvertFenRow($five);
      $six= $this->ConvertFenRow($six);
      $seven= $this->ConvertFenRow($seven);
      $eight= $this->ConvertFenRow($eight);

      //$FEN = $eight."/".$seven."/".$six."/".$five."/".$four."/".$three."/".$two."/".$one." ".$backfen;

    }

    return $FEN;

  }


  /**********************************************************************
  * SetServerEmailSettings
  *
  */
  function SetServerEmailSettings($smtp, $port, $user, $pass, $domain){

    $query1 = "SELECT * FROM server_email_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $update1 = "UPDATE server_email_settings SET o_smtp='".$smtp."', o_smtp_port='".$port."' WHERE o_id='1'";
      mysql_query($update1, $this->link) or die(mysql_error());

    }else{

      $insert1 = "INSERT INTO server_email_settings VALUES('1', '".$smtp."', '".$port."')";
      mysql_query($insert1, $this->link) or die(mysql_error());

    }

    $query1 = "SELECT * FROM smtp_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $update1 = "UPDATE smtp_settings SET o_user='".$user."', o_pass='".$pass."', o_domain='".$domain."' WHERE o_id='1'";
      mysql_query($update1, $this->link) or die(mysql_error());

    }else{

      $insert1 = "INSERT INTO smtp_settings VALUES('1', '".$user."', '".$pass."', '".$domain."')";
      mysql_query($insert1, $this->link) or die(mysql_error());

    }


  }


  /**********************************************************************
  * GetServerEmailSettings
  *
  */
  function GetServerEmailSettings(&$smtp, &$port, &$user, &$pass, &$domain){

    $smtp = "";
    $port = "";

    $query1 = "SELECT * FROM server_email_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $smtp = mysql_result($return1,0,"o_smtp");
      $port = mysql_result($return1,0,"o_smtp_port");
    }

    $user = "";
    $pass = "";
    $domain = "";

    $query1 = "SELECT * FROM smtp_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $user = mysql_result($return1,0,"o_user");
      $pass = mysql_result($return1,0,"o_pass");
      $domain = mysql_result($return1,0,"o_domain");
    }

  }


  /**********************************************************************
  * GetServerVersion
  *
  */
  function GetServerVersion(){

    $version = "0.0.0";

    $query1 = "SELECT * FROM server_version WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $major = mysql_result($return1,0,"o_major");
      $minor = mysql_result($return1,0,"o_minor");
      $build = mysql_result($return1,0,"o_build");

      $version = $major.".".$minor.".".$build;

    }

    return $version;

  }


  /**********************************************************************
  * GetPreCreatedGameSelectBox
  *
  */
  function GetPreCreatedGameSelectBox(){

    $query = "SELECT * FROM cfm_creategamefen";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select name='slc_precreate'>";
    echo "<option VALUE='0'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_144")."</option>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $o_id = trim(mysql_result($return,$i,"o_id"));
        $o_fen = trim(mysql_result($return,$i,"o_fen"));

        echo "<option VALUE='".$o_id."'>".$o_fen."</option>";

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * GetOnlinePlayerList
  *
  */
  function GetOnlinePlayerList(){

    $query = "SELECT * FROM active_sessions";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){
        $player_id = trim(mysql_result($return,$i,"player_id"));

        $this->GetPlayerInfoByID2($player_id, $userid, $password, $email);
        echo "<a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a>";

        $i++;

        if($i < $num){
          echo ", ";
        }

      }

    }else{
      echo $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_145");
    }

  }


  /**********************************************************************
  * MangeGameTimeOuts
  *
  */
  function MangeGameTimeOuts(){

    // Select all games that are not finished.
	// ? What about games that have not been accepted ?
    $query = <<<qq
SELECT game.* , cfm_game_options.o_rating, cfm_game_options.o_timetype, cfm_game_options.time_mode, timed_games.*
FROM game
LEFT JOIN cfm_game_options ON game.game_id = cfm_game_options.o_gameid
LEFT JOIN timed_games ON game.game_id = timed_games.id
WHERE game.status = 'A' AND completion_status = 'I'
qq;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num == 0) return;

	// Get the timout settings for the game type (in days)

	$this->GetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode);
    
	$now = time();
	$i = 0;
	while($i < $num)
	{

        $game_id = trim(mysql_result($return,$i,"game_id"));
        $o_rating = trim(mysql_result($return,$i,"o_rating"));
        $o_timetype = trim(mysql_result($return,$i,"o_timetype"));
        $time_mode = trim(mysql_result($return,$i,"time_mode"));

		$m1 = (int)@mysql_result($return, $i, 'moves1');
		$m2 = (int)@mysql_result($return, $i, 'moves2');
		$t1 = (int)@mysql_result($return, $i, 'times1');
		$t2 = (int)@mysql_result($return, $i, 'times2');

		$nTimeCheck = 0;
        switch($o_timetype){

          case "C-Blitz":
            $nTimeCheck = $CBlitz;
            break;

          case "C-Short":
            $nTimeCheck = $CShort;
            break;

          case "C-Normal":
            $nTimeCheck = $CNormal;
            break;

          case "C-Slow":
            $nTimeCheck = $CSlow;
            break;

          case "C-Snail":
            $nTimeCheck = $CSnail;
            break;

        }
		$nTimeCheck = $nTimeCheck * 86400;	// Convert the day values into seconds.


          $start_time = trim(mysql_result($return, $i, "start_time"));
          $w_player_id = trim(mysql_result($return, $i, "w_player_id"));
          $b_player_id = trim(mysql_result($return, $i, "b_player_id"));
          $next_move = trim(mysql_result($return, $i, "next_move"));

          // // Check if there is a move that we can reference our time to.
          // $query2 = "SELECT * FROM move_history WHERE game_id = '".$game_id."' ORDER BY move_id DESC";
          // $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          // $num2 = mysql_numrows($return2);

          // // defaut the time to the games start time
          // $move_time = $start_time;

          // // set the time to the last move if there is one
          // if($num2 != 0){
            // $move_time = mysql_result($return2,0,"time");
          // }

          // // check if the game has timed out
          // $time1 = strtotime("+".$nTimeCheck." day", $move_time);
          // $tnow1 = time();

          // //echo $game_id." - ".$tnow1." >= ".$time1."<br>";

		$timed_out = FALSE;

		if($time_mode == 1)
		{
			// Get the time of the last move. Get the time already used up by the player.
			// Then work out if the used time + the elapsed time since the last move is
			// greater than the allowed time. If that is the case, the game has timed out.
			$query = "SELECT time FROM move_history WHERE game_id = '$game_id' ORDER BY time DESC LIMIT 1";
			$return2 = mysql_query($query) or die(mysql_error());
			$cnt = mysql_numrows($return2);
			$last_move_time = ($cnt > 0 ? (int)mysql_result($return2, 0, 'time') : (int)$start_time);
			$player_time_recorded = ($next_move == 'w' ? (int)mysql_result($return, $i, 'w_time_used') : (int)mysql_result($return, $i, 'b_time_used'));
			$player_time_elapsed = $now - $last_move_time + $player_time_recorded;
			//echo "$now - $last_move_time = $player_time_elapsed for ".($nTimeCheck * 86400)."<br/>";
			if($player_time_elapsed > $nTimeCheck)
			{
				$timed_out = TRUE;
				//echo "is timed out";
			}
		}
		else
		{
			$expires_at = $start_time + $nTimeCheck;
			if($now >= $expires_at)
				$timed_out = TRUE;
		}
		  
		  //$i++;
		  //continue;
		if($timed_out){
            //echo "^timedout<br>";

            if($next_move == "w" || $next_move == ""){

              // white lost

              if($o_rating == "grated"){

                ///////////////////////////////////////////////////////////////////////
                //ELO Point Calculation
                if($this->ELOIsActive()){
                  $bcurpoints = $this->ELOGetRating($b_player_id);
                  $wcurpoints = $this->ELOGetRating($w_player_id);

                  //Calculate black player
                  $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                  //Calculate white player
                  $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                  //update points
                  $this->ELOUpdateRating($b_player_id, $bnewpoints);
                  $this->ELOUpdateRating($w_player_id, $wnewpoints);
                }
                ///////////////////////////////////////////////////////////////////////

              }

              $this->UpdateGameStatus($this->ChessCFGFileLocation, $game_id, "C", "B");

            }else{

              // black lossed

              if($o_rating == "grated"){

                ///////////////////////////////////////////////////////////////////////
                //ELO Point Calculation
                if($this->ELOIsActive()){
                  $bcurpoints = $this->ELOGetRating($b_player_id);
                  $wcurpoints = $this->ELOGetRating($w_player_id);

                  //Calculate black player
                  $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                  //Calculate white player
                  $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                  //update points
                  $this->ELOUpdateRating($b_player_id, $bnewpoints);
                  $this->ELOUpdateRating($w_player_id, $wnewpoints);

                }
                ///////////////////////////////////////////////////////////////////////

              }

              $this->UpdateGameStatus($this->ChessCFGFileLocation, $game_id, "C", "W");

            }

            // // Update game option list
            // $update = "UPDATE cfm_game_options SET o_timetype = '-' WHERE o_gameid='".$game_id."'";
            // mysql_query($update, $this->link) or die(mysql_error());

            //////////////////////////////////////////////////////////////////////////
            // Send email
            //////////////////////////////////////////////////////////////////////////

            $subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_25");

            $aTags = array("['gameid']", "['sitename']", "['siteurl']");
            $aReplaceTags = array($game_id, $this->SiteName, $this->TrimRSlash($this->SiteURL));

            $body = str_replace($aTags, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_26"));

            if($this->ChallangeNotification($w_player_id)){
              $this->SendEmail($this->GetEmailByPlayerID($ConfigFile, $w_player_id), $this->RegistrationEmail, $this->SiteName, $subject, $body);
            }

            if($this->ChallangeNotification($b_player_id)){
              $this->SendEmail($this->GetEmailByPlayerID($ConfigFile, $b_player_id), $this->RegistrationEmail, $this->SiteName, $subject, $body);
            }

            //////////////////////////////////////////////////////////////////////////

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

        

        $i++;

      }


  }

  /**********************************************************************
  * MangeGameTimeOuts
  *
  */
  function MangeGameTimeOutsOLD(){

    // Select all games that are not timed out
    $query = "SELECT * FROM cfm_game_options WHERE o_timetype != '-'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Check all the games to see if they have timedout
      $i = 0;
      while($i < $num)
	  {

        $o_gameid = trim(mysql_result($return,$i,"o_gameid"));
        $o_rating = trim(mysql_result($return,$i,"o_rating"));
        $o_timetype = trim(mysql_result($return,$i,"o_timetype"));

        // Get the timout settings for the game type (in days)
        $nTimeCheck = 0;
        $this->GetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode);


        switch($o_timetype){

          case "C-Blitz":
            $nTimeCheck = $CBlitz;
            break;

          case "C-Short":
            $nTimeCheck = $CShort;
            break;

          case "C-Normal":
            $nTimeCheck = $CNormal;
            break;

          case "C-Slow":
            $nTimeCheck = $CSlow;
            break;

          case "C-Snail":
            $nTimeCheck = $CSnail;
            break;

        }

        // get the game information
        $query1 = "SELECT * FROM game WHERE game_id='".$o_gameid."' AND status='A' AND completion_status='I'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $start_time = trim(mysql_result($return1,0,"start_time"));
          $w_player_id = trim(mysql_result($return1,0,"w_player_id"));
          $b_player_id = trim(mysql_result($return1,0,"b_player_id"));
          $next_move = trim(mysql_result($return1,0,"next_move"));

          // // Check if there is a move that we can reference our time to.
          // $query2 = "SELECT * FROM move_history WHERE game_id = '".$o_gameid."' ORDER BY move_id DESC";
          // $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          // $num2 = mysql_numrows($return2);

          // // defaut the time to the games start time
          // $move_time = $start_time;

          // // set the time to the last move if there is one
          // if($num2 != 0){
            // $move_time = mysql_result($return2,0,"time");
          // }

          // // check if the game has timed out
          // $time1 = strtotime("+".$nTimeCheck." day", $move_time);
          // $tnow1 = time();

          // //echo $o_gameid." - ".$tnow1." >= ".$time1."<br>";

		  $tnow1 = time();
		  $expires_at = $start_time + $nTimeCheck * 86400;
		  //$i++;
		  //continue;
          if($tnow1 >= $expires_at){
            //echo "^timedout<br>";

            if($next_move == "w" || $next_move == ""){

              // white lossed

              if($o_rating == "grated"){

                ///////////////////////////////////////////////////////////////////////
                //ELO Point Calculation
                if($this->ELOIsActive()){
                  $bcurpoints = $this->ELOGetRating($b_player_id);
                  $wcurpoints = $this->ELOGetRating($w_player_id);

                  //Calculate black player
                  $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 1, 1, $this->GetPlayerGameCount($b_player_id));

                  //Calculate white player
                  $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 0, 1, $this->GetPlayerGameCount($w_player_id));

                  //update points
                  $this->ELOUpdateRating($b_player_id, $bnewpoints);
                  $this->ELOUpdateRating($w_player_id, $wnewpoints);
                }
                ///////////////////////////////////////////////////////////////////////

              }

              $this->UpdateGameStatus($this->ChessCFGFileLocation, $o_gameid, "C", "B");

            }else{

              // black lossed

              if($o_rating == "grated"){

                ///////////////////////////////////////////////////////////////////////
                //ELO Point Calculation
                if($this->ELOIsActive()){
                  $bcurpoints = $this->ELOGetRating($b_player_id);
                  $wcurpoints = $this->ELOGetRating($w_player_id);

                  //Calculate black player
                  $bnewpoints = $this->ELOCalculation($bcurpoints, $wcurpoints, 0, 1, $this->GetPlayerGameCount($b_player_id));

                  //Calculate white player
                  $wnewpoints = $this->ELOCalculation($wcurpoints, $bcurpoints, 1, 1, $this->GetPlayerGameCount($w_player_id));

                  //update points
                  $this->ELOUpdateRating($b_player_id, $bnewpoints);
                  $this->ELOUpdateRating($w_player_id, $wnewpoints);

                }
                ///////////////////////////////////////////////////////////////////////

              }

              $this->UpdateGameStatus($this->ChessCFGFileLocation, $o_gameid, "C", "W");

            }

            // Update game option list
            $update = "UPDATE cfm_game_options SET o_timetype = '-' WHERE o_gameid='".$o_gameid."'";
            mysql_query($update, $this->link) or die(mysql_error());

            //////////////////////////////////////////////////////////////////////////
            // Send email
            //////////////////////////////////////////////////////////////////////////

            $subject = $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_25");

            $aTags = array("['gameid']", "['sitename']", "['siteurl']");
            $aReplaceTags = array($o_gameid, $this->SiteName, $this->TrimRSlash($this->SiteURL));

            $body = str_replace($aTags, $aReplaceTags, $this->GetStringFromStringTable("IDS_CR3DCQUERY_EMAIL_TVST_26"));

            if($this->ChallangeNotification($w_player_id)){
              $this->SendEmail($this->GetEmailByPlayerID($ConfigFile, $w_player_id), $this->RegistrationEmail, $this->SiteName, $subject, $body);
            }

            if($this->ChallangeNotification($b_player_id)){
              $this->SendEmail($this->GetEmailByPlayerID($ConfigFile, $b_player_id), $this->RegistrationEmail, $this->SiteName, $subject, $body);
            }

            //////////////////////////////////////////////////////////////////////////

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * IsGameRated
  *
  */
  function IsGameRated($GameID){

    $bRated = true;

    $query = "SELECT * FROM cfm_game_options WHERE o_gameid = '".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_rating = trim(mysql_result($return,0,"o_rating"));

      if($o_rating == "gunrated"){
        $bRated = false;
      }

    }

    return $bRated;

  }


  /**********************************************************************
  * GetServerGameOptions
  *
  */
  function GetServerGameOptions(&$CSnail, &$CSlow, &$CNormal, &$CShort, &$CBlitz, &$timing_mode){

    $query = "SELECT * FROM admin_game_options WHERE o_id = 1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $CSnail = trim(mysql_result($return,0,"o_snail"));
      $CSlow = trim(mysql_result($return,0,"o_slow"));
      $CNormal = trim(mysql_result($return,0,"o_normal"));
      $CShort = trim(mysql_result($return,0,"o_short"));
      $CBlitz = trim(mysql_result($return,0,"o_blitz"));
	  $timing_mode = (int)trim(mysql_result($return, 0, 'timing_mode'));
    }

  }


  /**********************************************************************
  * SetServerGameOptions
  *
  */
  function SetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode){

    $update = "UPDATE admin_game_options SET o_snail = ".$CSnail.", o_slow = ".$CSlow.", o_normal = ".$CNormal.", o_short = ".$CShort.", o_blitz = ".$CBlitz.", timing_mode = $timing_mode WHERE o_id = 1";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetGamePlayTypeInfo
  *
  */
  function GetGamePlayTypeInfo($GameID){

    $gametypecode = $this->GetGameTypeCode($GameID);

    $txtDescription = "";

    if($gametypecode == 2){
      $txtDescription = "Real-Time Passive";
    }elseif($gametypecode == 3){
      $txtDescription = "Real-Time Active";
    }else{
      $txtDescription = "Normal";
    }

    echo "<table width='210'>";
    echo "<tr>";
    echo "<td><b>Game Type:</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>".$txtDescription."</td>";
    echo "</tr>";
    echo "</table>";

    $query = "SELECT * FROM cfm_game_options WHERE o_gameid = '".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bRated = true;

    if($num != 0){

      $o_rating = trim(mysql_result($return,0,"o_rating"));
      $o_timetype = trim(mysql_result($return,0,"o_timetype"));

      if($o_rating == "gunrated"){
        $bRated = false;
      }

      echo "<table width='210'>";
      echo "<tr>";
      echo "<td><b>Game Rating Option:</b></td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td>";

      if($bRated){
        echo "Rated";
      }else{
        echo "Not Rated";
      }

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      $txtType = "-";

      switch($o_timetype){

        case "C-Blitz":
          $txtType = "Blitz";
          break;

        case "C-Short":
          $txtType = "Short";
          break;

        case "C-Normal":
          $txtType = "Normal";
          break;

        case "C-Slow":
          $txtType = "Slow";
          break;

        case "C-Snail":
          $txtType = "Snail";
          break;

      }

      if($txtType != "-"){
        echo "<table width='210'>";
        echo "<tr>";
        echo "<td><b>Game Timeout Options:</b></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>".$txtType."</td>";
        echo "</tr>";
        echo "</table>";
      }
    }

  }


  /**********************************************************************
  * CheckChessClubExists
  *
  */
  function CheckChessClubExists($ClubName){

    $query = "SELECT * FROM chess_club WHERE o_clubname = '".$ClubName."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bExists = false;

    if($num != 0){
      $bExists = true;
    }

    return $bExists;

  }


  /**********************************************************************
  * CreateChessClub
  *
  */
  function CreateChessClub($ClubName, $PlayerID){

    // Create the club
    $insert = "INSERT INTO chess_club VALUES(NULL, '".$ClubName."', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

       $clubid = trim(mysql_result($return,0,0));

       // Add the creater to the members list and set him/her as the owner
       $insert = "INSERT INTO chess_club_members VALUES(NULL, '".$clubid."', '".$PlayerID."', 'y', 'y')";
       mysql_query($insert, $this->link) or die(mysql_error());

       // Create the chess club page record
       $insert = "INSERT INTO chess_club_page VALUES(NULL, '".$clubid."', '')";
       mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * IsUserInClub
  *
  */
  function IsUserInClub($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bInClub = false;

    if($num != 0){
      $bInClub = true;
    }

    return $bInClub;

  }


  /**********************************************************************
  * GetChessClubSelectBox
  *
  */
  function GetChessClubSelectBox(){

    $query = "SELECT * FROM chess_club ORDER By o_clubname ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select NAME='lstclublist' size='15' style='width:540'>";

    if($num != 0){

      $i = 0;
      While($i < $num){

        $o_id = trim(mysql_result($return,$i,"o_id"));
        $o_clubname = trim(mysql_result($return,$i,"o_clubname"));

        echo "<option VALUE='".$o_id."'>[".($i+1)."] ".$o_clubname."</option>";

        $i++;
      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * JoinChessClub
  *
  */
  function JoinChessClub($ClubID, $PlayerID){

    // Add the player to the members list
    $insert = "INSERT INTO chess_club_members VALUES(NULL, '".$ClubID."', '".$PlayerID."', 'n', 'n')";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * IsUserApplicationPending
  *
  */
  function IsUserApplicationPending($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bAppPending = true;

    if($num != 0){

      $o_active = mysql_result($return,0,"o_active");

      if($o_active == 'y'){
        $bAppPending = false;
      }

    }

    return $bAppPending;

  }


  /**********************************************************************
  * LeaveClub
  *
  */
  function LeaveClub($PlayerID){

    $delete = "DELETE FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    mysql_query($delete, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * IsUserClubLeader
  *
  */
  function IsUserClubLeader($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bOwner = false;

    if($num != 0){

      $o_owner = mysql_result($return,0,"o_owner");

      if($o_owner == 'y'){
        $bOwner = true;
      }

    }

    return $bOwner;

  }


  /**********************************************************************
  * GetClubMemberlistAdmin
  *
  */
  function GetClubMemberlistAdmin($ClubLeaderID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$ClubLeaderID."' AND o_owner='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_chessclubid = mysql_result($return,0,"o_chessclubid");

      $query1 = "SELECT * FROM chess_club_members WHERE o_chessclubid = '".$o_chessclubid."'";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";

        echo "<tr>";
        echo "<td class='tableheadercolor' colspan='3'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_TXT_1")."</font></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1' width='55%'>".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_TXT_2")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_TXT_3")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_TXT_4")."</td>";
        echo "</tr>";

        $i=0;
        while($i < $num1){

          $o_playerid = mysql_result($return1,$i,"o_playerid");
          $o_owner = mysql_result($return1,$i,"o_owner");
          $o_active = mysql_result($return1,$i,"o_active");

          if($o_owner != 'y'){

            echo "<tr>";
            echo "<td class='row2'>".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid)."</td>";

            if($o_active == 'y'){
              echo "<td class='row2'>".$this->GetStringFromStringTable("IDS_ADMINTS_SELECT_YES")."</td>";
            }else{
              echo "<td class='row2'>".$this->GetStringFromStringTable("IDS_ADMINTS_SELECT_NO")."</td>";
            }
            echo "<td class='row2'>";

            if($o_active == 'n'){
              echo "<input type='button' name='cmdActivateMem' value='".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_BTN_1")."' class='mainoption' onclick=\"javascript:MemOpts(1,".$o_playerid.");\">";
            }

            echo "<input type='button' name='cmdRemoveMem' value='".$this->GetStringFromStringTable("IDS_CHESS_CFG_CLUB_MEMBERS_BTN_2")."' class='mainoption' onclick=\"javascript:MemOpts(2,".$o_playerid.");\">";

            echo "</td>";
            echo "</tr>";

          }

          $i++;
        }

        echo "</table>";

      }

    }

  }


  /**********************************************************************
  * ActivateClubMember
  *
  */
  function ActivateClubMember($PlayerID){

    $update = "UPDATE chess_club_members SET o_active='y' WHERE o_playerid = '".$PlayerID."'";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * DisbandClub
  *
  */
  function DisbandClub($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_chessclubid = mysql_result($return,0,"o_chessclubid");

      if($this->IsUserClubLeader($PlayerID)){

        // Delete all the current members
        $delete = "DELETE FROM chess_club_members WHERE o_chessclubid = '".$o_chessclubid."'";
        mysql_query($delete, $this->link) or die(mysql_error());

        // Delete club page
        $delete = "DELETE FROM chess_club_page WHERE o_chessclubid = '".$o_chessclubid."'";
        mysql_query($delete, $this->link) or die(mysql_error());

        //Delete the club
        $delete = "DELETE FROM chess_club WHERE o_id = '".$o_chessclubid."'";
        mysql_query($delete, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * GetClubPageHTML
  *
  */
  function GetClubPageHTML($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $txtpagehtml = "";

    if($num != 0){

      $o_chessclubid = mysql_result($return,0,"o_chessclubid");

      if($this->IsUserClubLeader($PlayerID)){

        $query1 = "SELECT * FROM chess_club_page WHERE o_chessclubid = '".$o_chessclubid."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){
          $txtpagehtml = mysql_result($return1,0,"o_pagehtml");
        }

      }

    }

    return stripslashes($txtpagehtml);

  }


  /**********************************************************************
  * UpdateClubPageHTML
  *
  */
  function UpdateClubPageHTML($PlayerID, $HTML){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_chessclubid = mysql_result($return,0,"o_chessclubid");

      if($this->IsUserClubLeader($PlayerID)){

        $update = "UPDATE chess_club_page SET o_pagehtml='".addslashes($HTML)."' WHERE o_chessclubid = '".$o_chessclubid."'";
        mysql_query($update, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * GetClubPageHTMLPlayer
  *
  */
  function GetClubPageHTMLPlayer($ClubID){

    $query1 = "SELECT * FROM chess_club_page WHERE o_chessclubid = '".$ClubID."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $txtpagehtml = "";

    if($num1 != 0){
      $txtpagehtml = mysql_result($return1,0,"o_pagehtml");
    }

    return stripslashes($txtpagehtml);

  }


  /**********************************************************************
  * GetClubNameById
  *
  */
  function GetClubNameById($ClubID){

    $query = "SELECT * FROM chess_club WHERE o_id = '".$ClubID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $clubname = "";

    if($num != 0){
      $clubname = mysql_result($return,0,"o_clubname");
    }

    return $clubname;

  }


  /**********************************************************************
  * ChessClubPageMenu
  *
  */
  function ChessClubPageMenu($PlayerID){

    $query = "SELECT * FROM chess_club_members WHERE o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_chessclubid = mysql_result($return,0,"o_chessclubid");
      $clubname = $this->GetClubNameById($o_chessclubid);

      echo "<center>";
      echo "<a href='./chess_club_page.php?clubid=".$o_chessclubid."'><img src='./skins/".$this->SkinsLocation."/images/clubpage.gif' border='0'></a>";
      echo "<br>";
      echo $clubname;
      echo "</center>";

    }

  }


  /**********************************************************************
  * GetCurrentGamesByPlayerIDForMobile
  *
  */
  function GetCurrentGamesByPlayerIDForMobile($ConfigFile, $ID){

    //Get game where the player is white
    $queryw = "SELECT * FROM game WHERE w_player_id = ".$ID." AND completion_status IN('A','I')";
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    // //get all the games from the c4m_tournamentgames table
    // $queryt = "SELECT tg_gameid FROM c4m_tournamentgames";
    // $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    // $numt = mysql_numrows($returnt);

    if($numw != 0){

      $i = 0;
      while($i < $numw){

        $game_id = trim(mysql_result($returnw,$i,"game_id"));
        $initiator = trim(mysql_result($returnw,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnw,$i,"next_move"));
        $status = trim(mysql_result($returnw,$i,"status"));
        $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

        $start_time = trim(mysql_result($returnw,$i,"start_time"));

        //Check if the game is a tournament game
        // $ti = 0;
        // $bexit = false;
        // while($ti < $numt && $bexit == false){

          // $TGID = mysql_result($returnt, $ti, 0);

          // if($TGID == $game_id){
            // $bexit = true;
          // }

          // $ti++;
        // }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "W" || $next_move == "w"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

          echo "<DESCRIPTION>";

          echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
			echo " VS ";
            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

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
		  echo $this->GetFEN3('', $game_id);
		  echo "</GAMEFEN>\n";

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

    //Get game where the player is black
    $queryb = "SELECT * FROM game WHERE b_player_id = ".$ID." AND completion_status IN('A','I')";
    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    if($numb != 0){

      $i = 0;
      while($i < $numb){

        $game_id = trim(mysql_result($returnb,$i,"game_id"));
        $initiator = trim(mysql_result($returnb,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnb,$i,"next_move"));
        $start_time = trim(mysql_result($returnb,$i,"start_time"));
        $status = trim(mysql_result($returnb,$i,"status"));
        $completion_status = trim(mysql_result($returnb,$i,"completion_status"));

        //Check if the game is a tournament game
        // $ti = 0;
        // $bexit = false;
        // while($ti < $numt && $bexit == false){

          // $TGID = mysql_result($returnt, $ti, 0);

          // if($TGID == $game_id){
            // $bexit = true;
          // }

          // $ti++;
        // }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "w" || $next_move == "w"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

          echo "<DESCRIPTION>";

          echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
			echo " VS ";
            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

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
		  echo $this->GetFEN3('', $game_id);
		  echo "</GAMEFEN>\n";

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

  }

  /**********************************************************************
  * GetCurrentGamesByPlayerIDForMobile2
  * Returns only games which this player is taking part in and challenges this 
  * player has made.
  */
  function GetCurrentGamesByPlayerIDForMobile2($ConfigFile, $ID){

    // Get all incomplete games where this player is taking part in, except for games where this player
	// was not the challenger (initiator).
    $query = <<<qq
SELECT * FROM
	`game`
WHERE
	(`w_player_id` = $ID OR `b_player_id` = $ID) AND 
	(`completion_status` = 'A' OR `completion_status` = 'I') AND
	(`status` = 'A' OR (`status` = 'W' AND initiator = $ID))
qq;
    $result = mysql_query($query, $this->link) or die(mysql_error());
    $result_cnt = mysql_numrows($result);
	
	$i = 0;
	while($i < $result_cnt)
	{
		$game_id = trim(mysql_result($result,$i,"game_id"));
        $initiator = trim(mysql_result($result,$i,"initiator"));
        $w_player_id = trim(mysql_result($result,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($result,$i,"b_player_id"));
        $next_move = trim(mysql_result($result,$i,"next_move"));
        $status = trim(mysql_result($result,$i,"status"));
        $completion_status = trim(mysql_result($result,$i,"completion_status"));
        $start_time = trim(mysql_result($result,$i,"start_time"));
		
		$playing_white = ($ID == $w_player_id ? TRUE : FALSE);
		
		echo "<GAMES>\n";

		echo "<STATUS>";

		if($status == 'W')
			echo "IDS_GAME_NOT_ACCEPTED";
		else // status is 'A' - active
		{
			if($next_move == "")
			{
				echo $playing_white ? 'IDS_PLAYER_TURN' : 'IDS_NOT_PLAYER_TURN';
			}
			else
			{
				if($next_move == 'W' || $next_move == 'w')
					echo $playing_white ? 'IDS_PLAYER_TURN' : 'IDS_NOT_PLAYER_TURN';
				elseif($next_move == 'B' || $next_move == 'b')
					echo $playing_white ? 'IDS_NOT_PLAYER_TURN' : 'IDS_PLAYER_TURN';
			}
		}
		// if($next_move == "W" || $next_move == "w"){
			// echo "IDS_PLAYER_TURN";
		// }
		// if($next_move == "B" || $next_move == "b"){
			// echo "IDS_NOT_PLAYER_TURN";
		// }
		// if($next_move == "" && $status == "W" && $completion_status == "I"){
			// echo "IDS_GAME_NOT_ACCEPTED";
		// }
		// if($next_move == "" && $status == "A" && $completion_status == "I"){
			// echo "IDS_PLAYER_TURN";
		// }

		echo "</STATUS>\n";

		echo "<COMPLETIONSTATUS>$completion_status</COMPLETIONSTATUS>\n";

		$gametypecode = $this->GetGameTypeCode($game_id);
		$strGameType = "GT_NORMAL_GAME";

		switch($gametypecode){

		case 2:
		  $strGameType = "GT_PASV_RT_GAME";
		  break;

		case 3:
		  $strGameType = "GT_ACTIVE_RT_GAME";
		  break;

		}

		echo "<GAMETYPE>$strGameType</GAMETYPE>\n";

		$this->GetGamePlayTypeInfoForMobile($game_id);
		$this->TimedGameStatsForMobile($game_id);

		echo "<TIMECREATED>$start_time</TIMECREATED>\n";

		echo "<DESCRIPTION>";
		echo $this->GetUserIDByPlayerID($ConfigFile, $w_player_id);
		echo " VS ";
		echo $this->GetUserIDByPlayerID($ConfigFile, $b_player_id);
		echo "</DESCRIPTION>\n";

		echo "<INITIATOR>$initiator</INITIATOR>\n";

		echo "<WHITE>$w_player_id</WHITE>\n";

		echo "<BLACK>$b_player_id</BLACK>\n";

		echo "<NEXTMOVE>$next_move</NEXTMOVE>\n";

		echo "<GAMEID>$game_id</GAMEID>\n";

		echo "<GAMEFEN>" . $this->GetFEN3('', $game_id) . "</GAMEFEN>\n";

		echo "</GAMES>\n";
		$i++;
	}
  }

  /**********************************************************************
  * GetCurrentOpenChallengeGamesForMobile
  *
  */
  function GetCurrentOpenChallengeGamesForMobile($ConfigFile, $ID){

    //Get game where the player is white
    $queryw = "SELECT * FROM game WHERE w_player_id = '0' AND completion_status = 'I'";
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    //get all the games from the c4m_tournamentgames table
    $queryt = "SELECT tg_gameid FROM c4m_tournamentgames";
    $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    $numt = mysql_numrows($returnt);

    if($numw != 0){

      $i = 0;
      while($i < $numw){

        $game_id = trim(mysql_result($returnw,$i,"game_id"));
        $initiator = trim(mysql_result($returnw,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnw,$i,"next_move"));
        $status = trim(mysql_result($returnw,$i,"status"));
        $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

        $start_time = trim(mysql_result($returnw,$i,"start_time"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "w" || $next_move == "w"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

		  echo "<DESCRIPTION>";
		  
          echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
			echo " VS ";
            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

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
		  echo $this->GetFEN3('', $game_id);
		  echo "</GAMEFEN>\n";

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

    //Get game where the player is black
    $queryb = "SELECT * FROM game WHERE b_player_id = '0' AND completion_status = 'I' ";
    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    if($numb != 0){

      $i = 0;
      while($i < $numb){

        $game_id = trim(mysql_result($returnb,$i,"game_id"));
        $initiator = trim(mysql_result($returnb,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnb,$i,"next_move"));
        $start_time = trim(mysql_result($returnb,$i,"start_time"));
        $status = trim(mysql_result($returnb,$i,"status"));
        $completion_status = trim(mysql_result($returnb,$i,"completion_status"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "w" || $next_move == "w"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

          echo "<DESCRIPTION>";

            echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
			echo " VS ";
            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

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
		  echo $this->GetFEN3('', $game_id);
		  echo "</GAMEFEN>\n";

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

  }

/**********************************************************************
  * GetCurrentGameChallengesByPlayerID
  * Returns only games which this player has been challenged. Can be direct or open challenges.
  */
  function GetCurrentGameChallengesByPlayerID($ConfigFile, $ID){

    // Get all incomplete games where this player is taking part in, except for games where this player
	// was not the challenger (initiator).
	$queryw = "SELECT * FROM game WHERE w_player_id = '0' AND completion_status = 'I'";
    $query = <<<qq
SELECT * FROM
	`game`
WHERE
	`completion_status` = 'I' AND `status` = 'W' AND
	(`w_player_id` = $ID OR `b_player_id` = $ID OR `w_player_id` = 0 OR `b_player_id` = 0) AND
	`initiator` <> $ID
qq;
    $result = mysql_query($query, $this->link) or die(mysql_error());
    $result_cnt = mysql_numrows($result);
	
	$i = 0;
	while($i < $result_cnt)
	{
		$game_id = trim(mysql_result($result,$i,"game_id"));
        $initiator = trim(mysql_result($result,$i,"initiator"));
        $w_player_id = trim(mysql_result($result,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($result,$i,"b_player_id"));
        $next_move = trim(mysql_result($result,$i,"next_move"));
        $status = trim(mysql_result($result,$i,"status"));
        $completion_status = trim(mysql_result($result,$i,"completion_status"));
        $start_time = trim(mysql_result($result,$i,"start_time"));
		
		$playing_white = ($ID == $w_player_id ? TRUE : FALSE);
		
		echo "<GAMES>\n";

		echo "<STATUS>";

		if($status == 'W')
			echo "IDS_GAME_NOT_ACCEPTED";
		else // status is 'A' - active
		{
			if($next_move == "")
			{
				echo $playing_white ? 'IDS_PLAYER_TURN' : 'IDS_NOT_PLAYER_TURN';
			}
			else
			{
				if($next_move == 'W' || $next_move == 'w')
					echo $playing_white ? 'IDS_PLAYER_TURN' : 'IDS_NOT_PLAYER_TURN';
				elseif($next_move == 'B' || $next_move == 'b')
					echo $playing_white ? 'IDS_NOT_PLAYER_TURN' : 'IDS_PLAYER_TURN';
			}
		}

		echo "</STATUS>\n";

		echo "<COMPLETIONSTATUS>$completion_status</COMPLETIONSTATUS>\n";

		$gametypecode = $this->GetGameTypeCode($game_id);
		$strGameType = "GT_NORMAL_GAME";

		switch($gametypecode){

		case 2:
		  $strGameType = "GT_PASV_RT_GAME";
		  break;

		case 3:
		  $strGameType = "GT_ACTIVE_RT_GAME";
		  break;

		}

		echo "<GAMETYPE>$strGameType</GAMETYPE>\n";

		$this->GetGamePlayTypeInfoForMobile($game_id);
		$this->TimedGameStatsForMobile($game_id);

		echo "<TIMECREATED>$start_time</TIMECREATED>\n";

		echo "<DESCRIPTION>";
		echo $this->GetUserIDByPlayerID($ConfigFile, $w_player_id);
		echo " VS ";
		echo $this->GetUserIDByPlayerID($ConfigFile, $b_player_id);
		echo "</DESCRIPTION>\n";

		echo "<INITIATOR>$initiator</INITIATOR>\n";

		echo "<WHITE>$w_player_id</WHITE>\n";

		echo "<BLACK>$b_player_id</BLACK>\n";

		echo "<NEXTMOVE>$next_move</NEXTMOVE>\n";

		echo "<GAMEID>$game_id</GAMEID>\n";

		echo "<GAMEFEN>" . $this->GetFEN3('', $game_id) . "</GAMEFEN>\n";

		echo "</GAMES>\n";
		$i++;
	}
  }
  

  /**********************************************************************
  * AddWordsToFilter
  *
  */
  function AddWordsToFilter($strWord){

    $insert = "INSERT INTO language_filter VALUES(NULL, '".$strWord."')";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetFilteredWordSelectBox
  *
  */
  function GetFilteredWordSelectBox(){

    $query = "SELECT * FROM language_filter";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select NAME='lstwordlist' size='15' style='width:540'>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = mysql_result($return,$i,"o_id");
        $o_word = mysql_result($return,$i,"o_word");

        echo "<option value='".$o_id."'>".$o_word."</option>";

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * RemoveFilteredWords
  *
  */
  function RemoveFilteredWords($ID){

    $delete = "DELETE FROM language_filter WHERE o_id='".$ID."'";
    mysql_query($delete, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetFilteredWordArray
  *
  */
  function GetFilteredWordArray(&$aArray){

    $query = "SELECT * FROM language_filter";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = mysql_result($return,$i,"o_id");
        $o_word = mysql_result($return,$i,"o_word");

        array_push($aArray, $o_word);

        $i++;

      }

    }

  }


  /**********************************************************************
  * GetEmailLogHTML
  *
  */
  function GetEmailLogHTML(){

    $query = "SELECT * FROM email_log";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETEMAILLOGHTML_TABLE1_WIDTH') && defined('CFG_GETEMAILLOGHTML_TABLE1_BORDER') && defined('CFG_GETEMAILLOGHTML_TABLE1_CELLPADDING') && defined('CFG_GETEMAILLOGHTML_TABLE1_CELLSPACING') && defined('CFG_GETEMAILLOGHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETEMAILLOGHTML_TABLE1_BORDER."' align='".CFG_GETEMAILLOGHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETEMAILLOGHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETEMAILLOGHTML_TABLE1_CELLSPACING."' width='".CFG_GETEMAILLOGHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_152")."</font></td></tr>";

    //echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = mysql_result($return,$i,"o_id");
        $o_to = mysql_result($return,$i,"o_to");
        $o_fromemail = mysql_result($return,$i,"o_fromemail");
        $o_fromname = mysql_result($return,$i,"o_fromname");
        $o_subject = mysql_result($return,$i,"o_subject");
        $o_body = mysql_result($return,$i,"o_body");
        $o_errormsg = mysql_result($return,$i,"o_errormsg");
        $o_date = mysql_result($return,$i,"o_date");

        $text = "TO: $o_to<br>FROM: $o_fromemail<br>SUBLECT: $o_subject<br>ERROR: $o_errormsg<br>DATE: $o_date";

        echo "<tr><td class='row2'>$text</td></tr>";
        echo "<tr><td class='row1'><input type='button' name='btnSendMail' value='Send Mail' class='mainoption' onclick=\"EmailLog(1,".$o_id.");\"><input type='button' name='btnDeleteMail' value='Delete Mail' class='mainoption' onclick=\"EmailLog(2,".$o_id.");\"></td></tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * DeleteEmailLog
  *
  */
  function DeleteEmailLog($id){

    $delete = "DELETE FROM email_log WHERE o_id='".$id."'";
    mysql_query($delete, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * SendEmailLog
  *
  */
  function SendEmailLog($id){

    $query = "SELECT * FROM email_log WHERE o_id='".$id."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $o_id = mysql_result($return,$i,"o_id");
      $o_to = mysql_result($return,$i,"o_to");
      $o_fromemail = mysql_result($return,$i,"o_fromemail");
      $o_fromname = mysql_result($return,$i,"o_fromname");
      $o_subject = mysql_result($return,$i,"o_subject");
      $o_body = mysql_result($return,$i,"o_body");
      $o_errormsg = mysql_result($return,$i,"o_errormsg");
      $o_date = mysql_result($return,$i,"o_date");

      $this->SendEmail($o_to, $o_fromemail, $o_fromname, $o_subject, $o_body);

      $delete = "DELETE FROM email_log WHERE o_id='".$id."'";
      mysql_query($delete, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * LoginTemp
  *
  */
  function LoginTemp($UserID, $Password){

    $query = "SELECT * FROM player WHERE userid = '".$UserID."' AND password = '".$Password."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $player_id = "";

    if($num != 0){
       $player_id  = trim(mysql_result($return,0,"player_id"));
    }

    return $player_id;

  }


  /**********************************************************************
  * AdminMainLinkList1
  *
  */
  function AdminMainLinkList1(){

    $conf = $this->conf;

    $nProposedTcount = 0;
    $nNewPlayerscount = 0;
    $nFaildEmailcount = 0;

    // Count proposed tournaments
    $query = "SELECT COUNT(*) FROM c4m_tournament WHERE t_status='P'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nProposedTcount = mysql_result($return,0,0);
    }

    // Get pending player count
    $query = "SELECT COUNT(*) FROM pendingplayer";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nNewPlayerscount = mysql_result($return,0,0);
    }

    // Get fail email count
    $query = "SELECT COUNT(*) FROM email_log";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nFaildEmailcount = mysql_result($return,0,0);
    }

    //$nProposedTcount
    echo $this->v2GetNewTournamentCount()." Proposed tournament(s) to review. (<a href='./chess_accept_tournament_v2.php'>Click Here</a>)<br>";
    echo $nNewPlayerscount." New member(s) to approve. (<a href='./admin_new_players.php'>Click Here</a>)<br>";
    echo $nFaildEmailcount." Unsent mail message(s) due to SMTP failure. (<a href='./cfg_email_log.php'>Click Here</a>)<br>";

  }


  /**********************************************************************
  * CreateFENStringf
  *
  */
  function CreateFENStringf($board){

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

    return $fen." w KQkq - 0 0";

  }


  /**********************************************************************
  * CreateFENArray
  *
  */
  function CreateFENArray($strFEN, &$aChessBoard){

    $fen = $strFEN;

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

  }


  /**********************************************************************
  * GetPreCreatedGamesListHTML
  *
  */
  function GetPreCreatedGamesListHTML(){

    $query = "SELECT * FROM cfm_creategamefen";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id  = trim(mysql_result($return,$i,"o_id"));
        $o_fen  = trim(mysql_result($return,$i,"o_fen"));

        echo "<tr>";
        echo "<td class='row1'>";
        echo "<input type='radio' name='rdoID' value='".$o_id."'>";
        echo "</td>";
        echo "<td class='row2'>";
        echo $o_fen;
        echo "</td>";
        echo "</tr>";

        $i++;
      }

    }

  }


  /**********************************************************************
  * GetActiveGamesList
  *
  */
  function GetActiveGamesList($PID){

    // Build Search query (When player is white)
    $queryw = "SELECT * FROM game WHERE w_player_id =".$PID." AND completion_status='I' AND status='A'";

    // Build Search query (When player is black)
    $queryb = "SELECT * FROM game WHERE b_player_id =".$PID." AND completion_status='I' AND status='A'";

    // Query the database
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_GETACTIVEGAMESLIST_TABLE1_WIDTH') && defined('CFG_GETACTIVEGAMESLIST_TABLE1_BORDER') && defined('CFG_GETACTIVEGAMESLIST_TABLE1_CELLPADDING') && defined('CFG_GETACTIVEGAMESLIST_TABLE1_CELLSPACING') && defined('CFG_GETACTIVEGAMESLIST_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETACTIVEGAMESLIST_TABLE1_WIDTH."' border='".CFG_GETACTIVEGAMESLIST_TABLE1_BORDER."' cellpadding='".CFG_GETACTIVEGAMESLIST_TABLE1_CELLPADDING."' cellspacing='".CFG_GETACTIVEGAMESLIST_TABLE1_CELLSPACING."' align='".CFG_GETACTIVEGAMESLIST_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='6'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_153")." (".($numw+$numb)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_154").")</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_136")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_137")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_138")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_139")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_140")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_141")."</td></tr>";

    $i = 0;
    while($i < $numw){

      $game_id = trim(mysql_result($returnw,$i,"game_id"));
      $initiator = trim(mysql_result($returnw,$i,"initiator"));
      $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
      $status = trim(mysql_result($returnw,$i,"status"));
      $completion_status = trim(mysql_result($returnw,$i,"completion_status"));
      $start_time = trim(mysql_result($returnw,$i,"start_time"));

      echo "<tr>";
      echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_142")."</a></td>";
      echo "<td class='row2'><a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_143")."</a></td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
      echo "<td class='row2'>".$completion_status."</td>";
      echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
      echo "</tr>";

      $i++;
    }

    $i = 0;
    while($i < $numb){

      $game_id = trim(mysql_result($returnb,$i,"game_id"));
      $initiator = trim(mysql_result($returnb,$i,"initiator"));
      $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
      $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
      $status = trim(mysql_result($returnb,$i,"status"));
      $completion_status = trim(mysql_result($returnb,$i,"completion_status"));
      $start_time = trim(mysql_result($returnb,$i,"start_time"));

      echo "<tr>";
      echo "<td class='row2'><a href='./chess_game.php?gameid=".$game_id."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_142")."</a></td>";
      echo "<td class='row2'><a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_143")."</a></td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$w_player_id)."</td>";
      echo "<td class='row2'>".$this->GetUserIDByPlayerID($ConfigFile,$b_player_id)."</td>";
      echo "<td class='row2'>".$completion_status."</td>";
      echo "<td class='row2'>".date("m-d-Y",$start_time)."</td>";
      echo "</tr>";

      $i++;
    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetDefaultAdminChessboardColors
  *
  */
  function GetDefaultAdminChessboardColors(&$clrl, &$clrd){

    $query = "SELECT * FROM admin_chessboard_colors";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $clrd = trim(mysql_result($return,0,"o_dcolor"));
      $clrl = trim(mysql_result($return,0,"o_lcolor"));

    }

  }


  /**********************************************************************
  * UpdateDefaultAdminChessboardColors
  *
  */
  function UpdateDefaultAdminChessboardColors($clrl, $clrd){

    $update = "UPDATE admin_chessboard_colors SET o_dcolor='".$clrd."', o_lcolor='".$clrl."' WHERE o_id=1";
    $return = mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * ChessBoardColorsAdmin
  *
  */
  function ChessBoardColorsAdmin($clrl, $clrd){

    echo "<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>";
    echo "<tr>";
    echo "<td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_12")."</font><b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_13")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_14")."</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td bgcolor='".$clrl."'>&nbsp;<br></td><td bgcolor='".$clrd."'>&nbsp;<br></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top' class='row2'>";

    echo "<input type='text' name='txtl' value='".$clrl."' class='post' size='18' >";

    echo " <A HREF=\"#\" onClick=\"cp.select(document.forms[0].txtl,'pick');return false;\" NAME=\"pick\" ID=\"pick\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_15")."</a>";

    echo "</td>";
    echo "<td valign='top' class='row2'>";

    echo "<input type='text' name='txtd' value='".$clrd."' class='post' size='18' >";
    echo " <A HREF=\"#\" onClick=\"cp.select(document.forms[0].txtd,'pick');return false;\" NAME=\"pick\" ID=\"pick\">".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_15")."</a>";

    echo "</td>";
    echo "</tr>";
    echo "<td colspan='2' valign='top' class='row1' align='right'>";
    echo "<input type='submit' name='cmdchgcolor' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_CNGCLR")."' class='mainoption'>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * AddOnlinePlayerToGraphData
  *
  */
  function AddOnlinePlayerToGraphData($player){

    $insert = "INSERT INTO whos_online_graph VALUES(NULL, NOW(), '".$player."')";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetOnlinePlayerFromGraphData
  *
  */
  function GetOnlinePlayerFromGraphData(&$FirstDate, &$SecondDate, &$ThirdDate){

    $FirstDate = 0;
    $SecondDate = 0;
    $ThirdDate = 0;

    $query = "SELECT DISTINCT o_date FROM whos_online_graph WHERE o_date <= NOW() ORDER BY o_date DESC;";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $switch = 1;
      while($i < $num){

        $date = trim(mysql_result($return,$i,"o_date"));

        $query1 = "SELECT COUNT(*) FROM whos_online_graph WHERE o_date = '".$date."';";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $count = mysql_result($return1,0,0);

          if($switch == 1){
            $FirstDate = $count;
          }elseif($switch == 2){
            $SecondDate = $count;
          }elseif($switch == 3){
            $ThirdDate = $count;
          }

        }

        $i++;
        $switch++;
      }

    }

  }


  /**********************************************************************
  * GetOnlinePlayerFromGraphDataDate
  *
  */
  function GetOnlinePlayerFromGraphDataDate(&$Date1, &$Date2, &$Date3){

    $Date1 = "*";
    $Date2 = "*";
    $Date3 = "*";

    $query = "SELECT DISTINCT o_date FROM whos_online_graph WHERE o_date <= NOW() ORDER BY o_date DESC;";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $switch = 1;
      while($i < $num){

        $date = trim(mysql_result($return,$i,"o_date"));

        if($switch == 1){
          $Date1 = $date;
        }elseif($switch == 2){
          $Date2 = $date;
        }elseif($switch == 3){
          $Date3 = $date;
        }

        $i++;
        $switch++;
      }

    }

  }


  /**********************************************************************
  * UpdateLastLoginInfo
  *
  */
  function UpdateLastLoginInfo($playerid){

    $query = "SELECT * FROM player_last_login WHERE o_playerid = '".$playerid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $update = "UPDATE player_last_login SET o_date = NOW() WHERE o_playerid = '".$playerid."'";
      mysql_query($update, $this->link) or die(mysql_error());
    }else{
      $insert = "INSERT INTO player_last_login VALUES(NULL, '".$playerid."', NOW())";
      mysql_query($insert, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * GetClubListHTML
  *
  */
  function GetClubListHTML(){

    $query = "SELECT * FROM chess_club ORDER BY o_clubname ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETCLUBLISTHTML_TABLE1_WIDTH') && defined('CFG_GETCLUBLISTHTML_TABLE1_BORDER') && defined('CFG_GETCLUBLISTHTML_TABLE1_CELLPADDING') && defined('CFG_GETCLUBLISTHTML_TABLE1_CELLSPACING') && defined('CFG_GETCLUBLISTHTML_TABLE1_ALIGN') && defined('CFG_GETCLUBLISTHTML_ROW1_WIDTH')){
      echo "<table width='".CFG_GETCLUBLISTHTML_TABLE1_WIDTH."' border='".CFG_GETCLUBLISTHTML_TABLE1_BORDER."' cellpadding='".CFG_GETCLUBLISTHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETCLUBLISTHTML_TABLE1_CELLSPACING."' align='".CFG_GETCLUBLISTHTML_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='500' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr>";
    echo "<td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_158")."</font><b></td>";
    echo "</tr>";

    echo "<tr>";

    // Skin table settings
    if(defined('CFG_GETCLUBLISTHTML_ROW1_WIDTH')){
      echo "<td class='row1' width='".CFG_GETCLUBLISTHTML_ROW1_WIDTH."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_159")."</td>";
    }else{
      echo "<td class='row1' width='300'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_159")."</td>";
    }

    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_160")."</td>";
    echo "</tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $id = mysql_result($return,$i,"o_id");
        $clubname = mysql_result($return,$i,"o_clubname");
        $date = mysql_result($return,$i,"o_date");

        echo "<tr>";
        echo "<td class='row1'><a href='./chess_club_page.php?clubid=".$id."'>".$clubname."</a></td>";
        echo "<td class='row1'>".$date."</td>";
        echo "</tr>";

        $i++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * timeLeft
  *
  */

  function timeLeft($timeLeft){

    if($timeLeft > 0){
      $days = floor($timeLeft/60/60/24);
      $hours = $timeLeft/60/60%24;
      $mins = $timeLeft/60%60;
      $secs = $timeLeft%60;

      if($days){
        $theText = $days." Day(s)";
        if($hours){
          $theText .= ", ".$hours." Hour(s)";
        }

        if($mins){
          $theText .= ", ".$mins." Minute(s)";
        }

        if($secs){
          $theText .= ", ".$secs." Second(s)";
        }

      }elseif($hours){
        $theText = $hours." Hour(s)";
        if($mins){
          $theText .= ", ".$mins." Minute(s)";
        }

        if($secs){
          $theText .= ", " .$secs." Second(s)";
        }
      }elseif($mins){
        $theText = $mins." Minute(s)";
        if($secs){
          $theText .= ", " .$secs." Second(s)";
        }
      }elseif($secs){
        $theText = $secs." Second(s)";
      }
    }else{
      $theText = "The time is already in the past!";
    }

    return $theText;

  }


  /**********************************************************************
  * GetGameTimeoutByGameRelation
  *
  */
  function GetGameTimeoutByGameRelation($Playerid, $GameID){

    $icopic = "./skins/".$this->SkinsLocation."/images/clock.gif";
    $GameTypeCode = $this->GetGameTypeCode($GameID);
    $text = "";

    if($GameTypeCode == 1 || $GameTypeCode == 2){

      $query = "SELECT * FROM cfm_game_options WHERE o_gameid='".$GameID."'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $o_gameid = trim(mysql_result($return,0,"o_gameid"));
        $o_rating = trim(mysql_result($return,0,"o_rating"));
        $o_timetype = trim(mysql_result($return,0,"o_timetype"));

        $nTimeCheck = 0;
        $this->GetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode);

        switch($o_timetype){

          case "C-Blitz":
            $nTimeCheck = $CBlitz;
            break;

          case "C-Short":
            $nTimeCheck = $CShort;
            break;

          case "C-Normal":
            $nTimeCheck = $CNormal;
            break;

          case "C-Slow":
            $nTimeCheck = $CSlow;
            break;

          case "C-Snail":
            $nTimeCheck = $CSnail;
            break;

        }

        $query1 = "SELECT * FROM move_history WHERE game_id='".$o_gameid."' ORDER BY move_id DESC";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        $days = 0;

        if($num1 != 0){

          $move_time = trim(mysql_result($return1,0,"time"));
          $nTimeDiff = strtotime("+".$nTimeCheck." day", $move_time) - time();
          $days = $this->timeLeft($nTimeDiff);

        }else{

          $query2 = "SELECT * FROM game WHERE game_id = '".$o_gameid."'";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2){

            $move_time = trim(mysql_result($return2,0,"start_time"));
            $nTimeDiff = strtotime("+".$nTimeCheck." day", $move_time) - time();
            $days = $this->timeLeft($nTimeDiff);

          }

        }

        if($this->IsPlayersTurn($this->ChessCFGFileLocation, $Playerid, $GameID) == true){
          $text = "<img src='".$icopic."' alt='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_161")."'> ".$days."";
        }else{
          $text = "<img src='".$icopic."' alt='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_161")."'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_163");
        }

      }

    }

    return $text;

  }


  /**********************************************************************
  * GetGamesLastMove
  *
  */
  function GetGamesLastMove($GameID){

    $aMove = array();

    //Who made the first move
    $query1 = "SELECT * FROM move_history WHERE game_id = '".$GameID."' ORDER BY move_id DESC";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){
      $move = trim(mysql_result($return1, 0, "move"));

      switch($move){

        case "O-O w":

          // Case for short castle white
          $aMove[0] = "1-7";
          $aMove[1] = "1-6";

          break;

        case "O-O-O w":

          // Case for long castle white
          $aMove[0] = "1-3";
          $aMove[1] = "1-4";

          break;

        case "O-O b":

          // Case for short castle black
          $aMove[0] = "8-7";
          $aMove[1] = "8-6";

          break;

        case "O-O-O b":

          // Case for long castle black
          $aMove[0] = "8-3";
          $aMove[1] = "8-4";

          break;

        default:

          // Default case if no castling detected.
          list($from, $to) = explode(",", $move);

          $col = preg_split('//', $to);

          $aFind = array("a", "b", "c", "d", "e", "f", "g", "h");
          $aReplace = array("1", "2", "3", "4", "5", "6", "7", "8");

          $aMove[0] = $col[2]."-".str_replace($aFind, $aReplace, $col[1]);

          break;

      }

    }

    return $aMove;

  }


  /**********************************************************************
  * SetPlayerCreditsAdmin
  *
  */
  function SetPlayerCreditsAdmin($Credits){

    $query = "SELECT * FROM admin_player_credits WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $update = "UPDATE admin_player_credits SET o_credits='".$Credits."' WHERE o_id=1";
      mysql_query($update, $this->link) or die(mysql_error());
    }else{
      $insert = "INSERT INTO admin_player_credits VALUES(1, '".$Credits."')";
      mysql_query($insert, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * SetPlayerCreditsInit
  *
  */
  function SetPlayerCreditsInit($PlayerID){

    $query = "SELECT * FROM player_credits WHERE o_playerid='".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Do nothing, player found.

    }else{

      $query1 = "SELECT * FROM admin_player_credits WHERE o_id=1";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $credits = mysql_result($return1,0,"o_credits");

        $insert = "INSERT INTO player_credits VALUES('".$PlayerID."', '".$credits."')";
        mysql_query($insert, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * AddPlayerCredits
  *
  */
  function AddPlayerCredits($PlayerID, $Credits){

    $query = "SELECT * FROM player_credits WHERE o_playerid='".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $xcredits = mysql_result($return,0,"o_credits");
      $xcredits = $xcredits + $Credits;

      $update = "UPDATE player_credits SET o_credits='".$xcredits."' WHERE o_playerid='".$PlayerID."'";
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * RemovePlayerCredits
  *
  */
  function RemovePlayerCredits($PlayerID, $Credits){

    $query = "SELECT * FROM player_credits WHERE o_playerid='".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $xcredits = mysql_result($return,0,"o_credits");
      $xcredits = $xcredits - $Credits;

      $update = "UPDATE player_credits SET o_credits='".$xcredits."' WHERE o_playerid='".$PlayerID."'";
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetPlayerCredits
  *
  */
  function GetPlayerCredits($PlayerID){

    $credits = 0;

    $query = "SELECT * FROM player_credits WHERE o_playerid='".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $credits = mysql_result($return,0,"o_credits");
    }

    return $credits;

  }


  /**********************************************************************
  * GetPuzzleCount
  *
  */
  function GetPuzzleCount(){

    $pcount = 0;

    // pzl = puzzle
    $query = "SELECT COUNT(*) FROM activities WHERE o_type='pzl' AND o_enabled='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $pcount = mysql_result($return,0,0);
    }

    return $pcount;

  }


  /**********************************************************************
  * GetLessonCount
  *
  */
  function GetLessonCount(){

    $lcount = 0;

    // lsn = lesson
    $query = "SELECT COUNT(*) FROM activities WHERE o_type='lsn' AND o_enabled='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $lcount = mysql_result($return,0,0);
    }

    return $lcount;

  }


  /**********************************************************************
  * GetOtherCount
  *
  */
  function GetOtherCount(){

    $ocount = 0;

    // Other count
    $query = "SELECT COUNT(*) FROM activities WHERE o_type !='lsn' AND o_type !='pzl' AND o_enabled='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $ocount = mysql_result($return,0,0);
    }

    return $ocount;

  }


  /**********************************************************************
  * GetPuzzleCountb
  *
  */
  function GetPuzzleCountb($PlayerID){

    $pcount = 0;

    // pzl = puzzle
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type='pzl' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $pcount = mysql_result($return,0,0);
    }

    return $pcount;

  }


  /**********************************************************************
  * GetLessonCountb
  *
  */
  function GetLessonCountb($PlayerID){

    $lcount = 0;

    // lsn = lesson
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type='lsn' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $lcount = mysql_result($return,0,0);
    }

    return $lcount;

  }


  /**********************************************************************
  * GetOtherCountb
  *
  */
  function GetOtherCountb($PlayerID){

    $ocount = 0;

    // get other activities
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type !='lsn' AND activities.o_type !='pzl' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $ocount = mysql_result($return,0,0);
    }

    return $ocount;

  }


  /**********************************************************************
  * GetPuzzleCountbComplete
  *
  */
  function GetPuzzleCountbComplete($PlayerID){

    $pcount = 0;

    // pzl = puzzle
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type='pzl' AND player_purchased_activities.o_complete ='y' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $pcount = mysql_result($return,0,0);
    }

    return $pcount;

  }


  /**********************************************************************
  * GetLessonCountbComplete
  *
  */
  function GetLessonCountbComplete($PlayerID){

    $lcount = 0;

    // lsn = lesson
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type='lsn' AND player_purchased_activities.o_complete ='y' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $lcount = mysql_result($return,0,0);
    }

    return $lcount;

  }


  /**********************************************************************
  * GetOtherCountbComplete
  *
  */
  function GetOtherCountbComplete($PlayerID){

    $ocount = 0;

    // get other activities
    $query = "SELECT COUNT(*) FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type !='lsn' AND activities.o_type !='pzl' AND player_purchased_activities.o_complete ='y' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $ocount = mysql_result($return,0,0);
    }

    return $ocount;

  }


  /**********************************************************************
  * GetActivitiesMenuBarHTML
  *
  */
  function GetActivitiesMenuBarHTML($PlayerID){

    echo "<br>";
    echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_164")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$this->GetPlayerCredits($PlayerID)."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_165")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$this->GetLessonCountb($PlayerID)."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_166")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$this->GetPuzzleCountb($PlayerID)."</span>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td>";
    echo "<span class='gensmall'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_167")."</span>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<span class='gensmall'>".$this->GetOtherCountb($PlayerID)."</span>";
    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetPersonalActivityStatsHTML
  *
  */
  function GetPersonalActivityStatsHTML($PlayerID){

    // Skin table settings
    if(defined('CFG_GETPERSONALACTIVITYSTATSHTM_WIDTH') && defined('CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_BORDER') && defined('CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_CELLPADDING') && defined('CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_CELLSPACING') && defined('CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETPERSONALACTIVITYSTATSHTM_WIDTH."' cellpadding='".CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_CELLPADDING."' cellspacing='".CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_CELLSPACING."' border='".CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_BORDER."' align='".CFG_GETPERSONALACTIVITYSTATSHTM_TABLE1_ALIGN."'>";
    }else{
      echo "<table width='95%' cellpadding='3' cellspacing='1' border='0' align='center'>";
    }

    echo "<tr>";
    echo "<td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_168")."</b></td><td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_169")."</b></td><td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_170")."</b></td><td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_171")."</b></td><td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_172")."</b></td><td><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_173")."</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td colspan='6'><hr width='100%'></td>";
    echo "</tr>";

    // Puzzle Stats
    echo "<tr>";
    echo "<td>".$this->GetPuzzleCountb($PlayerID)."</td><td><a href='./chess_view_activities.php?tag=puzzle'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_174")."</a></td><td>".($this->GetPuzzleCountb($PlayerID) - $this->GetPuzzleCountbComplete($PlayerID))."</td><td>".$this->GetPuzzleCountbComplete($PlayerID)."</td><td>".($this->GetPuzzleCount() - $this->GetPuzzleCountb($PlayerID))."</td><td>".$this->GetPuzzleCount()."</td>";
    echo "</tr>";

    // lesson Stats
    echo "<tr>";
    echo "<td>".$this->GetLessonCountb($PlayerID)."</td><td><a href='./chess_view_activities.php?tag=lesson'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_175")."</a></td><td>".($this->GetLessonCountb($PlayerID) - $this->GetLessonCountbComplete($PlayerID))."</td><td>".$this->GetLessonCountbComplete($PlayerID)."</td><td>".($this->GetLessonCount() - $this->GetLessonCountb($PlayerID))."</td><td>".$this->GetLessonCount()."</td>";
    echo "</tr>";

    // Other item stats
    echo "<tr>";
    echo "<td>".$this->GetOtherCountb($PlayerID)."</td><td><a href='./chess_view_activities.php?tag=other'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_176")."</a></td><td>".($this->GetOtherCountb($PlayerID) - $this->GetOtherCountbComplete($PlayerID))."</td><td>".$this->GetOtherCountbComplete($PlayerID)."</td><td>".($this->GetOtherCount() - $this->GetOtherCountb($PlayerID))."</td><td>".$this->GetOtherCount()."</td>";
    echo "</tr>";

    echo "</table>";

  }


  /**********************************************************************
  * GetPersonalActivityListHTML
  *
  */
  function GetPersonalActivityListHTML($PlayerID, $Type){

    $query = "";
    $title = "";

    // Set the query variable to the associated type
    if($Type == "lsn"){
      $query = "SELECT * FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type ='lsn' AND o_playerid = '".$PlayerID."' Order By player_purchased_activities.o_id DESC";
      $title = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_175");
    }elseif($Type == "pzl"){
      $query = "SELECT * FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type ='pzl' AND o_playerid = '".$PlayerID."' Order By player_purchased_activities.o_id DESC";
      $title = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_174");
    }else{
      $query = "SELECT * FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND activities.o_type !='lsn' AND activities.o_type !='pzl' AND o_playerid = '".$PlayerID."' Order By player_purchased_activities.o_id DESC";
      $title = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_176");
    }

    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_WIDTH') && defined('CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_BORDER') && defined('CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLPADDING') && defined('CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLSPACING') && defined('CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_WIDTH."' border='".CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_BORDER."' cellpadding='".CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_CELLSPACING."' align='".CFG_GETPERSONALACTIVITYLISTHTML_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='95%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$title."</font><b></td></tr>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return,$i,0);
        $o_name = mysql_result($return,$i,1);
        $o_description = mysql_result($return,$i,2);
        $o_createdby = mysql_result($return,$i,3);
        $o_type = mysql_result($return,$i,4);
        $o_credit = mysql_result($return,$i,5);
        $o_enabled = mysql_result($return,$i,6);
        $o_date = mysql_result($return,$i,7);
        $o_id_1 = mysql_result($return,$i,8);
        $o_playerid = mysql_result($return,$i,9);
        $o_activitiesid = mysql_result($return,$i,10);
        $o_credit_1 = mysql_result($return,$i,11);
        $o_complete = mysql_result($return,$i,12);
        $o_date_1 = mysql_result($return,$i,13);

        $strComplete = "";

        if($o_complete == 'y'){
          $strComplete = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_177");
        }else{
          $strComplete = $this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_178");
        }

        echo "<tr><td class='row1'>".$o_name."</td><td class='row1' align='center'>".$strComplete."</td><td class='row1' align='center'><a href='./chess_view_activities.php?tag=sa&aid=".$o_activitiesid."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_179")."</a></td></tr>";
        echo "<tr><td  class='row2' colspan='3'>".$o_description."</td></tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * IsPlayerAllowedToViewActivity
  *
  */
  function IsPlayerAllowedToViewActivity($PlayerID, $ActivityID){

    $AllowedToView = false;

    // get other activities
    $query = "SELECT * FROM player_purchased_activities WHERE o_activitiesid = '".$ActivityID."' AND o_playerid = '".$PlayerID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $AllowedToView = true;
    }

    return $AllowedToView;

  }


  /**********************************************************************
  * GetActivityPageCount
  *
  */
  function GetActivityPageCount($ActivityID){

     $pagecount = 0;

    // Get record count
    $query = "SELECT COUNT(*) FROM activity_pages WHERE o_activitiesid='".$ActivityID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $pagecount = mysql_result($return,0,0);
    }

    return $pagecount;

  }


  /**********************************************************************
  * GetActivityPageResource
  *
  */
  function GetActivityPageResource($ActivityID, $PageIndex, $loc = "./"){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        // Get resource text
        $resourcetxt = mysql_result($return, $PageIndex, "o_content1");

        // Parse the resources and place them in an array
        $aResource = array();
        preg_match_all("#<RESOURCE>(.*?)</RESOURCE>#s", $resourcetxt, $aResource);

        // Get count needs to be at least two records in the array
        $nCount1 = count($aResource);

        if($nCount1 >= 2){

          // Get count of array index 1
          $nCount2 = count($aResource[1]);

          $ii = 0;
          while($ii < $nCount2){

            $re1 = $aResource[1][$ii];

            // Get resource info
            $query1 = "SELECT * FROM activity_resources WHERE o_id='".$re1."'";
            $return1 = mysql_query($query1, $this->link) or die(mysql_error());
            $num1 = mysql_numrows($return1);

            if($num1 != 0){

              $o_id = mysql_result($return1,0,"o_id");
              $o_activitiesid = mysql_result($return1,0,"o_activitiesid");
              $o_name = mysql_result($return1,0,"o_name");
              $o_data = mysql_result($return1,0,"o_data");
              $o_type = mysql_result($return1,0,"o_type");
              $o_date = mysql_result($return1,0,"o_date");

              /////////////////////////////////////////////////////////////////////////////
              // Handle the pgn viewer resource
              /////////////////////////////////////////////////////////////////////////////
              if($o_type == "pgn"){

                // Parse the resources info and place them in an array
                $aPGN = array();
                $aFEN = array();

                // Get PGN info
                preg_match_all("#<PGN>(.*?)</PGN>#s", $o_data, $aPGN);

                $pgn1 = "";
                $nCountpgn1 = count($aPGN);

                if($nCountpgn1 >= 2){

                  $nCountpgn2 = count($aPGN[1]);

                  if($nCountpgn2 == 1){

                    if(trim($aPGN[1][0]) != ""){
                      $pgn1 = $aPGN[1][0];
                    }

                  }

                }

                // Get FEN info
                preg_match_all("#<FEN>(.*?)</FEN>#s", $o_data, $aFEN);

                $fen1 = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
                $nCountfen1 = count($aFEN);

                if($nCountfen1 >= 2){

                  $nCountfen2 = count($aFEN[1]);

                  if($nCountfen2 == 1){

                    if(trim($aFEN[1][0]) != ""){
                      $fen1 = $aFEN[1][0];
                    }

                  }

                }

                echo "<iframe name=\"g1\" scrolling=no frameborder=0 width=500 height=355 src=\"".$loc."pgnviewer/view_pgn_game_activity.php?fen=".$fen1."&pgn=".$pgn1."\"></iframe>";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the Movie resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "mov"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<embed SRC='".$loc."activities/".$txtname."' autostart='false' volume='50' ";
                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }
                echo ">";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the Flash resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "fls"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<object ";

                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }

                echo ">";
                echo "<param name='movie' value='".$loc."activities/".$txtname."'>";
                echo "<embed src='".$loc."activities/".$txtname."' ";

                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }

                echo ">";
                echo "</embed>";
                echo "</object>";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the Image resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "img"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<img src='".$loc."activities/".$txtname."' ";
                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }
                echo ">";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the Sound resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "snd"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<embed SRC='".$loc."activities/".$txtname."' autostart='false' volume='50' ";
                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }
                echo ">";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the text resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "txt"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<iframe name=\"g1\" scrolling=auto frameborder=0 ";

                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }

                echo " src='".$loc."activities/".$txtname."'></iframe>";

              /////////////////////////////////////////////////////////////////////////////
              // Handle the html resource
              /////////////////////////////////////////////////////////////////////////////
              }elseif($o_type == "htm"){

                // Parse the resources info and place them in an array
                $aNAME = array();
                $aWIDTH = array();
                $aHEIGHT = array();

                // Get NAME info
                preg_match_all("#<NAME>(.*?)</NAME>#s", $o_data, $aNAME);

                $txtname = "";
                $nCountname1 = count($aNAME);

                if($nCountname1 >= 2){

                  $nCountname2 = count($aNAME[1]);

                  if($nCountname2 == 1){

                    if(trim($aNAME[1][0]) != ""){
                      $txtname = $aNAME[1][0];
                    }

                  }

                }

                // Get WIDTH info
                preg_match_all("#<WIDTH>(.*?)</WIDTH>#s", $o_data, $aWIDTH);

                $txtwidth = "";
                $nCountwidth1 = count($aWIDTH);

                if($nCountwidth1 >= 2){

                  $nCountwidth2 = count($aWIDTH[1]);

                  if($nCountwidth2 == 1){

                    if(trim($aWIDTH[1][0]) != ""){
                      $txtwidth = $aWIDTH[1][0];
                    }

                  }

                }

                // Get HEIGHT info
                preg_match_all("#<HEIGHT>(.*?)</HEIGHT>#s", $o_data, $aHEIGHT);

                $txtheight = "";
                $nCountheight1 = count($aHEIGHT);

                if($nCountheight1 >= 2){

                  $nCountheight2 = count($aHEIGHT[1]);

                  if($nCountheight2 == 1){

                    if(trim($aHEIGHT[1][0]) != ""){
                      $txtheight = $aHEIGHT[1][0];
                    }

                  }

                }

                echo "<iframe name=\"g1\" scrolling=auto frameborder=0 ";

                if($txtwidth != ""){
                  echo "width='".$txtwidth."' ";
                }

                if($txtheight != ""){
                  echo "height='".$txtheight."'";
                }

                echo " src='".$loc."activities/".$txtname."'></iframe>";

              }

            }

            $ii++;
          }

        }

      }

    }

  }


  /**********************************************************************
  * GetActivityPageText
  *
  */
  function GetActivityPageText($ActivityID, $PageIndex){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_content2 = mysql_result($return,$PageIndex,"o_content2");

        echo "<br>";
        echo stripslashes($o_content2);
        echo "<br><br>";

      }

    }

  }


  /**********************************************************************
  * GetActivityNameByID
  *
  */
  function GetActivityNameByID($ActivityID){

    $strName = 0;

    // Other count
    $query = "SELECT * FROM activities WHERE o_id='".$ActivityID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $strName = mysql_result($return,0,"o_name");
    }

    return $strName;

  }


  /**********************************************************************
  * GetActivityPageType
  *
  */
  function GetActivityPageType($ActivityID, $PageIndex){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $type = "";

    if($num != 0){

      if($PageIndex < $num){
        $type = mysql_result($return,$PageIndex,"o_type");
      }

    }

    return $type;

  }


  /**********************************************************************
  * HandleActivityPageControlType
  *
  */
  function HandleActivityPageControlType($ActivityID, $PageIndex, $PID){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");
        $o_type = mysql_result($return,$PageIndex,"o_type");

        $query1 = "SELECT * FROM player_purchased_activities WHERE o_activitiesid='".$ActivityID."' AND o_playerid='".$PID."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $o_id1 = mysql_result($return1,0,"o_id");

          $query2 = "SELECT * FROM player_purchased_activity_pages WHERE o_playerpurchasedactivitiesid='".$o_id1."' AND o_activitypagesid='".$o_id."'";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 != 0){

              $o_answertype = mysql_result($return2,0,"o_answertype");

              if($o_answertype == "sln"){
                echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_229")." ".$o_solution."<br><br>";
              }elseif($o_answertype == "r"){
                echo "You have answered correctly.<br><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_214")."</b> ".$o_solution."<br><br>";
              }

          }else{

            // Create the controls
            if($o_type == "tnf"){

              echo "<input type='button' name='btnTrue' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_213")."' class='mainoption' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd=true';\">";
              echo "<input type='button' name='btnFalse' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_212")."' class='mainoption' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd=false';\">";

            }elseif($o_type == "lan"){

              echo "<input type='text' name='txtAnsX' class='post'>";
              echo "<input type='button' name='btnLongAnswer' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_228")."' class='mainoption' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd='+document.frmActivity.txtAnsX.value+'';\">";

            }

          }

        }

      }

    }

  }


  /**********************************************************************
  * HandleActivityAnswer
  *
  */
  function HandleActivityAnswer($ActivityID, $PageIndex, $Answer, $PID){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $txtreturn = "";

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");

        $query1 = "SELECT * FROM player_purchased_activities WHERE o_activitiesid='".$ActivityID."' AND o_playerid='".$PID."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $o_id1 = mysql_result($return1,0,"o_id");

          $query2 = "SELECT * FROM player_purchased_activity_pages WHERE o_playerpurchasedactivitiesid='".$o_id1."' AND o_activitypagesid='".$o_id."'";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 == 0){

            if(strcasecmp(trim($o_solution), trim($Answer)) == 0){

              $insert = "INSERT INTO player_purchased_activity_pages VALUES(NULL, '".$o_id1."', '".$o_id."', 'r', NOW())";
              mysql_query($insert, $this->link) or die(mysql_error());

            }else{

              $txtreturn = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_227")."<br><br>";

            }

          }

        }

      }

    }

    return $txtreturn;

  }


  /**********************************************************************
  * HandleActivitySolution
  *
  */
  function HandleActivitySolution($ActivityID, $PageIndex, $PID){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");

        $query1 = "SELECT * FROM player_purchased_activities WHERE o_activitiesid='".$ActivityID."' AND o_playerid='".$PID."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $o_id1 = mysql_result($return1,0,"o_id");

          $query2 = "SELECT * FROM player_purchased_activity_pages WHERE o_playerpurchasedactivitiesid='".$o_id1."' AND o_activitypagesid='".$o_id."'";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 == 0){

            $insert = "INSERT INTO player_purchased_activity_pages VALUES(NULL, '".$o_id1."', '".$o_id."', 'sln', NOW())";
            mysql_query($insert, $this->link) or die(mysql_error());

          }

        }

      }

    }

  }


  /**********************************************************************
  * HandleActivitySolutionViewed
  *
  */
  function HandleActivitySolutionViewed($ActivityID, $PageIndex, $PID){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");
        $o_type = mysql_result($return,$PageIndex,"o_type");

        $query1 = "SELECT * FROM player_purchased_activities WHERE o_activitiesid='".$ActivityID."' AND o_playerid='".$PID."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $o_id1 = mysql_result($return1,0,"o_id");

          $query2 = "SELECT * FROM player_purchased_activity_pages WHERE o_playerpurchasedactivitiesid='".$o_id1."' AND o_activitypagesid='".$o_id."'";
          $return2 = mysql_query($query2, $this->link) or die(mysql_error());
          $num2 = mysql_numrows($return2);

          if($num2 == 0 && $o_type == "lsn"){

            $insert = "INSERT INTO player_purchased_activity_pages VALUES(NULL, '".$o_id1."', '".$o_id."', 'v', NOW())";
            mysql_query($insert, $this->link) or die(mysql_error());

          }

          if($this->IsPlayerActivityComplete($ActivityID, $PageIndex, $PID)){

            $update = "UPDATE player_purchased_activities SET o_complete='y' WHERE o_playerid='".$PID."' AND o_activitiesid='".$ActivityID."' AND o_id='".$o_id1."'";
            mysql_query($update, $this->link) or die(mysql_error());

          }

        }

      }

    }

  }


  /**********************************************************************
  * IsPlayerActivityComplete
  *
  */
  function IsPlayerActivityComplete($ActivityID, $PageIndex, $PID){

    $complete = false;

    $query = "SELECT COUNT(*) FROM activity_pages WHERE o_activitiesid='".$ActivityID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $query1 = "SELECT COUNT(*) FROM player_purchased_activities, player_purchased_activity_pages WHERE player_purchased_activities.o_id = player_purchased_activity_pages.o_playerpurchasedactivitiesid AND player_purchased_activities.o_playerid='".$PID."' AND player_purchased_activities.o_activitiesid='".$ActivityID."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num != 0 && $num1 != 0){

      $ncount1 = mysql_result($return,0,0);
      $ncount2 = mysql_result($return1,0,0);

      if($ncount1 == $ncount2){

        $complete = true;

      }

    }

    return $complete;

  }


  /**********************************************************************
  * GetPlayerActivityHTMLMenu
  *
  */
  function GetPlayerActivityHTMLMenu($ActivityID, $PageIndex, $PID){

    if($ActivityID != "" && $PID != ""){

      if($PageIndex == ""){
        $PageIndex = 0;
      }

      $query = "SELECT * FROM activities, player_purchased_activities WHERE activities.o_id = player_purchased_activities.o_activitiesid AND player_purchased_activities.o_activitiesid='".$ActivityID."' AND o_playerid = '".$PID."'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $o_id = mysql_result($return,$i,0);
        $o_name = mysql_result($return,$i,1);
        $o_description = mysql_result($return,$i,2);
        $o_createdby = mysql_result($return,$i,3);
        $o_type = mysql_result($return,$i,4);
        $o_credit = mysql_result($return,$i,5);
        $o_enabled = mysql_result($return,$i,6);
        $o_date = mysql_result($return,$i,7);
        $o_id_1 = mysql_result($return,$i,8);
        $o_playerid = mysql_result($return,$i,9);
        $o_activitiesid = mysql_result($return,$i,10);
        $o_credit_1 = mysql_result($return,$i,11);
        $o_complete = mysql_result($return,$i,12);
        $o_date_1 = mysql_result($return,$i,13);

        echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";

        echo "<tr>";
        echo "<td><img src='./skins/".$this->SkinsLocation."/images/a_folder.gif' border='0'> <a href='./chess_view_activities.php?tag=sa&aid=".$o_activitiesid."'>".$o_name."</a></td>";
        echo "</tr>";

        // Get pages
        $query1 = "SELECT * FROM activity_pages WHERE o_activitiesid = '".$o_activitiesid."' Order By o_id ASC";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $i=0;
          while($i < $num1){

            $o_id2 = mysql_result($return1,$i,"o_id");

            echo "<tr>";
            echo "<td>";

            if($this->IsTopicComplete($o_id_1, $o_id2)){
              echo "<img src='./skins/".$this->SkinsLocation."/images/a_choice-yes.gif' border='0'>";
            }else{
              echo "<img src='./skins/".$this->SkinsLocation."/images/a_choice-no.gif' border='0'>";
            }

            echo "<img src='./skins/".$this->SkinsLocation."/images/a_viewtopic.gif' border='0'> <a href='./chess_view_activities.php?tag=sa&aid=".$o_activitiesid."&pgi=".$i."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_226")."".($i+1)."</a></td>";
            echo "</tr>";

            $i++;
          }

        }

        echo "</table>";
        echo "<br>";

      }

    }

  }


  /**********************************************************************
  * IsTopicComplete
  *
  */
  function IsTopicComplete($PlayerPurchasedActivitiesID, $ActivityPagesID){

    $complete = false;

    $query = "SELECT * FROM player_purchased_activity_pages WHERE o_playerpurchasedactivitiesid='".$PlayerPurchasedActivitiesID."' AND o_activitypagesid='".$ActivityPagesID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $complete = true;
    }

    return $complete;

  }


  /**********************************************************************
  * GetAdminActivityListHTML
  *
  */
  function GetAdminActivityListHTML(){

    // Get Puzzle list
    $query = "SELECT * FROM activities WHERE o_type='pzl'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_WIDTH') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_BORDER') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLPADDING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLSPACING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETADMINACTIVITYLISTHTML_TABLE1_BORDER."' align='".CFG_GETADMINACTIVITYLISTHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETADMINACTIVITYLISTHTML_TABLE1_CELLSPACING."' width='".CFG_GETADMINACTIVITYLISTHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    $XRow1 = "25%";
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE1_ROW1_WIDTH')){
      $XRow1 = CFG_GETADMINACTIVITYLISTHTML_TABLE1_ROW1_WIDTH;
    }

    echo "<tr><td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_225")."</font><b></td></tr>";
    echo "<tr>";
    echo "<td class='row1' width='".$XRow1."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_201")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_202")."</td><td class='row1' width='10%'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_222")."</td>";
    echo "</tr>";

    if($num != 0){
      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_description = mysql_result($return, $i, "o_description");
        $o_createdby = mysql_result($return, $i, "o_createdby");
        $o_type = mysql_result($return, $i, "o_type");
        $o_credit = mysql_result($return, $i, "o_credit");
        $o_enabled = mysql_result($return, $i, "o_enabled");
        $o_date = mysql_result($return, $i, "o_date");

        echo "<tr>";
        echo "<td class='row2'><a href='./edit_activity.php?aid=".$o_id."'>".$o_name."</a></td><td class='row2'>".$o_description."</td><td class='row2'>".$o_enabled."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

    // Get lessons list
    $query = "SELECT * FROM activities WHERE o_type='lsn'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_WIDTH') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_BORDER') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLPADDING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLSPACING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_ALIGN')){
      echo "<table border='".CFG_GETADMINACTIVITYLISTHTML_TABLE2_BORDER."' align='".CFG_GETADMINACTIVITYLISTHTML_TABLE2_ALIGN."' class='forumline' cellpadding='".CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLPADDING."' cellspacing='".CFG_GETADMINACTIVITYLISTHTML_TABLE2_CELLSPACING."' width='".CFG_GETADMINACTIVITYLISTHTML_TABLE2_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    $XRow2 = "25%";
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE2_ROW1_WIDTH')){
      $XRow2 = CFG_GETADMINACTIVITYLISTHTML_TABLE2_ROW1_WIDTH;
    }

    echo "<tr><td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_224")."</font><b></td></tr>";
    echo "<tr>";
    echo "<td class='row1' width='".$XRow2."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_201")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_202")."</td><td class='row1' width='10%'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_222")."</td>";
    echo "</tr>";

    if($num != 0){
      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_description = mysql_result($return, $i, "o_description");
        $o_createdby = mysql_result($return, $i, "o_createdby");
        $o_type = mysql_result($return, $i, "o_type");
        $o_credit = mysql_result($return, $i, "o_credit");
        $o_enabled = mysql_result($return, $i, "o_enabled");
        $o_date = mysql_result($return, $i, "o_date");

        echo "<tr>";
        echo "<td class='row2'><a href='./edit_activity.php?aid=".$o_id."'>".$o_name."</a></td><td class='row2'>".$o_description."</td><td class='row2'>".$o_enabled."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

    // Get other list
    $query = "SELECT * FROM activities WHERE o_type !='lsn' && o_type !='pzl'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_WIDTH') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_BORDER') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLPADDING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLSPACING') && defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_ALIGN')){
      echo "<table border='".CFG_GETADMINACTIVITYLISTHTML_TABLE3_BORDER."' align='".CFG_GETADMINACTIVITYLISTHTML_TABLE3_ALIGN."' class='forumline' cellpadding='".CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLPADDING."' cellspacing='".CFG_GETADMINACTIVITYLISTHTML_TABLE3_CELLSPACING."' width='".CFG_GETADMINACTIVITYLISTHTML_TABLE3_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    $XRow3 = "25%";
    if(defined('CFG_GETADMINACTIVITYLISTHTML_TABLE3_ROW1_WIDTH')){
      $XRow3 = CFG_GETADMINACTIVITYLISTHTML_TABLE3_ROW1_WIDTH;
    }

    echo "<tr><td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_223")."</font><b></td></tr>";
    echo "<tr>";
    echo "<td class='row1' width='".$XRow3."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_201")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_202")."</td><td class='row1' width='10%'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_222")."</td>";
    echo "</tr>";

    if($num != 0){
      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_description = mysql_result($return, $i, "o_description");
        $o_createdby = mysql_result($return, $i, "o_createdby");
        $o_type = mysql_result($return, $i, "o_type");
        $o_credit = mysql_result($return, $i, "o_credit");
        $o_enabled = mysql_result($return, $i, "o_enabled");
        $o_date = mysql_result($return, $i, "o_date");

        echo "<tr>";
        echo "<td class='row2'><a href='./edit_activity.php?aid=".$o_id."'>".$o_name."</a></td><td class='row2'>".$o_description."</td><td class='row2'>".$o_enabled."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * CreateNewActivity
  *
  */
  function CreateNewActivity($Name, $Description, $CreatedBy, $Type, $Credit){

    $nActivityID = 0;

    if($Credit == -1){
      $Credit = $this->GetActivityDefaultCredit();
    }

    $insert = "INSERT INTO activities VALUES(NULL, '".$Name."', '".$Description."', '".$CreatedBy."', '".$Type."', '".$Credit."', 'n', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nActivityID = trim(mysql_result($return,0,0));
    }

    return $nActivityID;

  }


  /**********************************************************************
  * GetActivityInfoByIDHTML
  *
  */
  function GetActivityInfoByIDHTML($AID){

    $query = "SELECT * FROM activities WHERE o_id='".$AID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_WIDTH') && defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_BORDER') && defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLPADDING') && defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLSPACING') && defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETACTIVITYINFOBYIDHTML_TABLE1_BORDER."' align='".CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETACTIVITYINFOBYIDHTML_TABLE1_CELLSPACING."' width='".CFG_GETACTIVITYINFOBYIDHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    $Rowx1 = "20%";
    if(defined('CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ROW1_WIDTH')){
      $Rowx1 = CFG_GETACTIVITYINFOBYIDHTML_TABLE1_ROW1_WIDTH;
    }

    echo "<tr><td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_221")."</font><b></td></tr>";

    if($num != 0){
      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_description = mysql_result($return, $i, "o_description");
        $o_createdby = mysql_result($return, $i, "o_createdby");
        $o_type = mysql_result($return, $i, "o_type");
        $o_credit = mysql_result($return, $i, "o_credit");
        $o_enabled = mysql_result($return, $i, "o_enabled");
        $o_date = mysql_result($return, $i, "o_date");

        echo "<tr>";
        echo "<td class='row1' width='".$Rowx1."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_220")."</td><td class='row2'>".$o_name."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_219")."</td>";
        echo "<td class='row2'>".$o_description."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_218")."</td><td class='row2'>".$o_createdby."</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_217")."</td><td class='row2'>".$o_type."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_216")."</td><td class='row2'>".$o_credit."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * CreateResource
  *
  */
  function CreateResource($AID, $Name, $optType, $Field1, $Field2, $FileName){

    if($optType == 'pgn'){
      $data = "<PGN>".$Field1."</PGN><FEN>".$Field2."</FEN>";
    }else{
      $data = "<NAME>".$FileName."</NAME><WIDTH>".$Field1."</WIDTH><HEIGHT>".$Field2."</HEIGHT>";
    }

    $insert = "INSERT INTO activity_resources VALUES(NULL, '".$AID."', '".$Name."', '".$data."', '".$optType."', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * CreateResourceHTMLList
  *
  */
  function CreateResourceHTMLList($AID){

    $query = "SELECT * FROM activity_resources WHERE o_activitiesid='".$AID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select name='slctResource'>";
    echo "<option value='-1' selected>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_215")."</option>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_type = mysql_result($return, $i, "o_type");

        echo "<option value='".$o_id."'>[".$o_type."] ".$o_name."</option>";

        $i++;
      }


    }

    echo "</select>";

  }


  /**********************************************************************
  * CreateActivityPage
  *
  */
  function CreateActivityPage($AID, $slctResource, $content2, $slctType, $txtsolution){

    if($slctResource == -1){
      $txtResource = "<RESOURCE></RESOURCE>";
    }else{
      $txtResource = "<RESOURCE>".$slctResource."</RESOURCE>";
    }

    $insert = "INSERT INTO activity_pages VALUES(NULL, '".$AID."', '".$txtResource."', '".addslashes($content2)."', '".$txtsolution."', '".$slctType."', NOW());";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * EnableActivity
  *
  */
  function EnableActivity($AID){

    if($this->GetActivityPageCount($AID) > 0){

      $update = "UPDATE activities SET o_enabled='y' WHERE o_id='".$AID."'";
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * DisableActivity
  *
  */
  function DisableActivity($AID){

    $update = "UPDATE activities SET o_enabled='n' WHERE o_id='".$AID."'";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * IsActivityEnabled
  *
  */
  function IsActivityEnabled($AID){

    $enabled = false;

    $query = "SELECT * FROM activities WHERE o_id='".$AID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_enabled = mysql_result($return, 0, "o_enabled");

      if($o_enabled == 'y'){
        $enabled = true;
      }

    }

    return $enabled;

  }


  /**********************************************************************
  * HandleActivityPageControlTypeAdmin
  *
  */
  function HandleActivityPageControlTypeAdmin($ActivityID, $PageIndex){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");
        $o_type = mysql_result($return,$PageIndex,"o_type");

        // Create the controls
        if($o_type == "tnf"){

          echo "<input type='button' name='btnTrue' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_213")."' class='mainoption' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd=true';\">";
          echo "<input type='button' name='btnFalse' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_212")."' class='mainoption' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd=false';\">";

        }elseif($o_type == "lan"){

          echo "<input type='text' name='txtAnsX' class='post'>";
          echo "<input type='button' name='btnLongAnswer' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_211")."' class='mainoption' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$ActivityID."&pgi=".$PageIndex."&cmd='+document.frmActivity.txtAnsX.value+'';\">";

        }

        if($o_solution != ""){

          echo "<br><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_214")."</b> ".$o_solution;

        }

      }

    }

  }


  /**********************************************************************
  * GetActivityDefaultCredit
  *
  */
  function GetActivityDefaultCredit(){

    $credit = 0;

    $query = "SELECT * FROM activity_config WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $credit = mysql_result($return, 0, "o_credit");
    }

    return $credit;

  }


  /**********************************************************************
  * IsActivitysCreditFree
  *
  */
  function IsActivitysCreditFree(){

    $free = false;

    $query = "SELECT * FROM activity_config WHERE o_id=1 AND o_free='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $free = true;
    }

    return $free;

  }


  /**********************************************************************
  * GetActivityListForPurchaseHTML
  *
  */
  function GetActivityListForPurchaseHTML($Type, $PID){

    $txtActivityType = "";
    $txttag = "";
    if($Type == "pzl"){
      $txtActivityType = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_210")."";
      $txttag = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_209")."";
    }elseif($Type == "lsn"){
      $txtActivityType = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_208")."";
      $txttag = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_207")."";
    }else{
      $txtActivityType = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_206")."";
      $txttag = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_205")."";
    }

    $strSQLp1 = "o_type = '".$Type."'";

    if($Type == "other"){
      $strSQLp1 = "o_type != 'pzl' AND o_type != 'lsn'";
    }

    $query = "SELECT * FROM activities WHERE o_enabled='y' AND ".$strSQLp1." ORDER BY o_name ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_WIDTH') && defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_BORDER') && defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLPADDING') && defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLSPACING') && defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_WIDTH."' border='".CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_BORDER."' cellpadding='".CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_CELLSPACING."' align='".CFG_GETACTIVITYLISTFORPURCHASEHTML_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='95%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    // Skin table settings
    $rowx1 = "20%";
    if(defined('CFG_GETACTIVITYLISTFORPURCHASEHTML_ROW1_WIDTH')){
      $rowx1 = CFG_GETACTIVITYLISTFORPURCHASEHTML_ROW1_WIDTH;
    }

    echo "<tr><td colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'>".$txtActivityType."</font><b></td></tr>";
    echo "<tr><td class='row1' width='".$rowx1."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_201")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_202")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_203")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_204")."</td></tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = mysql_result($return, $i, "o_id");
        $o_name = mysql_result($return, $i, "o_name");
        $o_description = mysql_result($return, $i, "o_description");
        $o_createdby = mysql_result($return, $i, "o_createdby");
        $o_type = mysql_result($return, $i, "o_type");
        $o_credit = mysql_result($return, $i, "o_credit");
        $o_enabled = mysql_result($return, $i, "o_enabled");
        $o_date = mysql_result($return, $i, "o_date");

        $query1 = "SELECT * FROM player_purchased_activities WHERE o_playerid='".$PID."' AND o_activitiesid='".$o_id."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($this->IsActivitysCreditFree()){
          $o_credit = "Free";
        }

        if($num1 == 0){

          echo "<tr>";
          echo "<td class='row2' width='".$rowx1."' valign='top'>".$o_name."</td><td class='row2' valign='top'>".$o_description."</td><td class='row2' valign='top'>".$o_credit."</td>";
          echo "<td class='row2' valign='top'><input type='button' name='btnGetActivity' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_200")."' class='mainoption' onclick=\"window.location='./chess_get_activities.php?tag=".$txttag."&id=".$o_id."'\"></td>";
          echo "</tr>";

        }

        $i++;
      }

    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * IsActivityPurchased
  *
  */
  function IsActivityPurchased($AID, $PID){

    $purchased = false;

    $query = "SELECT * FROM player_purchased_activities WHERE o_playerid='".$PID."' AND o_activitiesid='".$AID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $purchased = true;
    }

    return $purchased;

  }


  /**********************************************************************
  * PlayerPurchaseActivity
  *
  */
  function PlayerPurchaseActivity($PID, $AID){

    $query = "SELECT * FROM activities WHERE o_id='".$AID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_credit = mysql_result($return, 0, "o_credit");

      if(!$this->IsActivityPurchased($AID, $PID) && ($this->GetPlayerCredits($PID) >= $o_credit)){

        if($this->IsActivitysCreditFree()){
          $o_credit = 0;
        }else{
          $this->RemovePlayerCredits($PID, $o_credit);
        }

        $insert = "INSERT INTO player_purchased_activities VALUES(NULL, '".$PID."', '".$AID."', '".$o_credit."', 'n', NOW())";
        mysql_query($insert, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * DeleteActivityPage
  *
  */
  function DeleteActivityPage($ActivityID, $PageIndex){

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");

        $delete = "DELETE FROM activity_pages WHERE o_id='".$o_id."'";
        mysql_query($delete, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * MoveActivityPage
  *
  */
  function MoveActivityPage($ActivityID, $PageIndex, $Direction){

    $strreturnmsg = "nm";

    $query = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      if($PageIndex < $num){

        $o_id = mysql_result($return,$PageIndex,"o_id");
        $o_activitiesid = mysql_result($return,$PageIndex,"o_activitiesid");
        $o_content1 = mysql_result($return,$PageIndex,"o_content1");
        $o_content2 = mysql_result($return,$PageIndex,"o_content2");
        $o_solution = mysql_result($return,$PageIndex,"o_solution");
        $o_type = mysql_result($return,$PageIndex,"o_type");
        $o_date = mysql_result($return,$PageIndex,"o_date");

        /////////////////////////////////////////////////
        //Move directon left
        if($Direction == "ml"){

          // Get previous record
          $query1 = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
          $return1 = mysql_query($query1, $this->link) or die(mysql_error());
          $num1 = mysql_numrows($return1);

          if($num1 != 0){

            $nPreviousActivityID = $PageIndex - 1;

            if($nPreviousActivityID < $num  && $nPreviousActivityID >= 0){

              $o_id1 = mysql_result($return,$nPreviousActivityID,"o_id");
              $o_activitiesid1 = mysql_result($return,$nPreviousActivityID,"o_activitiesid");
              $o_content11 = mysql_result($return,$nPreviousActivityID,"o_content1");
              $o_content21 = mysql_result($return,$nPreviousActivityID,"o_content2");
              $o_solution1 = mysql_result($return,$nPreviousActivityID,"o_solution");
              $o_type1 = mysql_result($return,$nPreviousActivityID,"o_type");
              $o_date1 = mysql_result($return,$nPreviousActivityID,"o_date");

              // Delete Record to move
              $delete1 = "DELETE FROM activity_pages WHERE o_id='".$o_id."'";
              mysql_query($delete1, $this->link) or die(mysql_error());

              // Delete Record left of record to move
              $delete2 = "DELETE FROM activity_pages WHERE o_id='".$o_id1."'";
              mysql_query($delete2, $this->link) or die(mysql_error());

              // Create the new records
              $insert1 = "INSERT INTO activity_pages VALUES('".$o_id1."', '".$o_activitiesid."', '".$o_content1."', '".$o_content2."', '".$o_solution."', '".$o_type."', '".$o_date."')";
              mysql_query($insert1, $this->link) or die(mysql_error());

              // Create the new records
              $insert2 = "INSERT INTO activity_pages VALUES('".$o_id."', '".$o_activitiesid1."', '".$o_content11."', '".$o_content21."', '".$o_solution1."', '".$o_type1."', '".$o_date1."')";
              mysql_query($insert2, $this->link) or die(mysql_error());

              $strreturnmsg = "ml";

            }

          }

        /////////////////////////////////////////////////
        //Move directon right
        }elseif($Direction == "mr"){

          // Get previous record
          $query1 = "SELECT * FROM activity_pages WHERE o_activitiesid='".$ActivityID."' ORDER BY o_id ASC";
          $return1 = mysql_query($query1, $this->link) or die(mysql_error());
          $num1 = mysql_numrows($return1);

          if($num1 != 0){

            $nNextActivityID = $PageIndex + 1;

            if($nNextActivityID < $num && $nNextActivityID >= 0){

              $o_id1 = mysql_result($return,$nNextActivityID,"o_id");
              $o_activitiesid1 = mysql_result($return,$nNextActivityID,"o_activitiesid");
              $o_content11 = mysql_result($return,$nNextActivityID,"o_content1");
              $o_content21 = mysql_result($return,$nNextActivityID,"o_content2");
              $o_solution1 = mysql_result($return,$nNextActivityID,"o_solution");
              $o_type1 = mysql_result($return,$nNextActivityID,"o_type");
              $o_date1 = mysql_result($return,$nNextActivityID,"o_date");

              // Delete Record to move
              $delete1 = "DELETE FROM activity_pages WHERE o_id='".$o_id."'";
              mysql_query($delete1, $this->link) or die(mysql_error());

              // Delete Record right of record to move
              $delete2 = "DELETE FROM activity_pages WHERE o_id='".$o_id1."'";
              mysql_query($delete2, $this->link) or die(mysql_error());

              // Create the new records
              $insert1 = "INSERT INTO activity_pages VALUES('".$o_id1."', '".$o_activitiesid."', '".$o_content1."', '".$o_content2."', '".$o_solution."', '".$o_type."', '".$o_date."')";
              mysql_query($insert1, $this->link) or die(mysql_error());

              // Create the new records
              $insert2 = "INSERT INTO activity_pages VALUES('".$o_id."', '".$o_activitiesid1."', '".$o_content11."', '".$o_content21."', '".$o_solution1."', '".$o_type1."', '".$o_date1."')";
              mysql_query($insert2, $this->link) or die(mysql_error());

              $strreturnmsg = "mr";

            }

          }

        }

      }

    }

    return $strreturnmsg;

  }


  /**********************************************************************
  * GetPurchaseCreditHTMLForm
  *
  */
  function GetPurchaseCreditHTMLForm($PID, $Root_Path){

    // Skin table settings
    if(defined('CFG_GETPURCHASECREDITHTMLFORM_TABLE1_WIDTH') && defined('CFG_GETPURCHASECREDITHTMLFORM_TABLE1_BORDER') && defined('CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLPADDING') && defined('CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLSPACING') && defined('CFG_GETPURCHASECREDITHTMLFORM_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETPURCHASECREDITHTMLFORM_TABLE1_WIDTH."' border='".CFG_GETPURCHASECREDITHTMLFORM_TABLE1_BORDER."' cellpadding='".CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLPADDING."' cellspacing='".CFG_GETPURCHASECREDITHTMLFORM_TABLE1_CELLSPACING."' align='".CFG_GETPURCHASECREDITHTMLFORM_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='95%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_199")."</font><b></td></tr>";
    echo "<tr><td colspan='2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_198")."</b></td></tr>";
    echo "<tr><td colspan='2' class='row2'>";

    $query2 = "SELECT * FROM player WHERE player_id='".$PID."'";
    $return2 = mysql_query($query2, $this->link) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    if($num2 != 0){
      $userid = mysql_result($return2, 0, "userid");
      $email = mysql_result($return2, 0, "email");

      echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_197")." ".$PID."<input type='hidden' name='txtPID' value='".$PID."'><br>";
      echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_196")." ".$userid."<input type='hidden' name='txtUID' value='".$userid."'><br>";
      echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_195")." ".$email."<input type='hidden' name='txtEmail' value='".$email."'><br>";

    }

    echo "</td></tr>";
    echo "<tr><td colspan='2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_194")."</b></td></tr>";

    $query = "SELECT * FROM admin_player_credits WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $txtExchangeRate = "";

    if($num != 0){

      $paypalcurrency = "";

      $query1 = "SELECT * FROM c4m_paypalaccount WHERE p_id=1";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      if($num1 != 0){

        $paypalcurrency = mysql_result($return1, 0, "p_currency");
        echo "<input type='hidden' name='txtppcurrency' value='".$paypalcurrency."'>";

      }

      $o_exchangerate = mysql_result($return,0,"o_exchangerate");
      $txtExchangeRate = "1 ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_193")." = ".number_format($o_exchangerate, 2, '.', '')." ".$paypalcurrency;
      echo "<input type='hidden' name='txtexchangerate' value='".$o_exchangerate."'>";
    }

    // Skin table settings
    if(defined('CFG_GETPURCHASECREDITHTMLFORM_ROW1_WIDTH')){
      echo "<tr><td class='row1' width='".CFG_GETPURCHASECREDITHTMLFORM_ROW1_WIDTH."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_192")."</td><td class='row2'>".$txtExchangeRate."</td></tr>";
    }else{
      echo "<tr><td class='row1' width='30%'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_192")."</td><td class='row2'>".$txtExchangeRate."</td></tr>";
    }

    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_191")."</td><td class='row2'><input type='text' name='txtcredit' value='' size='30' class='post'></td></tr>";
    echo "<tr><td colspan='2' class='row1' align='center'><img src='".$Root_Path."skins/".$this->SkinsLocation."/images/paypal1.gif'><br><input type='submit' name='cmdPurchase' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_190")."' class='mainoption'></td></tr>";

    echo "</table>";

  }


  /**********************************************************************
  * PurchaseCredits
  *
  */
  function PurchaseCredits($PID, $UID, $Email, $Credits, $ExchangeRate, $TotalAmount){

    $nCRID = 0;

    $insert = "INSERT INTO admin_player_credits_request VALUES(NULL, '".$PID."', '".$UID."', '".$Email."', '".$Credits."', '".$ExchangeRate."', '".$TotalAmount."', 'n', NOW())";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nCRID = trim(mysql_result($return,0,0));
    }

    return $nCRID;

  }


  /**********************************************************************
  * GetCreditRequestsAdminHTML
  *
  */
  function GetCreditRequestsAdminHTML(){

    $query = "SELECT * FROM admin_player_credits_request WHERE o_status='n'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Skin table settings
    if(defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE1_WIDTH') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE1_BORDER') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLPADDING') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLSPACING') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_GETCREDITREQUESTSADMINHTML_TABLE1_BORDER."' align='".CFG_GETCREDITREQUESTSADMINHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_GETCREDITREQUESTSADMINHTML_TABLE1_CELLSPACING."' width='".CFG_GETCREDITREQUESTSADMINHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    echo "<tr>";
    echo "<td class='row2'>";
    echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_189")."";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = trim(mysql_result($return,$i,"o_id"));
        $o_playerid = trim(mysql_result($return,$i,"o_playerid"));
        $o_userid = trim(mysql_result($return,$i,"o_userid"));
        $o_email = trim(mysql_result($return,$i,"o_email"));
        $o_credits = trim(mysql_result($return,$i,"o_credits"));
        $o_exchangerate = trim(mysql_result($return,$i,"o_exchangerate"));
        $o_totalamount = trim(mysql_result($return,$i,"o_totalamount"));
        $o_status = trim(mysql_result($return,$i,"o_status"));
        $o_date = trim(mysql_result($return,$i,"o_date"));

        echo "<br>";

        // Skin table settings
        if(defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_WIDTH') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_BORDER') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLPADDING') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLSPACING') && defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ALIGN')){
          echo "<table border='".CFG_GETCREDITREQUESTSADMINHTML_TABLE2_BORDER."' align='".CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ALIGN."' class='forumline' cellpadding='".CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLPADDING."' cellspacing='".CFG_GETCREDITREQUESTSADMINHTML_TABLE2_CELLSPACING."' width='".CFG_GETCREDITREQUESTSADMINHTML_TABLE2_WIDTH."'>";
        }else{
          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
        }

        $xRow1 = "30%";
        if(defined('CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ROW1_WIDTH')){
          $xRow1 = CFG_GETCREDITREQUESTSADMINHTML_TABLE2_ROW1_WIDTH;
        }

        echo "<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_188")." ".$o_id."</font><b></td></tr>";
        echo "<tr><td class='row1' width='".$xRow1."'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_187")."</td><td class='row2'>".$o_playerid."</td></tr>";
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_186")."</td><td class='row2'>".$o_userid."</td></tr>";
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_185")."</td><td class='row2'>".$o_email."</td></tr>";
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_184")."</td><td class='row2'>".$o_credits."</td></tr>";
        echo "<tr><td class='row1'><b>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_183")."</b></td><td class='row2'><b>$".number_format($o_totalamount,2,'.', '')."</b></td></tr>";
        echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_182")."</td><td class='row2'>".$o_date."</td></tr>";
        echo "<tr><td class='row1' colspan='2' align='right'>";

        echo "<input type='button' name='btnAccept' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_181")."' class='mainoption' onclick=\"window.location='./cfg_player_credit_requests.php?rid=".$o_id."&tag=accept'\">";
        echo "<input type='button' name='btnDecline' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_180")."' class='mainoption' onclick=\"window.location='./cfg_player_credit_requests.php?rid=".$o_id."&tag=decline'\">";

        echo "</td></tr>";
        echo "<br>";

        echo "</table>";


        $i++;
      }

    }

  }


  /**********************************************************************
  * AcceptCreditRequest
  *
  */
  function AcceptCreditRequest($RID){

    $query = "SELECT * FROM admin_player_credits_request WHERE o_id='".$RID."' AND o_status='n'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_id = trim(mysql_result($return,0,"o_id"));
      $o_playerid = trim(mysql_result($return,0,"o_playerid"));
      $o_email = trim(mysql_result($return,0,"o_email"));
      $o_credits = trim(mysql_result($return,0,"o_credits"));

      $update = "UPDATE admin_player_credits_request SET o_status='y' WHERE o_id='".$RID."'";
      mysql_query($update, $this->link) or die(mysql_error());

      // Update player credits
      $this->AddPlayerCredits($o_playerid, $o_credits);

    }

  }


  /**********************************************************************
  * DeclineCreditRequest
  *
  */
  function DeclineCreditRequest($RID){

    $query = "SELECT * FROM admin_player_credits_request WHERE o_id='".$RID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_id = trim(mysql_result($return,0,"o_id"));
      $o_playerid = trim(mysql_result($return,0,"o_playerid"));
      $o_email = trim(mysql_result($return,0,"o_email"));
      $o_credits = trim(mysql_result($return,0,"o_credits"));

      $update = "UPDATE admin_player_credits_request SET o_status='d' WHERE o_id='".$RID."'";
      mysql_query($update, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * IsCreditsSystemEnabled
  *
  */
  function IsCreditsSystemEnabled(){

    $enabled = false;

    $query = "SELECT * FROM admin_player_credits WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $enabled = true;
    }

    return $enabled;

  }


  /**********************************************************************
  * GetActivityConfigInfo
  *
  */
  function GetActivityConfigInfo(&$Free, &$Credit){

    $query = "SELECT * FROM activity_config WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $Free = trim(mysql_result($return,0,"o_free"));
      $Credit = trim(mysql_result($return,0,"o_credit"));

    }

  }


  /**********************************************************************
  * UpdateActivityConfigInfo
  *
  */
  function UpdateActivityConfigInfo($Free, $Credit){

    $update = "UPDATE activity_config SET o_free='".$Free."', o_credit='".$Credit."' WHERE o_id=1";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetPlayerCreditConfigInfo
  *
  */
  function GetPlayerCreditConfigInfo(&$Credits, &$ExchangeRate){

    $query = "SELECT * FROM admin_player_credits WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $Credits = trim(mysql_result($return,0,"o_credits"));
      $ExchangeRate = trim(mysql_result($return,0,"o_exchangerate"));

    }

  }


  /**********************************************************************
  * UpdatePlayerCreditConfigInfo
  *
  */
  function UpdatePlayerCreditConfigInfo($Credits, $ExchangeRate){

    $query = "SELECT * FROM admin_player_credits WHERE o_id=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE admin_player_credits SET o_credits='".$Credits."', o_exchangerate='".$ExchangeRate."' WHERE o_id=1";
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO admin_player_credits VALUES(1, '".$Credits."', '".$ExchangeRate."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * isBoardCustomerSettingDragDrop
  *
  */
  function isBoardCustomerSettingDragDrop($PlayerID){

    $bDragDrop = false;

    $query = "SELECT * FROM chess_board_type WHERE o_playerid=".$PlayerID." AND o_isdragdrop=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bDragDrop = true;
    }

    return $bDragDrop;

  }


  /**********************************************************************
  * SetBoardCustomerSetting
  *
  */
  function SetBoardCustomerSetting($PlayerID, $setting){

    $query = "SELECT * FROM chess_board_type WHERE o_playerid=".$PlayerID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $update = "UPDATE chess_board_type SET o_isdragdrop=".$setting." WHERE o_playerid=".$PlayerID."";
      mysql_query($update, $this->link) or die(mysql_error());
    }else{
      $insert = "INSERT INTO chess_board_type VALUES(".$PlayerID.", ".$setting.")";
      mysql_query($insert, $this->link) or die(mysql_error());
    }

  }


  /**********************************************************************
  * GetOnlinePlayersForMobile
  *
  */
  function GetOnlinePlayersForMobile(){

    $query = "SELECT active_sessions.player_id, active_sessions.session_time, c4m_avatars.a_imgname, player.userid FROM active_sessions LEFT JOIN c4m_avatars ON active_sessions.player_id = c4m_avatars.a_playerid LEFT JOIN player ON active_sessions.player_id = player.player_id ORDER BY player.userid ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $playerid = trim(mysql_result($return,$i,"player_id"));
        $sessiontime = trim(mysql_result($return,$i,"session_time"));
		$avatar_url = trim(mysql_result($return, $i, "a_imgname"));
		$avatar_url = $avatar_url == "" ? "avatars/noimage.jpg" : "avatars/$avatar_url";
		$userid = trim(mysql_result($return, $i, "userid"));
        //$userid = $this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $playerid);

        echo "<PLAYERS>\n";

        echo "<PLAYERID>";
        echo $playerid;
        echo "</PLAYERID>\n";

        echo "<USERID>";
        echo $userid;
        echo "</USERID>\n";

        echo "<SESSIONTIME>";
        echo $sessiontime;
        echo "</SESSIONTIME>\n";
		
		echo "<AVATAR>";
		echo $avatar_url;
		echo "</AVATAR>\n";

        echo "</PLAYERS>\n";

        $i++;

      }

    }

  }


  /**********************************************************************
  * GetPlayerStatusInformationForMobile
  *
  */
  function GetPlayerStatusInformationForMobile($ID){

    $wins = 0;
    $loss = 0;
    $draws = 0;

    $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $ID, $wins, $loss, $draws);

    // Points
    $points = 0;

    if($this->ELOIsActive()){
      $points = $this->ELOGetRating($ID);
    }else{
      $points = $this->GetPointValue($wins, $loss, $draws);
    }

    $this->SetChessPointCacheData($ID, $points);

    // Get member signup stats
    $daysx = 0;
    $datex = "";

    $query = "SELECT * FROM player WHERE player_id = ".$ID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $signup_time = mysql_result($return,0,"signup_time");

      // Get difference between the dates.
      $firstdate = time();
      $seconddate = $signup_time; // Current Date

      $difference = $firstdate - $seconddate;
      $diffday = $difference/86400;

      //format the difference
      $hours = date("H",$difference);
      $minutes = date("m",$difference);
      $seconds = date("s",$difference);

      $daysx = (int) $diffday;
      $datex = date("m-d-Y",$signup_time);

    }

    // Date Of last login
    $lastlogindate = "";

    $query = "SELECT * FROM player_last_login WHERE o_playerid = '".$ID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $lastlogindate = mysql_result($return,0,"o_date");
    }

    // Date of last move
    $lastmovedate = "";

    $query = "SELECT * FROM move_history WHERE player_id = '".$ID."' ORDER BY move_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $time1 = mysql_result($return,0,"time");
      $lastmovedate = date("Y-m-d G:i:s", $time1);
    }

    // Get other info
    $txtRealName = "";
    $txtLocation = "";
    $txtAge = "";
    $txtSelfRating = "";
    $txtComment = "";
    $txtChessPlayer = "";
    $this->GetPersonalInformation($this->ChessCFGFileLocation, $txtRealName, $txtLocation, $txtAge, $txtSelfRating, $txtComment, $txtChessPlayer, $ID);

    // Is player in club
    $Club = "false";
    $clubid = 0;

    $querycb = "SELECT * FROM chess_club_members WHERE o_playerid ='".$ID."' AND o_active='y'";
    $returncb = mysql_query($querycb, $this->link) or die(mysql_error());
    $numcb = mysql_numrows($returncb);

    if($numcb != 0){

      $clubid = mysql_result($returncb,$i,"o_chessclubid");

      $querycb1 = "SELECT * FROM chess_club_members WHERE o_chessclubid='".$clubid."' AND o_playerid ='".$ID."'";
      $returncb1 = mysql_query($querycb1, $this->link) or die(mysql_error());
      $numcb1 = mysql_numrows($returncb1);

      if($numcb1 != 0){
        $Club = "true";
      }

    }

    // Display the info
    echo "<STATISTICS>\n";

    echo "<WIN>";
    echo $wins;
    echo "</WIN>\n";

    echo "<LOSS>";
    echo $loss;
    echo "</LOSS>\n";

    echo "<DRAW>";
    echo $draws;
    echo "</DRAW>\n";

    echo "<POINTS>";
    echo $points;
    echo "</POINTS>\n";

    echo "<MEMBERDAYS>";
    echo $daysx;
    echo "</MEMBERDAYS>\n";

    echo "<SIGNUPDATE>";
    echo $datex;
    echo "</SIGNUPDATE>\n";

    echo "<LASTLOGINDATE>";
    echo $lastlogindate;
    echo "</LASTLOGINDATE>\n";

    echo "<LASTMOVEDATE>";
    echo $lastmovedate;
    echo "</LASTMOVEDATE>\n";

    echo "<REALNAME>";
    echo $txtRealName;
    echo "</REALNAME>\n";

    echo "<LOCATION>";
    echo $txtLocation;
    echo "</LOCATION>\n";

    echo "<AGE>";
    echo $txtAge;
    echo "</AGE>\n";

    echo "<SELFRATING>";
    echo $txtSelfRating;
    echo "</SELFRATING>\n";

    echo "<COMMENT>";
    echo $txtComment;
    echo "</COMMENT>\n";

    echo "<CHESSPLAYER>";
    echo $txtChessPlayer;
    echo "</CHESSPLAYER>\n";

    echo "<CLUBNAME>";
    if($clubid != 0){
      echo $this->GetClubNameById($clubid);
    }
    echo "</CLUBNAME>\n";

    echo "</STATISTICS>\n";

  }


  /**********************************************************************
  * GetInboxForMobile
  *
  */
  function GetInboxForMobile($ConfigFile, $ID){

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE player_id =".$ID."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Text Messages
      /////////////////////////////////////
      $i = 0;
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          $msg = substr($message, 10);
          LIST($name, $message) = explode("-", $msg);

          echo "<MESSAGE>\n";

          echo "<ID>";
          echo $inbox_id;
          echo "</ID>\n";

          echo "<TYPE>";
          echo "TEXT_MESSAGE";
          echo "</TYPE>\n";

          echo "<SENDERNAME>";
          echo $name;
          echo "</SENDERNAME>\n";

          echo "<PLAYERID>";
          echo "</PLAYERID>\n";

          echo "<GAMEID>";
          echo "</GAMEID>\n";

          echo "<TOURNAMENTID>";
          echo "</TOURNAMENTID>\n";

          echo "<MSGTEXTRAW>";
          echo $message;
          echo "</MSGTEXTRAW>\n";

          echo "<MSGTEXT>";
          echo $this->ReadMessageForMobile($ConfigFile, $inbox_id);
          echo "</MSGTEXT>\n";

          echo "<UNIXTIME>";
          echo $posted;
          echo "</UNIXTIME>\n";

          echo "</MESSAGE>\n";

        }

        $i++;

      }


      // Move Message
      /////////////////////////////////////
      $i = 0;
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);

          echo "<MESSAGE>\n";

          echo "<ID>";
          echo $inbox_id;
          echo "</ID>\n";

          echo "<TYPE>";
          echo "MOVE_MESSAGE";
          echo "</TYPE>\n";

          echo "<SENDERNAME>";
          echo "</SENDERNAME>\n";

          echo "<PLAYERID>";
          echo $player_id;
          echo "</PLAYERID>\n";

          echo "<GAMEID>";
          echo "</GAMEID>\n";

          echo "<TOURNAMENTID>";
          echo "</TOURNAMENTID>\n";

          echo "<MSGTEXTRAW>";
          echo $message;
          echo "</MSGTEXTRAW>\n";

          echo "<MSGTEXT>";
          echo $this->ReadMessageForMobile($ConfigFile, $inbox_id);
          echo "</MSGTEXT>\n";

          echo "<UNIXTIME>";
          echo $posted;
          echo "</UNIXTIME>\n";

          echo "</MESSAGE>\n";

        }

        $i++;

      }


      // Challenge Message
      /////////////////////////////////////
      $i = 0;
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $GameId = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          echo "<MESSAGE>\n";

          echo "<ID>";
          echo $inbox_id;
          echo "</ID>\n";

          echo "<TYPE>";
          echo "CHALLENGE_MESSAGE";
          echo "</TYPE>\n";

          echo "<SENDERNAME>";
          echo "</SENDERNAME>\n";

          echo "<PLAYERID>";
          echo $player_id;
          echo "</PLAYERID>\n";

          echo "<GAMEID>";
          echo $GameId;
          echo "</GAMEID>\n";

          echo "<TOURNAMENTID>";
          echo "</TOURNAMENTID>\n";

          echo "<MSGTEXTRAW>";
          echo $message;
          echo "</MSGTEXTRAW>\n";

          echo "<MSGTEXT>";
          echo $this->ReadMessageForMobile($ConfigFile, $inbox_id);
          echo "</MSGTEXT>\n";

          echo "<UNIXTIME>";
          echo $posted;
          echo "</UNIXTIME>\n";

          echo "</MESSAGE>\n";

        }

        $i++;

      }


      // Tournament Message
      /////////////////////////////////////
      $i = 0;
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));

          echo "<MESSAGE>\n";

          echo "<ID>";
          echo $inbox_id;
          echo "</ID>\n";

          echo "<TYPE>";
          echo "TOURNAMENT_MESSAGE";
          echo "</TYPE>\n";

          echo "<SENDERNAME>";
          echo "</SENDERNAME>\n";

          echo "<PLAYERID>";
          echo "</PLAYERID>\n";

          echo "<GAMEID>";
          echo "</GAMEID>\n";

          echo "<TOURNAMENTID>";
          echo $tid;
          echo "</TOURNAMENTID>\n";

          echo "<MSGTEXTRAW>";
          echo $message;
          echo "</MSGTEXTRAW>\n";

          echo "<MSGTEXT>";
          echo $this->ReadMessageForMobile($ConfigFile, $inbox_id);
          echo "</MSGTEXT>\n";

          echo "<UNIXTIME>";
          echo $posted;
          echo "</UNIXTIME>\n";

          echo "</MESSAGE>\n";

        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * ReadMessageForMobile
  *
  */
  function ReadMessageForMobile($ConfigFile, $InboxID){

    $TotalMessage = "";

    //Get game info
    $query = "SELECT * FROM c4m_msginbox WHERE inbox_id =".$InboxID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $inbox_id = trim(mysql_result($return,$i,"inbox_id"));
        $message = trim(mysql_result($return,$i,"message"));
        $posted = trim(mysql_result($return,$i,"msg_posted"));

        if(substr($message, 0, 2) == "C0"){

          // Format Message
          $msg = substr($message, 10);
          LIST($name, $msg) = explode("-", $msg);
          $TotalMessage = $name." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_44")." ".$msg."";

        }

        if(substr($message, 0, 2) == "M0"){

          $player_id = substr($message, 11,8);
          $Move = substr($message, (strlen($message)-5),5);
          $gameid = substr($message, 19, (strlen($message)-24));

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_45")." ".$gameid." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_46")." ".$Move."";

        }

        if(substr($message, 0, 2) == "GC"){

          $TextCount = substr($message, 2,8);
          $gameid = substr($message, 10,((int)$TextCount-8));
          $player_id = substr($message, (strlen($message)-8), 8);

          $TotalMessage = $this->GetUserIDByPlayerID($ConfigFile,$player_id)." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_47")." ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_48")." ".$gameid."";

        }

        if(substr($message, 0, 2) == "T0"){

          $tid = substr($message, 3,(strlen($message)-3));
          $tname = $this->GetTournamentName($ConfigFile,$tid);

          $TotalMessage = "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_49")." \"".$tname."\" ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_50")."";

        }

        $i++;

      }

    }

    return $TotalMessage;

  }


  /**********************************************************************
  * ListAvailablePlayersAForMobile
  *
  */
  function ListAvailablePlayersAForMobile($ConfigFile, $oplayerid){

    $aLetters = array($this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_1"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_2"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27"),
                      $this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28"));

    $aLettersCount = count($aLetters);

    $a=0;
    while($a < $aLettersCount){

      // Set up the query string
      if($aLetters[$a] != "" && ucwords($aLetters[$a]) != ""){
        $query = "SELECT * FROM player WHERE (LEFT(userid,1) = '".$aLetters[$a]."' OR LEFT(userid,1) = '".ucwords($aLetters[$a])."') ORDER BY userid ASC";
      }else{

        $query = "SELECT * FROM player
                  WHERE (LEFT(userid,1)
                  NOT IN('".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28")."')
                  OR LEFT(userid,1)
                  NOT IN('".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27")."',
                         '".$this->GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28")."'))
                  ORDER BY userid ASC";

      }

      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      // Place the results in the array
      if($num != 0){

        $PlayerPoints = array();

        $i = 0;
        $ia = 0;
        while($i < $num){

          $player_id = trim(mysql_result($return,$i,"player_id"));
          $userid = trim(mysql_result($return,$i,"userid"));
          $signup_time  = trim(mysql_result($return,$i,"signup_time"));
          $email = trim(mysql_result($return,$i,"email"));

          if($this->IsPlayerDisabled($player_id) == false){
            $wins = 0;
            $loss = 0;
            $draws = 0;

            $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);

            if($this->ELOIsActive()){
              $points = $this->ELOGetRating($player_id);
            }else{
              $points = $this->GetPointValue($wins, $loss, $draws);
            }

            $this->GetPointRanking($points, $wins);

            $PlayerPoints[$ia]['PlayerID'] = $player_id;
            $PlayerPoints[$ia]['UserID'] = $userid;
            $PlayerPoints[$ia]['SignUpTime'] = $signup_time;
            $PlayerPoints[$ia]['Email'] = $email;
            $PlayerPoints[$ia]['Points'] = $points;

            $ia++;

          }

          $i++;

        }

        if(count($PlayerPoints) != 0){

          // Sort the array
          $PlayerPoints = $this->array_csort($PlayerPoints,'Points',SORT_DESC, SORT_NUMERIC);

          $ncount = count($PlayerPoints);
          $ii = 0;

          while($ii < $ncount){

            if($this->IsPlayerOnline($ConfigFile, $PlayerPoints[$ii]['PlayerID'])){
              $status = "PLAYER_ONLINE";
            }else{
              $status = "PLAYER_OFFLINE";
            }

            // Is player in buddy list
            $BuddyList = "false";

            $querybl = "SELECT * FROM c4m_buddylist WHERE buddy_id='".$PlayerPoints[$ii]['PlayerID']."' AND player_id ='".$oplayerid."'";
            $returnbl = mysql_query($querybl, $this->link) or die(mysql_error());
            $numbl = mysql_numrows($returnbl);

            if($numbl != 0){
              $BuddyList = "true";
            }

            // Is player in club
            $Club = "false";
            $clubid = 0;

            $querycb = "SELECT * FROM chess_club_members WHERE o_playerid ='".$PlayerPoints[$ii]['PlayerID']."' AND o_active='y'";
            $returncb = mysql_query($querycb, $this->link) or die(mysql_error());
            $numcb = mysql_numrows($returncb);

            if($numcb != 0){

              $clubid = mysql_result($returncb, 0,"o_chessclubid");

              $querycb1 = "SELECT * FROM chess_club_members WHERE o_chessclubid='".$clubid."' AND o_playerid ='".$PlayerPoints[$ii]['PlayerID']."'";
              $returncb1 = mysql_query($querycb1, $this->link) or die(mysql_error());
              $numcb1 = mysql_numrows($returncb1);

              if($numcb1 != 0){
                $Club = "true";
              }

            }

            echo "<PLAYERS>\n";

            echo "<PID>";
            echo $PlayerPoints[$ii]['PlayerID'];
            echo "</PID>\n";

            echo "<USERID>";
            echo $PlayerPoints[$ii]['UserID'];
            echo "</USERID>\n";

            echo "<SIGNUPTIME>";
            echo date("m-d-Y",$PlayerPoints[$ii]['SignUpTime']);
            echo "</SIGNUPTIME>\n";

            echo "<POINTS>";
            echo $PlayerPoints[$ii]['Points'];
            echo "</POINTS>\n";

            echo "<INCLUB>";
            echo $Club;
            echo "</INCLUB>\n";

            echo "<CLUBNAME>";
            if($clubid != 0){
              echo $this->GetClubNameById($clubid);
            }
            echo "</CLUBNAME>\n";

            echo "<INBUDDYLIST>";
            echo $BuddyList;
            echo "</INBUDDYLIST>\n";

            echo "<STATUS>";
            echo $status;
            echo "</STATUS>\n";

            echo "</PLAYERS>\n";

            $ii++;

          }

        }

      }

      $a++;

    }

  }


  /**********************************************************************
  * GetAllGamesByPlayerIDForMobile
  *
  */
  function GetAllGamesByPlayerIDForMobile($ConfigFile, $ID){

    //Get game where the player is white
    $queryw = "SELECT * FROM game WHERE w_player_id = ".$ID."";
    $returnw = mysql_query($queryw, $this->link) or die(mysql_error());
    $numw = mysql_numrows($returnw);

    //get all the games from the c4m_tournamentgames table
    $queryt = "SELECT tg_gameid FROM c4m_tournamentgames";
    $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    $numt = mysql_numrows($returnt);

    if($numw != 0){

      $i = 0;
      while($i < $numw){

        $game_id = trim(mysql_result($returnw,$i,"game_id"));
        $initiator = trim(mysql_result($returnw,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnw,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnw,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnw,$i,"next_move"));
        $start_time = trim(mysql_result($returnw,$i,"start_time"));
        $status = trim(mysql_result($returnw,$i,"status"));
        $completion_status = trim(mysql_result($returnw,$i,"completion_status"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "w" || $next_move == "w"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

          echo "<DESCRIPTION>";

          echo $this->GetUserIDByPlayerID($ConfigFile,$initiator)."";
          echo " VS ";

          if($w_player_id != $initiator){
            echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
          }

          if($b_player_id != $initiator){
            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);
          }
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

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

    //Get game where the player is black
    $queryb = "SELECT * FROM game WHERE b_player_id = ".$ID."";
    $returnb = mysql_query($queryb, $this->link) or die(mysql_error());
    $numb = mysql_numrows($returnb);

    if($numb != 0){

      $i = 0;
      while($i < $numb){

        $game_id = trim(mysql_result($returnb,$i,"game_id"));
        $initiator = trim(mysql_result($returnb,$i,"initiator"));
        $w_player_id = trim(mysql_result($returnb,$i,"w_player_id"));
        $b_player_id = trim(mysql_result($returnb,$i,"b_player_id"));
        $next_move = trim(mysql_result($returnb,$i,"next_move"));
        $start_time = trim(mysql_result($returnb,$i,"start_time"));
        $status = trim(mysql_result($returnb,$i,"status"));
        $completion_status = trim(mysql_result($returnb,$i,"completion_status"));

        //Check if the game is a tournament game
        $ti = 0;
        $bexit = false;
        while($ti < $numt && $bexit == false){

          $TGID = mysql_result($returnt, $ti, 0);

          if($TGID == $game_id){
            $bexit = true;
          }

          $ti++;
        }

        if($bexit == false){

          echo "<GAMES>\n";

          echo "<STATUS>";

          if($next_move == "w" || $next_move == "w"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          if($next_move == "B" || $next_move == "b"){
            echo "IDS_PLAYER_TURN";
          }

          if($next_move == "" && $status == "W" && $completion_status == "I"){
            echo "IDS_GAME_NOT_ACCEPTED";
          }

          if($next_move == "" && $status == "A" && $completion_status == "I"){
            echo "IDS_NOT_PLAYER_TURN";
          }

          echo "</STATUS>\n";

          echo "<COMPLETIONSTATUS>";
          echo $completion_status;
          echo "</COMPLETIONSTATUS>\n";

          $gametypecode = $this->GetGameTypeCode($game_id);
          $strGameType = "GT_NORMAL_GAME";

          switch($gametypecode){

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

          $this->GetGamePlayTypeInfoForMobile($game_id);
          $this->TimedGameStatsForMobile($game_id);

          echo "<TIMECREATED>";
          echo $start_time;
          echo "</TIMECREATED>\n";

          echo "<DESCRIPTION>";

          echo $this->GetUserIDByPlayerID($ConfigFile,$initiator)."";
          echo " VS ";

          if($w_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);
          }

          if($b_player_id != $initiator){

            echo $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);
          }

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

          echo "</GAMES>\n";

        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * PostMobileClientIP
  *
  */
  function PostMobileClientIP($ConfigFile, $ID, $IP){

    //Get game where the player is white
    $insert = "INSERT INTO mobile_client_ip VALUES(NULL, '".$ID."', '".$IP."')";

    if(mysql_query($insert, $this->link)){
      return true;
    }else{
      return false;
    }

  }

  /**********************************************************************
  * ListAvailablePlayersAForMobile - reduced
  *
  */
  function ListAvailablePlayersAForMobile2($ConfigFile, $oplayerid){

    $query = "SELECT * FROM `player` ORDER BY `userid` ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $i=0;
      while($i < $num){
        $player_id = trim(mysql_result($return,$i,"player_id"));
	  $userid = trim(mysql_result($return,$i,"userid"));
        if($this->IsPlayerDisabled($player_id) == false){
          $wins = 0;
          $loss = 0;
          $draws = 0;

          $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $player_id, $wins, $loss, $draws);

          if($this->ELOIsActive()){
            $points = $this->ELOGetRating($player_id);
          }else{
            $points = $this->GetPointValue($wins, $loss, $draws);
          }

          echo "<PLAYERS>\n";

          echo "<PID>";
          echo $player_id;
          echo "</PID>\n";

          echo "<USERID>";
          echo $userid;
          echo "</USERID>\n";

          echo "<POINTS>";
          echo $points;
          echo "</POINTS>\n";

          echo "</PLAYERS>\n";

        }
        $i++;

      }

    }
 
  }


  /**********************************************************************
  * GetMobileClientIPByPlayerID
  *
  */
  function GetMobileClientIPByPlayerID($ConfigFile, $ID){

    $query = "SELECT * FROM mobile_client_ip WHERE o_playerid='".$ID."' ORDER BY o_id DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $clientip = trim(mysql_result($return,0,"o_ip"));

      echo "<RESPONSE>\n";
      echo "<IP>";
      echo $clientip;
      echo "</IP>\n";
      echo "</RESPONSE>\n";

    }

  }


  /**********************************************************************
  * GetServerTypeName
  *
  */
  function GetServerTypeName(){

    $type = "ERROR";

    $query1 = "SELECT * FROM server_version WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $o_serverid = mysql_result($return1,0,"o_serverid");

      $type = $o_serverid;

    }

    return $type;

  }


  /**********************************************************************
  * GetGChatForMobile
  *
  */
  function GetGChatForMobile($ConfigFile, $GameID){

    $msg = "";

    $query = "SELECT * FROM c4m_gamechat WHERE tgc_gameid='".$GameID."' ORDER BY tgc_date DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $Message = $this->FilterChatText(mysql_result($return, $i, "tgc_message"));
	$Message = htmlentities(rawurldecode($Message));
        $msg = $msg."".$Message."\n\n";

        $i++;
      }

    }

    return $msg;

  }


  /**********************************************************************
  * TrimRSlash
  *
  */
  function TrimRSlash($URL){

    $nLength = strlen($URL);
    return ($URL{$nLength - 1} == '/') ? substr($URL, 0, $nLength - 1) : $URL;

  }


  /**********************************************************************
  * GetGamePlayTypeInfoForMobile
  *
  */
  function GetGamePlayTypeInfoForMobile($GameID){

    $gametypecode = $this->GetGameTypeCode($GameID);

    $query = "SELECT * FROM cfm_game_options WHERE o_gameid = '".$GameID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bRated = true;

    if($num != 0){

      $o_rating = trim(mysql_result($return,0,"o_rating"));
      $o_timetype = trim(mysql_result($return,0,"o_timetype"));

      if($o_rating == "gunrated"){
        $bRated = false;
      }

      ////////////////////
      // Rated
      echo "<RATED>";

      if($bRated){
        echo "true";
      }else{
        echo "false";
      }

      echo "</RATED>\n";

      $txtType = "-";
      switch($o_timetype){

        case "C-Blitz":
          $txtType = "IDS_BLITZ";
          break;

        case "C-Short":
          $txtType = "IDS_SHORT";
          break;

        case "C-Normal":
          $txtType = "IDS_NORMAL";
          break;

        case "C-Slow":
          $txtType = "IDS_SLOW";
          break;

        case "C-Snail":
          $txtType = "IDS_SNAIL";
          break;

      }

      ////////////////////
      // Normal Timeout
      echo "<TIMEOUT>";

      if($txtType != "-"){
        echo $txtType;
      }else{
        echo IDS_ACTIVE_REALTIME_CONTROLS;
      }

      echo "</TIMEOUT>\n";

    }else{

      echo "<RATED>";
      echo "true";
      echo "</RATED>\n";

      echo "<TIMEOUT>";
      echo IDS_UNKNOWN;
      echo "</TIMEOUT>\n";

    }

  }


  /**********************************************************************
  * TimedGameStatsForMobile
  *
  */
  function TimedGameStatsForMobile($gid){

    $query1 = "SELECT * FROM timed_games WHERE id='".$gid."'";
    $return1 = mysql_query($query1, $this->link) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    if($num1 != 0){

      $moves1 = mysql_result($return1,0,"moves1");
      $time1 = mysql_result($return1,0,"time1");
      $moves2 = mysql_result($return1,0,"moves2");
      $time2 = mysql_result($return1,0,"time2");

      echo "<TIMECONTROL1>";
      echo $moves1." ".$time1;
      echo "</TIMECONTROL1>\n";

      echo "<TIMECONTROL2>";
      echo $moves2." ".$time2;
      echo "</TIMECONTROL2>\n";

    }else{
      echo "<TIMECONTROL1>";
      echo "IDS_NULL";
      echo "</TIMECONTROL1>\n";

      echo "<TIMECONTROL2>";
      echo "IDS_NULL";
      echo "</TIMECONTROL2>\n";
    }

  }


  /**********************************************************************
  * IsCronManagementEnabled
  *
  */
  function IsCronManagementEnabled(){

    $bReturn = false;

    if($this->GetCronJobSettings() == 1){
      $bReturn = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * PlayerChatAddChatMessage
  *
  */
  function PlayerChatAddChatMessage($playerid, $Message){
    $Message = rawurlencode($Message);
    $insert = "INSERT INTO pc_chat_messages VALUES(NULL, ".$playerid.", '".$Message."', ".time().")";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * PlayerChatGetChatMessages
  *
  */
  function PlayerChatGetChatMessages($jtime, $isJAVA=false){

    $strMSG = "";

    $query = "SELECT * FROM pc_chat_messages WHERE o_datesent >= ".$jtime." ORDER BY o_chatid DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $o_message = mysql_result($return, $i, "o_message");
        $o_datesent = mysql_result($return, $i, "o_datesent");

        $strPlayer = $this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid);

        if($isJAVA){
          $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent)."]: ". htmlentities(rawurldecode($o_message))."\\n";
        }else{
          $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent)."]: ".htmlentities(rawurldecode($o_message))."\n";
        }

        $i++;
      }

    }

    return $strMSG;

  }


  /**********************************************************************
  * PlayerChatJoinAndMaintainChatStatus
  *
  */
  function PlayerChatJoinAndMaintainChatStatus($playerid){

    $query = "SELECT * FROM pc_chat_players WHERE o_playerid=".$playerid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE pc_chat_players SET o_joined=".time()." WHERE o_playerid=".$playerid."";
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO pc_chat_players VALUES(NULL, ".$playerid.", ".time().")";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * PlayerChatTimeOutOfflinePlayers
  *
  */
  function PlayerChatTimeOutOfflinePlayers(){

    $ntimeoutSec = 120;
    $nCurTime = time();

    $query = "SELECT * FROM pc_chat_players";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_cplayer = mysql_result($return, $i, "o_cplayer");
        $o_joined = mysql_result($return, $i, "o_joined");

        $nDifference = $nCurTime - $o_joined;

        if($nDifference >= $ntimeoutSec){
          $delete = "DELETE FROM pc_chat_players WHERE o_cplayer=".$o_cplayer."";
          mysql_query($delete, $this->link) or die(mysql_error());
        }

        $i++;
      }

    }

  }


  /**********************************************************************
  * PlayerChatGetOnlinePlayerListJAVA
  *
  */
  function PlayerChatGetOnlinePlayerListJAVA($playerid){

    $strReturnJAVA = "";

    $query = "SELECT * FROM pc_chat_players";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $strReturnJAVA = $strReturnJAVA."aPlayerList[".$i."]=\"".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid)."\";\n";

        $i++;
      }

    }

    return $strReturnJAVA;

  }


  /**********************************************************************
  * GetPlayerListSelectBoxV2
  *
  */
  function GetPlayerListSelectBoxV2($SelectName, $height=15, $width=170){

    $query = "SELECT * FROM player ORDER BY userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<select NAME='".$SelectName."' multiple size='".$height."' style='width:".$width."'>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){
          echo "<option VALUE='".$player_id."'>".$userid."</option>";
        }

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * v2CreateNewTournament_OneToMany
  *
  */
  function v2CreateNewTournament_OneToMany($nTType, $strTName, $strTDescription, $nPlayerCutoffTime, $nTStartTime, $nTEndTime, $strTimeZone, $strGameTimeOut, $aTOrganizers, $aTPlayer, $aTPlayers, $nPlayerSignupType){

    $ntotmid = 0;

    // Insert info for the tournament config table
    $insert = "INSERT INTO v2_tournament_config_onetomany VALUES(NULL, '".$strTName."', '".$strTDescription."', ".$nPlayerCutoffTime.", ".$nTStartTime.", ".$nTEndTime.", '".$strTimeZone."', '".$strGameTimeOut."', ".$nPlayerSignupType.", NOW(), 'p')";
    mysql_query($insert, $this->link) or die(mysql_error());

    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $ntotmid = trim(mysql_result($return,0,0));

      // Add players to the tournament organizers table
      $nCount = count($aTOrganizers);
      $i=0;
      while($i < $nCount){
        $this->v2CreateNewTournament_AddOrganizer($nTType, $ntotmid, $aTOrganizers[$i]);
        $i++;
      }

      // Add main player to the tournament players table
      $nCount = count($aTPlayer);
      $i=0;
      while($i < $nCount){
        $this->v2CreateNewTournament_AddPlayer($nTType, $ntotmid, $aTPlayer[$i], "*");
        $i++;
      }

      // Add players to the tournament players table
      $nCount = count($aTPlayers);
      $i=0;
      while($i < $nCount){
        $this->v2CreateNewTournament_AddPlayer($nTType, $ntotmid, $aTPlayers[$i], "");
        $i++;
      }

    }

    return $ntotmid;

  }


  /**********************************************************************
  * v2CreateNewTournament_AddPlayer
  *
  */
  function v2CreateNewTournament_AddPlayer($nTType, $TID, $playerID, $strNote){

    $oclubid = 0;
    $ostatus = 'p';

    // configure the status code
    if($strNote == "*"){
      $ostatus = 'a';
    }

    // Get player clubid
    $query = "SELECT * FROM chess_club_members WHERE o_playerid=".$playerID." AND o_active='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $oclubid = mysql_result($return,0,"o_chessclubid");
    }

    // Insert Tournament player
    $insert = "INSERT INTO v2_tournament_players VALUES(NULL, ".$nTType.", ".$TID.", ".$playerID.", ".$oclubid.", '".$ostatus."', '".$strNote."')";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * v2CreateNewTournament_AddOrganizer
  *
  */
  function v2CreateNewTournament_AddOrganizer($nTType, $TID, $playerID){

    // Insert Tournament organizer
    $insert = "INSERT INTO v2_tournament_organizers VALUES(NULL, ".$nTType.", ".$TID.", ".$playerID.")";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * v2NewTournamentListHTML
  *
  */
  function v2NewTournamentListHTML(){

    // Skin table settings
    if(defined('CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_WIDTH') && defined('CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_BORDER') && defined('CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLPADDING') && defined('CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLSPACING') && defined('CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_BORDER."' align='".CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_CELLSPACING."' width='".CFG_V2NEWTOURNAMENTLISTHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='538'>";
    }

    echo "<tr>";
    echo "<td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_67")."</font><b></td>";
    echo "</tr>";

    //////////////////////////////
    // One Against Many

    echo "<tr>";
    echo "<td colspan='3' class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_68")."</td>";
    echo "</tr>";

    $query = "SELECT * FROM v2_tournament_config_onetomany where o_status='p'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_totmid = mysql_result($return, $i, "o_totmid");
        $o_name = mysql_result($return, $i, "o_name");
        $o_dateadded = mysql_result($return, $i, "o_dateadded");

        echo "<tr>";
        echo "<td class='row2'>".$o_name."</td>";
        echo "<td class='row2'>".$o_dateadded."</td>";
        echo "<td class='row2' align='center'>";
        echo "[<a href=\"javascript:PopupWindowTInfo('./chess_tournament_info_v2.php?tid=".$o_totmid."&type=1')\">".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_69")."</a>]";
        echo "[<a href='./chess_accept_tournament_v2.php?accept=".$o_totmid."&type=1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_70")."</a>]";
        echo "[<a href='./chess_accept_tournament_v2.php?remove=".$o_totmid."&type=1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_75")."</a>]";
        echo "</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2AcceptNewTournament
  *
  */
  function v2AcceptNewTournament($type, $tid){

    if($type == 1){

      $query = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid." AND o_status='p'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $update = "UPDATE v2_tournament_config_onetomany SET o_status='a' WHERE o_totmid=".$tid." AND o_status='p'";
        mysql_query($update, $this->link) or die(mysql_error());

        // Message tournament players
        $queryp = "SELECT * FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$tid."";
        $returnp = mysql_query($queryp, $this->link) or die(mysql_error());
        $nump = mysql_numrows($returnp);

        if($nump != 0){

          $i=0;
          while($i < $nump){

            $o_playerid = mysql_result($returnp, $i, "o_playerid");

            $message = "T2|".$tid."|1";
            $insert = "INSERT INTO message_queue(player_id, message, posted) VALUES(".$o_playerid.",'".$message."',".time().")";
            mysql_query($insert, $this->link) or die(mysql_error());

            $i++;
          }

        }

      }

    }

  }


  /**********************************************************************
  * v2RejectNewTournament
  *
  */
  function v2RejectNewTournament($type, $tid){

    if($type == 1){

      $query = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid." AND o_status='p'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        // delete tournament players
        $delete = "DELETE FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$tid."";
        mysql_query($delete, $this->link) or die(mysql_error());

        // delete tournament organizers
        $delete = "DELETE FROM v2_tournament_organizers WHERE o_ttype=1 AND o_tid=".$tid."";
        mysql_query($delete, $this->link) or die(mysql_error());

        // delete tournament config
        $delete = "DELETE FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid."";
        mysql_query($delete, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * v2GetTournamentInformation_OneToMany
  *
  */
  function v2GetTournamentInformation_OneToMany($tid, &$strname, &$strdescription, &$nplayercutoffdate, &$ntournamentstartdate, &$ntournamentenddate, &$strtimezone, &$strgametimeout, &$nplayersignuptype, &$strdateadded, &$strstatus, &$aTOrganizers, &$aTPlayers){

    $aTOrganizers = array();
    $aTPlayers = array();

    $query = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $strname = mysql_result($return, 0, "o_name");
      $strdescription = mysql_result($return, 0, "o_description");
      $nplayercutoffdate = mysql_result($return, 0, "o_playercutoffdate");
      $ntournamentstartdate = mysql_result($return, 0, "o_tournamentstartdate");
      $ntournamentenddate = mysql_result($return, 0, "o_tournamentenddate");
      $strtimezone = mysql_result($return, 0, "o_timezone");
      $strgametimeout = mysql_result($return, 0, "o_gametimeout");
      $nplayersignuptype = mysql_result($return, 0, "o_playersignuptype");
      $strdateadded = mysql_result($return, 0, "o_dateadded");
      $strstatus = mysql_result($return, 0, "o_status");

      // Get tournament organizers
      $queryo = "SELECT * FROM v2_tournament_organizers WHERE o_ttype=1 AND o_tid=".$tid."";
      $returno = mysql_query($queryo, $this->link) or die(mysql_error());
      $numo = mysql_numrows($returno);

      if($numo != 0){

        $i=0;
        while($i < $numo){

          $aTOrganizers[$i][0] = mysql_result($returno, $i, "o_torg");
          $aTOrganizers[$i][1] = mysql_result($returno, $i, "o_ttype");
          $aTOrganizers[$i][2] = mysql_result($returno, $i, "o_tid");
          $aTOrganizers[$i][3] = mysql_result($returno, $i, "o_playerid");

          $i++;
        }

      }

      // Get tournament players
      $queryp = "SELECT * FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$tid."";
      $returnp = mysql_query($queryp, $this->link) or die(mysql_error());
      $nump = mysql_numrows($returnp);

      if($nump != 0){

        $i=0;
        while($i < $nump){

          $aTPlayers[$i][0] = mysql_result($returnp, $i, "o_tplayer");
          $aTPlayers[$i][1] = mysql_result($returnp, $i, "o_ttype");
          $aTPlayers[$i][2] = mysql_result($returnp, $i, "o_tid");
          $aTPlayers[$i][3] = mysql_result($returnp, $i, "o_playerid");
          $aTPlayers[$i][4] = mysql_result($returnp, $i, "o_clubid");
          $aTPlayers[$i][5] = mysql_result($returnp, $i, "o_status");
          $aTPlayers[$i][6] = mysql_result($returnp, $i, "o_note");

          $i++;
        }

      }

    }

  }


  /**********************************************************************
  * v2GetClosedTournamentInvites
  *
  */
  function v2GetClosedTournamentInvites($playerid){

    // one to many
    $query = "SELECT * FROM v2_tournament_config_onetomany, v2_tournament_players WHERE v2_tournament_config_onetomany.o_status='a' AND v2_tournament_players.o_playerid=".$playerid." AND v2_tournament_players.o_ttype=1 AND v2_tournament_players.o_tid=v2_tournament_config_onetomany.o_totmid AND v2_tournament_players.o_status='p'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_tid = mysql_result($return, $i, "o_tid");
        $o_name = mysql_result($return, $i, "o_name");

        echo "<tr><td><font class='menubulletcolor'>";
        echo "&nbsp; &#8226; &nbsp<a href='./chess_v2_tournament_status.php?tid=".$o_tid."&type=1'>".$o_name."</a>";
        echo "</font></td></tr>";

        $i++;
      }

    }

  }


  /**********************************************************************
  * v2AcceptTournamentInvite
  *
  */
  function v2AcceptTournamentInvite($type, $tid, $playerid){

    // One to many
    if($type == 1){

      $query = "SELECT * FROM v2_tournament_config_onetomany where o_totmid=".$tid." AND o_status='a'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $ndatetime = time();
        $o_playercutoffdate = mysql_result($return, 0, "o_playercutoffdate");

        if($ndatetime < $o_playercutoffdate){

          $queryp = "SELECT * FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$tid." AND o_playerid=".$playerid."";
          $returnp = mysql_query($queryp, $this->link) or die(mysql_error());
          $nump = mysql_numrows($returnp);

          if($nump != 0){
            $update = "UPDATE v2_tournament_players SET o_status='a' WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$playerid."";
            mysql_query($update, $this->link) or die(mysql_error());
          }

        }

      }

    }

  }


  /**********************************************************************
  * v2GetOpenTournamentListHTML
  *
  */
  function v2GetOpenTournamentListHTML($playerid){

    echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='538'>";
    echo "<tr>";
    echo "<td colspan='3' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_71")."</font><b></td>";
    echo "</tr>";

    //////////////////////////////
    // One Against Many

    echo "<tr>";
    echo "<td colspan='3' class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_68")."</td>";
    echo "</tr>";

    $query = "SELECT * FROM v2_tournament_config_onetomany where o_status='a' AND o_playersignuptype=2";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_totmid = mysql_result($return, $i, "o_totmid");
        $o_name = mysql_result($return, $i, "o_name");
        $o_dateadded = mysql_result($return, $i, "o_dateadded");

        echo "<tr>";
        echo "<td class='row2'>".$o_name."</td>";
        echo "<td class='row2'>".$o_dateadded."</td>";
        echo "<td class='row2' align='center'>";

        echo "[<a href=\"javascript:PopupWindowTInfo('./admin/chess_tournament_info_v2.php?tid=".$o_totmid."&type=1')\">".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_69")."</a>]";

        $queryp = "SELECT * FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$o_totmid." AND o_playerid=".$playerid."";
        $returnp = mysql_query($queryp, $this->link) or die(mysql_error());
        $nump = mysql_numrows($returnp);

        if($nump == 0){
          echo "[<a href='./chess_view_open_tournament_v2.php?join=".$o_totmid."&type=1'&pid=".$playerid.">".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_78")."</a>]";
        }

        echo "</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2JoinTournamentInvite
  *
  */
  function v2JoinTournamentInvite($type, $tid, $playerid){

    // One to many
    if($type == 1){

      $query = "SELECT * FROM v2_tournament_config_onetomany where o_totmid=".$tid." AND o_status='a'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $ndatetime = mktime();
        $o_playercutoffdate = mysql_result($return, 0, "o_playercutoffdate");

        if($ndatetime < $o_playercutoffdate){

          $queryp = "SELECT * FROM v2_tournament_players WHERE o_ttype=1 AND o_tid=".$tid." AND o_playerid=".$playerid."";
          $returnp = mysql_query($queryp, $this->link) or die(mysql_error());
          $nump = mysql_numrows($returnp);

          if($nump == 0){
            $this->v2CreateNewTournament_AddPlayer($type, $tid, $playerid, $strNote);
            $this->v2AcceptTournamentInvite($type, $tid, $playerid);
          }

        }

      }

    }

  }


  /**********************************************************************
  * v2ActiveTournamentManagement
  *
  */
  function v2ActiveTournamentManagement($playerid){

    // Skin table settings
    if(defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_WIDTH') && defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_BORDER') && defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLPADDING') && defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLSPACING') && defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_ALIGN')){
      echo "<table border='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_BORDER."' align='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLPADDING."' cellspacing='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_CELLSPACING."' width='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_TABLE1_WIDTH."'>";
    }else{
      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    echo "<tr>";
    echo "<td class='tableheadercolor' colspan='2'>";
    echo "<font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_79")."</font>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_82")."</td>";

    // Skin table settings
    if(defined('CFG_V2ACTIVETOURNAMENTMANAGEMENT_ROWX1_WIDTH')){
      echo "<td class='row1' width='".CFG_V2ACTIVETOURNAMENTMANAGEMENT_ROWX1_WIDTH."'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_83")."</td>";
    }else{
      echo "<td class='row1' width='100'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_83")."</td>";
    }

    echo "</tr>";

    // one to many
    $query = "SELECT * FROM v2_tournament_config_onetomany, v2_tournament_organizers where v2_tournament_config_onetomany.o_totmid = v2_tournament_organizers.o_tid AND v2_tournament_organizers.o_ttype=1 AND v2_tournament_config_onetomany.o_status='a' AND v2_tournament_organizers.o_playerid = ".$playerid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_totmid = mysql_result($return, $i, "o_totmid");
        $o_name = mysql_result($return, $i, "o_name");

        echo "<tr>";
        echo "<td class='row2'><a href=\"javascript:PopupWindowTM('./tv2_console_index.php?tid=".$o_totmid."&type=1');\">".$o_name."</a></td>";
        echo "<td class='row2'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_80")."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2ConsoleJoinAndMaintainChatStatus
  *
  */
  function v2ConsoleJoinAndMaintainChatStatus($type, $tid, $playerid){

    $query = "SELECT * FROM v2_tournament_console_players WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$playerid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE v2_tournament_console_players SET o_joined=".time()." WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$playerid."";
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO v2_tournament_console_players VALUES(NULL, ".$type.", ".$tid.", ".$playerid.", ".time().")";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * v2ConsoleGetOnlinePlayerListJAVA
  *
  */
  function v2ConsoleGetOnlinePlayerListJAVA($type, $tid, $playerid){

    $strReturnJAVA = "";

    $query = "SELECT * FROM v2_tournament_console_players WHERE o_ttype=".$type." AND o_tid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $strReturnJAVA = $strReturnJAVA."aPlayerList[".$i."]=\"".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid)."\";\n";

        $i++;
      }

    }

    return $strReturnJAVA;

  }


  /**********************************************************************
  * v2ConsoleTimeOutOfflinePlayers
  *
  */
  function v2ConsoleTimeOutOfflinePlayers($type, $tid){

    $ntimeoutSec = 120;
    $nCurTime = time();

    $query = "SELECT * FROM v2_tournament_console_players WHERE o_ttype=".$type." AND o_tid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_cplayer = mysql_result($return, $i, "o_cplayer");
        $o_joined = mysql_result($return, $i, "o_joined");

        $nDifference = $nCurTime - $o_joined;

        if($nDifference >= $ntimeoutSec){
          $delete = "DELETE FROM v2_tournament_console_players WHERE o_cplayer=".$o_cplayer."";
          mysql_query($delete, $this->link) or die(mysql_error());
        }

        $i++;
      }

    }

  }


  /**********************************************************************
  * v2ConsoleAddChatMessage
  *
  */
  function v2ConsoleAddChatMessage($type, $tid, $playerid, $Message){
    $Message = rawurlencode($Message);
    // Replace French, Latin characters with HTML equivalents
    $aToReplace = array("%E0","%E1","%E2","%E3","%E4","%E5","%E6","%E7","%E8","%E9","%EA","%EB","%EC","%ED","%EE","%EF","%F0","%F1","%F2","%F3","%F4","%F5","%F6","%F7","%F8","%F9","%FA","%FB","%FC","%FD","%FE","%FF","%20AC","201C","201D","%AB","%BB","A6","%C1","%C0","%C2","%C3","%C4","%C5","%C6","%C7","%C8","%C9","%CA","%CB","%CC","%CD","%CE","%CF","%D0","%D1","%D2","%D3","%D4","%D5","%D6","%D7","%D8","%D9","%DA","%DB","%DC","%DD","%DE","%DF");
    $aReplaceWith = array("&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;","&#8364;","&#8220;","&#8221;","&#171;","&#187;","&#166;","&#193;","&#192;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;");
    $Message = str_replace($aToReplace, $aReplaceWith, $Message);
    $insert = "INSERT INTO v2_tournament_console_chat VALUES(NULL, ".$type.", ".$tid.", ".$playerid.", '".$Message."', ".time().")";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * v2ConsoleGetChatMessages
  *
  */
  function v2ConsoleGetChatMessages($type, $tid, $isJAVA=false){

    $strMSG = "";

    $query = "SELECT * FROM v2_tournament_console_chat WHERE o_ttype=".$type." AND o_tid=".$tid." ORDER BY o_chatid DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $o_message = mysql_result($return, $i, "o_message");
        $o_datesent = mysql_result($return, $i, "o_datesent");

        $strPlayer = $this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid);

        if($isJAVA){
          $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent)."]: ".rawurldecode ($o_message)."\\n";
        }else{
          $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent)."]: ".rawurldecode ($o_message)."\n";
        }

        $i++;
      }

    }

    return $strMSG;

  }


  /**********************************************************************
  * v2ConsoleCreateGamesAll
  *
  */
  function v2ConsoleCreateGamesAll($type, $tid, $time){

    if($type == 1){

      $queryx = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='i'";
      $returnx = mysql_query($queryx, $this->link) or die(mysql_error());
      $numx = mysql_numrows($returnx);

      // Check if games already exists
      if($numx == 0){

        $this->v2GetTournamentInformation_OneToMany($tid, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);

        $query1 = "SELECT * FROM v2_tournament_players WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='a' AND o_note = '*'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        $nPlayer1 = 0;

        if($num1 != 0){
          $nPlayer1 = mysql_result($return1, 0, "o_playerid");
        }

        $gameid = $this->gen_unique();

        $insertgame = "INSERT INTO game(game_id, initiator, w_player_id, b_player_id, status, completion_status, start_time, next_move) VALUES('".$gameid."', $nPlayer1, $nPlayer1, 0, 'A', 'I', ".$time.", NULL)";
        mysql_query($insertgame, $this->link) or die(mysql_error());

        $insertgamematch = "INSERT INTO c4m_tournamentgames VALUES(NULL, 0, '".$gameid."', ".$nPlayer1.", 0, 'N', 'N', 'I' )";
        mysql_query($insertgamematch, $this->link) or die(mysql_error());

        // Convert the game timeout to seconds
        list($h1, $m1, $s1) = explode(":", $strgametimeout, 3);
        $tseconds = $time + (($h1 * 3600 ) + ($m1 * 60) + $s1);

        $insertgamematch2 = "INSERT INTO v2_tournament_game_config VALUES(NULL, ".$type.", ".$tid.", '".$gameid."', ".$nPlayer1.", 0, 'n', 'n', ".$ntournamentstartdate.", ".$ntournamentenddate.", 'i')";
        mysql_query($insertgamematch2, $this->link) or die(mysql_error());

      }

    }

  }


  /**********************************************************************
  * v2ViewTournamentGameStatusCalendar
  *
  */
  function v2ViewTournamentGameStatusCalendar($ConfigFile, $TID, $month, $day, $year, $timezone, $type, $index){

    // Get tournament information
    if($type == 1){
      $this->v2GetTournamentInformation_OneToMany($TID, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);
    }

    // Get tournament player cutoff date
    $playercutoffdate = date("n.j.Y.H:i:s", $nplayercutoffdate + (3600*$timezone));
    list($pcdmonth, $pcdday, $pcdyear, $pcdtime) = preg_split('/[\/.-]/', $playercutoffdate, 4);

    // Get tournament start date
    $tournamentstartdate = date("n.j.Y.H:i:s", $ntournamentstartdate + (3600*$timezone));
    list($tsdmonth, $tsdday, $tsdyear, $tsdtime) = preg_split('/[\/.-]/', $tournamentstartdate, 4);

    // Get tournament end date
    $tournamentenddate = date("n.j.Y.H:i:s", $ntournamentenddate + (3600*$timezone));
    list($tedmonth, $tedday, $tedyear, $tedtime) = preg_split('/[\/.-]/', $tournamentenddate, 4);

    //Find the days in the current month
    $nDays = date("t", mktime(0,0,0, $month, $day, $year));

    // Get current month text
    $strmonth = date("F", mktime(0,0,0, $month, $day, $year));

    // Get current date
    $today = date("n.j.Y", mktime() + (3600*$timezone));
    list($monthc, $dayc, $yearc) = preg_split('/[\/.-]/', $today, 3);

    // Get the start day text of the month
    $tDay = date("l", mktime(0,0,0, $month, $day, $year));

    $startcol = 0;

    switch($tDay){

      case "Saturday":
        $startcol = '6';
        break;

      case "Friday":
        $startcol = '5';
        break;

      case "Thursday":
        $startcol = '4';
        break;

      case "Wednesday":
        $startcol = '3';
        break;

      case "Tuesday":
        $startcol = '2';
        break;

      case "Monday":
        $startcol = '1';
        break;

      case "Sunday":
        $startcol = '0';
        break;

    }

    // Generate the calendar

    // Skin table settings
    if(defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_WIDTH') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_BORDER') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLPADDING') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLSPACING') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_ALIGN')){
      echo "<table border='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_BORDER."' width='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_WIDTH."' cellspacing='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLSPACING."' cellpadding='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_CELLPADDING."' align='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE1_ALIGN."'>";
    }else{
      echo "<table border='0' width='500' cellspacing='0' cellpadding='1' align='center'>";
    }

    $nWidthX1 = 21;
    if(defined('CFG_GETCLUBLISTHTML_ROWX1_WIDTH')){
      $nWidthX1 = CFG_GETCLUBLISTHTML_ROWX1_WIDTH;
    }

    echo "<tr>";

    if(($index - 1) >= 0){
      echo "<td width='".$nWidthX1."' class='tableheadercolor'><p align='left'><a href='./chess_v2_tournament_status.php?tzn=".$timezone."&tid=".$TID."&type=".$type."&cmonth=".($index - 1)."'><img border='0' src='./skins/".$this->SkinsLocation."/images/back.gif' width='13' height='13'></a></p></td>";
    }else{
      echo "<td width='".$nWidthX1."' class='tableheadercolor'></td>";
    }

    echo "<td class='tableheadercolor'><p align='center'>".$strmonth." ".$year."</p></td>";

    echo "<td width='".$nWidthX1."' class='tableheadercolor'><p align='right'><a href='./chess_v2_tournament_status.php?tzn=".$timezone."&tid=".$TID."&type=".$type."&cmonth=".($index + 1)."'><img border='0' src='./skins/".$this->SkinsLocation."/images/next.gif' width='13' height='13'></a></p></td>";
    echo "</tr>";
    echo "</table>";

    // Skin table settings
    if(defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_WIDTH') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_BORDER') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLPADDING') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLSPACING') && defined('CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_ALIGN')){
      echo "<table border='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_BORDER."' cellspacing='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLSPACING."' cellpadding='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_CELLPADDING."' width='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_WIDTH."' align='".CFG_V2VIEWTOURNAMENTGAMESTATUSCALENDAR_TABLE2_ALIGN."'>";
    }else{
      echo "<table border='1' cellspacing='0' cellpadding='1' width='500' align='center'>";
    }

    $nWidthX2 = 70;
    $nHeightX2 = 70;
    if(defined('CFG_GETCLUBLISTHTML_ROWX2_WIDTH') && defined('CFG_GETCLUBLISTHTML_ROWX2_HEIGHT')){
      $nWidthX2 = CFG_GETCLUBLISTHTML_ROWX2_WIDTH;
      $nHeightX2 = CFG_GETCLUBLISTHTML_ROWX2_HEIGHT;
    }

    echo "<tr>";
    echo "<td class='row1' align='center'>Sun</td><td class='row1' align='center'>Mon</td><td class='row1' align='center'>Tues</td><td class='row1' align='center'>Wed</td><td class='row1' align='center'>Thur</td><td class='row1' align='center'>Fri</td><td class='row1' align='center'>Sat</td>";
    echo "</tr>";

    $ncol = 0;
    $nscol = 0;
    $nswitchcol = 6;
    $ncdate = 1;

    echo "<tr>";

    while($ncdate < $nDays + 1){

      if($nscol > $nswitchcol){
        echo "</tr><tr>";
        $nscol = 0;
      }

      if($ncol >= $startcol){

        // Mark the current day on the calendar
        if($monthc == $month && $dayc == $ncdate && $yearc == $year){

          echo "<td class='textcurrent' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
          echo "<font style='font-size: 10px'>".$ncdate."<br></font><br>";

          // Mark the player cutoff day on the calendar
          if($pcdmonth == $month && $pcdday == $ncdate && $pcdyear == $year){
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_96")."<br></font>";
          }

          // Mark the tournament start day on the calendar
          if($tsdmonth == $month && $tsdday == $ncdate && $tsdyear == $year){
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_97")."<br></font>";

          }

          // Mark the tournament endday on the calendar
          if($tedmonth == $month && $tedday == $ncdate && $tedyear == $year){
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_98")."<br></font>";
          }

          echo "</td>";

        }else{

          // Mark the player cutoff day on the calendar
          if($pcdmonth == $month && $pcdday == $ncdate && $pcdyear == $year){

            echo "<td bgcolor='#CCCC33' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
            echo "<font style='font-size: 10px'>".$ncdate."<br></font><br>";
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_96")."<br></font>";

            // Mark the tournament start day on the calendar
            if($tsdmonth == $month && $tsdday == $ncdate && $tsdyear == $year){
              echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_97")."<br></font>";
            }

            // Mark the tournament endday on the calendar
            if($tedmonth == $month && $tedday == $ncdate && $tedyear == $year){
              echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_98")."<br></font>";
            }

            echo "</td>";

          // Mark the tournament start day on the calendar
          }elseif($tsdmonth == $month && $tsdday == $ncdate && $tsdyear == $year){

            echo "<td bgcolor='#CCCC66' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
            echo "<font style='font-size: 10px'>".$ncdate."<br></font><br>";
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_97")."<br></font>";

            // Mark the tournament endday on the calendar
            if($tedmonth == $month && $tedday == $ncdate && $tedyear == $year){
              echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_98")."<br></font>";
            }

            echo "</td>";

          // Mark the tournament day on the calendar
          }elseif($month >= $tsdmonth && $month <= $tedmonth && $ncdate > $tsdday && $ncdate < $tedday && $year >= $tsdyear && $year <= $tedyear){

            echo "<td bgcolor='#CCCC66' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
            echo "<font style='font-size: 10px'>".$ncdate."</font><br>";
            echo "</td>";

          // Mark the tournament endday on the calendar
          }elseif($tedmonth == $month && $tedday == $ncdate && $tedyear == $year){

            echo "<td bgcolor='#CCCC66' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
            echo "<font style='font-size: 10px'>".$ncdate."</font><br><br>";
            echo "<font style='font-size: 10px'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_98")."<br></font>";
            echo "</td>";

          // Display the regular day on the calendar
          }else{

            echo "<td class='row2' align='center' valign='top' width='".$nWidthX2."' height='".$nHeightX2."'>";
            echo "<font style='font-size: 10px'>".$ncdate."</font><br>";
            echo "</td>";

          }

        }

        $ncol++;
        $nscol++;
        $ncdate++;

      }else{
        echo "<td class='row2' align='center'>&nbsp;</td>";
        $ncol++;
        $nscol++;
      }

    }

    // fill the remaining cols if any
    if($nscol < $nswitchcol + 1){

      while($nscol < $nswitchcol + 1){
        echo "<td class='row2' align='center'>&nbsp;</td>";
        $nscol++;
      }

    }

    echo "</tr>";
    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * v2GetInvertedTimeZoneGMT
  *
  */
  function v2GetInvertedTimeZoneGMT($GMT){

    $nGmt = 0;

    switch($GMT){

      case -12:
        $nGmt = 12;
        break;

      case -11:
        $nGmt = 11;
        break;

      case -10:
        $nGmt = 10;
        break;

      case -9:
        $nGmt = 9;
        break;

      case -8:
        $nGmt = 8;
        break;

      case -7:
        $nGmt = 7;
        break;

      case -6:
        $nGmt = 6;
        break;

      case -5:
        $nGmt = 5;
        break;

      case -4:
        $nGmt = 4;
        break;

      case -3.5:
        $nGmt = 3.5;
        break;

      case -3:
        $nGmt = 3;
        break;

      case -2:
        $nGmt = 2;
        break;

      case -1:
        $nGmt = 1;
        break;

      case 1:
        $nGmt = -1;
        break;

      case 2:
        $nGmt = -2;
        break;

      case 3:
        $nGmt = -3;
        break;

      case 3.5:
        $nGmt = -3.5;
        break;

      case 4:
        $nGmt = -4;
        break;

      case 4.5:
        $nGmt = -4.5;
        break;

      case 5:
        $nGmt = -5;
        break;

      case 5.5:
        $nGmt = -5.5;
        break;

      case 6:
        $nGmt = -6;
        break;

      case 6.5:
        $nGmt = -6.5;
        break;

      case 7:
        $nGmt = -7;
        break;

      case 8:
        $nGmt = -8;
        break;

      case 9:
        $nGmt = -9;
        break;

      case 9.5:
        $nGmt = -9.5;
        break;

      case 10:
        $nGmt = -10;
        break;

      case 11:
        $nGmt = -11;
        break;

      case 12:
        $nGmt = -12;
        break;

      case 13:
        $nGmt = -13;
        break;

    }

    return $nGmt;

  }


  /**********************************************************************
  * v2GetActiveTournamentListHTML
  *
  */
  function v2GetActiveTournamentListHTML(){

    /////////////////////////////////////////
    // One To Many

    // Skin table settings
    if(defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_WIDTH') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_BORDER') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLPADDING') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLSPACING') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_ALIGN')){
      echo "<table width='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_WIDTH."' cellpadding='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_CELLSPACING."' border='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_BORDER."' align='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='500' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    echo "<tr>";
    echo "<td class='tableheadercolor' colspan='4'>";
    echo "<font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_99")."</font>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_102")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_103")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_104")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_105")."</td>";
    echo "</tr>";

    $query = "SELECT * FROM v2_tournament_config_onetomany where o_status='a'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_totmid = mysql_result($return, $i, "o_totmid");
        $strname = mysql_result($return, $i, "o_name");
        $strdescription = mysql_result($return, $i, "o_description");
        $nplayercutoffdate = mysql_result($return, $i, "o_playercutoffdate");
        $ntournamentstartdate = mysql_result($return, $i, "o_tournamentstartdate");
        $ntournamentenddate = mysql_result($return, $i, "o_tournamentenddate");
        $strtimezone = mysql_result($return, $i, "o_timezone");
        $strgametimeout = mysql_result($return, $i, "o_gametimeout");
        $nplayersignuptype = mysql_result($return, $i, "o_playersignuptype");
        $strdateadded = mysql_result($return, $i, "o_dateadded");
        $strstatus = mysql_result($return, $i, "o_status");

        echo "<tr>";
        echo "<td class='row2'>";
        echo "<a href='./chess_v2_tournament_status.php?tid=".$o_totmid."&type=1'>".$strname."</a>";
        echo "</td>";
        echo "<td class='row2'>";

        if($nplayersignuptype == 1){
          echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_63");
        }else{
          echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_64");
        }

        echo "</td>";
        echo "<td class='row2'>";
        echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_101");
        echo "</td>";
        echo "<td class='row2'>";
        echo $strdateadded;
        echo "</td>";
        echo "</tr>";

        $i++;

      }

    }

    echo "</table><br>";

    /////////////////////////////////////////
    // Finished Tournaments


    // Skin table settings
    if(defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_WIDTH') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_BORDER') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLPADDING') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLSPACING') && defined('CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_ALIGN')){
      echo "<table width='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_WIDTH."' cellpadding='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLPADDING."' cellspacing='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_CELLSPACING."' border='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_BORDER."' align='".CFG_v2GETACTIVETOURNAMENTLISTHTML_TABLE2_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='500' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    echo "<tr>";
    echo "<td class='tableheadercolor' colspan='4'>";
    echo "<font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_100")."</font>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_102")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_103")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_104")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_105")."</td>";
    echo "</tr>";

    $query = "SELECT * FROM v2_tournament_config_onetomany where o_status='c'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_totmid = mysql_result($return, $i, "o_totmid");
        $strname = mysql_result($return, $i, "o_name");
        $strdescription = mysql_result($return, $i, "o_description");
        $nplayercutoffdate = mysql_result($return, $i, "o_playercutoffdate");
        $ntournamentstartdate = mysql_result($return, $i, "o_tournamentstartdate");
        $ntournamentenddate = mysql_result($return, $i, "o_tournamentenddate");
        $strtimezone = mysql_result($return, $i, "o_timezone");
        $strgametimeout = mysql_result($return, $i, "o_gametimeout");
        $nplayersignuptype = mysql_result($return, $i, "o_playersignuptype");
        $strdateadded = mysql_result($return, $i, "o_dateadded");
        $strstatus = mysql_result($return, $i, "o_status");

        echo "<tr>";
        echo "<td class='row2'>";
        echo "<a href='./chess_v2_tournament_status.php?tid=".$o_totmid."&type=1'>".$strname."</a>";
        echo "</td>";
        echo "<td class='row2'>";

        if($nplayersignuptype == 1){
          echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_63");
        }else{
          echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_64");
        }

        echo "</td>";
        echo "<td class='row2'>";
        echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_101");
        echo "</td>";
        echo "<td class='row2'>";
        echo $strdateadded;
        echo "</td>";
        echo "</tr>";

        $i++;

      }

    }

    echo "</table><br>";

  }


  /**********************************************************************
  * v2GetCurrentTournamentGamesHTML
  *
  */
  function v2GetCurrentTournamentGamesHTML($type, $tid, $timezone, $playerID){

    $query = "SELECT * FROM v2_tournament_game_config where o_ttype=".$type." AND o_tid=".$tid." AND o_status='i'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_WIDTH') && defined('CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_BORDER') && defined('CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLPADDING') && defined('CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLSPACING') && defined('CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_ALIGN')){
      echo "<table border='".CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_BORDER."' align='".CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLPADDING."' cellspacing='".CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_CELLSPACING."' width='".CFG_V2GETCURRENTTOURNAMENTGAMESHTML_TABLE1_WIDTH."'>";
    }else{
      echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    }

    echo "<tr>";
    echo "<td class='row1'><b>Game Name</b></td>";
    echo "<td class='row1'><b>Start Time</b></td>";
    echo "<td class='row1'><b>End Time</b></td>";
    echo "</tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_tgc = mysql_result($return, $i, "o_tgc");
        $o_ttype = mysql_result($return, $i, "o_ttype");
        $o_tid = mysql_result($return, $i, "o_tid");
        $o_wplayerid = mysql_result($return, $i, "o_wplayerid");
        $o_bplayerid = mysql_result($return, $i, "o_bplayerid");
        $o_gmtstarttime = mysql_result($return, $i, "o_gmtstarttime");
        $o_gmtendtime = mysql_result($return, $i, "o_gmtendtime");

        echo "<tr>";
        echo "<td class='row2'>";
        echo "<a href=\"javascript:PopupWindowP('./tv2p_index.php?tgc=".$o_tgc."&tid=".$tid."&type=".$type."&tzn=".$timezone."')\">".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_wplayerid)." VS ".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_bplayerid)."</a>";
        echo "</td>";
        echo "<td class='row2'>".date("Y-m-j H:i:s", $o_gmtstarttime + (3600*$timezone))."</td>";
        echo "<td class='row2'>".date("Y-m-j H:i:s", $o_gmtendtime + (3600*$timezone))."</td>";
        echo "</tr>";

        $i++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2OTMAddChatMessage
  *
  */
  function v2OTMAddChatMessage($type, $tid, $playerid, $Message, $ctype){

    $Message = rawurlencode($Message);
    // Replace French, Latin characters with HTML equivalents
    $aToReplace = array("%E0","%E1","%E2","%E3","%E4","%E5","%E6","%E7","%E8","%E9","%EA","%EB","%EC","%ED","%EE","%EF","%F0","%F1","%F2","%F3","%F4","%F5","%F6","%F7","%F8","%F9","%FA","%FB","%FC","%FD","%FE","%FF","%20AC","201C","201D","%AB","%BB","A6","%C1","%C0","%C2","%C3","%C4","%C5","%C6","%C7","%C8","%C9","%CA","%CB","%CC","%CD","%CE","%CF","%D0","%D1","%D2","%D3","%D4","%D5","%D6","%D7","%D8","%D9","%DA","%DB","%DC","%DD","%DE","%DF");
    $aReplaceWith = array("&#224;","&#225;","&#226;","&#227;","&#228;","&#229;","&#230;","&#231;","&#232;","&#233;","&#234;","&#235;","&#236;","&#237;","&#238;","&#239;","&#240;","&#241;","&#242;","&#243;","&#244;","&#245;","&#246;","&#247;","&#248;","&#249;","&#250;","&#251;","&#252;","&#253;","&#254;","&#255;","&#8364;","&#8220;","&#8221;","&#171;","&#187;","&#166;","&#193;","&#192;","&#194;","&#195;","&#196;","&#197;","&#198;","&#199;","&#200;","&#201;","&#202;","&#203;","&#204;","&#205;","&#206;","&#207;","&#208;","&#209;","&#210;","&#211;","&#212;","&#213;","&#214;","&#215;","&#216;","&#217;","&#218;","&#219;","&#220;","&#221;","&#222;","&#223;");
    $Message = str_replace($aToReplace, $aReplaceWith, $Message);
    
    $insert = "INSERT INTO v2_tournament_onetomany_chat VALUES(NULL, ".$type.", ".$tid.", ".$playerid.", '".$Message."', '".$ctype."', ".mktime().")";
    mysql_query($insert, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * v2OTMGetChatMessages
  *
  */
  function v2OTMGetChatMessages($type, $tid, $timezone, $isJAVA=false){

    $strMSG = "";

    $query = "SELECT * FROM v2_tournament_onetomany_chat WHERE o_ttype=".$type." AND o_tid=".$tid." ORDER BY o_chatid DESC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $o_message = mysql_result($return, $i, "o_message");
        $o_mtype = mysql_result($return, $i, "o_mtype");
        $o_datesent = mysql_result($return, $i, "o_datesent");

        if($o_playerid != 0){
          $strPlayer = $this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid);
        }

        if($isJAVA){

          if($o_mtype == "sys"){
            $strMSG = $strMSG."<System>[".date("Y-m-d h:i:s", $o_datesent + 3600*$timezone)."]: ".$o_message."\\n";
          }else{
            $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent + 3600*$timezone)."]: ".rawurldecode($o_message)."\\n";
          }

        }else{

          if($o_mtype == "sys"){
            $strMSG = $strMSG."<System>[".date("Y-m-d h:i:s", $o_datesent + 3600*$timezone)."]: ".$o_message."\n";
          }else{
            $strMSG = $strMSG."<".$strPlayer.">[".date("Y-m-d h:i:s", $o_datesent + 3600*$timezone)."]: ".$o_message."\n";
          }

        }

        $i++;
      }

    }

    return $strMSG;

  }


  /**********************************************************************
  * v2OTMJoinAndMaintainChatStatus
  *
  */
  function v2OTMJoinAndMaintainChatStatus($type, $tid, $playerid){

    $query = "SELECT * FROM v2_tournament_onetomany_players WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$playerid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE v2_tournament_onetomany_players SET o_joined=".time()." WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$playerid."";
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO v2_tournament_onetomany_players VALUES(NULL, ".$type.", ".$tid.", ".$playerid.", ".time().")";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * v2OTMTimeOutOfflinePlayers
  *
  */
  function v2OTMTimeOutOfflinePlayers($type, $tid){

    $ntimeoutSec = 120;
    $nCurTime = time();

    $query = "SELECT * FROM v2_tournament_onetomany_players WHERE o_ttype=".$type." AND o_tid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_cplayer = mysql_result($return, $i, "o_cplayer");
        $o_joined = mysql_result($return, $i, "o_joined");

        $nDifference = $nCurTime - $o_joined;

        if($nDifference >= $ntimeoutSec){
          $delete = "DELETE FROM v2_tournament_onetomany_players WHERE o_cplayer=".$o_cplayer."";
          mysql_query($delete, $this->link) or die(mysql_error());
        }

        $i++;
      }

    }

  }


  /**********************************************************************
  * v2OTMGetOnlinePlayerListJAVA
  *
  */
  function v2OTMGetOnlinePlayerListJAVA($type, $tid, $playerid){

    $strReturnJAVA = "";

    $query = "SELECT * FROM v2_tournament_onetomany_players WHERE o_ttype=".$type." AND o_tid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_playerid = mysql_result($return, $i, "o_playerid");
        $strReturnJAVA = $strReturnJAVA."aPlayerList[".$i."]=\"".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $o_playerid)."\";\n";

        $i++;
      }

    }

    return $strReturnJAVA;

  }


  /**********************************************************************
  * V2GetGameStatus
  *
  */
  function V2GetGameStatus($ConfigFile, $GameID, $sid, $player_id, $Gamestatus, $clrl, $clrd, $ViewMode, $GameType, $BoardType){

    // retieve data
    $fen = $this->GetFEN($sid, $GameID);
    $isblack = $this->IsPlayerBlack($ConfigFile, $GameID, $player_id, true);

    $initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";

    $this->GetCurrentGameInfoByRef($ConfigFile, $GameID, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

    // Build the table
    echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";

    if($ViewMode == 1){

      echo "<tr>";
      echo "<td valign='top' colspan='2' class='row2'>";

      echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' class='forumline'>";
      echo "<tr>";
      echo "<td valign='top' align='left' class='row2'>";

      $image = $this->GetAvatarImageName($w_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "<td valign='top' align='left' class='row2'>";
      echo "White:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);

      if($this->IsPlayerOnline($ConfigFile, $w_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $w_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($w_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."<br>";

      echo "</td>";
      echo "<td valign='top' align='center' class='row2'>";
      echo "<b>VS</b><br>";
      echo "<br><br><br><br>";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";
      echo "Black:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

      if($this->IsPlayerOnline($ConfigFile, $b_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $b_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($b_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";

      $image = $this->GetAvatarImageName($b_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      echo "</td>";
      echo "</tr>";

    }

    echo "<tr>";
    echo "<td valign='top' colspan='2'>";

    echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";

    // Move ///////////////////////////////////////////////////
    echo "<tr>";
    echo "<td valign='top' class='row2' colspan='4'>";

    if($this->IsPlayersTurn($ConfigFile, $player_id, $GameID, true) == true && $GameType != 3){

      if($this->isBoardCustomerSettingDragDrop($player_id)){
        echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_231")." <input type='hidden' name='txtmovefrom' class='post' size='3'><input type='hidden' name='txtmoveto' class='post' size='3'> <input type='hidden' name='cmdMove' value=''>&nbsp;";
      }else{
        echo "".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_230")." <input type='text' name='txtmovefrom' class='post' size='3'> ".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_6")." <input type='text' name='txtmoveto' class='post' size='3'> <input type='submit' name='cmdMove' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_PM")."' class='mainoption'>&nbsp;";
      }

    }

/*
    if($Gamestatus == "I"){
      echo "<input type='submit' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption' onclick=\"return (confirm('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_233")."') == true)? true : false\" >";
      echo "<input type='submit' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption' onclick=\"return (confirm('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_232")."') == true)? true : false\" >";
    }else{

      echo "<input type='button' name='cmdResign' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_R")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_1")."');\">";
      echo "<input type='button' name='cmdDraw' value='".$this->GetStringFromStringTable("IDS_CR3DCQUERY_BTN_D")."' class='mainoption' onclick=\"javascript:alert('".$this->GetStringFromStringTable("IDS_CR3DCQUERY_JAVA_2")."');\">";

    }
*/

    if($ViewMode == 1 || $ViewMode == 2){
      echo "<input type='button' name='btnSavePGN' value='PGN/FEN' class='mainoption' onclick=\"javascript:PopupWindow('./view_PGN.php?gid=".$GameID."')\">";
    }

    echo "</td>";
    echo "</tr>";

    echo "</table>";

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td valign='top'>";

    if($this->isBoardCustomerSettingDragDrop($player_id)){

      if($isblack){
        $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "b", $this->IsPlayersTurn($ConfigFile, $player_id, $GameID, true));
      }else{
        $this->CreateChessBoardDragDrop($fen, $clrl, $clrd, true, "w", $this->IsPlayersTurn($ConfigFile, $player_id, $GameID, true));
      }

    }else{

      if($isblack){
        $this->CreateChessBoard($fen, $clrl, $clrd, true, "b");
      }else{
        $this->CreateChessBoard($fen, $clrl, $clrd, true, "w");
      }

    }

    echo "</td>";
    echo "<td valign='top' align='right'>";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

  }


  /**********************************************************************
  * v2OTMGenerateMPGameListHTML
  *
  */
  function v2OTMGenerateMPGameListHTML($type, $tid){

    $query = "SELECT * FROM v2_tournament_game_config where o_ttype=".$type." AND o_tid=".$tid." AND o_status='i'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<table width='100%'>";

    if($num != 0){

      $i=0;
      $swap=0;
      $bResetSwap = false;
      while($i < $num){

        $o_tgc = mysql_result($return, $i, "o_tgc");
        $o_gameid = mysql_result($return, $i, "o_gameid");

        if($swap == 0){
          echo "<tr>";
        }

        echo "<td width='50%'><iframe id='".$o_gameid."' name='".$o_gameid."' scrolling='auto' frameborder='0' width='100%' height='500' src='./tv2mp_game.php?gameid=".$o_gameid."&tid=".$tid."&type=".$type."'></iframe></td>";

        if($swap == 1){
          echo "</tr>";
          $bResetSwap = true;
        }

        $swap++;

        if($bResetSwap){
          $swap=0;
          $bResetSwap = false;
        }

        $i++;

      }

    }

    if($this->checkNumEven(($i+1))){
      echo "<td></td>";
      echo "</tr>";
    }

    echo "</table>";

  }


  /**********************************************************************
  * checkNumOdd
  *
  */
  function checkNumEven($num){
    return ($num%2 == 0) ? TRUE : FALSE;
  }


  /**********************************************************************
  * v2OTMGeneratePGameListHTML
  *
  */
  function v2OTMGeneratePGameListHTML($type, $tid, $tgc){

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_tgc=".$tgc." AND o_status='i'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<table width='100%'>";

    if($num != 0){

      $o_gameid = mysql_result($return, $i, "o_gameid");

      echo "<td width='50%'><iframe id='".$o_gameid."' name='".$o_gameid."' scrolling='auto' frameborder='0' width='100%' height='500' src='./tv2mp_game.php?gameid=".$o_gameid."&tid=".$tid."&type=".$type."'></iframe></td>";

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2ManageTournamentGameQueueJAVA
  *
  */
  function v2ManageTournamentGameQueueJAVA($pid, $type, $tid){

    $query = "SELECT * FROM v2_tournament_game_queue WHERE o_pid=".$pid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_tgq = mysql_result($return, $i, "o_tgq");
        $o_gameid = mysql_result($return, $i, "o_gameid");
        $o_pid = mysql_result($return, $i, "o_pid");
        $o_reload = mysql_result($return, $i, "o_reload");

        if($o_pid == $pid && $o_reload='y'){

          echo "top.frGameList.document.getElementById('".$o_gameid."').src = './tv2mp_game.php?gameid=".$o_gameid."&tid=".$tid."&type=".$type."';";

          $delete = "DELETE FROM v2_tournament_game_queue WHERE o_tgq=".$o_tgq."";
          mysql_query($delete, $this->link) or die(mysql_error());


        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * v2AddTournamentGameQueue
  *
  */
  function v2AddTournamentGameQueue($pid, $type, $tid, $gid){

    if($pid == 0){

      $query = "SELECT * FROM v2_tournament_players WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='a' AND o_note != '*'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $i=0;
        while($i < $num){

          $o_playerid = mysql_result($return, $i, "o_playerid");

          $insert = "INSERT INTO v2_tournament_game_queue VALUES(NULL, '".$gid."', $o_playerid, 'y')";
          mysql_query($insert, $this->link) or die(mysql_error());

          $i++;
        }

      }

    }else{

      $insert = "INSERT INTO v2_tournament_game_queue VALUES(NULL, '".$gid."', $pid, 'y')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }


  }


  /**********************************************************************
  * v2ClearTournamentGameQueue
  *
  */
  function v2ClearTournamentGameQueue($pid, $type, $tid){

    // delete black player queues
    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_bplayerid=".$pid." AND o_status='i'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_gameid = mysql_result($return, $i, "o_gameid");

        $delete = "DELETE FROM v2_tournament_game_queue WHERE o_gameid='".$o_gameid."' AND o_pid=".$pid."";
        mysql_query($delete, $this->link) or die(mysql_error());

        $i++;
      }

    }

    // delete white player queues
    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_wplayerid=".$pid." AND o_status='i'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_gameid = mysql_result($return, $i, "o_gameid");

        $delete = "DELETE FROM v2_tournament_game_queue WHERE o_gameid='".$o_gameid."' AND o_pid=".$pid."";
        mysql_query($delete, $this->link) or die(mysql_error());

        $i++;
      }

    }

  }


  /**********************************************************************
  * v2IsUserPrimaryPlayer
  *
  */
  function v2IsUserPrimaryPlayer($pid, $type, $tid){

    $bReturn = false;

    $query = "SELECT * FROM v2_tournament_players WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_playerid=".$pid." AND o_note='*'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bReturn = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * v2IsUserPlayer
  *
  */
  function v2IsUserPlayer($tgc, $pid, $type, $tid){

    $bReturn = false;

    $query = "SELECT * FROM v2_tournament_players WHERE o_playerid=".$pid." AND o_ttype=".$type." AND o_tid=".$tid." AND o_status='a'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bReturn = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * v2GenerateSpectatorChessboardHTML
  *
  */
  function v2GenerateSpectatorChessboardHTML($tgc, $pid, $type, $tid, $clrl, $clrd){

    $ConfigFile = $this->ChessCFGFileLocation;

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_tgc=".$tgc."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_gameid = mysql_result($return, $i, "o_gameid");

      $fen = $this->GetFEN("", $o_gameid);

      $initiator = "";
      $w_player_id = "";
      $b_player_id = "";
      $next_move = "";
      $start_time = "";

      $this->GetCurrentGameInfoByRef($ConfigFile, $o_gameid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);


      // Build the table
      echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";

      echo "<tr>";
      echo "<td valign='top' colspan='2' class='row2'>";

      echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' class='forumline'>";
      echo "<tr>";
      echo "<td valign='top' align='left' class='row2'>";

      $image = $this->GetAvatarImageName($w_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "<td valign='top' align='left' class='row2'>";
      echo "White:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);

      if($this->IsPlayerOnline($ConfigFile, $w_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $w_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($w_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."<br>";

      echo "</td>";
      echo "<td valign='top' align='center' class='row2'>";
      echo "<b>VS</b><br>";
      echo "<br><br><br><br>";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";
      echo "Black:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

      if($this->IsPlayerOnline($ConfigFile, $b_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $b_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($b_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";

      $image = $this->GetAvatarImageName($b_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      echo "</td>";
      echo "</tr>";



      echo "</table>";


      $this->CreateChessBoard($fen, $clrl, $clrd, false, "w");

    }

  }


  /**********************************************************************
  * v2UpdateGameLoginAndPlayStatus
  *
  */
  function v2UpdateGameLoginAndPlayStatus($gid, $pid, $type, $tid){

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_gameid='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_wplayerid = mysql_result($return, 0, "o_wplayerid");
      $o_bplayerid = mysql_result($return, 0, "o_bplayerid");

      if($o_wplayerid == $pid){
        $update = "UPDATE v2_tournament_game_config SET o_wplayerln='y' WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_gameid='".$gid."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

      if($o_bplayerid == $pid){
        $update = "UPDATE v2_tournament_game_config SET o_bplayerln='y' WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_gameid='".$gid."'";
        mysql_query($update, $this->link) or die(mysql_error());
      }

    }

  }


  /**********************************************************************
  * v2GetTournamentGameTimeoutStatus
  *
  */
  function v2GetTournamentGameTimeoutStatus($type, $tid, $tgc=0){

    $Status = "IDS_GAME_NOT_READY";

    if($tgc == 0){

      $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='i'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $o_gmtstarttime = mysql_result($return, 0, "o_gmtstarttime");
        $o_gmtendtime = mysql_result($return, 0, "o_gmtendtime");

        if(mktime() >= $o_gmtstarttime && mktime() < $o_gmtendtime){
          $Status = "IDS_GAME_READY";
        }elseif(mktime() >= $o_gmtendtime){
          $Status = "IDS_GAME_FINISHED";
        }

      }

    }else{

      $query = "SELECT * FROM v2_tournament_game_config WHERE o_tgc=".$tgc."";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $o_gmtstarttime = mysql_result($return, 0, "o_gmtstarttime");
        $o_gmtendtime = mysql_result($return, 0, "o_gmtendtime");

        if(mktime() >= $o_gmtstarttime && mktime() < $o_gmtendtime){

          $Status = "IDS_GAME_READY";
        }elseif(mktime() >= $o_gmtendtime){
          $Status = "IDS_GAME_FINISHED";
        }

      }

    }

    return $Status;

  }


  /**********************************************************************
  * v2IsOTMTournamentGamesCreated
  *
  */
  function v2IsOTMTournamentGamesCreated($type, $tid){

    $breturn = false;

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $breturn = true;
    }

    return $breturn;

  }


  /**********************************************************************
  * v2FinishAndCloseTournament
  *
  */
  function v2FinishAndCloseTournament($type, $tid){

    $queryx = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid." AND o_status='a'";
    $returnx = mysql_query($queryx, $this->link) or die(mysql_error());
    $numx = mysql_numrows($returnx);

    ///////////////////
    // Step 1

    // Display log message
    echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_112")."<br>";

    // Check if all the games are timed out
    $strStatus = $this->v2GetTournamentGameTimeoutStatus($type, $tid);
    if($strStatus == "IDS_GAME_FINISHED" && $numx != 0){

      ///////////////////
      // Step 2

      // calculate game scores
      $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='i'";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $i=0;
        while($i < $num){

          $o_tgc = mysql_result($return, $i, "o_tgc");
          $o_gameid = mysql_result($return, $i, "o_gameid");
          $o_wplayerid = mysql_result($return, $i, "o_wplayerid");
          $o_bplayerid = mysql_result($return, $i, "o_bplayerid");
          $o_wplayerln = mysql_result($return, $i, "o_wplayerln");
          $o_bplayerln = mysql_result($return, $i, "o_bplayerln");

          // Get the game information
          $initiator = "";
          $w_player_id = "";
          $b_player_id = "";
          $status = "";
          $completion_status = "";
          $start_time = "";
          $next_move = "";

          $this->GetGameInfoByRef($this->ChessCFGFileLocation, $o_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

          // Display log message
          echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_114")." ".$this->GetUserIDByPlayerID($ConfigFile,$o_wplayerid)." VS ".$this->GetUserIDByPlayerID($ConfigFile,$o_bplayerid)."<br>";

          // Check if the game was completed or not
          if($completion_status == "I"){

            // white's move
            if($next_move == 'w' || $next_move == ''){
              $this->UpdateGameStatus($config, $o_gameid, "C", "B");

            // black's move
            }elseif($next_move == 'b'){
              $this->UpdateGameStatus($config, $o_gameid, "C", "W");
            }

          }else{

            // winner was black
            if($completion_status == "B"){
              $this->UpdateGameStatus($config, $o_gameid, "C", "B");

            // winer was white
            }elseif($completion_status == "W"){
              $this->UpdateGameStatus($config, $o_gameid, "C", "W");
            }

          }

          $this->CachePlayerPointsByPlayerID($b_player_id);
          $this->CachePlayerPointsByPlayerID($w_player_id);

          // Set the game to complete
          $update = "UPDATE v2_tournament_game_config SET o_status='c' WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_gameid='".$o_gameid."'";
          mysql_query($update, $this->link) or die(mysql_error());

          $i++;
        }

      }

      ///////////////////
      // Step 3

      // Close the tournament

      // Display log message
      echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_115")."<br>";

      $update = "UPDATE v2_tournament_config_onetomany SET o_status='c' WHERE o_totmid=".$tid."";
      mysql_query($update, $this->link) or die(mysql_error());

    }else{
      // Display log message
      echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_113")."<br>";
    }

    // Display log message
    echo $this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_116")."<br>";

  }


  /**********************************************************************
  * v2IsOTMCutoffDateMet
  *
  */
  function v2IsOTMCutoffDateMet($type, $tid){

    $breturn = false;

    $query = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_playercutoffdate = mysql_result($return, 0, "o_playercutoffdate");

      if(mktime() >= $o_playercutoffdate){
        $breturn = true;
      }

    }

    return $breturn;

  }


  /**********************************************************************
  * v2IsOTMTournamentComplete
  *
  */
  function v2IsOTMTournamentComplete($type, $tid){

    $breturn = false;

    $query = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid." AND o_status='c'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $breturn = true;
    }

    return $breturn;

  }


  /**********************************************************************
  * v2GenerateTournamentResultTable
  *
  */
  function v2GenerateTournamentResultTable($type, $tid){

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_ttype=".$type." AND o_tid=".$tid." AND o_status='c'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_WIDTH') && defined('CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_BORDER') && defined('CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLPADDING') && defined('CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLSPACING') && defined('CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_ALIGN')){
      echo "<table width='".CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_WIDTH."' border='".CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_BORDER."' cellpadding='".CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLPADDING."' cellspacing='".CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_CELLSPACING."' align='".CFG_V2GENERATETOURNAMENTRESULTTABLE_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='500' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='3'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_117")."</font></td></tr>";

    echo "<tr>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_118")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_119")."</td>";
    echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_120")."</td>";
    echo "</tr>";

    if($num !=0){

      $i=0;
      while($i < $num){

        $o_gameid = mysql_result($return, $i, "o_gameid");
        $o_wplayerid = mysql_result($return, $i, "o_wplayerid");
        $o_bplayerid = mysql_result($return, $i, "o_bplayerid");

        $this->GetGameInfoByRef($this->ChessCFGFileLocation, $o_gameid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

        echo "<tr>";
        echo "<td class='row2'>".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $w_player_id)."</td>";
        echo "<td class='row2'>".$this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $b_player_id)."</td>";
        echo "<td class='row2'>".$completion_status."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * v2GetNewTournamentCount
  *
  */
  function v2GetNewTournamentCount(){

    $nCount = 0;

    $query = "SELECT COUNT(*) FROM v2_tournament_config_onetomany WHERE o_status='p'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nCount = mysql_result($return,0, 0);
    }

    return $nCount;

  }


  /**********************************************************************
  * v2AddTournamentGameMoveVote
  *
  */
  function v2AddTournamentGameMoveVote($gameid, $pid, $move){

    $query = "SELECT * FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' AND o_pid=".$pid."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num == 0){

      $insert = "INSERT INTO v2_tournament_onetomany_gamemove_vote VALUES(NULL, '".$gameid."', ".$pid.", '".$move."', ".time().")";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * v2GetTournamentGameMoveVote
  *
  */
  function v2GetTournamentGameMoveVote($gameid){

    $query = "SELECT * FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' ORDER BY o_move";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<table width='200' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td colspan='2' class='row1'>".$this->GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_121")."</td></tr>";

    if($num != 0){

      $i=0;
      $lastmove = "";
      while($i < $num){

        $nvote = 0;

        $o_tgmv = mysql_result($return, $i, "o_tgmv");
        $o_gameid = mysql_result($return, $i, "o_gameid");
        $o_pid = mysql_result($return, $i, "o_pid");
        $o_move = mysql_result($return, $i, "o_move");

        if($lastmove != $o_move){

          // Get move count
          $query1 = "SELECT count(*) FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' AND o_move='".$o_move."'";
          $return1 = mysql_query($query1, $this->link) or die(mysql_error());
          $num1 = mysql_numrows($return1);

          if($num1 != 0){
            $nvote = mysql_result($return1, 0, 0);
          }

          list($move1, $move2) = preg_split("/\|/", $o_move, 2);
          echo "<tr><td class='row1'>".$nvote."</td><td class='row2'>".$move1."</td></tr>";
          $lastmove = $o_move;

        }

        $i++;
      }


    }

    echo "</table>";

  }


  /**********************************************************************
  * v2MakeTournamentGameMove_Vote
  *
  */
  function v2MakeTournamentGameMove_Vote($tgc, $type, $tid){

    $move = "";
    $gameid = "";
    $bCheckMoveVotes = false;

    $queryt = "SELECT * FROM v2_tournament_config_onetomany WHERE o_totmid=".$tid."";
    $returnt = mysql_query($queryt, $this->link) or die(mysql_error());
    $numt = mysql_numrows($returnt);

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_tgc=".$tgc."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0 && $numt != 0){

      $gameid = mysql_result($return, 0, "o_gameid");
      $gametimeout = mysql_result($returnt, 0, "o_gametimeout");

      // Convert the game timeout to seconds
      list($h1, $m1, $s1) = explode(":", $gametimeout, 3);
      $tseconds = (($h1 * 3600 ) + ($m1 * 60) + $s1);

      $query1 = "SELECT * FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' ORDER BY o_tgmv ASC LIMIT 1";
      $return1 = mysql_query($query1, $this->link) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      $query2 = "SELECT * FROM move_history WHERE game_id = '".$gameid."' ORDER BY time DESC LIMIT 1";
      $return2 = mysql_query($query2, $this->link) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num1 != 0 && $num2 != 0){

        $ntime1 = mysql_result($return1, 0, "o_time");
        $ntime2 = mysql_result($return2, 0, "time");

        if((time() - $ntime1) >= $tseconds){
          $bCheckMoveVotes = true;
        }

      }

    }

    if($bCheckMoveVotes){

      $aMoveVotes = array();

      $query = "SELECT * FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' ORDER BY o_move";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $i=0;
        $a=0;
        $lastmove = "";
        while($i < $num){

          $nvote = 0;

          $o_tgmv = mysql_result($return, $i, "o_tgmv");
          $o_gameid = mysql_result($return, $i, "o_gameid");
          $o_pid = mysql_result($return, $i, "o_pid");
          $o_move = mysql_result($return, $i, "o_move");

          if($lastmove != $o_move){

            // Get move count
            $query1 = "SELECT count(*) FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."' AND o_move='".$o_move."'";
            $return1 = mysql_query($query1, $this->link) or die(mysql_error());
            $num1 = mysql_numrows($return1);

            if($num1 != 0){
              $nvote = mysql_result($return1, 0, 0);
            }

            list($move1, $move2) = preg_split("/\|/", $o_move, 2);

            $aMoveVotes[$a]['PID'] = $o_pid;
            $aMoveVotes[$a]['Move'] = $move1;
            $aMoveVotes[$a]['Vote'] = $nvote;

            $a++;

            $lastmove = $o_move;

          }

          $i++;
        }

      }

      $aMoveVotes = $this->array_csort($aMoveVotes,'Vote',SORT_DESC, SORT_NUMERIC);

      if(count($aMoveVotes) > 0){
        $move = $aMoveVotes[0]['Move'].",$gameid";
      }

    }

    return $move;

  }


  /**********************************************************************
  * v2ClearTournamentGameMove_Vote
  *
  */
  function v2ClearTournamentGameMove_Vote($tgc, $type, $tid){

    $query = "SELECT * FROM v2_tournament_game_config WHERE o_tgc=".$tgc."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $gameid = mysql_result($return, 0, "o_gameid");

      $delete = "DELETE FROM v2_tournament_onetomany_gamemove_vote WHERE o_gameid='".$gameid."'";
      mysql_query($delete, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetPChatSettings
  *
  */
  function GetPChatSettings(){

    $nSetting = 0;

    $query = "SELECT * FROM cfg_player_chat WHERE o_pcid=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nSetting = mysql_result($return,0, "o_setting");
    }

    return $nSetting;

  }


  /**********************************************************************
  * UpdatePChatSettings
  *
  */
  function UpdatePChatSettings($nSetting){

    $update = "UPDATE cfg_player_chat SET o_setting=".$nSetting." WHERE o_pcid=1";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * GetCronJobSettings
  *
  */
  function GetCronJobSettings(){

    $nSetting = 0;

    $query = "SELECT * FROM cfg_cron_job WHERE o_cjid=1";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nSetting = mysql_result($return,0, "o_setting");
    }

    return $nSetting;

  }


  /**********************************************************************
  * UpdateCronJobSettings
  *
  */
  function UpdateCronJobSettings($nSetting){

    $update = "UPDATE cfg_cron_job SET o_setting=".$nSetting." WHERE o_cjid=1";
    mysql_query($update, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * ConfirmSID
  *
  */
  function ConfirmSID($sid, &$user, &$id){

    $query = "SELECT * FROM active_sessions WHERE session='".$sid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $id = mysql_result($return,0, "player_id");
      $user = $this->GetUserIDByPlayerID($this->ChessCFGFileLocation, $id);

    }

  }


  /**********************************************************************
  * GetAllMovesForMobile
  *
  */
  function GetAllMovesForMobile($gameid){

    echo "<RESPONSE>\n";

    $query = "SELECT * FROM move_history WHERE game_id = '".$gameid."' ORDER BY time ASC";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      echo "<NUMBEROFMOVES>".$num."</NUMBEROFMOVES>\n";
      echo "<MOVES>\n";

      $i=0;
      while($move = mysql_fetch_array($return)){

        $i++;

        echo "<MOVE>\n";
        echo "<MOVENUMBER>".$i."</MOVENUMBER>\n";

        if(strstr($move["move"], "O-O-O w")){

          echo "<MOVECOMMA>e1,c1</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e1c1</MOVENOCOMMA>\n";

        }elseif(strstr($move["move"], "O-O-O b")){

          echo "<MOVECOMMA>e8,c8</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e8c8</MOVENOCOMMA>\n";

        }elseif(strstr($move["move"], "O-O w")){

          echo "<MOVECOMMA>e1,g1</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e1g1</MOVENOCOMMA>\n";

        }elseif(strstr($move["move"], "O-O b")){

          echo "<MOVECOMMA>e8,g8</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e8g8</MOVENOCOMMA>\n";

        }else{

          echo "<MOVECOMMA>".$move["move"]."</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>".str_replace(",", "", $move["move"])."</MOVENOCOMMA>\n";

        }

        echo "</MOVE>\n";

      }

      echo "</MOVES>\n";

    }else{

      echo "<NUMBEROFMOVES>0</NUMBEROFMOVES>\n";
      echo "<MOVES></MOVES>\n";

    }

    echo "</RESPONSE>\n";

  }


  /**********************************************************************
  * GetNewMoveForMobile
  *
  */
  function GetNewMoveForMobile($gameid){

    echo "<RESPONSE>\n";

    $query = "SELECT COUNT(move_id) FROM move_history WHERE game_id = '".$gameid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $move = mysql_fetch_array($return);
    if($moves != $move["COUNT(move_id)"]){

      $sqlquery = "SELECT * FROM move_history WHERE game_id = '".$gameid."' ORDER BY time DESC LIMIT 1";
      $result = mysql_query($sqlquery) or die(mysql_error());
      $move = mysql_fetch_array($result);

      echo "<NEWMOVE>";
      //echo "<DEBUG>".$move["move"]."</DEBUG>";

      if(strstr($move["move"], "O-O-O w")){

          echo "<MOVECOMMA>e1,c1</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e1c1</MOVENOCOMMA>\n";

      }elseif(strstr($move["move"], "O-O-O b")){

          echo "<MOVECOMMA>e8,c8</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e8c8</MOVENOCOMMA>\n";

      }elseif(strstr($move["move"], "O-O w")){

          echo "<MOVECOMMA>e1,g1</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e1g1</MOVENOCOMMA>\n";

      }elseif(strstr($move["move"], "O-O b")){

          echo "<MOVECOMMA>e8,g8</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>e8g8</MOVENOCOMMA>\n";

      }else{

        if($move["move"] != ""){
          echo "<MOVECOMMA>".$move["move"]."</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>".str_replace(",", "", $move["move"])."</MOVENOCOMMA>\n";
        }else{
          echo "<MOVECOMMA>*</MOVECOMMA>\n";
          echo "<MOVENOCOMMA>*</MOVENOCOMMA>\n";
        }

      }

      echo "</NEWMOVE>\n";

    }else{

      echo "<NEWMOVE>";
      echo "<MOVECOMMA>*</MOVECOMMA>\n";
      echo "<MOVENOCOMMA>*</MOVENOCOMMA>\n";
      echo "</NEWMOVE>\n";

    }

    echo "</RESPONSE>\n";

  }


  /**********************************************************************
  * IsGameControlsViewableByPlayer
  *
  */
  function IsGameControlsViewableByPlayer($gameid, $playerid){

    $bReturn = false;

    $query = "SELECT * FROM game WHERE game_id='".$gameid."' AND ((w_player_id=".$playerid." OR b_player_id=".$playerid.") OR w_player_id='0' OR b_player_id='0')";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $bReturn = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * ViewOtherPlayerGameListHTML
  *
  */
  function ViewOtherPlayerGameListHTML($playerid, $type){

    $strTitle = "";
    $isrealtime = "";

    if($type == 2){
      $strTitle = $this->GetStringFromStringTable("IDS_CHESS_STATS_BTN_TXT_2");
      $query = "SELECT * FROM game WHERE (w_player_id=".$playerid." OR b_player_id=".$playerid.") AND status='C'";
    }else{
      $strTitle = $this->GetStringFromStringTable("IDS_CHESS_STATS_BTN_TXT_1");
      $query = "SELECT * FROM game WHERE (w_player_id=".$playerid." OR b_player_id=".$playerid.") AND status='A' AND completion_status='I'";
    }

    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='500'>";
    echo "<tr><td colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'>".$strTitle."</font><b></td></tr>";

    echo "<tr>";
    echo "<td class='row1' align='center'>";
    echo "</td>";

    echo "<td class='row1'>";
    echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_4");
    echo "</td>";

    echo "<td class='row1'>";
    echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_5");
    echo "</td>";

    echo "<td class='row1'>";

    if($type == 2){
      echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_7");
    }else{
      echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_6");
    }

    echo "</td>";
    echo "</tr>";

    if($num != 0){

      $i=0;
      while($i < $num){

        $game_id = mysql_result($return, $i,"game_id");
        $start_time = mysql_result($return, $i,"start_time");
        $w_player_id = mysql_result($return, $i,"w_player_id");
        $b_player_id = mysql_result($return, $i,"b_player_id");
        $completion_status = mysql_result($return, $i,"completion_status");

        echo "<tr>";
        echo "<td class='row2' align='center'>";

        if($type == 2){
          echo "<a href=\"javascript:ViewOldGame('./old_game_viewer.php?gid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_1")."</a>";
        }else{
          echo "<a href=\"javascript:ViewLiveGame('./active_game_viewer.php?gid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_1")."</a>";
        }

        echo "|";
        echo "<a href=\"javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php?gameid=".$game_id."');\">".$this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_11")."</a>";

        echo "</td>";

        echo "<td class='row2'>";
        echo $this->GetUserIDByPlayerID("", $w_player_id);
        echo "</td>";

        echo "<td class='row2'>";
        echo $this->GetUserIDByPlayerID("", $b_player_id);
        echo "</td>";

        echo "<td class='row2'>";

        if($type == 2){

          if($completion_status == "W"){
            echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_8");
          }elseif($completion_status == "B"){
            echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_9");
          }elseif($completion_status == "D"){
            echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_10");
          }

        }else{

          if($b_player_id == $playerid){
            $isrealtime = $this->IsRequestRealTime("", $game_id, true);
          }else{
            $isrealtime = $this->IsRequestRealTime("", $game_id, false);
          }

          if($isrealtime == "IDS_REAL_TIME"){
            $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_2");
          }else{
            echo $this->GetStringFromStringTable("IDS_OTHER_GAMELIST_TXT_3");
          }

        }

        echo "</td>";
        echo "</tr>";

        $i++;

      }

    }

    echo "</table>";

  }



  /**********************************************************************
  * GenerateSpectatorChessboardHTML
  *
  */
  function GenerateSpectatorChessboardHTML($gid, $clrl, $clrd){

    $ConfigFile = $this->ChessCFGFileLocation;

    $query = "SELECT * FROM game WHERE game_id='".$gid."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_gameid = mysql_result($return, $i, "game_id");

      $fen = $this->GetFEN("", $o_gameid);

      $initiator = "";
      $w_player_id = "";
      $b_player_id = "";
      $next_move = "";
      $start_time = "";

      $this->GetCurrentGameInfoByRef($ConfigFile, $o_gameid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);


      // Build the table
      echo "<table border='0' cellpadding='0' cellspacing='0' align='center'>";

      echo "<tr>";
      echo "<td valign='top' colspan='2' class='row2'>";

      echo "<table width='100%' border='0' cellpadding='0' cellspacing='0' class='forumline'>";
      echo "<tr>";
      echo "<td valign='top' align='left' class='row2'>";

      $image = $this->GetAvatarImageName($w_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "<td valign='top' align='left' class='row2'>";
      echo "White:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$w_player_id);

      if($this->IsPlayerOnline($ConfigFile, $w_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $w_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($w_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."<br>";

      echo "</td>";
      echo "<td valign='top' align='center' class='row2'>";
      echo "<b>VS</b><br>";
      echo "<br><br><br><br>";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2' style='text-align: right;'>";
      echo "Black:<br>";

      $userid = $this->GetUserIDByPlayerID($ConfigFile,$b_player_id);

      if($this->IsPlayerOnline($ConfigFile, $b_player_id)){
        echo "<font color='green'>".$userid."</font><br>";
      }else{
        echo "<font color='red'>".$userid."</font><br>";
      }

      $wins = 0;
      $loss = 0;
      $draws = 0;

      $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $b_player_id, $wins, $loss, $draws);

      if($this->ELOIsActive()){
        $points = $this->ELOGetRating($b_player_id);
      }else{
        $points = $this->GetPointValue($wins, $loss, $draws);
      }

      echo $points."";

      echo "</td>";
      echo "<td valign='top' align='right' class='row2'>";

      $image = $this->GetAvatarImageName($b_player_id);

      if($image != ""){
        echo "<img src='./avatars/".$image."' style='float: right'>";
      }else{
        echo "<img src='./avatars/noimage.jpg'>";
      }

      echo "</td>";
      echo "</tr>";
      echo "</table>";

      

      $this->CreateChessBoard($fen, $clrl, $clrd, false, "w");

	  echo "</td>";
      echo "</tr>";

      echo "</table>";
    }

  }

//??????????????????????????????????????????????????????????????????????????????

  /**********************************************************************
  * GetUserPassword
  *
  */
  function GetUserPassword($ConfigFile, $UserID){

    $query = "SELECT * FROM player WHERE userid = '".$UserID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $password = "";

    if($num != 0){
       $password  = trim(mysql_result($return,0,"password"));
    }

    return $password;

  }


  /**********************************************************************
  * GetUserListArray
  *
  */
  function GetUserListArray(){

    $aUserList = array();

    $query = "SELECT * FROM player";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim(mysql_result($return, $i, "player_id"));

        array_push($aUserList, $player_id);

        $i++;

      }

    }

    return $aUserList;

  }


  /**********************************************************************
  * ListAvailablePlayersB
  *
  */
  function ListAvailablePlayersB($ConfigFile, $strSkinName, $strHTMLPage, $action, $index){

    $nMaxList = 20;

    //Get game info
    $query = "SELECT * FROM player LEFT JOIN player2 ON player.player_id = player2.player_id WHERE player2.player_id IS NULL ORDER BY player.userid Asc";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    // Check if the index is numeric
    if(!is_numeric($index)){
      $index = 1;
    }

    $nNumberOfPages = ceil($num / $nMaxList);
    $nPrevPage = $index - 1;
    $nNextPage = $index + 1;

    $nIndex = ($nMaxList * $index) - $nMaxList;

    if($nIndex < 0){
      $nIndex = 0;
    }

    echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_27")."</font></td></tr>";

    echo "<tr>";
    echo "<td class='row2' colspan='5'>";

    echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>";
    echo "<tr>";

    // Display the previous page arrow
    if($nPrevPage >= 1){
      echo "<td class='row2' align='left'><div align='left'><a href='./".$strHTMLPage."?action=bck&index=".$nPrevPage."'><img src='./skins/".$strSkinName."/images/back.gif' border='0'></a></div></td>";
    }else{
      echo "<td class='row2'></td>";
    }

    echo "<td class='row2'></td>";
    echo "<td class='row2'>";
    echo "<center>";

    // Display the page numbers
    $ix = 1;
    while($ix <= $nNumberOfPages){

      if($index == $ix){
        echo "[<a href='./".$strHTMLPage."?action=nxt&index=".$ix."'>$ix</a>] ";
      }else{
        echo "<a href='./".$strHTMLPage."?action=nxt&index=".$ix."'>$ix</a> ";
      }

      $ix++;

    }

    echo "</center>";
    echo "</td>";
    echo "<td class='row2'></td>";

    // Display the next page arrow
    if($nNextPage <= $nNumberOfPages){
      echo "<td class='row2' align='right'><div align='right'><a href='./".$strHTMLPage."?action=nxt&index=".$nNextPage."'><img src='./skins/".$strSkinName."/images/next.gif' border='0'></a></div></td>";
    }else{
      echo "<td class='row2'></td>";
    }

    echo "</tr>";
    echo "</table>";

    echo "</td>";
    echo "</tr>";

    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_28")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_29")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_30")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_32")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_33")."</td></tr>";

    if($num != 0){

      $i = $nIndex;
      $ii = 0;
      while($i < $num && $ii < $nMaxList){

        $player_id = trim(mysql_result($return,$i,"player_id"));
        $userid = trim(mysql_result($return,$i,"userid"));
        $signup_time  = trim(mysql_result($return,$i,"signup_time"));
        $email = trim(mysql_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){

          echo "<tr>";
          echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
          echo "<td class='row2'>".date("m-d-Y",$signup_time)."</td>";
          echo "<td class='row2'><a href='./chess_msg_center.php?type=newmsg&slctUsers=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_34")."</a></td>";
          echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_35")."</a></td>";
          echo "<td class='row2'>";

          if($this->IsPlayerOnline($ConfigFile, $player_id)){
            echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_36")."</font>";
          }else{
            echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CR3DCQUERY_TXT_37")."</font>";
          }

          echo "</td>";
          echo "</tr>";

        }

        $i++;
        $ii++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * DoesPlayerIDExists
  *
  */
  function DoesPlayerIDExists($PID){

    $query = "SELECT * FROM player WHERE player_id = ".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bExists = false;

    if($num != 0){
      $bExists = true;
    }

    return $bExists;

  }


  /**********************************************************************
  * GetPlayerAvatarImageURL
  *
  */
  function GetPlayerAvatarImageURL($PID){

    $strURL = "N/A";

    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $strURL = $this->SiteURL."/avatars/".mysql_result($return, 0, "a_imgname");
    }

    return $strURL;

  }

  /**********************************************************************
  * GetBuddyListmobile
  *
  */
  function GetBuddyListmobile($PID){

    $query = "SELECT * FROM c4m_buddylist WHERE player_id = ".$PID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);
    
	echo "<BUDDIES>";
	while($row = mysql_fetch_array($return, MYSQL_NUM))
	{
		echo "<ID>";
		echo $row[2];
		echo "</ID>";
	}
	echo "</BUDDIES>";
	
	// echo "<ID>";
    // echo "1";
    // echo "</ID>";
	

//  if($num != 0){
//	while ($row = mysql_fetch_array($return, MYSQL_NUM)) {
//	  echo "<ID>";
//	  echo $row[2];
//	  echo "</ID>";
//    }
//  }else{
//cho "IDS_NULL";
//  )
  }


  /**********************************************************************
  * SetChessPointCacheData
  *
  */
  function SetChessPointCacheData($nPlayerID, $nPoints){

    $query = "SELECT * FROM cfm_point_caching WHERE player_id=".$nPlayerID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE cfm_point_caching SET points='".$nPoints."' WHERE player_id=".$nPlayerID;
      mysql_query($update, $this->link) or die(mysql_error());

    }else{

      $insert = "INSERT INTO cfm_point_caching values(".$nPlayerID.", '".$nPoints."')";
      mysql_query($insert, $this->link) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetChessPointCacheData
  *
  */
  function GetChessPointCacheData($nPlayerID){

    $nPoints=0;

    $query = "SELECT * FROM cfm_point_caching WHERE player_id=".$nPlayerID;
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $nPoints = mysql_result($return, 0, "points");
    }

    return $nPoints;

  }


  /**********************************************************************
  * DeleteMessageFromInboxForMobile
  *
  */
  function DeleteMessageFromInboxForMobile($ConfigFile, $PlayerID, $InboxID){

    $query = "DELETE FROM c4m_msginbox WHERE inbox_id=".$InboxID." AND player_id=".$PlayerID;

    if($InboxID == 0){
      $query = "DELETE FROM c4m_msginbox WHERE player_id=".$PlayerID;
    }

    // Remove Item
    mysql_query($query, $this->link) or die(mysql_error());

  }


  /**********************************************************************
  * CheckLoginCredentialsForMobile
  *
  */
  function CheckLoginCredentialsForMobile($ConfigFile, $UserID, $Pass){

    $bReturn = false;

    $query = "SELECT * FROM player WHERE userid='".$UserID."' AND password='".$Pass."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
       $bReturn  = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * CheckChessClubExistsByID
  *
  */
  function CheckChessClubExistsByID($ClubID){

    $query = "SELECT * FROM chess_club WHERE o_id = '".$ClubID."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    $bExists = false;

    if($num != 0){
      $bExists = true;
    }

    return $bExists;

  }


  /**********************************************************************
  * GetClubMemberlistForMobile
  *
  */
  function GetClubMemberlistForMobile($PlayerID){

    if($this->IsUserInClub($PlayerID)){

      // get the member's club id
      $query = "SELECT * FROM chess_club_members WHERE o_playerid=".$PlayerID."";
      $return = mysql_query($query, $this->link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $o_chessclubid = mysql_result($return,0,"o_chessclubid");

        $query1 = "SELECT * FROM chess_club_members WHERE o_chessclubid = '".$o_chessclubid."'";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          echo "<RESPONSE>\n";
          echo "<CLUBMEMBERS>\n";

          $i=0;
          while($i < $num1){

            $o_playerid = mysql_result($return1, $i, "o_playerid");
            $o_owner = mysql_result($return1, $i, "o_owner");
            $o_active = mysql_result($return1, $i, "o_active");

            echo "<CLUBMEMBER>\n";
            echo "<PLAYERID>".$o_playerid."</PLAYERID>\n";
            echo "<RATING>".$this->GetChessPointCacheData($o_playerid)."</RATING>\n";
            echo "<AVATAR>".$this->GetPlayerAvatarImageURL($o_playerid)."</AVATAR>\n";
            echo "</CLUBMEMBER>\n";

            $i++;
          }

          echo "</CLUBMEMBERS>\n";
          echo "</RESPONSE>\n";

        }else{

          echo "<RESPONSE>\n";
          echo "<ERROR>IDS_UNABLE_TO_LOAD_PLAYER_LIST</ERROR>\n";
          echo "</RESPONSE>\n";

        }

      }else{

        echo "<RESPONSE>\n";
        echo "<ERROR>IDS_UNABLE_TO_LOAD_PLAYER_LIST</ERROR>\n";
        echo "</RESPONSE>\n";

      }

    }else{

      echo "<RESPONSE>\n";
      echo "<ERROR>IDS_USER_NOT_IN_CLUB</ERROR>\n";
      echo "</RESPONSE>\n";

    }

  }


  /**********************************************************************
  * CachePlayerPointsByPlayerID
  *
  */
  function CachePlayerPointsByPlayerID($PlayerID){

    $x_wins=0;
    $x_loss=0;
    $x_draws=0;
    $xPoints=0;

    $this->GetPlayerStatusrRefByPlayerID($ConfigFile, $PlayerID, $x_wins, $x_loss, $x_draws);

    if($this->ELOIsActive()){
      $xPoints = $this->ELOGetRating($PlayerID);
    }else{
      $xPoints = $this->GetPointValue($x_wins, $x_loss, $x_draws);
    }

    $this->SetChessPointCacheData($PlayerID, $xPoints);

    return true;

  }


  /**********************************************************************
  * GetClubMembershipDetails
  *
  */
  function GetClubMembershipDetails($PlayerID, &$aDetails){

    $aDetails = array();

    $query = "SELECT chess_club.o_id, chess_club.o_clubname, chess_club_members.o_owner FROM chess_club, chess_club_members WHERE chess_club.o_id = chess_club_members.o_chessclubid AND chess_club_members.o_playerid=".$PlayerID." AND chess_club_members.o_active='y'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $aDetails[0] = mysql_result($return, 0, "o_id");
      $aDetails[1] = mysql_result($return, 0, "o_clubname");
      $aDetails[2] = mysql_result($return, 0, "o_owner");

    }

  }


  /**********************************************************************
  * ManageAbandonedPlayers
  *
  */
  function ManageAbandonedPlayers(){

    // select all the players that are older than 7 days and have not logged in
    $query = "SELECT player.player_id, player.signup_time FROM player LEFT OUTER JOIN player_last_login ON player.player_id = player_last_login.o_playerid WHERE player_last_login.o_date IS NULL AND player.signup_time <= ".(time() - 604800)."";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $player_id = mysql_result($return, $i, "player_id");

        // Check to see if the player has any games
        $query1 = "SELECT COUNT(game_id) FROM game WHERE w_player_id=".$player_id." OR b_player_id=".$player_id."";
        $return1 = mysql_query($query1, $this->link) or die(mysql_error());
        $num1 = mysql_numrows($return1);

        if($num1 != 0){

          $nGameCount = mysql_result($return1, 0, 0);

          if($nGameCount == 0){

            // Remove the player
            $delete = "DELETE FROM player WHERE player_id=".$player_id;
            mysql_query($delete, $this->link) or die(mysql_error());

          }

        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * IsUserNameLegal
  *
  */
  function IsUserNameLegal($UserName){

    $bReturn = false;

    // Check the player name.
    $bInvalidSymbols = false;
    $aInvalidSymbols = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "+", "=", "{", "}", "[", "]", "|", "\\", ":", ";", "\"", "'", "<", ">", "?", "/", ",", " ");

    $nMAX = count($aInvalidSymbols);
    for($i=0; $i < $nMAX; $i++){

      $pos = strpos($UserName, $aInvalidSymbols[$i]);
      if($pos !== false){
        $bInvalidSymbols = true;
        break;
      }

    }

    // check the length of the player name
    $bNameLengthInvalid = true;
    $nMaxLength = 11;
    if(strlen($UserName) <= $nMaxLength){
      $bNameLengthInvalid = false;
    }

    if(!$bInvalidSymbols && !$bNameLengthInvalid){
      $bReturn = true;
    }

    return $bReturn;

  }


  /**********************************************************************
  * CheckUserNameLogin
  *
  */
  function CheckUserNameLogin($OldUserName, $Password){

    $strReturn = "";

    // Get the username if the player exists
    $query = "SELECT player.userid FROM player, c4m_invalid_players WHERE player.player_id = c4m_invalid_players.player_id AND c4m_invalid_players.olduserid = '".$OldUserName."' AND player.password = '".$Password."'";
    $return = mysql_query($query, $this->link) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $strReturn = mysql_result($return, 0, "userid");
    }

    return $strReturn;

  }


  /**********************************************************************
  * ChangeUserNameByOldName
  *
  */
  function ChangeUserNameByOldName($OldUserName, $NewUserName){

    $bReturn = false;
    $player_id = $this->GetIDByUserID($ConfigFile, $OldUserName);

    if(is_numeric($player_id)){

      if($player_id != 0){

        // Change the username in all tables
        $update = "UPDATE player SET userid='".$NewUserName."' WHERE player_id=".$player_id."";
        mysql_query($update, $this->link) or die(mysql_error());

        $update = "UPDATE player2 SET userid='".$NewUserName."' WHERE player_id=".$player_id."";
        mysql_query($update, $this->link) or die(mysql_error());

        $update = "UPDATE player3 SET userid='".$NewUserName."' WHERE player_id=".$player_id."";
        mysql_query($update, $this->link) or die(mysql_error());

        $update = "UPDATE admin_player_credits_request SET o_userid='".$NewUserName."' WHERE o_playerid=".$player_id."";
        mysql_query($update, $this->link) or die(mysql_error());

        $update = "UPDATE whos_online_graph SET o_player='".$NewUserName."' WHERE o_player='".$OldUserName."'";
        mysql_query($update, $this->link) or die(mysql_error());

        $update = "UPDATE c4m_playerorders SET o_username='".$NewUserName."' WHERE o_username='".$OldUserName."'";
        mysql_query($update, $this->link) or die(mysql_error());

        $bReturn = true;

      }

    }

    return $bReturn;

  }


  /**********************************************************************
  * GetActualFEN
  *
  */
  function GetActualFEN($sid, $GameID){

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->request_FEN($this->ChessCFGFileLocation, $GameID);
    unset($oChess);

    $returncode = "0000000000".$returncode;

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = explode("/", substr(str_replace(strrchr($returncode, "/"), "", $returncode),10), 8);

    //Get the rest of the fen string
    list($j1, $j2, $j3, $j4, $j5, $j6, $j7) = explode(" ", $returncode, 7);

    $backfen = trim($j2." ".$j3." ".$j4." ".$j5." ".$j6." ".$j7);

    //convert the fen
    $one = $this->ConvertFenRow($one);
    $two= $this->ConvertFenRow($two);
    $three= $this->ConvertFenRow($three);
    $four= $this->ConvertFenRow($four);
    $five= $this->ConvertFenRow($five);
    $six= $this->ConvertFenRow($six);
    $seven= $this->ConvertFenRow($seven);
    $eight= $this->ConvertFenRow($eight);

    $returncode = $eight."/".$seven."/".$six."/".$five."/".$four."/".$three."/".$two."/".$one." ".$backfen;


    return $returncode;

  }


  /**********************************************************************
  * FormatInputedFEN
  *
  */
  function FormatInputedFEN($fen){

    $fen = "0000000000".$fen;

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = explode("/", substr(str_replace(strrchr($fen, "/"), "", $fen),10), 8);

    //Get the rest of the fen string
    list($j1, $j2, $j3, $j4, $j5, $j6, $j7) = explode(" ", $fen, 7);

    $backfen = trim($j2." ".$j3." ".$j4." ".$j5." ".$j6." ".$j7);

    //convert the fen
    $one = $this->ConvertFenRow($one);
    $two= $this->ConvertFenRow($two);
    $three= $this->ConvertFenRow($three);
    $four= $this->ConvertFenRow($four);
    $five= $this->ConvertFenRow($five);
    $six= $this->ConvertFenRow($six);
    $seven= $this->ConvertFenRow($seven);
    $eight= $this->ConvertFenRow($eight);

    $fen = $eight."/".$seven."/".$six."/".$five."/".$four."/".$three."/".$two."/".$one." ".$backfen;

    return $fen;

  }


  /**********************************************************************
  * FormatInputedFEN2
  *
  */
  function FormatInputedFEN2($fen){

    $aFEN = explode(" ", $fen);

    // Format/Decode the FEN
    list($one, $two, $three, $four, $five, $six, $seven, $eight) = explode("/", $aFEN[0], 8);

    //Get the rest of the fen string
//    list($j1, $j2, $j3, $j4, $j5, $j6, $j7) = split(" ", $fen, 7);

    $backfen = $aFEN[1]." ".$aFEN[2]." ".$aFEN[3]." ".$aFEN[4]." ".$aFEN[5];  //trim($j2." ".$j3." ".$j4." ".$j5." ".$j6." ".$j7);


//rnbqkb1r/pppppppp/5n2/8/6P1/2N5/PPPPPP1P/R1BQKBNR b KQkq - 0 0





    //convert the fen
    $one = $this->ConvertFenRow($one);
    $two= $this->ConvertFenRow($two);
    $three= $this->ConvertFenRow($three);
    $four= $this->ConvertFenRow($four);
    $five= $this->ConvertFenRow($five);
    $six= $this->ConvertFenRow($six);
    $seven= $this->ConvertFenRow($seven);
    $eight= $this->ConvertFenRow($eight);

    $fen = $eight."/".$seven."/".$six."/".$five."/".$four."/".$three."/".$two."/".$one." ".$backfen;

    return $fen;

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){

    //mysql_close($this->link);

  }


} //end of class definition
?>
