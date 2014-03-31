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

class CAvatars{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkCAvatars;
  var $ChessCFGFileLocation;
  var $AvatarDirAbsLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
  * CAvatars (Constructor)
  *
  */
  function CAvatars($ConfigFile){

    ////////////////////////////////////////////////////////////////////////////
    // Sets the chess config file location (absolute location on the server)
    ////////////////////////////////////////////////////////////////////////////
    $this->ChessCFGFileLocation  = $ConfigFile;
    ////////////////////////////////////////////////////////////////////////////

    include($ConfigFile);

    $this->AvatarDirAbsLocation = $conf['avatar_absolute_directory_location'];

    $this->host = $conf['database_host'];
    $this->dbnm = $conf['database_name'];
    $this->user = $conf['database_login'];
    $this->pass = $conf['database_pass'];

    $this->linkCAvatars = mysql_connect($this->host, $this->user, $this->pass);
    mysql_select_db($this->dbnm);

    if(!$this->linkCAvatars){
      die("CAvatars.php: ".mysql_error());
    }

  }


  /**********************************************************************
  * CreateUpdateAvatar
  * Params: Player ID, Image Name
  * Return: Create the users avatar
  */
  function CreateUpdateAvatar($pid, $imagename){

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysql_query($query, $this->linkCAvatars) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Avatar exists
      // delete the current avatar image

      list($one, $two) = explode("/", mysql_result($return, 0, "a_imgname"));

      if(trim($one) == "USER"){
        @unlink($this->AvatarDirAbsLocation.mysql_result($return, 0, "a_imgname"));
      }

      // delete the current db record
      $delete = "DELETE FROM c4m_avatars WHERE a_playerid = ".$pid."";
      mysql_query($delete, $this->linkCAvatars) or die(mysql_error());

      // Create the new db record
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysql_query($insert, $this->linkCAvatars) or die(mysql_error());

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysql_query($insert, $this->linkCAvatars) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetAvatarImageName
  * Params: Player ID
  * Return: retieves the users avatar image name
  */
  function GetAvatarImageName($pid){

    $strImageName = "";

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysql_query($query, $this->linkCAvatars) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){
       $strImageName = mysql_result($return, 0, "a_imgname");
    }

    return $strImageName;

  }


  /**********************************************************************
  * GetAvatarDriveList
  * Params: n/a
  * Return: retieves all the images in the avatar directory
  */
  function GetAvatarDriveList(){

    // Open the language directory
    $dir = $this->AvatarDirAbsLocation;

    if(is_dir($dir)){

      if($dh = opendir($dir)){

        while (($file = readdir($dh)) !== false){

          //check if the selected item is a folder
          if(filetype($dir . $file) == "dir"){
        
            if($file != "." && $file != ".." && $file != "USER"){

              // Print the folder name
              echo "<tr><td colspan='5' class='row1' align='left'><b>".$file."</b></td></tr>";

              $dir2 = $dir.$file."/";

              if(is_dir($dir2)){

                if($dh1 = opendir($dir2)){

                  echo "<tr>";

                  $colcount = 0;

                  while (($file1 = readdir($dh1)) !== false){

                    //check if the selected item is a file
                    if(filetype($dir2 . $file1) == "file"){

                      if($colcount == 5){
                        $colcount=0;
                        echo "</tr><tr>";
                      }
                    
                      echo "<td class='row2'><a href='./chess_cfg_avatar.php?avatar=".$file."/".$file1."&cmdAddAvatar=yes'><img src='./avatars/".$file."/".$file1."' border='0'></a></td>";
                    
                      $colcount++;

                    }
 
                  }

                  $ntdneeded = 5 - $colcount;

                  if($ntdneeded > 0 && $ntdneeded != 5){

                    $i=0;
                    while($i < $ntdneeded){
                      echo "<td class='row2'></td>";
                      $i++;
                    }
 
                  }

                  echo "</tr>";

                  closedir($dh1);

                }

              }


            }

          }

        }

        closedir($dh);

      }

    }

  }


  /**********************************************************************
  * CreateUpdateAvatar2
  * Params: Player ID, Image Name
  * Return: Create the users avatar
  */
  function CreateUpdateAvatar2($pid, $imagename){

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysql_query($query, $this->linkCAvatars) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Avatar exists
     
      // delete the current avatar image
     
      list($one, $two) = explode("/", mysql_result($return, 0, "a_imgname"));

      if(trim($one) == "USER"){

        unlink($this->AvatarDirAbsLocation.mysql_result($return, 0, "a_imgname"));

      }

      // delete the current db record
      $delete = "DELETE FROM c4m_avatars WHERE a_playerid = ".$pid."";
      mysql_query($delete, $this->linkCAvatars) or die(mysql_error());

      // Create the new db record
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysql_query($insert, $this->linkCAvatars) or die(mysql_error());

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysql_query($insert, $this->linkCAvatars) or die(mysql_error());

    }

  }
  
  
  function SetAvatar($pid, $imagename){

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysql_query($query, $this->linkCAvatars) or die(mysql_error());
    $num = mysql_numrows($return);

    if($num != 0){

      // Avatar exists
     
      // If using a user uploaded avatar, and switching to a system avatar,
	  // delete the user's avatar.
     
      list($one, $two) = explode("/", mysql_result($return, 0, "a_imgname"));

      if(trim($one) == "USER" && mysql_result($return, 0, "a_imgname") != $imagename){

        unlink($this->AvatarDirAbsLocation.mysql_result($return, 0, "a_imgname"));

      }
	  // Update the current db record
	  $query = "UPDATE c4m_avatars SET `a_imgname` = \"$imagename\", `a_datechanges` = NOW() WHERE `a_playerid` = $pid";
	  mysql_query($query, $this->linkCAvatars) or die(mysql_error());

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysql_query($insert, $this->linkCAvatars) or die(mysql_error());

    }

  }


  /**********************************************************************
  * GetAdminAvatarSettings
  * 
  */
  function GetAdminAvatarSettings(){

    $query = "SELECT * FROM admin_avatar_settings WHERE o_id = 1";
    $return = mysql_query($query, $this->linkCAvatars) or die(mysql_error());
    $num = mysql_numrows($return);
 
    $setting = 1;

    if($num != 0){
      $setting = mysql_result($return, 0, "o_setting");
    }

    return $setting;

  }


  /**********************************************************************
  * UpdateAdminAvatarSettings
  * 
  */
  function UpdateAdminAvatarSettings($value){

    $update = "UPDATE admin_avatar_settings SET o_setting = '".$value."' WHERE o_id = 1";
    mysql_query($update, $this->linkCAvatars) or die(mysql_error());

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysql_close($this->linkCAvatars);
  }


} //end of class definition
?>