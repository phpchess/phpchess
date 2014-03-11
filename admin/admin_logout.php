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

  ini_set("output_buffering","1");
  session_start();

  if(isset($_SESSION['UNAME'])){
    unset($_SESSION['UNAME']);
  }

  if(isset($_SESSION['LOGIN'])){ 
    unset($_SESSION['LOGIN']);
  }

  session_destroy();

  header("Location: ./index.php"); 

?>