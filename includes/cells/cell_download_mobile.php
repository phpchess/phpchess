<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<center><h1><?php echo GetStringFromStringTable("IDS_MOBILE_DOWNLOAD_TXT_1", $config);?></h1></center>

<?php echo GetStringFromStringTable("IDS_MOBILE_DOWNLOAD_TXT_2", $config);?>
<br><br>

<a href='./clients/MobileChess.jad'>MobileChess.jad</a>&nbsp;&nbsp;
<a href='./clients/MobileChess.jar'>MobileChess.jar</a>

