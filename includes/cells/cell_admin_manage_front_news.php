<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
  if($login == "yes"){
?>
  <form name='frmManagement' method='post' action='manage_front_news.php'>
  <table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
  <tr>
  <td class='row1'>
  <input type='submit' name='cmdNew' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_BTN_NEW", $config);?>' class='mainoption'>
  <input type='submit' name='cmdEdit' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_BTN_EDIT", $config);?>' class='mainoption'>
  <input type='submit' name='cmdDelete' value='<?php echo GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_BTN_DELETE", $config);?>' class='mainoption'>
  </td>
  </tr>
  </table>

<?php
    if($cmdNew == "" && $cmdEdit == ""){
      $oFrontNews->GetFrontNewsAdmin();
    }

    if($cmdNew != ""){

      echo "<br>";
      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";

      echo "<tr>";
      echo "<td class='row1'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_TXT_1", $config)."</td><td class='row2'><input type='text' class='post' name='txttitle' value='' size='60'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row1'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_TXT_2", $config)."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row2'><textarea name='txtnews' rows='10' cols='83' class='post'></textarea></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row2' valign='top' align='right'><input type='submit' name='cmdAdd' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_BTN_ANI", $config)."' class='mainoption'></td>";
      echo "</tr>";

      echo "</table>";
  
    }

    if($cmdEdit != "" && $rdodelete != ""){

      $Title = "";
      $News = "";
      $oFrontNews->GetFrontNewsForEdit($rdodelete, $Title, $News);

      echo "<br>";

      echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
      echo "<tr>";
      echo "<td class='row1'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_TXT_1", $config)."</td><td class='row2'><input type='text' class='post' name='txttitle' value='".$Title."' size='60'></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row1'>".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_TXT_2", $config)."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row2'><textarea name='txtnews' rows='10' cols='83' class='post'>".$News."</textarea></td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td colspan='2' class='row2' valign='top' align='right'>";

      echo "<input type='hidden' name='txtID' value='".$rdodelete."'>";
      echo "<input type='submit' name='cmdEditItem' value='".GetStringFromStringTable("IDS_ADMIN_MANAGE_FRONTNEWS_BTN_ENI", $config)."' class='mainoption'>";

      echo "</td>";
      echo "</tr>";
      echo "</table>";

    }


  }

?>

  </form>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './admin_main.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_4", $config);?>' class='mainoption' onclick="javascript:window.location = './manage_news_data.php';">
</center>
<br>