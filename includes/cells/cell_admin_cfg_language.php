<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<form name='frmRedemption' method='post' action='./cfg_language.php'>
<table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='100%'>
<tr>
<td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_ADMIN_LANGUAGE_TABLE_HEADER", $config);?></font><b></td>
</tr>

<tr>
<td class='row1' width='120'><?php echo GetStringFromStringTable("IDS_ADMIN_LANGUAGE_TABLE_TXT_1", $config);?></td>
<td class='row2'>
<select name='optlangname'>
<?php
/**********************************************************************
* getdir
*
*/
function getdir($directory, $config) {

   if($dir = opendir($directory)) {

       while($file = readdir($dir)) {
           
           if($file != "." && $file != ".." && $file[0] != '.' && $file != "help") { 

             if(GetServerLanguageFile($config) == $file){
               echo "<option value='".$file."' SELECTED>".$file."</option>";
             }else{
               echo "<option value='".$file."'>".$file."</option>";
             }

           }
       }

       closedir($dir);
   }
}

getdir('../includes/languages', $config);
?>
</select>
</td>
</tr>

<tr>
<td class='row2' colspan='2'><input type='submit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_LANGUAGE_BTN_INSTALL", $config);?>' name='cmdChange' class='mainoption'></td>
</tr>

</table>
</form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>