<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited, 2004-2006, in association with Goliath Systems. 
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // you can find us at http://www.phpchess.com. 
  //
  ////////////////////////////////////////////////////////////////////////////

  define('CHECK_PHPCHESS', true);

  $host = $_SERVER['HTTP_HOST'];
  $self = $_SERVER['PHP_SELF'];
  $query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
  $url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

  header("Content-Type: text/html; charset=utf-8");
  session_start();
  ob_start();

  $isappinstalled = 0;
  include("./includes/install_check.php");

  if($isappinstalled == 0){
    header("Location: ./not_installed.php");
  }

  // This is the vairable that sets the root path of the website
  $Root_Path = "./";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_view_player_list2.php";  

  require($Root_Path."bin/CSkins.php");
  
  //Instantiate the CSkins Class
  $oSkins = new CSkins($config);
  $SkinName = $oSkins->getskinname();
  $oSkins->Close();
  unset($oSkins);

  //////////////////////////////////////////////////////////////
  //Skin - standard includes
  //////////////////////////////////////////////////////////////

  $SSIfile = "./skins/".$SkinName."/standard_cfg.php";
  if(file_exists($SSIfile)){
    include($SSIfile);
  }
  //////////////////////////////////////////////////////////////

  require($Root_Path."bin/CR3DCQuery.php");
  require($Root_Path."bin/CTipOfTheDay.php");
  require($Root_Path."bin/CBuddyList.php");
  require($Root_Path."includes/siteconfig.php");
  require($Root_Path."includes/language.php");

  $gid = trim($_GET['gameid']);

  $action = $_GET['action'];
  $index = $_GET['index'];


  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oBuddyList = new CBuddyList($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////


  ///////////////////////////////////////////////////////////////////
  // Check For the nonempty SID var
  $sid = trim($_GET['sid']);

  // Log the user on or manage the session if a SID is passed to the page
  if($sid != ""){

    $user = "";
    $id = "";
    $oR3DCQuery->ConfirmSID($sid, $user, $id);
 
    if($user != "" && $id != ""){
      $_SESSION['sid'] = $sid;
      $_SESSION['user'] = $user;
      $_SESSION['id'] = $id;

      $oR3DCQuery->GetChessBoardColors($config, $_SESSION['id'], $l, $d);
      
      $_SESSION['lcolor'] = $l;
      $_SESSION['dcolor'] = $d;

      if($oR3DCQuery->IsPlayerDisabled($id) == false){

        $clrl = $_SESSION['lcolor'];
        $clrd = $_SESSION['dcolor'];

        $oR3DCQuery->AddOnlinePlayerToGraphData($_SESSION['user']);
        $oR3DCQuery->UpdateLastLoginInfo($_SESSION['id']);
        $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);

      }else{
        header('Location: ./chess_logout.php');
      }

    }

  }
  ///////////////////////////////////////////////////////////////////



  ///////////////////////////////////////////////////////////////////
  //Check if the logged in user has access
  if(!isset($_SESSION['sid']) && !isset($_SESSION['user']) && !isset($_SESSION['id']) ){
    $_SESSION['PageRef'] = $url;
    header('Location: ./chess_login.php');
  }else{
    $oR3DCQuery->CheckSIDTimeout();

    if($oR3DCQuery->CheckLogin($config, $_SESSION['sid']) == false){
      $_SESSION['PageRef'] = $url;
      header('Location: ./chess_login.php');
    }else{
      $_SESSION['PageRef'] = "";
      $oR3DCQuery->UpdateSIDTimeout($ConfigFile, $_SESSION['sid']);
      $oR3DCQuery->SetPlayerCreditsInit($_SESSION['id']);
    }

    if(!$bCronEnabled){

      if($oR3DCQuery->ELOIsActive()){
        $oR3DCQuery->ELOCreateRatings();
      }

      $oR3DCQuery->MangeGameTimeOuts();
    }
  }
  ///////////////////////////////////////////////////////////////////////
  
  if($_SERVER['REQUEST_METHOD'] != 'POST'):
  
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_15", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript.php");?>
<script src="<?php echo $Root_Path; ?>includes/jquery/jquery-1.7.1.min.js"></script>

</head>
<body>

<?php include("./skins/".$SkinName."/layout_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oBuddyList->Close();
  unset($oR3DCQuery);
  unset($oBuddyList);
  //////////////////////////////////////////////////////////////

  ob_end_flush();
?>

<?php else: 	// Handle post requests here.
	
	echo "moo";
	
	

	
	endif; 

	function __get_player_list($cr3d)
	{
		// Get player ratings. Either ELO or self rating.
		if(!$cr3d->ELOIsActive())
		{
			// When not using ELO rating system, needs to calculate a self rating. This requires
			// looking at how many games where won, lost and drawn.
			// $query = "SELECT w_player_id AS w, b_player_id AS b, completion_status FROM game WHERE completion_status='W' OR completion_status='W' OR completion_status='B'";
			// $result = mysql_query($query, $cr3d->link) or die(mysql_error());
			// $game_finishes = array();
			// while($row = mysql_fetch_array($result))
			// {
				// if($row['completion_status'] == 'D')
				// {
					// if(!isset($game_finishes[$row['w']]))
						// $game_finishes[$row['w']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// if(!isset($game_finishes[$row['b']]))
						// $game_finishes[$row['b']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// $game_finishes[$row['w']]['draws']++;
					// $game_finishes[$row['b']]['draws']++;
				// }
				// elseif($row['completion_status'] == 'W')
				// {
					// if(!isset($game_finishes[$row['w']]))
						// $game_finishes[$row['w']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// if(!isset($game_finishes[$row['b']]))
						// $game_finishes[$row['b']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// $game_finishes[$row['w']]['wins']++;
					// $game_finishes[$row['b']]['losses']++;
				// }
				// else
				// {
					// if(!isset($game_finishes[$row['w']]))
						// $game_finishes[$row['w']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// if(!isset($game_finishes[$row['b']]))
						// $game_finishes[$row['b']] = array('wins' => 0, 'draws' => 0, 'losses' => 0);
					// $game_finishes[$row['w']]['losses']++;
					// $game_finishes[$row['b']]['wins']++;
				// }
			// }
			// echo '<pre>' . print_r($game_finishes, true) . '</pre>';
			
			// Can just use the cfm_point_caching table to look up self rating points.
			$query = "SELECT player_id, points FROM cfm_point_caching";
			$result = mysql_query($query, $cr3d->link) or die (mysql_error());
			$self_points = array();
			while($row = mysql_fetch_array($result))
			{
				$self_points[$row['player_id']] = (int)$row['points'];
			}
		}
		else
		{
			// elo_points stores the elo points for every player.
			$query = "SELECT player_id, cpoints FROM elo_points";
			$result = mysql_query($query, $cr3d->link) or die (mysql_error());
			$elo_points = array();
			while($row = mysql_fetch_array($result))
			{
				$elo_points[$row['player_id']] = (int)$row['cpoints'];
			}
		}
		
		// Get all buddy mappings
		$query = "SELECT player_id, buddy_id FROM c4m_buddylist";
		$result = mysql_query($query, $cr3d->link) or die (mysql_error());
		$buddies = array();
		while($row = mysql_fetch_array($result))
		{
			if(!isset($buddies[$row['player_id']]))
				$buddies[$row['player_id']] = array();
			$buddies[$row['player_id']][] = (int)$row['buddy_id'];
		}
		//echo '<pre>' . print_r($buddies, true) . '</pre>';
		
		// Get all player info and add game finishes and buddy info to each player.
		$query = "SELECT player.player_id, player.userid, player.signup_time, active_sessions.player_id AS online FROM player LEFT JOIN active_sessions ON player.player_id = active_sessions.player_id ORDER BY player.userid ASC";
		$result = mysql_query($query, $cr3d->link) or die (mysql_error());
		$players = array();
		while($row = mysql_fetch_array($result))
		{
			$points = 0;
			if($cr3d->ELOIsActive()){
				//$points = $cr3d->ELOGetRating($row['player_id']);
				$points = $elo_points[$row['player_id']];
			}else{
				//$finishes = $game_finishes[$row['player_id']];
				//$points = $cr3d->GetPointValue($finishes['wins'], $finishes['losses'], $finishes['draws']);
				$points = $self_points[$row['player_id']];
			}
			$buddylist = isset($buddies[$row['player_id']]) ? $buddies[$row['player_id']] : array();
			$players[$row['player_id']] = array(
				'userid' => $row['userid'],
				'signup_time' => (int)$row['signup_time'],
				'buddies' => $buddylist,
				'points' => $points,
				'online' => $row['online'] != null ? true : false
			);
		}
		// echo '<pre>'.print_r($players, true).'</pre>';
		return $players;
	}
?>