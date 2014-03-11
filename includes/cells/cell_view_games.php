<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<table width="100%" cellspacing="10" cellpadding="4">
  <tr>
    <td >
<form name='frmfindPlayer' method='post' action='./chess_view_games.php'>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='450'>

<tr>

<td colspan='2' class='tableheadercolor'><?php echo GetStringFromStringTable("IDS_VIEW_GAMES_TABLE_HEADER", $config);?></td>

</tr>

<tr>

<td class="row1"><?php echo GetStringFromStringTable("IDS_VIEW_GAMES_TABLE_TXT_1", $config);?></td>

<td class='row2'><Input type='text' name='txtSearch' size='33' value='<?php echo $txtSearch;?>' class="post">



<?php SelectSearchType("slctSearchOpt", $slctSearchOpt, $config);?>



</td>

</tr>

<tr>

<td colspan='2' class='row2' align='right'>

<input type='submit' name='cmdSearch' value='<?php echo GetStringFromStringTable("IDS_VIEW_GAMES_BTN_SEARCH", $config);?>' class='mainoption'>

<input type='submit' name='cmdNPL' value='<?php echo GetStringFromStringTable("IDS_VIEW_GAMES_BTN_NPL", $config);?>' class='mainoption'>



</td>

</tr>

</table>

</form>

&nbsp;</td>
  </tr>
  <tr><Td > 


<?php



if($cmdSearch != "" && $slctSearchOpt != "" && $cmdNPL == ""){

  $oR3DCQuery->SearchGames($_SESSION['id'], $txtSearch, $slctSearchOpt);

}



if($cmdNPL != ""){

  $oR3DCQuery->SearchGames($_SESSION['id'], $txtSearch, 4);

} 

echo "<br>";



$oR3DCQuery->GetActiveGamesList($_SESSION['id']);

?>
</Td></tr></table>