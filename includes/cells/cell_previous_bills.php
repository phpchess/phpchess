<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_PREVIOUSBILLS_TXT_1", $config);?></font>
</td>
</table>
<?php
  $oBilling->GetOrdersByUserName($_SESSION['user'], 'p');
?>

<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr>
<td class='tableheadercolor' colspan='2'>
<font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_PREVIOUSBILLS_TXT_2", $config);?></font>
</td>
</table>

<?php
  $oBilling->GetOrdersByUserName($_SESSION['user'], 'f');
?>

<br>