<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmManageuser' method='post' action='./cfg_avatar_settings.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo __l("Manage Avatar Settings");?></font><b></td>
</tr>

<tr>
<td colspan='2' class='row2'><?php echo __l("Please select your avatar setting below:");?></td>
</tr>

<?php
$checked = $avatarmethod == 1 ? 'checked' : '';
$str = __l("Allow uploads");
echo <<<qq
	<tr>
		<td class='row1' width='5%'>
			<input type='checkbox' name='rdomethod' value='1' $checked>
		</td>
		<td class='row2'>
			$str
		</td>
	</tr>
qq;
?>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChange' value='<?php echo __l("Save");?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo __l("Back To Main Page");?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo __l("Back To Server Management");?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>