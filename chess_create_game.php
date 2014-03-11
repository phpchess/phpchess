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
  $Contentpage = "cell_create_game_ar.php";  

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

  $othpid = trim($_GET['othpid']);
  $my_color = trim($_GET['my_color']);
  $fen = trim($_GET['fen']);
  $otherplayerid = trim($_GET['otherplayerid']);
  $cmdCreateGame = trim($_GET['cmdCreateGame']);
  $chkrealtime = $_GET['chkrealtime'];
  $chkrealtimeposs = $_GET['chkrealtimeposs'];

  $move1 = trim($_GET['txtmoves1']);
  $time1 = trim($_GET['txtmins1']);
  $move2 = trim($_GET['txtmoves2']);
  $time2 = trim($_GET['txtmins2']);

  $precreate = trim($_GET['slc_precreate']);
  $slctGameRating = trim($_GET['slctGameRating']);
  $slctGameTime = trim($_GET['slctGameTime']);

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

  $bGameCreated = false;

  //Create the chess game
  if($cmdCreateGame != "" && $otherplayerid != "" && $my_color != ""){

    $bRTGame = false;
    if($chkrealtime != ""){
      $bRTGame = true;
    }

    $bRTGamepass = false;
    if($chkrealtimeposs != ""){
      $bRTGamepass = true;
    }

    if(trim($fen) != ""){

      // validate the fen
      if(preg_match('/^([rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/\s[wb]{1}\s[KQkq-]{1,4}\s-\s[0-9]{1,4}\s[1-9]{1,4})$/', trim($fen))){

        $txtgid = $oR3DCQuery->CreateGame($config, $_SESSION['sid'], $_SESSION['id'], $otherplayerid, $my_color, $fen, $move1, $time1, $move2, $time2, $bRTGame, 0, $bRTGamepass, $slctGameRating, $slctGameTime);
        $bGameCreated = true;

      }elseif(preg_match('/^([rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\s[wb]{1}\s[KQkq-]{1,4}\s-\s[0-9]{1,4}\s[1-9]{1,4})$/', trim($fen))){

        list($part1, $part2, $part3, $part4, $part5) = explode(" ", $fen,5);
        $fen = "$part1/ $part2 $part3 $part4 $part5";

        $txtgid = $oR3DCQuery->CreateGame($config, $_SESSION['sid'], $_SESSION['id'], $otherplayerid, $my_color, $fen, $move1, $time1, $move2, $time2, $bRTGame, 0, $bRTGamepass, $slctGameRating, $slctGameTime);
        $bGameCreated = true;

      }

    }else{

      if(trim($fen) == ""){
        $txtgid = $oR3DCQuery->CreateGame($config, $_SESSION['sid'], $_SESSION['id'], $otherplayerid, $my_color, $fen, $move1, $time1, $move2, $time2, $bRTGame, $precreate, $bRTGamepass, $slctGameRating, $slctGameTime);
        $bGameCreated = true;
      }

    }

  }
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_24", $config);?></title>

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