<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<script language='Javascript'>
function MemOpts(functionid, pid){

  document.frmClubMems.txtfunc.value = functionid;
  document.frmClubMems.txtpid.value = pid;

  document.frmClubMems.submit();
}
</script>

<?php
echo "<form name='frmClubMems' method='post' action='./chess_cfg_club_members.php'>";
echo "<input type='hidden' name='txtfunc'><input type='hidden' name='txtpid'>";
$oR3DCQuery->GetClubMemberlistAdmin($_SESSION['id']);
echo "</form>";
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_10", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg_clubs.php';">
</center>
<br>