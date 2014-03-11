<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

    // Create the layout of the activity page
    echo "<form name='frmActivity' method='GET' action='./chess_view_activities.php'>";
    echo "<table width='95%' border='0' align='center'>";
    echo "<tr>";
    echo "<td align='left'>";
    echo "<b>".$oR3DCQuery->GetActivityNameByID($activityid)."</b>";
    echo "</td>";
    echo "<td align='right'>";
    echo "<b>".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_1", $config)." ".($pgi+1)."/".$oR3DCQuery->GetActivityPageCount($activityid)."</b>";
    echo "</td>";
    echo "</tr>";

    echo "<tr>";
    echo "<td colspan='2' align='center'>";
    $oR3DCQuery->GetActivityPageResource($activityid, $pgi, "../");
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

    $oR3DCQuery->HandleActivityPageControlTypeAdmin($activityid, $pgi);

    echo "</td>";
    echo "</tr>";


    echo "<tr>";
    echo "<td align='left'>";

    if($oR3DCQuery->GetActivityPageType($activityid, $pgi) != "lsn"){
      echo "<input type='button' name='btnViewSolution' value='".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_2", $config)."' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$activityid."&pgi=".$pgi."&vs=1';\" class='mainoption'>";
    }    

    echo "</td>";
    echo "<td align='right'>";

    // BAck Button
    if(($pgi-1) < 0){
      echo "<input type='button' name='btnBack' value='".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_3", $config)."' disabled class='mainoption'>";
    }else{
      echo "<input type='button' name='btnBack' value='".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_3", $config)."' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$activityid."&pgi=".($pgi-1)."';\" class='mainoption'>";
    }

    // Next Button
    if(($pgi+1) < $oR3DCQuery->GetActivityPageCount($activityid)){
      echo "<input type='button' name='btnNext' value='".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_4", $config)."' onclick=\"window.location='./preview_activity.php?tag=sa&aid=".$activityid."&pgi=".($pgi+1)."';\" class='mainoption'>";
    }else{
      echo "<input type='button' name='btnNext' value='".GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_4", $config)."' disabled class='mainoption'>";
    }

    echo "</td>";
    echo "</tr>";

    echo "</table>";
    echo "</form>";


    echo "<form name='frmActivity' method='GET' action='./preview_activity.php'>";
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2" align='center'>
<input type='button' name='btnMovePageLeft' Value='<?php echo GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_5", $config);?>' class='mainoption' onclick="window.location='./preview_activity.php?tag=ml&aid=<?php echo $activityid;?>&pgi=<?php echo $pgi;?>';">
<input type='button' name='btnMovePageRight' Value='<?php echo GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_6", $config);?>' class='mainoption' onclick="window.location='./preview_activity.php?tag=mr&aid=<?php echo $activityid;?>&pgi=<?php echo $pgi;?>';">
<input type='button' name='btnDeletePage' Value='<?php echo GetStringFromStringTable("IDS_PREVIEW_ACTIVITY_TXT_7", $config);?>' class='mainoption' onclick="window.location='./preview_activity.php?tag=del&aid=<?php echo $activityid;?>&pgi=<?php echo $pgi;?>';">
</td>
</tr>
</table>

<?php
    echo "</form>";
?>