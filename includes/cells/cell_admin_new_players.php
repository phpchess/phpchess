<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
  $bLimit = $oR3DCQuery->IsUserLimitReached();

  if($bLimit == false){
?>

<form name='frmManageNewPlayers' method='Post' action='./admin_new_players.php'>
<?php
  $oAdmin->GetNewPlayers($config);
?>
</form>



<?php
}else{
?>
<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_NEWPLAYERS_TXT_1", $config);?>
<?php
}
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_2", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_players.php';">
</center>
<br>