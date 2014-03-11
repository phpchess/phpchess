<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<?php

 //////////////////////////////////////////////////////////////////
 //  Left Menu Include
 //  Administration Page
 //

?>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_1", $config);?></font></td>
</tr>
<tr>
<td>

<?php
  $AllGames = 0;
  $TGames = 0;

  $oR3DCQuery->GetOngoingGameCount2($config, &$AllGames, &$TGames);

  echo "<br>";
  echo "&nbsp;".GetStringFromStringTable("IDS_MENU_TXT_23", $config)." ".$AllGames;
  echo "<br>";
  echo "&nbsp;".GetStringFromStringTable("IDS_MENU_TXT_24", $config)." ".$TGames;
  echo "<br>";
  echo "<br>";
?>

</td>
</tr>
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_2", $config);?></font></td>
</tr>
<tr>
<td>

<?php $oR3DCQuery->GetTopPlayers($config);?>

</td>
</tr>
</table>