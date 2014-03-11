<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<!--Header Begin-->
<?php include($Root_Path."skins/".$SkinName."/admin_header.php");?>
<!--Header End-->

<table width="100%" border="0" cellspacing="0" cellpadding="0" align='center'>
<tr>
<td></td>
<td width="100%" class="tbltop">&nbsp;</td>
<td></td>
</tr>
<tr>
<td class="tblleft">&nbsp;</td>
<td>

<table width="100%" border="0" cellspacing="0" cellpadding="0" class='forumline2'>
<tr>
<td class='forumline2r' valign='top' width='<?php echo $LeftMenuSize;?>'>

<!--Left Menu Begin-->
<?php include($Root_Path."skins/".$SkinName."/admin_left_menu.php");?>
<!--Left Menu Finish-->

<!--Right Menu Begin-->
<?php include($Root_Path."skins/".$SkinName."/admin_right_menu.php");?>
<!--Right Menu Finish-->

</td>
<td valign='top'>

<table class='forumline2l' width="96%" cellpadding="0" cellspacing="0" border="0" align='center'>
<tr>
<td>
<!--Content Begin-->
<?php include($Root_Path."includes/cells/".$Contentpage);?>
<!--Content Finish-->
</td>
</tr>
</table>

</td>
</tr>
</table>

</td>
<td class="tblright">&nbsp;</td>
</tr>
<tr>
<td></td>
<td class="tblbot">&nbsp;</td>
<td></td>
</tr>
</table>

<?php /* ?>
<table width="100%" border="0" cellspacing="0" cellpadding="2" align='center'>
<tr>
<td align="center" class="topnav">
<!--Footer Begin-->
<?php include($Root_Path."skins/".$SkinName."/footer.php");?>
<!--Footer End-->
</td>
</tr>
</table>

<?php */ ?>


<table bgcolor="#f0f0f0" border="0" cellpadding="0" cellspacing="0" width="779" align="center" valign="bottom">
<tr>
<!-- Shim row, height 1. -->
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="44" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="84" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="65" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="36" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="55" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="56" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="71" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="72" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="192" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="104" height="1" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="1" height="1" border="0" alt=""></td>
  </tr>

  <tr><!-- row 6 -->
   <td colspan="10"><img name="layers_f2" src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/layers_f2.gif" width="779" height="23" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="1" height="23" border="0" alt=""></td>
  </tr>
  <tr><!-- row 7 -->
   <td colspan="10"><img name="layers_f1" src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/layers_f1.gif" width="779" height="27" border="0" alt=""></td>
   <td><img src="<?php echo $Root_Path."skins/".$SkinName."/";?>images/spacer.gif" width="1" height="27" border="0" alt=""></td>
  </tr>
</table>