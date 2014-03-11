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

  // Start or get an old session
  session_start();
  
  // Variables and includes
  $ROOT_PATH="./";
  $config = $ROOT_PATH."bin/config.php";

  require($ROOT_PATH."bin/CSkins.php");
  
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
  require($Root_Path."includes/language.php");

  ////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  ////////////////////////////////////////////////////

  $tgc = $_GET['tgc'];
  $tid = $_GET['tid'];
  $type = $_GET['type'];
  $tzn = $_GET['tzn'];

  $bPlayerHasAccess = false;
  $bPrimaryPlayerHasAccess = false;

  // Check if the user is logged in and has access to the man player tournament console
  if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id'])){

    if(is_numeric($_SESSION['id'])){

      if($oR3DCQuery->v2IsUserPlayer($tgc, $_SESSION['id'], $type, $tid)){

        if($oR3DCQuery->v2IsUserPrimaryPlayer($_SESSION['id'], $type, $tid) == false){

          $oR3DCQuery->v2ClearTournamentGameQueue($_SESSION['id'], $type, $tid);
          $bPlayerHasAccess = true;

        }elseif($oR3DCQuery->v2IsUserPrimaryPlayer($_SESSION['id'], $type, $tid)){

          $oR3DCQuery->v2ClearTournamentGameQueue($_SESSION['id'], $type, $tid);
          $bPrimaryPlayerHasAccess = true;

        }

      }

    }

  }

 $strStatus = $oR3DCQuery->v2GetTournamentGameTimeoutStatus($type, $tid, $tgc);

?>
<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>

<?php if($bPlayerHasAccess && $bPrimaryPlayerHasAccess == false && $strStatus == "IDS_GAME_READY"){?>


<frameset rows="1%, 43%, 56%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" name='frHandler' src="./tv2p_handler.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">

  <frameset cols="200, 700" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

    <frame noresize="noresize" name='frmenu1' src="./tv2p_menu1.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">
    <frame noresize="noresize" name='frchat1' src="./tv2p_chat1.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">

  </frameset>
  <frame noresize="noresize" name='frGameList' src="./tv2p_gameList.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>"> 
</frameset> 


<?php }elseif($bPlayerHasAccess == false && $bPrimaryPlayerHasAccess && $strStatus == "IDS_GAME_READY"){?>



<frameset rows="1%, 43%, 56%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

  <frame noresize="noresize" name='frHandler' src="./tv2p_handler.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">

  <frameset cols="200, 700" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>

    <frame noresize="noresize" name='frmenu1' src="./tv2p_menu1.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">
    <frame noresize="noresize" name='frchat1' src="./tv2p_chat1.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>">

  </frameset>
  <frame noresize="noresize" name='frGameList' src="./tv2p_gameList.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>"> 
</frameset>




<?php }elseif($strStatus == "IDS_GAME_NOT_READY" || $strStatus == "IDS_GAME_FINISHED"){?>

<table align='center'>
<tr><td>
<?php 
    // Game is not ready
    if($strStatus == "IDS_GAME_NOT_READY"){
      echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_108", $config);
    }

    // Game is finished
    if($strStatus == "IDS_GAME_FINISHED"){
      echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_109", $config);
    }
?>
</td></tr>
</table>

<?php }else{?>

<frameset rows="5%, 95%" BORDER=0 FRAMEBORDER=0 FRAMESPACING=0>
  <frame noresize="noresize" name='frtop' src="./tv2_spectator_m1.php">
  <frame noresize="noresize" name='frmain' src="./tv2_spectator_m2.php?tgc=<?php echo $tgc;?>&tid=<?php echo $tid;?>&type=<?php echo $type;?>&tzn=<?php echo $tzn;?>"> 
</frameset> 

<?php }?>

</html>

<?php
  ////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  ////////////////////////////////////////////////////
?>