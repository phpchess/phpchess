<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<script language='javascript'>
function checkAll() {
    for(var i=0;i<document.frmMessage.elements.length;i++)
    {
        if(document.frmMessage.elements[i].type == "checkbox")
        {
            document.frmMessage.elements[i].checked = true;
        }
    }

}
</script>

<form name='frmMessage' method='post' action='./chess_msg_center_saved.php'>
<table border='0' cellpadding='3' cellspacing='1' align='center' class='forumline' width='95%'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_MSGCENTER_TABLE_HEADER", $config);?></font></td></tr>
<tr>
<td class='row1'>

<input type='button' name='btnMenuOpts1' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_LINK_TXT_2", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_msg_center.php';"> 
<input type='button' name='btnMenuOpts2' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_LINK_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_msg_center_saved.php';"> 
<input type='button' name='btnMenuOpts3' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_LINK_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_msg_center.php?type=newmsg';"> 
<br>

</td>
</tr>

<tr>
<td class='row1'>

<input type='submit' name='cmdDeleteAll' Value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_BTN_DS", $config);?>' class='mainoption'> 
<input type='button' name='btnSelectAll' Value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_BTN_SA", $config);?>' class='mainoption' onClick="checkAll();">

</td>
</tr>

<?php
if($type == ""){
?>
<tr>
<td class='row2'>
<?php $oR3DCQuery->GetSavedMessages($config, $_SESSION['id'], $SkinName);?>
</td>
</tr>
<?php
}


//////////////////////////////////////////////////////////////////
if($type == "newmsg"){
?>
<tr>
<td class='row2'>
New Message:
<br>

<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>
<tr>
<td class='row2' align='left'> 

<?php $oR3DCQuery->GetAllPlayers($config);?>

<input type='text' class='post' name='txtmsg' size='80'>

</td>
</tr>
<tr><td class='row2' align='right'><input type='submit' class='mainoption' name='cmdSend' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_BTN_SM", $config);?>'></td></tr>
</table>


</td>
</tr>
<?php
}


//////////////////////////////////////////////////////////////////
if($type == "read" && $inid != ""){
?>
<tr>
<td class='row2'>

<table width='100%' cellpadding='0' cellspacing='0' border='0' align='center'>
<tr>
<td class='row2' align='left'> 
<?php $oR3DCQuery->ReadMessageSaved($config, $inid);?>
</td>
</tr>
<tr><td class='row2' align='right'><input type='submit' class='mainoption' name='cmdDelete' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_BTN_D", $config);?>'><input type='submit' class='mainoption' name='cmdKeep' value='<?php echo GetStringFromStringTable("IDS_MSGCENTER_BTN_K", $config);?>'></td></tr>
</table>

</td>
</tr>
<?php
}
?>

</table>
</form>

<?php
if($bMessageSent){
?>
<table border='0' cellpadding='3' cellspacing='1' align='center' class='forumline' width='95%'>
<tr><td class='row2'><?php echo GetStringFromStringTable("IDS_MSGCENTER_TXT_2", $config);?></td></tr>
</table>
<?php
}
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
</center>
<br>