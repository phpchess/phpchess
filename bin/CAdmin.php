<?php
  ////////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  // Main inclusion for admin functionality....location of all admin function capabilities.
  ////////////////////////////////////////////////////////////////////////////////


if(!defined('CHECK_PHPCHESS')){
  die("Hacking attempt");
  exit;
}

// Class Includes
@include_once('CChess.php');

class CAdmin{

  //////////////////////////////////////////////////////////////////////////////
  //Define properties
  //////////////////////////////////////////////////////////////////////////////
  var $host;
  var $db;
  var $user;
  var $pass;
  var $linkCAdmin;
  var $ChessCFGFileLocation;

  //////////////////////////////////////////////////////////////////////////////
  //Define methods
  //////////////////////////////////////////////////////////////////////////////

  /**********************************************************************
   * CAdmin (Constructor)
   *
   */
  function __construct($ConfigFile){

    ////////////////////////////////////////////////////////////////////////////
    // Sets the chess config file location (absolute location on the server)
    ////////////////////////////////////////////////////////////////////////////
    $this->ChessCFGFileLocation  = $ConfigFile;
    ////////////////////////////////////////////////////////////////////////////

    include($ConfigFile);

    $this->host = $conf['database_host'];
    $this->dbnm = $conf['database_name'];
    $this->user = $conf['database_login'];
    $this->pass = $conf['database_pass'];

    $this->linkCAdmin = mysqli_connect($this->host, $this->user, $this->pass);
    mysqli_select_db($this->linkCAdmin,$this->dbnm);

    if(!$this->linkCAdmin){
      die("CAdmin.php: ".mysqli_error($this->linkCAdmin));
    }

  }

  
  function mysqli_result($result, $number, $field=0) {
    mysqli_data_seek($result, $number);
    $row = mysqli_fetch_array($result);
    return $row[$field];
  }

  /**********************************************************************
   * GetStringFromStringTable
   *
   */
  function GetStringFromStringTable($strTag){

    include($this->ChessCFGFileLocation);

    // Get Server Language
    $LanguageFile = "";

    if(isset($_SESSION['language'])){
 
      if($_SESSION['language'] != ""){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$_SESSION['language'];
      }

    }else{

      $query = "SELECT * FROM server_language WHERE o_id=1";
      $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
      $num = mysqli_num_rows($return);

      if($num != 0){
        $LanguageFile = $conf['absolute_directory_location']."includes/languages/".$this->mysqli_result($return, 0, "o_languagefile");
      }

    }

    $text = "Error";

    if($LanguageFile != ""){

      // Open the language file an get the contents
      $lines = file($LanguageFile);
 
      // Search for the key
      for($x=1; $x<=sizeof($lines); $x++){
        //echo "Line $x: " . $lines[$x-1] . "<br>";

        if (preg_match("/\b".$strTag."\b/i", $lines[$x-1])){
          // We found the key
    
          list($Key, $strText, $junk) = preg_split("/\|\|/", $lines[$x-1], 3);

	  $strText = utf8_encode($strText);

          $text = trim($strText);

          // Exit loop
          break;

        }

      }

    }

    //Parse tags

    $aTags = array("['avatar_image_width']", "['avatar_image_height']", "['user_name']");
    $aReplace = array($conf['avatar_image_width'], $conf['avatar_image_height'], $_SESSION['user']);
    $text = str_replace($aTags, $aReplace, $text);

    return $text;
  
  }


  /**********************************************************************
   * GetNewPlayers
   *
   */
  function GetNewPlayers($ConfigFile){

    $query = "SELECT * FROM pendingplayer ORDER BY signup_time";
    $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
    $num = mysqli_num_rows($return);

    echo "<table border='0' align='center' class='forumline' cellpadding='3' cellspacing='1' width='95%'>";
    echo "<tr>";
    echo "<td colspan='4' class='tableheadercolor'><b><font class='sitemenuheader'>".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_HEADER_1")."</font><b></td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td class='row1'>-</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_1_TXT_1")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_1_TXT_2")."</td><td class='row1'>".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_1_TXT_3")."</td>";
    echo "</tr>";

    if($num != 0){

      $i = 0;

      while($i < $num){
        $player_id = trim($this->mysqli_result($return,$i,"player_id"));
        $userid = trim($this->mysqli_result($return,$i,"userid"));
        $email = trim($this->mysqli_result($return,$i,"email"));
        $signuptime  = trim($this->mysqli_result($return,$i,"signup_time"));

        echo "<tr>";
        echo "<td class='row2'><input type='radio' name='rdoPendingPlayer' value='".$player_id."'></td><td class='row2'>".$userid."</td><td class='row2'>".$email."</td><td class='row2'>".date("m-d-Y",$signuptime)."</td>";
        echo "</tr>";

        $i++;
      }

    }

    echo "<tr>";
    echo "<td class='row1' colspan='4' align='right'><input type='button' name='cmdAccept' value='".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_1_BTN_A")."' class='mainoption' onclick=\"javascript:ExecuteCommand('A');\"><input type='button' name='cmdRevoke' value='".$this->GetStringFromStringTable("IDS_CADMIN_TABLE_1_BTN_R")."' class='mainoption' onclick=\"javascript:ExecuteCommand('R');\"></td>";
    echo "</tr>";

    echo "<input type='hidden' name='txtcommand'>";

    echo "</table>";
    echo "<br>";

  }


  /**********************************************************************
   * AcceptNewPlayer
   *
   */
  function AcceptNewPlayer($ConfigFile, $NewPlayerid){

    $query = "SELECT * FROM pendingplayer WHERE player_id =".$NewPlayerid;
    $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
    $num = mysqli_num_rows($return);

    if($num != 0){

      $userid = trim($this->mysqli_result($return,0,"userid"));
      $email = trim($this->mysqli_result($return,0,"email"));

      $this->RegisterNewPlayer($userid, $email);

      $delete = "DELETE FROM pendingplayer WHERE player_id =".$NewPlayerid;
      mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

    }

  }

  
  /**********************************************************************
   * RegisterNewPlayer
   *
   */
  function RegisterNewPlayer($Name, $Email){

    $returncode = "";

    //Instantiate the CChess Class
    $oChess = new CChess($this->ChessCFGFileLocation);
    $returncode = $oChess->register($this->ChessCFGFileLocation, $Name, $Email);
    unset($oChess);

    return $returncode;

  }


  /**********************************************************************
   * RevokeNewPlayer
   *
   */
  function RevokeNewPlayer($ConfigFile, $NewPlayerid){

    $delete = "DELETE FROM pendingplayer WHERE player_id =".$NewPlayerid;
    mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

  }


  /**********************************************************************
   * AdminLogin
   *
   */
  function AdminLogin($uname, $pass){

    $query = "SELECT * FROM c4m_admin WHERE a_username='".$uname."' AND a_password='".$this->hash_password($pass)."'";
    $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
    $num = mysqli_num_rows($return);
 
    $login = false;

    if($num != 0){
      $login = true;
    }

    return $login;

  }
  
  // Hashes the password string using the salt from the config file.
  public function hash_password($pass)
  {
	include('../bin/config.php');
	$salt = $conf['password_salt'];
	$hash = md5($salt . $pass);
	//echo "salt: $salt, pass: $pass, hash: $hash";
	return $hash;
  }


  /**********************************************************************
   * DisablePlayer
   *
   */
  function DisablePlayer($PID){

    $query = "SELECT * FROM player WHERE player_id = ".$PID;
    $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
    $num = mysqli_num_rows($return);
 
    if($num != 0){
      
      $userid = $this->mysqli_result($return, 0, "userid");

      $query1 = "SELECT * FROM player2 WHERE player_id = ".$PID;
      $return1 = mysqli_query($this->linkCAdmin,$query1) or die(mysqli_error($this->linkCAdmin));
      $num1 = mysqli_num_rows($return1);

      if($num1 == 0){

        $insert = "INSERT INTO player2 VALUES(".$PID.", '".$userid."')";
        mysqli_query($this->linkCAdmin,$insert) or die(mysqli_error($this->linkCAdmin));

        // Leave club
        $delete = "DELETE FROM chess_club_members WHERE o_playerid = '".$PID."'";
        mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

        // Remove all messages
        $delete = "DELETE FROM c4m_msginbox WHERE player_id =".$PID;
        mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

        $delete = "DELETE FROM c4m_msgsaved  WHERE player_id =".$PID;
        mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

        // Clear buddy list
        $delete = "DELETE FROM c4m_buddylist WHERE player_id =".$PID;
        mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));
   
        // Set all active games to draw
        $update = "UPDATE game SET status='C', completion_status='B' WHERE w_player_id =".$PID." AND completion_status = 'I'";
        mysqli_query($this->linkCAdmin,$update) or die(mysqli_error($this->linkCAdmin));

        $update = "UPDATE game SET status='C', completion_status='W' WHERE b_player_id =".$PID." AND completion_status = 'I'";
        mysqli_query($this->linkCAdmin,$update) or die(mysqli_error($this->linkCAdmin));

      }

    }

  }


  /**********************************************************************
   * EnablePlayer
   *
   */
  function EnablePlayer($PID){

    $query = "SELECT * FROM player2 WHERE player_id = ".$PID;
    $return = mysqli_query($this->linkCAdmin,$query) or die(mysqli_error($this->linkCAdmin));
    $num = mysqli_num_rows($return);
 
    if($num != 0){
      
      $delete = "DELETE FROM player2 WHERE player_id = ".$PID;
      mysqli_query($this->linkCAdmin,$delete) or die(mysqli_error($this->linkCAdmin));

    }

  }


  /**********************************************************************
   * Close (Deconstructor)
   *
   */
  function Close(){
    mysqli_close($this->linkCAdmin);
  }

  function get_count($query) {
    global $db_link;
    $x = mysqli_fetch_array(mysqli_query($db_link,$query));
    return $x[0];
  }

  function count_active_players() {
    return $this->get_count("SELECT count(player_id) FROM player;");
  }

  function count_archived_players() {
    return $this->get_count("SELECT count(*) FROM player_archive;");
  }

  function count_challenges() {
    return $this->get_count("SELECT count(*) FROM c4m_notification WHERE p_challange='y';");
  }

  function count_active_games() {
    return $this->get_count("SELECT count(*) FROM game WHERE completion_status='I';");
  }

  function count_completed_games() {
    return $this->get_count("SELECT count(*) FROM game WHERE status='C' OR completion_status='W' OR completion_status='B' OR completion_status='D';");
  }

  function count_archived_games() {
    return $this->get_count("SELECT count(*) FROM game_fin;"); 
  }

  function count_active_moves() {
    return $this->get_count("SELECT count(*) FROM move_history");
  }

  function count_active_messages() {
    return $this->get_count("SELECT count(*) FROM (SELECT * FROM `c4m_msginbox` UNION SELECT * FROM message_queue) AS allm;");
  }

  function count_history() {
    return $this->get_count("SELECT count(*) FROM whos_online_graph;");
  }


  function one_to_many_blob($query) {
    global $db_link;
    $items = mysqli_query($db_link,$query);
    $items_array = array();
    while ($item = mysqli_fetch_assoc($items)) {
      $items_array[] = $item; }
    $itemsblob = serialize($items_array);
    return $itemsblob; }

  function db_array_insert($rowdef, $table, $tabledef) {
    global $db_link;
    $cols = array();
    foreach ($tabledef as $name => $type) {
      $cols[] = (in_array($type, array("varchar", "text", "string", "enum", "char")) ? '"'.addslashes($rowdef[$name]).'"'
		 : ($rowdef[$name] == null ? ($type == "int" ? "0" : "NULL") : $rowdef[$name])); }
    $query = "INSERT INTO `".$table."` VALUES(".implode($cols, ", ").");";
    return $this->query_or_fail($query); }

  function db_delete_rows($tablename, $id, $idcolname) {
    global $db_link;
    $query = "DELETE FROM `".$tablename."` WHERE `".$idcolname."`=".$id.";";
    return $this->query_or_fail($query); }

  function query_or_fail($query) {
    global $db_link;
    $r = mysqli_query($db_link,$query);
    if (!$r) { echo "query failed on \"".$query."\""; exit(); }
    return $r; }

  function archive_games($olderthan) {
    global $db_link;
    $date = time() - $olderthan * (24*60*60);
    $query = "SELECT * FROM game WHERE start_time < ".$date.";";
    $results = mysqli_query($db_link,$query);
    $rows = array();

    while ($row = mysqli_fetch_array($results)) {
      $row['moves'] = $this->one_to_many_blob("SELECT * FROM move_history WHERE game_id = '".$row['game_id']."';");
      $row['messages'] = $this->one_to_many_blob("SELECT * FROM c4m_gamechat WHERE tgc_gameid = '".$row['game_id']."';");
      $this->db_array_insert($row, "game_fin", array('game_id' => "string", 'initiator' => "int", 'w_player_id' => "int", 'b_player_id' => "int", 'status' => "string", 'completion_status' => "string", 'start_time' => "int", 'cast_ws' => "string", 'cast_wl' => "string", 'cast_bs' => "string", 'cast_bl' => "string", 'moves' => "string", 'messages' => "string"));
      $rows[] = $row; }
    return $this->delete_games_in_array($rows); }

  function mysql_fetch_rows($result) {
    $ar = array();
    while ($row = mysqli_fetch_array($result)) {
      $ar[] = $row; }
    return $ar; }
  
  function delete_games($olderthan, $status) {
    global $db_link;
    $date = time() - $olderthan * (24*60*60);
    $query = "SELECT * FROM game WHERE start_time < ".$date.($status == "*" ? "" : " AND status='".$status."'").";";	
    $results = mysqli_query($db_link,$query);

    return $this->delete_games_in_array($this->mysql_fetch_rows($results)); }

  function delete_games_missing_player_info() {
    global $db_link;
    $query = "SELECT * FROM game WHERE w_player_id = 0 OR b_player_id = 0;";
    $results = mysqli_query($db_link,$query);

    return $this->delete_games_in_array($this->mysql_fetch_rows($results)); }

  function delete_games_in_array($rows) {
    foreach($rows as $game) {
      $preped_gameid = '"'.$game['game_id'].'"';
      $this->db_delete_rows("game", $preped_gameid, "game_id");
      $this->db_delete_rows("move_history", $preped_gameid, "game_id");
      $this->db_delete_rows("c4m_gamechat", $preped_gameid, "tgc_gameid");
      $this->db_delete_rows("c4m_gamedraws", $preped_gameid, "tm_gameid");
      $this->db_delete_rows("c4m_gamerealtime", $preped_gameid, "gr_gameid");
      $this->db_delete_rows("cfm_gamesrealtime", $preped_gameid, "id");
      $this->db_delete_rows("cfm_game_options", $preped_gameid, "o_gameid");
      $this->db_delete_rows("timed_games", $preped_gameid, "id");
      $this->db_delete_rows("timed_game_stats", $preped_gameid, "id"); }
    return true; }

  function delete_players_in_array($rows) {
    foreach($rows as $player) {
      $playerid = $player['player_id'];
      if ($playerid != 0) {
	$this->db_delete_rows("player", $playerid, "player_id");
	$this->db_delete_rows("c4m_avatars", $playerid, "a_playerid");
	$this->db_delete_rows("c4m_buddylist", $playerid, "player_id");
	$this->db_delete_rows("c4m_chessboardcolors", $playerid, "player_id");
	$this->db_delete_rows("c4m_invalid_players", $playerid, "player_id");
	$this->db_delete_rows("c4m_msginbox", $playerid, "player_id");
	$this->db_delete_rows("c4m_msgsaved", $playerid, "player_id");
	$this->db_delete_rows("c4m_notification", $playerid, "p_playerid");
	$this->db_delete_rows("c4m_personalinfo", $playerid, "p_playerid");
	$this->db_delete_rows("cfm_point_caching", $playerid, "player_id");
	$this->db_delete_rows("chess_boardstyle", $playerid, "id");
	$this->db_delete_rows("chess_board_type", $playerid, "o_playerid");
	$this->db_delete_rows("chess_club_members", $playerid, "o_playerid");
	$this->db_delete_rows("elo_points", $playerid, "player_id");
	$this->db_delete_rows("message_queue", $playerid, "player_id");
	$this->db_delete_rows("mobile_client_ip", $playerid, "o_playerid");
	$this->db_delete_rows("pc_chat_messages", $playerid, "o_playerid");
	$this->db_delete_rows("pc_chat_players", $playerid, "o_playerid");
	$this->db_delete_rows("player_credits", $playerid, "o_playerid");
	$this->db_delete_rows("player_last_login", $playerid, "o_playerid");
	$this->db_delete_rows("player_purchased_activities", $playerid, "o_playerid"); }}
    return true; }

  function archive_players_in_array($rows) {
    global $db_link;
    foreach($rows as $player) {
      if ($player['player_id'] != 0) {
	$row = "SELECT player.player_id AS player_id, player.userid AS userid, player.password AS password, player.signup_time AS signup_time, player.status AS status, player.email AS email, c4m_avatars.a_imgname, c4m_chessboardcolors.cc_dcolor, c4m_chessboardcolors.cc_lcolor, c4m_notification.p_move, c4m_notification.p_challange, c4m_personalinfo.p_fullname, c4m_personalinfo.p_location, c4m_personalinfo.p_age, c4m_personalinfo.p_selfrating, c4m_personalinfo.p_commentmotto, c4m_personalinfo.p_favouritechessplayer, cfm_point_caching.points, chess_boardstyle.style, chess_board_type.o_isdragdrop, elo_points.cpoints, elo_points.opoints, player_credits.o_credits "
	  ."FROM player "
	  ."LEFT JOIN c4m_avatars ON c4m_avatars.a_playerid = player.player_id "
	  ."LEFT JOIN c4m_chessboardcolors ON c4m_chessboardcolors.player_id = player.player_id "
	  ."LEFT JOIN c4m_notification ON c4m_notification.p_playerid = player.player_id "
	  ."LEFT JOIN c4m_personalinfo ON c4m_personalinfo.p_playerid = player.player_id "
	  ."LEFT JOIN cfm_point_caching ON cfm_point_caching.player_id = player.player_id "
	  ."LEFT JOIN chess_boardstyle ON chess_boardstyle.id = player.player_id "
	  ."LEFT JOIN chess_board_type ON chess_board_type.o_playerid = player.player_id "
	  ."LEFT JOIN elo_points ON elo_points.player_id = player.player_id "
	  ."LEFT JOIN player_credits ON player_credits.o_playerid = player.player_id "
	  ."WHERE player.player_id = ".$player['player_id'].";";
	$row = mysqli_fetch_assoc(mysqli_query($db_link,$row));
	$this->db_array_insert($row, "player_archive", array("player_id" => "int", "userid" => "varchar", "password" => "varchar", "signup_time" => "int", "status" => "enum", "email" => "varchar", "a_imgname" => "varchar", "cc_dcolor" => "varchar", "cc_lcolor" => "varchar", "p_move" => "char", "p_challange" => "char", "p_fullname" => "varchar", "p_location" => "varchar", "p_age" => "char", "p_selfrating" => "varchar", "p_commentmotto" => "varchar", "p_favouritechessplayer" => "varchar", "points" => "int", "style" => "int", "o_isdragdrop" => "int", "cpoints" => "int", "opoints" => "int", "o_credits" => "int")); }}
    $this->delete_players_in_array($rows);
    return true; }

  function archive_players_with_0_games($days) {
    global $db_link;
    $time = time() - $days*24*60*60;
    $query = "SELECT * FROM player LEFT JOIN game ON (game.w_player_id = player.player_id OR game.b_player_id = player.player_id) WHERE game.game_id IS NULL AND player.signup_time < ".$time.";";
    $players = $this->mysql_fetch_rows(mysqli_query($db_link,$query)); 
    return  $this->archive_players_in_array($players); }

  function delete_players_with_0_games($days) {
    global $db_link;
    $time = time() - $days*24*60*60;
    $query = "SELECT * FROM player LEFT JOIN game ON (game.w_player_id = player.player_id OR game.b_player_id = player.player_id) WHERE game.game_id IS NULL AND player.signup_time < ".$time.";";
    $players = $this->mysql_fetch_rows(mysqli_query($db_link,$query)); 
    return $this->delete_players_in_array($players); }

  function unarchive_player($playername) {
    global $db_link;
    $archive = mysqli_fetch_array(mysqli_query($db_link,"SELECT * FROM player_archive WHERE userid='".addslashes($playername)."' LIMIT 1;"));
    if (!$archive) {
      echo "Sorry, that player does not exist in the archives.";
      exit(); }
    $this->db_array_insert($archive, "player", array("player_id" => "int", "userid" => "varchar", "password" => "varchar", "signup_time" => "int", "status" => "enum", "email" => "varchar"));
    $this->db_array_insert(array("a_player_id" => $archive['player_id'], "a_imgname" => $archive['a_imgname'], "a_datechanges" => "CURRENT_TIME"), "c4m_avatars", array("a_playerid" => "int", "a_imgname" => "varchar", "a_datechanges" => "datetime"));
    $this->db_array_insert(array("cc_id" => "NULL", "player_id" => $archive['player_id'], "cc_dcolor" => $archive['cc_dcolor'], "cc_lcolor" => $archive['cc_lcolor']), "c4m_chessboardcolors", array("cc_id" => "int", "player_id" => "int", "cc_dcolor" => "varchar", "cc_lcolor" => "varchar"));
    $this->db_array_insert(array("p_player_id" => $archive['player_id'], "p_move" => $archive['p_move'], "p_challenge" => $archive['p_challenge']), "c4m_notification", array("p_player_id" => "int", "p_move" => "char", "p_challange" => "char"));
    $this->db_array_insert(array("p_player_id" => $archive['player_id'], "p_fullname" => $archive['p_fullname'], "p_location" => $archive['p_location'], "p_age" => $archive['p_age'], "p_selfrating" => $archive['p_selfrating'], "p_commentmotto" => $archive['p_commentmotto'], "p_favouritechessplayer" => $archive['p_favouritechessplayer']), "c4m_personalinfo", array("p_player_id" => "int", "p_fullname" => "varchar", "p_location" => "varchar", "p_age" => "char", "p_selfrating" => "varchar", "p_commentmotto" => "varchar", "p_favouritechessplayer" => "varchar"));
    $this->db_array_insert(array("player_id" => $archive['player_id'], "points" => $archive['points']), "cfm_point_caching", array("player_id" => $archive['player_id'], "points" => "int"));
    $this->db_array_insert(array("id" => $archive['player_id'], "style" => $archive['style']), "chess_boardstyle", array("id" => "int", "style" => "int"));
    $this->db_array_insert(array("o_playerid" => $archive['player_id'], "o_isdragdrop" => $archive['o_isdragdrop']), "chess_board_type", array("o_playerid" => 'int', "o_isdragdrop" => "int"));
    $this->db_array_insert(array("player_id" => $archive['player_id'], "cpoints" => $archive['cpoints'], "opoints" => $archive['opoints']), "elo_points", array("player_id" => "int", "cpoints" => "int", "opoints" => "int"));
    $this->db_array_insert(array("o_playerid" => $archive['player_id'], "o_credits" => $archive['o_credits']), "player_credits", array("o_playerid" => "int", "o_credits" => "int"));
    $this->db_delete_rows("player_archive", $archive['player_id'], "player_id");
    return true; }

  function delete_messages_older($days, $including_saved) {
    global $db_link;
    $time = time() - $days*24*60*60;
    return mysqli_query($db_link,"DELETE FROM message_queue WHERE posted < ".$time."; ") &&
      mysqli_query($db_link,"DELETE FROM c4m_msginbox WHERE msg_posted < ".$time."; ") &&
      ($including_saved ? mysqli_query($db_link,"DELETE FROM c4m_msgsaved WHERE msg_posted < ".$time.";") : true); }

  function clean_up_history($days) {
    global $db_link;
    $query = "DELETE FROM whos_online_graph WHERE o_date < DATE_SUB(NOW(), INTERVAL ".$days." DAY);";
    return mysqli_query($db_link,$query); }


} //end of class definition
?>
