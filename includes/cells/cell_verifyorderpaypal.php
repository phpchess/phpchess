<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($cmdpayment != ""){
//////////////////////////////////////////////////////////////////////////////////////////////

  // Create the bill
  if($txtIsRenew == "1"){
    $orderid = $oBilling->UpdateBill($ID, $txtName, $FirstName, $LastName, $Address, $txtCityTown, $slctCountry, $StateProvence, $PostalZip, $Email, $Phonea, $Phoneb, $Phonec, $txtRedemptionCode, $slctPaymentTerm);
  }else{
    $orderid = $oBilling->CreateNewBill($txtName, $FirstName, $LastName, $Address, $txtCityTown, $slctCountry, $StateProvence, $PostalZip, $Email, $Phonea, $Phoneb, $Phonec, $txtRedemptionCode, $slctPaymentTerm);
  }

  // Create the user
  if($txtIsRenew == ""){
    $ReturnStatus = $oR3DCQuery->RegisterNewPlayer2($txtName, $txtEmail);
  }

  if($txtRedemptionCode != ""){
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_1", $config);?>
</td>
</tr>
</table>

<?php
  }else{

  // Get Paypal INFO
  $nPrice = $oBilling->GetDefinedPrice();
  $oBilling->GetPaypalInfo($PrimaryPaypalEmail, $Currency);

  if($slctPaymentTerm == 's'){
    $OrderCost = $nPrice * 6;
  }elseif($slctPaymentTerm == 'y'){
    $OrderCost = $nPrice * 12;
  }else{
    $OrderCost = $nPrice * 1;
  }
?>

<form action='https://www.paypal.com/cgi-bin/webscr' method='post' id=form1 name=form1>
<input type='hidden' name='cmd' value='_xclick'>
<input type='hidden' name='business' value='<?php echo $PrimaryPaypalEmail;?>'>
<input type='hidden' name='item_name' value='Order #<?php echo $orderid;?>'>
<input type='hidden' name='item_number' value='<?php echo $orderid;?>'>
<input type='hidden' name='amount' value='<?php echo number_format($OrderCost,2,'.', '');?>'>
<input type='hidden' name='no_shipping' value='1'>
<input type='hidden' name='return' value='./payment_complete.php'>
<input type='hidden' name='rm' value='2'>
<input type='hidden' name='cancel_return' value='./payment_canceled.php'>
<input type='hidden' name='no_note' value='1'>
<input type='hidden' name='currency_code' value='<?php echo $Currency;?>'>
<input type='image' src='https://www.paypal.com/images/x-click-but02.gif' border='0' name='submit' alt='Make payments with PayPal - its fast, free and secure!'>

<input type="hidden" name="first_name" value="<?php echo $FirstName;?>">
<input type="hidden" name="last_name" value="<?php echo $LastName;?>">
<input type="hidden" name="address1" value="<?php echo $Address;?>">
<input type="hidden" name="city" value="<?php echo $txtCityTown;?>">
<input type="hidden" name="state" value="<?php echo $StateProvence;?>">
<input type="hidden" name="zip" value="<?php echo $PostalZip;?>">
<input type="hidden" name="country" value="<?php echo $slctCountry;?>">
                  
<input type="hidden" name="email" value="<?php echo $Email;?>">
<input type="hidden" name="night_phone_a" value="<?php echo $Phonea;?>">
<input type="hidden" name="night_phone_b" value="<?php echo $Phoneb;?>">
<input type="hidden" name="night_phone_c" value="<?php echo $Phonec;?>">
<input type="hidden" name="day_phone_a" value="<?php echo $Phonea;?>">
<input type="hidden" name="day_phone_b" value="<?php echo $Phoneb;?>">
<input type="hidden" name="day_phone_c" value="<?php echo $Phonec;?>">

</form>

<script language="javascript">
   document.form1.submit();
</script>

<?php
  }

//////////////////////////////////////////////////////////////////////////////////////////////
}else{

$bError = false;
?>

<form name='frmMakePayment' method='post' action='./verifyorderpaypal.php'>
<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center' class='forumline'>
<tr>
<td align='left' colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_VERIFYORDER_TABLE_HEADER", $config);?></font></b></td>
</tr>
<tr>
<td class='row1'>

<table border="0" width="100%">
<tr>
<td valign="middle" align="left" colspan="4"></td>
</tr>
<tr>
<td valign="middle" align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_1", $config);?>&nbsp;</b></td>
<td valign="middle" align="left"><?php echo $FirstName;?></td>
<td valign="middle" align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_2", $config);?>&nbsp;</b></td>
<td valign="middle" align="left"><?php echo $LastName;?></td>
</tr>
<tr>
<td valign="middle" align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_3", $config);?>&nbsp;</b></td>
<td valign="middle" align="left"><?php echo $Email;?></td>
<td valign="middle" align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_4", $config);?>&nbsp;</b></td>
<td valign="middle" align="left"><?php echo $Phonea;?> - <?php echo $Phoneb;?> - <?php echo $Phonec;?></td>
</tr>
<tr>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_5", $config);?>&nbsp;</b></td>
<td align="left"><?php echo $Address;?></td>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_6", $config);?>&nbsp;</b></td>
<td align="left"><?php echo $StateProvence;?></td>
</tr>
<tr>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_7", $config);?>&nbsp; </b></td>
<td align="left"><?php echo $txtCityTown;?></td>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_8", $config);?>&nbsp; </b></td>
<td align="left"><?php echo $PostalZip;?></td>
</tr>
<tr>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_9", $config);?>&nbsp; </b></td>
<td align="left"><?php echo  GetCountryCode($slctCountry);?></td>
<td align="left"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_10", $config);?>&nbsp; </b></td>
<td align="left">

<?php 
if($slctPaymentTerm == "s"){
  echo GetStringFromStringTable("IDS_SELECT_TERM_SEMIANNUAL", $config);
}elseif($slctPaymentTerm == "y"){
  echo GetStringFromStringTable("IDS_SELECT_TERM_YEARLY", $config);
}else{
  echo GetStringFromStringTable("IDS_SELECT_TERM_MONTHLY", $config);
}
?>

</td>
</tr>
</table>

</td>
</tr>
</table>

<?php
$bNameExists = $oR3DCQuery->UserNameExists($txtName);
$bNameLegal = $oR3DCQuery->IsUserNameLegal($txtName);

if($bNameExists == false && $txtName != "" && $bNameLegal == true || $txtIsRenew == "1" && $txtName != ""){
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row1' width='25%'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_2", $config);?>
</td>
<td class='row2'>
<?php echo $txtName;?>
</td>
</tr>
</table>
<input type="hidden" value="<?php echo $txtName;?>" name="txtName">

<?php
}else{
  $bError = true;
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row2' colspan='2'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_3", $config);?>
</td>
</tr>

<tr>
<td class='row1' width='25%'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_2", $config);?>
</td>
<td class='row2'>
<Input type='text' name='txtName' size='50' class="post">
</td>
</tr>
</table>

<?php
}

if($txtRedemptionCode != ""){

$CodeValid = $oBilling->CheckRedemtionCode($txtRedemptionCode, $txtName);

if($CodeValid == true){
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row1' width='25%'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_4", $config);?>
</td>
<td class='row2'>
<?php echo $txtRedemptionCode;?>
</td>
</tr>

<tr>
<td class='row2' colspan='2'>

<?php 
$CodeValid = $oBilling->DecodeRedemtionCode(base64_decode($txtRedemptionCode), $UserName, $Type, $Date);
$type = "";

switch($Type){

  case "M":
    $slctPaymentTerm = "m";
    $type = GetStringFromStringTable("IDS_VERIFYORDER_TXT_5", $config);
    break;

  case "S":
    $slctPaymentTerm = "s";
    $type = GetStringFromStringTable("IDS_VERIFYORDER_TXT_6", $config);
    break;

  case "Y":
    $slctPaymentTerm = "y";
    $type = GetStringFromStringTable("IDS_VERIFYORDER_TXT_7", $config);
    break;

}

echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_8", $config)." ".$UserName." ".$type.".";
?>

<input type="hidden" value="<?php echo $txtRedemptionCode;?>" name="txtRedemptionCode">
</td>
</tr>
</table>

<?php
}else{
  $bError = true;
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row2' colspan='2'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_9", $config);?>
</td>
</tr>
<tr>
<td class='row1' width='25%'>
<?php echo GetStringFromStringTable("IDS_VERIFYORDER_TXT_4", $config);?>
</td>
<td class='row2'>
<Input type='text' name='txtRedemptionCode' size='50' class="post">
</td>
</tr>
</table>

<?php
}

}
?>

<input type='hidden' name='txtID' value='<?php echo $ID;?>'>
<input type="hidden" value="<?php echo $FirstName;?>" name="txtFirstName">
<input type="hidden" value="<?php echo $LastName;?>" name="txtLastName">
<input type="hidden" value="<?php echo $Email;?>" name="txtEmail">
<input type="hidden" value="<?php echo $Phonea;?>" name="txta">
<input type="hidden" value="<?php echo $Phoneb;?>" name="txtb">
<input type="hidden" value="<?php echo $Phonec;?>" name="txtc">
<input type="hidden" value="<?php echo $Address;?>" name="txtAddress">
<input type="hidden" value="<?php echo $StateProvence;?>" name="txtStateProvence">
<input type="hidden" value="<?php echo $txtCityTown;?>" name="txtCityTown">
<input type="hidden" value="<?php echo $PostalZip;?>" name="txtPostalZip">
<input type="hidden" value="<?php echo $slctCountry;?>" name="slctCountry">
<input type='hidden' name='slctPaymentTerm' value='<?php echo $slctPaymentTerm;?>'>


<?php
if($txtIsRenew == "1"){
?>
<input type="hidden" value="1" name="txtIsRenew">
<?php
}
?>

<table border="0" align='center'>
<tr>
<td>

<?php
if($bError == true){
?>
<input type="submit" name='cmdUpdate' value="<?php echo GetStringFromStringTable("IDS_VERIFYORDER_BTN_UPDATE", $config);?>" class='mainoption'>
<?php
}else{
?>
<input type="submit" name='cmdpayment' value="<?php echo GetStringFromStringTable("IDS_VERIFYORDER_BTN_PAYMENT", $config);?>" class='mainoption'>
<?php
}
?>

</td>
</tr>
</table>
</form>

<?php
}
?>