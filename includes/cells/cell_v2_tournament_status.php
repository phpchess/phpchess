<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php 

// Get the users timezone
if(!is_numeric($tzn)){
?>

<form name='frmTimeZone' method='get' action='./chess_v2_tournament_status.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='538'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_95", $config);?></font><b></td>
</tr>
<tr>
<td class='row1' colspan='2'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_94", $config);?></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_35", $config);?></td>
<td class='row2'><?php selectTimezone("tzn", $slctTimeZone, $config);?></td>
</tr>
<tr>
<td class='row1' colspan='2'><input type='submit' class='mainoption' name='cmdContinue' value='<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_106", $config);?>'></td>
</tr>
<input type='hidden' name='tid' value='<?php echo $TID;?>'>
<input type='hidden' name='type' value='<?php echo $TYPE;?>'>
</table>
</form>

<?
}else{

  // Get the current time
  $nCurrentTimestamp = mktime();

  //echo "Current Date: [".date("n.j.Y", mktime() + (3600*$tzn))."]<br><br>";

  // Get tournament information
  if($TYPE == 1){
    $oR3DCQuery->v2GetTournamentInformation_OneToMany($TID, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);
  }

  // Display the tournament calendar
  $oR3DCQuery->v2ViewTournamentGameStatusCalendar($ConfigFile, $TID, $month, $day, $year, $tzn, $TYPE, $index);

  //Display Tournament information

  /////////////////////////////////////////
  // One to Many
  if($TYPE == 1){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='538'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_71", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_57", $config);?></b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_2", $config);?></td>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_4", $config);?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_58", $config);?></td>
<td class='row2'><?php echo $strname;?></td>
</tr>

<tr>
<td class='row1' valign='top'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_59", $config);?></td>
<td class='row2'><?php echo $strdescription;?></td>
</tr>

<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_0", $config);?></b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_40", $config);?></td>
<td class='row2'>
<?php echo date("Y-m-d, H:i:s", $nplayercutoffdate + (3600*$tzn));?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_9", $config);?></td>
<td class='row2'>
<?php echo date("Y-m-d, H:i:s", $ntournamentstartdate + (3600*$tzn));?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_10", $config);?></td>
<td class='row2'>
<?php echo date("Y-m-d, H:i:s", $ntournamentenddate + (3600*$tzn));?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_122", $config);?></td>
<td class='row2'><?php echo date("r");?></td>
</tr>

<!--
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_35", $config);?></td>
<td class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config);?> <?php echo $tzn;?></td>
</tr>
-->

<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_51", $config);?></b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_52", $config);?></td>
<td class='row2'>
<?php echo $strgametimeout;?>
</td>
</tr>

<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_11", $config);?></b></td>
</tr>

<tr>
<td class='row1' colspan='2'><u><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_12", $config);?></b></u></td>
</tr>

<tr>
<td class='row2' colspan='2'>
<?php

  $ncount = count($aTOrganizers);

  $i=0;
  while($i < $ncount){

    echo " * ".$oR3DCQuery->GetUserIDByPlayerID($config, $aTOrganizers[$i][3])."<br>";
    $i++;
  }

?>
</td>
</tr>

<tr>
<td class='row1' colspan='2'><u><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_15", $config);?></b></u></td>
</tr>

<tr>
<td class='row2' colspan='2'>
<?php

  $ncount = count($aTPlayers);

  $i=0;
  while($i < $ncount){

    if($aTPlayers[$i][6] == "*"){
      echo " * ".$oR3DCQuery->GetUserIDByPlayerID($config, $aTPlayers[$i][3])."<br>";
    }

    $i++;
  }

?>
</td>
</tr>

<tr>
<td class='row1' colspan='2'><u><b><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_14", $config);?></b></u></td>
</tr>

<tr>
<td class='row2' colspan='2'>
<?php

  // Display the tournament signup and information form for online players.
  $bDisplayPlayerList = false;
  if($nCurrentTimestamp < $nplayercutoffdate ){
    
    // Check if the player is logged on
    if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){

      // closed signup
      if($nplayersignuptype == 1){

        $ncount = count($aTPlayers);
 
        $i=0;
        while($i < $ncount){

          if($aTPlayers[$i][6] != "*"){

            echo " * ".$oR3DCQuery->GetUserIDByPlayerID($config, $aTPlayers[$i][3]);

            switch($aTPlayers[$i][5]){

              case "p":

                if($_SESSION['id'] == $aTPlayers[$i][3]){
                  echo " <input type='button' class='mainoption' name='btn".$aTPlayers[$i][3]."' value='".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_77", $config)."' onclick=\"window.location='./chess_v2_tournament_status.php?tzn=".$tzn."&tid=".$TID."&type=".$TYPE."&join=x1'\">";
                }else{
                  echo " <b>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_72", $config)."</b> ";
                  echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_73", $config)."</i>";
                }

                break;

              case "a":
                echo " <b>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_72", $config)."</b> ";
                echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_74", $config)."</i>";
                break;

            }

            echo "<br>";

          }

          $i++;
        }

      // Open signup
      }elseif($nplayersignuptype == 2){

        $bplayerExists = false;
        $ncount = count($aTPlayers);

        $i=0;
        while($i < $ncount){

          if($aTPlayers[$i][6] != "*"){

            if($_SESSION['id'] == $aTPlayers[$i][3]){
              $bplayerExists = true;
            }

            echo " * ".$oR3DCQuery->GetUserIDByPlayerID($config, $aTPlayers[$i][3]);
            echo " <b>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_72", $config)."</b> ";

            switch($aTPlayers[$i][5]){

              case "p":
                echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_73", $config)."</i>";
                break;

              case "a":
               echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_74", $config)."</i>";
               break;

            }

            echo "<br>";

          }

          $i++;
        }

        if(!$bplayerExists){
          echo " <input type='button' class='mainoption' name='btnjoin' value='".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_77", $config)."' onclick=\"window.location='./chess_v2_tournament_status.php?tzn=".$tzn."&tid=".$TID."&type=".$TYPE."&join=x2'\">";
        }

      }

    }else{
      $bDisplayPlayerList = true;
    }

  }else{
    $bDisplayPlayerList = true;
  }

  if($bDisplayPlayerList){

    $ncount = count($aTPlayers);

    $i=0;
    while($i < $ncount){

      if($aTPlayers[$i][6] != "*"){

        echo " * ".$oR3DCQuery->GetUserIDByPlayerID($config, $aTPlayers[$i][3]);
        echo " <b>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_72", $config)."</b> ";

        switch($aTPlayers[$i][5]){

          case "p":
            echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_73", $config)."</i>";
            break;

          case "a":
            echo "<i>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_74", $config)."</i>";
            break;

        }

        echo "<br>";

      }

      $i++;
    }

  }
?>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_60", $config);?></td>
<td class='row2'>
<?php

  switch($nplayersignuptype){

    case "1":
       echo "".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_63", $config)."";
       break;

    case "2":
      echo "".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_64", $config)."";
      break;

  }

?>
</td>
</tr>
</table>

<?php
  }


  if($oR3DCQuery->v2IsOTMTournamentComplete($TYPE, $TID)){

    $oR3DCQuery->v2GenerateTournamentResultTable($TYPE, $TID);

  }else{

    // Check if the player is logged on
    if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){
      $oR3DCQuery->v2GetCurrentTournamentGamesHTML($TYPE, $TID, $tzn, $_SESSION['id']);
    }else{
      $oR3DCQuery->v2GetCurrentTournamentGamesHTML($TYPE, $TID, $tzn, 0);
    }

  }

}


?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_11", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_tournament_status.php';">
</center>
<br>