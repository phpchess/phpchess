<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" border="0" cellspacing="8" cellpadding="0" id="footlinks">
	<tr>
		<td><a href="admin_main.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_1", $config);?></a> | <a href="chess_tournament_v2.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_2", $config);?></a> 
                  | <a href="create_newsletter.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_3", $config);?></a> | <a href="create_activity.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_17", $config);?></a> | 
                  <a href="manage_players.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_4", $config);?></a> | <a href="manage_billing_data.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_5", $config);?></a> | <a href="manage_news_data.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_6", $config);?></a> 
                  | <a href="manage_lookandfeel.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_7", $config);?></a><?php if($_SESSION['UNAME'] != ""){ ?>| <a href="admin_logout.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_8", $config);?></a><?php }else{ ?>| <a href="index.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_9", $config);?></a><?php } ?></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
	</tr>
</table>
