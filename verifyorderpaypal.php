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
  $Contentpage = "cell_verifyorderpaypal.php";  

  require($Root_Path."includes/language.php");
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

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oBilling = new CBilling($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  $RequiresPayment = $oBilling->IsPaymentEnabled();

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

      echo "<option value='".$CountryArray[$ncount]."'>";
      if($selectCountry == $CountryArray[$ncount]){ 
        echo "selected"; 
      }
      echo $CountryArray[$ncount]."</option>";

      $ncount++;
    }
    echo "</select>";
  }

  // post variables
  $FirstName = trim($_POST['txtFirstName']);
  $LastName = trim($_POST['txtLastName']);
  $Email = trim($_POST['txtEmail']);
  $Phonea = trim($_POST['txta']);
  $Phoneb = trim($_POST['txtb']);
  $Phonec = trim($_POST['txtc']);
  $Address = trim($_POST['txtAddress']);
  $StateProvence = trim($_POST['txtStateProvence']);
  $txtCityTown = trim($_POST['txtCityTown']);
  $PostalZip = trim($_POST['txtPostalZip']);
  $slctCountry = trim($_POST['slctCountry']);
  $CCName = trim($_POST['txtCCName']);
  $slctPaymentTerm = trim($_POST['slctPaymentTerm']);
  $lstPurchaseProducts = $_POST['lstPurchaseProducts'];
  $txtName = trim($_POST['txtName']);
  $txtEmail = trim($_POST['txtEmail']);
  $txtRedemptionCode = trim($_POST['txtRedemptionCode']);
  $cmdpayment = $_POST['cmdpayment'];

  /**********************************************************************
  * GetCountryCode
  *
  */
  function GetCountryCode($selectCountry){
   
    $country = "";

    $CountryArray = array("Anguilla", "Argentina", "Australia", "Austria", "Belgium", "Brazil", "Canada", "Chile", "China", "Costa Rica", "Denmark", "Dominican Republic", "Finland", "France", "Germany", "Greece", "Hong Kong", "Iceland", "India", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Luxembourg", "Mexico", "Netherlands", "New Zealand", "Norway", "Portugal", "Singapore", "South Korea", "Spain", "Sweden", "Switzerland", "Taiwan", "United Kingdom", "United States");
    $CountryCode = array("AI","AR","AU", "AT", "BE", "BR", "CA", "CL", "CN", "CR", "DK", "DO", "FI", "FR", "DE", "GR", "HK", "IS", "IN", "IE", "IL", "IT", "JM", "JP", "LU", "MX", "NL", "NZ", "NO", "PT", "SG", "KR", "ES", "SE", "CH", "TW", "GB", "US");

    $ncount=0;
    $nindex=38;
    while($ncount < $nindex) {

      if($selectCountry == $CountryArray[$ncount]){ 
        $country = $CountryCode[$ncount];
        break;
      }

      $ncount++;
    }

    return $country;

  }

  $txtIsRenew = $_POST['txtIsRenew'];
  $ID = $_POST['txtID'];

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_43", $config);?></title>

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