<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
$bTEnabled = $oR3DCQuery->IsTournamentEnabled();

if($bTEnabled == true){

if($txtadd != ""){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='3' class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TXT_1", $config);?></td>
</tr>
</table>

<?php
}
?>

<form name='frmTProposal' method='post' action='./chess_tournament_proposal.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='3' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td colspan='1' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_1", $config);?></td><td class='row2' colspan='2'><input type='text' name='txttname' class="post" size='35'></td>
</tr>

<tr>
<td colspan='1' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_2", $config);?></td>
<td class='row2' colspan='2'>
<select name='slcttType'>
<option value='0'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_3", $config);?></option>
<option value='1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_4", $config);?></option>
<option value='2'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_5", $config);?></option>
<option value='3'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_6", $config);?></option>
</select>
</td>
</tr>

<tr>
<td colspan='1' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_7", $config);?></td><td class='row2' colspan='2'><input type='text' name='txtplayernum' class="post" size='35' value='0' onblur="ValidateNumField(this.form, this.form.txtplayernum);"></td>
</tr>

<tr>
<td colspan='1' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_8", $config);?></td><td class='row2' colspan='2'>

<select size="1" name="slctcpDateMonth">
<option value='1'><?php echo $m1;?></option>
<option value='2'><?php echo $m2;?></option>
<option value='3'><?php echo $m3;?></option>
<option value='4'><?php echo $m4;?></option>
<option value='5'><?php echo $m5;?></option>
<option value='6'><?php echo $m6;?></option>
<option value='7'><?php echo $m7;?></option>
<option value='8'><?php echo $m8;?></option>
<option value='9'><?php echo $m9;?></option>
<option value='10'><?php echo $m10;?></option>
<option value='11'><?php echo $m11;?></option>
<option value='12'><?php echo $m12;?></option>
</select>

<select size="1" name="slctcpDateDay">
<option value='01'>01</option>
<option value='02'>02</option>
<option value='03'>03</option>
<option value='04'>04</option>
<option value='05'>05</option>
<option value='06'>06</option>
<option value='07'>07</option>
<option value='08'>08</option>
<option value='09'>09</option>
<option value='10'>10</option>
<option value='11'>11</option>
<option value='12'>12</option>
<option value='13'>13</option>
<option value='14'>14</option>
<option value='15'>15</option>
<option value='16'>16</option>
<option value='17'>17</option>
<option value='18'>18</option>
<option value='19'>19</option>
<option value='20'>20</option>
<option value='21'>21</option>
<option value='22'>22</option>
<option value='23'>23</option>
<option value='24'>24</option>
<option value='25'>25</option>
<option value='26'>26</option>
<option value='27'>27</option>
<option value='28'>28</option>
<option value='29'>29</option>
<option value='30'>30</option>
<option value='31'>31</option>
</select>

<select size="1" name="slctcpDateYear">
<?php selectyear();?>
</select>

<br>
<input type='text' name='txtcptime' class="post" value='<?php echo $todaystime;?>'>

 </td>
</tr>

<tr>
<td colspan='1' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_9", $config);?></td><td class='row2' colspan='2'>

<select size="1" name="slctsDateMonth">
<option value='1'><?php echo $m1;?></option>
<option value='2'><?php echo $m2;?></option>
<option value='3'><?php echo $m3;?></option>
<option value='4'><?php echo $m4;?></option>
<option value='5'><?php echo $m5;?></option>
<option value='6'><?php echo $m6;?></option>
<option value='7'><?php echo $m7;?></option>
<option value='8'><?php echo $m8;?></option>
<option value='9'><?php echo $m9;?></option>
<option value='10'><?php echo $m10;?></option>
<option value='11'><?php echo $m11;?></option>
<option value='12'><?php echo $m12;?></option>
</select>

<select size="1" name="slctsDateDay">
<option value='01'>01</option>
<option value='02'>02</option>
<option value='03'>03</option>
<option value='04'>04</option>
<option value='05'>05</option>
<option value='06'>06</option>
<option value='07'>07</option>
<option value='08'>08</option>
<option value='09'>09</option>
<option value='10'>10</option>
<option value='11'>11</option>
<option value='12'>12</option>
<option value='13'>13</option>
<option value='14'>14</option>
<option value='15'>15</option>
<option value='16'>16</option>
<option value='17'>17</option>
<option value='18'>18</option>
<option value='19'>19</option>
<option value='20'>20</option>
<option value='21'>21</option>
<option value='22'>22</option>
<option value='23'>23</option>
<option value='24'>24</option>
<option value='25'>25</option>
<option value='26'>26</option>
<option value='27'>27</option>
<option value='28'>28</option>
<option value='29'>29</option>
<option value='30'>30</option>
<option value='31'>31</option>
</select>

<select size="1" name="slctsDateYear">
<?php selectyear();?>
</select>

<br>
<input type='text' name='txtstime' class="post" value='<?php echo $todaystime;?>'>

</td>
</tr>

<tr>
<td colspan='3' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_10", $config);?></td>
</tr>

<tr>
<td colspan='3' class='row2'><textarea name='txtcomments' cols='65' rows='10' class="post"></textarea></td>
</tr>

<tr>
<td colspan='3' class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_TXT_11", $config);?></td>
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
<td colspan='3' class='row1'>

<input type="button" name="right" value="<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_BTN_VPG", $config);?>" class='mainoption' ONCLICK="GenerateGroupOutlook(this.form['lstTplayers[]'], this.form['txtplayernum'], this.form['slcttType'])">

</td>
</tr>

<tr>
<td colspan='3' class='row1' align='right'><input type='button' value='<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TABLE_BTN_CP", $config);?>' name='cmdCreate' class='mainoption' onclick="javascript:submitform();"></td>
</tr>
</table>
</form>

<?php
}else{
?>
<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENTPROPOSAL_TXT_2", $config);?>
<?php
}
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>
<br>
