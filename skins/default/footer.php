<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" border="0" cellspacing="8" cellpadding="0" id="footlinks">
	<tr>
		<td><a href="<?php echo $Root_Path;?>chess_members.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_10", $config);?></a> | <a href="<?php echo $Root_Path;?>chess_msg_center.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_11", $config);?></a> 
                  | <a href="<?php echo $Root_Path;?>chess_view_games.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_12", $config);?></a> | <a href="<?php echo $Root_Path;?>chess_find_player.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_13", $config);?></a> | 
                  <a href="<?php echo $Root_Path;?>chess_buddy_list.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_14", $config);?></a> | <a href="<?php echo $Root_Path;?>chess_statistics.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_15", $config);?></a> | <a href="<?php echo $Root_Path;?>chess_cfg.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_16", $config);?></a> 
                  | <a href="<?php echo $Root_Path;?>chess_login.php"><?php echo GetStringFromStringTable("IDS_MENU_TXT_20", $config);?></a></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
