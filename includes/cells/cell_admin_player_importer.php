<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
$bTEnabled = $oR3DCQuery->IsPlayerImportEnabled();

if($bTEnabled == true){
?>

<form name='frmTProposal' method='post' action='./player_import.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_IMPORTPLAYER_TABLE_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class='row2'>
<textarea name='txtPlayers' rows='10' cols='80' class='post'></textarea>
</td>
</tr>
<tr>
<td class='row1'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_IMPORTPLAYER_BTN_IP", $config);?>' name='cmdImport' class='mainoption'></td>
</tr>

</table>
</form>

<?php
}else{
  echo GetStringFromStringTable("IDS_ADMIN_MANAGE_IMPORTPLAYER_TXT_1", $config);
}

if($addError != ""){
?>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_IMPORTPLAYER_TXT_2", $config);?><br>
<?php echo $addError;?>
</td>
</tr>
</table>

<?php
}
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_2", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_players.php';">
</center>
<br>