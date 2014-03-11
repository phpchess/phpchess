<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<!-- tinyMCE -->
<script language="javascript" type="text/javascript" src="../includes/tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript">
	tinyMCE.init({
		theme : "advanced",
		mode : "textareas",
		plugins : "table",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,formatselect,fontselect,fontsizeselect,forecolor",
		theme_advanced_buttons3_add_before : "tablecontrols,separator",
		extended_valid_elements : "font[face|size|color]",
                theme_advanced_toolbar_location: "top",
                relative_urls: "false",
                remove_script_host: "false"
	});
</script>
<!-- /tinyMCE -->

<form name='frmSendMail' method='post' action='./newsletter_mailer.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_NEWSLETTER_TABLE_HEADER", $config);?></font><b></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_NEWSLETTER_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtSubject' class='post' size='35'>
</td>
</tr>

<tr>
<td colspan='2' class='row2'>
<textarea id="elm1" name="elm2" style="width:100%" rows="30">

</textarea>
</td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdSend' value='<?php echo GetStringFromStringTable("IDS_ADMIN_NEWSLETTER_BTN_1", $config);?>' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
</center>
<br>