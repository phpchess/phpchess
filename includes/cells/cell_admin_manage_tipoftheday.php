<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

  if($login == "yes"){

    if($cmdAdd != "" && $txttip != ""){

      // Add the tip to the db
      $oTipOfTheDay->AddTip($config, $txttip);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_TXT_1", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }

    if($rdodelete != "" && $cmdDelete != ""){

      // remove the tip from the db
      $oTipOfTheDay->DeleteTip($config, $rdodelete);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_TXT_2", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }

    if($cmdEdit != "" && $txtetip != "" && $txttipid != ""){

      // Edit the tip
      $oTipOfTheDay->EditTip($config, $txttipid, $txtetip);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_TXT_3", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }


    echo "<form name='frmlogin' method='post' action='manage_tipoftheday.php'>";
    echo "<table align='center' width='430' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor'><b><font class='sitemenuheader'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_HEADER_1", $config)."</font></b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    $oTipOfTheDay->GetTips($config);

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_BTN_E", $config)."' name='cmdEdit' class='mainoption'>";
    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_BTN_D", $config)."' name='cmdDelete' class='mainoption'>";

    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";


    if($cmdEdit != "" && $rdodelete != ""){

      //Get item info
      $rid = 0;
      $rtext = "";
      $rdate = "";

      $oTipOfTheDay->GetTipsByID($config, $rdodelete, $rid, $rtext, $rdate);

      echo "<br>";
      echo "<form name='frmEdit' method='post' action='manage_tipoftheday.php' onsubmit='return ValidateEditForm();'>";
      echo "<table align='center' width='430' class='forumline'>";
      echo "<tr>";
      echo "<td class='tableheadercolor'><b><font class='sitemenuheader'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_HEADER_2", $config)."</font></b></td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td class='row2'>";

      echo "<b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_TXT_4", $config)."</b> <input type='text' name ='txtetip' value='".$rtext."' size='60' class='post'>";
      echo "<input type='hidden' name ='txttipid' value='".$rid."'>";
      echo "</td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td class='row2'>";

      echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_BTN_E", $config)."' name='cmdEdit' class='mainoption'>";

      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</form>";

    }

    echo "<br>";
    echo "<form name='frmadd' method='post' action='manage_tipoftheday.php' onsubmit='return ValidateAddForm();'>";
    echo "<table align='center' width='430' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor'><b><font class='sitemenuheader'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_HEADER_3", $config)."</font></b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    echo "<b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_TABLE_TXT_4", $config)."</b> <input type='text' name ='txttip' size='60' class='post'>";

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_TIPOFTHEDAY_BTN_A", $config)."' name='cmdAdd' class='mainoption'>";

    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";

  }

?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_4", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_news_data.php';">
</center>
<br>