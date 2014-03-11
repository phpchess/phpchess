<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_1", $config);?>

<br><br>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='4'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_2", $config);?></font>
</td>
</tr>
<tr>
<td>
<center>
<a href='./manage_new_bills.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_3_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_7", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_3", $config);?>
</center>
</td><td>
<center>
<a href='./view_old_bills.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_3_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_8", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_4", $config);?>
</center>
</td><td>
<center>
<a href='./manage_redemption.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_3_3.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_9", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_5", $config);?>
</center>
</td><td>
<center>
<a href='./manage_multiredemption.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_3_4.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_10", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_6", $config);?>
</center>
</td>
</tr>

<tr>
<td>
<center>
<a href='./cfg_player_credit_requests.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_3_5.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_12", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_TXT_11", $config);?>
</center>
</td><td>

</td><td>

</td><td>

</td>
</tr>
</table>



