<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
  $CSnail = "";
  $oR3DCQuery->GetServerGameOptions($CSnail, $CSlow, $CNormal, $CShort, $CBlitz, $timing_mode);
?>

<form name='frmManageGameOpts' method='post' action='./cfg_srv_game_opts.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_TXT_1", $config);?></td><td class='row2'><input type='text' name='txtCSnail' class='post' value='<?php echo $CSnail;?>'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtCSlow' class='post' value='<?php echo $CSlow;?>'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_TXT_3", $config);?></td><td class='row2'><input type='text' name='txtCNormal' class='post' value='<?php echo $CNormal;?>'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_TXT_4", $config);?></td><td class='row2'><input type='text' name='txtCShort' class='post' value='<?php echo $CShort;?>'></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_TABLE_TXT_5", $config);?></td><td class='row2'><input type='text' name='txtCBlitz' class='post' value='<?php echo $CBlitz;?>'></td>
</tr>

<tr>
	<td class='row1'><?php echo __l('TIMING MODE') ;?></td>
	<td class='row2'>
		<?php
			$check = array('', '');
			$timing_mode = (int)$timing_mode;
			if($timing_mode == 0)
				$check[0] = 'checked';
			else
				$check[1] = 'checked';
			$html = "<input id='time_game' type='radio' name='timingMode' class='post' value='time_game' " . $check[0] . " /><label for='time_game'>" . __l('Time Game') . "</label><br/>";
			$html .= "<input id='time_players' type='radio' name='timingMode' class='post' value='time_players' " . $check[1] . "/><label for='time_players'>" . __l('Time Players') . "</label><br/>";
			echo $html;
		?>
	</td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdChange' value='<?php echo GetStringFromStringTable("IDS_ADMIN_GAME_OPTS_BTN_CV", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>