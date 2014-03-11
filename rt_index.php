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

  session_start(); 
  $_SESSION['Refreashed'] = "";
  $_SESSION['DisplayedDraw'] = "";
  $_SESSION['DisplayedGameEnded'] = "";
  $_SESSION['DisplayedRTEnded'] = "";
  $_SESSION['RefreashGameOnlyOnce'] = "";

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";

  require($Root_Path."includes/language.php");
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

  $GID = $_GET['gid'];
  $PlayerName = $_GET['pn'];

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_45", $config);?> <?php echo $PlayerName;?></title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>

<frameset rows="2%,98%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" src="./rt_handler.php?gid=<?php echo $GID;?>">
  <frame noresize="noresize" name="chessboard" src="./chess_game3.php?gameid=<?php echo $GID;?>">

</frameset> 

</html>