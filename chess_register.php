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
  $Page_Name = "chess_register.php";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_chess_register.php";  

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
  require($Root_Path."bin/php-captcha.inc.php");
  require($Root_Path."bin/LanguageParser.php");

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

  }else{

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      header('Location: ./chess_login.php');
    }

  }
  
  LanguageFile::load_language_file2($conf);
  
  $bLimit = $oR3DCQuery->IsUserLimitReached();
  $bRequiresApproval = $oR3DCQuery->NewUserRequiresApproval();

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }

  $txtName = trim($_POST['txtName']);
  $txtEmail = trim($_POST['txtEmail']);
  $txtVI = trim($_POST['txtVI']);
  $cmdRegister = trim($_POST['cmdRegister']);

  $ReturnStatus = "";
  $register_success = FALSE;

  if($cmdRegister != "" && $txtName != "" && $txtEmail != ""){
   if (PhpCaptcha::Validate($_POST['txtVI'])) {

    if($bRequiresApproval == false && $RequiresPayment == false){

      //Add the new user
      $ret = $oR3DCQuery->RegisterNewPlayer($txtName, $txtEmail);
	  $register_success = $ret['success'];
	  $ReturnStatus = $ret['msg'];

    }elseif($bRequiresApproval == true  && $RequiresPayment == false){

      //Add the new user
      $ReturnStatus = $oR3DCQuery->RegisterNewPlayer2($txtName, $txtEmail);
	  $register_success = $ret['success'];
	  $ReturnStatus = $ret['msg'];
    }
   }
  }
  
  // Convert return status in a string to display to the user
  if($ReturnStatus == 'One or more required fields was left blank!')
  {
	$ReturnStatus = __l('One or more required fields was left blank!');
  }
  else if($ReturnStatus == 'That userid is taken, please reregister with a different id!')
  {
	$ReturnStatus = __l('That userid is taken, please reregister with a different id!');
  }
  else if($ReturnStatus == 'Your account has been created. An initial password will be emailed to the address you specified.')
  {
	$ReturnStatus = __l('Your account has been created. An initial password will be emailed to the address you specified.');
  }
  else if($ReturnStatus == 'Your account has been created. Your account will be enabled when the administrator approves it.')
  {
	$ReturnStatus = __l('Your account has been created. Your account will be enabled when the administrator approves it.');
  }
  else if($ReturnStatus == 'Invalid characters detected.')
  {
	$ReturnStatus = __l('Invalid characters detected.');
  }
  else if($ReturnStatus == 'Name must be less than or equal to 11 characters.')
  {
	$ReturnStatus = __l('Name must be less than or equal to 11 characters.');
  }
  
  $tologinpage = __l('Return to the login page');
  
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_10", $config);?></title>

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