<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }
  
  if (!function_exists('mysqli_result')) {
	  function mysqli_result($result, $number, $field=0) {
	      mysqli_data_seek($result, $number);
	      $row = mysqli_fetch_array($result);
	      return $row[$field];
	  }
  }
?>


<!-- Help link -->
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center'>
	<tr>
		<td align='right'>
			<a href='./chess_faq.php'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_1", $config);?></a>
		</td>
	</tr>
</table>

<!-- Gretting -->
<div class='tableheadercolor' style="font-size: 1.5em;"><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_GREETING", $config);?></div>

<!-- List of server messages -->
<?php
$oServMsg->GetServerMessages($config);
?>

<!-- List of user settings/actions -->
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
	<tr>
		<td class='tableheadercolor' colspan='5'>
			<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_2", $config);?></font>
		</td>
	</tr>
	<tr>
		<td width="20%">
			<center>
			<a href="javascript:promptChallenge('0');"><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/startgame.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config);?>
			</center>
		</td>
		<td width="20%">
			<center>
			<a href='./chess_cfg.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/mysettings.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_5", $config);?>
			</center>
		</td>
		<td width="20%">
			<center>
			<a href='./chess_activities.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/activities.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_6", $config);?>
			</center>
		</td>
		<td width="20%">
			<center>
			<a href='./chess_tournament_status.php'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/cluboptions.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_MY_TOURNAMENTS", $config);?>
			</center>
		</td>
		<td width="20%">
		<?php if($oR3DCQuery->GetPChatSettings() == 1){?>
			<center>
			<a href="javascript:PlayerChat('./pc_index.php')"><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/playerchat.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_9", $config);?>
			</center>
		<?php }else{?>
			<center>
			<a href='javascript:alert("<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_10", $config);?>")'><img src='<?php echo $Root_Path."skins/".$SkinName."/";?>images/playerchat.gif' border='0'></a>
			<br>
			<?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_9", $config);?>
			</center>
		<?php }?>
		</td>
	</tr>
</table>



<?php
  //$oR3DCQuery->v2ActiveTournamentManagement($_SESSION['id']);
  //$oR3DCQuery->GetOngoingGameCount($config, $_SESSION['id']);
  //$oR3DCQuery->GetMessageCount($config, $_SESSION['id']);
  
  //$oR3DCQuery->GetCurrentGamesByPlayerID($config, $_SESSION['id']);


  $RequiresPayment = $oBilling->IsPaymentEnabled();
  $oBilling->GetBillCountByUName($_SESSION['user'], $Current, $Previous);

if($RequiresPayment == true){
?>
<br>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
	<tr>
		<td class='tableheadercolor' colspan='2'>
			<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TABLE_BILLING_HEADER", $config);?></font>
		</td>
	</tr>
	<tr>
		<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TABLE_BILLING_TXT_1", $config);?></td>
		<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TABLE_BILLING_TXT_2", $config);?></td>
	</tr>
	<tr>
		<td class='row2'><?php echo $Current;?> <a href='./renew_bill.php' class='sitelinks'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TABLE_BILLING_TXT_3", $config);?></a></td>
		<td class='row2'><?php echo $Previous;?> <a href='./chess_previous_bills.php' class='sitelinks'><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TABLE_BILLING_TXT_3", $config);?></a></td>
	</tr>
</table>
<?php }?>

<br />

<div id="challengewrapper">
</div>
<div id="challenge">
	<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config); ?>
	<br/>
	<a href="javascript:cancelChallenge();">
		<img src="modules/RealTimeInterface/images/close.png" width="10" style="border:none; position:absolute; right:3px; top:3px;" />
	</a>
	<div style="clear:both">
		<div style="float:left"><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TABLE_TXT_2", $config); ?></div>
		<div style="float:right">
			<select id="mypiececolor" class="post">
				<option value="w">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_1", $config); ?>
				</option>
				<option value="b">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_SELECT_COLOR_2", $config); ?>
				</option>
			</select>
		</div>
	</div>
	<div style="clear:both">
		<div style="float:left"><?php echo GetStringFromStringTable("IDS_CREATE_GAME_TXT_8", $config); ?></div>
		<div style="float:right">
			<select id="ratingtype" class="post">
				<option value="grated">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_7", $config); ?>
				</option>
				<option value="gunrated">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_8", $config); ?>
				</option>
			</select>
		</div>
	</div>
	<div style="clear:both">
<?php
$snail = $slow = $normal = $short = $blitz = 0;
$timemode = 0;
$oR3DCQuery->GetServerGameOptions($snail, $slow, $normal, $short, $blitz, $timemode);
$times = array('snail' => $snail, 'slow' => $slow, 'normal' => $normal, 'short' => $short, 'blitz' => $blitz);
$durations = array();
foreach($times as $key => $time)
{
	$time = $time * 86400;
	$d = (int)($time / 86400);
	$time -= $d * 86400;
	$h = (int)($time / 3600);
	$time -= $h * 3600;
	$m = (int)($time / 60);
	$h = str_pad($h, 2, '0', STR_PAD_LEFT);
	$m = str_pad($m, 2, '0', STR_PAD_LEFT);
	$durations[$key] = "$d $h:$m";
}
?>
		<div style="float:left"><?php echo __l('Game Time'); ?></div>
		<div style="float:right">
			<select id="gametime" class="post">
				<option value="C-Blitz">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_1", $config) . ' (' . $durations['blitz'] . ')';?>
				</option>
				<option value="C-Short">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_2", $config) . ' (' . $durations['short'] . ')';?>
				</option>
				<option value="C-Normal">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_3", $config) . ' (' . $durations['normal'] . ')';?>
				</option>
				<option value="C-Slow">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_4", $config) . ' (' . $durations['slow'] . ')';?>
				</option>
				<option value="C-Snail">
					<?php echo GetStringFromStringTable("IDS_CREATE_GAME_OPT_5", $config) . ' (' . $durations['snail'] . ')';?>
				</option>
			</select>
		</div>
	</div>
	<br/>
	<div style="clear:both; text-decoration: underline; cursor: pointer" onclick="$('#adv_game_settings').toggle();">
		<?php echo __l('Advanced settings'); ?>
	</div>
	<div id="adv_game_settings" style="display: none">
		<br/>
		<div style="clear:both">
			<input type='text' id='fen' name='fen' value='' class='post' size='40'>
			<input type='button' name='cmdCreatFEN' value='<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CF", $config);?>' class='mainoption' onClick='javascript:PopupWindowRT("./pgnviewer/board2fen.html");'>
		</div>
		<br/>
		<div style="clear:both">
			<div style="float:left"><?php echo __l('Time Control 1'); ?>:</div><br/>
			<div>
				<input id="tc_m1" class="post" size="1"/> <?php echo __l('moves adds'); ?> 
				<input id="tc_t1" class="post" size="1"/> <?php echo __l('minutes'); ?>
			</div>
			<br/>
			<div><?php echo __l('Time Control 2'); ?>:</div>
			<div>
				<input id="tc_m2" class="post" size="1" /> <?php echo __l('moves adds'); ?> 
				<input id="tc_t2" class="post" size="1"/> <?php echo __l('minutes'); ?>
			</div>
		</div>
	</div>
	<br/>
	<div style="clear:both">
		<input type="button" class="mainoption" style="float:left" onclick="cancelChallenge();" value="<?php echo __l('Cancel'); ?>" />
		<input type="button" class="mainoption" style="float:right" onclick="challenge();" value="<?php echo GetStringFromStringTable("IDS_CREATE_GAME_BTN_CG", $config)?>" />
	</div>
</div>
<div id="wrapper">
	<div id="playerswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader">
		<span><?php echo GetStringFromStringTable("IDS_INDEX_TXT_4", $config); ?></span>
		<span id="players_sorting_area"></span>
		<span id="players_notification_area" ></span>
		</div>
		<div id="players" style="overflow:auto">
		</div>
	</div>
	<div id="challengeswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader"><span style="display:inline-block; width:200px"><?php echo GetStringFromStringTable("IDS_CHESS_MEMBER_TXT_4", $config); ?></span></div>
		<div id="challenges" style="overflow:auto">
		</div>
	</div>
	<div id="opengameswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader"><span style="display:inline-block; width:200px"><?php echo GetStringFromStringTable("IDS_CR3DCQUERY_TXT_42", $config); ?></span></div>
		<div id="opengames" style="overflow:auto">
		</div>
	</div>
	<div id="recentgameswrapper" class="forumline" style="overflow:auto">
		<div class="tableheadercolor sitemenuheader"><span style="display:inline-block;"><?php echo __l('Recently Finished Games') ?></span></div>
		<div id="recentgames" style="overflow:auto">
		</div>
	</div>
</div>
<div id="temp_elements" style="height: 0px; width: 10px;">
</div>


<script type="text/javascript" >
	
	//set the session id, player id and the user
   
    var sessionid="<?php echo $_SESSION['sid'];?>", playerid="<?php echo $_SESSION['id'];?>", user="<?php echo $_SESSION['user'];?>"
	
	<?php 
		$white_colour = ""; $black_colour = "";
		$oR3DCQuery->GetChessBoardColors('', $_SESSION['id'], $white_colour, $black_colour);
		echo "var tile_colour_white = \"$white_colour;\"\n";
		echo "var tile_colour_black = \"$black_colour;\"\n";
	?>
	
	var LANG = {
		txt_days_remaining: '<?php echo __l('Days Remaining: {d}') ?>',
		txt_time_remaining1: '<?php echo __l('Time Remaining: {h}:{m}') ?>',
		txt_time_remaining2: '<?php echo __l('Time Remaining: {m}:{s}') ?>',
		txt_game_timed_out: '<?php echo __l('Game has timed out') ?>'
	};
	
	<?php
		$timeouts = array('snail' => NULL, 'slow' => NULL, 'normal' => NULL, 'short' => NULL, 'blitz' => NULL);
		$query = "SELECT * FROM admin_game_options WHERE o_id = 1";
		$return = mysqli_query($oR3DCQuery->link,$query) or die(mysqli_error($oR3DCQuery->link));
		$num = mysqli_num_rows($return);

		if($num != 0)
		{
			$timeouts['snail'] = trim(mysqli_result($return,0,"o_snail")) * 86400;
			$timeouts['slow'] = trim(mysqli_result($return,0,"o_slow")) * 86400;
			$timeouts['normal'] = trim(mysqli_result($return,0,"o_normal")) * 86400;
			$timeouts['short'] = trim(mysqli_result($return,0,"o_short")) * 86400;
			$timeouts['blitz'] = trim(mysqli_result($return,0,"o_blitz")) * 86400;
		}
	?>
	var timeouts = <?php echo json_encode($timeouts); ?>;
	var timeleft = {started: 0, duration: 0};
	
	var update_speed = <?php echo $conf['view_chess_games_refresh_rate']; ?> * 1000;
	var players_online = [];	// Holds list of players online.
	var buddy_list = [];		// Holds buddy IDs.
	
	//variables to hold commonly used layers
	var playersdiv, cdiv, opendiv, recentgames;
	var player_details = new Object;			// Caches player details. Key: player id.
	var clickcheck = new Object;		// Stores player ids for which player details are being retrieved.
	var player_list = {};				// Stores the player list xml from the server.
	
	//browser information and a variable that holds the player to be challenged
	var ie=false, other_player_id=0;
	
	if(navigator.appName.indexOf('Microsoft')>-1){
		ie=true;
	}
	
	//open a popup that replays the game
	function replayGame(gid){
		window.open ("pgnviewer/view_pgn_game.php?gameid="+gid, "pgn","location=0,status=0,scrollbars=0,menubar=0,width=600,height=500"); 
	}
	
	//function to set the location of an HTML element to the dest element
	function setLocation(src, dest){
		var offx=src.offsetLeft, offy=src.offsetTop+src.offsetHeight;
			
		while(src=src.offsetParent){
			offx+=src.offsetLeft;
			offy+=src.offsetTop;
		}
		
		offy-=5;
		offx+=5;
		
		if(ie){
			dest.style.left=offx;
			dest.style.top=offy;
		}else{
			dest.style.left=offx+'px';
			dest.style.top=offy+'px';
		}
	}
	
	function processChallenge(transport)
	{
		cancelChallenge();
		// Once a game is created need to update the game lists.
		getOpen();
		getChallenges();
	}
	
	function challenge(){
		var mypiececolor, ratingtype, gametime, move1, move2, time1, time2, fen;             
		if(other_player_id!='0'){
			hide_player_details(other_player_id);
		}
		document.getElementById('challenge').style.visibility='hidden';
		document.getElementById('challenge').style.display='none';
		mypiececolor=document.getElementById('mypiececolor').value;
		ratingtype=document.getElementById('ratingtype').value;
		gametime=document.getElementById('gametime').value;
		move1 = $('#tc_m1').val();
		move2 = $('#tc_m2').val();
		time1 = $('#tc_t1').val();
		time2 = $('#tc_t2').val();
		fen = $('#fen').val();
		var url='mobile.php?action=creategame&sid='+sessionid+'&precreate=0&mypiececolor='+mypiececolor+'&oplayerid='+other_player_id+'&brtGame=0&gametime='+gametime+'&ratingtype='+ratingtype + '&move1=' + move1 + '&time1=' + time1 + '&move2=' + move2 + '&time2=' + time2 + '&fen=' + fen;
		console.log(url);
		$.get(url, processChallenge);
	}
	
	function cancelChallenge(){
		document.body.style.overflow="auto";
		if(other_player_id!='0'){
			hide_player_details(other_player_id);
		}
		other_player_id=0;
		//document.getElementById('challengewrapper').style.visibility='hidden';
		document.getElementById('challengewrapper').style.display='none';
		document.getElementById('challenge').style.visibility='hidden';
		document.getElementById('challenge').style.display='none';
	}
	
	function promptChallenge(pid){
		$('#player_actions_info').remove();
		other_player_id=pid;

		//$("challengewrapper").show();
		document.getElementById('challenge').style.visibility='visible';
		document.getElementById('challenge').style.display='block';
		document.body.style.overflow="hidden";
		
		var ele = $('#challenge');
		var offY = $(window).scrollTop();
		var offX = $(window).scrollLeft();
		var winY = $(window).height();
		var winX = $(window).width();
		
		ele.css({left: winX / 2 + offX - ele.width() / 2});
		ele.css({top: winY / 2 + offY - ele.height() / 2});
		
		var obj = $('#challengewrapper');
		obj.css({width: $('body').width(), height: $('body').height()});
		obj.show();
	}
	
	function process_player_details(transport, pid)
	{
		var details = {};
		details['wins'] = $(transport).find('WIN').get(0).firstChild.data;
		details['losses'] = $(transport).find('LOSS').get(0).firstChild.data;
		details['draws'] = $(transport).find('DRAW').get(0).firstChild.data;
		details['points'] = $(transport).find('POINTS').get(0).firstChild.data;
		player_details[pid] = details;
		//console.log(player_details);
		display_player_actions(pid);
	}
	
	function showDetails(pid)
	{
		if(clickcheck[pid]==true){
			return;
		}
		if(player_details[pid] !== undefined){
			display_player_actions(pid);
		}else{
			clickcheck[pid]=true;
			var url='mobile.php?action=playerstats&sid='+sessionid+'&playerid='+pid;
			$.get(url, function(data){
				clickcheck[pid] = false;
				process_player_details(data, pid)
			}).error(function(){
				clickcheck[pid] = false;
				//showDetails(pid);
			});
		}
	}
	
	function hide_player_details(pid)
	{
		$('#challengewrapper').hide();
		$('#player_actions_info').remove();
		document.body.style.overflow="auto";
	}
	
	function display_player_actions(pid)
	{
		// overlay
		document.body.style.overflow="hidden";
		var obj = $('#challengewrapper');
		obj.css({width: $('body').width(), height: $('body').height()});
		obj.show();
		// contains actions/info on player
		var d = player_details[pid];
		var html = '<div id="player_actions_info" class=>';
		
		html += '<div style="height: 20px"><a style="cursor: pointer;" onclick="hide_player_details(\''+pid+'\');"><img src="modules/RealTimeInterface/images/close.png" width="10" style="float:right; border:none; position:absolute; right:3px; top:3px;" /></a></div>';
		html += '<div><?php echo _T('IDS_MAIN_PLAYER_RATING', $config); ?> ' + d.points + '</div>';
		html += '<br/>';
		var is_buddy = false;
		for(var b = 0; b < buddy_list.length; b++)
		{
			if(buddy_list[b] == pid) is_buddy = true;
		}
		if(is_buddy)
			html += '<div class="button" onclick="remove_buddy(\'' + pid + '\'); hide_player_details();"><?php echo _T('IDS_MAIN_PLAYER_REMOVE_BUDDY', $config); ?></div>';
		else
			html += '<div class="button" onclick="add_buddy(\'' + pid + '\'); hide_player_details();"><?php echo _T('IDS_MAIN_PLAYER_ADD_BUDDY', $config); ?></div>';
		html += '<div class="button" onclick="promptChallenge(\'' + pid + '\')"><?php echo _T('IDS_CR3DCQUERY_TXT_69', $config); ?></div>';
		html += '<div class="button" onclick="window.location=\'chess_statistics.php?playerid=' + pid + '\'"><?php echo _T('IDS_MAIN_PLAYER_STATISTICS', $config); ?></div>';
		
		html += '</div>';
		
		var ele = $(html);
		
		$('#temp_elements').append(ele);
		var offY = $(window).scrollTop();
		var winY = $(window).height();
		//console.log(offY, winY, winY / 2 + offY, winY / 2 + offY + ele.height() / 2, ele.height());
		ele.css({left: $('body').width() / 2 - ele.width() / 2});
		ele.css({top: winY / 2 + offY - ele.height() / 2});
	}
	
	
	function getPlayers(){
		
		var url='mobile.php?action=playersonline&sid='+sessionid;
		$.get(url, processPlayers);
	}
	
	function processPlayers(transport){
		window.setTimeout('getPlayers()', update_speed);
		
		player_list = $(transport).find('PLAYERS');
		draw_player_list();
	}
	
	function draw_player_list()
	{
		var i, content='', uid, pid, name='';

		// Add image representing a bot to play against.
		content += '<div id="player_ai" title="<?php echo _T('IDS_MAIN_PLAY_COMPUTER', $config); ?>" class="player_list_item" ><a href="javascriptchess.php" target="_blank"><img src="avatars/computer.jpg" /><br/><?php echo _T('IDS_MAIN_COMPUTER_NAME', $config); ?></a></div>';
		
		for(i=0; i<player_list.length; i++){
			name = uid = player_list[i].getElementsByTagName('USERID').item(0).firstChild.data;
			if(uid.length > 15)
				name = uid.substr(0, 12) + '...';
			pid=player_list[i].getElementsByTagName('PLAYERID').item(0).firstChild.data;
			if(pid == playerid) continue;	// Don't add self
			avatar=player_list[i].getElementsByTagName('AVATAR').item(0).firstChild.data;
			players_online.push(uid);
			var classname = "";
			if(pid != playerid)
			{
				for(var b = 0; b < buddy_list.length; b++)
				{
					if(buddy_list[b] == pid)
					{
						classname = "player_list_item_buddy";
						break;
					}
				}
			}
			var str = '<?php echo _T('IDS_MAIN_PLAYER_HOVER', $config); ?>';
			str = str.replace('{player}', uid);
			content=content+'<div id="player' + pid + '" class="' + classname + ' player_list_item" title=" ' + str + '"><a href="javascript:showDetails(\'' + pid + '\')"><img src="' + avatar + '" /><br/>' + name + '</a></div>';
		}
		playersdiv.innerHTML=content;
	}
	
	function getBuddyList()
	{
		var url='mobile.php?action=getbuddylist&sid='+sessionid;
		$.get(url, process_buddies);
	}
	
	function process_buddies(transport)
	{
		var buddies = $(transport).find('BUDDY');
		for(var i = 0; i < buddies.length; i++)
		{
			pid = buddies[i].getElementsByTagName('PID').item(0).firstChild.data;
			buddy_list.push(pid);
		}
	}
	
	function remove_buddy(pid)
	{
		var url = 'mobile.php?action=deletefrombuddylist&buddyid=' + pid + '&sid=' + sessionid;
		$.get(url, removed_buddy).error(remove_buddy_failed);
	}
	function removed_buddy(response)
	{
		var success = $(response).find('BUDDYLIST');
		if(success)
		{
			$("#players_notification_area").hide().text('<?php echo _T('IDS_MANAGE_BUDDYLIST_TEXT_1', $config); ?>').fadeIn(500).animate(
				{opacity: 1}, 2000
			).fadeOut(500);
			var id = $(response).find('BUDDYID').text();
			for(var b = 0; b < buddy_list.length; b++)
			{
				if(buddy_list[b] == id)
				{
					buddy_list.splice(b, 1);
					draw_player_list();
					return;
				}
			}
		}
		else
			alert('%Could not remove buddy%');
	}
	function remove_buddy_failed()
	{
		alert('%Unable to remove buddy due to communication error%');
	}
	
	function add_buddy(pid)
	{
		var url = 'mobile.php?action=addtobuddylist&buddyid=' + pid + '&sid=' + sessionid;
		$.get(url, added_buddy).error(add_buddy_failed);
	}
	function m()
	{
	
	}
	function added_buddy(response)
	{
		var success = $(response).find('BUDDYLIST');

		if(success)
		{
			//alert('%Buddy was added%');
			$("#players_notification_area").hide().text('<?php echo _T('IDS_MANAGE_BUDDYLIST_TEXT_1', $config); ?>').fadeIn(500).animate(
				{opacity: 1}, 2000
			).fadeOut(500);
			var id = $(response).find('BUDDYID').text();
			var exists = false;
			for(var b = 0; b < buddy_list.length; b++)
			{
				if(buddy_list[b] == id) exists = true;
			}
			if(!exists)
			{
				buddy_list.push(id);
				draw_player_list();
			}
		}
		else
			alert('%Could not add buddy%');
	}
	function add_buddy_failed()
	{
		alert('%Unable to add buddy due to communication error%');
	}
	
	function acceptChallenge(gid)
	{
		var url='mobile.php?action=acceptgame&sid='+sessionid+'&gameid='+gid;
		$.get(url, accepted_challenge).error(accept_challenge_failed);
	}
	function accepted_challenge()
	{
		getOpen();
		getChallenges();
	}
	function accept_challenge_failed()
	{
		alert('There was an error accepting the challenge.');
	}
	
	function acceptOpenChallenge(gid)
	{
		var url='mobile.php?action=acceptopenchallange&sid='+sessionid+'&gameid='+gid;
		$.get(url, accepted_challenge).error(acceptopen_challenge_failed);
	}
	function accepted_open_challenge()
	{
		getOpen();
		getChallenges();
	}
	function acceptopen_challenge_failed()
	{
		alert('There was an error accepting the open challenge.');
	}
	
	
	function revokeChallenge(gid)
	{
		var url='mobile.php?action=revokegame&sid='+sessionid+'&gameid='+gid;
		$.get(url, revoked_challenge).error(revoke_challenge_failed);
	}
	function revoked_challenge()
	{
		getOpen();
		getChallenges();
	}
	function revoke_challenge_failed()
	{
		alert('There was an error revoking the challenge.');
	}
	
	// Active games and challenges made by this player
	function processChallenges(transport){
	
		window.setTimeout('getChallenges()', update_speed);

		var i, content='',games = $(transport).find('GAMES'), desc, gid, wid, bid, initid, status, color, timeout, type, fen;
		$('#game_details').remove();
		cdiv.empty();
		recentdiv.empty();
		for(i=0; i<games.length; i++){
			desc=games[i].getElementsByTagName('DESCRIPTION').item(0).firstChild.data;
			gid=games[i].getElementsByTagName('GAMEID').item(0).firstChild.data;
			status=games[i].getElementsByTagName('STATUS').item(0).firstChild.data;
			initid=games[i].getElementsByTagName('INITIATOR').item(0).firstChild.data;
			wid=games[i].getElementsByTagName('WHITE').item(0).firstChild.data;
			bid=games[i].getElementsByTagName('BLACK').item(0).firstChild.data;
			timeout=games[i].getElementsByTagName('TIMEOUT').item(0).firstChild.data;
			type=games[i].getElementsByTagName('GAMETYPE').item(0).firstChild.data;
			rated=games[i].getElementsByTagName('RATED').item(0).firstChild.data;
			time_created=games[i].getElementsByTagName('TIMECREATED').item(0).firstChild.data;
			time_ctrl1=games[i].getElementsByTagName('TIMECONTROL1').item(0).firstChild.data;
			time_ctrl2=games[i].getElementsByTagName('TIMECONTROL2').item(0).firstChild.data;
			fen=games[i].getElementsByTagName('GAMEFEN').item(0).firstChild.data;
			var data = {desc: desc, gid: gid, status: status, initid: initid, wid: wid, bid: bid, timeout: timeout, type: type, rated: rated, time_created: time_created, time_ctrl1: time_ctrl1, time_ctrl2: time_ctrl2, fen: fen};
			create_game_field(data, false);
		}
	
	}
	
	function getChallenges(){
	
		var url='mobile.php?action=gamelist&sid='+sessionid;
		//new Ajax.Request(url,  {  method: 'get', onSuccess: processChallenges, onFailure:getChallenges});
		$.get(url, processChallenges);
	}
	
	// Open challenges (player s ANYONE)
	function processOpen(transport){
		window.setTimeout('getOpen()', update_speed);
		
		var i, games = $(transport).find('GAMES'), content='', desc, gid, wid, bid, initid, status, color, timeout, type, fen;
		
		$('#game_details').remove();
		opendiv.empty();
		for(i=0; i<games.length; i++){
			desc=games[i].getElementsByTagName('DESCRIPTION').item(0).firstChild.data;
			gid=games[i].getElementsByTagName('GAMEID').item(0).firstChild.data;
			status=games[i].getElementsByTagName('STATUS').item(0).firstChild.data;
			initid=games[i].getElementsByTagName('INITIATOR').item(0).firstChild.data;
			wid=games[i].getElementsByTagName('WHITE').item(0).firstChild.data;
			bid=games[i].getElementsByTagName('BLACK').item(0).firstChild.data;
			timeout=games[i].getElementsByTagName('TIMEOUT').item(0).firstChild.data;
			type=games[i].getElementsByTagName('GAMETYPE').item(0).firstChild.data;
			rated=games[i].getElementsByTagName('RATED').item(0).firstChild.data;
			time_created=games[i].getElementsByTagName('TIMECREATED').item(0).firstChild.data;
			time_ctrl1=games[i].getElementsByTagName('TIMECONTROL1').item(0).firstChild.data;
			time_ctrl2=games[i].getElementsByTagName('TIMECONTROL2').item(0).firstChild.data;
			fen = games[i].getElementsByTagName('GAMEFEN').item(0).firstChild.data;
			
			var data = {desc: desc, gid: gid, status: status, initid: initid, wid: wid, bid: bid, timeout: timeout, type: type, rated: rated, time_created: time_created, time_ctrl1: time_ctrl1, time_ctrl2: time_ctrl2, fen: fen};
			create_game_field(data, true);
		}
			
	}
	
	function getOpen(){
	
		var url='mobile.php?action=openchallenges&sid='+sessionid;
		//new Ajax.Request(url,  {  method: 'get', onSuccess: processOpen, onFailure:getOpen});
		$.get(url, processOpen);
	}
	
	function initDivs(){
		playersdiv=document.getElementById('players');
		cdiv = $('#challenges').get(0); // document.getElementById('challenges');
		cdiv = $(cdiv);
		opendiv = $('#opengames').get(0);  // document.getElementById('opengames');
		opendiv = $(opendiv);
		recentdiv = $('#recentgames');
		getBuddyList();
		getPlayers();
		getChallenges();
		getOpen();
	}
	
	
	function create_game_field(data, is_open)
	{
		var content = '';
		var is_completed = false;

		var classes = "game";
		if(data.status == "IDS_GAME_NOT_ACCEPTED") classes += " not_accepted";
		if(data.status == "IDS_PLAYER_TURN") classes += " player_turn";
		if(data.status == "IDS_NOT_PLAYER_TURN") classes += " not_player_turn";
		if(data.status == "C") is_completed = true;
		
		var challenger = '';
		data.desc = data.desc.split(" ");		// Splits 'Player1 VS Player2' text.
		if(data.desc[0] == user)
			challenger = data.desc[2];
		else if(data.desc[2] == user)
			challenger = data.desc[0];
		else if(is_open)
		{
			challenger = data.desc[0] == '<?php echo _T('IDS_MAIN_GAMEBOX_ANYONE', $config); ?>' ? data.desc[2] : data.desc[0];
		}
		
		var opponent_online = false;
		for(var i = 0; i < players_online.length; i++)
		{
			if(players_online[i] == challenger)
			{
				opponent_online = true;
				break;
			}
		}
		
		var styling = "";
		if(data.initid == 0)
			styling = "background-image: url(<?php echo $Root_Path."skins/".$SkinName."/images/trophy-icon.png"; ?>); background-repeat: no-repeat; background-position: right bottom";
		if(is_open)
			styling += "background-image: url(<?php echo $Root_Path . "skins/" . $SkinName . "/images/rss.png"; ?>); background-repeat: no-repeat; background-position: right bottom; background-color: #f7a526";
		
		content = '<div id="game_' + data.gid + '" class="' + classes + '" style="' + styling + '">';
		styling = 'challenger' + (!opponent_online ? ' opponent_online' : '');
		content += '<div><span class="' + styling + '">' + challenger + '</span><br/>';
		content += '<span><?php echo _T('IDS_MAIN_GAMEBOX_ID', $config); ?> ' + data.gid.substr(0, 4) + '</span><br/>';
		//content += '<span>' + type + '</span><br/>';
		
		var action = '';
		if(data.status == 'IDS_GAME_NOT_ACCEPTED')
		{
			if(data.initid == playerid)
			{
				action += '<a href="javascript:revokeChallenge(\''+data.gid+'\');" ><?php echo _T('IDS_GAME_BTN_REVOKE_CHALLANGE', $config); ?></a>';
			}
			else
			{
				if(is_open)
					action += '<a href="javascript:acceptOpenChallenge(\''+data.gid+'\');" ><?php echo _T('IDS_GAME_BTN_ACCEPT_CHALLANGE', $config); ?></a>';
				else
					action+='<a href="javascript:acceptChallenge(\''+data.gid+'\');" ><?php echo _T('IDS_GAME_BTN_ACCEPT_CHALLANGE', $config); ?></a>';
			}
		}
		else
		{
			if(!is_completed)
				action = '<a href="chess_game_rt.php?gameid='+data.gid+'&pn='+user+'" target="_blank"><?php echo _T('IDS_MAIN_GAMEBOX_PLAY', $config); ?></a><br>';
			if(!is_open)
				action += '<a href="javascript:replayGame(\''+data.gid+'\');"><?php echo _T("IDS_CR3DCQUERY_BTN_REPLAY", $config)?></a>';
		}
		content += '<span class="action">' + action + '</span>';
		
		
		content += '</div>';
		//console.log(content);
		
		if(is_open)
			opendiv.append(content);
		else if(is_completed)
			recentdiv.append(content);
		else
			cdiv.append(content);
			
		$('#game_' + data.gid).hover(
			function(){ show_game_details(data) },
			function(){ hide_game_details(data) } 
		);
			
		return content;
	}
	
	function show_game_details(data)
	{
		var timeout, rated, creation_time, time_ctrl1, time_ctrl2;
		var is_completed = false;
		var is_accepted = true;
		if(data.status == "C") is_completed = true;
		if(data.status == "IDS_GAME_NOT_ACCEPTED") is_accepted = false;

		creation_time = new Date(data.time_created * 1000);
		timeout = data.timeout.substr(4).toLowerCase();
		rated = data.rated == 'true' ? '<?php echo _T('IDS_MAIN_GAMEBOX_HOVER_YES', $config); ?>' : '<?php echo _T('IDS_MAIN_GAMEBOX_HOVER_NO', $config); ?>';
		creation_time = creation_time.toDateString();
		
		if(data.time_ctrl1 == "IDS_NULL")
			time_ctrl1 = "<?php echo _T('IDS_MAIN_GAMEBOX_HOVER_NONE', $config); ?>";
		else
			time_ctrl1 = data.time_ctrl1;
		if(data.time_ctrl2 == "IDS_NULL")
			time_ctrl2 = "<?php echo _T('IDS_MAIN_GAMEBOX_HOVER_NONE', $config); ?>";
		else
			time_ctrl2 = data.time_ctrl2;
	
		var is_touch = false;
		if(('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch) {
			is_touch = true;
		}
	
		var html = '<div id="game_details" class="game_details">';
		html += '<table width="100%">';
		html += '<tr><td></td><td><div class="game_details_close" onclick="hide_game_details(' + data + ')">' + (is_touch ? 'X' : '') + '</div></td></tr>';
		html += '<tr><td><?php echo _T('IDS_MAIN_GAMEBOX_HOVER_RATED', $config); ?></td><td>' + rated + '</td></tr>';
		if(!is_completed)
		{
			html += '<tr><td><?php echo _T('IDS_MAIN_GAMEBOX_HOVER_DATE', $config); ?></td><td>' + creation_time + '</td></tr>';
			if(is_accepted)
			{
				html += '<tr><td><?php echo _T('IDS_MAIN_GAMEBOX_HOVER_TO', $config); ?></td><td><span id="timeleft">' + timeout + '</span></td></tr>';
			}
		}
		//html += '<tr><td><?php echo _T('IDS_MAIN_GAMEBOX_HOVER_TC1', $config); ?></td><td>' + time_ctrl1 + '</td></tr>';
		//html += '<tr><td><?php echo _T('IDS_MAIN_GAMEBOX_HOVER_TC2', $config); ?></td><td>' + time_ctrl2 + '</td></tr>';
		//console.log(data);
		//html += '<tr><td colspan="2" valign="center" text-align="center">' + create_game_board(data.fen, true) + '</td></tr>';
		html += '</table>';
		html += '<div id="board_container">' + create_game_board(data.fen, true) + '</div>';
		html += '</div>';
		var ele = $(html);
		var game_box = $('#game_' + data.gid).get(0);
		var pos = $(game_box).offset();
		$("#temp_elements").append(ele);
		//console.log('pos: ', pos, ' top: ', pos.top - ele.outerHeight() - 1, ele.outerHeight());
		ele.hide(0);
		ele.fadeIn(300);
		ele.css({position: 'absolute', top: pos.top - ele.outerHeight() - 1, left: pos.left - (ele.outerWidth() - $(game_box).outerWidth()) / 2});
		var bc_h = $('#game_details').height();
		var bc_w = $('#game_details').width();
		var b_h = $('.board').height();
		var b_w = $('.board').width();
		//console.log(bc_h, bc_w, b_h, b_w);
		var b_left = Math.floor((bc_w - b_w) / 2);
		var b_top = (bc_h - b_h) / 2;
		//console.log(b_left, b_top);
		$('#board_container').css({position: 'relative', left: b_left, top: 0});
		if(!is_completed)
		{
			timeleft.started = data.time_created;
			if(timeouts[timeout] !== undefined)
			{
				timeleft.duration = timeouts[timeout];
				update_time_remaining();
			}
		}
	}
	
	function hide_game_details(data)
	{
		$('#game_details').remove();
	}
	
	function create_game_board(fen, white_side)
	{
		var colour = white_side ? 0 : 1;
		var segments = fen.split(" ");
		var rows = segments[0].split("/");
		//console.log(rows);
		var html = '<table class="board">';
		if(white_side)
		{
			for(var i = 7; i > -1; i--)
			{
				html += create_game_board_row(rows[i], colour);
				colour = colour ? 0 : 1;
			}
		}
		else
		{
			for(var i = 0; i < 8; i++)
			{
				html += create_game_board_row(rows[i], colour);
				colour = colour ? 0 : 1;
			}
		}
		html += '</table>';
		//console.log(html);
		return html;
	}
	
	function create_game_board_row(row, colour)
	{
		var pieces = row.split('');
		var side_map = {p: 'w', r: 'w', n: 'w', b: 'w', q: 'w', k: 'w', P: 'b', R: 'b', N: 'b', B: 'b', Q: 'b', K: 'b'};
		var img_map = {p: '06.gif', r: '03.gif', n: '05.gif', b: '04.gif', q: '02.gif', k: '01.gif', P: '12.gif', R: '09.gif', N: '11.gif', B: '10.gif', Q: '08.gif', K: '07.gif'};
		var html = '<tr>';
		for(var i = 0; i < pieces.length; i++)
		{
			if(parseInt(pieces[i], 10) > 0 && parseInt(pieces[i], 10) < 9)
			{
				//console.log();
				var l = parseInt(pieces[i], 10);
				for(var c = 0; c < l; c++)
				{
					html += '<td class="tile" style="background-color: ' + (colour ? tile_colour_black : tile_colour_white) + '"><div class="tile"></div></td>';
					colour = colour ? 0 : 1;
				}
			}
			else
			{
				var classes = 'tile'; // + ' piece_' + side_map[pieces[i]] + pieces[i].toLowerCase();
				html += '<td class="tile" style="background-color: ' + (colour ? tile_colour_black : tile_colour_white) + '"><img src="<?php echo $Root_Path."skins/".$SkinName."/images/chess/";?>' + img_map[pieces[i]] + '" class="tile" /></td>';
				colour = colour ? 0 : 1;
			}
		}
		html += '</tr>';
		return html;
	}
	
	function update_time_remaining()
	{
		var now = Math.floor(new Date().getTime() / 1000);
		var remaining = timeleft.duration - (now - timeleft.started);
		//console.log(remaining, self.timeleft, now);
		var text = "";
		var d = 0; var h = 0; var m = 0; var s = 0;
		d = Math.floor(remaining / (3600 * 24));
		h = Math.floor((remaining - d * 3600 * 24) / 3600);
		m = Math.floor((remaining - d * 3600 * 24 - h * 3600) / 60);
		s = Math.floor(remaining - d * 3600 * 24 - h * 3600 - m * 60);

		if(d > 0)
			text = LANG.txt_days_remaining.replace("{d}", d);
		else
		{
			if(h > 0 || m >= 30)
				text = LANG.txt_time_remaining1.replace("{h}", zero_pad(h, 2)).replace("{m}", zero_pad(m, 2));
			else
				text = LANG.txt_time_remaining2.replace("{m}", zero_pad(m, 2)).replace("{s}", zero_pad(s, 2));
		}
		if(remaining <= 0)
			text = LANG.txt_game_timed_out;
			
		$('#timeleft').text(text);
		//setTimeout(update_time_remaining, 1000);
	}
	
	function zero_pad(number, places)
	{
		var value = String(number);
		var add = places - value.length;
		if(add > 0)
		{
			for(var i = 0; i < add; i++)
				value = '0' + value;
		}
		return value;
	}
	
	$(document).ready(function(){
		//alert('hi');
		initDivs();
	});

</script>
<scripto src="modules/RealTimeInterface/scripts/lib/prototype.js" type="text/javascript"></script>
<scripto src="modules/RealTimeInterface/scripts/src/scriptaculous.js" type="text/javascript"></script>

<link rel="stylesheet" href="modules/RealTimeInterface/scripts/realtimehub.css" type="text/css">

