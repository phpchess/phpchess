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

  session_start();

  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  $clrl = str_replace("#", "", $_SESSION['lcolor']);
  $clrd = str_replace("#", "", $_SESSION['dcolor']); 

  $Code = $_GET['scode'];
  $color = $_GET['color'];
  $turn = $_GET['turn'];

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  $nChessImgSize = 38;
  if(defined('CFG_CHESSBOARD_IMG_SIZE')){
    $nChessImgSize = CFG_CHESSBOARD_IMG_SIZE;
  }


?>

<html>
<head>
<title></title>
</head>
<body>

<style type="text/css">
.ChessBoardXL{
  BORDER-RIGHT: 1px solid; BORDER-TOP: 1px solid; LEFT: 0px; BACKGROUND-IMAGE: url(./bin/img_chessboard.php?w=<?php echo $clrl;?>&b=<?php echo $clrd;?>&imgsize=<?php echo $nChessImgSize;?>); BORDER-LEFT: 1px solid; WIDTH: <?php echo $nChessImgSize * 8;?>px; BORDER-BOTTOM: 1px solid; POSITION: absolute; TOP: 0px; HEIGHT: <?php echo $nChessImgSize * 8;?>px;
}

.toolbar1{
  BORDER-RIGHT: 1px solid; BORDER-TOP: 1px solid; LEFT: 0px; BORDER-LEFT: 1px solid; WIDTH: <?php echo $nChessImgSize * 8;?>px; BORDER-BOTTOM: 1px solid; POSITION: absolute; TOP: <?php echo ($nChessImgSize * 8) + 10;?>px; HEIGHT: <?php echo $nChessImgSize * 8;?>px;
}

.toolbar2{
  BORDER-RIGHT: 1px solid; BORDER-TOP: 1px solid; LEFT: 0px; BORDER-LEFT: 1px solid; WIDTH: <?php echo $nChessImgSize * 8;?>px; BORDER-BOTTOM: 1px solid; POSITION: absolute; TOP: <?php echo ($nChessImgSize * 8) *2;?>px; HEIGHT: <?php echo $nChessImgSize * 8;?>px;
}
</style>

<div class='ChessBoardXL' id='grid'></div>

<div class='toolbar1'>
1: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/bbw.gif" name=_1_>
2: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/bkw.gif" name=_2_>
3: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/bnw.gif" name=_3_>
4: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/bpw.gif" name=_4_> <BR><BR>
5: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/bqw.gif" name=_5_>
6: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/brw.gif" name=_6_>
7: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wbw.gif" name=_7_>
8: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wkw.gif" name=_8_> <BR><BR>
9: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wnw.gif" name=_9_>
10: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wpw.gif" name=_10_>
11: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wqw.gif" name=_11_>
12: <IMG src="./skins/<?php echo $SkinName;?>/img_chess/wrw.gif" name=_12_>
</div>

<div class='toolbar2'>
<TEXTAREA id=details-panel rows=4 cols=34><?php echo $Code;?></TEXTAREA>
<BUTTON title="Clear the game grid." onclick=clearCanvas(); alt="Clear the game grid.">Clear</BUTTON>
<BUTTON title="Load the icon text onto the game grid." onclick=resetCanvas(); alt="Load the icon text onto the game grid.">Load</BUTTON>
</div>

<SCRIPT src="./includes/wz_dragdrop.js" type=text/javascript></SCRIPT>

<SCRIPT type=text/javascript>

var minX = 0;
var minY = 0;
var maxX = <?php echo $nChessImgSize * 7;?>;
var maxY = <?php echo $nChessImgSize * 7;?>;

function DecodeChessPiecePosition2(x, y){

  var strPositionx = "";
  var strPositiony = "";
  var strPosX = x;
  var strPosY = y;

  switch(strPosX){

    // handle the column positions
    case <?php echo $nChessImgSize * 0;?>:
      strPositionx = "h";
      break;

    case <?php echo $nChessImgSize * 1;?>:
      strPositionx = "g";
      break;

    case <?php echo $nChessImgSize * 2;?>:
      strPositionx = "f";
      break;

    case <?php echo $nChessImgSize * 3;?>:
      strPositionx = "e";
      break;

    case <?php echo $nChessImgSize * 4;?>:
      strPositionx = "d";
      break;

    case <?php echo $nChessImgSize * 5;?>:
      strPositionx = "c";
      break;

    case <?php echo $nChessImgSize * 6;?>:
      strPositionx = "b";
      break;

    case <?php echo $nChessImgSize * 7;?>:
      strPositionx = "a";
      break;

  }

  switch(strPosY){

    // handle the row positions
    case <?php echo $nChessImgSize * 7;?>:
      strPositiony = "8";
      break;

    case <?php echo $nChessImgSize * 6;?>:
      strPositiony = "7";
      break;

    case <?php echo $nChessImgSize * 5;?>:
      strPositiony = "6";
      break;

    case <?php echo $nChessImgSize * 4;?>:
      strPositiony = "5";
      break;

    case <?php echo $nChessImgSize * 3;?>:
      strPositiony = "4";
      break;

    case <?php echo $nChessImgSize * 2;?>:
      strPositiony = "3";
      break;

    case <?php echo $nChessImgSize * 1;?>:
      strPositiony = "2";
      break;

    case <?php echo $nChessImgSize * 0;?>:
      strPositiony = "1";
      break;

  }

  return strPositionx + "" + strPositiony;

}


function DecodeChessPiecePosition(x, y){

  var strPositionx = "";
  var strPositiony = "";
  var strPosX = x;
  var strPosY = y;

  switch(strPosX){

    // handle the column positions
    case <?php echo $nChessImgSize * 0;?>:
      strPositionx = "a";
      break;

    case <?php echo $nChessImgSize * 1;?>:
      strPositionx = "b";
      break;

    case <?php echo $nChessImgSize * 2;?>:
      strPositionx = "c";
      break;

    case <?php echo $nChessImgSize * 3;?>:
      strPositionx = "d";
      break;

    case <?php echo $nChessImgSize * 4;?>:
      strPositionx = "e";
      break;

    case <?php echo $nChessImgSize * 5;?>:
      strPositionx = "f";
      break;

    case <?php echo $nChessImgSize * 6;?>:
      strPositionx = "g";
      break;

    case <?php echo $nChessImgSize * 7;?>:
      strPositionx = "h";
      break;

  }

  switch(strPosY){

    // handle the row positions
    case <?php echo $nChessImgSize * 7;?>:
      strPositiony = "1";
      break;

    case <?php echo $nChessImgSize * 6;?>:
      strPositiony = "2";
      break;

    case <?php echo $nChessImgSize * 5;?>:
      strPositiony = "3";
      break;

    case <?php echo $nChessImgSize * 4;?>:
      strPositiony = "4";
      break;

    case <?php echo $nChessImgSize * 3;?>:
      strPositiony = "5";
      break;

    case <?php echo $nChessImgSize * 2;?>:
      strPositiony = "6";
      break;

    case <?php echo $nChessImgSize * 1;?>:
      strPositiony = "7";
      break;

    case <?php echo $nChessImgSize * 0;?>:
      strPositiony = "8";
      break;

  }

  return strPositionx + "" + strPositiony;

}


function my_PickFunc(){
  var i = 0;
  var name = dd.obj.name;

  if(dd.obj.x > maxX){

    // Increase copies of picked item.
    name = dd.Int(name.substring(1, name.lastIndexOf('_')));
    dd.elements['_' + name + '_'].copy(1);

  }else{

    var y = dd.obj.y;
    var x = dd.obj.x;
    x = snapToGrid(x, true);
    y = snapToGrid(y, false);
  
    //parent.document.frmchess.txtFrom.value = "Move From X:" + x + " Y:" + y;

<?php if($turn == 1){?>

  <?php if($color == 'w'){?>
    parent.document.frmcolor.txtmovefrom.value = DecodeChessPiecePosition(x, y);
  <?php }else{?>
    parent.document.frmcolor.txtmovefrom.value = DecodeChessPiecePosition2(x, y);
  <?php }?>

<?php }?>


  }

}


function my_DropFunc(){

  var y = dd.obj.y;
  var x = dd.obj.x;

  x = snapToGrid(x, true);
  y = snapToGrid(y, false);

  // Let the dropped item snap to position
  dd.obj.moveTo(x, y);

  //parent.document.frmchess.txtTo.value = "Move To X:" + x + " Y:" + y;

<?php if($turn == 1){?>

  <?php if($color == 'w'){?>
    parent.document.frmcolor.txtmoveto.value = DecodeChessPiecePosition(x, y);
  <?php }else{?>
    parent.document.frmcolor.txtmoveto.value = DecodeChessPiecePosition2(x, y);
  <?php }?>

  parent.document.frmcolor.cmdMove.value = "process move";
  parent.document.frmcolor.submit();
  
<?php }?>

}


function snapToGrid(value, isX){

  if(isX){

    if(value < minX){
      value = minX;
    }else if(value > maxX){
      value = maxX;
    }else{
      value = Math.round(value / <?php echo $nChessImgSize;?>) * <?php echo $nChessImgSize;?>;
    }

  }else{

    if(value < minY){
      value = minY;
    }else if(value > maxY){
      value = maxY;
    }else{
      value = Math.round(value / <?php echo $nChessImgSize;?>) * <?php echo $nChessImgSize;?>;
    }

  }

  return value;

}


function clearCanvas(){
  var element;

  for(var i = 0; i < dd.elements.length; i++){

    element = dd.elements[i];

    if(element.x <= maxX){

      element.hide();

    }

  }

  document.getElementById('details-panel').value = '';

}


function resetCanvas(){

  var items;
  var item;
  var details = '';
  var newX = 0;
  var newY = 0;
  var index = 0;
  var lines = document.getElementById('details-panel').value.split('_');

  clearCanvas();

  for(var i = 0; i < lines.length; i++){

    if(lines[i].length > 0){

      items = lines[i].split('|');

      if(items.length == 3){

        index = parseInt(items[0]);
        newX = parseInt(items[1]);
        newY = parseInt(items[2]);

        if(index > 0 && newX >= minX && newY >= minY){

          item = dd.elements['_'+ index + '_'];

          if(item !== null){

            newX = snapToGrid(newX, true);
            newY = snapToGrid(newY, false);
            item.copy(1);
            item.copies[item.copies.length - 1].moveTo(newX, newY);
            lines[i] = index + '|' + newX + '|' + newY;

          }

        }

      }

      details += lines[i] + '_';

    }

  }

  document.getElementById('details-panel').value = details;

}


function toggleGrid(){

  var button = document.getElementById('grid-button');

  if(button.getAttribute('title').indexOf('Hide') > -1) {

    button.setAttribute('title', 'Show the board.');
    button.setAttribute('alt', 'Show the board.');
    button.childNodes[0].nodeValue =  "Show Board";
    document.getElementById('grid').setAttribute('style', 'background-image: none;');

  }else{

    button.setAttribute('title', 'Hide the board.');
    button.setAttribute('alt', 'Hide the board.');
    button.childNodes[0].nodeValue =  "Hide Board";
    document.getElementById('grid').setAttribute('style', 'background-image: url(./bin/img_chessboard.php?w=<?php echo $clrl;?>&b=<?php echo $clrd;?>&imgsize=<?php echo $nChessImgSize;?>)');

  }

}


SET_DHTML(CURSOR_MOVE, TRANSPARENT, DETACH_CHILDREN, NO_ALT, "_1_", "_2_", "_3_", "_4_", "_5_", "_6_", "_7_", "_8_", "_9_", "_10_", "_11_", "_12_");
resetCanvas();

</SCRIPT>

</body>
</html>