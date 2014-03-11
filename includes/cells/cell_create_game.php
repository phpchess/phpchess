<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='4'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_1", $config);?></font>
</td>
</tr>
<tr>
<td>
<center>
<a href='./chess_create_game_nm.php?othpid=<?php echo $othpid;?>'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cg_1.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_2a", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_2", $config);?>
</center>
</td><td>
<center>
<a href='./chess_create_game_pr.php?othpid=<?php echo $othpid;?>'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cg_2.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_3a", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_3", $config);?>
</center>
</td><td>
<center>
<a href='./chess_create_game_ar.php?othpid=<?php echo $othpid;?>'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cg_3.gif' border='0' alt='<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_4a", $config);?>'></a>
<br>
<?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_4", $config);?>
</center>
</td>
</tr>

</table>