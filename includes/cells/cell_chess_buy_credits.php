<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if(!$oR3DCQuery->IsCreditsSystemEnabled()){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<?php echo GetStringFromStringTable("IDS_BUY_CREDITS_TXT_1", $config);?>
</td>
</tr>
</table>

<?php
}else{

if($paymentHTML){

  $nPrice = $oBilling->GetDefinedPrice();
  $oBilling->GetPaypalInfo($PrimaryPaypalEmail, $Currency);

?>

<form action='https://www.paypal.com/cgi-bin/webscr' method='post' id=form1 name=form1>
<input type='hidden' name='cmd' value='_xclick'>
<input type='hidden' name='business' value='<?php echo $PrimaryPaypalEmail;?>'>
<input type='hidden' name='item_name' value='Credit Purchase - Player ID:<?php echo $PID;?> CreditRID:<?php echo $CreditRID;?>'>
<input type='hidden' name='item_number' value='<?php echo $CreditRID;?>'>
<input type='hidden' name='amount' value='<?php echo number_format($TotalAmount,2,'.', '');?>'>
<input type='hidden' name='no_shipping' value='1'>
<input type='hidden' name='return' value='./payment_complete.php'>
<input type='hidden' name='rm' value='2'>
<input type='hidden' name='cancel_return' value='./payment_canceled.php'>
<input type='hidden' name='no_note' value='1'>
<input type='hidden' name='currency_code' value='<?php echo $Currency;?>'>
<input type='image' src='https://www.paypal.com/images/x-click-but02.gif' border='0' name='submit' alt='Make payments with PayPal - its fast, free and secure!'>

</form>

<script language="javascript">
   document.form1.submit();
</script>

<?php
}else{

  echo "<form name='frmBuyCredits' method='post' action='./chess_buy_credits.php'>";
  $oR3DCQuery->GetPurchaseCreditHTMLForm($_SESSION['id'], $Root_Path);
  echo "</form>";

}

}

?>