<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php $oR3DCQuery->ViewOtherPlayerGameListHTML($PlayerID, $ListingType, $Root_Path);?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = '../admin/admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_12", $config);?>' class='mainoption' onclick="javascript:window.location = '../admin/admin_player_list.php';">
</center>
<br>