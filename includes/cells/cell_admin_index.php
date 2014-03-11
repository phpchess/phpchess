<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<script type="text/javascript" src="../includes/jquery/jquery-1.7.1.min.js" ></script>
<form name='frmChessLogin' method='post' action='index.php' > <!--onsubmit="return validateForm();"-->
	<table border='0' align='center' class='forumline' cellpadding="3" cellspacing="1">
		<tr>
			<td colspan='2' class='tableheadercolor'>
				<b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_LOGIN_TABLE_HEADER", $config);?></font><b>
			</td>
		</tr>
		<tr>
			<td class="row1"><?php echo GetStringFromStringTable("IDS_ADMIN_LOGIN_TABLE_TXT_1", $config);?></td>
			<td class="row2"><Input type='text' name='txtName' size='50' class="post"></td>
		</tr>
		<tr>
			<td class="row1"><?php echo GetStringFromStringTable("IDS_ADMIN_LOGIN_TABLE_TXT_2", $config);?></td>
			<td class="row2"><Input type='password' name='txtPassword' size='50' class="post"></td>
		</tr>
		<tr>
			<td colspan='2' class='tableheadercolor'>
				<input type='submit' name='cmdLogin' value='<?php echo GetStringFromStringTable("IDS_ADMIN_LOGIN_BTN_LOGIN", $config);?>' class='mainoption'>
				<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_ADMIN_LOGIN_BTN_RESET", $config);?>' class='mainoption'>
			</td>
		</tr>
	</table>
</form>
<br />
<br />
<br />
<table border='0' align='center' class='forumline' cellpadding="3" cellspacing="1">
	<tr/>
		<td class='tableheadercolor'><b><font class="sitemenuheader"><?php echo "Administration News from phpChess.com"?></font><b>
		</td>
	</tr>
	<tr>
		<td id="feed_display" >&nbsp;</td>
	</tr>
</table>

<script id="loader">
var checker;
var dots = 0;
$(document).ready(function(){
	var el = '<script id="bob" type="text/javascript" src="http://www.phpchess.com/?feed=json" ><\/script>';
	$('#loader').after(el);
	checker = setInterval(check, 333);
});

function check()
{
	if(window.feed !== undefined)
	{
		clearInterval(checker);
		display_feed(window.feed);
	}
	else
	{
		dots++;
		var html = '&nbsp;';
		if(dots > 4) dots = 0;
		for(var i = 0; i < dots; i++)
			html += '.';
		$('#feed_display').html(html);
	}
}
function display_feed(feed)
{
	var items = [];
	for(var i = 0; i < feed.length && i < 5; i++)
	{
		items.push('<div>' + feed[i].date + '<br/><a href="' + feed[i].permalink + '">' + feed[i].title + '</a><br/></div>');
	}
	var html = '<br/>' + items.join('<br/>');
	$('#feed_display').html(html);
}
</script>