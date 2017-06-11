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

class CBuddyList{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkBuddyList;
  var $ChessCFGFileLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CBuddyList (Constructor)
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

    $this->linkBuddyList = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->linkBuddyList,$this->dbnm);

    if(!$this->linkBuddyList){
      die("CBuddyList.php: ".mysqli_error($this->linkBuddyList));
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
      $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
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
  * GetBuddyList
  *
  */
  function GetBuddyList($ConfigFile, $Player_ID){

    $query = "SELECT * FROM c4m_buddylist WHERE player_id = ".$Player_ID." ORDER BY bl_date DESC";
    $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
    $num = mysqli_num_rows($return);

    // Skin table settings
    if(defined('CFG_GETBUDDYLIST_TABLE1_WIDTH') && defined('CFG_GETBUDDYLIST_TABLE1_BORDER') && defined('CFG_GETBUDDYLIST_TABLE1_CELLPADDING') && defined('CFG_GETBUDDYLIST_TABLE1_CELLSPACING') && defined('CFG_GETBUDDYLIST_TABLE1_ALIGN')){
      echo "<table width='".CFG_GETBUDDYLIST_TABLE1_WIDTH."' border='".CFG_GETBUDDYLIST_TABLE1_BORDER."' cellpadding='".CFG_GETBUDDYLIST_TABLE1_CELLPADDING."' cellspacing='".CFG_GETBUDDYLIST_TABLE1_CELLSPACING."' align='".CFG_GETBUDDYLIST_TABLE1_ALIGN."' class='forumline'>";
    }else{
      echo "<table width='450' border='0' cellpadding='3' cellspacing='1' align='center' class='forumline'>";
    }

    echo "<tr><td class='tableheadercolor' colspan='5'><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_HEADER_1")."</font></td></tr>";
    echo "<tr><td class='row1'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_1")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_2")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_3")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_4")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_5")."</td></tr>";

    if($num != 0){

      $i = 0;
      While($i < $num){
        
        //Get the buddy's information by his ID
        $query1 = "SELECT * FROM player WHERE player_id = ".$this->mysqli_result($return,$i,"buddy_id");
        $return1 = mysqli_query($this->linkBuddyList,$query1) or die(mysqli_error($this->linkBuddyList));
        $num1 = mysqli_num_rows($return1);

        if($num1 != 0){

          $ii = 0;
          while($ii < $num1){

            $player_id = trim($this->mysqli_result($return1,$ii,"player_id"));
            $userid = trim($this->mysqli_result($return1,$ii,"userid"));
            $signup_time = trim($this->mysqli_result($return1,$ii,"signup_time"));
            $email = trim($this->mysqli_result($return1,$ii,"email"));

            if($player_id != "" && $this->IsPlayerDisabled($player_id) == false){
              echo "<tr>";
              echo "<td class='row2'><a href='./chess_statistics.php?playerid=".$player_id."&name=".$userid."'>".$userid."</a></td>";
              echo "<td class='row2'>".date("m-d-Y",$signup_time)."</td>";
              echo "<td class='row2'><a href='./chess_msg_center.php?type=newmsg&slctUsers=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_9")."</a></td>";
              echo "<td class='row2'><a href='./chess_create_game_ar.php?othpid=".$player_id."' class='menulinks'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_8")."</a></td>";
              echo "<td class='row2'>";

              if($this->IsPlayerOnline($ConfigFile, $player_id)){
                echo "<font color='Green'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_6")."</font>";
              }else{
                echo "<font color='red'>".$this->GetStringFromStringTable("IDS_CBUDDYLIST_TABLE_1_TXT_7")."</font>";
              }

              echo "</td>";
              echo "</tr>";
            }

            $ii++;

          }

        }

        $i++;

      }

    }

    echo "</table>";

  }


  /**********************************************************************
  * IsPlayerOnline
  *
  */
  function IsPlayerOnline($ConfigFile, $ID){

    $bOnline = false;

    //Get game info
    $query = "SELECT * FROM active_sessions WHERE player_id =".$ID;
    $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $bOnline = true;

    }

    return $bOnline;

  }


  /**********************************************************************
  * GetPlayerListNotInBuddyListSelectBox
  *
  */
  function GetPlayerListNotInBuddyListSelectBox($ConfigFile, $Player_ID){

    $query1 = "SELECT buddy_id FROM c4m_buddylist WHERE player_id =".$Player_ID;
    $return1 = mysqli_query($this->linkBuddyList,$query1) or die(mysqli_error($this->linkBuddyList));
    $num1 = mysqli_num_rows($return1);

    $BuddyListIDs = "";

    if($num1 != 0){

      $i = 0;
      while($i < $num1){

        $Buddy_id = trim($this->mysqli_result($return1,$i,"buddy_id"));

        $i++;

        if($i < $num1){
          $BuddyListIDs .= $Buddy_id.",";
        }else{
          $BuddyListIDs .= $Buddy_id."";
        }
    
      }

    }

    if($BuddyListIDs != ""){ 

      $query = "SELECT * FROM player WHERE player_id NOT IN(".$BuddyListIDs.") ORDER BY userid Asc";
      $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
      $num = mysqli_num_rows($return);

    }else{

      $query = "SELECT * FROM player ORDER BY userid Asc";
      $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
      $num = mysqli_num_rows($return);

    }

    echo "<select NAME='lstPlayers[]' multiple size='15' style='width:170'>";

    if($num != 0){

      $i = 0;
      while($i < $num){

        $player_id = trim($this->mysqli_result($return,$i,"player_id"));
        $userid = trim($this->mysqli_result($return,$i,"userid"));
        $signup_time  = trim($this->mysqli_result($return,$i,"signup_time"));
        $email = trim($this->mysqli_result($return,$i,"email"));

        if($this->IsPlayerDisabled($player_id) == false){

          echo "<option VALUE='".$player_id."'>".$userid."</option>";

        }

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * GetBuddyListSelectBox
  *
  */
  function GetBuddyListSelectBox($ConfigFile, $Player_ID){

    $query = "SELECT * FROM c4m_buddylist WHERE player_id = ".$Player_ID." ORDER BY bl_date DESC";
    $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
    $num = mysqli_num_rows($return);

    echo "<select NAME='lstBuddy[]' multiple size='15' style='width:170'>";

    if($num != 0){

      $i = 0;
      While($i < $num){
        
        //Get the buddy's information by his ID
        $query1 = "SELECT * FROM player WHERE player_id = ".$this->mysqli_result($return,$i,"buddy_id");
        $return1 = mysqli_query($this->linkBuddyList,$query1) or die(mysqli_error($this->linkBuddyList));
        $num1 = mysqli_num_rows($return1);

        if($num1 != 0){

          $ii = 0;
          while($ii < $num1){

            $player_id = trim($this->mysqli_result($return1,$ii,"player_id"));
            $userid = trim($this->mysqli_result($return1,$ii,"userid"));
            $signup_time = trim($this->mysqli_result($return1,$ii,"signup_time"));
            $email = trim($this->mysqli_result($return1,$ii,"email"));

            if($this->IsPlayerDisabled($player_id) == false){
              echo "<option VALUE='".$player_id."'>".$userid."</option>";
            }
            $ii++;

          }

        }

        $i++;

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * ClearBuddyList
  *
  */
  function ClearBuddyList($ConfigFile, $Player_ID){

    $query = "DELETE FROM c4m_buddylist WHERE player_id = ".$Player_ID;
    mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));

  }


  /**********************************************************************
  * IsPlayerDisabled
  *
  */
  function IsPlayerDisabled($PID){

    $query = "SELECT * FROM player2 WHERE player_id = ".$PID;
    $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
    $num = mysqli_num_rows($return);

    $bDisabled = false;

    if($num != 0){
      $bDisabled = true;
    }
  
    return $bDisabled;

  }


  /**********************************************************************
  * AddBuddyToList
  *
  */
  function AddBuddyToList($ConfigFile, $Player_ID, $Buddy_ID){

    $query = "INSERT INTO c4m_buddylist VALUES(NULL,".$Player_ID.",".$Buddy_ID.",NOW())";
    mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));

  }


  /**********************************************************************
  * DeleteBuddyFromBuddyList
  *
  */
  function DeleteBuddyFromBuddyList($ConfigFile, $Player_ID, $BuddyID){

    $query = "DELETE FROM c4m_buddylist WHERE player_id = ".$Player_ID." AND buddy_id=".$BuddyID."";
    mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));

  }

  /**********************************************************************
  * GetBuddyListForMobile
  *
  */
  function GetBuddyListForMobile($Player_ID){

    $query = "SELECT * FROM c4m_buddylist WHERE player_id = ".$Player_ID." ORDER BY bl_date DESC";
    $return = mysqli_query($this->linkBuddyList,$query) or die(mysqli_error($this->linkBuddyList));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $i = 0;
      While($i < $num){
        
        //Get the buddy's information by his ID
        $query1 = "SELECT * FROM player WHERE player_id = ".$this->mysqli_result($return,$i,"buddy_id");
        $return1 = mysqli_query($this->linkBuddyList,$query1) or die(mysqli_error($this->linkBuddyList));
        $num1 = mysqli_num_rows($return1);

        if($num1 != 0){

          $ii = 0;
          while($ii < $num1){

            $player_id = trim($this->mysqli_result($return1,$ii,"player_id"));
            $userid = trim($this->mysqli_result($return1,$ii,"userid"));
            $signup_time = trim($this->mysqli_result($return1,$ii,"signup_time"));
            $email = trim($this->mysqli_result($return1,$ii,"email"));

            if($player_id != "" && $this->IsPlayerDisabled($player_id) == false){

              echo "<BUDDY>\n";

              echo "<PID>".$player_id."</PID>\n";
              echo "<UID>".$userid."</UID>\n";

              if($this->IsPlayerOnline($ConfigFile, $player_id)){
                echo "<ONLINE>true</ONLINE>";
              }else{
                echo "<ONLINE>false</ONLINE>";
              }

              echo "</BUDDY>";

            }

            $ii++;

          }

        }

        $i++;

      }

    }

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->linkBuddyList);
  }

} //end of class definition
?>
