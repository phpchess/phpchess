<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmCreateFilter' method='post' action='./cfg_srv_filter.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_TXT_1", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtWord' class='post' size='50'> <input type='submit' name='cmdAddWord' value='<?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_BTN_1", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<form name='frmManageFilter' method='post' action='./cfg_srv_filter.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_TXT_3", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_TXT_4", $config);?></td>
</tr>

<tr>
<td class='row2'><?php $oR3DCQuery->GetFilteredWordSelectBox();?></td>
</tr>

<tr>
<td class='row1'><input type='submit' name='cmdRemoveWord' value='<?php echo GetStringFromStringTable("IDS_CHESS_CFG_FILTER_BTN_2", $config);?>' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>