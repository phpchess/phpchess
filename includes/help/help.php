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
  ob_start();

  // This is the vairable that sets the root path of the website
  $Root_Path = "../../";
  $Page_Name = "help.php";
  $config = $Root_Path."bin/config.php";

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  include_once($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."bin/CFrontNews.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $strtag = $_GET['tag'];
  $IDSSTRING = "";

  switch($strtag){

    Case "register":
      $IDSSTRING = "IDS_REGISTER_USER";
      break;

    case "creategame":
      $IDSSTRING = "IDS_CREATE_GAME";
      break;

  }

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_36", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv=Content-Type content="text/html; charset=utf-8">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">

</head>
<body>

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_PAGETITLES_36", $config);?></font><b></td>
</tr>

<tr>
<td colspan='2' class='row2'>

<?php
if($IDSSTRING != ""){
  echo GetStringFromStringTableHELP($IDSSTRING, $config);
}else{
  echo GetStringFromStringTableHELP("IDS_TAG_ERROR", $config);
}
?>

</td>
</tr>
</table>

</body>
</html>

<?php
  ob_end_flush();
?>