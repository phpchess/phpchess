<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php if($bCheck){?>
<table border='0' align='center' width='98%' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='2' class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_BUG_REPORT_TXT_0", $config);?></td>
</tr>
</table>
<?php }elseif(!$bCheck && $cmdSendReport != ""){?>
<table border='0' align='center' width='98%' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='2' class='row2'><?php echo GetStringFromStringTable("IDS_CHESS_BUG_REPORT_TXT_1", $config);?></td>
</tr>
</table>
<?php }?>

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

<form name='frmBugReport' method='post' action='./bugreport.php'>
<table border='0' align='center' width='98%' class="forumline" cellpadding="3" cellspacing="1" >
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader">Bug Report</font><b></td>
</tr>

<tr>
<td class='row1'>Bug Report Type:</td>
<td class='row2'>
<select name='slctType'>
<option value='Game Related Problems' selected>Game Related Problems</option>
<option value='Tournament Related Problems'>Tournament Related Problems</option>
<option value='Other'>Other</option>
</select>
</td>
</tr>

<tr>
<td class='row1'>Bug Condition:</td>
<td class='row2'>
<select name='slctCondition'>
<option value='Low' selected>Low</option>
<option value='Medium'>Medium</option>
<option value='High'>High</option>
<option value='Emergency'>Emergency</option>
</select>
</td>
</tr>

<tr>
<td class='row1'>Email:</td>
<td class='row2'>
<input type='text' name='txtEmail' Value='' size='40'>
</td>
</tr>

<tr>
<td colspan='2' class='row1'>Report</td>
</tr>
<tr>
<td colspan='2' class='row2'>
Please indicate how you came across the bug and the steps needed to recreate it.<br>
* If you are reporting a game bug, please include the PGN and FEN.
</td>
</tr>
<tr>
<td colspan='2' class='row2'>
<textarea id="elm1" name="elm2" style="width:100%" rows="30"></textarea>
</td>
</tr>

<tr>
<td colspan='2' class='row1'>
<input type="submit" name="cmdSendReport" value="Send Bug Report" class='mainoption'>
</td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
</center>
<br>