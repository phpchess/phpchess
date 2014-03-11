<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" cellspacing="0" cellpadding="0" border="0" align="center">
<tr>
<td  id="header"><table width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="logo" width="320"><h1>
<a href="<?php if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) )
	echo "./index.php";
else
	echo "./chess_members.php";
?>	">phpChess-Chess at its best</a></h1></td>
<td align="right">
<ul id="topnav">
<li><a href="<?php echo $Root_Path;?>chess_register.php">Register</a></li>           
        </ul>
        </td>
      </tr>
      </table>
      </td></tr></table>

