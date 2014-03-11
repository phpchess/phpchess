<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
<table width="100%" cellspacing="0" cellpadding="4" class="forumline">
  <tr>
    <td>

<form name='frmcolor' method='get' action='./cfg_chessboard_colors.php'>

<?php

  $oR3DCQuery->GetDefaultAdminChessboardColors($clrl, $clrd);

  $oR3DCQuery->ChessBoardColorsAdmin($clrl, $clrd);

?>

</form>



<br><br>

<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>

<br></td>
  </tr>
</table>