<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<script language='Javascript'>
function EmailLog(func, id){
  document.frmEmailLog.txtid.value = id;
  document.frmEmailLog.txtfunc.value = func;
  document.frmEmailLog.submit();
}
</script>

<?php
echo "<form name='frmEmailLog' method='post' action='./cfg_email_log.php'>";
echo "<input type='hidden' name='txtid'>";
echo "<input type='hidden' name='txtfunc'>";
$oR3DCQuery->GetEmailLogHTML();
echo "</form>";
?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_3", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_lookandfeel.php';">
</center>
<br>