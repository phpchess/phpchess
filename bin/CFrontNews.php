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

class CFrontNews{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkFrontNews;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CFrontNews (Constructor)
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

    $this->linkFrontNews = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->linkFrontNews,$this->dbnm);

    if(!$this->linkFrontNews){
      die("CServMsg.php: ".mysqli_error($this->linkFrontNews));
    }

  }

  function mysqli_result($result, $number, $field=0) {
      mysqli_data_seek($result, $number);
      $row = mysqli_fetch_array($result);
      return $row[$field];
  }

  /**********************************************************************
  * GetFrontNews
  *
  */
  function GetFrontNews(){

    $query = "SELECT * FROM c4m_frontnews ORDER BY f_id DESC";
    $return = mysqli_query($this->linkFrontNews,$query) or die(mysqli_error($this->linkFrontNews));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $f_id = $this->mysqli_result($return,$i,"f_id");
        $f_title = $this->mysqli_result($return,$i,"f_title");
        $f_msg = $this->mysqli_result($return,$i,"f_msg");
        $f_date = $this->mysqli_result($return,$i,"f_date");

        //echo "<br>";
        echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";

        echo "<tr>";
        echo "<td class='row1' valign='top' width='30%'>".$f_date."</td><td valign='top'>&nbsp;</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='row2' valign='top'>".$f_title."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='row2' valign='top'>".str_replace("\n","<br>",$f_msg)."</td>";
        echo "</tr>";

        echo "</table>";

        $i++;
      }

    }

  }


  /**********************************************************************
  * GetFrontNewsAdmin
  *
  */
  function GetFrontNewsAdmin(){

    $query = "SELECT * FROM c4m_frontnews ORDER BY f_id DESC";
    $return = mysqli_query($this->linkFrontNews,$query) or die(mysqli_error($this->linkFrontNews));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $f_id = $this->mysqli_result($return,$i,"f_id");
        $f_title = $this->mysqli_result($return,$i,"f_title");
        $f_msg = $this->mysqli_result($return,$i,"f_msg");
        $f_date = $this->mysqli_result($return,$i,"f_date");

        echo "<br>";

        // Skin table settings
        if(defined('CFG_GETFRONTNEWSADMIN_TABLE1_WIDTH') && defined('CFG_GETFRONTNEWSADMIN_TABLE1_BORDER') && defined('CFG_GETFRONTNEWSADMIN_TABLE1_CELLPADDING') && defined('CFG_GETFRONTNEWSADMIN_TABLE1_CELLSPACING') && defined('CFG_GETFRONTNEWSADMIN_TABLE1_ALIGN')){
          echo "<table border='".CFG_GETFRONTNEWSADMIN_TABLE1_BORDER."' align='".CFG_GETFRONTNEWSADMIN_TABLE1_ALIGN."' class='forumline' cellpadding='".CFG_GETFRONTNEWSADMIN_TABLE1_CELLPADDING."' cellspacing='".CFG_GETFRONTNEWSADMIN_TABLE1_CELLSPACING."' width='".CFG_GETFRONTNEWSADMIN_TABLE1_WIDTH."'>";
        }else{
          echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
        }

        $xRow1 = "35%";
        if(defined('GETFRONTNEWSADMIN_TABLE1_ROW1_WIDTH')){
          $xRow1 = GETFRONTNEWSADMIN_TABLE1_ROW1_WIDTH;
        } 
   
        echo "<tr>";
        echo "<td class='row1' valign='top' width='".$xRow1."'><input type='radio' name='rdodelete' value='".$f_id."'> ".$f_date."</td><td valign='top'>&nbsp;</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='row2' valign='top'>".$f_title."</td>";
        echo "</tr>";

        echo "<tr>";
        echo "<td colspan='2' class='row2' valign='top'>".$f_msg."</td>";
        echo "</tr>";

        echo "</table>";

        $i++;
      }

    }

  }


  /**********************************************************************
  * AddFrontNews
  *
  */
  function AddFrontNews($Title, $News){

    $insert = "INSERT INTO c4m_frontnews VALUES(NULL, '".$Title."', '".$News."', NOW())";
    mysqli_query($this->linkFrontNews,$insert) or die(mysqli_error($this->linkFrontNews));

  }


  /**********************************************************************
  * GetFrontNewsForEdit
  *
  */
  function GetFrontNewsForEdit($id, &$Title, &$News){

    $query = "SELECT * FROM c4m_frontnews WHERE f_id =".$id;
    $return = mysqli_query($this->linkFrontNews,$query) or die(mysqli_error($this->linkFrontNews));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $f_id = $this->mysqli_result($return,$i,"f_id");
      $Title = $this->mysqli_result($return,$i,"f_title");
      $News = $this->mysqli_result($return,$i,"f_msg");
      $f_date = $this->mysqli_result($return,$i,"f_date");

    }

  }


  /**********************************************************************
  * EditFrontNews
  *
  */
  function EditFrontNews($id, $Title, $News){

    $update = "UPDATE c4m_frontnews SET f_title='".$Title."', f_msg='".$News."' WHERE f_id =".$id;
    mysqli_query($this->linkFrontNews,$update) or die(mysqli_error($this->linkFrontNews));

  }


  /**********************************************************************
  * DeleteFrontNews
  *
  */
  function DeleteFrontNews($id){

    $delete = "DELETE FROM c4m_frontnews WHERE f_id =".$id;
    mysqli_query($this->linkFrontNews,$delete) or die(mysqli_error($this->linkFrontNews));

  }


  /**********************************************************************
  * GetFrontNewsForMobile
  *
  */
  function GetFrontNewsForMobile(){

    $query = "SELECT * FROM c4m_frontnews ORDER BY f_id DESC";
    $return = mysqli_query($this->linkFrontNews,$query) or die(mysqli_error($this->linkFrontNews));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $i = 0;
      while($i < $num){

        $f_id = $this->mysqli_result($return,$i,"f_id");
        $f_title = $this->mysqli_result($return,$i,"f_title");
        $f_msg = $this->mysqli_result($return,$i,"f_msg");
        $f_date = $this->mysqli_result($return,$i,"f_date");

        echo "<NEWS>\n";

        echo "<TITLE>\n";
        echo $f_title;
        echo "</TITLE>\n";

        echo "<MESSAGE>\n";
        echo $f_msg;
        echo "</MESSAGE>\n";

        echo "<DATE>\n";
        echo $f_date;
        echo "</DATE>\n";

        echo "</NEWS>\n";

        $i++;
      }

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->linkFrontNews);
  }

} //end of class definition
?>