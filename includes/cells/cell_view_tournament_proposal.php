<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmTournament' method='get' action='chess_view_tournament_proposal.php'>
<?php $oR3DCQuery->GetTournamentInfo($config, $TID, $_SESSION['id']);?>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>
<br>