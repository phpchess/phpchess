<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" border="0" cellspacing="8" cellpadding="0" id="footlinks">
			  <tr>
				<td><a href="admin_main.php">Main</a> | <a href="chess_tournament_v2.php">Tournament Setup</a> 
                  | <a href="create_newsletter.php">Create NewsLetter</a> | <a href="create_activity.php">Create Activity</a> | 
                  <a href="manage_players.php">Players</a> | <a href="manage_billing_data.php">Player Billing</a> | <a href="manage_news_data.php">News/Msc Data</a> 
                  | <a href="manage_lookandfeel.php">Server Management</a><?php if($_SESSION['UNAME'] != ""){ ?>| <a href="admin_logout.php">Logout</a><?php }else{ ?>| <a href="index.php">Login</a><?php } ?></td>
			  </tr>
			  <tr>
				<td>&nbsp;</td>
			  </tr>
			</table>