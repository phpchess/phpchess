<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table align='center' width="300" border="0" cellspacing="0" cellpadding="0" class='forumline2'>
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_TOP_TEN_TXT_1", $config);?></font></td>
</tr>

<tr>
<td class='row2' align='center'><?php $oR3DCQuery->GetTopPlayers($ConfigFile, true);?></td>
</tr>
</table>


<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>
<br>