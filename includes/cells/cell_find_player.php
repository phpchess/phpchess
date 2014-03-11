<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>
 <table width="100%" cellspacing="0" cellpadding="10">
  <tr>
    <td>
 <form name='frmfindPlayer' method='post' action='./chess_find_player.php'>

  <table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='400'>

  <tr>

  <td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_TABLE_HEADER", $config);?></font><b></td>

  </tr>

  <tr>

  <td class="row1">Search</td><td class='row2'><Input type='text' name='txtSearch' size='50' class="post"></td>

  </tr>



  <tr>

  <td colspan='2' class='row1'>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_1", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_1", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_2", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_2", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_3", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_4", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_5", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_6", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_7", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_8", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_9", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_10", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_11", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_12", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_13", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_14", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_15", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_16", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_17", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_18", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_19", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_20", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_21", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_22", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_23", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_24", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_25", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_26", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_27", $config);?></a>

  <a href="javascript:ListByLetter('<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28", $config);?>');"><?php echo GetStringFromStringTable("IDS_FIND_PLAYER_JAVA_TXT_28", $config);?></a>

  </td>

  </tr>



  <tr>

  <td colspan='2' class='row2' align='right'>

  <input type='submit' name='cmdSearch' value='<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_BTN_SEARCH", $config);?>' class='mainoption'>

  </td>

  </tr>

  </table>

  </form>



  <form name='frmfindPlayerPoints' method='post' action='./chess_find_player.php'>

  <table border='0' align='center' class="forumline" cellpadding="3" cellspacing="1" width='400'>

  <tr>

  <td colspan='2' class='tableheadercolor'><b><font class="sitemenuheader">Search By Point Relation</font><b></td>

  </tr>

  <tr>

  <td class="row1">Search</td><td class='row2'>Above: <Input type='text' name='txtAbove' size='10' class="post"> Below: <Input type='text' name='txtBelow' size='10' class="post"></td>

  </tr>



  <tr>

  <td colspan='2' class='row2' align='right'>

  <input type='submit' name='cmdSearchPoints' value='<?php echo GetStringFromStringTable("IDS_FIND_PLAYER_BTN_SEARCH", $config);?>' class='mainoption'>

  </td>

  </tr>

  </table>

  </form>



<?php

  if($txtSearch != ""){

    $oR3DCQuery->SearchPlayers($config, $txtSearch);

  }



  if($cmdSearch != "" && $txtSearch == ""){

    $oR3DCQuery->SearchPlayers($config, $txtSearch);

  }



  if($cmdSearchPoints != ""){



    if($txtAbove == ""){

      $txtAbove = 0;

    }

    

    if($txtBelow == ""){

      $txtBelow = 0;

    }



    $oR3DCQuery->FindPlayersByPoints($config, $_SESSION['id'], $txtAbove, $txtBelow);

  }



?></td>
  </tr>
</table>