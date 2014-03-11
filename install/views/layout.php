<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<title>Install PHPChess</title>

	<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

	<link rel="stylesheet" href="../skins/default/layout.css" type="text/css">
	<link rel="stylesheet" href="../includes/jquery/cupertino/jquery-ui-1.10.3.custom.min.css" type="text/css">
	<?php include('../includes/javascript.php');?>
	<script src="../includes/jquery/jquery-1.7.1.min.js" type="text/javascript"></script>
	<script src="../includes/jquery/jquery-ui-1.10.3.custom.min.js" type="text/javascript"></script>

	<style>
		input[type=text], input[type=password] {
			width: 300px;
		}
	</style>
	
</head>
<body >

<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tr>
	<td id="header">
		<!--Header Begin-->
		<?php include('./views/header.php');?>
		<!--Header End-->
	</td>
	</tr>
	<tr>
	<td>
		<table width="100%" border="0" cellspacing="1" cellpadding="0">
			<tr>
			<td class="greenbg" width="18">
				<img src="../skins/default/images/spacer.gif" width="1" height="22" />
			</td>
			<td class="bluebg" width="225">
				&nbsp;
			</td>
			<td class="shadow_green">
				&nbsp;
			</td>
			</tr>
			<tr>
			<td class="lgreenbg" width="18">
				&nbsp;
			</td>
			<td class="whitebg" width="225">
				<!--Left Menu Begin-->
				<?php include('./views/menu.php');?>
				<!--Left Menu Finish-->
			</td>
			<td class="shadow_lgreen">
				<div style="padding: 5px;" class="white">
				<!--Content Begin-->
				<?php 
				
				include('./views/' . $g_stages[$g_stage] . '.php');

				?>
				<!--Content Finish-->
				</div>
			</td>
			</tr>
			<tr>
			<td class="greenbg" width="18">
				&nbsp;
			</td>
			<td class="bluebg" width="225" align="center" style="padding:10px 0;">
				<span class="bluebg" style="padding:10px 0;">
					<img src="../skins/default/images/logo_footer.gif" width="180" height="44" />
				</span>
			</td>
			<td class="shadow_green" valign="middle">
			</td>
			</tr>
		</table>
	</td>
	</tr>
</table>

</body>

</html>

