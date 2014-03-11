<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

 //////////////////////////////////////////////////////////////////
 //  Left Menu Include
 //
 //

?>
<h3 class="title_h3">&nbsp;&nbsp;<?php echo GetStringFromStringTable("IDS_MENU_TXT_10", $config);?></h3>
<form name='frmChessLogin' method='post' action='./<?php echo $Page_Name;?>'> 
	<table width="95%" border="0" cellspacing="8" cellpadding="0" align="center" class="white">
		<tr>
			<td width="65"><?php echo GetStringFromStringTable("IDS_MENU_TXT_17", $config);?></td>
			<td width="100%"><Input type='text' name='txtName' size='15' class="input_text"></td>
		</tr>
		<tr>
			<td><?php echo GetStringFromStringTable("IDS_MENU_TXT_18", $config);?></td>
			<td><Input type='password' name='txtPassword' size='15' class="input_text"></td>
		</tr>
		<tr>
			<td><?php echo GetStringFromStringTable("IDS_MENU_TXT_25", $config);?></td>
			<td><?php GetLanguageList("slctlanguage", $config);?></td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td> <input type='checkbox' name='chkAutoLogin' value='1'><?php echo GetStringFromStringTable("IDS_MENU_TXT_19", $config);?></td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' name='cmdLogin' value='<?php echo GetStringFromStringTable("IDS_MENU_TXT_20", $config);?>' class='input_btn' />&nbsp;&nbsp;<input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_MENU_TXT_21", $config);?>' class='input_btn'></td>
		</tr>
		<tr>
			<td></td>
			<td>[<a href='./chess_retrieve_pass.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_TXT_22", $config);?></a>]</td>
		</tr>
	</table>
</form>					
							
							
							
