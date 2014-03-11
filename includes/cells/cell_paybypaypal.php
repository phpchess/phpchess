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
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_1", $config);?>");
    theForm.txtFirstName.focus();
    return false;
  }

  if (theForm.txtLastName.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_2", $config);?>");
    theForm.txtLastName.focus();
    return false;
  }

  if (theForm.txtEmail.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_3", $config);?>");
    theForm.txtEmail.focus();
    return false;
  }

  if (theForm.txta.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_4", $config);?>");
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
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_5", $config);?>");
    theForm.txta.focus();
    return false;
  }

  if (theForm.txtb.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_6", $config);?>");
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
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_7", $config);?>");
    theForm.txtb.focus();
    return false;
  }

  if (theForm.txtc.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_8", $config);?>");
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
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_9", $config);?>");
    theForm.txtc.focus();
    return false;
  }

  if (theForm.txtAddress.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_10", $config);?>");
    theForm.txtAddress.focus();
    return false;
  }

  if (theForm.txtCityTown.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_11", $config);?>");
    theForm.txtCityTown.focus();
    return false;
  }

  if (theForm.txtPostalZip.value == "")
  {
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_12", $config);?>");
    theForm.txtPostalZip.focus();
    return false;
  }

  if(theForm.slctCountry.value == "-"){
    alert("<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_JAVA_TXT_13", $config);?>");
    return false;
  }

  return true;
}


</script>

<form name="frmBilling" method="post" action="./verifyorderpaypal.php" onsubmit="return Validate(this);">
<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center' class='forumline'>
<tr>
<td align='left' colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_HEADER", $config);?></font></b></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_1", $config);?></b></td><td class='row2'><input type="text" name="txtFirstName" class='post' maxlength="40" value='<?php echo $FirstName;?>'></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_2", $config);?></b></td><td class='row2'><input type="text" name="txtLastName" class='post' maxlength="40" value='<?php echo $LastName;?>'></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_3", $config);?></b> </td><td class='row2'><input type="text" name="txtEmail" class='post' maxlength="100"></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_4", $config);?></b></td><td class='row2'><input type="text" name="txta" size="3" maxlength="3" class='post'>-<input type="text" name="txtb" size="3" maxlength="3" class='post'>-<input type="text" name="txtc" size="4" maxlength="4" class='post'></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_5", $config);?></b></td><td class='row2'><input type="text" name="txtAddress" class='post' maxlength="50"></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_6", $config);?></b></td><td class='row2'><input type="text" class='post' name="txtStateProvence" maxlength="40"></td>
</tr>
<tr>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_7", $config);?></b></td><td class='row2'><input type="text" name="txtCityTown" class='post' maxlength="40"></td>
<td class='row1'><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_8", $config);?></b></td><td class='row2'><input type="text" name="txtPostalZip" class='post' maxlength="9"></td>
</tr>
<tr>
<td class='row1' colspan="1"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_9", $config);?></b></td><td class='row2' colspan='3'><?php SelectCountries("","slctCountry"); ?></td>
</tr>
<tr>
<td class='row1' colspan="1"><b><?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_BILLING_TABLE_TXT_10", $config);?></b></td>
<td class='row2' colspan='3'>
<select name='slctPaymentTerm'>
<option value='m'><?php echo GetStringFromStringTable("IDS_SELECT_TERM_MONTHLY", $config);?></option>
<option value='s'><?php echo GetStringFromStringTable("IDS_SELECT_TERM_SEMIANNUAL", $config);?></option>
<option value='y'><?php echo GetStringFromStringTable("IDS_SELECT_TERM_YEARLY", $config);?></option>
</select>
</td>
</tr>
</table>


<?php
  $nPrice = $oBilling->GetDefinedPrice();
?>
<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td class='row2' valign='top'>
<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_TXT_1", $config);?><?php echo number_format($nPrice,2,'.', '')?>
</td>
<td class='row2' valign='top'>
<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_TXT_2", $config);?><?php echo number_format($nPrice,2,'.', '');?><br>
<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_TXT_3", $config);?><?php echo number_format($nPrice*6,2,'.', '');?><br>
<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_TXT_4", $config);?><?php echo number_format($nPrice*12,2,'.', '');?>
</td>
</tr>

<tr>
<td class='row1'>
<?php echo GetStringFromStringTable("IDS_PAYBYPAYPAL_TXT_5", $config);?>
</td>
<td class='row2'>
<input type="text" name="txtRedemptionCode" class='post' size="55">
</td>
</tr>
</table>

<table border='0' cellspacing='1' cellpadding='3' width='95%' align='center'>
<tr>
<td>
<input type='hidden' name='txtName' value='<?php echo $txtName;?>'>
<input type='hidden' name='txtEmail' value='<?php echo $txtEmail;?>'>
<center><input type="submit" value="<?php echo GetStringFromStringTable("IDS_REGISTER_BTN_NEXT", $config);?>" name="cmd" class='mainoption'></center>
</td>
</tr>
</table>
</form>