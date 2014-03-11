<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TXT_1", $config);?>

<br><br>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='4'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLES_HEADER", $config);?></font>
</td>
</tr>
<tr>
<td>
<center>
<a href='./manage_front_news.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_4_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_TXT_1", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_LINK_1", $config);?>
</center>
</td><td>
<center>
<a href='./manage_tipoftheday.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_4_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_TXT_2", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_LINK_2", $config);?>
</center>
</td><td>
<center>
<a href='./manage_servermsg.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_4_3.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_TXT_3", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWSDATA_TABLE_LINK_3", $config);?>
</center>
</td><td>
<center>
<a href='./edit_faq.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_4_3.gif' border='0' alt='<?php echo __l('Edit FAQ');?>'></a>
<br>
<?php echo __l('Edit FAQ');?>
</center>
</td>
</tr>
</table>