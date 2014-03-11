<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmRedemption' method='post' action='./cfg_skins.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_SKINS_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' width='100'><?php echo GetStringFromStringTable("IDS_ADMIN_SKINS_TABLE_TXT_1", $config);?></td>
<td class='row2'>
<select name='optskinname'>
<?php
/**********************************************************************
* getdir
*
*/
function getdir($directory, $SkinName) {

   if($dir = opendir($directory)) {


       while($file = readdir($dir)) {
           
           if($file != "." && $file != ".." && $file[0] != '.' && $file != "0mobileimages") {
               
               if(is_dir($directory . "/" . $file)) {

                   if($SkinName == $file){
                     echo "<option value='".$file."' selected>".$file."</option>";
                   }else{
                     echo "<option value='".$file."'>".$file."</option>";
                   }

               }
           }
       }


       closedir($dir);
   }
}

getdir('../skins', $SkinName);
?>
</select>
</td>
</tr>

<tr>
<td class='row2' colspan='2'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_SKINS_BTN_1", $config);?>' name='cmdChange' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>