<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<br>
<font class='menubulletcolor'>
&nbsp; &#8226; &nbsp<a href='./chess_register.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_1", $config);?></a><br>
&nbsp; &#8226; &nbsp<a href='./chess_club_list.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_6", $config);?></a><br>
&nbsp; &#8226; &nbsp<a href='./chess_faq.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_2", $config);?></a><br>
&nbsp; &#8226; &nbsp<a href='./chess_tournament_status.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_3", $config);?></a><br>
&nbsp; &#8226; &nbsp<a href='./chess_jchess.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_4", $config);?></a><br>
&nbsp; &#8226; &nbsp<a href="javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php');" class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_OTHER_5", $config);?></a><br>

</font>
<br>
