<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php $oR3DCQuery->ViewOtherPlayerGameListHTML($PlayerID, $ListingType);?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_12", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_statistics.php?playerid=<?php echo $PlayerID;?>';">
</center>
<br>