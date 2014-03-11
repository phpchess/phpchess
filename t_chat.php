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

  header("Content-Type: text/html; charset=utf-8");
  ini_set("output_buffering","1");
  session_start();  

  $Root_Path="./";
  $config = $Root_Path."bin/config.php";

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

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/language.php");


  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

  if(isset($_SESSION['sid'])){
    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
    $oR3DCQuery->Close();
    unset($oR3DCQuery);
  }

  $GID = $_GET['gid'];

?>

<html>
<head>
<title>Tournament Game</title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>

<script language="JavaScript">

// Configure refresh interval (in seconds)
var refreshinterval=<?php echo $conf['chat_refresh_rate'];?>

// Shall the coundown be displayed inside your status bar? Say "yes" or "no" below:
var displaycountdown="no"

// Do not edit the code below
var starttime
var nowtime
var reloadseconds=0
var secondssinceloaded=0

function starttime() {
	starttime=new Date()
	starttime=starttime.getTime()
    countdown()
}

function countdown() {
	nowtime= new Date()
	nowtime=nowtime.getTime()
	secondssinceloaded=(nowtime-starttime)/1000
	reloadseconds=Math.round(refreshinterval-secondssinceloaded)
	if (refreshinterval>=secondssinceloaded) {
        var timer=setTimeout("countdown()",1000)
		if (displaycountdown=="yes") {
			window.status="Page refreshing in "+reloadseconds+ " seconds"
		}
    }
    else {
        clearTimeout(timer)
		window.location.reload(true)
    } 
}
window.onload=starttime



<?php
  ////////////
  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  if($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID) && $oR3DCQuery->TimeForTGame($config, $GID)){
    echo "parent.frames['chessboard'].location.reload();";     
  }elseif($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID) == false && $oR3DCQuery->TimeForTGame($config, $GID) == false){
    echo "parent.frames['chessboard'].location.reload();";  
  }elseif($oR3DCQuery->TGameStatus($GID) == false){
    echo "parent.frames['chessboard'].location.reload();"; 
  }
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  ///////////
?>

</script>
<body>

<textarea cols='32' rows='24' class='post'>

<?php
  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $oR3DCQuery->GetTChat($config, $GID);
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>

</textarea>

</body>
</html>