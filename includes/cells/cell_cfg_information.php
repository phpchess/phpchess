<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmInfo' method='post' action='./chess_cfg_information.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1">
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtRealName' value='<?php echo $txtRealName;?>' class='post' size='40'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtLocation' value='<?php echo $txtLocation;?>' class='post' size='40'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_3", $config);?></td><td class='row2'><input type='text' name='txtAge' value='<?php echo $txtAge;?>' class='post' size='40'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_4", $config);?></td><td class='row2'><input type='text' name='txtSelfRating' value='<?php echo $txtSelfRating;?>' class='post' size='40'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_5", $config);?></td><td class='row2'><input type='text' name='txtComment' value='<?php echo $txtComment;?>' class='post' size='40'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_6", $config);?></td><td class='row2'><input type='text' name='txtChessPlayer' value='<?php echo $txtChessPlayer;?>' class='post' size='40'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_7", $config);?></td><td class='row2'><input type='text' name='txtEmailAddress' value='<?php echo $txtEmailAddress;?>' class='post' size='40'></td>
</tr>

<tr>
<td class='row1' colspan='2' align='right'><input type='submit' name='cmdSave' value='<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_BTN_SAVE", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>