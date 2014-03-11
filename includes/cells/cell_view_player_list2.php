<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
	__get_player_list($oR3DCQuery);
  $oBuddyList->GetBuddyList($config, $_SESSION['id']);
  echo "<br>";
  $oR3DCQuery->ListAvailablePlayersB($config, $SkinName, "chess_view_player_list2.php", $action, $index);
?>

<script>

	var players = <?php echo json_encode(__get_player_list($oR3DCQuery)); ?>;		// Holds list of players that exist on the server.
	
	/*
		ID, nick, join date, online status, rating, [challenge] [add-as|remove-as buddy]
	*/

	$().ready(function(){
		
		console.log('hi');
	
	});
	
	function create_buddy_table()
	{
	
	}
	
	function create_non_buddy_table()
	{
	
	}
	
	// Constructs a html table row using the given player info.
	function player_row_html(player_info)
	{
	
	}


</script>






<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>
<br>