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

class CLanguage{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $link1;
  var $ChessCFGFileLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CLanguage (Constructor)
  *
  */
  function CLanguage($ConfigFile){

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

    $this->link1 = mysql_connect($this->host, $this->user, $this->pass);
    mysql_select_db($this->dbnm);

    if(!$this->link1){
      die("CLanguage.php: ".mysql_error());
    }

  }


  /**********************************************************************
  * InstallLanguage
  *
  */
  function InstallLanguage($langfile){

    $query = "SELECT * FROM server_language WHERE o_id=1";
    $return = mysql_query($query, $this->link1) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      $update = "UPDATE server_language SET o_languagefile='".$langfile."' WHERE o_id=1";
      mysql_query($update, $this->link1) or die(mysql_error());

    }else{

      $insert = "INSERT INTO server_language VALUES(1, 'english.1.0.txt')";
      mysql_query($insert, $this->link1) or die(mysql_error());

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysql_close($this->link1);
  }

} //end of class definition
?>