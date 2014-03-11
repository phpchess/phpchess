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
  $Contentpage = "cell_admin_manage_tipoftheday.php";  

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
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/language.php");

  $login = "no";

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oTipOfTheDay = new CTipOfTheDay($config);
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

  $txttip = trim($_POST['txttip']);
  $cmdAdd = trim($_POST['cmdAdd']);
  $rdodelete = trim($_POST['rdodelete']);
  $cmdDelete = trim($_POST['cmdDelete']);
  $cmdEdit = trim($_POST['cmdEdit']);
  $txtetip = trim($_POST['txtetip']);
  $txttipid = trim($_POST['txttipid']);

  if(!$bCronEnabled){
    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_7", $config);?></title>

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

<script language='javascript'>
function ValidateEditForm(){

  if(document.frmEdit.txtetip.value == ""){
    alert("<?php echo GetStringFromStringTable("IDS_TIPOFTHEDAY_JAVA_TXT_1", $config);?>");
    document.frmEdit.txtetip.select();
    document.frmEdit.txtetip.focus();
     
    return false;
  }else{
    return true;
  }

}

function ValidateAddForm(){

  if(document.frmadd.txttip.value == ""){
    alert("<?php echo GetStringFromStringTable("IDS_TIPOFTHEDAY_JAVA_TXT_1", $config);?>");
    document.frmadd.txttip.select();
    document.frmadd.txttip.focus();
     
    return false;
  }else{
    return true;
  }

}

</script>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oTipOfTheDay->Close();
  unset($oR3DCQuery);
  unset($oTipOfTheDay);
  //////////////////////////////////////////////////////////////
?>