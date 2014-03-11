<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<?php

 //////////////////////////////////////////////////////////////////
 //  Right Menu Include
 //
 //

 $bShowPanels = false;

 if($Contentpage == "cell_game2.php" || $Contentpage == "cell_game3.php"){

   if($isrealtime == "IDS_REAL_TIME"){
     $bShowPanels = false;
   }

 }

?>
<table width="100%" cellpadding="0" cellspacing="0" border="0">

<?php if($bShowPanels){ ?>
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_13", $config);?></font></td>
</tr>
<tr>
<td>

<?php $oR3DCQuery->GetTopPlayers($config);?>

</td>
</tr>

<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_14", $config);?></font></td>
</tr>
<tr>
<td>

<?php
if($_SESSION['id'] != ""){
 $oR3DCQuery->GetTournamentInvites($config, $_SESSION['id']);
}
?>

</td>
</tr>

<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_15", $config);?></font></td>
</tr>
<tr>
<td>

<?php
  //Instantiate the CTipOfTheDay Class
  $oTipOfTheDay = new CTipOfTheDay($config);
  $oTipOfTheDay->GetRandomTip($config);
  unset($oTipOfTheDay);
?>

</td>
</tr>
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_16", $config);?></font></td>
</tr>
<tr>
<td>

<?php $oR3DCQuery->GetNewPlayers($config);?>

</td>
</tr>
<?php
}
?>
</table>