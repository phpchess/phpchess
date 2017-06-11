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

class CSkins{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $link2;
  var $ChessCFGFileLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CSkins (Constructor)
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
    $this->sitename = $conf['site_name'];

    $this->link2 = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->link2,$this->dbnm);

    if(!$this->link2){
      die("CSkins.php: ".mysqli_error($this->link2));
    }

  }


  function mysqli_result($result, $number, $field=0) {
      mysqli_data_seek($result, $number);
      $row = mysqli_fetch_array($result);
      return $row[$field];
  }
  

  /**********************************************************************
  * getskinname
  *
  */
  function getskinname(){
    $query = "SELECT * FROM c4m_skins LIMIT 1";
    $return = mysqli_query($this->link2,$query) or die(mysqli_error($this->link2));
    $num = mysqli_num_rows($return);

    if($num != 0){
      $name = $this->mysqli_result($return,$i,"name");
    }

    return $name;
  }

  /**********************************************************************
  * getsitename
  *
  */
  function getsitename(){

    $name = $this->sitename;

    return $name;
  }


  /**********************************************************************
  * setskinname
  *
  */
  function setskinname($skinname){

    $Update = "update c4m_skins SET name = '".$skinname."' WHERE id=1";
    mysqli_query($this->link2,$Update) or die(mysqli_error($this->link2));

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->link2);
  }

} //end of class definition
?>