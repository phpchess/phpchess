<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmBuddlyList' method='post' action='./chess_cfg_buddylist.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='3' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MANAGE_BUDDYLIST_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' align='left'>
<?php
  $oBuddyList->GetPlayerListNotInBuddyListSelectBox($config, $_SESSION['id']);
?>
</td>
<td class='row2' align='center'>

<input type="button" name="right" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_RIGHT", $config);?>" class='mainoption' ONCLICK="moveSelectedOptions(this.form['lstPlayers[]'],this.form['lstBuddy[]'],true,this.form['movepattern1'].value)"><br><br>
<input type="button" name="right" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_ALLRIGHT", $config);?>" class='mainoption' ONCLICK="moveAllOptions(this.form['lstPlayers[]'],this.form['lstBuddy[]'],true,this.form['movepattern1'].value)"><br><br>
<input type="button" name="left" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_LEFT", $config);?>" class='mainoption' ONCLICK="moveSelectedOptions(this.form['lstBuddy[]'],this.form['lstPlayers[]'],true,this['form'].movepattern1.value)"><br><br>
<input type="button" name="left" value="<?php echo GetStringFromStringTable("IDS_LIST_BTN_ALLLEFT", $config);?>" class='mainoption' ONCLICK="moveAllOptions(this.form['lstBuddy[]'],this.form['lstPlayers[]'],true,this.form['movepattern1'].value)">

</td>

<td class='row1' align='right'>
<?php
  $oBuddyList->GetBuddyListSelectBox($config, $_SESSION['id']);
?>
</td>
<tr>
<td colspan='3' class='row1' align='right'><input type='button' value='<?php echo GetStringFromStringTable("IDS_MANAGE_BUDDYLIST_BTN_UPDATE", $config);?>' name='cmdUpdate' class='mainoption' onclick="javascript:submitform();"></td>
</tr>

<?php
  if($updated == true){
?>

<tr>
<td colspan='3' class='row2'><?php echo GetStringFromStringTable("IDS_MANAGE_BUDDYLIST_TEXT_1", $config);?></td>
</tr>

<?php
  }
?>

</table>

<input type="hidden" name="txtadd" value=''>
<input type="hidden" name="pattern1" value=''>
<input type="hidden" name="movepattern1" value="">
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_8", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_buddy_list.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>