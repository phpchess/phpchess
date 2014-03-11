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
  $Contentpage = "cell_chess_member.php";  

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
  require($Root_Path."bin/CServMsg.php");
  require($Root_Path."includes/language.php");
  require($Root_Path."includes/siteconfig.php");
  include_once($Root_Path."bin/CAvatars.php");
  require($Root_Path."bin/LanguageParser.php");
	include($config);

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oAvatars = new CAvatars($config);
  $oBilling = new CBilling($config);
  $oServMsg = new CServMsg($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////
  // Check For the nonempty SID var
  $sid = trim($_GET['sid']);

  // Log the user on or manage the session if a SID is passed to the page
  if($sid != ""){

    $user = "";
    $id = "";
    $oR3DCQuery->ConfirmSID($sid, $user, $id);
 
    if($user != "" && $id != ""){
      $_SESSION['sid'] = $sid;
      $_SESSION['user'] = $user;
      $_SESSION['id'] = $id;

      $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
      
      $_SESSION['lcolor'] = $l;
      $_SESSION['dcolor'] = $d;

      if($oR3DCQuery->IsPlayerDisabled($id) == false){

        $clrl = $_SESSION['lcolor'];
        $clrd = $_SESSION['dcolor'];

        $oR3DCQuery->AddOnlinePlayerToGraphData($_SESSION['user']);
        $oR3DCQuery->UpdateLastLoginInfo($_SESSION['id']);
        $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);

      }else{
        header('Location: ./chess_logout.php');
      }

    }

  }
  ///////////////////////////////////////////////////////////////////


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

      if(!isset($_SESSION['point_cached'])){

        /////////////////////////////////////////////
        // Point caching
        /////////////////////////////////////////////
        $oR3DCQuery->GetPlayerStatusrRefByPlayerID($ConfigFile, $_SESSION['id'], $x_wins, $x_loss, $x_draws);
        $xPoints=0;

        if($oR3DCQuery->ELOIsActive()){
          $xPoints = $oR3DCQuery->ELOGetRating($_SESSION['id']);
        }else{
          $xPoints = $oR3DCQuery->GetPointValue($x_wins, $x_loss, $x_draws);
        }

        $oR3DCQuery->SetChessPointCacheData($_SESSION['id'], $xPoints);

        /////////////////////////////////////////////

        $_SESSION['point_cached'] = true;

      }

    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////////
	LanguageFile::load_language_file($Root_Path . 'includes/languages/' . preg_replace('/\.txt/', '.php', $_SESSION['language']));

  ///////////////////////////////////////////////////////////////////////
  // Forum Management
  ///////////////////////////////////////////////////////////////////////

  $FMfile = "./includes/forum_management.php";
  if(file_exists($FMfile)){

    $FMPassChange = false;
    $FMUserID = $_SESSION['id'];
    $FMUserName = $_SESSION['user'];

    include($FMfile);


  }

  ///////////////////////////////////////////////////////////////////////

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_16", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>
<script src="./includes/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>

</head>
<body >
<?php include("./skins/".$SkinName."/layout_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oAvatars->Close();
  $oBilling->Close();
  $oServMsg->Close();
  unset($oR3DCQuery);
  unset($oAvatars);
  unset($oBilling);
  unset($oServMsg);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>