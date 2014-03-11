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

  $gid = trim($_GET['gameid']);  
  
  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_game.php";  

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

  include ($Root_Path."includes/support_chess.inc"); 
  include ($Root_Path."includes/chess.inc"); 

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $idc = trim($_GET['idc']); 
  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 
  $movefrom = trim($_GET['txtmovefrom']);
  $moveto = trim($_GET['txtmoveto']);
  $cmdMove = trim($_GET['cmdMove']);
  $cmdAccept = trim($_GET['cmdAccept']);
  $txtChatMessage = trim($_GET['txtChatMessage']);
  $cmdChat = trim($_GET['cmdChat']);
  $bmove_error = false;

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
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
    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////
  // Check if the game is not playable by the viewer
  if(!$oR3DCQuery->IsGameControlsViewableByPlayer($gid, $_SESSION['id'])){
    header('Location: ./chess_members.php');
  }

  //////////////////////////////////////////////
  //Accept game
  if ($cmdAccept != "" && $gid != ""){
	if ($cmdAccept == "OC") {
    		$oR3DCQuery->AcceptOCGame($_SESSION['sid'], $gid, $_SESSION['id']);
  	}else{
    $oR3DCQuery->AcceptGame($_SESSION['sid'], $gid, $_SESSION['id']);
  	}
  }

  $cmdRevoke = $_GET['cmdRevoke'];

  $brevoked = false;
  //////////////////////////////////////////////
  //Revoke game
  if ($cmdRevoke != "" && $gid != ""){
    $oR3DCQuery->RevokeGame($gid, $_SESSION['id']);
    $brevoked = true;
  }

  $cmdRevokeChlng = $_GET['cmdRevokeChlng'];

  if($cmdRevokeChlng != "" && $gid != ""){
    $oR3DCQuery->RevokeGame2($gid, $_SESSION['id']);
    $brevoked = true;
  }


  //Check if the game is accepted
  $IsAccepted = $oR3DCQuery->CheckGameAccepted($config, $_SESSION['id'], $gid);
  $isblack = $oR3DCQuery->IsPlayerBlack($config, $gid, $_SESSION['id']);

  $gametypecode = $oR3DCQuery->GetGameTypeCode($gid);

  list($PlayerType, $status) = explode(" ", $IsAccepted, 2);
  if($status == "waiting" || $status == "-"){
    
  }else{
    //Redirect to the game.

    if($gametypecode == 2){
      Header("Location: ./chess_game2.php?gameid=".$gid);
    }elseif($gametypecode == 3){
      Header("Location: ./chess_game3.php?gameid=".$gid);
    }else{
      Header("Location: ./chess_game1.php?gameid=".$gid);
    }

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_23", $config);?></title>

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