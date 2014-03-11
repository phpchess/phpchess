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

  require("./bin/CChess.php");

  $config = "./bin/config.php";

  //Instantiate the CChess Class
  $oChess = new CChess($config);
  $sid = $oChess->delete_session($config, $_SESSION['sid']);
  unset($oChess);

  $_SESSION = array();

  if(isset($_COOKIE['TestCookie'])){ 
    setcookie("TestCookie", '', time()-360000);
  }

  session_destroy();

  header("Location: ./index.php"); 

?>