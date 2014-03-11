<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
$bTEnabled = $oR3DCQuery->IsTournamentEnabled();

if($bTEnabled == true){
?>

<script language='javascript'>
function ExecuteCommand(cmd){

  if(cmd == "A"){
    document.frmTournament.txtcommand.value = 0;
  }

  if(cmd == "R"){
    document.frmTournament.txtcommand.value = 1;
  }

  if(cmd == "S"){
    document.frmTournament.txtcommand.value = 2;
  }

  document.frmTournament.submit();

}
</script>

<?php
if($bInvalidDate){
  echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1'>";
  echo "<tr>";
  echo "<td colspan='3' class='row2'>".GetStringFromStringTable("IDS_ADMIN_VIEWTOURNAMENTPROPOSAL_TXT_1", $config)."</td>";
  echo "</tr>";
  echo "</table>";

  echo "<br>";
}
?>

<form name='frmTournament' method='get' action='./admin_view_proposal.php'>
<?php

  $oR3DCQuery->viewTournamentProposal($config, $TID);


  echo "<input type='hidden' name='tid' value='".$TID."'>";

?>
</form>

<?php
}else{
?>
<?php echo GetStringFromStringTable("IDS_ADMIN_VIEWTOURNAMENTPROPOSAL_TXT_2", $config);?>
<?php
}
?>