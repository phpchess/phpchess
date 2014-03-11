<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<center><h1><?php echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_1", $config);?></h1></center>

<?php
if($action == "lesson"){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<b><a href='./chess_activities.php'><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_1", $config);?></a></b> >> <b><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_2", $config);?></b>
</td>
</tr>
</table>

<?php
  $oR3DCQuery->GetPersonalActivityListHTML($_SESSION['id'], "lsn");

}elseif($action == "puzzle"){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<b><a href='./chess_activities.php'><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_1", $config);?></a></b> >> <b><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_3", $config);?></b>
</td>
</tr>
</table>

<?php
  $oR3DCQuery->GetPersonalActivityListHTML($_SESSION['id'], "pzl");

}elseif($action == "other"){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<b><a href='./chess_activities.php'><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_1", $config);?></a></b> >> <b><?php echo GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_4", $config);?></b>
</td>
</tr>
</table>

<?php
  $oR3DCQuery->GetPersonalActivityListHTML($_SESSION['id'], "");

}elseif($action == "sa"){

  // Check if the player has access to view the activity
  if($oR3DCQuery->IsPlayerAllowedToViewActivity($_SESSION['id'], $activityid)){

    // Create the layout of the activity page
    echo "<form name='frmActivity' method='GET' action='./chess_view_activities.php'>";
    echo "<table width='95%' border='0' align='center'>";
    echo "<tr>";
    echo "<td align='left'>";
    echo "<b><a href='./chess_activities.php'>".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_1", $config)."</a></b> >> <b>".$oR3DCQuery->GetActivityNameByID($activityid)."</b>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<b>".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_5", $config)." ".($pgi+1)."/".$oR3DCQuery->GetActivityPageCount($activityid)."</b>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' align='center'>";
    $oR3DCQuery->GetActivityPageResource($activityid, $pgi);
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' >";
    $oR3DCQuery->GetActivityPageText($activityid, $pgi);
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' align='center'>";

    if($txtinfo != ""){
      echo $txtinfo;
    }

    $oR3DCQuery->HandleActivityPageControlType($activityid, $pgi, $_SESSION['id']);

    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td align='left'>";

    if($oR3DCQuery->GetActivityPageType($activityid, $pgi) != "lsn"){
      echo "<input type='button' name='btnViewSolution' value='".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_6", $config)."' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$activityid."&pgi=".$pgi."&vs=1';\" class='mainoption'>";
    }    

    echo "</td>";
    echo "<td align='right'>";

    // BAck Button
    if(($pgi-1) < 0){
      echo "<input type='button' name='btnBack' value='".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_7", $config)."' disabled class='mainoption'>";
    }else{
      echo "<input type='button' name='btnBack' value='".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_7", $config)."' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$activityid."&pgi=".($pgi-1)."';\" class='mainoption'>";
    }

    // Next Button
    if(($pgi+1) < $oR3DCQuery->GetActivityPageCount($activityid)){
      echo "<input type='button' name='btnNext' value='".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_8", $config)."' onclick=\"window.location='./chess_view_activities.php?tag=sa&aid=".$activityid."&pgi=".($pgi+1)."';\" class='mainoption'>";
    }else{
      echo "<input type='button' name='btnNext' value='".GetStringFromStringTable("IDS_VIEW_ACTIVITIES_TXT_8", $config)."' disabled class='mainoption'>";
    }

    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "</form>";

  }


}else{
  // do nothing
}
?>