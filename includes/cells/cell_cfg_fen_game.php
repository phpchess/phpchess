<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($cmdAddGame != ""){
?>

<form name='frmAddGame' method='post' action='./cfg_fen_game.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_2", $config);?></font></td></tr>
<tr><td><input type='txtFen'><input type='submit' name='cmdAddGame1' value='<?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_2", $config);?>' class='mainoption'></td></tr>
</table>
</form>

<?php
}
?>

<form name='frmPreCreatedGame' method='post' action='./cfg_fen_game.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_1", $config);?></font></td></tr>

<tr>
<td class='row2' colspan='2'>
<input type='submit' name='cmdAddGame' value='<?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_2", $config);?>' class='mainoption'>
<input type='submit' name='cmdEditGame' value='<?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_3", $config);?>' class='mainoption'>
<input type='submit' name='cmdDeleteGame' value='<?php echo GetStringFromStringTable("IDS_CFG_FEN_GAME_TXT_4", $config);?>' class='mainoption'>
</td>
</tr>

<?php $oR3DCQuery->GetPreCreatedGamesListHTML();?>

</table>
</form>