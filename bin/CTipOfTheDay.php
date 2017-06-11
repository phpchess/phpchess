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

class CTipOfTheDay{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $link3;

  var $ChessCFGFileLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CTipOfTheDay (Constructor)
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

    $this->link3 = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->link3,$this->dbnm);

    if(!$this->link3){
      die("CTipOfTheDay.php: ".mysqli_error($this->link3));
    }

  }

  function mysqli_result($result, $number, $field=0) {
      mysqli_data_seek($result, $number);
      $row = mysqli_fetch_array($result);
      return $row[$field];
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
      $return = mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
      $num = mysqli_num_rows($return);

      if($num != 0){

        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$this->mysqli_result($return, 0, "o_languagefile");

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
  * GetRandomTip
  *
  */
  function GetRandomTip($ConfigFile){

    if ($_COOKIE["TipDayID"] != ""){

      $query = "SELECT * FROM c4m_tipoftheday WHERE tip_id =".$_COOKIE["TipDayID"];
      $return = mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
      $num = mysqli_num_rows($return);

      if ($num != 0){

        $tip_id = trim($this->mysqli_result($return,0,"tip_id"));
        $tip_tiptext  = trim($this->mysqli_result($return,0,"tip_tiptext"));

        echo "<br>";
        echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
        echo "<tr>";
        echo "<td>".$tip_tiptext."</td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
         
      }

    }else{

      $query = "SELECT * FROM c4m_tipoftheday ORDER BY RAND() LIMIT 1";
      $return = mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
      $num = mysqli_num_rows($return);

      if ($num != 0){

        $tip_id = trim($this->mysqli_result($return,0,"tip_id"));
        $tip_tiptext  = trim($this->mysqli_result($return,0,"tip_tiptext"));

        echo "<br>";
        echo "<table width='95%' cellpadding='0' cellspacing='0' border='0' align='center'>";
        echo "<tr>";
        echo "<td>".$tip_tiptext."</td>";
        echo "</tr>";
        echo "</table>";
        echo "<br>";
         
        setcookie("TipDayID",$tip_id,time()+86400);

      }

    }

  }


  /**********************************************************************
  * GetTips
  *
  */
  function GetTips($ConfigFile){

    $query = "SELECT * FROM c4m_tipoftheday";
    $return = mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
    $num = mysqli_num_rows($return);

    if($num != 0){

      // Skin table settings
      if(defined('CFG_GETTIPS_TABLE1_WIDTH') && defined('CFG_GETTIPS_TABLE1_BORDER') && defined('CFG_GETTIPS_TABLE1_CELLPADDING') && defined('CFG_GETTIPS_TABLE1_CELLSPACING') && defined('CFG_GETTIPS_TABLE1_ALIGN')){
        echo "<table border='".CFG_GETTIPS_TABLE1_BORDER."' align='".CFG_GETTIPS_TABLE1_ALIGN."' cellpadding='".CFG_GETTIPS_TABLE1_CELLPADDING."' cellspacing='".CFG_GETTIPS_TABLE1_CELLSPACING."' width='".CFG_GETTIPS_TABLE1_WIDTH."'>";
      }else{
        echo "<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>";
      }

      echo "<tr>";
      echo "<td></td>";
      echo "<td><u><b>".$this->GetStringFromStringTable("IDS_CTIPOFTHEDAY_TXT_1")."</b></u></td>";
      echo "<td><u><b>".$this->GetStringFromStringTable("IDS_CTIPOFTHEDAY_TXT_2")."</b></u></td>";
      echo "</tr>";

      $i= 0;
      while($i < $num){

        $tip_id = trim($this->mysqli_result($return,$i,"tip_id"));
        $tip_tiptext  = trim($this->mysqli_result($return,$i,"tip_tiptext"));
        $tip_dateadded  = trim($this->mysqli_result($return,$i,"tip_dateadded"));

        echo "<tr>";
        echo "<td valign='top'><input type='radio' value='".$tip_id."' name='rdodelete'></td>";
        echo "<td valign='top'>".$tip_tiptext."</td>";
        echo "<td valign='top'>".$tip_dateadded."</td>";
        echo "</tr>";

        $i++;
      } 

      echo "</table>";

    }

  }


  /**********************************************************************
  * GetTipsByID
  *
  */
  function GetTipsByID($ConfigFile, $id, &$rid, &$rtext, &$rdate){

    $query = "SELECT * FROM c4m_tipoftheday WHERE tip_id =".$id;
    $return = mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
    $num = mysqli_num_rows($return);

    if ($num != 0){

        $rid = trim($this->mysqli_result($return,0,"tip_id"));
        $rtext = trim($this->mysqli_result($return,0,"tip_tiptext"));
        $rdate = trim($this->mysqli_result($return,0,"tip_dateadded"));

    }else{

        $rid = 0;
        $rtext = "";
        $rdate = "";

    }

  }


  /**********************************************************************
  * AddTip
  *
  */
  function AddTip($ConfigFile, $text){

    $query = "INSERT INTO c4m_tipoftheday VALUES(NULL,'".$text."',NOW())";
    mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
    
  }


  /**********************************************************************
  * DeleteTip
  *
  */
  function DeleteTip($ConfigFile, $tipid){

    $query = "DELETE FROM c4m_tipoftheday WHERE tip_id = ".$tipid;
    mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));
  }


  /**********************************************************************
  * EditTip
  *
  */
  function EditTip($ConfigFile, $tipid, $tiptext){

    $query = "UPDATE c4m_tipoftheday SET tip_tiptext ='".$tiptext."' WHERE tip_id = ".$tipid;
    mysqli_query($this->link3,$query) or die(mysqli_error($this->link3));

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->link3);
  }


} //end of class definition
?>
