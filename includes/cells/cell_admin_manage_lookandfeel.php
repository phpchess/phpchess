<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TXT_1", $config);?>

<br><br>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='4'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_HEADER", $config);?></font>
</td>
</tr>
<tr>
<td>
<center>
<a href='./cfg_skins.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_1", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_1", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_userreg.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_2", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_2", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_tourreg.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_3.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_3", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_3", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_links.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_4.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_4", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_4", $config);?>
</center>
</td>
</tr>

<tr>
<td>
<center>
<a href='./manage_enable_billing.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_5.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_5", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_5", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_email.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_6.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_6", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_6", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_language.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_7.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_7", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_7", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_ratings.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_8.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_8", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_8", $config);?>
</center>
</td>
</tr>

<tr>
<td>
<center>
<a href='./cfg_srv_email.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_9.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_9", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_9", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_srv_game_opts.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_10.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_10", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_10", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_srv_filter.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_11.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_11", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_11", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_email_log.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_12.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_12", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_12", $config);?>
</center>
</td>
</tr>

<tr>
<td>
<center>
<a href='./cfg_chessboard_colors.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_14.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_14", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_14", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_avatar_settings.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_15.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_15", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_15", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_player_credits.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_16.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_16", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_16", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_activity_config.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_17.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_17", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_17", $config);?>
</center>
</td>
</tr>

<tr>
<td>
<center>
<a href='./cfg_pchat_config.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_18.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_18", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_18", $config);?>
</center>
</td><td>
<center>
<a href='./cfg_cronjob_config.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_19.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_19", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_19", $config);?>
</center>
</td><td>
<center>
<a href='./manage_db.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_5_20.gif' border='0' alt="<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_20", $config);?>"></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_LINK_20", $config);?>
</center>
</td><td>
</td><td>

</td>
</tr>
</table>
