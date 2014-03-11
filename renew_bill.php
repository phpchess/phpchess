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
  $Contentpage = "cell_renew_bill.php";  

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
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oBilling = new CBilling($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  if(!$bCronEnabled){
    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }

  $isLoggedInUser = false;
  $isLoggedInDisUser = false;

  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){

    $user = trim($_POST['txtName']);
    $pass = trim($_POST['txtPassword']);

    if($user != "" && $pass !=""){

      $bLoggedIN = $oR3DCQuery->LoginBilling($user, $pass);

      if($bLoggedIN){

        $_SESSION['userdis'] = $user;

        $isLoggedInDisUser = true;

      }

    }

  }else{

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      header('Location: ./chess_login.php');
    }else{
      $isLoggedInUser = true;
      $isLoggedInDisUser = false;
    }

  }

  if($isLoggedInUser == false && $isLoggedInDisUser == false){
      header('Location: ./renew_bill_login.php');
  }

  $USERNAME = "";

  if($isLoggedInUser && $isLoggedInDisUser == false){
    $USERNAME = $_SESSION['user'];
  }

  if($isLoggedInUser == false && $isLoggedInDisUser){
    $USERNAME = $_SESSION['userdis'];
  }


  /**********************************************************************
  * SelectCountries
  *
  */
  function SelectCountries($selectCountry,$SelectName){

    $CountryArray = array("Anguilla", "Argentina", "Australia", "Austria", "Belgium", "Brazil", "Canada", "Chile", "China", "Costa Rica", "Denmark", "Dominican Republic", "Finland", "France", "Germany", "Greece", "Hong Kong", "Iceland", "India", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Luxembourg", "Mexico", "Netherlands", "New Zealand", "Norway", "Portugal", "Singapore", "South Korea", "Spain", "Sweden", "Switzerland", "Taiwan", "United Kingdom", "United States");

    echo "<select name='".$SelectName."'>";

    if($selectCountry == "-"){
      echo "<option value='-' Selected>-- Choose Your Country --</option>";
    }

    if($selectCountry == ""){
      echo "<option value='-' Selected>-- Choose Your Country --</option>";
    }
 
    $ncount=0;
    $nindex=38;
    while($ncount < $nindex) {

      echo "<option value='".$CountryArray[$ncount]."'";
      if($selectCountry == $CountryArray[$ncount]){ 
        echo "selected>"; 
      }else{
        echo ">";
      }
      echo $CountryArray[$ncount]."</option>";

      $ncount++;
    }
    echo "</select>";
  }


  /**********************************************************************
  * SelectPaymentTerm
  *
  */
  function SelectPaymentTerm($SelectPaymentTerm,$SelectName){
 
    $config = $Root_Path."bin/config.php";

    echo "<select name='".$SelectName."'>";

    if($SelectPaymentTerm == 'm'){
      echo "<option value='m' selected>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_M", $config)."</option>";
    }else{
      echo "<option value='m'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_M", $config)."</option>";
    }

    if($SelectPaymentTerm == 's'){
      echo "<option value='s' selected>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_SA", $config)."</option>";
    }else{
      echo "<option value='s'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_SA", $config)."</option>";
    }

    if($SelectPaymentTerm == 'y'){
      echo "<option value='y' selected>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_Y", $config)."</option>";
    }else{
      echo "<option value='y'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_REDEMPTIONCODE_SELECT_Y", $config)."</option>";
    }

    echo "</select>";

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_41", $config);?></title>

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
  $oBilling->Close();
  unset($oR3DCQuery);
  unset($oBilling);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>