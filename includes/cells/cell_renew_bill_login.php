<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmLogin' method='post' action='./renew_bill.php'>
<table cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_RENEWBILLLOGIN_TABLE_HEADER", $config);?></font>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_RENEWBILLLOGIN_TABLE_TXT_1", $config);?></td>
<td class='row2'><Input type='text' name='txtName' size='40' class="post" value=''></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_RENEWBILLLOGIN_TABLE_TXT_2", $config);?></td>
<td class='row2'><Input type='password' name='txtPassword' size='40' class="post" value=''></td>
</tr>

<tr>
<td colspan='2' class='row1'>
<input type='submit' name='cmdLogin' value='<?php echo GetStringFromStringTable("IDS_RENEWBILLLOGIN_BTN_L", $config);?>' class='mainoption'>
<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_RENEWBILLLOGIN_BTN_R", $config);?>' class='button'>
</td>
</tr>
</table>
</form>