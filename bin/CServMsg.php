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

class CServMsg{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkCServMsg;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CServMsg (Constructor)
  *
  */
  function CServMsg($ConfigFile){

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

    $this->linkCServMsg = mysql_connect($this->host, $this->user, $this->pass);
    mysql_select_db($this->dbnm);

    if(!$this->linkCServMsg){
      die("CServMsg.php: ".mysql_error());
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
      $return = mysql_query($query, $this->linkCServMsg) or die(mysql_error());
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
  * GetServerMessages
  *
  */
  function GetServerMessages($ConfigFile){

    $query = "SELECT * FROM c4m_servermessage ORDER BY sm_id DESC";
    $return = mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    $num = mysql_numrows($return);

    echo "<br>";

    // Skin table settings
    if(defined('CFG_GETSERVERMESSAGES_TABLE1_WIDTH') && defined('CFG_GETSERVERMESSAGES_TABLE1_BORDER') && defined('CFG_GETSERVERMESSAGES_TABLE1_CELLPADDING') && defined('CFG_GETSERVERMESSAGES_TABLE1_CELLSPACING') && defined('CFG_GETSERVERMESSAGES_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETSERVERMESSAGES_TABLE1_WIDTH."' cellpadding='".CFG_GETSERVERMESSAGES_TABLE1_CELLPADDING."' cellspacing='".CFG_GETSERVERMESSAGES_TABLE1_CELLSPACING."' border='".CFG_GETSERVERMESSAGES_TABLE1_BORDER."' align='".CFG_GETSERVERMESSAGES_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    }

    if($num != 0){

      echo "<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CSRVMSG_TABLE_HEADER_1")."</font></td></tr>";

      $i = 0;
      while($i < $num){

        $sm_id = mysql_result($return,$i,"sm_id");
        $sm_msg = mysql_result($return,$i,"sm_msg");
        $sm_date = mysql_result($return,$i,"sm_date");

        echo "<tr>";
        echo "<td class='row1' valign='top'>".$sm_date."</td><td class='row2' valign='top'>".$sm_msg."</td>";
        echo "</tr>";

        $i++;
      }

    }else{

      echo "<tr><td class='tableheadercolor'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CSRVMSG_TABLE_HEADER_1")."</font></td></tr>";

      echo "<tr>";
      echo "<td class='row1'>".$this->GetStringFromStringTable("IDS_CSRVMSG_TXT_1")."</td>";
      echo "</tr>";
    }

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
  * GetMessages
  *
  */
  function GetMessages($ConfigFile){

    $query = "SELECT * FROM c4m_servermessage";
    $return = mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    $num = mysql_numrows($return);
    
    if($num != 0){

      // Skin table settings
      if(defined('CFG_GETMESSAGES_TABLE1_WIDTH') && defined('CFG_GETMESSAGES_TABLE1_BORDER') && defined('CFG_GETMESSAGES_TABLE1_CELLPADDING') && defined('CFG_GETMESSAGES_TABLE1_CELLSPACING') && defined('CFG_GETMESSAGES_TABLE1_ALIGN')){
        echo "<table border='".CFG_GETMESSAGES_TABLE1_BORDER."' align='".CFG_GETMESSAGES_TABLE1_ALIGN."' cellpadding='".CFG_GETMESSAGES_TABLE1_CELLPADDING."' cellspacing='".CFG_GETMESSAGES_TABLE1_CELLSPACING."' width='".CFG_GETMESSAGES_TABLE1_WIDTH."'>";
      }else{
        echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      }

      echo "<tr>";
      echo "<td></td>";
      echo "<td><u><b>".$this->GetStringFromStringTable("IDS_CSRVMSG_TXT_2")."</b></u></td>";
      echo "<td><u><b>".$this->GetStringFromStringTable("IDS_CSRVMSG_TXT_3")."</b></u></td>";
      echo "</tr>";

      $i = 0;
      while($i < $num){

        $sm_id = mysql_result($return,$i,"sm_id");
        $sm_msg = mysql_result($return,$i,"sm_msg");
        $sm_date = mysql_result($return,$i,"sm_date");

        echo "<tr>";
        echo "<td valign='top'><input type='radio' value='".$sm_id."' name='rdodelete'></td>";
        echo "<td valign='top'>".$sm_msg."</td>";
        echo "<td valign='top'>".$sm_date."</td>";
        echo "</tr>";

        $i++;
      }

      echo "</table>";
    }

  }


  /**********************************************************************
  * GetMessagesByID
  *
  */
  function GetMessagesByID($ConfigFile, $id, &$rid, &$rtext, &$rdate){

    $query = "SELECT * FROM c4m_servermessage WHERE sm_id =".$id;
    $return = mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    $num = mysql_numrows($return);
    
    if ($num != 0){

        $rid = trim(mysql_result($return,0,"sm_id"));
        $rtext = trim(mysql_result($return,0,"sm_msg"));
        $rdate = trim(mysql_result($return,0,"sm_date"));

    }else{

        $rid = 0;
        $rtext = "";
        $rdate = "";

    }

  }


  /**********************************************************************
  * AddServerMessage
  *
  */
  function AddServerMessage($ConfigFile, $text){

    $query = "INSERT INTO c4m_servermessage VALUES(NULL,'".$text."',NOW())";
    mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    
  }


  /**********************************************************************
  * DeleteServerMessage
  *
  */
  function DeleteServerMessage($ConfigFile, $id){

    $query = "DELETE FROM c4m_servermessage WHERE sm_id = ".$id;
    mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    
  }


  /**********************************************************************
  * EditServerMessage
  *
  */
  function EditServerMessage($ConfigFile, $id, $text){

    $query = "UPDATE c4m_servermessage SET sm_msg = '".$text."' WHERE sm_id = ".$id;
    mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    
  }


  /**********************************************************************
  * GetMessagesForMobile
  *
  */
  function GetMessagesForMobile(){

    $query = "SELECT * FROM c4m_servermessage";
    $return = mysql_query($query, $this->linkCServMsg) or die(mysql_error());
    $num = mysql_numrows($return);
    
    if($num != 0){

      $i = 0;
      while($i < $num){

        $sm_id = mysql_result($return,$i,"sm_id");
        $sm_msg = mysql_result($return,$i,"sm_msg");
        $sm_date = mysql_result($return,$i,"sm_date");

        echo "<SERVERMSG>\n";

        echo "<MESSAGE>\n";
        echo $sm_msg;
        echo "</MESSAGE>\n";

        echo "<DATE>\n";
        echo $sm_date;
        echo "</DATE>\n";

        echo "</SERVERMSG>\n";

        $i++;
      }

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysql_close($this->linkCServMsg);
  }

} //end of class definition
?>
