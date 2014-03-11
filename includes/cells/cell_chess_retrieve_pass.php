<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmRegister' method='post' action='./chess_retrieve_pass.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_TABLE_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class="row1"><?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_TABLE_TXT_1", $config);?></td><td class="row2"><Input type='text' name='txtName' size='50' class="post"></td>
</tr>
<tr>
<td class="row1"><?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_TABLE_TXT_2", $config);?></td><td class="row2"><Input type='text' name='txtEmail' size='50' class="post"></td>
</tr>
<tr>
<td colspan='2' class='tableheadercolor'>
<input type='submit' name='cmdCommand' value='<?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_BTN_SUBMIT", $config);?>' class='mainoption'>
<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_BTN_RESET", $config);?>' class='button'>

</td>
</tr>

<?php
if($bsent){
?>
<tr>
<td colspan='2' class="row2">
<?php echo GetStringFromStringTable("IDS_RETRIEVEPASS_TEXT_1", $config);?>
</td>
</tr>
<?php
}
?>

</table>
</form>
