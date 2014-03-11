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

  $isappinstalled = 0;
  include("../includes/install_check2.php");

  if($isappinstalled == 0){
    header("Location: ../not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "../";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_admin_bugreport.php";  

  $SoapServerURL = "http://www.phpchess.com/webservices/";

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "../skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CAdmin.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");
  require($Root_Path."includes/xml.php");
  // require_once($Root_Path."includes/nusoap/nusoap.php");

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  ////////////////////////////////////////////////
  //Login Processing
  ////////////////////////////////////////////////
  //Check if admin is logged in already
  if(!isset($_SESSION['LOGIN'])){
     $login = "no";
     header('Location: ./index.php');
    
  }else{

    if($_SESSION['LOGIN'] != true){

      if (isset($_SESSION['UNAME'])){
        unset($_SESSION['UNAME']);
      }

      if (isset($_SESSION['LOGIN'])) { 
        unset($_SESSION['LOGIN']);
      }

      $login = "no";
      header('Location: ./index.php');

    }else{
      $login = "yes";
    }

  }
  ////////////////////////////////////////////////

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }

  $cmdSendReport = $_POST['cmdSendReport'];
  $elm2 = $_POST['elm2'];
  $txtEmail = trim($_POST['txtEmail']);
  $slctCondition = $_POST['slctCondition'];
  $slctType = $_POST['slctType'];

  $bCheck = false;

  // if($cmdSendReport != "" && $elm2 != "" && $slctCondition != "" && $slctType != ""){

    // // Format the colected data
    // $strType = $slctType;
    // $strCondition = $slctCondition;
    // $strServerVersion = $oR3DCQuery->GetServerTypeName()." - ".$oR3DCQuery->GetServerVersion();
    // $strEmail = $txtEmail;
    // $strReport = base64_encode(stripslashes($elm2));

    // $bCheckComplete = false;

    // // Create the soap client
    // $client = new soap_client($SoapServerURL."bugreport_soap.php?wsdl", true, $proxyhost, $proxyport, $proxyusername, $proxypassword);

    // // Check for an error
    // if(!$client->getError()){
  
      // // Call the SendServerBugReport function 
      // $result = $client->call('SendServerBugReport', array('strType' => $strType, 'strCondition' => $strCondition, 'strServerVersion' => $strServerVersion, 'strEmail' => $strEmail, 'strReport' => $strReport ), '', '', false, true);

      // // Check for a fault
      // if(!$client->fault){

        // // Check for errors
        // if(!$client->getError()){

          // if($result == true){
            // $bCheckComplete = true;
          // }
            
        // }

      // }

    // }

    // if($bCheckComplete){
      // $bCheck = $result;
    // }

    // //Debugging
    // /*
    // echo $client->getError()."<br>";
    // echo '<h2>Request</h2><pre>' . htmlspecialchars($client->request, ENT_QUOTES) . '</pre>';
    // echo '<h2>Response</h2><pre>' . htmlspecialchars($client->response, ENT_QUOTES) . '</pre>';
    // echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->debug_str, ENT_QUOTES) . '</pre>';
    // */

  // }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_67", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript_admin.php");?>
</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////
?>