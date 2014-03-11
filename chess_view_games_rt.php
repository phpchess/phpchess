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
  
  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_view_games_rt.php";  

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
  
  ///////////////////////////////////////////////////////////////////
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
	}else{
		header('Location: ./chess_logout.php');
	}


  }
  ///////////////////////////////////////////////////////////////////


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
      $oR3DCQuery->UpdateSIDTimeout($config, $_SESSION['sid']);
      $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);
    }

  }
  ///////////////////////////////////////////////////////////////////////
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

<script type="text/javascript" >
	
	//set the session id, player id and the user
   
    var sessionid="<?php echo $_SESSION['sid'];?>", playerid="<?php echo $_SESSION['id'];?>", user="<?php echo $_SESSION['user'];?>"

	//variables to hold commonly used layers
	var playersdiv, cdiv, opendiv, details=new Object, clickcheck=new Object;
	
	//browser information and a variable that holds the player to be challenged
	var ie=false, oplayerid=0;
	
	if(navigator.appName.indexOf('Microsoft')>-1){
		ie=true;
	}
	
	//open a popup that replays the game
	function replayGame(gid){
		window.open ("view_pgn_rt.php?gameid="+gid, "pgn","location=0,status=0,scrollbars=0,menubar=0,width=600,height=500"); 
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
	
	function processChallenge(transport){
		//alert(transport.responseText);
		cancelChallenge();
	}
	
	function challenge(){
		var mypiececolor, ratingtype, gametime;             
		if(oplayerid!='0'){
			hideDetails(oplayerid);
		}
		document.getElementById('challenge').style.visibility='hidden';
		document.getElementById('challenge').style.display='none';
		mypiececolor=document.getElementById('mypiececolor').value;
		ratingtype=document.getElementById('ratingtype').value;
		gametime=document.getElementById('gametime').value;
		var url='mobile.php?action=creategame&sid='+sessionid+'&precreate=0&mypiececolor='+mypiececolor+'&oplayerid='+oplayerid+'&brtGame=1&gametime='+gametime+'&ratingtype='+ratingtype;
		//alert(url);
		new Ajax.Request(url,  {  method: 'get', onSuccess: processChallenge, onFailure:challenge});
	}
	
	function cancelChallenge(){
		document.body.style.overflow="auto";
		if(oplayerid!='0'){
			hideDetails(oplayerid);
		}
		oplayerid=0;
		document.getElementById('challengewrapper').style.visibility='hidden';
		document.getElementById('challengewrapper').style.display='none';
		document.getElementById('challenge').style.visibility='hidden';
		document.getElementById('challenge').style.display='none';
	}
	
	function promptChallenge(pid){
		oplayerid=pid;
		document.getElementById('challengewrapper').style.visibility='visible';
		document.getElementById('challengewrapper').style.display='block';
		document.getElementById('challenge').style.visibility='visible';
		document.getElementById('challenge').style.display='block';
		document.body.style.overflow="hidden";
		if(ie){
			document.getElementById('challengewrapper').style.top=document.body.scrollTop;
			document.getElementById('challengewrapper').style.left=document.body.scrollLeft;
			document.getElementById('challengewrapper').style.width=document.body.clientWidth;
			document.getElementById('challengewrapper').style.height=document.body.clientHeight;
			document.getElementById('challenge').style.top=document.body.scrollTop+Math.floor(document.body.clientHeight/2-150);
			document.getElementById('challenge').style.left=document.body.scrollLeft+Math.floor(document.body.clientWidth/2-150);
		}else{
			document.getElementById('challengewrapper').style.top=window.pageYOffset+'px';
			document.getElementById('challengewrapper').style.left=window.pageXOffset+'px';
			document.getElementById('challengewrapper').style.width=window.innerWidth+'px';
			document.getElementById('challengewrapper').style.height=window.innerHeight+'px';
			document.getElementById('challenge').style.top=window.pageYOffset+Math.floor(window.innerHeight/2-150)+'px';
			document.getElementById('challenge').style.left=window.pageXOffset+Math.floor(window.innerWidth/2-150)+'px';
		}
	}
	
	function processDetails(transport, pid){
		var win, loss, draw, points, detailsdiv;
		detailsdiv=document.createElement('div');
		detailsdiv.className='details';
		detailsdiv.style.position='absolute';
		win=transport.responseXML.getElementsByTagName('WIN').item(0).firstChild.data;
		loss=transport.responseXML.getElementsByTagName('LOSS').item(0).firstChild.data;
		draw=transport.responseXML.getElementsByTagName('DRAW').item(0).firstChild.data;
		points=transport.responseXML.getElementsByTagName('POINTS').item(0).firstChild.data;
		detailsdiv.id='playerdetails'+pid;
		detailsdiv.innerHTML='<a href="javascript:hideDetails(\''+pid+'\');"><img src="modules/RealTimeInterface/images/close.png" width="10" style="float:right; border:none; position:absolute; right:3px; top:3px;" /></a><div><div style="float:left">Won:</div> <div style="float:right">'+win+'</div></div><div style="clear:both"><div style="float:left">Lost: </div><div style="float:right">'+loss+'</div></div><div style="clear:both"><div style="float:left">Draw: </div><div style="float:right">'+draw+'</div></div><div style="clear:both"><div style="float:left">Points: </div><div style="float:right">'+points;
		if(pid!=playerid){
			detailsdiv.innerHTML+='<br/><a href="javascript:promptChallenge(\''+pid+'\');" >Challenge</a>';
		}
		document.body.appendChild(detailsdiv);
		setLocation(document.getElementById('player'+pid), detailsdiv);
		document.getElementById('challengewrapper').style.visibility='visible';
		document.getElementById('challengewrapper').style.display='block';
		document.body.style.overflow="hidden";
		if(ie){
			document.getElementById('challengewrapper').style.top=document.body.scrollTop;
			document.getElementById('challengewrapper').style.left=document.body.scrollLeft;
			document.getElementById('challengewrapper').style.width=document.body.clientWidth;
			document.getElementById('challengewrapper').style.height=document.body.clientHeight;
		}else{
			document.getElementById('challengewrapper').style.top=window.pageYOffset+'px';
			document.getElementById('challengewrapper').style.left=window.pageXOffset+'px';
			document.getElementById('challengewrapper').style.width=window.innerWidth+'px';
			document.getElementById('challengewrapper').style.height=window.innerHeight+'px';
		}
		details[pid]=true;
	}
	
	function showDetails(pid){
		if(clickcheck[pid]==true){
			return;
		}
		if(details[pid]==true){
			document.getElementById('playerdetails'+pid).style.visibility='visible';
			document.getElementById('playerdetails'+pid).style.display='block';
			document.getElementById('challengewrapper').style.visibility='visible';
			document.getElementById('challengewrapper').style.display='block';
			document.body.style.overflow="hidden";
			if(ie){
				document.getElementById('challengewrapper').style.top=document.body.scrollTop;
				document.getElementById('challengewrapper').style.left=document.body.scrollLeft;
				document.getElementById('challengewrapper').style.width=document.body.clientWidth;
				document.getElementById('challengewrapper').style.height=document.body.clientHeight;
			}else{
				document.getElementById('challengewrapper').style.top=window.pageYOffset+'px';
				document.getElementById('challengewrapper').style.left=window.pageXOffset+'px';
				document.getElementById('challengewrapper').style.width=window.innerWidth+'px';
				document.getElementById('challengewrapper').style.height=window.innerHeight+'px';
			}
			setLocation(document.getElementById('player'+pid), document.getElementById('playerdetails'+pid));
		}else{
			clickcheck[pid]=true;
			var url='mobile.php?action=playerstats&sid='+sessionid+'&playerid='+pid;
			new Ajax.Request(url,  {  method: 'get', onSuccess: function(transport){
				clickcheck[pid]=false;
				processDetails(transport, pid);
			}, onFailure:function(){
				clickcheck[pid]=false;
				showDetails(pid);
			}
			});
		}
	}
	
	function hideDetails(pid){
		document.getElementById('playerdetails'+pid).style.visibility='hidden';
		document.getElementById('playerdetails'+pid).style.display='none';
		document.getElementById('challengewrapper').style.visibility='hidden';
		document.getElementById('challengewrapper').style.display='none';
		document.body.style.overflow="auto";
	}
	
	function processPlayers(transport){
		window.setTimeout('getPlayers()', 3000);
		
		var i, playerlist=transport.responseXML.getElementsByTagName('PLAYERS'), content='', uid, pid;
		
		for(i=0; i<playerlist.length; i++){
			uid=playerlist.item(i).getElementsByTagName('USERID').item(0).firstChild.data;
			pid=playerlist.item(i).getElementsByTagName('PLAYERID').item(0).firstChild.data;
			content=content+'<div id="player'+pid+'" style="float:left; margin:20px; width:100px;" ><a href="javascript:showDetails(\''+pid+'\')">'+uid+'</a></div>';
		}
		
		playersdiv.innerHTML=content;		
	}
	
	function getPlayers(){
		
		var url='mobile.php?action=playersonline&sid='+sessionid;
		new Ajax.Request(url,  {  method: 'get', onSuccess: processPlayers, onFailure:getPlayers});
	}
	
	function acceptChallenge(gid){
		
		var url='mobile.php?action=acceptgame&sid='+sessionid+'&gameid='+gid;
		new Ajax.Request(url,  {  method: 'get', onFailure:function(){
				acceptChallenge(gid);
			}
		});
	}
	
	function acceptOpenChallenge(gid){
		
		var url='mobile.php?action=acceptopenchallange&sid='+sessionid+'&gameid='+gid;
		new Ajax.Request(url,  {  method: 'get', onFailure:function(){
				acceptChallenge(gid);
			}
		});
	}
	
	function revokeChallenge(gid){
		
		var url='mobile.php?action=revokegame&sid='+sessionid+'&gameid='+gid;
		new Ajax.Request(url,  {  method: 'get', onFailure:function(){
				revokeChallenge(gid);
			}
		});
	}
	
	function processChallenges(transport){
		window.setTimeout('getChallenges()', 3000);
		//alert(transport.responseText);
		
		var i, playerlist=transport.responseXML.getElementsByTagName('GAMES'), content='', desc, gid, wid, bid, initid, status, color, timeout, type;
		
		for(i=0; i<playerlist.length; i++){
			desc=playerlist.item(i).getElementsByTagName('DESCRIPTION').item(0).firstChild.data;
			gid=playerlist.item(i).getElementsByTagName('GAMEID').item(0).firstChild.data;
			status=playerlist.item(i).getElementsByTagName('STATUS').item(0).firstChild.data;
			initid=playerlist.item(i).getElementsByTagName('INITIATOR').item(0).firstChild.data;
			wid=playerlist.item(i).getElementsByTagName('WHITE').item(0).firstChild.data;
			bid=playerlist.item(i).getElementsByTagName('BLACK').item(0).firstChild.data;
			timeout=playerlist.item(i).getElementsByTagName('TIMEOUT').item(0).firstChild.data;
			type=playerlist.item(i).getElementsByTagName('GAMETYPE').item(0).firstChild.data;
			if(wid==playerid){
				color='#FFFFFF';
			}else if(bid==playerid){
				color='#000000';
			}else if(wid==0){
				color='#FFFFFF';
			}else{
				color='#000000';
			}
			switch(type){
				case 'GT_PASV_RT_GAME':
					type='Passive RT';
					break;
				case 'GT_ACTIVE_RT_GAME':
					type='Passive RT';
					break;
			}
			switch(timeout){
				case 'IDS_NORMAL':
					timeout='NORMAL';
					break;
				case 'IDS_ACTIVE_REALTIME_CONTROLS':
					timeout='REALTIME';
					break;
			}
			switch(status){
				case 'IDS_GAME_NOT_ACCEPTED':
					if(initid==playerid){
						content+='<div class="game">'+desc+'<div><a href="javascript:revokeChallenge(\''+gid+'\');" >Revoke Challenge</a></div>';
					}else{
						content+='<div class="game">'+desc+'<div><a href="javascript:acceptChallenge(\''+gid+'\');" >Accept Challenge</a></div>';
					}
					break;
				case 'IDS_PLAYER_TURN':
					content+='<div class="game" style="background:#d7e8ca;"><a href="chess_game_rt.php?gameid='+gid+'&pn='+user+'" target="_blank">'+desc+'</a><br><a href="javascript:replayGame(\''+gid+'\');"><?=GetStringFromStringTable("IDS_CR3DCQUERY_BTN_REPLAY", $config)?></a>';
					break;
				case 'IDS_NOT_PLAYER_TURN':
					content+='<div class="game" style="background:#5ea4b3;"><a href="chess_game_rt.php?gameid='+gid+'&pn='+user+'" target="_blank">'+desc+'</a><br><a href="javascript:replayGame(\''+gid+'\');"><?=GetStringFromStringTable("IDS_CR3DCQUERY_BTN_REPLAY", $config)?></a>';
					break;
				default:
					content+='<div class="game">'+desc;
					break;
			}
			content+='<div><div style="float:left" >Your Color:</div><div style="width:40px; height:15px; float:right; background:'+color+';"></div></div>';
			content+='<div style="clear:both"><div style="float:left" >Type:</div><div style="float:right">'+type+'</div> </div>';
			content+='<div style="clear:both"><div style="float:left" >Timeout:</div><div style="float:right">'+timeout+'</div> </div>';
			content+='</div>';
		}
		
		cdiv.innerHTML=content;		
	}
	
	function getChallenges(){
	
		var url='mobile.php?action=gamelist&sid='+sessionid;
		new Ajax.Request(url,  {  method: 'get', onSuccess: processChallenges, onFailure:getChallenges});
		
	}
	
	function processOpen(transport){
		window.setTimeout('getOpen()', 3000);
		
		var i, playerlist=transport.responseXML.getElementsByTagName('GAMES'), content='', desc, gid, wid, bid, initid, status, color, timeout, type;
		
		for(i=0; i<playerlist.length; i++){
			desc=playerlist.item(i).getElementsByTagName('DESCRIPTION').item(0).firstChild.data;
			gid=playerlist.item(i).getElementsByTagName('GAMEID').item(0).firstChild.data;
			status=playerlist.item(i).getElementsByTagName('STATUS').item(0).firstChild.data;
			initid=playerlist.item(i).getElementsByTagName('INITIATOR').item(0).firstChild.data;
			wid=playerlist.item(i).getElementsByTagName('WHITE').item(0).firstChild.data;
			bid=playerlist.item(i).getElementsByTagName('BLACK').item(0).firstChild.data;
			timeout=playerlist.item(i).getElementsByTagName('TIMEOUT').item(0).firstChild.data;
			type=playerlist.item(i).getElementsByTagName('GAMETYPE').item(0).firstChild.data;
			if(wid==playerid){
				color='#FFFFFF';
			}else if(bid==playerid){
				color='#000000';
			}else if(wid==0){
				color='#FFFFFF';
			}else{
				color='#000000';
			}
			switch(type){
				case 'GT_PASV_RT_GAME':
					type='Passive RT';
					break;
				case 'GT_ACTIVE_RT_GAME':
					type='Passive RT';
					break;
			}
			switch(timeout){
				case 'IDS_NORMAL':
					timeout='NORMAL';
					break;
				case 'IDS_ACTIVE_REALTIME_CONTROLS':
					timeout='REALTIME';
					break;
			}
			switch(status){
				case 'IDS_GAME_NOT_ACCEPTED':
					if(initid==playerid){
						content+='<div class="game">'+desc+'<div><a href="javascript:revokeChallenge(\''+gid+'\');" >Revoke Challenge</a></div>';
					}else{
						content+='<div class="game">'+desc+'<div><a href="javascript:acceptOpenChallenge(\''+gid+'\');" >Accept Challenge</a></div>';
					}
					break;
				case 'IDS_PLAYER_TURN':
					content+='<div class="game" style="background:#d7e8ca;"><a href="chess_game_rt.php?gameid='+gid+'&pn='+user+'" target="_blank">'+desc+'</a>';
					break;
				case 'IDS_NOT_PLAYER_TURN':
					content+='<div class="game" style="background:#5ea4b3;"><a href="chess_game_rt.php?gameid='+gid+'&pn='+user+'" target="_blank">'+desc+'</a>';
					break;
				default:
					content+='<div class="game">'+desc;
					break;
			}
			content+='<div><div style="float:left" >Your Color:</div><div style="width:40px; height:15px; float:right; background:'+color+';"></div></div>';
			content+='<div style="clear:both"><div style="float:left" >Type:</div><div style="float:right">'+type+'</div> </div>';
			content+='<div style="clear:both"><div style="float:left" >Timeout:</div><div style="float:right">'+timeout+'</div> </div>';
			content+='</div>';
		}
		
		opendiv.innerHTML=content;		
	}
	
	function getOpen(){
	
		var url='mobile.php?action=openchallenges&sid='+sessionid;
		new Ajax.Request(url,  {  method: 'get', onSuccess: processOpen, onFailure:getOpen});
	}
	
	function initDivs(){
		playersdiv=document.getElementById('players');
		cdiv=document.getElementById('challenges');
		opendiv=document.getElementById('opengames');
		getPlayers();
		getChallenges();
		getOpen();
	}

</script>
<script src="modules/RealTimeInterface/scripts/lib/prototype.js" type="text/javascript"></script>
<script src="modules/RealTimeInterface/scripts/src/scriptaculous.js" type="text/javascript"></script>

<link rel="stylesheet" href="modules/RealTimeInterface/scripts/realtimehub.css" type="text/css">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">

</head>
<body onload="initDivs();" >

<?php include("./skins/".$SkinName."/layout_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>