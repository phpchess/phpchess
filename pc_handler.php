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

  $jtime = $_GET['jtime'];

  if(isset($_SESSION['sid'])){
    $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
    $oR3DCQuery->PlayerChatJoinAndMaintainChatStatus($_SESSION['id']);
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

///////////////////////////////////
// Manage the online players list
var aPlayerList = new Array();

<?php
  if(isset($_SESSION['sid'])){
    $oR3DCQuery->PlayerChatTimeOutOfflinePlayers();
    echo $oR3DCQuery->PlayerChatGetOnlinePlayerListJAVA($_SESSION['id']);
  }
?>

try{

  // Update the chat message
  var chatobject = top.frchat1.document.frmChatList.txtchatmsg;
  chatobject.value="<?php echo $oR3DCQuery->PlayerChatGetChatMessages($jtime, true);?>";

  // remove timed out players
  var aDeleteList = new Array();
  var selectObject = top.frmenu1.document.frmPlayerList.lstTPlayerList;

  var i=0;
  var n=0;
  var ncount = selectObject.options.length;

  while(i < ncount){

    var btest=false;
    var ii=0;
    var ncount1 = aPlayerList.length;

    while(ii < ncount1){

      if(aPlayerList[ii] == selectObject.options[i].value){
        btest=true;
      }

      ii++;
    }

    if(btest == false){
      aDeleteList[n]=i;
      n++;
    }

    i++;
  }

  ndeletecount = aDeleteList.length;
  if(ndeletecount > 0){

    iii=0;
    while(iii < ndeletecount){

      selectObject.options[aDeleteList[iii]] = null;

      iii++;

    }

  }

  // Add new players
  var i=0;
  var n=0;
  var ncount = aPlayerList.length;

  while(i < ncount){

    var btest=false;
    var ii=0;
    var ncount1 = selectObject.options.length;

    while(ii < ncount1){

      if(aPlayerList[i] == selectObject.options[ii].value){
        btest=true;
      }

      ii++;
    }

    if(btest == false){

      var optionObject = new Option(aPlayerList[i],aPlayerList[i]);
      var optionRank = selectObject.options.length;
      selectObject.options[optionRank]= optionObject;

      n++;
    }

    i++;
  }


}catch(e){

}
</script>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>