<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }
  
	$image = $oAvatars->GetAvatarImageName($playerid);
	
?>
<table align='center' cellpadding="10">
<tr>
	<td valign='top'>
		<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1">
			<tr>
				<td colspan='3' class='tableheadercolor'>
					<b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_STATISTICS_AVATAR_TABLE_HEADER", $config);?></font><b>
				</td>
			</tr>
			<tr>
				<td class='row2'  valign='top'>
					<a href="chess_cfg_avatar.php">
						<img src='./avatars/<?php echo $image != "" ? $image : "noimage.jpg";?>'>
					</a>
				</td>
			</tr>
		</table>
	</td>
	<td valign='top'>
	<?php
	  $oR3DCQuery->GetPlayerStatusInformation($config, $playerid, $name);
	?>
	</td>
</tr>
<tr>
	<td colspan='2'>
	<?php
	  $txtRealName = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $txtLocation = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $txtAge = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $txtSelfRating = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $txtComment = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $txtChessPlayer = GetStringFromStringTable("IDS_CHESS_STATISTICS_TXT_1", $config);
	  $oR3DCQuery->GetPersonalInformation($config, $txtRealName, $txtLocation, $txtAge, $txtSelfRating, $txtComment, $txtChessPlayer, $playerid);
	?>
		<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
		<tr>
			<td colspan='2' class='tableheadercolor'>
				<b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CHESS_STATISTICS_PI_TABLE_HEADER", $config);?></font><b>
			</td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_1", $config);?></td><td class='row2'><?php echo $txtRealName;?>
			</td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_2", $config);?></td><td class='row2'><?php echo $txtLocation;?>
			</td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_3", $config);?></td><td class='row2'><?php echo $txtAge;?>
			</td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_4", $config);?></td><td class='row2'><?php echo $txtSelfRating;?>
			</td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_5", $config);?></td><td class='row2'><?php echo $txtComment;?></td>
		</tr>
		<tr>
			<td class='row1'>
				<?php echo GetStringFromStringTable("IDS_MANAGE_INFORMATION_TABLE_TXT_6", $config);?></td><td class='row2'><?php echo $txtChessPlayer;?>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
<br>

<table border='0' align='center' cellpadding="3" cellspacing="1">
<tr>
	<td class='row1'>
		<input type='button' name='btnViewType1' value='<?php echo GetStringFromStringTable("IDS_CHESS_STATS_BTN_TXT_1", $config);?>' OnClick="location.href='./chess_othp_gamelist.php?pid=<?php echo $playerid;?>&lstype=1';" class='mainoption'>
	</td>
	<td class='row1'>
		<input type='button' name='btnViewType2' value='<?php echo GetStringFromStringTable("IDS_CHESS_STATS_BTN_TXT_2", $config);?>' OnClick="location.href='./chess_othp_gamelist.php?pid=<?php echo $playerid;?>&lstype=2';" class='mainoption'>
	</td>
</tr>
</table>
<br><br>
<center>
	<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>