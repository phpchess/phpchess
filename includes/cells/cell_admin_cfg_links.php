<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

  $name = "";
  $url = "";
  $oR3DCQuery->GetMainLinkByRef(&$name, &$url);

?>

<form name='frmManageLink' method='post' action='./cfg_links.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>

<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_LINK_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_LINK_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtLinkName' class='post' value='<?php echo $name;?>' size='50'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_LINK_TABLE_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtURL' class='post' value='<?php echo $url;?>' size='50'></td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChange' value='<?php echo GetStringFromStringTable("IDS_ADMIN_LINK_BTN_CV", $config);?>' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>