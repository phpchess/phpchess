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
  function __construct($ConfigFile){

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

    $this->linkCAvatars = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->linkCAvatars,$this->dbnm);

    if(!$this->linkCAvatars){
      die("CAvatars.php: ".mysqli_error($this->linkCAvatars));
    }

  }

  
  function mysqli_result($result, $number, $field=0) {
      mysqli_data_seek($result, $number);
      $row = mysqli_fetch_array($result);
      return $row[$field];
  }


  /**********************************************************************
  * CreateUpdateAvatar
  * Params: Player ID, Image Name
  * Return: Create the users avatar
  */
  function CreateUpdateAvatar($pid, $imagename){

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));
    $num = mysqli_num_rows($return);

    if($num != 0){

      // Avatar exists
      // delete the current avatar image

      list($one, $two) = explode("/", $this->mysqli_result($return, 0, "a_imgname"));

      if(trim($one) == "USER"){
        @unlink($this->AvatarDirAbsLocation.$this->mysqli_result($return, 0, "a_imgname"));
      }

      // delete the current db record
      $delete = "DELETE FROM c4m_avatars WHERE a_playerid = ".$pid."";
      mysqli_query($this->linkCAvatars,$delete) or die(mysqli_error($this->linkCAvatars));

      // Create the new db record
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysqli_query($this->linkCAvatars,$insert) or die(mysqli_error($this->linkCAvatars));

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysqli_query($this->linkCAvatars,$insert) or die(mysqli_error($this->linkCAvatars));

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
    $return = mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));
    $num = mysqli_num_rows($return);

    if($num != 0){
       $strImageName = $this->mysqli_result($return, 0, "a_imgname");
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
    $return = mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));
    $num = mysqli_num_rows($return);

    if($num != 0){

      // Avatar exists
     
      // delete the current avatar image
     
      list($one, $two) = explode("/", $this->mysqli_result($return, 0, "a_imgname"));

      if(trim($one) == "USER"){

        unlink($this->AvatarDirAbsLocation.$this->mysqli_result($return, 0, "a_imgname"));

      }

      // delete the current db record
      $delete = "DELETE FROM c4m_avatars WHERE a_playerid = ".$pid."";
      mysqli_query($this->linkCAvatars,$delete) or die(mysqli_error($this->linkCAvatars));

      // Create the new db record
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysqli_query($this->linkCAvatars,$insert) or die(mysqli_error($this->linkCAvatars));

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysqli_query($this->linkCAvatars,$insert) or die(mysqli_error($this->linkCAvatars));

    }

  }
  
  
  function SetAvatar($pid, $imagename){

    // Check to see if the user has an avatar already
    $query = "SELECT * FROM c4m_avatars WHERE a_playerid = ".$pid."";
    $return = mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));
    $num = mysqli_num_rows($return);

    if($num != 0){

      // Avatar exists
     
      // If using a user uploaded avatar, and switching to a system avatar,
	  // delete the user's avatar.
     
      list($one, $two) = explode("/", $this->mysqli_result($return, 0, "a_imgname"));

      if(trim($one) == "USER" && $this->mysqli_result($return, 0, "a_imgname") != $imagename){

        unlink($this->AvatarDirAbsLocation.$this->mysqli_result($return, 0, "a_imgname"));

      }
	  // Update the current db record
	  $query = "UPDATE c4m_avatars SET `a_imgname` = \"$imagename\", `a_datechanges` = NOW() WHERE `a_playerid` = $pid";
	  mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));

    }else{

      // Avatar does not exist
      $insert = "INSERT INTO c4m_avatars VALUES(".$pid.",'".$imagename."', NOW())";
      mysqli_query($this->linkCAvatars,$insert) or die(mysqli_error($this->linkCAvatars));

    }

  }


  /**********************************************************************
  * GetAdminAvatarSettings
  * 
  */
  function GetAdminAvatarSettings(){

    $query = "SELECT * FROM admin_avatar_settings WHERE o_id = 1";
    $return = mysqli_query($this->linkCAvatars,$query) or die(mysqli_error($this->linkCAvatars));
    $num = mysqli_num_rows($return);
 
    $setting = 1;

    if($num != 0){
      $setting = $this->mysqli_result($return, 0, "o_setting");
    }

    return $setting;

  }


  /**********************************************************************
  * UpdateAdminAvatarSettings
  * 
  */
  function UpdateAdminAvatarSettings($value){

    $update = "UPDATE admin_avatar_settings SET o_setting = '".$value."' WHERE o_id = 1";
    mysqli_query($this->linkCAvatars,$update) or die(mysqli_error($this->linkCAvatars));

  }


  /**********************************************************************
  * Close (Deconstructor)
  *
  */
  function Close(){
    mysqli_close($this->linkCAvatars);
  }


} //end of class definition
?>