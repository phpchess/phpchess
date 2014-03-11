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
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_3", $config);?></font></td>
</tr>
<tr>
<td>

<?php
if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetTournamentProposal($config);
}
?>

</td>
</tr>
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_4", $config);?></font></td>
</tr>
<tr>
<td>

<?php

if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetAcceptedTournamentProposal($config);
}
?>

</td>
</tr>

<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_5", $config);?></font></td>
</tr>
<tr>
<td>

<?php
if(isset($_SESSION['UNAME']) && isset($_SESSION['LOGIN']) && $bTEnabled == true){
  $oR3DCQuery->GetStartedTournamentProposal($config);
}
?>

</td>
</tr>
</table>