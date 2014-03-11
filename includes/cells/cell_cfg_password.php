<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
  // Change Password
  $bBadPass = false;
  $bPassChanged = false;

  $txtcurrentpasswd = trim($_POST['txtcurrentpasswd']);
  $txtnewpasswd = trim($_POST['txtnewpasswd']);
  $txtretypepasswd = trim($_POST['txtretypepasswd']);

  if($txtnewpasswd !="" && $txtretypepasswd != ""){

    $oR3DCQuery->ChangePassword($config, $_SESSION['id'], $txtnewpasswd, $txtretypepasswd);
    $_SESSION['password'] = $txtnewpasswd;

    $bPassChanged = true;

    if($_COOKIE['TestCookie'] != ""){ 

      setcookie("TestCookie", $cookie_data, time()-360000);
      $cookie_data = $_SESSION['user']."|".base64_encode($txtnewpasswd);
      setcookie("TestCookie", $cookie_data, time()+360000);

    }

  }


  ///////////////////////////////////////////////////////////////////////
  // Forum Management
  ///////////////////////////////////////////////////////////////////////

  $FMfile = "./includes/forum_management.php";
  if(file_exists($FMfile) && $bPassChanged){

    $FMPassChange = true;
    $FMPASS = $txtnewpasswd;
    $FMUserID = $_SESSION['id'];
    $FMUserName = $_SESSION['user'];

    include($FMfile);

  }

  ///////////////////////////////////////////////////////////////////////


  if($bBadPass){
?>

    <table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
    <tr>
    <td class='row2'><?php echo GetStringFromStringTable("IDS_MANAGE_CHANGEPASSWORD_ERROR_TXT_1", $config);?></td>
    </tr>
    </table>

<?php
}
?>

<form name='frmchangepasswd' method='post' action='./chess_cfg_password.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_MANAGE_CHANGEPASSWORD_TABLE_HEADER", $config);?></font></td></tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_CHANGEPASSWORD_TABLE_TXT_2", $config);?></td><td class='row2'><input type='password' name='txtnewpasswd' class='post' size='50'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_MANAGE_CHANGEPASSWORD_TABLE_TXT_3", $config);?></td><td class='row2'><input type='password' name='txtretypepasswd' class='post' size='50'></td>
</tr>
<tr>
<td class='row2' align='right' colspan='2'><input type='submit' name='cmdChangePassword' value='<?php echo GetStringFromStringTable("IDS_MANAGE_CHANGEPASSWORD_BTN_CHANGE", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>
