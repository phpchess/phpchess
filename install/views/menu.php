<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="207" border="0" cellspacing="0" cellpadding="0" align="center">
	<tr>
	<td align="left">
		<h3 class="menu_title"><?php echo ($mode = 'install' ? 'Installation Process' : 'Upgrade Process'); ?></h3>
		<ul class="mainmenu">
			<?php
				$cnt = 1;
				$img = array('../skins/default/images/button_ok.png', '../skins/default/images/button_cancel.png', '../skins/default/images/button_ok_grey.png');
				$active_item = $menu_items[$g_stage];
				$img_id = 0;
				$img_url = $img[0];
				foreach($menu_items as $item)
				{
					if($item == $active_item)
					{
						$img_url = $img[2];
						$img_id = 2;
					}
					echo <<<qq
			<li class="active_mainmenu">
				$cnt. $item<span style="float: right;"><img src="$img_url" /></span>
			</li>
qq;
					$cnt++;
				}
			
			?>
		</ul>
	</td>
	</tr>
	<tr>
	<td align="left" class="white">
		<h3 class="menu_title">Other Links</h3>
		<ul>
			<li>
				<a href="http://www.phpchess.com/?page_id=151">Help</a>
			</li>
			<li>
				<a href="http://www.phpchess.com">phpChess.com</a>
			</li>
			<li>
				<a href="http://www.phpchess.com/wiki/">Wiki</a>
			</li>
			<li>
				<a href="http://www.phpchess.com/?page_id=12">@Support</a>
			</li>
		</ul>
	</td>
	</tr>
	<tr>
</table>





