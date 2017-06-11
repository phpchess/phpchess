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
  function __construct($ConfigFile){

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

    $this->link1 = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->link1,$this->dbnm);

    if(!$this->link1){
      die("CLanguage.php: ".mysqli_error($this->link1));
    }

  }


  /**********************************************************************
  * InstallLanguage
  *
  */
  function InstallLanguage($langfile){

    $query = "SELECT * FROM server_language WHERE o_id=1";
    $return = mysqli_query($this->link1,$query) or die(mysqli_error($this->link1));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $update = "UPDATE server_language SET o_languagefile='".$langfile."' WHERE o_id=1";
      mysqli_query($this->link1,$update) or die(mysqli_error($this->link1));

    }else{

      $insert = "INSERT INTO server_language VALUES(1, 'english.1.0.txt')";
      mysqli_query($this->link1,$insert) or die(mysqli_error($this->link1));

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->link1);
  }

} //end of class definition
?>