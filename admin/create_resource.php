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
  $Contentpage = "cell_admin_create_resource.php";  

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

  /**********************************************************************
  * UploadResourceFile
  *
  */
  function UploadResourceFile($UploadDrive){

    // Declarations
    $aError = array("IDS_NO_FILE_UPLOADED", "", "");

    // Check if a File was uploaded
    if($_FILES['fFileName']['error'] != 4){

      // Gen unique ID
      $UniqueID = substr(md5(rand(0,100000)), 5);

      // Sets the upload drive
      $uploadFile = $UploadDrive.$UniqueID.$_FILES['fFileName']['name'];

      // upload the file
      if(move_uploaded_file($_FILES['fFileName']['tmp_name'], $uploadFile)){

        chmod($uploadFile, 0755);

        // Clear the error variable
        $aError = array("", $uploadFile, $UniqueID.$_FILES['fFileName']['name']);

      }else{

        // Setup error message
        $aError = array("IDS_IMAGE_MOVE_ERROR", "", "");

        // Unlink the temp file
        unlink($_FILES['fFileName']['tmp_name']);

      }

    }

    return $aError;

  }

  $AID = $_GET['aid'];
  $AIDx = $_POST['aid'];
  $txtName = $_POST['txtName'];
  $optType = $_POST['optType'];
  $txtX1 = $_POST['txtX1'];
  $txtX2 = $_POST['txtX2'];
  $cmdSubmit = $_POST['cmdSubmit'];

  if($AID == ""){
    $AID = $AIDx;
  }

  $ViewFinished = false;

  if($cmdSubmit != "" && $AIDx != "" && $txtName != "" && $optType != ""){

    if($optType != 'pgn'){

      $BanDirAbsolute = $conf['absolute_directory_location']."activities/";

      $aReturn = array();
      $aReturn = UploadResourceFile($BanDirAbsolute);

      if($aReturn[0] == "" && $aReturn[1] != "" && $aReturn[2] != ""){

        //Add The info to the db
        $oR3DCQuery->CreateResource($AIDx, $txtName, $optType, $txtX1, $txtX2, $aReturn[2]);
        $ViewFinished = true;

      }else{
        //Error
        $errorid = $aReturn[0];

      }

    }else{

      $oR3DCQuery->CreateResource($AIDx, $txtName, $optType, $txtX1, $txtX2, "");
      $ViewFinished = true;

    }

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_53", $config);?></title>

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

<?php include("../includes/cells/".$Contentpage."");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////
?>