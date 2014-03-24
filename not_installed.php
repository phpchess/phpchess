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

  $isappinstalled = 0;
  include("./includes/install_check.php");

  $Root_Path = "./";
  $Page_Name = "not_installed.php";
?>

<html>
<head>
<title>PHPChess Not Installed</title>
</head>
<body>

<table width="96%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td>
Thank you for visiting our site; unfortunately phpChess is not currently installed. Please come back at a later date.
</td>
</tr>
</table>

</body>
</html>

<?php
  ob_end_flush();
?>