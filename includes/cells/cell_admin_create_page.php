<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($ViewFinished == false){
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

<form name='frmManagePage' method='post' action='./create_page.php'>
<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_1", $config);?></font><b></td></tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_2", $config);?></td>
<td class='row2'>
<?php
  $oR3DCQuery->CreateResourceHTMLList($AID);
?>
</td>
</tr>

<tr>
<td colspan='2' class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_3", $config);?></td>
</tr>
<tr>
<td colspan='2' class='row2'>
<textarea id="elm1" name="content2" style="width:100%" rows="25"></textarea>
</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_4", $config);?></td>
<td class='row2'>

<select name='slctType'>
<option value='lsn' selected><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_5", $config);?></option>
<option value='lan'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_6", $config);?></option>
<option value='tnf'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_7", $config);?></option>
</select>

</td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_8", $config);?></td><td class='row2'><input type='text' name='txtsolution' size='40' class='post'></td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdSubmit' value='<?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_9", $config);?>' class='mainoption'></td>
</tr>

</table>

<input type='hidden' name='aid' value='<?php echo $AID;?>'>
</form>

<?php
}else{
?>

<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr>
<td class='row1' align='center'><input type='button' name='btnClose' value='<?php echo GetStringFromStringTable("IDS_CREATE_PAGE_TXT_10", $config);?>' onclick='javascript:window.close();' class='mainoption'></td>
</tr>
</table>

<?php
}
?>