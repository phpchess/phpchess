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
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/language.php");

  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  $tgc = $_GET['tgc'];
  $tid = $_GET['tid'];
  $type = $_GET['type'];
  $tzn = $_GET['tzn'];
  $message = trim($_GET['txtMessage']);
  $cmdSend = $_GET['cmdSend'];

  if($cmdSend != "" && is_numeric($tid) && is_numeric($type) && isset($_SESSION['id']) && $message != ""){
    $oR3DCQuery->v2OTMAddChatMessage($type, $tid, $_SESSION['id'], $message, "cht");
  }

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<form name='frmChatList' method='get' action='./tv2p_chat1.php'>
<textarea name='txtchatmsg' rows='16' cols='107'><?php echo $oR3DCQuery->v2OTMGetChatMessages($type, $tid, $tzn);?></textarea><br>
<input type='text' name='txtMessage' class='post' size='90'>
<input type='submit' name='cmdSend' value='Send' class='mainoption'>

<input type='hidden' name='tid' value='<?php echo $tid;?>'>
<input type='hidden' name='type' value='<?php echo $type;?>'>
<input type='hidden' name='tzn' value='<?php echo $tzn;?>'>
</form>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>