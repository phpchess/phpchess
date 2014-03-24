<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<table width="100%" cellspacing="10" cellpadding="4">
  <tr>
    <td><h2 class="title_h2"><?php echo GetStringFromStringTable("IDS_CONFIG_INTRO", $config);?></h2>
<br><br>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>

<tr>

<td class='tableheadercolor' colspan='4'>

<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_MANAGE_OPTIONS_TABLE_HEADER", $config);?></font>

</td>

</tr>

<tr>

<td>

<center>

<a href='./chess_cfg_chessboard.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CBC_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CBC", $config);?>

</center>

</td><td>

<center>

<a href='./chess_cfg_buddylist.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_BL_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_BL", $config);?>

</center>

</td><td>

<center>

<a href='./chess_cfg_password.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_3.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CP_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CP", $config);?>

</center>

</td><td>

<center>

<a href='./chess_cfg_avatar.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_4.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_A_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_A", $config);?>

</center>

</td>

</tr>



<tr>

<td>

<center>

<a href='./chess_cfg_information.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_5.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_PI_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_PI", $config);?>

</center>

</td><td>

<center>

<a href='./chess_cfg_notification.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_6.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CN_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CN", $config);?>

</center>

</td><!--<td>

<center>

<a href='./chess_cfg_board.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_7.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_MBL_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_MBL", $config);?>

</center>

</td>--><td>

<center>

<a href='./chess_cfg_clubs.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_8.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CO_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CO", $config);?>

</center>

</td>

<!--</tr>



<tr>-->

<td>

<center>

<a href='./chess_cfg_close.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cfg_1_9.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CA_TXT", $config);?>'></a>

<br>

<?php echo GetStringFromStringTable("IDS_MANAGE_OPTONS_LINK_CA", $config);?>

</center>

</td><td>



</td><td>



</td><td>



</td>

</tr>

</table>

&nbsp;</td>
  </tr>
</table>

