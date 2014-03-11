<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

  // Include this function after the $config declaration

  /**********************************************************************
  * GetStringFromStringTable
  *
  */
  function GetStringFromStringTable($strTag, $config){

    include($config);

    // Get Server Language
    $LanguageFile = "";
  
    if(isset($_SESSION['language'])){
 
      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$_SESSION['language'];
      }

    }else{

      $host = $conf['database_host'];
      $dbnm = $conf['database_name'];
      $user = $conf['database_login'];
      $pass = $conf['database_pass'];

      $link = mysql_connect($host, $user, $pass);
      mysql_select_db($dbnm);

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysql_query($query, $link) or die(mysql_error());
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

  // Alias for GetStringFromStringTable
  function _T($strTag, $config)
  {
	return GetStringFromStringTable($strTag, $config);
  }
  
  

  /**********************************************************************
  * GetServerLanguageFile
  *
  */
  function GetServerLanguageFile($config1){

    include($config1);

    $host = $conf['database_host'];
    $dbnm = $conf['database_name'];
    $user = $conf['database_login'];
    $pass = $conf['database_pass'];

    $link2 = mysql_connect($host, $user, $pass);
    mysql_select_db($dbnm);
 
    $query = "SELECT * FROM server_language";
    $return = mysql_query($query, $link2) or die(mysql_error());
    $num = mysql_numrows($return);

    $text = error;

    if($num != 0){
      $text = mysql_result($return, 0, "o_languagefile");
    }

    return $text;

  }


  /**********************************************************************
  * GetLanguageList
  *
  */
  function GetLanguageList($name, $config){

    echo "<select name='".$name."'>";

    // Open the language directory
    $dir = "./includes/languages/";

    if(is_dir($dir)){

      if($dh = opendir($dir)){

        while (($file = readdir($dh)) !== false){

          //check if the selected item is a file
          if(filetype($dir . $file) == "file"){

            //open the file and look for the language definition key (IDS_LANGUAGE_PACK)
            $Text = "Error";

            // Open the language file an get the contents
            $lines = file($dir . $file);

            $strTag = "IDS_LANGUAGE_PACK";

            // Search for the key
            for($x=1; $x<=sizeof($lines); $x++){

              if (preg_match("/\b".$strTag."\b/i", $lines[$x-1])){
                // We found the key
                list($Key, $strText, $junk) = preg_split("/\|\|/", $lines[$x-1], 3);

                $Text = trim($strText);

                // Exit loop
                break;

              }

            }

            $lines = "";
          
            if($Text != "Error"){

              echo "<option value='$file'";

              if(GetServerLanguageFile($config) == $file){
                echo " selected ";
              }

              echo ">".$Text."</option>";

            }

          }

        }

        closedir($dh);

      }

    }

    echo "</select>";

  }


  /**********************************************************************
  * GetStringFromStringTableHELP
  *
  */
  function GetStringFromStringTableHELP($strTag, $config){

    include($config);

    // Get Server Language
    $LanguageFile = "";

    if(isset($_SESSION['language'])){
 
      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/help/hlp_".$_SESSION['language'];
      }

    }else{

      $host = $conf['database_host'];
      $dbnm = $conf['database_name'];
      $user = $conf['database_login'];
      $pass = $conf['database_pass'];

      $link = mysql_connect($host, $user, $pass);
      mysql_select_db($dbnm);

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysql_query($query, $link) or die(mysql_error());
      $num = mysql_numrows($return);

      if($num != 0){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/help/hlp_".mysql_result($return, 0, "o_languagefile");
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

?>