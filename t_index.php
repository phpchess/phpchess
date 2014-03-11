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

  include($Root_Path."skins/".$SkinName."/tournament_cfg.php");

  $GID = $_GET['gid'];
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_44", $config);?></title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>

<frameset rows="<?php echo $tconfig[0];?>" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" src="t_top.php?gid=<?php echo $GID;?>">

  <frameset cols="<?php echo $tconfig[1];?>" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

    <frameset rows="<?php echo $tconfig[2];?>" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>
      <frame noresize="noresize" src="t_chat.php?gid=<?php echo $GID;?>">
      <frame noresize="noresize" src="./t_chatctrls.php?gid=<?php echo $GID;?>">
    </frameset> 

    <frameset rows="<?php echo $tconfig[3];?>" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>
      <frame noresize="noresize" name = "chessboard" src="./t_game.php?gid=<?php echo $GID;?>">
      <frame noresize="noresize" src="./t_ctrls.php?gid=<?php echo $GID;?>">
    </frameset> 

  </frameset> 

</frameset> 

</html>