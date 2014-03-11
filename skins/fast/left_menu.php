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

 $bShowPanels = false;

 if($Contentpage == "cell_game2.php" || $Contentpage == "cell_game3.php"){

   if($isrealtime == "IDS_REAL_TIME"){
     $bShowPanels = false;
   }

 }

?>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
</table>