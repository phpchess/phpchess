<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if(!$oR3DCQuery->IsCreditsSystemEnabled()){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_1", $config);?>
</td>
</tr>
</table>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<b><a href='./chess_activities.php'><?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_2", $config);?></a></b> >> <b><?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_3", $config);?></b>
</td>
</tr>
</table>

<?php
}else{

if($msg == "PA"){
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_4", $config);?>
</td>
</tr>
</table>

<?php
}
?>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='95%'>
<tr>
<td class="row2">
<b><a href='./chess_activities.php'><?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_2", $config);?></a></b> >> <b><?php echo GetStringFromStringTable("IDS_GET_ACTIVITIES_TXT_3", $config);?></b>
</td>
</tr>
</table>

<?php
  $oR3DCQuery->GetActivityListForPurchaseHTML($tag1, $_SESSION['id']);
}
?>