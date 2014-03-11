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

  $nJoinTime = time();

?>

<html>
<head>
<title></title>
</head>

<frameset rows="2%, 98%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" name='frHandler' src="./pc_handler.php?jtime=<?php echo $nJoinTime;?>">

  <frameset cols="200, 700" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

    <frame noresize="noresize" name='frmenu1' src="./pc_menu1.php?jtime=<?php echo $nJoinTime;?>">
    <frame noresize="noresize" name='frchat1' src="./pc_chat1.php?jtime=<?php echo $nJoinTime;?>">

  </frameset>
</frameset> 

</html>