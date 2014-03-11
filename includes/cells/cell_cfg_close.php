<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmCloseAccount' method='post' action='./chess_cfg_close.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CFG_CLOSE_TABLE_HEADER", $config);?></font></td></tr>
<tr>
<td class='row2' align='center' colspan='2'><input type='submit' name='cmdCloseAccount' value='<?php echo GetStringFromStringTable("IDS_CFG_CLOSE_BTN_CLOSE", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>