<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<script language="javascript">
function Validate(theForm)
{

  if (theForm.txtFirstName.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_1", $config);?>");
    theForm.txtFirstName.focus();
    return false;
  }

  if (theForm.txtLastName.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_2", $config);?>");
    theForm.txtLastName.focus();
    return false;
  }

  if (theForm.txtEmail.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_3", $config);?>");
    theForm.txtEmail.focus();
    return false;
  }

  if (theForm.txta.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_4", $config);?>");
    theForm.txta.focus();
    return false;
  }

  var checkOK = "0123456789-";
  var checkStr = theForm.txta.value;
  var allValid = true;
  var decPoints = 0;
  var allNum = "";
  for (i = 0;  i < checkStr.length;  i++)
  {
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j))
        break;
    if (j == checkOK.length)
    {
      allValid = false;
      break;
    }
    allNum += ch;
  }
  if (!allValid)
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_5", $config);?>");
    theForm.txta.focus();
    return false;
  }

  if (theForm.txtb.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_6", $config);?>");
    theForm.txtb.focus();
    return false;
  }

  var checkOK = "0123456789-";
  var checkStr = theForm.txtb.value;
  var allValid = true;
  var decPoints = 0;
  var allNum = "";
  for (i = 0;  i < checkStr.length;  i++)
  {
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j))
        break;
    if (j == checkOK.length)
    {
      allValid = false;
      break;
    }
    allNum += ch;
  }
  if (!allValid)
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_7", $config);?>");
    theForm.txtb.focus();
    return false;
  }

  if (theForm.txtc.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_8", $config);?>");
    theForm.txtc.focus();
    return false;
  }

  var checkOK = "0123456789-";
  var checkStr = theForm.txtc.value;
  var allValid = true;
  var decPoints = 0;
  var allNum = "";
  for (i = 0;  i < checkStr.length;  i++)
  {
    ch = checkStr.charAt(i);
    for (j = 0;  j < checkOK.length;  j++)
      if (ch == checkOK.charAt(j))
        break;
    if (j == checkOK.length)
    {
      allValid = false;
      break;
    }
    allNum += ch;
  }
  if (!allValid)
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_9", $config);?>");
    theForm.txtc.focus();
    return false;
  }

  if (theForm.txtAddress.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_10", $config);?>");
    theForm.txtAddress.focus();
    return false;
  }

  if (theForm.txtCityTown.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_11", $config);?>");
    theForm.txtCityTown.focus();
    return false;
  }

  if (theForm.txtPostalZip.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_12", $config);?>");
    theForm.txtPostalZip.focus();
    return false;
  }

  if(theForm.slctCountry.value == "-"){
    alert("<?php echo GetStringFromStringTable("IDS_RENEWBILL_JAVA_TXT_13", $config);?>");
    return false;
  }

  return true;
}
</script>


<?php
// Get new order information
$UserName = $USERNAME;
$aBillInfo = $oBilling->GetNewBillByUserName($UserName);
$ID = trim($aBillInfo[0]);
$FirstName = trim($aBillInfo[2]);
$LastName = trim($aBillInfo[3]);
$Email = trim($aBillInfo[9]);
$Phonea = trim($aBillInfo[10]);
$Phoneb = trim($aBillInfo[11]);
$Phonec = trim($aBillInfo[12]);
$Address = trim($aBillInfo[4]);
$StateProvence = trim($aBillInfo[7]);
$txtCityTown = trim($aBillInfo[5]);
$PostalZip = trim($aBillInfo[8]);
$slctCountry = trim($aBillInfo[6]);
$slctPaymentTerm = trim($aBillInfo[15]);
$txtName = trim($aBillInfo[1]);
$txtRedemptionCode = trim($aBillInfo[13]);

?>

<form name="frmBilling" method="post" action="./verifyorderpaypal.php" onsubmit="return Validate(this);">
<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center' class='forumline'>
<tr>
<td align='left' colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_HEADER", $config);?></font></b></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_1", $config);?></b></td><td class='row2'><input type="text" name="txtFirstName" class='post' maxlength="40" value='<?php echo $FirstName;?>'></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_2", $config);?></b></td><td class='row2'><input type="text" name="txtLastName" class='post' maxlength="40" value='<?php echo $LastName;?>'></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_3", $config);?></b> </td><td class='row2'><input type="text" name="txtEmail" class='post' maxlength="100" value='<?php echo $Email;?>'></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_4", $config);?></b></td><td class='row2'><input type="text" name="txta" size="3" maxlength="3" class='post' value='<?php echo $Phonea;?>'>-<input type="text" name="txtb" size="3" maxlength="3" class='post' value='<?php echo $Phoneb;?>'>-<input type="text" name="txtc" size="4" maxlength="4" class='post' value='<?php echo $Phonec;?>'></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_5", $config);?></b></td><td class='row2'><input type="text" name="txtAddress" class='post' maxlength="50" value='<?php echo $Address;?>'></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_6", $config);?></b></td><td class='row2'><input type="text" class='post' name="txtStateProvence" maxlength="40" value='<?php echo $StateProvence;?>'></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_7", $config);?></b></td><td class='row2'><input type="text" name="txtCityTown" class='post' maxlength="40" value='<?php echo $txtCityTown;?>'></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_8", $config);?></b></td><td class='row2'><input type="text" name="txtPostalZip" class='post' maxlength="9" value='<?php echo $PostalZip;?>'></td>
</tr>
<tr>
<td class='row1' colspan="1"><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_9", $config);?></b></td><td class='row2' colspan='3'><?php SelectCountries($slctCountry,"slctCountry"); ?></td>
</tr>
<tr>
<td class='row1' colspan="1"><b><?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_10", $config);?></b></td><td class='row2' colspan='3'><?php SelectPaymentTerm($slctPaymentTerm,"slctPaymentTerm"); ?></td>
</tr>
</table>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row1'>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_11", $config);?>
</td>
<td class='row2'>
<?php echo $txtName;?>
</td>
</tr>
</table>

<?php
  $nPrice = $oBilling->GetDefinedPrice();
?>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row2' valign='top'>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_12", $config);?><?php echo number_format($nPrice,2,'.', '')?>
</td>
<td class='row2' valign='top'>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_13", $config);?><?php echo number_format($nPrice,2,'.', '');?><br>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_14", $config);?><?php echo number_format($nPrice*6,2,'.', '');?><br>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_15", $config);?><?php echo number_format($nPrice*12,2,'.', '');?>
</td>
</tr>

<tr>
<td class='row1'>
<?php echo GetStringFromStringTable("IDS_RENEWBILL_TABLE_TXT_16", $config);?>
</td>
<td class='row2'>
<input type="text" name="txtRedemptionCode" class='post' size="55">
</td>
</tr>
</table>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td>

<input type='hidden' name='txtID' value='<?php echo $ID;?>'>
<input type='hidden' name='txtName' value='<?php echo $txtName;?>'>
<input type='hidden' name='txtIsRenew' value='1'>
<center><input type="submit" value="<?php echo GetStringFromStringTable("IDS_RENEWBILL_BTN_1", $config);?>" name="cmd" class='mainoption'></center>
</td>
</tr>
</table>
</form>