<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php 
  // Check if the game was created
  if($bGameCreated == false){ 
?>

<form name='frmCreateGame' method='get' action='./chess_create_game_nm.php'>
<table border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_5", $config);?></font><b></td>
</tr>

<?php
if($othpid != $_SESSION['id']){

  $challenged = $oR3DCQuery->GetUserIDByPlayerID($config, $othpid);
?>

<tr>
<td class='row2' colspan='2'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_1_1", $config);?> <?php echo $_SESSION['user'];?> <?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_1_2", $config);?> <?php echo $challenged;?></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_2", $config);?></td>
<td class='row2'>

<select name='my_color'>
<option value='w'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_1", $config);?></option>
<option value='b'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_2", $config);?></option>
</select>

</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_3", $config);?></td>
<td class='row2'>
<input type='text' name='fen' value='' class='post' size='40'>
<input type='button' name='cmdCreatFEN' value='<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CF", $config);?>' class='mainoption' onClick='javascript:PopupWindowRT("./pgnviewer/board2fen.html");'>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_1", $config);?></td>
<td class='row2'>
<select name='slctGameRating' onChange='SetTimeControl();'>
<option value='grated'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_7", $config);?></option>
<option value='gunrated'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_8", $config);?></option>
</select>

<select name='slctGameTime'>
<option value='C-Normal'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_3", $config);?></option>
<option value='C-Blitz'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_1", $config);?></option>
<option value='C-Short'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_2", $config);?></option>
<option value='C-Slow'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_4", $config);?></option>
<option value='C-Snail'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_5", $config);?></option>
</select>

</td>
</tr>

<tr>
<td class='row1' colspan='2' align='right'>

<input type='hidden' name='otherplayerid' value='<?php echo $othpid;?>'>
<input type='hidden' name='othpid' value='<?php echo $othpid;?>'>

<input type='submit' name='cmdCreateGame' value='<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config);?>' class='mainoption'>
</td>
</tr>
<?php
}else{
?>

<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_4", $config);?></td>
</tr>

<?php
}
?>
</table>
</form>

<?php }else{ ?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_5", $config);?></td>
</tr>
</table>

<SCRIPT language="JavaScript">
<!--
window.location="./chess_game.php?gameid=<?php echo substr($txtgid, 0, 32);?>";
//-->
</SCRIPT>

<?php } ?>