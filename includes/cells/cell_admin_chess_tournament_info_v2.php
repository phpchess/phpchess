<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }




  /////////////////////////////////////////

  // One to Many

  if($type == 1){



    $oR3DCQuery->v2GetTournamentInformation_OneToMany($tid, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);



?>



<table border='0' align='center' class="forumline" cellpadding="10" cellspacing="1" width='538'>

<tr>

<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_71", $config);?></font><b></td>

</tr>

<tr><Td>&nbsp;</Td></tr>

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

<?php echo gmdate("Y-m-d, H:i:s", ($nplayercutoffdate + (3600*$strtimezone)));?>

</td>

</tr>



<tr>

<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_9", $config);?></td>

<td class='row2'>

<?php echo gmdate("Y-m-d, H:i:s", ($ntournamentstartdate + (3600*$strtimezone)));?>

</td>

</tr>



<tr>

<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_10", $config);?></td>

<td class='row2'>

<?php echo gmdate("Y-m-d, H:i:s", ($ntournamentenddate + (3600*$strtimezone)));?>

</td>

</tr>



<tr>

<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_35", $config);?></td>

<td class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config);?> <?php echo $strtimezone;?></td>

</tr>



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



?>