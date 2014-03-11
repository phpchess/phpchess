<?php

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

?>

<?php
list($PlayerType, $status) = explode(" ", $IsAccepted, 2);

if($status == "waiting" || $status == "-"){

    echo "<form name='frmAccept' method='get' action='./chess_game.php'>";
    echo "<table border='0' cellpadding='0' cellspacing='0' align='center' width='100%'>";
    echo "<tr>";
    echo "<td colspan='2' class='tableheadercolor'><b><font class='sitemenuheader'>";
    echo GetStringFromStringTable("IDS_GAME_TXT_14", $config);
    echo "</font><b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td colspan='2' class='row2'>";
    $fen = $oR3DCQuery->GetFEN($_SESSION['sid'], $gid);
    echo "&nbsp;<b>".GetStringFromStringTable("IDS_CR3DCQUERY_TXT_149", $config)."</b> ".substr($fen, 10, strlen($fen));
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1' valign='top'>";
    $oR3DCQuery->GetGamePlayTypeInfo($gid);
    $oR3DCQuery->TimedGameStats($gid);
    echo "</td>";
    echo "<td class='row2'>";
    $oR3DCQuery->CreateChessBoard($fen, $clrl, $clrd, false, "w");
    echo "</td>";
    echo "</tr>";

  if($PlayerType == "i"){
?>
    <tr>
    <td class='row2' colspan='2'>
    <?php echo GetStringFromStringTable("IDS_GAME_TXT_1", $config);?>
    </td>
    </tr>

   <tr>
    <td class='row1' colspan='2'>

    <input type='hidden' name='gameid' value='<?php echo $gid;?>'>
    <Input type='submit' name='cmdRevokeChlng' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
    </td>
    </tr>

<?php
  }

  if($PlayerType == "o"){
?>
    <tr>
    <td class='row2' colspan='2'>
    <?php echo GetStringFromStringTable("IDS_GAME_TXT_2", $config);?>
    </td>
    </tr>
    <tr>
    <td class='row1' colspan='2'>

    <input type='hidden' name='gameid' value='<?php echo $gid;?>'>
    <Input type='submit' name='cmdAccept' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_ACCEPT_CHALLANGE", $config);?>' class='mainoption'>
    <Input type='submit' name='cmdRevoke' value='<?php echo GetStringFromStringTable("IDS_GAME_BTN_REVOKE_CHALLANGE", $config);?>' class='mainoption'>
    </td>
    </tr>

<?php
  }

    echo "</table>";
    echo "</form>";
}

if($brevoked){
?>
    <table border='0' cellpadding='0' cellspacing='0' align='center' width='95%'>
    <tr>
    <td class='row2'><?php echo GetStringFromStringTable("IDS_GAME_TXT_13", $config);?></td>
    </tr>
    </table>
<?php
}
?>




