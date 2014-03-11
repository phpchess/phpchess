<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($ViewFinished == false){
?>

<script language="JavaScript">
function ChangeText(){

  strType = document.frmManageResource.optType.options[document.frmManageResource.optType.selectedIndex].value;
		
  if(strType == "pgn"){
    document.getElementById('x1').innerHTML = "<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_1", $config);?>";
    document.getElementById('x2').innerHTML = "<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_2", $config);?>";
  }else{
    document.getElementById('x1').innerHTML = "<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_3", $config);?>";
    document.getElementById('x2').innerHTML = "<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_4", $config);?>";
  }

}
</script>

<form name='frmManageResource' method='post' action='./create_resource.php' enctype="multipart/form-data">
<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr><td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_5", $config);?></font><b></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_6", $config);?></td><td class='row2'><input type='text' name='txtName' size='35' class='post'></td>
</tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_7", $config);?></td><td class='row2'><input type='file' name='fFileName' class="post"></td>
</tr>

<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_8", $config);?></td>
<td class='row2'>

<select name='optType' onChange='javascript:ChangeText()'>
<option value='mov' selected><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_9", $config);?></option>
<option value='img'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_10", $config);?></option>
<option value='snd'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_11", $config);?></option>
<option value='fls'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_12", $config);?></option>
<option value='pgn'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_13", $config);?></option>
<option value='txt'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_14", $config);?></option>
<option value='html'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_15", $config);?></option>
</select>

</td>
</tr>

<tr>
<td class='row1'><div id='x1'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_3", $config);?></div></td><td class='row2'><input type='text' name='txtX1' class='post'></td>
</tr>
<tr>
<td class='row1'><div id='x2'><?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_4", $config);?></div></td><td class='row2'><input type='text' name='txtX2' class='post'></td>
</tr>

<tr>
<td class='row1' colspan='2'><input type='submit' name='cmdSubmit' value='<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_16", $config);?>' class='mainoption'></td>
</tr>

</table>

<input type='hidden' name='aid' value='<?php echo $AID;?>'>
</form>

<?php
}else{
?>

<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr>
<td class='row1' align='center'><input type='button' name='btnClose' value='<?php echo GetStringFromStringTable("IDS_CREATE_RESOURCE_TXT_17", $config);?>' onclick='javascript:window.close();' class='mainoption'></td>
</tr>
</table>

<?php
}
?>