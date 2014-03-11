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

  $tid = $_GET['tid'];
  $type = $_GET['type'];

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<script Language="JavaScript">
<!--
function PopupConsoleWindows(webpage, height, width){
     var url = webpage;
     var hWnd = window.open(url,"ConsoleWindows","width="+ width +",height="+ height +",resizable=no,scrollbars=yes,status=yes");
     if (hWnd != null) {     if (hWnd.opener == null) { hWnd.opener = self; window.name = "?"; hWnd.location.href=url; } }
}
//-->
</script>

<form name='frmMain' method='post' action='./tv2_console_main.php'>

<table width='200' cellpadding='3' cellspacing='1' border='0' class='forumline'>
<tr>
<td class='tableheadercolor'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_84", $config);?></font>
</td>
</tr>

<tr>
<td class='row2'>
<input type='button' name='btnCreateGames' value='<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_85", $config);?>' class='mainoption' onclick="javascript:PopupConsoleWindows('./tv2_console_main_cgame.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>', 105, 605);">
</td>
</tr>

<tr>
<td class='row2'>
<input type='button' name='btnCloseTournament' value='<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_111", $config);?>' class='mainoption' onclick="javascript:PopupConsoleWindows('./tv2_console_main_closet.php?tid=<?php echo $tid;?>&type=<?php echo $type;?>', 500, 600);">
</td>
</tr>
</table>

</form>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>