<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($UserName != ""){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWBILLS_TABLE_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class='row2'><?php echo str_replace("['other_user']", $UserName, GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWBILLS_TXT_1", $config));?>
<br></td>
</tr>
</table>

<?php
}

$oBilling->GetOrders('u');
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_5", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_billing_data.php';">
</center>
<br>