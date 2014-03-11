<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php

  if($login == "yes"){

    if($cmdAdd != "" && $txtmsg != ""){

      // Add the message to the db
      $oServMsg->AddServerMessage($config, $txtmsg);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_TXT_1", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }

    if($rdodelete != "" && $cmdDelete != ""){

      // remove the message from the db
      $oServMsg->DeleteServerMessage($config, $rdodelete);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_TXT_2", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }

    if($cmdEdit != "" && $txtemsg != "" && $txtmsgid != ""){

      // Edit the tip from the db
      $oServMsg->EditServerMessage($config, $txtmsgid, $txtemsg);

      echo "<table align='center' width='430'>";
      echo "<tr>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_TXT_3", $config)."</td>";
      echo "</tr>";
      echo "</table>";

    }

    echo "<form name='frmlogin' method='post' action='manage_servermsg.php'>";
    echo "<table align='center' width='430' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor'><b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_HEADER_1", $config)."</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    $oServMsg->GetMessages($config);

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1'>";

    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_BTN_E", $config)."' name='cmdEdit' class='mainoption'>";
    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_BTN_D", $config)."' name='cmdDelete' class='mainoption'>";

    echo "</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";

    if($cmdEdit != "" && $rdodelete != ""){

      //Get item info
      $rid = 0;
      $rtext = "";
      $rdate = "";

      $oServMsg->GetMessagesByID($config, $rdodelete, $rid, $rtext, $rdate);

      echo "<br>";
      echo "<form name='frmEdit' method='post' action='manage_servermsg.php' onsubmit='return ValidateEditForm();'>";
      echo "<table align='center' width='430' class='forumline'>";
      echo "<tr>";
      echo "<td class='tableheadercolor'><b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_HEADER_2", $config)."</b></td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td class='row2'>";

      echo "<b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_TXT_4", $config)."</b> <input type='text' name ='txtemsg' value='".$rtext."' size='60' class='post'>";
      echo "<input type='hidden' name ='txtmsgid' value='".$rid."'>";
      echo "</td>";
      echo "</tr>";
      echo "<tr>";
      echo "<td class='row2'>";

      echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_BTN_E", $config)."' name='cmdEdit' class='mainoption'>";

      echo "</td>";
      echo "</tr>";
      echo "</table>";
      echo "</form>";

    }

    echo "<br>";
    echo "<form name='frmadd' method='post' action='manage_servermsg.php' onsubmit='return ValidateAddForm();'>";
    echo "<table align='center' width='430' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor'><b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_HEADER_1", $config)."</b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";

    echo "<b>".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_TABLE_TXT_4", $config)."</b> <input type='text' name ='txtmsg' size='60' class='post'>";

    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row2'>";
    echo "<input type = 'submit' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_SERVERMSG_BTN_A", $config)."' name='cmdAdd' class='mainoption'>";
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