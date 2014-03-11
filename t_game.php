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

  $Root_Path="./";
  $config = $Root_Path."bin/config.php";

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
  require($Root_Path."includes/language.php"); 

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

  $GID = $_GET['gid'];

?>

<html>
<head>
<title>Tournament Game</title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<?php
  if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id']) ){
  
    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);

    if($oR3DCQuery->TimeForTGame($config, $GID) == false){
      echo "<br>".GetStringFromStringTable("IDS_TGAME_TXT_1", $config)."";

    }else{

      if($oR3DCQuery->TGameStatus($GID)){
      
        $oR3DCQuery->GetTGameBoard($config, $GID, $_SESSION['sid'], $_SESSION['id'], $clrl, $clrd );

        if($oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $GID)){
          //echo "Its, ".$_SESSION['user']." turn to play.";
          echo str_replace("['user']", $_SESSION['user'], GetStringFromStringTable("IDS_TGAME_TXT_3", $config));
        }

      }else{
        echo "<br>".GetStringFromStringTable("IDS_TGAME_TXT_3", $config)."";
      }

    }
    $oR3DCQuery->Close();
    unset($oR3DCQuery);

  }else{

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);

    if($oR3DCQuery->TimeForTGame($config, $GID) == false){
      echo "<br>".GetStringFromStringTable("IDS_TGAME_TXT_1", $config)."";
    }else{
      if($oR3DCQuery->TGameStatus($GID)){
        $oR3DCQuery->GetTGameBoard($config, $GID, "", 0, $clrl, $clrd );
      }else{
        echo "<br>".GetStringFromStringTable("IDS_TGAME_TXT_2", $config)."";
      }

    }
    $oR3DCQuery->Close();
    unset($oR3DCQuery);
 
  }

?>

</body>
</html>