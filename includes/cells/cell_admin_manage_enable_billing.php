<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmManageuser' method='post' action='./manage_enable_billing.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>

<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_1_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td colspan='2' class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_1_TXT_1", $config);?></td>
</tr>

<?php
$bRequiresApproval = $oR3DCQuery->PaypalIsEnabled();

if($bRequiresApproval == true){
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdorapprove' value='1' CHECKED></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_SELECT_YES", $config);?></td>
  </tr>
<?php
}else{
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdorapprove' value='1'></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_SELECT_YES", $config);?></td>
  </tr>
<?php
}

if($bRequiresApproval == false){
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdorapprove' value='0' CHECKED></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_SELECT_NO", $config);?></td>
  </tr>
<?php
}else{
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdorapprove' value='0'></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_SELECT_NO", $config);?></td>
  </tr>
<?php
}
?>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChange' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_BTN_CV", $config);?>' class='mainoption'></td>
</tr>

</table>
</form>

<br>
<?php

  $oBilling->GetPaypalInfo($email, $Cur);
  $price = $oBilling->GetDefinedPrice();

?>

<form name='frmManagePaypal' method='post' action='./manage_enable_billing.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>

<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_2_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_2_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtEmail' class='post' size='30' value='<?php echo $email;?>'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_2_TXT_2", $config);?></td>
<td class='row2'>

<select name='slctCurrency'>
<?php
if($Cur == "USD"){
?>
<option value='USD' selected>USD</option>
<?php
}else{
?>
<option value='USD'>USD</option>
<?php
}
?>

<?php
if($Cur == "CAD"){
?>
<option value='CAD' selected>CAD</option>
<?php
}else{
?>
<option value='CAD'>CAD</option>
<?php
}
?>

<?php
if($Cur == "EUR"){
?>
<option value='EUR' selected>EUR</option>
<?php
}else{
?>
<option value='EUR'>EUR</option>
<?php
}
?>

</select>

</td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_TABLE_2_TXT_3", $config);?></td><td class='row2'><input type='text' name='txtCharge' class='post' size='30' value='<?php echo $price;?>'></td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdUpdate' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EBILLS_BTN_UPI", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>