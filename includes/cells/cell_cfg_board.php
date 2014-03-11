<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
/**********************************************************************
* SelectBoard
*
*/
function SelectBoard($name, $selected, $config){

  echo "<select name='".$name."'>";

  if($selected == "3"){
    echo "<option value='3' selected>".GetStringFromStringTable("IDS_CFGBOARD_TXT_1", $config)."</option>";
  }else{
    echo "<option value='3'>".GetStringFromStringTable("IDS_CFGBOARD_TXT_1", $config)."</option>";
  }

  if($selected == "2" || $selected == ""){
    echo "<option value='2' selected>".GetStringFromStringTable("IDS_CFGBOARD_TXT_2", $config)."</option>";
  }else{
    echo "<option value='2'>".GetStringFromStringTable("IDS_CFGBOARD_TXT_2", $config)."</option>";
  }

  if($selected == "1" || $selected == ""){
    echo "<option value='1' selected>".GetStringFromStringTable("IDS_CFGBOARD_TXT_3", $config)."</option>";
  }else{
    echo "<option value='1'>".GetStringFromStringTable("IDS_CFGBOARD_TXT_3", $config)."</option>";
  }

  echo "</select>";

}


/**********************************************************************
* SelectBoardType
*
*/
function SelectBoardType($name, $selected, $config){

  echo "<select name='".$name."'>";

  if($selected == "1"){
    echo "<option value='1' selected>".GetStringFromStringTable("IDS_CFGBOARD_TXT_8", $config)."</option>";
  }else{
    echo "<option value='1'>".GetStringFromStringTable("IDS_CFGBOARD_TXT_8", $config)."</option>";
  }

  if($selected == "0" || $selected == ""){
    echo "<option value='0' selected>".GetStringFromStringTable("IDS_CFGBOARD_TXT_9", $config)."</option>";
  }else{
    echo "<option value='0'>".GetStringFromStringTable("IDS_CFGBOARD_TXT_9", $config)."</option>";
  }

  echo "</select>";

}
?>

<form name='frmBoardSetup' method='post' action='chess_cfg_board.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1">
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CFGBOARD_TXT_4", $config);?></font><b></td>
</tr>

<tr>
<td class='row1'>
<?php echo GetStringFromStringTable("IDS_CFGBOARD_TXT_5", $config);?>
</td>
<td class='row2'>
<?php SelectBoard("slcBoardLayout", $selected, $config);?>
</td>
</tr>

<tr>
<td class='row1'>
<?php echo GetStringFromStringTable("IDS_CFGBOARD_TXT_7", $config);?>
</td>
<td class='row2'>
<?php SelectBoardType("slcBoardType", $selectedbt, $config);?>
</td>
</tr>

<tr>
<td class='row2' colspan='2'>
<input type='submit' name='cmdSetBoard' value='<?php echo GetStringFromStringTable("IDS_CFGBOARD_TXT_6", $config);?>' class='mainoption'>
</td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>