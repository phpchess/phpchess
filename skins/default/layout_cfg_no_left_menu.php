<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td id="header">
	<!--Header Begin-->
	<?php include($Root_Path."skins/".$SkinName."/header.php");?>
	<!--Header End-->
	</td>
  </tr>
  <tr>
    <td>
		<table width="100%" border="0" cellspacing="1" cellpadding="0">
		  <tr>
			<td class="shadow_green" height="22">&nbsp;</td>
		  </tr>
		  <tr>
			<td class="shadow_lgreen">
			
<?php 

$strCustomCellPath = $Root_Path."skins/".$SkinName."/cells/".$Contentpage;
if(file_exists($strCustomCellPath)){
  include($strCustomCellPath);
}else{
  include($Root_Path."includes/cells/".$Contentpage);
}

?>

<!--Content Finish--></td>
		  </tr>
		  <tr>
			<td class="shadow_green" valign="middle">
			<?php include($Root_Path."skins/".$SkinName."/footer.php");?>		</td>
		  </tr>
		</table>

	</td>
  </tr>
</table>



