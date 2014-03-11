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

<form name='frmCreateGame' method='get' action='./chess_create_game_oc.php'>
<table border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CREAT_GAME_TXT_7", $config);?></font><b></td>
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

<script language='javascript'>

function SetTimeControl(){

  var parent_object = document.frmCreateGame.slctGameTime.options;
  var selection = parent_object[parent_object.selectedIndex].value;

  switch(selection){

    case "RT-Custom":
      document.frmCreateGame.txtmoves1.value = "";
      document.frmCreateGame.txtmins1.value = "";
      break;

    case "RT-Blitz":
      document.frmCreateGame.txtmoves1.value = "50";
      document.frmCreateGame.txtmins1.value = "15";
      break;

    case "RT-Short":
      document.frmCreateGame.txtmoves1.value = "50";
      document.frmCreateGame.txtmins1.value = "60";
      break;

    case "RT-Normal":
      document.frmCreateGame.txtmoves1.value = "50";
      document.frmCreateGame.txtmins1.value = "120";
      break;

    case "RT-Slow":
      document.frmCreateGame.txtmoves1.value = "50";
      document.frmCreateGame.txtmins1.value = "720";
      break;

  }

}
</script>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_1", $config);?></td>
<td class='row2'>
<select name='slctGameRating' onChange='SetTimeControl();'>
<option value='grated'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_7", $config);?></option>
<option value='gunrated'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_8", $config);?></option>
</select>

<select name='slctGameTime' onChange='SetTimeControl();'>
<option value='RT-Custom'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_6", $config);?></option>
<option value='RT-Blitz'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_1", $config);?></option>
<option value='RT-Short'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_2", $config);?></option>
<option value='RT-Normal'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_3", $config);?></option>
<option value='RT-Slow'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_4", $config);?></option>
</select>

</td>
</tr>

<tr>
<td class='row1' colspan='2'><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_3", $config);?></td>
</tr>
<tr>
<td class='row2' colspan='2'>

<?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_4", $config);?> <input type='text' name='txtmoves1' value='' class='post' size='10'> <?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_6", $config);?> <input type='text' name='txtmins1' value='' class='post' size='10'> <?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_7", $config);?><br>
<?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_5", $config);?> <input type='text' name='txtmoves2' value='' class='post' size='10'> <?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_6", $config);?> <input type='text' name='txtmins2' value='' class='post' size='10'> <?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_7", $config);?><br>

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

<?php
$oR3DCQuery->SearchOpenGames($_SESSION['id']);
?>