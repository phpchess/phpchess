<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<!-- tinyMCE -->
<script language="javascript" type="text/javascript" src="../includes/tiny_mce/tiny_mce.js"></script>
<script language="javascript" type="text/javascript" src="../includes/jquery/jquery-1.7.1.min.js"></script>
<script language="javascript" type="text/javascript">
	/*tinyMCE.init({
		theme : "advanced",
		mode : "textareas",
		plugins : "table",
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,separator,formatselect,fontselect,fontsizeselect,forecolor",
		theme_advanced_buttons3_add_before : "tablecontrols,separator",
		extended_valid_elements : "font[face|size|color]",
		theme_advanced_toolbar_location: "top",
		relative_urls: "false",
		remove_script_host: "false",
		content_css: "<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css"
	});*/
	/*tinyMCE.init({
		selector: "#faqtxt",
		theme: 'advanced',
		plugins: 'preview',
		theme_advanced_buttons3_add : "preview",
		plugin_preview_width : "500",
		plugin_preview_height : "600",
		//plugins: 'advlist autolink autoresize autosave bbcode charmap contextmenu directionality emoticons example example_dependency fullpage fullscreen insertdatetime layer legacyoutput lists media nonbreaking noneditable pagebreak paste preview print save searchreplace spellchecker tabfocus table template visualblocks visualchars wordcount',
		content_css: "<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css"
	})*/
</script>
<!-- /tinyMCE -->

<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
	<tr>
		<td colspan='2' class='tableheadercolor'>
			<b><font class="sitemenuheader"><?php echo __l('Edit FAQ');?></font><b>
			<br/>
			<br/>
		</td>
	</tr>

	<tr>
		<td colspan='2' class='row2'>
		<textarea id="faqtxt" style="width:100%" rows="30"><?php echo $faq_content; ?></textarea>
		</td>
	</tr>

	<tr>
		<td class='row1' colspan='2'>
			<input type='submit' id="savefaq" value='<?php echo __l('Save');?>' class='mainoption'>
		</td>
	</tr>
</table>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo __l('Back To Main Menu');?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
</center>
<br>

<script type="text/javascript">
$(document).ready(function(){

	$('#savefaq').click(save_faq);
	tinyMCE.init({
		selector: "#faqtxt",
		theme: 'advanced',
		plugins: 'preview',
		theme_advanced_buttons3_add : "preview",
		plugin_preview_width : "500",
		plugin_preview_height : "600",
		content_css: "<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css"
	})
});

function save_faq()
{
	var content = tinyMCE.activeEditor.getContent();
	$.post('', {content: content}, function(){
		alert('<?php echo __l('FAQ was updated'); ?>');
	});
}
</script>