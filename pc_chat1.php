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

  $jtime = $_GET['jtime'];

  $message = trim($_GET['txtMessage']);
  $cmdSend = $_GET['cmdSend'];

  if($cmdSend != "" && isset($_SESSION['id']) && $message != ""){
    $oR3DCQuery->PlayerChatAddChatMessage($_SESSION['id'], $message);
  }

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<form name='frmChatList' method='get' action='./pc_chat1.php'>

<?php

$XRow = 25;
$XCOL = 107;

if(defined('CFG_PLAYERCHAT_ROWS') && defined('CFG_PLAYERCHAT_COLS')){
  $XRow = CFG_PLAYERCHAT_ROWS;
  $XCOL = CFG_PLAYERCHAT_COLS;
}

?>

<textarea name='txtchatmsg' rows='<?php echo $XRow;?>' cols='<?php echo $XCOL?>'><?php echo $oR3DCQuery->PlayerChatGetChatMessages($jtime);?></textarea><br>
<input type='text' name='txtMessage' class='post' size='90'>
<input type='submit' name='cmdSend' value='Send' class='mainoption'>

<input type='hidden' name='jtime' value='<?php echo $jtime;?>'>
</form>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>