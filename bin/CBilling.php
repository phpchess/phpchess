<?php
////////////////////////////////////////////////////////////////////////////////
//
// (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
// All rights reserved. Please observe respective copyrights.
// phpChess - Chess at its best
// you can find us at http://www.phpchess.com. 
//
////////////////////////////////////////////////////////////////////////////////

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

class CBilling{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkBilling;
  var $mail;
  var $ename;
  var $adl;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CBilling (Constructor)
  *
  */
  function CBilling($ConfigFile){

    ////////////////////////////////////////////////////////////////////////////
    // Sets the chess config file location (absolute location on the server)
    ////////////////////////////////////////////////////////////////////////////
    $this->ChessCFGFileLocation  = $ConfigFile;
    ////////////////////////////////////////////////////////////////////////////

    include($ConfigFile);

    $this->host = $conf['database_host'];
    $this->dbnm = $conf['database_name'];
    $this->user = $conf['database_login'];
    $this->pass = $conf['database_pass'];
    $this->adl = $conf['absolute_directory_location'];
    $this->mail = $conf['registration_email'];
    $this->ename = $conf['site_name'];

    $this->linkBilling = mysql_connect($this->host, $this->user, $this->pass);
    mysql_select_db($this->dbnm);

    if(!$this->linkBilling){
      die("CBilling.php: ".mysql_error());
    }

  }


  /**********************************************************************
  * GetStringFromStringTable
  *
  */
  function GetStringFromStringTable($strTag){

    include($this->ChessCFGFileLocation);

    // Get Server Language
    $LanguageFile = "";

    if(isset($_SESSION['language'])){
 
      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$_SESSION['language'];
      }

    }else{

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){

        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".mysql_result($return, 0, "o_languagefile");

      }

    }

    $text = "Error";

    if($LanguageFile != ""){

      // Open the language file an get the contents
      $lines = file($LanguageFile);
 
      // Search for the key
      for($x=1; $x<=sizeof($lines); $x++){
        //echo "Line $x: " . $lines[$x-1] . "<br>";

        if (preg_match("/\b".$strTag."\b/i", $lines[$x-1])){
          // We found the key
    
          list($Key, $strText, $junk) = preg_split("/\|\|/", $lines[$x-1], 3);
		      $strText = utf8_encode($strText);
          $text = trim($strText);

          // Exit loop
          break;

        }

      }

    }

    //Parse tags

    $aTags = array("['avatar_image_width']", "['avatar_image_height']", "['user_name']");
    $aReplace = array($conf['avatar_image_width'], $conf['avatar_image_height'], $_SESSION['user']);
    $text = str_replace($aTags, $aReplace, $text);

    return $text;
  
  }


  /**********************************************************************
  * IsPaymentEnabled
  *
  */
  function IsPaymentEnabled(){

    $query = "SELECT * FROM c4m_paypal LIMIT 1";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $bEnabled = false;

    if($num != 0){

      $i = 0;
      $a_requirespayment = mysql_result($return,$i,"a_requirespayment");

      if($a_requirespayment == '1'){
        $bEnabled = true;
      }

    }

    return $bEnabled;

  }


  /**********************************************************************
  * CheckRedemtionCode
  *
  */
  function CheckRedemtionCode($Code, $SUserName){

    $bIsGood = false;

    $Code1 = $Code;
    $Code = base64_decode($Code);

    $ncharcount = substr_count($Code, "|");

    if($ncharcount == 2){

      $this->DecodeRedemtionCode($Code, $UserName, $Type, $Date);
      
      // Test the redemption code type
      $isGood = false;

      switch($Type){
        case "M":
          $isGood = true;
          break;

        case "S":
          $isGood = true;
          break;

        case "Y":
          $isGood = true;
          break;
      }

      $bMultiUser = false;

      //Check if the redemption code is multi user
      $query = "SELECT * FROM c4m_multiuserredemptioncode WHERE o_redemptioncode like '".$Code1."'";
      $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
      $num = mysql_numrows($return);
        
      if($num != 0){
         $bMultiUser = true;
      }

      if(($isGood == true && $UserName == $SUserName && $Date != "") || $bMultiUser == true){

        //Check if the redemption code has been used already
        $query = "SELECT * FROM c4m_playerorders WHERE o_redemptioncode = '".$Code1."'";
        $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
        $num = mysql_numrows($return);

        if($num == 0){
          $bIsGood = true;
        }

        //Check if the redemption code is multi user
        $query = "SELECT * FROM c4m_multiuserredemptioncode WHERE o_redemptioncode like '".$Code1."'";
        $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
        $num = mysql_numrows($return);
        
        if($num != 0){
          $bIsGood = true;
        }

      }

    }

    return $bIsGood;

  }

  /**********************************************************************
  * DecodeRedemtionCode
  *
  */
  function DecodeRedemtionCode($Code, &$UserName, &$Type, &$Date){

    list($UserName, $Type, $Date) = preg_split("/\|/", $Code, 3);

  }


  /**********************************************************************
  * GetDefinedPrice
  *
  */
  function GetDefinedPrice(){

    $query = "SELECT * FROM c4m_paypalaccount LIMIT 1";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $nPrice = 0;

    if($num != 0){

      $i = 0;
      $nPrice = mysql_result($return,$i,"p_monthlycharge");

    }

    return $nPrice;

  }


  /**********************************************************************
  * GetPaypalInfo
  *
  */
  function GetPaypalInfo(&$email, &$currency){

    $query = "SELECT * FROM c4m_paypalaccount LIMIT 1";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $email = mysql_result($return,$i,"p_email");
      $currency = mysql_result($return,$i,"p_currency");
    }

  }


  /**********************************************************************
  * CreateNewBill
  *
  */
  function CreateNewBill($playerUName, $firstname, $lastname, $address, $citytown, $country, $provincestatearea, $postalcode, $email, $phonea, $phoneb, $phonec, $redemptioncode, $paymentterm){

    $insert = "INSERT INTO c4m_playerorders VALUES(NULL, '".$playerUName."', '".$firstname."', '".$lastname."', '".$address."', '".$citytown."', '".$country."', '".$provincestatearea."', '".$postalcode."', '".$email."', '".$phonea."', '".$phoneb."', '".$phonec."', '".$redemptioncode."', NOW(), '".$paymentterm."', NULL, NULL, 'u')";
    mysql_query($insert, $this->linkBilling) or die(mysql_error());

    //Select the new Order ID
    $query = "SELECT LAST_INSERT_ID()";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $orderid = 0;

    if($num != 0){
       $orderid = mysql_result($return,0,0);
    }

    // Send email
    $subject = $this->GetStringFromStringTable("IDS_CBILLING_EMAIL_1_1");

    $aTags = array("['playerUName']", "['orderid']");
    $aReplace = array($playerUName, $orderid);

    $body = str_replace($aTags, $aReplace, $this->GetStringFromStringTable("IDS_CBILLING_EMAIL_1_2"));

    $this->SendEmail($this->mail, $email, $firstname." ".$lastname, $subject, $body);

    return $orderid;

  }


  /**********************************************************************
  * GetOrders
  * Params: Order Status
  * Return: Prints the orders
  */
  function GetOrders($OrderStatus){

    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_orderstatus='".$OrderStatus."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = trim(mysql_result($return, $i, "o_id"));
        $o_username = trim(mysql_result($return, $i, "o_username"));
        $o_firstname = trim(mysql_result($return, $i, "o_firstname"));
        $o_lastname = trim(mysql_result($return, $i, "o_lastname"));
        $o_address = trim(mysql_result($return, $i, "o_address"));
        $o_citytown = trim(mysql_result($return, $i, "o_citytown"));
        $o_country = trim(mysql_result($return, $i, "o_country"));
        $o_provincestatearea = trim(mysql_result($return, $i, "o_provincestatearea"));
        $o_postalcode = trim(mysql_result($return, $i, "o_postalcode"));
        $o_email = trim(mysql_result($return, $i, "o_email"));
        $o_phonea = trim(mysql_result($return, $i, "o_phonea"));
        $o_phoneb = trim(mysql_result($return, $i, "o_phoneb"));
        $o_phonec = trim(mysql_result($return, $i, "o_phonec"));
        $o_redemptioncode = trim(mysql_result($return, $i, "o_redemptioncode"));
        $o_dateoforder = trim(mysql_result($return, $i, "o_dateoforder"));
        $o_paymentterm = trim(mysql_result($return, $i, "o_paymentterm"));
        $o_datepaid = trim(mysql_result($return, $i, "o_datepaid"));
        $o_datedue = trim(mysql_result($return, $i, "o_datedue"));
        $o_orderstatus = trim(mysql_result($return, $i, "o_orderstatus"));

        echo "<form name='frmBilling".$o_id."' method='post' action='./manage_new_bills.php'>";

        // Skin table settings
        if(defined('CFG_GETORDERS_TABLE1_WIDTH') && defined('CFG_GETORDERS_TABLE1_BORDER') && defined('CFG_GETORDERS_TABLE1_CELLPADDING') && defined('CFG_GETORDERS_TABLE1_CELLSPACING') && defined('CFG_GETORDERS_TABLE1_ALIGN')){
          echo "<table border='".CFG_GETORDERS_TABLE1_BORDER."' align='".CFG_GETORDERS_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETORDERS_TABLE1_CELLPADDING."' cellspacing='".CFG_GETORDERS_TABLE1_CELLSPACING."' width='".CFG_GETORDERS_TABLE1_WIDTH."'>";
        }else{
          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
        }

        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_1")."</b>".$o_id."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_2")."</b></td><td class='row2'>".$o_firstname." ".$o_lastname."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_3")."</b></td><td class='row2'>".$o_email."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_4")."</b></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td align='left' colspan='2' class='row2'>";
        echo "".$o_firstname." ".$o_lastname."<br>";
        echo "".$o_address."<br>";
        echo "".$o_citytown.", ".$o_provincestatearea.", ".$o_country."<br>";
        echo "".$o_postalcode."<br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_5")."</b></td><td class='row2'>".$o_username."</td>";
        echo "</tr>";

        if($o_redemptioncode != ""){

          $this->DecodeRedemtionCode(base64_decode($o_redemptioncode), $UserName, $Type, $Date);

          $type = "";
          switch($Type){

            case "M":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_6")."";
              break;

            case "S":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_7")."";
              break;

            case "Y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_8")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_9")."</td><td class='row2'>".$o_redemptioncode."</td>";
          echo "</tr>";
          echo "<tr>";
          echo "<td class='row2' colspan='2'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_10")." ".$UserName." ".$type.".</td>";
          echo "</tr>";

        }else{

          $nPrice = $this->GetDefinedPrice();

          if($o_paymentterm == 's'){
            $OrderCost = $nPrice * 6;
          }elseif($o_paymentterm == 'y'){
            $OrderCost = $nPrice * 12;
          }else{
            $OrderCost = $nPrice * 1;
          }

          $type = "";
          switch($o_paymentterm){

            case "m":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_11")."";
              break;

            case "s":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_12")."";
              break;

            case "y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_13")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_14")."</b></td><td class='row2'>".$type."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_15")."</b></td><td class='row2'>$".number_format($nPrice,2,'.', '')."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_16")."</b></td><td class='row2'>$".number_format($OrderCost,2,'.', '')."</td>";
          echo "</tr>";

        }

        echo "<tr>";
        echo "<td class='row1' colspan='2'>";
        echo "<input type='hidden' name='txtID' value='".$o_id."'>";
        echo "<input type='submit' name='cmd' value='".$this->GetStringFromStringTable("IDS_CBILLING_BTN_1")."' class='mainoption'>";
        echo "<input type='submit' name='cmdcancel' value='".$this->GetStringFromStringTable("IDS_CBILLING_BTN_2")."' class='mainoption'>";
        echo "</td>";
        echo "</tr>";

        echo "</table>";

        echo "</form>";
        echo "<br>";

        $i++;

       }

    }else{

      echo "<table border='0' align='center'>";
      echo "<tr><td>";
      echo "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_17")."";
      echo "</td></tr>";
      echo "</table>";

    }

  }


  /**********************************************************************
  * CancelOrder
  * Params: Order ID
  * Return: Cancels the order
  */
  function CancelOrder($OrderID){

    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_id=".$OrderID."";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $o_username = trim(mysql_result($return, 0, "o_username"));

      //Delete Username
      $delete = "DELETE FROM pendingplayer WHERE userid='".$o_username."'";
      mysql_query($delete, $this->linkBilling) or die(mysql_error());

      //Delete bill
      $delete = "DELETE FROM c4m_playerorders WHERE o_id=".$OrderID."";
      mysql_query($delete, $this->linkBilling) or die(mysql_error());
      
    }

  }


  /**********************************************************************
  * SetOrderPaid
  * Params: Order ID
  * Return: sets an order to paid
  */
  function SetOrderPaid($OID){

    // Select the order information
    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_id=".$OID;
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      $o_paymentterm = trim(mysql_result($return, $i, "o_paymentterm"));

      // Update the order
      if($o_paymentterm == 'y'){
        $update = "UPDATE c4m_playerorders SET o_orderstatus = 'p', o_datepaid=NOW(), o_datedue=DATE_ADD(NOW(), INTERVAL 1 YEAR) WHERE o_id=".$OID;
      }elseif($o_paymentterm == 's'){
        $update = "UPDATE c4m_playerorders SET o_orderstatus = 'p', o_datepaid=NOW(), o_datedue=DATE_ADD(NOW(), INTERVAL 6 MONTH) WHERE o_id=".$OID;
      }else{
        $update = "UPDATE c4m_playerorders SET o_orderstatus = 'p', o_datepaid=NOW(), o_datedue=DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE o_id=".$OID;
      }
      mysql_query($update, $this->linkBilling) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetUserNameByOrderID
  * Params: Order ID
  * Return: gets the username that was submited with the order/bill
  */
  function GetUserNameByOrderID($OID){

    // Select the order information
    $query = "SELECT * FROM c4m_playerorders WHERE o_id=".$OID;
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $name = "";

    if($num != 0){
      $name = trim(mysql_result($return, 0, "o_username"));
    }

    return $name;

  }


  /**********************************************************************
  * GetBillCountByUName
  * Params: Username
  * Return: 
  */
  function GetBillCountByUName($UName, &$Current, &$Previous){

    // Select the order information
    $query = "SELECT COUNT(*) FROM c4m_playerorders WHERE o_username='".$UName."' AND (o_orderstatus='p' OR o_orderstatus='f')";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $Previous = 0;

    if($num != 0){
      $Previous = trim(mysql_result($return, 0, 0));
    }

    // Select the order information
    $query = "SELECT COUNT(*) FROM c4m_playerorders WHERE o_username='".$UName."' AND o_orderstatus='u'";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $Current = 0;

    if($num != 0){
      $Current = trim(mysql_result($return, 0, 0));
    }

  }


  /**********************************************************************
  * GetOrdersByUserName
  * Params: Order Status
  * Return: Prints the orders
  */
  function GetOrdersByUserName($UserName, $OrderStatus){

    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_orderstatus='".$OrderStatus."' AND o_username='".$UserName."' ORDER BY o_id ASC";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = trim(mysql_result($return, $i, "o_id"));
        $o_username = trim(mysql_result($return, $i, "o_username"));
        $o_firstname = trim(mysql_result($return, $i, "o_firstname"));
        $o_lastname = trim(mysql_result($return, $i, "o_lastname"));
        $o_address = trim(mysql_result($return, $i, "o_address"));
        $o_citytown = trim(mysql_result($return, $i, "o_citytown"));
        $o_country = trim(mysql_result($return, $i, "o_country"));
        $o_provincestatearea = trim(mysql_result($return, $i, "o_provincestatearea"));
        $o_postalcode = trim(mysql_result($return, $i, "o_postalcode"));
        $o_email = trim(mysql_result($return, $i, "o_email"));
        $o_phonea = trim(mysql_result($return, $i, "o_phonea"));
        $o_phoneb = trim(mysql_result($return, $i, "o_phoneb"));
        $o_phonec = trim(mysql_result($return, $i, "o_phonec"));
        $o_redemptioncode = trim(mysql_result($return, $i, "o_redemptioncode"));
        $o_dateoforder = trim(mysql_result($return, $i, "o_dateoforder"));
        $o_paymentterm = trim(mysql_result($return, $i, "o_paymentterm"));
        $o_datepaid = trim(mysql_result($return, $i, "o_datepaid"));
        $o_datedue = trim(mysql_result($return, $i, "o_datedue"));
        $o_orderstatus = trim(mysql_result($return, $i, "o_orderstatus"));

        echo "<form name='frmBilling".$o_id."' method='post' action='./manage_new_bills.php'>";

        echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_1")."</b>".$o_id."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_2")."</b></td><td class='row2'>".$o_firstname." ".$o_lastname."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_3")."</b></td><td class='row2'>".$o_email."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_4")."</b></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td align='left' colspan='2' class='row2'>";
        echo "".$o_firstname." ".$o_lastname."<br>";
        echo "".$o_address."<br>";
        echo "".$o_citytown.", ".$o_provincestatearea.", ".$o_country."<br>";
        echo "".$o_postalcode."<br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_5")."</b></td><td class='row2'>".$o_username."</td>";
        echo "</tr>";

        if($o_redemptioncode != ""){

          $this->DecodeRedemtionCode(base64_decode($o_redemptioncode), $UserName, $Type, $Date);

          $type = "";
          switch($Type){

            case "M":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_6")."";
              break;

            case "S":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_7")."";
              break;

            case "Y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_8")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_9")."</td><td class='row2'>".$o_redemptioncode."</td>";
          echo "</tr>";
          echo "<tr>";
          echo "<td class='row2' colspan='2'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_10")." ".$UserName." ".$type.".</td>";
          echo "</tr>";

        }else{

          $nPrice = $this->GetDefinedPrice();

          if($o_paymentterm == 's'){
            $OrderCost = $nPrice * 6;
          }elseif($o_paymentterm == 'y'){
            $OrderCost = $nPrice * 12;
          }else{
            $OrderCost = $nPrice * 1;
          }

          $type = "";
          switch($o_paymentterm){

            case "m":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_11")."";
              break;

            case "s":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_12")."";
              break;

            case "y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_13")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_14")."</b></td><td class='row2'>".$type."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_15")."</b></td><td class='row2'>$".number_format($nPrice,2,'.', '')."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_16")."</b></td><td class='row2'>$".number_format($OrderCost,2,'.', '')."</td>";
          echo "</tr>";

        }

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_18")."</b></td><td class='row2'>".$o_datedue."</td>";
        echo "</tr>";

        echo "</table>";

        echo "</form>";
        echo "<br>";

        $i++;

       }

    }else{

      echo "<table border='0'>";
      echo "<tr><td>";
      echo "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_17")."";
      echo "</td></tr>";
      echo "</table>";

    }

  }


  /**********************************************************************
  * GetNewBillByUserName
  * Params: Order Status
  * Return: Prints the orders
  */
  function GetNewBillByUserName($UserName){

    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_username='".$UserName."' AND o_orderstatus = 'u' ORDER BY o_id ASC LIMIT 1";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    $aBillInfo = array();

    if($num != 0){

      $i=0;
      $o_id = trim(mysql_result($return, $i, "o_id"));
      $o_username = trim(mysql_result($return, $i, "o_username"));
      $o_firstname = trim(mysql_result($return, $i, "o_firstname"));
      $o_lastname = trim(mysql_result($return, $i, "o_lastname"));
      $o_address = trim(mysql_result($return, $i, "o_address"));
      $o_citytown = trim(mysql_result($return, $i, "o_citytown"));
      $o_country = trim(mysql_result($return, $i, "o_country"));
      $o_provincestatearea = trim(mysql_result($return, $i, "o_provincestatearea"));
      $o_postalcode = trim(mysql_result($return, $i, "o_postalcode"));
      $o_email = trim(mysql_result($return, $i, "o_email"));
      $o_phonea = trim(mysql_result($return, $i, "o_phonea"));
      $o_phoneb = trim(mysql_result($return, $i, "o_phoneb"));
      $o_phonec = trim(mysql_result($return, $i, "o_phonec"));
      $o_redemptioncode = trim(mysql_result($return, $i, "o_redemptioncode"));
      $o_dateoforder = trim(mysql_result($return, $i, "o_dateoforder"));
      $o_paymentterm = trim(mysql_result($return, $i, "o_paymentterm"));
      $o_datepaid = trim(mysql_result($return, $i, "o_datepaid"));
      $o_datedue = trim(mysql_result($return, $i, "o_datedue"));
      $o_orderstatus = trim(mysql_result($return, $i, "o_orderstatus"));


      $aBillInfo = array($o_id, $o_username, $o_firstname, $o_lastname, $o_address, 
                         $o_citytown, $o_country, $o_provincestatearea, $o_postalcode, 
                         $o_email, $o_phonea, $o_phoneb, $o_phonec, $o_redemptioncode,
                         $o_dateoforder, $o_paymentterm, $o_datepaid, $o_datedue, $o_orderstatus);

    }

    return $aBillInfo;

  }


  /**********************************************************************
  * UpdateBill
  *
  */
  function UpdateBill($ID, $playerUName, $firstname, $lastname, $address, $citytown, $country, $provincestatearea, $postalcode, $email, $phonea, $phoneb, $phonec, $redemptioncode, $paymentterm){

    $update = "UPDATE c4m_playerorders SET o_username = '".$playerUName."', o_firstname = '".$firstname."', o_lastname = '".$lastname."', o_address = '".$address."', o_citytown = '".$citytown."', o_country = '".$country."', o_provincestatearea = '".$provincestatearea."', o_postalcode = '".$postalcode."', o_email = '".$email."', o_phonea = '".$phonea."', o_phoneb = '".$phoneb."', o_phonec = '".$phonec."', o_redemptioncode = '".$redemptioncode."', o_dateoforder = NOW(), o_paymentterm = '".$paymentterm."' WHERE o_id=".$ID;
    mysql_query($update, $this->linkBilling) or die(mysql_error());

    return $ID;

  }


  /**********************************************************************
  * GetOldOrders
  * Params: Order Status
  * Return: Prints the orders
  */
  function GetOldOrders(){

    //Select the service items
    $query = "SELECT * FROM c4m_playerorders WHERE o_orderstatus IN('f', 'p') ORDER BY o_id DESC";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i=0;
      while($i < $num){

        $o_id = trim(mysql_result($return, $i, "o_id"));
        $o_username = trim(mysql_result($return, $i, "o_username"));
        $o_firstname = trim(mysql_result($return, $i, "o_firstname"));
        $o_lastname = trim(mysql_result($return, $i, "o_lastname"));
        $o_address = trim(mysql_result($return, $i, "o_address"));
        $o_citytown = trim(mysql_result($return, $i, "o_citytown"));
        $o_country = trim(mysql_result($return, $i, "o_country"));
        $o_provincestatearea = trim(mysql_result($return, $i, "o_provincestatearea"));
        $o_postalcode = trim(mysql_result($return, $i, "o_postalcode"));
        $o_email = trim(mysql_result($return, $i, "o_email"));
        $o_phonea = trim(mysql_result($return, $i, "o_phonea"));
        $o_phoneb = trim(mysql_result($return, $i, "o_phoneb"));
        $o_phonec = trim(mysql_result($return, $i, "o_phonec"));
        $o_redemptioncode = trim(mysql_result($return, $i, "o_redemptioncode"));
        $o_dateoforder = trim(mysql_result($return, $i, "o_dateoforder"));
        $o_paymentterm = trim(mysql_result($return, $i, "o_paymentterm"));
        $o_datepaid = trim(mysql_result($return, $i, "o_datepaid"));
        $o_datedue = trim(mysql_result($return, $i, "o_datedue"));
        $o_orderstatus = trim(mysql_result($return, $i, "o_orderstatus"));

        echo "<form name='frmBilling".$o_id."' method='post' action='./manage_new_bills.php'>";

        // Skin table settings
        if(defined('CFG_GETOLDORDERS_TABLE1_WIDTH') && defined('CFG_GETOLDORDERS_TABLE1_BORDER') && defined('CFG_GETOLDORDERS_TABLE1_CELLPADDING') && defined('CFG_GETOLDORDERS_TABLE1_CELLSPACING') && defined('CFG_GETOLDORDERS_TABLE1_ALIGN')){
          echo "<table border='".CFG_GETOLDORDERS_TABLE1_BORDER."' align='".CFG_GETOLDORDERS_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETOLDORDERS_TABLE1_CELLPADDING."' cellspacing='".CFG_GETOLDORDERS_TABLE1_CELLSPACING."' width='".CFG_GETOLDORDERS_TABLE1_WIDTH."'>";
        }else{
          echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
        }

        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_1")."</b>".$o_id."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_2")."</b></td><td class='row2'>".$o_firstname." ".$o_lastname."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_3")."</b></td><td class='row2'>".$o_email."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td Colspan = '2' class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_4")."</b></td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td align='left' colspan='2' class='row2'>";
        echo "".$o_firstname." ".$o_lastname."<br>";
        echo "".$o_address."<br>";
        echo "".$o_citytown.", ".$o_provincestatearea.", ".$o_country."<br>";
        echo "".$o_postalcode."<br>";
        echo "</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_5")."</b></td><td class='row2'>".$o_username."</td>";
        echo "</tr>";

        if($o_redemptioncode != ""){

          $this->DecodeRedemtionCode(base64_decode($o_redemptioncode), $UserName, $Type, $Date);

          $type = "";
          switch($Type){

            case "M":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_6")."";
              break;

            case "S":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_7")."";
              break;

            case "Y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_8")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_9")."</td><td class='row2'>".$o_redemptioncode."</td>";
          echo "</tr>";
          echo "<tr>";
          echo "<td class='row2' colspan='2'>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_10")." ".$UserName." ".$type.".</td>";
          echo "</tr>";

        }else{

          $nPrice = $this->GetDefinedPrice();

          if($o_paymentterm == 's'){
            $OrderCost = $nPrice * 6;
          }elseif($o_paymentterm == 'y'){
            $OrderCost = $nPrice * 12;
          }else{
            $OrderCost = $nPrice * 1;
          }

          $type = "";
          switch($o_paymentterm){

            case "m":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_11")."";
              break;

            case "s":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_12")."";
              break;

            case "y":
              $type = "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_13")."";
              break;

          }

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_14")."</b></td><td class='row2'>".$type."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_15")."</b></td><td class='row2'>$".number_format($nPrice,2,'.', '')."</td>";
          echo "</tr>";

          echo "<tr>";
          echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_16")."</b></td><td class='row2'>$".number_format($OrderCost,2,'.', '')."</td>";
          echo "</tr>";

        }

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_19")."</b></td><td class='row2'>".$o_orderstatus."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td class='row1'><b>".$this->GetStringFromStringTable("IDS_CBILLING_TXT_20")."</b></td><td class='row2'>".$o_datedue."</td>";
        echo "</tr>";

        echo "</table>";

        echo "</form>";
        echo "<br>";

        $i++;

       }

    }else{

      echo "<table border='0' align='center'>";
      echo "<tr><td>";
      echo "".$this->GetStringFromStringTable("IDS_CBILLING_TXT_17")."";
      echo "</td></tr>";
      echo "</table>";

    }

  }


  /**********************************************************************
  * UpdatePaypalInfo
  *
  */
  function UpdatePaypalInfo($email, $currency, $price){

    $query = "SELECT * FROM c4m_paypalaccount LIMIT 1";
    $return = mysql_query($query, $this->linkBilling) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $i = 0;
      $p_id = mysql_result($return,$i,"p_id");

      $update = "UPDATE c4m_paypalaccount SET p_email='".$email."', p_currency='".$currency."', p_monthlycharge ='".$price."' WHERE p_id=".$p_id;
      mysql_query($update, $this->linkBilling) or die(mysql_error());
    }

  }


  /**********************************************************************
  * SendEmail
  * 
  * Params: $to, $fromemail, $fromname, $subject, $body
  */  
  function SendEmail($to, $fromemail, $fromname, $subject, $body){

    // Advanced email configuration
    $query1 = "SELECT * FROM server_email_settings WHERE o_id='1'";
    $return1 = mysql_query($query1, $this->linkBilling) or die(mysql_error());
    $num1 = mysql_numrows($return1);

    $query2 = "SELECT * FROM smtp_settings WHERE o_id='1'";
    $return2 = mysql_query($query2, $this->linkBilling) or die(mysql_error());
    $num2 = mysql_numrows($return2);

    $bOld = true;

    $xsmtp = "";
    $xport = "";
    $xuser = "";
    $xpass = "";
    $xdomain= "";

    if($num1 != 0){
      $smtp = trim(mysql_result($return1,0,"o_smtp"));
      $port = trim(mysql_result($return1,0,"o_smtp_port"));
      
      $user = "";
      $pass = "";
      $domain = "";

      if($num2 != 0){
        $user = trim(mysql_result($return2,0,"o_user"));
        $pass = trim(mysql_result($return2,0,"o_pass"));
        $domain = trim(mysql_result($return2,0,"o_domain"));
        $bOld = false;
      }

      if($smtp != "" && $port != "" && $user == "" && $pass == ""){

        ini_set("SMTP", $smtp); 
        ini_set("smtp_port", $port); 
        ini_set("sendmail_from", $fromemail); 

      }

      if($smtp != "" && $port != "" && $user != "" && $pass != ""){
        $xsmtp = $smtp;
        $xport = $port;
        $xuser = $user;
        $xpass = $pass;
        $xdomain = $domain;
      }

    }

    if($bOld){

      $headers1 .= "MIME-Version: 1.0\n";
      $headers1 .= "Content-type: text/html; charset=iso-8859-1\n";
      $headers1 .= "X-Priority: 1\n";
      $headers1 .= "X-MSMail-Priority: High\n";
      $headers1 .= "X-Mailer: php\n";
      $headers1 .= "From: \"".$fromname."\" <".$fromemail.">\n";
 
      // Now we send the message
      $send_check=mail($to,$subject,$body,$headers1);

    }else{

      require_once($this->adl."includes/phpmailer/class.phpmailer.php");

      $mail = new PHPMailer();
      //$mail->IsSMTP(); // set mailer to use SMTP
      $mail->SMTPAuth = true;

      $mail->Host = $xsmtp;
      $mail->SMTPAuth = true;
      $mail->Username = $xuser;
      $mail->Password = $xpass;
      $mail->From = $fromemail;
      $mail->FromName = $fromname;
      $mail->AddAddress($to);
      $mail->AddReplyTo($xdomain);

      $mail->WordWrap = 50;
      $mail->IsHTML(true);
      $mail->Subject = $subject;
      $mail->Body = $body;

      if(!$mail->Send()){
        $insert = "INSERT INTO email_log VALUES(NULL, '".$to."', '".$fromemail."', '".$fromname."', '".addslashes($subject)."', '".addslashes($body)."', '".addslashes($mail->ErrorInfo)."', NOW())";
        mysql_query($insert, $this->linkBilling) or die(mysql_error());
      }

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysql_close($this->linkBilling);
  }

} //end of class definition
?>
