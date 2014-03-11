<?php
  //example content cell for admin content page

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }


?>

<table width='100%' cellpadding='2' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_LOOKANFEEL_TABLE_TXT_20", $config);?></font>
<p> </p>
<br>
<?php
if ($_REQUEST['approved']=='y') {
  switch($_REQUEST['action']) {
  case "archivegames":
    $result = $oAdmin->archive_games($_REQUEST['days']); 
    break;
  case "deletegamesby":
    $result = $oAdmin->delete_games($_REQUEST['days'], $_REQUEST['status']);
    break;
  case "deletegamesbyplayerinfo":
    $result = $oAdmin->delete_games_missing_player_info(); 
    break;
  case "archiveplayersby0":
    $result = $oAdmin->archive_players_with_0_games($_REQUEST['days']); 
    break;
  case "unarchiveplayer":
    $result = $oAdmin->unarchive_player($_REQUEST['playername']);
  case "deleteplayersby0":
    $result = $oAdmin->delete_players_with_0_games($_REQUEST['days']); 
    break;
  case "deletemessagesolder":
    $result = $oAdmin->delete_messages_older($_REQUEST['days'], ($_REQUEST['including'] == "on"));
    break;
  case "cleanuphistoryby":
    $result = $oAdmin->clean_up_history($_REQUEST['days']); 
    break; }
  if ($result) {
    ?><center>
    <font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_RESULT1", $config);?></font>
	</center><br></td></tr></table>
	<br><br><center>
<a href="manage_db.php"><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_BACK", $config);?></a></center>
      <?php
      } else {
    ?>
     <center>
    <font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_RESULT2", $config);?></font>
	</center><br></td></tr></table>
	<br><br><center>
      <a href="manage_db.php"><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_BACK", $config);?></a></center>
      
      <?php } ?>

      <?php
}
else {
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}
$url = curPageURL();
?><center>
<font class='row2'><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_TXT_WARNING", $config);?></font>
</center>
<br>
</td>
</tr>
</table>
    <br /><br /><br /><center>
    	<a class='mainoption' href="<?php echo $url ?>&approved=y"><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_CONTINUE", $config);?></a>
    	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
	<a class='mainoption' href="manage_db.php"><?php echo GetStringFromStringTable("IDS_ADMIN_ARCHIVE_BUTTON_ABORT", $config);?></a>
</center><?php
}
?>