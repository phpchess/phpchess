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

  $tid = $_GET['tid'];
  $type = $_GET['type'];

?>
<html>
<head>
<title></title>
</head>

<frameset rows="2%, 50%, 30%, 18%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" name='frHandler' src="./tv2_console_handler.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>">

  <frameset cols="200, 700" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

    <frame noresize="noresize" name='frmenu1' src="./tv2_console_menu1.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>">
    <frame noresize="noresize" name='frchat1' src="./tv2_console_chat1.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>">

  </frameset>

  <frame noresize="noresize" name='frmain' src="./tv2_console_main.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>">
  <frame noresize="noresize" src="./tv2_console_mediation.php"> 
</frameset> 

</html>