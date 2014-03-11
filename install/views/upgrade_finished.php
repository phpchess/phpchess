<style>
.format p {
	padding-top: 0.5em;
	padding-bottom: 0.5em;
}
</style>
<script type="text/javascript" src="../includes/jquery/jquery-1.7.1.min.js" ></script>
<div class="format">
	<h3 class="title_h3">PHPCHESS UPGRADE - Finished</h3>

	<h3>Upgrade is finished</h3>
	
	<b>Please ensure the install directory and its contents are DELETED before you make the site public!</b>

	<p>
		You can find the phpChess admin area <a href="../admin/index.php">HERE.</a><br/>
		You can find your phpChess home page <a href="../index.php">HERE.</a>
	</p>
	
	<h3>Thank you for installing phpchess.</h3>
	
	<h3>Latest news from phpchess.com:</h3>
	
	<div id="feed_display"></div>
</div>

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