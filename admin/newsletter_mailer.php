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

  // This is the vairable that sets the root path of the website
  $Root_Path = "../";
  $config = $Root_Path."bin/config.php";

  // Includes
  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CAdmin.php");
  require($Root_Path."bin/config.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  /**********************************************************************
  * TrimRSlash
  *
  */
  function TrimRSlash($URL){

    $nLength = strlen($URL);
    return ($URL{$nLength - 1} == '/') ? substr($URL, 0, $nLength - 1) : $URL;

  }

  // Posted variables
  $txtSubject = $_POST['txtSubject'];
  $elm2 = $_POST['elm2'];

  // Set the body of the news letter
  $bodyp1 = "<html><head><title>".$txtSubject."</title></head><body>".stripslashes($elm2)."<br><br>".GetStringFromStringTable("IDS_NEWSLETTER_MAILER_TXT_1", $config)."<br><a href='".TrimRSlash($conf['site_url'])."/chess_close.php'>".TrimRSlash($conf['site_url'])."/chess_close.php</a></body></html>";

  $From = $conf['registration_email'];
  $Name = $conf['site_name'];

  //Instantiate theCR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);
  $aEmail = $oR3DCQuery->ListAvailablePlayersEmail($config);

  $nCount = count($aEmail);
  $i=0;
  while($i < $nCount){

    $To = $aEmail[$i];
    $oR3DCQuery->SendEmail($To, $From, $Name, $txtSubject, $bodyp1);

    $i++;
  }

  $oR3DCQuery->Close();
  unset($oR3DCQuery);

  header('Location: ./create_newsletter.php');
?>