<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
  $oR3DCQuery->GetActivityInfoByIDHTML($AID);
?>

<form name='frmEditActivity' method='get' action='./edit_activity.php'>
<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>
<tr><td colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_1", $config);?></font><b></td></tr>
<tr>

<?php if(defined('CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_WIDTH') && defined('CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_HEIGHT')){?>
<td class='row1'><input type='button' name='btnaddResource' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_2", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./create_resource.php?aid=<?php echo $AID;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_WIDTH;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_ADDRESOURCE_HEIGHT;?>')"></td>
<?php }else{?>
<td class='row1'><input type='button' name='btnaddResource' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_2", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./create_resource.php?aid=<?php echo $AID;?>', '400', '200')"></td>
<?php }?>


<?php if(defined('CFG_POPUPWINDOWACTIVITY_CREATEPAGE_WIDTH') && defined('CFG_POPUPWINDOWACTIVITY_CREATEPAGE_HEIGHT')){?>
<td class='row1'><input type='button' name='btnCreatePage' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_3", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./create_page.php?aid=<?php echo $AID;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_CREATEPAGE_WIDTH;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_CREATEPAGE_HEIGHT;?>')"></td>
<?php }else{?>
<td class='row1'><input type='button' name='btnCreatePage' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_3", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./create_page.php?aid=<?php echo $AID;?>', '600', '505')"></td>
<?php }?>


<?php if(defined('CFG_POPUPWINDOWACTIVITY_PREVIEW_WIDTH') && defined('CFG_POPUPWINDOWACTIVITY_PREVIEW_HEIGHT')){?>
<td class='row1'><input type='button' name='btnPreview' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_4", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./preview_activity.php?aid=<?php echo $AID;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_PREVIEW_WIDTH;?>', '<?php echo CFG_POPUPWINDOWACTIVITY_PREVIEW_HEIGHT;?>')"></td>
<?php }else{?>
<td class='row1'><input type='button' name='btnPreview' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_4", $config);?>' class='mainoption' onclick="javascript:PopupWindowActivity('./preview_activity.php?aid=<?php echo $AID;?>', '600', '505')"></td>
<?php }?>


<?php
if(!$isenabled){
?>
<td class='row1'><input type='submit' name='cmdEnable' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_5", $config);?>' class='mainoption'></td>
<?php
}else{
?>
<td class='row1'><input type='submit' name='cmdDisable' value='<?php echo GetStringFromStringTable("IDS_EDIT_ACTIVITY_TXT_6", $config);?>' class='mainoption'></td>
<?php
}
?>
</tr>
</table>

<input type='hidden' name='aid' value='<?php echo $AID;?>'>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_6", $config);?>' class='mainoption' onclick="javascript:window.location = './create_activity.php';">
</center>
<br>