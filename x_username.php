<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  define('CHECK_PHPCHESS', true);

  header("Content-Type: text/html; charset=utf-8");

  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."includes/language.php");

  $bLogedin = false;
  $bUsernameChanged = false;
  $strErrorMsg = "";
  $strNewUserName = "";

  $cmdViewUsername = $_POST['cmdViewUsername'];
  $txtOldUsername = $_POST['txtOldUsername'];
  $txtPassword = $_POST['txtPassword'];
  $cmdChangeUsername = $_POST['cmdChangeUsername'];
  $txtNewUsername = $_POST['txtNewUsername'];

  //Instantiate the CR3DCQuery Class
  $oR3DCQuery = new CR3DCQuery($config);

  // Log the user in
  if(($cmdViewUsername != "" || $cmdChangeUsername) && $txtOldUsername != "" && $txtPassword != ""){

    $strNewUserName = $oR3DCQuery->CheckUserNameLogin($txtOldUsername, $txtPassword);

    if($strNewUserName != ""){
      $bLogedin = true;
    }

  }

  // Change the users old username
  if($cmdChangeUsername != "" && $txtNewUsername != "" && $txtPassword != "" && $txtOldUsername != "" && $bLogedin){

    if($oR3DCQuery->IsUserNameLegal($txtNewUsername)){

      if(!$oR3DCQuery->UserNameExists($txtNewUsername)){

        if($oR3DCQuery->ChangeUserNameByOldName($strNewUserName, $txtNewUsername)){
          $bUsernameChanged = true;
        }

      }else{
        $strErrorMsg = "IDS_USERNAME_EXISTS";
      }

    }else{
      $strErrorMsg = "IDS_USERNAME_INVALID";
    }

  }elseif($cmdChangeUsername != "" && ($txtPassword == "" || $txtOldUsername == "" || !$bLogedin)){
    header("Locaton: ./x_username.php");
  }

  $oR3DCQuery->Close();
  unset($oR3DCQuery);

?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_74", $config);?></title>
<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">

</head>
<body>

<?php if($bUsernameChanged){?>

<table cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_9", $config);?></td>
</tr>
<tr>
<td class='row1'><center><input type='button' name='btnClose' value='<?php echo GetStringFromStringTable("IDS_USERNAME_TXT_10", $config);?>' onclick='javascript:window.close();'></center></td>
</tr>
</table>

<?php }elseif($bLogedin && $strNewUserName != "" && !$bUsernameChanged){?>

<form name='frmLogin' method='POST' action='./x_username.php'>
<table cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_3", $config);?></b></td>
</tr>
<tr>
<td class='row1' colspan='2'>

<?php
switch($strErrorMsg){

  case "IDS_USERNAME_EXISTS":
    echo "<font color='red'>".GetStringFromStringTable("IDS_USERNAME_TXT_7", $config)."</font>";
    break;

  case "IDS_USERNAME_INVALID":
    echo "<font color='red'>".GetStringFromStringTable("IDS_USERNAME_TXT_8", $config)."</font>";
    break;

}
?>

</td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_4", $config);?></td><td class='row2'><?php echo $strNewUserName;?></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_5", $config);?></td><td class='row2'><input type='text' name='txtNewUsername'></td>
</tr>
<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChangeUsername' value='<?php echo GetStringFromStringTable("IDS_USERNAME_TXT_6", $config);?>'></td>
</tr>
</table>
<input type='hidden' name='txtOldUsername' value='<?php echo $txtOldUsername;?>'>
<input type='hidden' name='txtPassword' value='<?php echo $txtPassword;?>'>
</form>

<?php }else{?>

<form name='frmLogin' method='POST' action='./x_username.php'>
<table cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='row1' colspan='2'><b><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_3", $config);?></b></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtOldUsername'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_USERNAME_TXT_2", $config);?></td><td class='row2'><input type='password' name='txtPassword'></td>
</tr>
<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdViewUsername' value='<?php echo GetStringFromStringTable("IDS_USERNAME_TXT_3", $config);?>'></td>
</tr>
</table>
</form>

<?php }?>

</body>
</html>