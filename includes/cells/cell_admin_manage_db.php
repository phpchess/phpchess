<?php
  //example content cell for admin content page

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

$active_players = $oAdmin->count_active_players();
$archived_players = $oAdmin->count_archived_players();
$challenges = $oAdmin->count_challenges();
$active_games = $oAdmin->count_active_games();
$completed_games = $oAdmin->count_completed_games();
$archived_games = $oAdmin->count_archived_games();
$active_moves = $oAdmin->count_active_moves();
$active_messages = $oAdmin->count_active_messages();
$history_count = $oAdmin->count_history();

?>

<table width='100%' cellpadding='2' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_20", $config);?></font>
<p> </p>
</td>
</tr>
<tr><td><left>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP01", $config);?> <?php echo $active_players ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP02", $config);?> <?php echo $archived_players ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP03", $config);?> <?php echo $challenges ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP04", $config);?> <?php echo $active_games ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP05", $config);?> <?php echo $completed_games ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP06", $config);?> <?php echo $archived_games ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP07", $config);?> <?php echo $active_moves ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP08", $config);?> <?php echo $active_messages ?></p>
<p><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_REP09", $config);?> <?php echo $history_count ?></p>
</left></td>
<td><left>
<form class="dbadminform" method="get" action="manage_db_run.php">
Archive completed games older than 
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option selected value="720">720</option>
</select>
days to db table 
<input type="hidden" name="action" value="archivegames" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_ARCHIVE", $config);?>' class='mainoption'/>
</form>
<form class="dbadminform" method="get" action="manage_db_run.php">
Delete games with status
<select name="status">
<option value="W">Open Challenges</option>
<option value="A">Active Game</option>
<option value="C">Completed Game</option>
<option value="*">Any</option>
</select>
older than
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option  selected value="720">720</option>
</select>
 days
<input type="hidden" name="action" value="deletegamesby" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_DELETE", $config);?>'class='mainoption'/>
</form>

<form class="dbadminform" method="get" action="manage_db_run.php">
Delete games where player info is missing
<input type="hidden" name="action" value="deletegamesbyplayerinfo" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_DELETE", $config);?>'class='mainoption' />
</form>


<form class="dbadminform" method="get" action="manage_db_run.php">
Archive Players with 0 games older than
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option selected value="720">720</option>
</select> days
<input type="hidden" name="action" value="archiveplayersby0" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_ARCHIVE", $config);?>'class='mainoption'/>
</form>

<form class="dbadminform" method="get" action="manage_db_run.php">
Unarchive Player 
<input type="text" name="playername" />
<input type="hidden" name="action" value="unarchiveplayer" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_UNARCHIVE", $config);?>'class='mainoption' />
</form>

<form class="dbadminform" method="get" action="manage_db_run.php">
Delete Players with 0 games older than
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option selected value="720">720</option>
</select>
 days
<input type="hidden" name="action" value="deleteplayersby0" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_DELETE", $config);?>'class='mainoption' />
</form>

<form class="dbadminform" method="get" action="manage_db_run.php">
Delete all Messages older than
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option selected value="720">720</option>
</select>
days (<label for="including"><input type="checkbox" name="includingsaved" />including saved messages</label>)
<input type="hidden" name="action" value="deletemessagesolder" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_DELETE", $config);?>'class='mainoption' />
</form>

<form class="dbadminform" method="get" action="manage_db_run.php">
Clean-up who is online history for last
<select name="days">
<option value="1">1</option>
<option value="2">2</option>
<option value="3">3</option>
<option value="5">5</option>
<option value="7">7</option>
<option value="10">10</option>
<option value="14">14</option>
<option value="30">30</option>
<option value="45">45</option>
<option value="60">60</option>
<option value="90">90</option>
<option value="120">120</option>
<option value="180">180</option>
<option value="240">240</option>
<option value="360">360</option>
<option value="520">520</option>
<option selected value="720">720</option>
</select>
days
<input type="hidden" name="action" value="cleanuphistoryby" />
<input type="submit" value='<?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_DELETE", $config);?>'class='mainoption' />
</form>


</left>
</td>
</tr>
</table>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>

