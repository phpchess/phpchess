<style>
#form p {
	margin-top: 10px;
	margin-bottom: 10px;
}
</style>

<h3 class="title_h3">PHPCHESS INSTALLATION - phpChess Server</h3>

<form action="" method="POST" id="form">

	<p>The phpchess installation requires a site name which will be used on the main chess page. Most things are ok, but we suggest you stay clear of special characters as these can lead to issues in some browsers. So "Farling chess club" is ok, but we would advise against "Farling's chess club". This entry is worth thinking about as it stays with you and is quite difficult to change.<p>

	<p>In regards to the path information, don't change the default values unless you really know what you are doing.</p>

	<table>
		<tr>
			<td>Site Name</td>
			<td><input name="site_name" type="text" value="<?php echo $g_params['name']; ?>" /></td>
		</tr>
		<tr>
			<td>Site URL</td>
			<td><input name="site_url" type="text" value="<?php echo $g_params['url']; ?>" /></td>
		</tr>
		<tr>
			<td>Absolute Path</td>
			<td><input name="absolute_path" type="text" value="<?php echo $g_params['absolute_path']; ?>" /></td>
		</tr>
		<tr>
			<td>Absolute Avatar Path</td>
			<td><input name="absolute_avatar_path" type="text" value="<?php echo $g_params['absolute_avatar_path']; ?>" /></td>
		</tr>



	</table>
	
	<input type="submit" name="back" value="Back" /><input type="submit" name="next" value="Next" />

</form>