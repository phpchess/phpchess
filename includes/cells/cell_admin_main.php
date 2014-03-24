<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }



  $strVersion = $oR3DCQuery->GetServerVersion();

?>

<table width="100%" cellspacing="0" cellpadding="4" class="white">
  <tr>
    <td>

<?php
	if(file_exists("../install"))
	{
		echo "<div style='border: 2px solid red; color: red; margin: 5px; padding: 10px; font-size: 1.4em; text-align: center'>The install folder exists. Please remove it.</div>";
	}
?>	

<h2 class="title_h2"><?php echo GetStringFromStringTable("IDS_ADMIN_MAIN_TXT_1", $config);?></h2><br>

<?php echo GetStringFromStringTable("IDS_ADMIN_MAIN_TXT_2", $config);?> <?php echo $strVersion; ?><br><br>
</td></tr>


<tr><Td>

<?php echo GetStringFromStringTable("IDS_ADMIN_MAIN_TXT_3", $config);?><br /><br />
<img src='../skins/<?php echo $SkinName;?>/images/reportbugs.gif'> <a href='./bugreport.php'><?php echo GetStringFromStringTable("IDS_CHESS_BUG_REPORT_TXT_2", $config);?></a>
<br>
<img src='../skins/<?php echo $SkinName;?>/images/arrow.gif'> <a href='http://www.phpchess.com/'> phpChess Homepage</a>
<br>
<img src='../skins/<?php echo $SkinName;?>/images/arrow.gif'> <a href='http://www.phpchess.com/?page_id=66'> phpChess Latest News</a>
<br>
<img src='../skins/<?php echo $SkinName;?>/images/arrow.gif'> <a href='http://www.phpchess.com/forum'> phpChess Forum</a>
<br>
<img src='../skins/<?php echo $SkinName;?>/images/arrow.gif'> <a href='http://www.phpchess.com/?page_id=13'> phpChess Downloads</a>
<br>
<img src='../skins/<?php echo $SkinName;?>/images/arrow.gif'> <a href='http://www.phpchess.com/wiki'> phpChess WIKI</a>
<br>
<br>


<?php

  $oR3DCQuery->AdminMainLinkList1();

?>




&nbsp;</td>
  </tr>
</table><br><br>
<textarea rows='20' cols='80'>

<?php



  // Get the contents of the crv.xml file

  $data = implode("", file("../bin/crv.xml"));



  // Load the xml file

  $xml = xmlize($data);

  $version = $xml["CRV"]["#"]["VERSION"];



  for($i = 0; $i < sizeof($version); $i++){



    $crv = $version[$i];



    $vers = $crv["@"]["vers"];

    $CHANGELIST = $crv["#"]["CHANGELIST"][0]["#"];



    echo "----------------------------------------------------------\n";

    echo "".$vers."\n";

    echo "----------------------------------------------------------\n";

    echo str_replace("\\r\\n", "\n", $CHANGELIST)."\n\n";



  }



?>

</textarea>

