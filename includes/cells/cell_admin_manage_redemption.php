<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($RedemptionCode != ""){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtcode' class='post' value='<?php echo $RedemptionCode;?>' size='40'></td>
</tr>
<tr>
<td class='row2' colspan='2'><?php echo base64_decode($RedemptionCode);?></td>
</tr>
</table>
<br>
<?php
}
?>

<form name='frmRedemption' method='post' action='./manage_redemption.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>

<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_TABLE_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtUName' class='post'  size='40'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_TABLE_TXT_3", $config);?></td>
<td class='row2'>
<select name='slctTerm'>
<option value='M'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_M", $config);?></option>
<option value='S'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_SA", $config);?></option>
<option value='Y'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_Y", $config);?></option>
</select>
</td>
</tr>

<tr>
<td class='row2' colspan='2'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_BTN_CC", $config);?>' name='cmdCreate' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_5", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_billing_data.php';">
</center>
<br>