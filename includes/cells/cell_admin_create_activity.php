<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmCreateActivity' method='post' action='./create_activity_c.php'>
<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_1", $config);?></font><b></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtName' size='50' class='post'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_3", $config);?></td><td class='row2'><input type='text' name='txtDescription' size='50' class='post'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_4", $config);?></td><td class='row2'><input type='text' name='txtCreatedBy' size='50' class='post'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_5", $config);?></td><td class='row2'><input type='text' name='txtCredit' class='post'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_6", $config);?></td>
<td class='row2'>

<select name='optType'>
<option value='pzl'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_7", $config);?></option>
<option value='lsn'><?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_8", $config);?></option>
</select>

</td>
</tr>
<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdCreateActivity' value='<?php echo GetStringFromStringTable("IDS_CREATE_ACTIVITY_TXT_9", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>
<br>

<?php
  $oR3DCQuery->GetAdminActivityListHTML();
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
</center>
<br>