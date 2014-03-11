<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  define('CHECK_PHPCHESS', true);

  $host = $_SERVER['HTTP_HOST'];
  $self = $_SERVER['PHP_SELF'];
  $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
  $url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

  header("Content-Type: text/html; charset=utf-8");
  session_start();
  ob_start();

  $gid = trim($_GET['gameid']);  
  
  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_game_rt.php";  

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////
  
  include ($Root_Path."includes/support_chess.inc"); 
  include ($Root_Path."includes/chess.inc"); 
  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");



  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  // Check For the nonempty SID var
  $sid = trim($_GET['sid']);
  
  // Log the user on or manage the session if a SID is passed to the page
  if($sid != ""){

    $user = "";
    $id = "";
    $oR3DCQuery->ConfirmSID($sid, $user, $id);
 
    if($user != "" && $id != ""){
      $_SESSION['sid'] = $sid;
      $_SESSION['user'] = $user;
      $_SESSION['id'] = $id;

      $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
      
      $_SESSION['lcolor'] = $l;
      $_SESSION['dcolor'] = $d;

      if($oR3DCQuery->IsPlayerDisabled($id) == false){

        $clrl = $_SESSION['lcolor'];
        $clrd = $_SESSION['dcolor'];

        $oR3DCQuery->AddOnlinePlayerToGraphData($_SESSION['user']);
        $oR3DCQuery->UpdateLastLoginInfo($_SESSION['id']);
        $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);

        //Check if the game is accepted
        $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
        $gametypecode = $oR3DCQuery->GetGameTypeCode($gid);
        list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

        if($status == "waiting" || $status == "-"){
          header("Location: ./chess_game.php?gameid=".$gid."");
        }

      }else{
        header('Location: ./chess_logout.php');
      }

    }

  }

  ///////////////////////////////////////////////////////////////////
  //Check if the logged in user has access
  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){
    $_SESSION['PageRef'] = $url;
    header('Location: ./chess_login.php');
  }else{

    $oR3DCQuery->CheckSIDTimeout();

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      $_SESSION['PageRef'] = $url;
      header('Location: ./chess_login.php');
    }else{
      $_SESSION['PageRef'] = "";
      $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
      $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);
    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////
  // Check if the game is not playable by the viewer
  if(!$oR3DCQuery->IsGameControlsViewableByPlayer($gid, $_SESSION['id'])){
    header('Location: ./chess_members.php');
  }

  ///////////////////////////////////////////////////////////////////////
	$initiator = "";
    $w_player_id = "";
    $b_player_id = "";
    $next_move = "";
    $start_time = "";
	
  $oR3DCQuery->GetCurrentGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $next_move, $start_time);

  $image = $oR3DCQuery->GetAvatarImageName($w_player_id);

  if($image != ""){
	$image="<img src='./avatars/".$image."'>";
  }else{
	$image="<img src='./avatars/noimage.jpg'>";
  }
  
  $userid = $oR3DCQuery->GetUserIDByPlayerID($config, $w_player_id);
  
  $wins = 0;
  $loss = 0;
  $draws = 0;

  $oR3DCQuery->GetPlayerStatusrRefByPlayerID($config, $w_player_id, $wins, $loss, $draws);

  if($oR3DCQuery->ELOIsActive()){
	$points = $oR3DCQuery->ELOGetRating($w_player_id);
  }else{
	$points = $oR3DCQuery->GetPointValue($wins, $loss, $draws);
  }
  
  $whitediv="
	<div class=\"avatar\">$image</div>
	<div class=\"userid\">$userid</div>
	<div class=\"points\">$points</div>
	";
  
  $image = $oR3DCQuery->GetAvatarImageName($b_player_id);

  if($image != ""){
	$image="<img src='./avatars/".$image."'>";
  }else{
	$image="<img src='./avatars/noimage.jpg'>";
  }
  
  $userid = $oR3DCQuery->GetUserIDByPlayerID($config, $b_player_id);
  
  $wins = 0;
  $loss = 0;
  $draws = 0;

  $oR3DCQuery->GetPlayerStatusrRefByPlayerID($config, $b_player_id, $wins, $loss, $draws);

  if($oR3DCQuery->ELOIsActive()){
	$points = $oR3DCQuery->ELOGetRating($b_player_id);
  }else{
	$points = $oR3DCQuery->GetPointValue($wins, $loss, $draws);
  }
  
  $blackdiv="
	<div class=\"avatar\">$image</div>
	<div class=\"userid\">$userid</div>
	<div class=\"points\">$points</div>
	";
  
  
	$player='white';
	$bdiv=$whitediv;
	$tdiv=$blackdiv;
	$imgc='w';
  
  if($gameid != ""){

    // Get the pgn text
    //Instantiate the CR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $fen = $oR3DCQuery->FormatInputedFEN2($oR3DCQuery->GetInitialGameFEN($config, $gameid)); //replaceLast($oR3DCQuery->GetHackedFEN($sid, $gameid), "0", "1");
    $oR3DCQuery->Close();
    unset($oR3DCQuery);

  }else{
    $fen = "rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1";
  }
  
  $fen=explode(" ", $fen);
  $board=explode("/", $fen[0]);
  
  $initiator = "";
  $w_player_id = "";
  $b_player_id = "";
  $status = "";
  $completion_status = "";
  $start_time = "";
  $next_move = "";

  $oR3DCQuery->GetGameInfoByRef($config, $gid, $initiator, $w_player_id, $b_player_id, $status, $completion_status, $start_time, $next_move);

	$turn="''";
  
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_23", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script type="text/javascript" src="modules/RealTimeInterface/scripts/realtime.class.js" >
	
</script>

<script type="text/javascript" >

var movelist, nmove=0;

var currentmove=0, play=false, reverse=false, ready=false;

function Move(xb, yb, xe, ye, d, p, np){
	this.xstart=xb;
	this.ystart=yb;
	this.xend=xe;
	this.yend=ye;
	this.destroy=d;
	this.promote=p;
	this.enpas=np;
}

function processMove(){
	if(!ready || currentmove>=nmove){
		return;
	}
	m=movelist[currentmove];
	if(m.destroy==false){
		if(cells[m.xend*8+m.yend]!=0){
			m.destroy=cells[m.xend*8+m.yend];
		}
		if(m.enpas){
			if(m.yend>4){
				m.destroy=cells[m.xend*8+m.yend-1];		
			}else{
				m.destroy=cells[m.xend*8+m.yend+1];	
			}
		}
	}
	if(m.destroy!=false){
		m.destroy.div.style.visibility='hidden';
	}
	cells[8*m.xstart+m.ystart].setLocation(m.xend, m.yend);
	
	if(cells[m.xend*8+m.yend].type==king && Math.abs(m.xend-m.xstart)==2){
		var xe, xb;
		xe=Math.floor((m.xstart+m.xend)/2);
		if(xe>4){
			xb=7;
		}else{
			xb=0;
		}
		cells[xb*8+m.yend].setLocation(xe, m.yend);						
	}
	if(m.promote!=false){
		var color=cells[8*m.xend+m.yend].color;
		divBoard.removeChild(cells[8*m.xend+m.yend].div);
		cells[8*m.xend+m.yend] = new Piece(divBoard, m.promote, m.xend, m.yend+1, color);
	}
	currentmove++;
}

function MoveForward(t){
	var i;
	for(i=0; i<t; i++){
		processMove();
	}
}

function MoveToEnd(){
	var i;
	while(currentmove<nmove){
		processMove();
	}
}

function reverseMove(){
	if(!ready || currentmove<1){
		return;
	}
	currentmove--;
	m=movelist[currentmove];
	if(cells[m.xend*8+m.yend].type==king && Math.abs(m.xend-m.xstart)==2){
		var xe, xb;
		xe=Math.floor((m.xstart+m.xend)/2);
		if(xe>4){
			xb=7;
		}else{
			xb=0;
		}
		cells[xe*8+m.yend].setLocation(xb, m.yend);						
	}
	cells[8*m.xend+m.yend].setLocation(m.xstart, m.ystart);
	
	if(m.promote!=false){
		var color=cells[8*m.xstart+m.ystart].color;
		divBoard.removeChild(cells[8*m.xstart+m.ystart].div);
		cells[8*m.xstart+m.ystart].destroy();
		cells[8*m.xstart+m.ystart] = new Piece(divBoard, pawn, m.xstart, m.ystart+1, color);
	}
	
	if(m.destroy!=false){
		m.destroy.div.style.visibility='visible';
		cells[m.xend*8+m.yend]=m.destroy;
		m.destroy.x=m.xend;
		m.destroy.y=m.yend;
	}
}

function MoveBack(t){
	var i;
	for(i=0; i<t; i++){
		reverseMove();
	}
}

function MoveToStart(){
	var i;
	while(currentmove>0){
		reverseMove();
	}
}

function playMoves(){
	if(!ready){
		return;
	}
	if(reverse){
		reverseMove();
	}else{
		processMove();
	}
	if(play && currentmove>=0 && currentmove<nmove){
		window.setTimeout('playMoves()', 1*document.getElementById('Delay').value);
	}else{
		document.getElementById('btnPlay').value='Play';
		play=false;
	}
}

function SwitchAutoPlay(){
	if(play){
		document.getElementById('btnPlay').value='Play';
		play=false;
	}else{
		document.getElementById('btnPlay').value='Stop';
		play=true;
		playMoves();
	}
}

function processMovelist(transport){

	movelist=new Array();
	var moves, move, i;
	var xb, yb, xe, ye, xp, yp, np, d=false, p=false;
	moves=transport.responseXML.getElementsByTagName('MOVE');
	for(i=0; i<moves.length; i++){
		move=moves.item(i).getElementsByTagName('MOVECOMMA').item(0).firstChild.data;
		if(move.length>4){
			d=false, p=false, np=false;
			xb=getIndex(move.charAt(0));
			xe=getIndex(move.charAt(3));
			yb=parseInt(move.charAt(1))-1;
			ye=parseInt(move.charAt(4))-1;
			if(player==black){
				xb=7-xb;
				xe=7-xe;
				yb=7-yb;
				ye=7-ye;
			}
			if(move.length>6){
				xp=parseInt(move.charAt(7));
				yp=parseInt(move.charAt(6));	
				if(player==black){
					xp=7-xp;
					yp=7-yp;
				}
				if(Math.abs(yp-ye)==1){
					np=true;
				}
			}
			if(move.length==6){
				switch(move.charAt(5)){
					case 'Q':
						p=queen;
						break;
					case 'R':
						p=rook;
						break;
					case 'B':
						p=bishop;
						break;
					case 'N':
						p=knight;
						break;
				}		
			}
			movelist[i]=new Move(xb, yb, xe, ye, d, p, np);
		}
	}
	nmove=i;
	ready=true;
}

function initDivs(){
	var i, j;
	sessionid='<?=$_SESSION['sid']?>';
	gameid='<?=$gid?>';
	initBoard(<?=$player?>, <?=$turn?>);
	divWrapper=document.getElementById('wrapper');
	divBoard=document.getElementById('board');
	cells=new Array();
	for(i=0; i<64; i++){
		cells[i]=0;
	}
	
	pieces=new Array();
	pieces[0]=new Array();
	pieces[1]=new Array();
	
	<?
	$k=0;
	$m=0;
	$rows=array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h');
	for($i=0; $i<8; $i++){
		$j=0;
		for($n=0; $n<strlen($board[$i]); $n++){
		
			switch($board[$i][$n]){
				case 'r':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, rook, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'n':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, knight, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'b':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, bishop, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'q':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, queen, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'k':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, king, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'p':
				?>
	pieces[1][<?=$k?>]=new Piece(divBoard, pawn, <?=$rows[$j]?>, r<?=(8-$i)?>, black);
				<?
					$k++;
					break;
				case 'R':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, rook, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				case 'N':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, knight, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				case 'B':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, bishop, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				case 'Q':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, queen, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				case 'K':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, king, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				case 'P':
				?>
	pieces[0][<?=$m?>]=new Piece(divBoard, pawn, <?=$rows[$j]?>, r<?=(8-$i)?>, white);
				<?
					$m++;
					break;
				default:
					if($board[$i][$n]>0){
						$j+=$board[$i][$n]-1;
					}
					break;
			}
			$j++;
		}
	}
	?>
		
	findLocation(divBoard);
	
	var url='mobile.php?action=getallmoves&sid='+sessionid+'&gameid='+gameid;
	new Ajax.Request(url,  {  method: 'get', onSuccess: function(transport){
			processMovelist(transport);
		}, 
		onFailure:function(){
			document.getElementById('warning').style.visibility='visible';
		}
	});
	
}

</script>
<script src="modules/RealTimeInterface/scripts/lib/prototype.js" type="text/javascript"></script>
<script src="modules/RealTimeInterface/scripts/src/scriptaculous.js" type="text/javascript"></script>

<link rel="stylesheet" href="modules/RealTimeInterface/scripts/realtime.css" type="text/css">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">

</head>
<body onload="initDivs();" >
<div id="wrapper" style="height:440px; position:static;">
	<div id="boardleft"> <img src="modules/RealTimeInterface/images/12345678<?=$player?>.png" height="440" /> </div>
	<div id="boardright"> <img src="modules/RealTimeInterface/images/12345678<?=$player?>.png" height="440" /> </div>
	<div id="boardtop"> <img src="modules/RealTimeInterface/images/abcdefgh<?=$player?>.png" width="440" /> </div>
	<div id="boardbottom"> <img src="modules/RealTimeInterface/images/abcdefgh<?=$player?>.png" width="440" /> </div>
	<div id="board"><img src="modules/RealTimeInterface/images/boardscalable.png" />
	</div>
	<div id="boardblock" style="filter:alpha(opacity=0);-moz-opacity:0;opacity:0;">
	</div>
	<div id="topdiv"><?=$tdiv?></div>
	<div id="bottomdiv"><?=$bdiv?></div>
	<div id="pgnview">
	</div>
</div>
<div id="playcontrols" style="margin-left:40px;">
<table noborder="" cellpadding="1" cellspacing="0">
<tbody>
	<tr>
		<td><input class="mainoption" value="I&lt;" style="width: 24px;" id="btnInit" onclick="javascript:MoveToStart()" type="button" width="20"></td>
		<td><input class="mainoption" value="&lt;&lt;" style="width: 24px;" id="btnMB10" onclick="javascript:MoveBack(10)" type="button" width="20"></td>
		<td><input class="mainoption" value="&lt;" style="width: 24px;" id="btnMB1" onclick="javascript:MoveBack(1)" type="button" width="20"></td>
		<td><input class="mainoption" value="&gt;" style="width: 24px;" id="btnMF1" onclick="javascript:MoveForward(1)" type="button" width="20"></td>
		<td><input class="mainoption" value="&gt;&gt;" style="width: 24px;" id="btnMF10" onclick="javascript:MoveForward(10)" type="button" width="20"></td>
		<td><input class="mainoption" value="&gt;I" style="width: 24px;" id="btnMF1000" onclick="javascript:MoveToEnd()" type="button" width="20"></td>
		<td><input class="mainoption" value="Play" style="width: 41px;" id="btnPlay" name="AutoPlay" onclick="javascript:SwitchAutoPlay()" type="button" width="40"></td>
		<td>
			<select id="Delay" size="1">
				<option value="1000"><?=GetStringFromStringTable("IDS_RT_TXT_FAST", $config)?></option>
				<option value="2000"><?=GetStringFromStringTable("IDS_RT_TXT_MED", $config)?></option>
				<option value="3000"><?=GetStringFromStringTable("IDS_RT_TXT_SLOW", $config)?></option>
			</select>
		</td>
	</tr>
</tbody>
</table>

</div>
<div id="warning" style="color:red; visibility:hidden; position:absolute; top:500px;" ><?=GetStringFromStringTable("IDS_RT_TXT_PROBLEM", $config)?></div>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>