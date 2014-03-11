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
  require($Root_Path."includes/language.php");

  $GID = $_GET['gid'];
  $gid = $GID;

  include ($Root_Path."includes/support_chess.inc");
  include ($Root_Path."includes/chess.inc"); 

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  if(isset($_SESSION['id'])){
    $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id']);
  }

  $cmdResign = $_GET['cmdResign'];
  if($cmdResign != ""){
    if($isblack){
      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");  
    }else{
      $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
    }
  }
  $oR3DCQuery->Close();
  unset($oR3DCQuery);

  $txtmovefrom = $_GET['txtmovefrom'];
  $txtmoveto = $_GET['txtmoveto'];

  if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id']) && $GID != "" && $txtmovefrom != "" && $txtmoveto != ""){

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    if($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID) && $oR3DCQuery->TimeForTGame($config, $GID) && $oR3DCQuery->TGameStatus($GID)){

      $movestr2 = $txtmovefrom."-".$txtmoveto;

      // get the fen for the game

      $fen = $oR3DCQuery->GetHackedFEN($_SESSION['sid'], $GID);

      //check to see if the move is valid
      if(is_Move_legal($fen, $movestr2)){ 

        //if ! promotion screen
        if(!($move_promotion_figur && strlen($movestr2)==5)) {
           $Move= $txtmovefrom.",".$txtmoveto;

    	  $oR3DCQuery->CurrentGameMovePiece($config, $gid, $_SESSION['sid'], $_SESSION['id'], $Move);
		
	  //checkmate
          if(get_GameState() == 1){

            if($w_player_id == $_SESSION['id']){
             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "B");
             $bmove_error = false;
              
            }else{
              $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "W");
              $bmove_error = false;
            }
         
          }

          //draw
          if(get_GameState() == 2){
             $oR3DCQuery->UpdateGameStatus($config, $gid, "C", "D");
             $bmove_error = false;
          }

	  unset($oR3DCQuery);

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

          $oR3DCQuery->GetGameInfoByRef($config, $GID, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);


          //checkmate
          if(get_GameState() == 1){

            if($w_player_id == $_SESSION['id']){
             $oR3DCQuery->UpdateGameStatus($config, $GID, "C", "B");
             $bmove_error = false;
              
            }else{
              $oR3DCQuery->UpdateGameStatus($config, $GID, "C", "W");
              $bmove_error = false;
            }
         
          }

          //draw
          if(get_GameState() == 2){
             $oR3DCQuery->UpdateGameStatus($config, $GID, "C", "D");
             $bmove_error = false;
          }

          if($bmove_error == true){
            echo "".GetStringFromStringTable("IDS_TCTRLS_TXT_9", $config)."";
          }

        }
          
      }



    }else{

      if($oR3DCQuery->TGameStatus($GID) == false){
        echo "".GetStringFromStringTable("IDS_TCTRLS_TXT_1", $config)."";
      }else{
        echo "".GetStringFromStringTable("IDS_TCTRLS_TXT_2", $config)."";
      }

    }
    
    unset($oR3DCQuery);

  }

?>

<html>
<head>
<title>Tournament Game</title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<script language="JavaScript">
<?php echo "parent.frames['chessboard'].location.reload();"?> 
</script>

<?php

  if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);

    $oR3DCQuery->PlayerLoginForTGame($config, $GID, $_SESSION['id']);

    if($oR3DCQuery->GetTGCanPlay($config, $GID, $_SESSION['id'])==1){

      if($oR3DCQuery->TimeForTGame($config, $GID)){

        echo "<form name='frmTMove' method='get' action='./t_ctrls.php'>";
        echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
        echo "<tr>";
        echo "<td valign='top' class='row1'>";
        echo "<font class='sitemenuheader'>".GetStringFromStringTable("IDS_TCTRLS_TXT_3", $config)."</font>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td valign='top' class='row2'>";
        echo "".GetStringFromStringTable("IDS_TCTRLS_TXT_4", $config)." <input type='text' name='txtmovefrom' class='post' size='4'> ".GetStringFromStringTable("IDS_TCTRLS_TXT_5", $config)." <input type='text' name='txtmoveto' class='post' size='4'> <input type='submit' name='cmdMove' value='".GetStringFromStringTable("IDS_TCTRLS_BTN_PM", $config)."' class='mainoption'><input type='submit' name='cmdResign' value='".GetStringFromStringTable("IDS_TCTRLS_BTN_R", $config)."' class='mainoption'>";
        echo "</td>";
        echo "</tr>";

        echo "<input type='hidden' name='gid' value='".$GID."'>";
        echo "</table>";

      }else{
        echo "".GetStringFromStringTable("IDS_TCTRLS_TXT_6", $config)."";
      }

    }else{

      $uname = "Anonymous";
      if(isset($_SESSION['user'])){
        $uname = $_SESSION['user'];
      }

      echo str_replace("['uname']", $uname, GetStringFromStringTable("IDS_TCTRLS_TXT_7", $config));

      if($oR3DCQuery->TimeForTGame($config, $GID) == false){
        echo "<br>".GetStringFromStringTable("IDS_TCTRLS_TXT_8", $config)."";
      }
 
    }
    $oR3DCQuery->Close();
    unset($oR3DCQuery);

  }else{

    $uname = "Anonymous";
    if(isset($_SESSION['user'])){
     $uname = $_SESSION['user'];
    }

    echo str_replace("['uname']", $uname, GetStringFromStringTable("IDS_TCTRLS_TXT_7", $config));

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);

    if($oR3DCQuery->TimeForTGame($config, $GID) == false){
      echo "<br>".GetStringFromStringTable("IDS_TCTRLS_TXT_8", $config)."";
    }
    $oR3DCQuery->Close();
    unset($oR3DCQuery);
  }

?>

</body>
</html>