<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

if(!$register_success)
{

?>

<?php
if($bLimit == false){

  if($RequiresPayment == true){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_GREETING", $config);?>
</td>
<td class="row2">
<img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/paypal1.gif'>
</td>
</tr>
</table>
<br>

<?php
  }
?>

<?php
if($RequiresPayment == true){
?>
<form name='frmRegister' method='post' action='./paybypaypal.php'>
<?php
}else{
?>
<form name='frmRegister' method='post' action='./chess_register.php'>
<?php
}
?>

<h3 class="title_h3">&nbsp;&nbsp;<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_HEADER", $config);?></h3>
<table border='0' align='center' cellpadding="0" cellspacing="8"  class="white" >
<tr>
<td colspan='3' align='right'><a href="javascript:PopupHelpWin('./includes/help/help.php?tag=register');"><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_1", $config);?> </a> <img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/help.gif' width='15' height='15'></td>
</tr>
<tr>
<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_1", $config);?></td>
<td  colspan='2'><Input type='text' name='txtName' size='35' class="input_text" ></td>
</tr>
<tr>
<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_2", $config);?></td><td colspan='2'><Input type='text' name='txtEmail' size='35' class="input_text" ></td>
</tr>
<tr>
<td ><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_TABLE_TXT_3", $config);?></td><td > <img src="./bin/img_validate.php" width="150" height="30" alt="Visual CAPTCHA" /></td><td width="285" ><Input type='text' name='txtVI' size='20' class="input_text" ></td>
</tr>
<tr>
<td colspan='3' class='tableheadercolor'>
<input type='submit' name='cmdRegister' value='<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_BTN_REGISTER", $config);?>' class="input_btn">
<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_BTN_RESET", $config);?>' class="input_btn">
</td>
</tr>


<?php
if($ReturnStatus != ""){
?>

<tr>
<td colspan='3' class="row2">
<?php
echo $ReturnStatus;
?>
</td>
</tr>

<?php
}
?>

</table>
</form>

<?php
if($RequiresPayment == true){
?>
<br>
<center><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_MESSAGE_TXT_1", $config);?></center>
<?php
}

}else{
?>

<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr><td class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_REGISTER_MESSAGE_TXT_2", $config);?></td></tr>
</table>

<?php
}
?>


<?php

}
else
{
	echo '<br/>';

	echo $ReturnStatus;
	
	echo "<br/><br/><a style='font-size: 1em' href='./'>$tologinpage</a>";

}
?>
