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
  // PopupWindowTInfo
  //

  $nWPopupWindowTInfo = 600;
  $nHPopupWindowTInfo = 580;

  if(defined('CFG_POPUPWINDOWTINFO_WIDTH') && defined('CFG_POPUPWINDOWTINFO_HEIGHT')){
    $nWPopupWindowTInfo = CFG_POPUPWINDOWTINFO_WIDTH;
    $nHPopupWindowTInfo = CFG_POPUPWINDOWTINFO_HEIGHT;
  }

  echo "function PopupWindowTInfo(webpage){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5(time()."".$_SESSION['id'])."\",\"width=".$nWPopupWindowTInfo.",height=".$nHPopupWindowTInfo.",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener = self; window.name=\"Tournament\"; hWnd.location.href=url; }}\n";
  echo "}\n\n";

  /////////////////////////////////////////
  // PopupWindowActivity
  //
  echo "function PopupWindowActivity(webpage, width, height){\n";
  echo "  var url = webpage;\n";
  echo "  var hWnd = window.open(url,\"".md5(time())."\",\"width=\"+ width +\",height=\"+ height +\",resizable=no,scrollbars=yes,status=yes\");\n";
  echo "  if(hWnd != null){ if(hWnd.opener == null){ hWnd.opener=self; window.name=\"home\"; hWnd.location.href=url; }}\n";
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

  echo "";

  echo "//-->\n";
  echo "</script>\n";

?>

