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
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_10", $config);?></font></td>
</tr>
<tr>
<td>

  <form name='frmChessLogin' method='post' action='./<?php echo $Page_Name;?>' > <!--onsubmit="return validateForm();"-->
  <table border='0' align='center'  cellpadding="3" cellspacing="1" width='99%'>
  <tr>
  <td class="row1"><?php echo GetStringFromStringTable("IDS_MENU_TXT_17", $config);?></td><td class="row2"><Input type='text' name='txtName' size='15' class="post"></td>
  </tr>
  <tr>
  <td class="row1"><?php echo GetStringFromStringTable("IDS_MENU_TXT_18", $config);?></td><td class="row2"><Input type='password' name='txtPassword' size='15' class="post"></td>
  </tr>

  <tr>
  <td class="row1"><?php echo GetStringFromStringTable("IDS_MENU_TXT_25", $config);?></td><td class="row2"><?php GetLanguageList("slctlanguage", $config);?></td>
  </tr>

  <tr>
  <td class="row2" colspan='2'>

  <input type='checkbox' name='chkAutoLogin' value='1'> 

  <font size='1'><?php echo GetStringFromStringTable("IDS_MENU_TXT_19", $config);?></font></td>
  </tr>

  <tr>
  <td colspan='2'>
  
  <input type='submit' name='cmdLogin' value='<?php echo GetStringFromStringTable("IDS_MENU_TXT_20", $config);?>' class='mainoption'>
  <input type='Reset' name='cmdReset' value='<?php echo GetStringFromStringTable("IDS_MENU_TXT_21", $config);?>' class='button'>

  </td>
  </tr>
  </table>

  </form>

<center><a href='./chess_retrieve_pass.php' class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_TXT_22", $config);?></a></center>
<br>
<center><a href="javascript:PopupUserNameChange('./x_username.php')" class='menulinks'><?php echo GetStringFromStringTable("IDS_MENU_TXT_27", $config);?></a></center>
<br>
</td>
</tr>

<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_11", $config);?></font></td>
</tr>
<tr>
<td>

<?php include($Root_Path."skins/".$SkinName."/otherlinks.php");?>

</td>
</tr>
<!--
<tr>
<td class='tableheadercolor'><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_MENU_TXT_12", $config);?></font></td>
</tr>

<tr>
<td>

<?php
if($_SESSION['id'] != ""){
  $oR3DCQuery->GetCurrentGamesByPlayerID($config, $_SESSION['id']);
}
?>

</td>
</tr>
-->
</table>