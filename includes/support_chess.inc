<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  if(!defined('CHECK_PHPCHESS')){
    die("Hacking attempt");
    exit;
  }

  /**********************************************************************
  * get_completion_status
  *
  */
  function get_completion_status() {
	global $config, $gid;
	$game_id = $gid;
    //include config file
    include($config);

    // connect to mysql and open database
    $db_my = mysql_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysql_select_db($conf['database_name']) or die("Unable to select database");

    $st = "SELECT completion_status FROM game WHERE game_id='".$game_id."';";
    $return = mysql_query($st) or die(mysql_error());

    $result = mysql_fetch_array($return);
	
	return $result['completion_status'];
  }
  

  /**********************************************************************
  * get_turn
  *
  */
  function get_turn() {
	global $config, $gid;
	$game_id = $gid;
    //include config file
    include($config);

    // connect to mysql and open database
    $db_my = mysql_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysql_select_db($conf['database_name']) or die("Unable to select database");

    $st = "SELECT next_move FROM game WHERE game_id='".$game_id."';";
    $return = mysql_query($st) or die(mysql_error());

    $result = mysql_fetch_array($return);
	
	return $result['next_move'];
  }
    

  /**********************************************************************
  * get_last_move
  *
  */
  function get_last_move() {
	global $config, $gid;
	$game_id = $gid;
    //include config file
    include($config);

    // connect to mysql and open database
    $db_my = mysql_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysql_select_db($conf['database_name']) or die("Unable to select database");

    $st = "SELECT move FROM move_history WHERE game_id='".$game_id."' ORDER BY move_id DESC LIMIT 1;";
    $return = mysql_query($st) or die(mysql_error());

    $result = mysql_fetch_array($return);
	
	return $result['move'];
  }
  

  //Castling
  /**********************************************************************
  * request_castling
  *
  */
   function request_castling() {
   	global $config, $gid;
	$game_id = $gid;
    //include config file
    include($config);

    // connect to mysql and open database
    $db_my = mysql_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysql_select_db($conf['database_name']) or die("Unable to select database");

    $st = "SELECT cast_ws, cast_wl, cast_bs, cast_bl FROM game WHERE game_id='".$game_id."';";
    $return = mysql_query($st) or die(mysql_error());

    return mysql_fetch_array($return);
  }
  

  /**********************************************************************
  * set_castling
  *
  */
  function set_castling($cast_ws, $cast_wl, $cast_bs, $cast_bl) {
   	global $config, $gid;
	$game_id = $gid;
    //include config file
    include($config);
	
	if ($cast_ws) $cast_ws = 1;
	else $cast_ws = 0;
	if ($cast_wl) $cast_wl = 1;
	else $cast_wl = 0;
	if ($cast_bs) $cast_bs = 1;
	else $cast_bs = 0;
	if ($cast_bl) $cast_bl = 1;
	else $cast_bl = 0;

    // connect to mysql and open database
    $db_my = mysql_connect($conf['database_host'],$conf['database_login'],$conf['database_pass']) or die("Couldn't connect to the database.");
    @mysql_select_db($conf['database_name']) or die("Unable to select database");

    $st = "UPDATE game SET cast_ws = '{$cast_ws}', cast_wl = '{$cast_wl}', cast_bs = '{$cast_bs}', cast_bl = '{$cast_bl}' WHERE game_id='".$game_id."';";
    $return = mysql_query($st) or die(mysql_error());

    return true;
  }

?>