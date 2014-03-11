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

  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  $tgc = $_GET['tgc'];
  $tid = $_GET['tid'];
  $type = $_GET['type'];
  $tzn = $_GET['tzn'];


  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<script language="JavaScript">

var refreshinterval=<?php echo $conf['chat_refresh_rate'];?>;
var starttime;
var nowtime;
var reloadseconds=0;
var secondssinceloaded=0;


function starttime(){

  starttime=new Date();
  starttime=starttime.getTime();
  countdown();

}


function countdown(){

  nowtime= new Date();
  nowtime=nowtime.getTime();
  secondssinceloaded=(nowtime-starttime)/1000;
  reloadseconds=Math.round(refreshinterval-secondssinceloaded);

  if(refreshinterval>=secondssinceloaded){
    var timer=setTimeout("countdown()",1000);
  }else{
    clearTimeout(timer);
    window.location.reload(true);
  } 

}

window.onload=starttime;

</script>

<?php $oR3DCQuery->v2GenerateSpectatorChessboardHTML($tgc, $pid, $type, $tid, $clrl, $clrd);?>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>