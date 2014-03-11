<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php $oR3DCQuery->GetEmailSettings($strTermOver);?>

<form name='frmManageuser' method='post' action='./cfg_email.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>

<tr>
<td class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_EMAIL_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row2'>
<textarea name='txtTermOver' class='post' rows='10' cols='80'><?php echo $strTermOver;?></textarea>
</td>
</tr>

<tr>
<td class='row1'><input type='submit' name='cmdUpdate' value='<?php echo GetStringFromStringTable("IDS_ADMIN_EMAIL_BTN_UPDATE", $config);?>' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>