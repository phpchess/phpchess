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

  $Root_Path = "./";
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

  // require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."includes/language.php");
  include($Root_Path."bin/CChess.php");
  include($Root_Path."CSession.php");
  include($Root_Path."CChess2.php");
  include($Root_Path."CChessBoard.php");
  include($Root_Path."CChessBoardUtilities.php");

  $GID = $_GET['gid'];

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_42", $config);?></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>

</head>
<body>

<center>
<textarea rows='27' cols='70' class='post'>
<?php
  //Instantiate the CR3DCQuery Class
  // $oR3DCQuery = new CR3DCQuery($config);
  // $PGN = $oR3DCQuery->SavePGN($config, $GID);
  // $fen = $oR3DCQuery->GetActualFEN($sid, $GID); //GetFEN
  // $oR3DCQuery->Close();
  // unset($oR3DCQuery);

  // echo $PGN;
	CSession::initialise($config);
	ChessHelper::load_chess_game($_GET['gid']);
	echo ChessHelper::get_game_pgn();
?>
</textarea>
<br>
<input type='text' value='<?php echo $fen;?>' size='70'>
</center>

</body>
</html>