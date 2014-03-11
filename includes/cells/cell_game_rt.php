
<!-- Clock White -->
<script language="JavaScript">
<?php

//get the time data for this game
$awhite = $oR3DCQuery->GetPlayerTimeRT($gid, "white");
$ablack = $oR3DCQuery->GetPlayerTimeRT($gid, "black");
?>

function PopupWindow(webpage)
{
	var url = webpage;
	var hWnd = window.open(url,"PHPChess","width=500,height=400,resizable=no,scrollbars=yes,status=yes");
	if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name="home"; hWnd.location.href=url; }}
} 

var sec1 = <?php echo $awhite[2];?>;
var min1 = <?php echo $awhite[1];?>;
var hour1 = <?php echo $awhite[0];?>;

function stopwatchwhite(text) {
  sec1++;
  if(sec1 == 60){
    sec1 = 0;
    min1 = min1 + 1; 
  }else{
    min1 = min1; 
  }
  if(min1 == 60){
    min1 = 0; 
    hour1 += 1;
  }
  if(sec1<=9){
    sec1 = "0" + sec1;
  }
  document.getElementById('whiteclock').innerHTML = ((hour1<=9) ? "0"+hour1 : hour1) + " : " + ((min1<=9) ? "0" + min1 : min1) + " : " + sec1;
  SD=window.setTimeout("stopwatchwhite();", 1000);
}
</script>

<!-- Clock Black -->
<script language="JavaScript">
var sec2 = <?php echo $ablack[2];?>;
var min2 = <?php echo $ablack[1];?>;
var hour2 = <?php echo $ablack[0];?>;

function stopwatchblack(text) {
  sec2++;
  if(sec2 == 60){
    sec2 = 0;
    min2 = min2 + 1; 
  }else{
    min2 = min2; 
  }
  if(min2 == 60){
    min2 = 0; 
    hour2 += 1;
  }
  if(sec2<=9){
    sec2 = "0" + sec2;
  }
  document.getElementById('blackclock').innerHTML = ((hour2<=9) ? "0"+hour2 : hour2) + " : " + ((min2<=9) ? "0" + min2 : min2) + " : " + sec2;
  SD=window.setTimeout("stopwatchblack();", 1000);
}
</script>
<div id="game_status_container">
	<div id="game_draw_requests" style="display: none"></div>
	<div id="game_status_message"></div>
	<div id="game_finished_options">
		<button class="mainoption" onclick='window.location.href="./chess_members.php"'>
			<?php echo __l('Back to Player Home') ?>
		</button>
		<button class="mainoption" onclick="window.open('pgnviewer/view_pgn_game.php?gameid=<?php echo $gid; ?>', 'pgn','location=0, status=0, scrollbars=0, menubar=0,width=600, height=500')">
			<?php echo __l('View Replay') ?>
		</button>
		<button class="mainoption" onclick="location.href='chess_create_game_ar.php?othpid=<?php echo ($_SESSION['id'] == $w_player_id ? $b_player_id : $w_player_id); ?>'">
			<?php echo __l('Challenge to Rematch') ?>
		</button>
	</div>
</div>
<div id="wrapper" class="wrapper">
	<div id="_chess_board_container"></div>
	<div id="topdiv"><?php echo$tdiv?></div>
	<div id="bottomdiv"><?php echo $bdiv?></div>
	<div id="taken_pieces">
		<p><?php echo __l('White has captured:') ?></p>
		<div id="taken_pieces_white"></div>
		<p><?php echo __l('Black has captured:') ?></p>
		<div id="taken_pieces_black"></div>
	</div>
	<div id="chatbox" class="chatbox">
	</div>
	<div id="sendmsg" >
	<input type="textbox" id="sendbox" class="sendbox" __onkeypress="{if (event.keyCode==13)sendGameMsg('sendbox');}" />
	
	<input type="button" id="sendmsgbtn" class="mainoption" value="<?php echo __l('Send Message')?>" />
	
	</div>
	<div id="controls">
	<div id="draw"><input type="button" class="mainoption" value="<?php echo __l('Draw')?>"/></div>
	<div id="resign"><input type="button" class="mainoption" value="<?php echo __l('Resign')?>"/></div>
	</div>
	<div id="pgnview">
		<div>
			<input class="mainoption" style="border: 1px solid #8FAE75" type="button" onclick="javascript:PopupWindow('./view_PGN.php?gid=<?php echo $gid; ?>')" value="PGN" name="btnSavePGN"></div>
		<div id="pgntext">
		
		</div>
	</div>
	<div id="clock_w">W CLOCK</div>
	<div id="clock_b">B CLOCK</div>
	
	<div id="test" style="position: absolute; left: 750px; top: 200px; display: none">
		<button id='w' class="mainoption">W</button>
		<button id='l' class="mainoption">L</button>
		<button id='d' class="mainoption">D</button>
	</div>
	<div id="effects"></div>
</div>

<div id="server" style="position:absolute; top:630px;" ></div>