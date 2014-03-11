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
  $Page_Name = "chess_register.php";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_renew_bill_login.php";  

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
  require($Root_Path."bin/CBilling.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/language.php");

  //Instantiate the CBilling Class
  $oBilling = new CBilling($config);
  $RequiresPayment = $oBilling->IsPaymentEnabled();
  $oBilling->Close();
  unset($oBilling);

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }

  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){

    $user = trim($_POST['txtName']);
    $pass = trim($_POST['txtPassword']);

    if($user != "" && $pass !=""){

      $sid = $oR3DCQuery->Login($user, $pass);
      $id = $oR3DCQuery->GetIDByUserID($config, $user);
      

      if($sid != ""){

        $_SESSION['sid'] = $sid;
        $_SESSION['user'] = $user;
        $_SESSION['id'] = $id;

        //Get Chessboard colors
        $d = "";
        $l = "";

        $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
        
        $_SESSION['lcolor'] = $l;
        $_SESSION['dcolor'] = $d;

        header("Location: ./chess_members.php");

      }

    }

  }else{

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      header('Location: ./chess_login.php');
    }

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_40", $config);?></title>

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