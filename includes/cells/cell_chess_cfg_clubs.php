<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
if($bclubcreateerror){

  echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
  echo "<tr>";
  echo "<td class='row1'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_3", $config)."</td>";
  echo "</tr>";
  echo "</table>";

}

if(!$oR3DCQuery->IsUserInClub($_SESSION['id'])){
?>

<form name='frmCreateClub' method='post' action='./chess_cfg_clubs.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor' colspan='2'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_1", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_2", $config);?></td><td class='row2'><input type='text' name='txtclubname' class='post' size='50'> <input type='submit' name='cmdCreateClub' value='<?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_BTN_1", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<form name='frmJoinClub' method='post' action='./chess_cfg_clubs.php'>
<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>
<tr><td class='tableheadercolor'><font class='sitemenuheader'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_4", $config);?></font></td></tr>
<tr>
<td class='row1'><?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_2", $config);?></td>
</tr>

<tr>
<td class='row2'><?php $oR3DCQuery->GetChessClubSelectBox();?></td>
</tr>

<tr>
<td class='row1'><input type='submit' name='cmdJoinClub' value='<?php echo GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_BTN_2", $config);?>' class='mainoption'></td>
</tr>
</table>
</form>

<?php
}else{

  if($oR3DCQuery->IsUserApplicationPending($_SESSION['id'])){

    echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    echo "<tr>";
    echo "<td class='row1'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_5", $config)."</td>";
    echo "</tr>";
    echo "</table>";

    echo "<form name='frmClubOpts' method='post' action='./chess_cfg_clubs.php'>";
    echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor' colspan='2'><font class='sitemenuheader'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_6", $config)."</font></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1' width='110'><input type='submit' name='cmdLeaveClub' value='".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_btn_3", $config)."' class='mainoption'></td>";
    echo "<td class='row2'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_7", $config)."</td>";
    echo "</tr>";
    echo "</table>";
    echo "</form>";

  }else{

    echo "<form name='frmClubOpts' method='post' action='./chess_cfg_clubs.php'>";
    echo "<table width='100%' cellpadding='3' cellspacing='1' border='0' align='center' class='forumline'>";
    echo "<tr>";
    echo "<td class='tableheadercolor' colspan='2'><font class='sitemenuheader'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_6", $config)."</font></td>";
    echo "</tr>";

    if($oR3DCQuery->IsUserClubLeader($_SESSION['id'])){

      echo "<tr>";
      echo "<td class='row1'><input type='submit' name='cmdDisband' value='".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_btn_4", $config)."' class='mainoption'></td>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_8", $config)."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='row1'><input type='button' name='cmdManApps' value='".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_btn_5", $config)."' class='mainoption' OnClick=\"location.href='./chess_cfg_club_members.php';\"></td>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_9", $config)."</td>";
      echo "</tr>";

      echo "<tr>";
      echo "<td class='row1'><input type='button' name='cmdManFPage' value='".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_btn_6", $config)."' class='mainoption' OnClick=\"location.href='./chess_cfg_club_mem.php';\"></td>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_10", $config)."</td>";
      echo "</tr>";

    }else{

      echo "<tr>";
      echo "<td class='row1'><input type='submit' name='cmdLeaveClub' value='".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_btn_3", $config)."' class='mainoption'></td>";
      echo "<td class='row2'>".GetStringFromStringTable("IDS_CHESS_CFG_CLUBS_TXT_7", $config)."</td>";
      echo "</tr>";

    }

    echo "</table>";
    echo "</form>";

  }
}

?>

<br><br>
<center>
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_1", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_members.php';">
<input type='button' name='btnBack' value='<?php echo GetStringFromStringTable("IDS_NAV_BUTTONS_TXT_9", $config);?>' class='mainoption' onclick="javascript:window.location = './chess_cfg.php';">
</center>
<br>