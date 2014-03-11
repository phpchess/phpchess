<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

 //////////////////////////////////////////////////////////////////
 //  Right Menu Include
 //  Administration Page
 //

 $bTEnabled = $oR3DCQuery->IsTournamentEnabled();

?>
<table width="207" border="0" cellspacing="0" cellpadding="0" align="center">
     <tr>
      <td align="left" class="white"><h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_3", $config);?></h3><?php
if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetTournamentProposal($config);
}
?>
</td>
</tr>
 <tr>
      <td align="left" class="white"><h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_4", $config);?></h3><?php
if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetAcceptedTournamentProposal($config);
}
?></td>
</tr>
<tr>
      <td align="left" class="white"><h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_5", $config);?></h3>
	  <?php
if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetStartedTournamentProposal($config);
}
?></td>
</tr>

</table>


