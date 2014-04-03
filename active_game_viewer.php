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
  header("Cache-Control: public");
  header("Pragma: cache");
  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  $gid = $_GET['gid'];

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  if($clrl == "" && $clrd == ""){
    $clrl = "#957A01";
    $clrd = "#FFFFFF";
  }

  
  if(isset($_POST['refresh']) && isset($_POST['gid']))
  {
	$gid = $_POST['gid'];
	$oR3DCQuery->GenerateSpectatorChessboardHTML($gid, $clrl, $clrd);

	exit();
  }
?>

<html>
<head>
<title></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<script src="./includes/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
</head>
<body>

<script language="JavaScript">

var refreshinterval=<?php echo $conf['chat_refresh_rate'];?>;

$(document).ready(function(){
	setTimeout("refresh()", refreshinterval * 1000);
});

function refresh()
{
	$.post('./active_game_viewer.php', {refresh: true, gid: '<?php echo $gid; ?>'}, got_data);
}
function got_data(data)
{
	$("#container").html(data);
	setTimeout("refresh()", refreshinterval * 1000);
}

</script>
<div id="container">
<?php $oR3DCQuery->GenerateSpectatorChessboardHTML($gid, $clrl, $clrd);?>
</div>
</body>
</html>

<?php
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
?>