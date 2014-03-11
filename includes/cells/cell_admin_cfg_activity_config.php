<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmActivityConfig' method='post' action='./cfg_activity_config.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_1", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' width='20%'><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_2", $config);?></td><td class='row2'>
<select name='slctFree'>

<?php
if($Free == 'y'){
?>
<option value='y' selected><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_3", $config);?></option>
<?php
}else{
?>
<option value='y'><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_3", $config);?></option>
<?php
}

if($Free == 'n'){
?>
<option value='n' selected><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_4", $config);?></option>
<?php
}else{
?>
<option value='n'><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_4", $config);?></option>
<?php
}
?>
</select>

</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_5", $config);?></td><td class='row2'><input type='text' name='txtCredit' value='<?php echo $Credit;?>' class='post'></td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdUpdate' value='<?php echo GetStringFromStringTable("IDS_ACTIVITY_CONFIG_TXT_6", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>