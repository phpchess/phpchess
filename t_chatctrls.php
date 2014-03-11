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
  $ChatMsg = $_GET['txtChatMsg'];

  if($ChatMsg != "" && $GID != ""){

    $User = "Viewer";

    if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id']) ){
      $User = $_SESSION['user'];
    }  

    $Message = $User." - ".$ChatMsg;

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $oR3DCQuery->SendTChat($config, $GID, $Message);
    $oR3DCQuery->Close();
    unset($oR3DCQuery);

  }

?>

<html>
<head>
<title>Tournament Chat Controls</title>

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<?php

if(isset($_SESSION['sid']) && isset($_SESSION['user']) && isset($_SESSION['id']) ){

  echo "<form name='frmtchat' method='get' action='./t_chatctrls.php'>";
  echo "<table width='100%' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
  echo "<tr>";
  echo "<td valign='top' class='row1'>";
  echo "<font class='sitemenuheader'>Chat</font>";
  echo "</td>";
  echo "</tr>";

  echo "<tr>";
  echo "<td valign='top' class='row2'>";
  echo "<input type='text' name='txtChatMsg' class='post' size='25'><input type='submit' name='cmdSend' value='Send' class='mainoption'>";
  echo "</td>";
  echo "</tr>";
 
  echo "<input type='hidden' name='gid' value='".$GID."'>";

  echo "</table>";
  echo "</form>";
}  
?>

</body>
</html>