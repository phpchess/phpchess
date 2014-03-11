<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

  ////////////////////////////////////////////////////////////////////////
  // Javascript Includes
  ////////////////////////////////////////////////////////////////////////

  echo "<script Language=\"JavaScript\">\n";
  echo "<!--\n";

  /////////////////////////////////////////
  // PopupHelpWin
  //

  $nWPopupHelpWin = 500;
  $nHPopupHelpWin = 400;

  if(defined('CFG_POPUPHELPWIN_WIDTH') && defined('CFG_POPUPHELPWIN_HEIGHT')){
    $nWPopupHelpWin = CFG_POPUPHELPWIN_WIDTH;
    $nHPopupHelpWin = CFG_POPUPHELPWIN_HEIGHT;
  }

  echo "function PopupHelpWin(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"Help\",\"width=".$nWPopupHelpWin.",height=".$nHPopupHelpWin.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"?\"; hWnd.location.href=url;}}\n";
  echo "}\n\n";
  
  /////////////////////////////////////////
  // PopupWindowRT
  //

  $nWPopupWindowRT = 580;
  $nHPopupWindowRT = 400;

  if(defined('CFG_POPUPWINDOWRT_WIDTH') && defined('CFG_POPUPWINDOWRT_HEIGHT')){
    $nWPopupWindowRT = CFG_POPUPWINDOWRT_WIDTH;
    $nHPopupWindowRT = CFG_POPUPWINDOWRT_HEIGHT;
  }

  echo "function PopupWindowRT(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5(time()."".$_SESSION['id'])."\",\"width=".$nWPopupWindowRT.",height=".$nHPopupWindowRT.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"home\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PopupWindow
  //

  $nWPopupWindow = 500;
  $nHPopupWindow = 400;

  if(defined('CFG_POPUPWINDOW_WIDTH') && defined('CFG_POPUPWINDOW_HEIGHT')){
    $nWPopupWindow = CFG_POPUPWINDOW_WIDTH;
    $nHPopupWindow = CFG_POPUPWINDOW_HEIGHT;
  }

  $bPopupWindowDefault = true;
  if(defined('CFG_JAVASCRIPT_VAR')){

    $bPopupWindowDefault = false;

    if(CFG_JAVASCRIPT_VAR == 1){

      echo "function PopupWindow(webpage){\n";
      echo "  var url = webpage;\n";
      echo "  var hWnd = window.open(url,\"".md5(time())."\",\"".$tconfig[4]."\");\n";
      echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name = \"home\"; hWnd.location.href=url; }}\n";
      echo "}\n\n";

    }

  }

  if($bPopupWindowDefault){

    echo "function PopupWindow(webpage){\n";
    echo "  var url = webpage;\n";
    echo "  var hWnd = window.open(url,\"PHPChess\",\"width=".$nWPopupWindow.",height=".$nHPopupWindow.",resizable=no,scrollbars=yes,status=yes\");\n";
    echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"home\"; hWnd.location.href=url; }}\n";
    echo "}\n\n";

  }

  /////////////////////////////////////////
  // PopupWindowTM
  //

  $nWPopupWindowTM = 900;
  $nHPopupWindowTM = 600;

  if(defined('CFG_POPUPWINDOWTM_WIDTH') && defined('CFG_POPUPWINDOWTM_HEIGHT')){
    $nWPopupWindowTM = CFG_POPUPWINDOWTM_WIDTH;
    $nHPopupWindowTM = CFG_POPUPWINDOWTM_HEIGHT;
  }

  echo "function PopupWindowTM(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5(time()."".$_SESSION['id'])."\",\"width=".$nWPopupWindowTM.",height=".$nHPopupWindowTM.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"home\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PlayerChat
  //

  $nWPlayerChat = 900;
  $nHPlayerChat = 400;

  if(defined('CFG_PLAYERCHAT_WIDTH') && defined('CFG_PLAYERCHAT_HEIGHT')){
    $nWPlayerChat = CFG_PLAYERCHAT_WIDTH;
    $nHPlayerChat = CFG_PLAYERCHAT_HEIGHT;
  }

  echo "function PlayerChat(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5($_SESSION['id'])."\",\"width=".$nWPlayerChat.",height=".$nHPlayerChat.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"PlayerChat\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // ViewLiveGame
  //

  $nWViewLiveGame = 500;
  $nHViewLiveGame = 430;

  if(defined('CFG_VIEWLIVEGAME_WIDTH') && defined('CFG_VIEWLIVEGAME_HEIGHT')){
    $nWViewLiveGame = CFG_VIEWLIVEGAME_WIDTH;
    $nHViewLiveGame = CFG_VIEWLIVEGAME_HEIGHT;
  }

  echo "function ViewLiveGame(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"LiveGame\",\"width=".$nWViewLiveGame.",height=".$nHViewLiveGame.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"?\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // ViewOldGame
  //

  $nWViewOldGame = 500;
  $nHViewOldGame = 430;

  if(defined('CFG_VIEWOLDGAME_WIDTH') && defined('CFG_VIEWOLDGAME_HEIGHT')){
    $nWViewOldGame = CFG_VIEWOLDGAME_WIDTH;
    $nHViewOldGame = CFG_VIEWOLDGAME_HEIGHT;
  }

  echo "function ViewOldGame(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"OldGame\",\"width=".$nWViewOldGame.",height=".$nHViewOldGame.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name = \"?\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PopupWindowMP
  //

  $nWPopupWindowMP = 910;
  $nHPopupWindowMP = 700;

  if(defined('CFG_POPUPWINDOWMP_WIDTH') && defined('CFG_POPUPWINDOWMP_HEIGHT')){
    $nWPopupWindowMP = CFG_POPUPWINDOWMP_WIDTH;
    $nHPopupWindowMP = CFG_POPUPWINDOWMP_HEIGHT;
  }

  echo "function PopupWindowMP(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".$RandName1."\",\"width=".$nWPopupWindowMP.",height=".$nHPopupWindowMP.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"mphome\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PopupWindowP
  //

  $nWPopupWindowP = 900;
  $nHPopupWindowP = 600;

  if(defined('CFG_POPUPWINDOWP_WIDTH') && defined('CFG_POPUPWINDOWP_HEIGHT')){
    $nWPopupWindowP = CFG_POPUPWINDOWP_WIDTH;
    $nHPopupWindowP = CFG_POPUPWINDOWP_HEIGHT;
  }

  echo "function PopupWindowP(webpage){";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".$RandName2."\",\"width=".$nWPopupWindowP.",height=".$nHPopupWindowP.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"mphome\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PopupPGNGame
  //

  $nWPopupPGNGame = 610;
  $nHPopupPGNGame = 600;

  if(defined('CFG_POPUPPGNGAME_WIDTH') && defined('CFG_POPUPPGNGAME_HEIGHT')){
    $nWPopupPGNGame = CFG_POPUPPGNGAME_WIDTH;
    $nHPopupPGNGame = CFG_POPUPPGNGAME_HEIGHT;
  }

  echo "function PopupPGNGame(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5(time())."\",\"width=".$nWPopupPGNGame.",height=".$nHPopupPGNGame.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"home\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";


  /////////////////////////////////////////
  // PopupUserNameChange
  //

  $nWUserNameChange = 500;
  $nHUserNameChange = 400;

  if(defined('CFG_POPUPUSERNAMECHANGE_WIDTH') && defined('CFG_POPUPUSERNAMECHANGE_HEIGHT')){
    $nWUserNameChange = CFG_POPUPUSERNAMECHANGE_WIDTH;
    $nHUserNameChange = CFG_POPUPUSERNAMECHANGE_HEIGHT;
  }

  echo "function PopupUserNameChange(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"UserNameChange\",\"width=".$nWUserNameChange.",height=".$nHUserNameChange.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"?\"; hWnd.location.href=url;}}\n";
  echo "}\n\n";


  echo "";

  echo "//-->\n";
  echo "</script>\n";

?>

