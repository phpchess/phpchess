<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($bLimit){
?>

<br>
<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr><td class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TXT_1", $config);?></td></tr>
</table>

<?php
}
?>

<br>

<?php
  $UserLimit = $oR3DCQuery->UserLimit();
  $CurUserLimit = $oR3DCQuery->CurUserLimit();
?>

<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr><td class='tableheadercolor' colspan='4'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_HEADER_1", $config);?></font></td></tr>
<tr><td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_TXT_1", $config);?></td><td class='row2'><?php echo $CurUserLimit;?></td><td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_TXT_2", $config);?></td><td class='row2'><?php echo $UserLimit;?></td></tr>
</table>

<br>
<form name='frmUserList' method='post' action='admin_player_list.php'>
<table width='400' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_HEADER_2", $config);?></font></td></tr>
<tr><td class='row2'>
<input type='submit' name='cmdDisable' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_SELECT_D", $config);?>' class='mainoption'>
<input type='submit' name='cmdEnable' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_PLAYERLIST_TABLE_SELECT_E", $config);?>' class='mainoption'>
</td></tr>
</table>

<input type='hidden' name='action' value='<?php echo $action;?>'>
<input type='hidden' name='index' value='<?php echo $index;?>'>

<?php
  $oR3DCQuery->ListAvailablePlayers2($config, $SkinName, "admin_player_list.php", $action, $index);
?>

</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_2", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_players.php';">
</center>
<br>