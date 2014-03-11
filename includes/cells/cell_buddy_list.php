<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<table width="100%" cellspacing="0" cellpadding="10">
  <tr>
    <td><?php $oBuddyList->GetBuddyList($config, $_SESSION['id']);?>
	</td></tr></table>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_7", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg_buddylist.php';">
</center>
<br>