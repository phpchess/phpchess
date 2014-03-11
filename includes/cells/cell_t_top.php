<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

    //Instantiate theCR3DCQuery Class
    $oR3DCQuery = new CR3DCQuery($config);
    $oR3DCQuery->GetCurrentGameInfoByGameID($config, $GID, "");
    $oR3DCQuery->Close();
    unset($oR3DCQuery);
 
?>

