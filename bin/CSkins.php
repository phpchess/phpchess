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
  function CSkins($ConfigFile){

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

    $this->link2 = mysql_connect($this->host, $this->user, $this->pass);
    mysql_select_db($this->dbnm);

    if(!$this->link2){
      die("CSkins.php: ".mysql_error());
    }

  }


  /**********************************************************************
  * getskinname
  *
  */
  function getskinname(){

    $query = "SELECT * FROM c4m_skins LIMIT 1";
    $return = mysql_query($query, $this->link2) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
      $name = mysql_result($return,$i,"name");
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
    mysql_query($Update, $this->link2) or die(mysql_error());

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysql_close($this->link2);
  }

} //end of class definition
?>