<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
/**********************************************************************
* SelectYesNo
*
*/
function SelectYesNo($name, $selected, $config){

  echo "<select name='".$name."'>";

  if($selected == "y"){
    echo "<option value='y' selected>".GetStringFromStringTable("IDS_SELECT_YES", $config)."</option>";
  }else{
    echo "<option value='y'>".GetStringFromStringTable("IDS_SELECT_YES", $config)."</option>";
  }

  if($selected == "n" || $selected == ""){
    echo "<option value='n' selected>".GetStringFromStringTable("IDS_SELECT_NO", $config)."</option>";
  }else{
    echo "<option value='n'>".GetStringFromStringTable("IDS_SELECT_NO", $config)."</option>";
  }

  echo "</select>";

}
?>

<form name='frmNotification' method='post' action='./chess_cfg_notification.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1">
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MANAGE_NOTIFICATION_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_NOTIFICATION_TABLE_TXT_1", $config);?></td>
<td class='row2'>
<?php SelectYesNo("slctmove", $slctmove, $config);?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_NOTIFICATION_TABLE_TXT_2", $config);?></td>
<td class='row2'>
<?php SelectYesNo("slctchallange", $slctchallange, $config);?>
</td>
</tr>
<tr>
<td class='row1' colspan='2' align='right'><input type='submit' name='cmdSave' value='<?php echo GetStringFromStringTable("IDS_MANAGE_NOTIFICATION_BTN_SAVE", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>