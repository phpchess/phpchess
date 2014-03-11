<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmManagePChat' method='post' action='./cfg_cronjob_config.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_1", $config);?></font><b></td>
</tr>

<tr>
<td colspan='2' class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_2", $config);?></td>
</tr>

<?php
if($PChatSetting == 1){
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdomethod' value='1' CHECKED></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_3", $config);?></td>
  </tr>
<?php
}else{
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdomethod' value='1'></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_3", $config);?></td>
  </tr>
<?php
}
if($PChatSetting == 2){
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdomethod' value='2' CHECKED></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_4", $config);?></td>
  </tr>
<?php
}else{
?>
  <tr>
  <td class='row1' width='5%'><input type='radio' name='rdomethod' value='2'></td><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_CJOB_SETTINGS_TXT_4", $config);?></td>
  </tr>
<?php
}
?>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChange' value='<?php echo GetStringFromStringTable("IDS_ADMINTS_BTN_CHANGE", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>