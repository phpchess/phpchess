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
  $slctPlayerCutOffDateMonth = trim($_GET['slctPlayerCutOffDateMonth']);
  $slctPlayerCutOffDateDay = trim($_GET['slctPlayerCutOffDateDay']);
  $slctPlayerCutOffDateYear = trim($_GET['slctPlayerCutOffDateYear']);
  $txtCutOfftimeH = trim($_GET['txtCutOfftimeH']);
  $txtCutOfftimeM = trim($_GET['txtCutOfftimeM']);
  $txtCutOfftimeS = trim($_GET['txtCutOfftimeS']);
  $cmdCreateGames = $_GET['cmdCreateGames'];

  $strError = "";
  $bAdded = false;

  if($cmdCreateGames != "" && $oR3DCQuery->v2IsOTMTournamentGamesCreated($type, $tid) == false){

    if($type == 1){
      $oR3DCQuery->v2GetTournamentInformation_OneToMany($tid, $strname, $strdescription, $nplayercutoffdate, $ntournamentstartdate, $ntournamentenddate, $strtimezone, $strgametimeout, $nplayersignuptype, $strdateadded, $strstatus, $aTOrganizers, $aTPlayers);
    }

    $oR3DCQuery->v2ConsoleCreateGamesAll($type, $tid, time());
    $bAdded = true;

  }

?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
</head>
<body>

<form name='frmCreateGames' method='get' action='./tv2_console_main_cgame.php'>

<?php
if($strError != ""){
  echo "<center>".$strError."</center><br>";
}

if(!$bAdded){

  if($oR3DCQuery->v2IsOTMTournamentGamesCreated($type, $tid) == false && $oR3DCQuery->v2IsOTMCutoffDateMet($type, $tid) == true){

//  if($oR3DCQuery->v2IsOTMTournamentGamesCreated($type, $tid) == false){

?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='3'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_84", $config);?></font>
</td>
</tr>

<tr>
<td class='row2' align='center' colspan='3'>
<input type='submit' name='cmdCreateGames' value='<?php echo GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_85", $config);?>' class='mainoption'>
</td>
</tr>
</table>

<input type='hidden' name='tid' value='<?php echo $tid;?>'>
<input type='hidden' name='type' value='<?php echo $type;?>'>

</form>

<?php
  }else{
    echo "<center>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_110", $config)."</center><br>";
  }

}else{
  echo "<center>".GetStringFromStringTable("IDS_CHESS_TOURNAMENT_V2_TXT_93", $config)."</center><br>";
}
?>

</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>