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
  ob_start();

  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $Page_Name = "index.php";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_index.php";  

  require($Root_Path."bin/CSkins.php");
  
  //////////////////////////////////////////////////////////////
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $SiteName = $oSkins->getsitename();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  include_once($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."bin/CFrontNews.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $user = trim($_POST['txtName']);
  $pass = trim($_POST['txtPassword']);
  $languagefile = trim($_POST['slctlanguage']);
  $chkAutoLogin = trim($_POST['chkAutoLogin']);

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oFrontNews = new CFrontNews($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  ///////////////////////////////////////////////////////////////////
  //LOGIN methods

  //manage cookie data
  if($_COOKIE['TestCookie'] != ""){ 

    list($user1, $pass1, $languagefile1) = preg_split("/\|/", $_COOKIE['TestCookie'], 3);
    $user = $user1;
    $pass = base64_decode($pass1);
    $languagefile = $languagefile1;

    $chkAutoLogin = "1";

  }  

  if($user != "" && $pass !=""){

    $sid = $oR3DCQuery->Login($user, $pass);
    $id = $oR3DCQuery->GetIDByUserID($config, $user);

    if($sid != ""){
	
      $_SESSION['sid'] = $sid;
      $_SESSION['user'] = $oR3DCQuery->GetUserIDByPlayerID($config,$id);
      $_SESSION['id'] = $id;
      $_SESSION['password'] = $pass;
      $_SESSION['language'] = $languagefile;

      //Get Chessboard colors
      $d = "";
      $l = "";

      $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
      
      $_SESSION['lcolor'] = $l;
      $_SESSION['dcolor'] = $d;

      if($oR3DCQuery->IsPlayerDisabled($id) == false){

        //Create the cookie if auto login
        if($chkAutoLogin == "1"){
          $cookie_data = $user."|".base64_encode($pass)."|".$languagefile;
          setcookie("TestCookie", $cookie_data, time()+360000);
        }

        $oR3DCQuery->AddOnlinePlayerToGraphData($_SESSION['user']);
        $oR3DCQuery->UpdateLastLoginInfo($_SESSION['id']);
        $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);

        if($_SESSION['PageRef'] != ""){
          header("Location: ".$_SESSION['PageRef']."");
        }else{
          header("Location: ./chess_members.php");
        }

      }else{

        if($_COOKIE['TestCookie'] != ""){ 
          setcookie("TestCookie", $cookie_data, time()-360000);
        }

        header("Location: ./chess_logout.php");
      }  
    }

  }

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }

    $oR3DCQuery->MangeGameTimeOuts();
  }
  ///////////////////////////////////////////////////////////////////
 
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_9", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv=Content-Type content="text/html; charset=utf-8">

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
  $oFrontNews->Close();
  unset($oR3DCQuery);
  unset($oFrontNews);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>