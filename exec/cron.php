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

  $CONFIG = realpath("../bin/config.php"); 
  require(realpath("../bin/CR3DCQuery.php"));

  ///////////////////////////////////////////////////////////////////////
  // Functions
  ///////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CheckPlayerBillingTerm
  * 
  */ 
  function CheckPlayerBillingTerm($config){

    include($config);

    $db = mysql_connect($conf['database_host'], $conf['database_login'], $conf['database_pass']) or die("Couldn't connect to the database."); 
    mysql_select_db($conf['database_name']) or die("Couldn't select the database"); 

    /************/
    //$result = mysql_query("SELECT * FROM c4m_playerorders WHERE o_orderstatus = 'p' AND DATE_FORMAT('2005-05-07 18:18:18','%Y-%m-%d') >= DATE_FORMAT(o_datedue,'%Y-%m-%d')") or die("Couldn't Check The End Dates.");
    $result = mysql_query("SELECT * FROM c4m_playerorders WHERE o_orderstatus = 'p' AND DATE_FORMAT(NOW(),'%Y-%m-%d') >= DATE_FORMAT(o_datedue,'%Y-%m-%d')") or die("Couldn't Check The End Dates.");
    /***********/

    $num = mysql_numrows($result);
  
    $i = 0;
    while($i < $num){

      $o_id = mysql_result($result,$i,"o_id");
      $o_username = mysql_result($result,$i,"o_username");
      $o_firstname = mysql_result($result,$i,"o_firstname");
      $o_lastname = mysql_result($result,$i,"o_lastname");
      $o_address = mysql_result($result,$i,"o_address");
      $o_citytown = mysql_result($result,$i,"o_citytown");
      $o_country = mysql_result($result,$i,"o_country");
      $o_provincestatearea = mysql_result($result,$i,"o_provincestatearea");
      $o_postalcode = mysql_result($result,$i,"o_postalcode");
      $o_email = mysql_result($result,$i,"o_email");
      $o_phonea = mysql_result($result,$i,"o_phonea");
      $o_phoneb = mysql_result($result,$i,"o_phoneb");
      $o_phonec = mysql_result($result,$i,"o_phonec");
      $o_redemptioncode = mysql_result($result,$i,"o_redemptioncode");
      $o_dateoforder = mysql_result($result,$i,"o_dateoforder");
      $o_paymentterm = mysql_result($result,$i,"o_paymentterm");
      $o_datepaid = mysql_result($result,$i,"o_datepaid");
      $o_datedue = mysql_result($result,$i,"o_datedue");
      $o_orderstatus = mysql_result($result,$i,"o_orderstatus");

      //echo "[".$o_id."] ".$o_username." - ".$o_datedue." - ".$o_orderstatus."<br>";

      // set order to finished
      $update = "UPDATE c4m_playerorders SET o_orderstatus = 'f' WHERE o_id=".$o_id;
      mysql_query($update, $db) or die(mysql_error());

      // create new order
      $insert = "INSERT INTO c4m_playerorders VALUES(NULL, '".$o_username."',  '".$o_firstname."', '".$o_lastname."', '".$o_address."', '".$o_citytown."', '".$o_country."', '".$o_provincestatearea."', '".$o_postalcode."', '".$o_email."', '".$o_phonea."', '".$o_phoneb."', '".$o_phonec."', '', NOW(), '".$o_paymentterm."', NULL, NULL, 'u')";
      mysql_query($insert, $db) or die(mysql_error());

      //Select the new Order ID
      $query1 = "SELECT o_id FROM c4m_playerorders WHERE o_username = '".$o_username."' AND o_orderstatus='u' ORDER BY o_id DESC LIMIT 1";
      $return1 = mysql_query($query1, $db) or die(mysql_error());
      $num1 = mysql_numrows($return1);

      $orderid = 0;

      if($num1 != 0){
         $orderid = mysql_result($return1,0,0);
      }

      // Send email
      $query2 = "SELECT * FROM c4m_emailmessageconfig";
      $return2 = mysql_query($query2, $db) or die(mysql_error());
      $num2 = mysql_numrows($return2);

      if($num2 != 0){

        $o_regover = mysql_result($return2,0,"o_regover");
        $subject = "Chess Membership Renewal";

        // configure message body
        $body = $o_regover;
        $body = str_replace("[NAME]", $o_firstname." ".$o_lastname, $body);
        $body = str_replace("[OID]", $orderid, $body);
        $body = str_replace("[UNAME]", $o_username, $body);

        //Instantiate theCR3DCQuery Class
        $oR3DCQuery = new CR3DCQuery($config);
        $oR3DCQuery->SendEmail($o_email, $conf['registration_email'], $conf['site_name'], $subject, $body);
        $oR3DCQuery->Close();
        unset($oR3DCQuery);

      }

      $i++;

    }

  }


  ///////////////////////////////////////////////////////////////////////
  // Main script
  ///////////////////////////////////////////////////////////////////////
  CheckPlayerBillingTerm($CONFIG);

  ///////////////////////////////////////////////////////////////////////   
  // Instantiate the CR3DCQuery Class
  ///////////////////////////////////////////////////////////////////////
  $oR3DCQuery = new CR3DCQuery($CONFIG);

  ///////////////////////////////////////////////////////////////////////   
  // Check And Create ELO Ratings
  ///////////////////////////////////////////////////////////////////////
  if($oR3DCQuery->ELOIsActive()){
    $oR3DCQuery->ELOCreateRatings();
  }

  ///////////////////////////////////////////////////////////////////////   
  // Check And Manage Game TimeOuts
  ///////////////////////////////////////////////////////////////////////
  $oR3DCQuery->MangeGameTimeOuts();

  ///////////////////////////////////////////////////////////////////////
  // Check And Manage Abandoned Players
  ///////////////////////////////////////////////////////////////////////
  $oR3DCQuery->ManageAbandonedPlayers();

  ///////////////////////////////////////////////////////////////////////   
  // Script Clean Up
  ///////////////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  unset($oR3DCQuery);

?>