<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

 //////////////////////////////////////////////////////////////////
 //  Left Menu Include
 //  Administration Page
 //

?>
<table width="207" border="0" cellspacing="0" cellpadding="0" align="center">
     <tr>
      <td align="left"><h3 class="menu_title">Navigation</h3>
		<ul class="mainmenu">
		<li class="active_mainmenu"><a href="admin_main.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_1", $config);?></a></li>
		<li ><a href="chess_tournament_v2.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_2", $config);?></a></li>		
		<li><a href="create_newsletter.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_3", $config);?></a></li>
		<li><a href="create_activity.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_17", $config);?></a></li>		
		<li><a href="manage_players.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_4", $config);?></a></li>
		<li><a href="admin_game_list.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_18", $config);?></a></li>
		<li><a href="manage_billing_data.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_5", $config);?></a></li>
		<li><a href="manage_news_data.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_6", $config);?></a></li>
		<li><a href="manage_lookandfeel.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_7", $config);?></a></li>
		<?php if($_SESSION['UNAME'] != ""){ ?>
		<li><a href="admin_logout.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_8", $config);?></a></li>
		<?php }else{ ?><li><a href="index.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_9", $config);?></a></li><?php } ?></ul></td>
        </tr>
		<tr>
      <td align="left" class="white"><h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_1", $config);?></h3>
	  <?php
  $AllGames = 0;
  $TGames = 0;

  $oR3DCQuery->GetOngoingGameCount2($config, $AllGames, $TGames);

  echo "<br>";
  echo "&nbsp;".GetStringFromStringTable("IDS_MENU_TXT_23", $config)." ".$AllGames;
  echo "<br>";
  echo "&nbsp;".GetStringFromStringTable("IDS_MENU_TXT_24", $config)." ".$TGames;
  echo "<br>";
  echo "<br>";
?>
	  </td>
        </tr>
		<tr>
      <td align="left" class="white">
	  <h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_2", $config);?></h3>

		<?php $oR3DCQuery->GetTopPlayers($config);?>
	   </td>
        </tr>
</table>





