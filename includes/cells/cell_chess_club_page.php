<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php echo $oR3DCQuery->GetClubPageHTMLPlayer($clubid);?>