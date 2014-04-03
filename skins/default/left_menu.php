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
 //  Left Menu Include
 //
 //
?>
<table width="207" border="0" cellspacing="0" cellpadding="0" align="center" >
<tr>
<td align="center">
	<a href="chess_statistics.php">
		<img src="<?php $image = $oR3DCQuery->GetAvatarImageName($_SESSION['id']); echo $Root_Path . '/avatars/' . ($image == "" ? "noimage.jpg" : $image); ?>" />
	</a>
</td>
</tr>
<tr><td class="white"><h3 class="menu_title">
<?php echo GetStringFromStringTable("IDS_MENU_TXT_6", $config);?></h3>

<?php if($_SESSION['id'] != ""){
  $oR3DCQuery->GetPlayerStatusByPlayerID($config, $_SESSION['id']);
}
?></td></tr>
<tr>
      <td ><h3 class="menu_title">Navigation</h3>
		<ul class="mainmenu">
		<li ><a href="<?php echo $Root_Path;?>chess_members.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_10", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_msg_center.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_11", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_view_games_rt.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_12", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_find_player.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_13", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_buddy_list.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_14", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_statistics.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_15", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_cfg.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_16", $config);?></a></li>
		<li><a href="<?php echo $Root_Path;?>chess_logout.php"><?php echo GetStringFromStringTable("IDS_APPLICATION_HEADERS_8", $config);?></a></li>
		</ul></td>        
</tr>
	  <tr>
         <td align="left" >
<?php include($Root_Path."skins/".$SkinName."/otherlinks.php");?>
</td></tr>

<?php if($bShowPanels){?>
 <tr><td class="white"><h3 class="menu_title">
<?php //echo GetStringFromStringTable("IDS_MENU_TXT_26", $config);?></h3>
<?php
//if($_SESSION['id'] != ""){
//  $oR3DCQuery->GetActivitiesMenuBarHTML($_SESSION['id']);
//  $oR3DCQuery->GetPlayerActivityHTMLMenu($_GET['aid'], $_GET['pgi'], $_SESSION['id']);
//}
//?>
</td></tr>
<?php
}
?>
<?php /* if($bShowPanels){ ?>
 <tr><td class="white"><h3 class="menu_title">
<?php echo GetStringFromStringTable("IDS_MENU_TXT_8", $config);?></h3>
<?php
if($_SESSION['id'] != ""){
  $oR3DCQuery->DownloadNewMessages($config, $_SESSION['id'], $_SESSION['sid']);
  $oR3DCQuery->GetNewMessages($config, $_SESSION['id']);
}
?>
</td></tr>


<?php
} */
?>
<?php
if($bShowPanels) { ?>
<tr><td class="white">
<h3 class="menu_title">
<?php // echo GetStringFromStringTable("IDS_MENU_TXT_9", $config);?>
</h3>
<?php
if($_SESSION['id'] != ""){
$oR3DCQuery->GetCurrentGamesByPlayerID($config, $_SESSION['id']);
}
?>
</td></tr>
<?php } ?>
</table>
			
			
			
			
			
