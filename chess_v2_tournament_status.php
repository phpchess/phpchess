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
  define("CFG_JAVASCRIPT_VAR", 1);

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
  $Page_Name = "chess_tournament_status.php";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_v2_tournament_status.php";  

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
  include($Root_Path."skins/".$SkinName."/tournament_cfg.php");


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

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }


  /**********************************************************************
  * selectTimezone
  *
  */
  function selectTimezone($name, $selected, $config){

    if($selected == ""){
      $selected = 0;
    }

    $aTag = array(GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 12 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 11 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 10 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 9 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 8 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 7 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 6 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 4 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 3.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 3 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 2 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." - 1 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 1 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 2 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 3 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 3.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 4 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 4.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 5.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 6 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 6.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 7 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 8 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 9 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 9.5 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 10 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 11 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 12 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config),
                     GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_33", $config)." + 13 ".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_34", $config));

    $aValues = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 6, 6.5, 7, 8, 9, 9.5, 10, 11, 12, 13);

    echo "<select name='".$name."'>";
 
    $ncount = count($aValues);

    $i=0;
    while($i < $ncount){

      if($selected == $aValues[$i]){
        echo "<option value='".$aValues[$i]."' selected>".$aTag[$i]."</option>";
      }else{
        echo "<option value='".$aValues[$i]."'>".$aTag[$i]."</option>";
      }

      $i++;
    }

    echo "</select>";

  }

  $TID = $_GET['tid'];
  $TYPE = $_GET['type'];
  $tzn = 0; //$_GET['tzn'];
  $join = $_GET['join'];

  /////////////////////////////////////
  // Get the query string variables if there is any
  $index = trim($_GET["cmonth"]);

  // Get the current/selected date
  $dday = 1;

  if($index != ""){

    if(!is_numeric($index)){
      $index = 0;
    }

  }else{
    $index = 0;
  }

  /////////////////////////////////////
  // Join the tournament (Closed)
  if($join == "x1" && is_numeric($TID) && is_numeric($TYPE)){

    // Check if the player is logged on
    if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){
      $oR3DCQuery->v2AcceptTournamentInvite($TYPE, $TID, $_SESSION['id']);
    }

  }

  /////////////////////////////////////
  // Join the tournament (open)
  if($join == "x2" && is_numeric($TID) && is_numeric($TYPE)){

    // Check if the player is logged on
    if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){
      $oR3DCQuery->v2JoinTournamentInvite($TYPE, $TID, $_SESSION['id']);
    }

  }

  /////////////////////////////////////
  // Get tournament information
  $oR3DCQuery->v2GetTournamentInformation_OneToMany($TID, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);
  $nDate = gmdate("n.j.Y", gmmktime(0,0,0, gmdate("n") + $index, $dday, gmdate("Y")));
  list($month, $day, $year) = preg_split('/[\/.-]/', $nDate, 3);

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_12", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php
  $RandName1 = md5(time().rand(0, 10000000));
  $RandName2 = md5(time().rand(0, 10000000));

  include($Root_Path."includes/javascript.php");
?>

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