<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmEmailSettings' method='post' action='./cfg_srv_email.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_1", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_2", $config);?></td>
<td class='row2'><input type='text' name='txtsmtp' value='<?php echo $smtp;?>' class='post'></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_3", $config);?></td>
<td class='row2'><input type='text' name='txtsmtpport' value='<?php echo $port;?>' class='post'></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_5", $config);?></td>
<td class='row2'><input type='text' name='txtuser' value='<?php echo $user;?>' class='post'></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_6", $config);?></td>
<td class='row2'><input type='text' name='txtpass' value='<?php echo $pass;?>' class='post'></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_7", $config);?></td>
<td class='row2'><input type='text' name='txtdomain' value='<?php echo $domain;?>' class='post'></td>
</tr>

<tr>
<td class='row2' colspan='2'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_CFGSRVEMAIL_TXT_4", $config);?>' name='cmdChange' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>