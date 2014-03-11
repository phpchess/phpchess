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

  $host = $_SERVER['HTTP_HOST'];
  $self = $_SERVER['PHP_SELF'];
  $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
  $url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

  header("Content-Type: text/html; charset=utf-8");
  session_start();
  ob_start();

  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
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

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  //include_once($Root_Path."bin/CAvatars.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $clrl = $_SESSION['lcolor'];
  $clrd = $_SESSION['dcolor']; 

  $strFEN = $_GET['FEN'];
  $txtboardpos = $_GET['txtboardpos'];
  $txtchesspeice = $_GET['txtchesspeice'];

  //////////////////////////////////////////////////////////////
  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_34", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>

</head>
<body>

<script language="javascript"> 
function ClearCoordinates(){

  document.frmcolor.txtboardpos.value = "";

  document.getElementById('8-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('8-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('8-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('7-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('7-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('7-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('6-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('6-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('6-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('5-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('5-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('5-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('4-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('4-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('4-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('3-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('3-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('3-8').style.background = "<?php echo $clrl;?>";

  document.getElementById('2-1').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-2').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-3').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-4').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-5').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-6').style.background = "<?php echo $clrd;?>";
  document.getElementById('2-7').style.background = "<?php echo $clrl;?>";
  document.getElementById('2-8').style.background = "<?php echo $clrd;?>";

  document.getElementById('1-1').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-2').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-3').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-4').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-5').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-6').style.background = "<?php echo $clrl;?>";
  document.getElementById('1-7').style.background = "<?php echo $clrd;?>";
  document.getElementById('1-8').style.background = "<?php echo $clrl;?>";

}

function ProcessMove(target){

  // This checks if the browser is an MSIE browser or Netscape browser.
  var browserVer=parseInt(navigator.appVersion); 
  if((navigator.appName == "Microsoft Internet Explorer" && browserVer >= 4) || navigator.appName == "Netscape" && browserVer >= 4){
  
   if(document.frmcolor.txtboardpos != null){

     document.getElementById(target).style.background = "#9999cc";

     if(document.frmcolor.txtboardpos.value == ""){
 
       document.frmcolor.txtboardpos.value = target;

     }else{

       if(document.frmcolor.txtboardpos.value != ""){
         // clear selection
         ClearCoordinates();

         document.getElementById(target).style.background = "#9999cc";
         document.frmcolor.txtboardpos.value = target;

       }

     }
 
   }

  } 

}

function addpiece(piece){

  document.frmcolor.txtchesspeice.value = piece;
  document.frmcolor.submit();

}

</script>

<?php

  $aboard = array(array('r','n','b','q','k','b','n','r'),
                 array('p','p','p','p','p','p','p','p'),
  		 array('e','e','e','e','e','e','e','e'),
  		 array('e','e','e','e','e','e','e','e'),
  		 array('e','e','e','e','e','e','e','e'),
  		 array('e','e','e','e','e','e','e','e'),
  		 array('P','P','P','P','P','P','P','P'),
  		 array('R','N','B','Q','K','B','N','R'));


  if($strFEN != ""){

    if(preg_match('/^([rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/?[rnbqkpRNBQKP1-8]{1,8}\/\s[wb]{1}\s[KQkq-]{1,4}\s-\s[0-9]{1,4}\s[0-9]{1,4})$/', trim($strFEN))){

      $oR3DCQuery->CreateFENArray($strFEN, $aChessBoard);
      $board = $aChessBoard;

    }else{
      $board = $aboard;
    }

  }else{

    $board = $aboard;

  }

  //change the piece
  if($txtboardpos != "" && $txtchesspeice != ""){

    list($one, $two) = explode("-", $txtboardpos);
    $board[($one-1)][($two-1)] = trim($txtchesspeice);

  }

  $fen1 = "0000000000".$oR3DCQuery->CreateFENStringf($board);
  $fen2 = $oR3DCQuery->CreateFENStringf($board);

  echo "<form name='frmcolor' method='GET' action='./create_fen.php'>";
  echo "<table>";
  echo "<tr>";
  echo "<td>";

  $oR3DCQuery->CreateChessBoard($fen1, $clrl, $clrd, true, "w");

  echo "</td>";
  echo "<td valign='top'>";
  echo "<table>";
  echo "<tr>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('r');\" border='0'>".$oR3DCQuery->GetChessPieceImage("r")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('n');\" border='0'>".$oR3DCQuery->GetChessPieceImage("n")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('b');\" border='0'>".$oR3DCQuery->GetChessPieceImage("b")."</a>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('k');\" border='0'>".$oR3DCQuery->GetChessPieceImage("k")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('q');\" border='0'>".$oR3DCQuery->GetChessPieceImage("q")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('p');\" border='0'>".$oR3DCQuery->GetChessPieceImage("p")."</a>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('R');\" border='0'>".$oR3DCQuery->GetChessPieceImage("R")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('N');\" border='0'>".$oR3DCQuery->GetChessPieceImage("N")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('B');\" border='0'>".$oR3DCQuery->GetChessPieceImage("B")."</a>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('K');\" border='0'>".$oR3DCQuery->GetChessPieceImage("K")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('Q');\" border='0'>".$oR3DCQuery->GetChessPieceImage("Q")."</a>";
  echo "</td>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('P');\" border='0'>".$oR3DCQuery->GetChessPieceImage("P")."</a>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td bgcolor='#FFFFFF'>";
  echo "<a href=\"javascript:addpiece('e');\" border='0'>".$oR3DCQuery->GetChessPieceImage("blnk")."</a>";
  echo "</td>";
  echo "<td>";
  echo "</td>";
  echo "<td>";
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "</td>";
  echo "</tr>";
  echo "<tr>";
  echo "<td colspan='2' bgcolor='#FFFFFF'>";
  echo $oR3DCQuery->FormatInputedFEN($fen2);
  echo "</td>";
  echo "</tr>";
  echo "</table>";
  echo "<input type='hidden' name='txtboardpos'>";
  echo "<input type='hidden' name='txtchesspeice'>";
  echo "<input type='hidden' name='FEN' value='".$fen2."'>";
  echo "</form>";

?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>