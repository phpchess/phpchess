<?php 

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

require('php-captcha.inc.php');
// define fonts
$aFonts = array('fonts/VeraBd.ttf');
// create new image
$oPhpCaptcha = new PhpCaptcha($aFonts, 150, 30);
$oPhpCaptcha->SetBackgroundImages('val.jpg');
$oPhpCaptcha->SetWidth(150);
$oPhpCaptcha->SetHeight(30);
$oPhpCaptcha->SetNumChars(5);
$oPhpCaptcha->SetMinFontSize(10);
$oPhpCaptcha->SetMinFontSize(12);
$oPhpCaptcha->UseColour(True);
$oPhpCaptcha->DisplayShadow(False);
$oPhpCaptcha->SetNumLines(250);
$oPhpCaptcha->Create();

?>