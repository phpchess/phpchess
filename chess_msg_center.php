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
  $Contentpage = "cell_msg_center.php";  

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
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $type = trim($_GET['type']);
  $inid = trim($_GET['id']);


  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

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
  ///////////////////////////////////////////////////////////////////////

  $slctUsers = trim($_GET['slctUsers']);
  if($slctUsers == ""){
    $slctUsers = trim($_POST['slctUsers']);
  }

  $txtmsg = trim($_POST['txtmsg']);
  $cmdSend = trim($_POST['cmdSend']);

  $bMessageSent = false;

  // Send message
  if($cmdSend != "" && $slctUsers != "-" && $txtmsg != ""){

    $aToReplace = array("<", ">", "\'", "\\\"", "“", "”", "„", "‘", "’", "‚");
    $aReplaceWith = array("&lt;", "&gt;", "&#x27;", "&#x22;", "&#x201C;", "&#x201D;", "&#x201E;", "&#x2018;", "&#x2019;", "&#x201A;");

    $txtmsg = str_replace($aToReplace, $aReplaceWith, $txtmsg);
    $oR3DCQuery->SendMessage($slctUsers, $_SESSION['sid'], $_SESSION['id'], $txtmsg);
    $bMessageSent = true;

  }

  $cmdDelete = trim($_POST['cmdDelete']);
  $cmdSave = trim($_POST['cmdSave']);
  $txtIbID = trim($_POST['txtIbID']);

  $achkID = $_POST['chkMessage'];
  $cmdDeleteAll = $_POST['cmdDeleteAll'];

  // Delete All Messages
  if($cmdDeleteAll != "" && $achkID != ""){

    $ncount = count($achkID);
    $i=0;

    while($i < $ncount){
      $oR3DCQuery->DeleteMessageFromInbox($config, $achkID[$i]);
      $i++;
    }

  }

  // Delete Message
  if($cmdDelete != "" && $txtIbID != ""){
    $oR3DCQuery->DeleteMessageFromInbox($config, $txtIbID);
  }

  // Save Message
  if($cmdSave != "" && $txtIbID != ""){
    $oR3DCQuery->SaveMessageFromInbox($config, $txtIbID);
  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_17", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>

</head>
<body>

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