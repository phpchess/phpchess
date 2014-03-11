<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<center><h1><?php echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_1", $config);?></h1></center>

<table width='80%' cellpadding='3' cellspacing='1' border='0' align='center'>
<tr>
<td valign='top'>
<center>
<a href='./chess_buy_credits.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/ca_1_1.gif' border='0'></a>
<br>
<?php 

echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_2", $config)."<br>";
echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_3", $config);

echo " ".$oR3DCQuery->GetPlayerCredits($_SESSION['id']);

?>
</center>
</td>

<td valign='top'>
<center>
<a href='./chess_get_activities.php?tag=puzzle'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/ca_1_2.gif' border='0'></a>
<br>
<?php 

echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_5", $config)."<br>";
echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_4", $config);

echo " ".$oR3DCQuery->GetPuzzleCount();

?>
</center>
</td>

<td valign='top'>
<center>
<a href='./chess_get_activities.php?tag=lesson'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/ca_1_3.gif' border='0'></a>
<br>
<?php 

echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_6", $config)."<br>";
echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_4", $config);

echo " ".$oR3DCQuery->GetLessonCount();

?>
</center>
</td>

<td valign='top'>
<center>
<a href='./chess_get_activities.php?tag=other'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/ca_1_4.gif' border='0'></a>
<br>
<?php 

echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_7", $config)."<br>";
echo GetStringFromStringTable("IDS_CHESS_ACTIVITIES_TXT_4", $config);

echo " ".$oR3DCQuery->GetOtherCount();

?>
</center>
</td>
</tr>
</table>
<br><br>
<?php

//Get the players activity stats
$oR3DCQuery->GetPersonalActivityStatsHTML($_SESSION['id']);

?>

<table width='100%' cellpadding='0' cellspacing='0' border='0'>

</table>