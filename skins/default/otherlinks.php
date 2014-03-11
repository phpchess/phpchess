<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td><h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_7", $config);?></h3>
					<ul class="mainmenu">
<!--						<li><a href="./chess_register.php"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_1", $config);?></a></li> -->
						<li><a href="./chess_club_list.php"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_6", $config);?></a></li>
						<li><a href="./chess_faq.php"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_2", $config);?></a></li>
						<li><a href="./chess_tournament_status.php"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_3", $config);?></a></li>
						<li><a href="./chess_jchess.php"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_4", $config);?></a></li>
						<li><a href="javascript:PopupPGNGame('./pgnviewer/view_pgn_game.php');"><?php echo GetStringFromStringTable("IDS_MENU_OTHER_5", $config);?></a></li>
					</ul>

&nbsp;</td>
  </tr>
</table>