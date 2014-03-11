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

<script language='javascript'>
function submitform(){
  document.frmTProposal.txtadd.value="1";
  selectMatchingOptions(document.frmTProposal['lstTplayers[]'], document.frmTProposal['pattern1'].value);
  document.frmTProposal.submit();

}
</script>

<form name='frmTProposal' method='post' action='./player_export.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='3' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EXPORTPLAYER_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td colspan='3' class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EXPORTPLAYER_TABLE_TXT_1", $config);?></td>
</tr>

<tr>
<td class='row1' align='left'>

<?php
  $oR3DCQuery->GetPlayerListSelectBox($config);
?>

</td>
<td class='row2' align='center'>

<input type="button" name="right" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_RIGHT", $config);?>" class='mainoption' ONCLICK="moveSelectedOptions(this.form['lstPlayers[]'],this.form['lstTplayers[]'],false,this.form['movepattern1'].value)"><br><br>
<input type="button" name="right" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_ALLRIGHT", $config);?>" class='mainoption' ONCLICK="moveAllOptions(this.form['lstPlayers[]'],this.form['lstTplayers[]'],true,this.form['movepattern1'].value)"><br><br>
<input type="button" name="left" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_LEFT", $config);?>" class='mainoption' ONCLICK="moveSelectedOptions(this.form['lstTplayers[]'],this.form['lstPlayers[]'],true,this['form'].movepattern1.value)"><br><br>
<input type="button" name="left" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_ALLLEFT", $config);?>" class='mainoption' ONCLICK="moveAllOptions(this.form['lstTplayers[]'],this.form['lstPlayers[]'],true,this.form['movepattern1'].value)">

<input type="hidden" name="txtadd" value=''>
<input type="hidden" name="pattern1" value=''>
<input type="hidden" name="movepattern1" value="">

</td>
<td class='row1' align='right'>

<select NAME='lstTplayers[]' multiple size='15' style='width:170'>
</select>

</td>
</tr>

<tr>
<td colspan='3' class='row1'><input type='button' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EXPORTPLAYER_BTN_EP", $config);?>' name='cmdUpdate' class='mainoption' onclick="javascript:submitform();"></td>
</tr>

</table>
</form>

<br>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td class='row1'>
<textarea rows='10' cols='80' class='post'>
<?php echo base64_encode($Names);?>
</textarea>
</td>
</tr>
</table>

<?php
}else{
?>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_EXPORTPLAYER_TXT_1", $config);?>
<?php
}
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_2", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_players.php';">
</center>
<br>