<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TXT_1", $config);?>

<br><br>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='4'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TABLE_HEADER", $config);?></font>
</td>
</tr>
<tr>
<td>
<center>
<a href='./admin_new_players.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_2_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TABLE_TXT_1", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TABLE_LINK_1", $config);?>
</center>
</td><td>
<center>
<a href='./admin_player_list2.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_2_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TABLE_TXT_2", $config);?>'></a>
<br>
<?php echo __l('Manage Current Players');?>
</center>
</td><td>
<center>
<a href='./admin_player_list.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_2_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERS_TABLE_TXT_2", $config);?>'></a>
<br>
<?php echo __l('Disable/Enable Players');?>
</center>
</td>
</tr>
</table>