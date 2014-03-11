<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<script language="JavaScript"><!--
var nav = window.Event ? true : false;
if (nav) {
   window.captureEvents(Event.KEYDOWN);
   window.onkeydown = NetscapeEventHandler_KeyDown;
} else {
   document.onkeydown = MicrosoftEventHandler_KeyDown;
}

function NetscapeEventHandler_KeyDown(e) {
  if (e.which == 13 && e.target.type != 'textarea' && e.target.type != 'submit') { return false; }
  return true;
}

function MicrosoftEventHandler_KeyDown() {
  if (event.keyCode == 13 && event.srcElement.type != 'textarea' && event.srcElement.type != 'submit')
    return false;
  return true;
}
//--></script>

<!-- Clock White -->
<script language="JavaScript">
<?php
$awhite = $oR3DCQuery->GetPlayerTimeRT($gid, "white");
$ablack = $oR3DCQuery->GetPlayerTimeRT($gid, "black");
?>

var sec1 = <?php echo $awhite[2];?>;
var min1 = <?php echo $awhite[1];?>;
var hour1 = <?php echo $awhite[0];?>;

function stopwatchwhite(text) {
  sec1++;
  if(sec1 == 60){
    sec1 = 0;
    min1 = min1 + 1; 
  }else{
    min1 = min1; 
  }
  if(min1 == 60){
    min1 = 0; 
    hour1 += 1;
  }
  if(sec1<=9){
    sec1 = "0" + sec1;
  }
  document.frmcolor.clockwhite.value = ((hour1<=9) ? "0"+hour1 : hour1) + " : " + ((min1<=9) ? "0" + min1 : min1) + " : " + sec1;
  SD=window.setTimeout("stopwatchwhite();", 1000);
}
</script>

<!-- Clock Black -->
<script language="JavaScript">
var sec2 = <?php echo $ablack[2];?>;
var min2 = <?php echo $ablack[1];?>;
var hour2 = <?php echo $ablack[0];?>;

function stopwatchblack(text) {
  sec2++;
  if(sec2 == 60){
    sec2 = 0;
    min2 = min2 + 1; 
  }else{
    min2 = min2; 
  }
  if(min2 == 60){
    min2 = 0; 
    hour2 += 1;
  }
  if(sec2<=9){
    sec2 = "0" + sec2;
  }
  document.frmcolor.clockblack.value = ((hour2<=9) ? "0"+hour2 : hour2) + " : " + ((min2<=9) ? "0" + min2 : min2) + " : " + sec2;
  SD=window.setTimeout("stopwatchblack();", 1000);
}
</script>

<?php
/////////////////////////////////////////////////////////////////////////
// Check if the client is in realtime mode
/////////////////////////////////////////////////////////////////////////
if($isexitrealtime == true){
?>
<script language="javascript"> 
<?php
  echo "parent.location.href = './chess_game2.php?gameid=".$gid."'";
?>
</script>
<?php
}
/////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////
// Used to get the last move
/////////////////////////////////////////////////////////////////////////

$aMoveElm = $oR3DCQuery->GetGamesLastMove($gid);
$lmclr = "#666699";

/////////////////////////////////////////////////////////////////////////
?>

<script language="javascript"> 

function ClearCoordinates(){

  document.frmcolor.txtmovefrom.value = "";
  document.frmcolor.txtmoveto.value = "";

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

  <?php
  /////////////////////////////////////////////////////////////
  $lmcount = count($aMoveElm);

  if($lmcount != 0){
    $iz = 0;
    while($iz < $lmcount){
      echo "document.getElementById('".$aMoveElm[$iz]."').style.background = \"".$lmclr."\";\n";
      $iz++;
    } 
  }
  ////////////////////////////////////////////////////////////
  ?>

}

function GetCoordinate(coordinate){

  var aCoord = coordinate.split("-");
  var Col1 = '';

  switch(aCoord[1]){
    case '1':
      Col1 = 'a';
      break;
    case '2':
      Col1 = 'b';
      break;
    case '3':
      Col1 = 'c';
      break;
    case '4':
      Col1 = 'd';
      break;
    case '5':
      Col1 = 'e';
      break;
    case '6':
      Col1 = 'f';
      break;
    case '7':
      Col1 = 'g';
      break;
    case '8':
      Col1 = 'h';
      break;
  }

  return Col1 + "" + aCoord[0];

}

function ProcessMove(target){

  // This checks if the browser is an MSIE browser or Netscape browser.
  var browserVer=parseInt(navigator.appVersion); 
  if((navigator.appName == "Microsoft Internet Explorer" && browserVer >= 4) || navigator.appName == "Netscape" && browserVer >= 4){
  
   if(document.frmcolor.txtmovefrom != null && document.frmcolor.txtmoveto != null){

     document.getElementById(target).style.background = "#9999cc";

     if(document.frmcolor.txtmovefrom.value == "" && document.frmcolor.txtmoveto.value == ""){
 
       document.frmcolor.txtmovefrom.value = GetCoordinate(target);

     }else{

       if(document.frmcolor.txtmovefrom.value != "" && document.frmcolor.txtmoveto.value == ""){
 
         document.frmcolor.txtmoveto.value = GetCoordinate(target);

       }else{

         if(document.frmcolor.txtmovefrom.value != "" && document.frmcolor.txtmoveto.value != ""){
           // clear selection
           ClearCoordinates();
         }

       }

     }
     
     //alert(target);

   }

  } 

}

</script>


<?php
/////////////////////////////////////////////////////////////////////////
// Used to get the game status
/////////////////////////////////////////////////////////////////////////
list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

if($status == "waiting" || $status == "-"){

  if($PlayerType == "i"){
?>

<form name='frmAccept' method='get' action='./chess_game2.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_GAME_TXT_1", $config);?>
<?php $oR3DCQuery->TimedGameStats($gid);?>
</td>
</tr>

<tr>
<td class='row1'>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
<Input type='submit' name='cmdRevokeChlng' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
</td>
</tr>
</table>
</form>

<?php
  }

  if($PlayerType == "o"){
?>

<form name='frmAccept' method='get' action='./chess_game2.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>
<tr>
<td class='row2'>
<?php echo GetStringFromStringTable("IDS_GAME_TXT_2", $config);?>
<?php $oR3DCQuery->TimedGameStats($gid);?>
</td>
</tr>

<tr>
<td class='row1'>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
<Input type='submit' name='cmdAccept' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_ACCEPT_CHALLANGE", $config);?>' class='mainoption'>
<Input type='submit' name='cmdRevoke' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
</td>
</tr>
</table>
</form>

<?php
  }

}else{

  if($move_promotion_figur && strlen($movestr2)==5) {
    $url = "chess_game2.php?txtmovefrom=".$movefrom."&txtmoveto=".$moveto;

    if(trim(get_turn()) == "b"){
      //black	
      $queen_img = "08.gif";
      $rook_img = "09.gif";
      $bishop_img = "10.gif";
      $knight_img = "11.gif";
    }else{
      //white
      $queen_img = "02.gif";
      $rook_img = "03.gif";
      $bishop_img = "04.gif";
      $knight_img = "05.gif";
    }
?>

<table border='0' cellpadding='2' cellsapcing='0' width='80%' align='center'>
<tr><td colspan='4' class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_3", $config);?></td></tr>
<tr>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>Q&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $queen_img; ?>' border='0' alt='Queen' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>R&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $rook_img; ?>' border='0' alt='Rook' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>B&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $bishop_img; ?>' border='0' alt='Bishop' width='36' height='36'></a></td>
<td width='25%' align='center' class='row2'>
<a href='<?php echo $url; ?>N&cmdMove=Process+Move&gameid=<?php echo $gid; ?>'>
<img src='skins/<?php echo $SkinName;?>/images/chess/<?php echo $knight_img; ?>' border='0' alt='Knight' width='36' height='36'></a></td>
</tr>
</table>

<?php
  }elseif($idc != "" && $gid != ""){

    echo "<form name='frmcolor' method='get' action='./chess_game2.php'>";
    $oR3DCQuery->GetPrevGameStatus($config, $gid, $_SESSION['sid'], $_SESSION['id'], $idc, $clrl, $clrd);
    echo "<input type='hidden' name='gameid' value='".$gid."'>";
    echo "</form>";

  }else{

    if($gid != ""){
	  $completion_status = get_completion_status();

	  if($completion_status == 'W') {
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_4", $config);?></td>
</tr>
</table>
<br>

<?php	  
	  }
	  if($completion_status == 'B') {
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_5", $config);?></td>
</tr>
</table>
<br>

<?php
	}
	if($completion_status == 'D') {
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_6", $config);?></td>
</tr>
</table>
<br>

<?php
	}
	
      if($bmove_error == true){       
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_7", $config);?></td>
</tr>
</table>
<br>

<?php
       }

      if($isdraw == "IDS_USER_REQUESTED_DRAW"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_8", $config);?></td>
</tr>
</table>

<?php
      }

      if($isdraw == "IDS_DRAW_REQUESTED"){
?>

<form name='frmRevokeDraw' method='get' action='./chess_game2.php'>
<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_9", $config);?> 
<input type='submit' name='cmdRevokeDraw' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_DRAW", $config);?>' class='mainoption'>
<input type='submit' name='cmdDraw' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_ACCEPT_DRAW", $config);?>' class='mainoption'>
</td>
</tr>
</table>
<input type='hidden' name='gameid' value='<?php echo $gid;?>'>
</form>

<?php
      }

      if($isrealtime == "IDS_REAL_TIME"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_10", $config);?></td>
</tr>
</table>

<?php
        if($_SESSION['RealTimeDoOnce'] == 0){
          $_SESSION['RefreashGameOnlyOnce'] = "1";
?>

<script language="javascript">
<!-- 
location.replace('./r_index.php?gid=<?php echo $gid;?>&pn=<?php echo $_SESSION['user'];?>');
-->
</script>

<?php
          $_SESSION['RealTimeDoOnce'] = 1;
        }

      }

      if($isrealtime == "IDS_USER_REQUESTED_REAL_TIME"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_11", $config);?></td>
</tr>
</table>

<?php
        if($_SESSION['RealTimeDoOnce'] == 0){
          $_SESSION['RefreashGameOnlyOnce'] = "";
?>

<script language="javascript">
<!-- 
location.replace('./r_index.php?gid=<?php echo $gid;?>&pn=<?php echo $_SESSION['user'];?>');
-->
</script>

<?php
          $_SESSION['RealTimeDoOnce'] = 1;
        }

      }

      if($isrealtime == "IDS_REALTIME_REQUESTED"){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_12", $config);?></td>
</tr>
</table>

<?php
      }

      echo "<form name='frmcolor' method='get' action='./chess_game2.php'>";
      $oR3DCQuery->GetGameStatus($config, $gid, $_SESSION['sid'], $_SESSION['id'], get_completion_status(), $clrl, $clrd, $oR3DCQuery->GetBoardStyleByUserID($_SESSION['id']), 2, 'dd');
      echo "<input type='hidden' name='gameid' value='".$gid."'>";
      echo "</form>";

    }else{
?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_GAME_CHESS_GAME_TABLE_HEADER", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_GAME_CHESS_GAME_TABLE_TXT_1", $config);?></td>
</tr>
</table>

<?php
    }
  }

}

if($brevoked){
?>

<table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
<tr>
<td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_13", $config);?></td>
</tr>
</table>

<?php
}

$bturn1 = $oR3DCQuery->IsPlayersTurn($config, $_SESSION['id'], $gid);

if($isrealtime == "IDS_REAL_TIME"){

if($bturn1 == true && $isblack == true){
?>
<script language="JavaScript">

if (sec1<=9) { sec1 = "0" + sec1; }
document.frmcolor.clockwhite.value = ((hour1<=9) ? "0"+hour1 : hour1) + " : " + ((min1<=9) ? "0" + min1 : min1) + " : " + sec1;

stopwatchblack("Start");
</script>
<?php
}elseif($bturn1 == false && $isblack == true){
?>
<script language="JavaScript">

if (sec2<=9) { sec2 = "0" + sec2; }
document.frmcolor.clockblack.value = ((hour2<=9) ? "0"+hour2 : hour2) + " : " + ((min2<=9) ? "0" + min2 : min2) + " : " + sec2;

stopwatchwhite("Start");
</script>
<?php
}elseif($bturn1 == true && $isblack == false){
?>
<script language="JavaScript">

if (sec2<=9) { sec2 = "0" + sec2; }
document.frmcolor.clockblack.value = ((hour2<=9) ? "0"+hour2 : hour2) + " : " + ((min2<=9) ? "0" + min2 : min2) + " : " + sec2;

stopwatchwhite("Start");
</script>
<?php
}elseif($bturn1 == false && $isblack == false){
?>
<script language="JavaScript">

if (sec1<=9) { sec1 = "0" + sec1; }
   document.frmcolor.clockwhite.value = ((hour1<=9) ? "0"+hour1 : hour1) + " : " + ((min1<=9) ? "0" + min1 : min1) + " : " + sec1;

stopwatchblack("Start");
</script>
<?php
}

}

/////////////////////////////////////////////////////////////////////////
// Used to get the last move
/////////////////////////////////////////////////////////////////////////
$lmcount = count($aMoveElm);

if($lmcount != 0){

  echo "<script language=\"JavaScript\">\n";
  echo "function SetLastMoveOnChessboard(){\n";
  echo "var browserVer=parseInt(navigator.appVersion);\n"; 
  echo "if((navigator.appName == \"Microsoft Internet Explorer\" && browserVer >= 4) || navigator.appName == \"Netscape\" && browserVer >= 4){\n";

  $iz = 0;
  while($iz < $lmcount){
    echo "document.getElementById('".$aMoveElm[$iz]."').style.background = \"".$lmclr."\";\n";
    $iz++;
  }

  echo "}\n";
  echo "}\n";

  if(!$oR3DCQuery->isBoardCustomerSettingDragDrop($_SESSION['id'])){
    echo "SetLastMoveOnChessboard();\n";
  }

  echo "</script>\n";
}

/////////////////////////////////////////////////////////////////////////
?>