<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

$bShowPanels = true;

if($Contentpage == "cell_game2.php" || $Contentpage == "cell_game3.php"){

  if($isrealtime == "IDS_REAL_TIME"){
    $bShowPanels = false;
  }

}

 //////////////////////////////////////////////////////////////////
 //  Right Menu Include
 //
 //

?>
<table width="207" border="0" cellspacing="0" cellpadding="0" align="center">
<?php
if($bShowPanels){
?>
 <tr>
 <td class="white">
<h3 class="menu_title">
<?php echo GetStringFromStringTable("IDS_MENU_TXT_13", $config);?></h3>
<?php $oR3DCQuery->GetTopPlayers($config);?>
</td>
</tr>
<!--<tr>
<td class="white">
<h3 class="menu_title"><?php // echo GetStringFromStringTable("IDS_MENU_TXT_14", $config);?></h3>
<?php
if($_SESSION['id'] != ""){
//  $oR3DCQuery->GetTournamentInvites($config, $_SESSION['id']);

}
?>
</td>
</tr> -->
<?php
if($_SESSION['id'] == ""){?>
<tr>
<td class="white">
<h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_15", $config);?></h3>
<?php
  //Instantiate the CTipOfTheDay Class
  $oTipOfTheDay = new CTipOfTheDay($config);
  $oTipOfTheDay->GetRandomTip($config);
  unset($oTipOfTheDay);
?>
</td>
</tr>

<tr>
<td class="white">
<h3 class="menu_title"><?php echo GetStringFromStringTable("IDS_MENU_TXT_16", $config);?></h3>
<?php $oR3DCQuery->GetNewPlayers($config);?>
</td>
</tr>
<?php }?>
<?php
}
?>
</table>