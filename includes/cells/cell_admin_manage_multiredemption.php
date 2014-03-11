<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmRedemption' method='post' action='./manage_multiredemption.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_MULTIREDEMPTION_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtCode' class='post' size='40'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_MULTIREDEMPTION_BTN_AC", $config);?>' name='cmdCreate' class='mainoption'></td>
</tr>

<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_MULTIREDEMPTION_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row2' colspan='2'>
<?php
  $bLimit = $oR3DCQuery->GetMultiUserRedemptionCodes();
?>
</td>
</tr>

<tr>
<td class='row2' colspan='2'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_MULTIREDEMPTION_BTN_DC", $config);?>' name='cmdDelete' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_5", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_billing_data.php';">
</center>
<br>