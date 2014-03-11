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

	header("Content-Type: text/html; charset=utf-8");
	ini_set("output_buffering","1");
	session_start();  

	$isappinstalled = 0;
	include("../includes/install_check2.php");

	if($isappinstalled == 0){
		header("Location: ../not_installed.php");
	}
	ini_set('error_log', "error_log.log");

	// This is the vairable that sets the root path of the website
	$Root_Path = "../";
	$config = $Root_Path."bin/config.php";
	$Contentpage = "cell_admin_chess_tournament_v2.php";  

	require($Root_Path."bin/CSkins.php");

	//Instantiate the CSkins Class
	$oSkins = new CSkins($config);
	$SkinName = $oSkins->getskinname();
	$oSkins->Close();
	unset($oSkins);

	//////////////////////////////////////////////////////////////
	//Skin - standard includes
	//////////////////////////////////////////////////////////////

	$SSIfile = "../skins/".$SkinName."/standard_cfg.php";
	if(file_exists($SSIfile)){
		include($SSIfile);
	}
	//////////////////////////////////////////////////////////////

	require($Root_Path."bin/CR3DCQuery.php");
	require($Root_Path."bin/CAdmin.php");
	require($Root_Path."bin/config.php");
	require($Root_Path."includes/siteconfig.php");
	require($Root_Path."includes/language.php");
	require($Root_Path."includes/xml.php");

	//////////////////////////////////////////////////////////////
	//Instantiate the CR3DCQuery Class
	$oR3DCQuery = new CR3DCQuery($config);
	$bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
	//////////////////////////////////////////////////////////////

	////////////////////////////////////////////////
	//Login Processing
	////////////////////////////////////////////////
	//Check if admin is logged in already
	if(!isset($_SESSION['LOGIN']))
	{
		$login = "no";
		header('Location: ./index.php');
	}
	else
	{
		if($_SESSION['LOGIN'] != true){
			if(isset($_SESSION['UNAME']))
				unset($_SESSION['UNAME']);
			if(isset($_SESSION['LOGIN']))
				unset($_SESSION['LOGIN']);
			$login = "no";
			header('Location: ./index.php');
		}
		else
		{
			$login = "yes";
		}
	}
	////////////////////////////////////////////////

	if(!$bCronEnabled)
	{
		if($oR3DCQuery->ELOIsActive())
			$oR3DCQuery->ELOCreateRatings();
		$oR3DCQuery->MangeGameTimeOuts();
	}
	// This variable will hold data to display on the page.
	$__pd = array();		

	$_str_all = _T('IDS_Admin_Tournament_Filter_ALL', $config);
	$_str_new = _T('IDS_Admin_Tournament_Filter_NEW', $config);
	$_str_planned = _T('IDS_Admin_Tournament_Filter_planned', $config);
	$_str_accepted = _T('IDS_Admin_Tournament_Filter_accepted', $config);
	$_str_started = _T('IDS_Admin_Tournament_Filter_started', $config);
	$_str_completed = _T('IDS_Admin_Tournament_Filter_completed', $config);
	$_str_finalised = _T('IDS_Admin_Tournament_Filter_finalised', $config);

	// For the cell 
	$_str_filter = _T('IDS_Admin_Tournament_Main_FILTER', $config);
	$_str_id = _T('IDS_Admin_Tournament_Main_ID', $config);
	$_str_name = _T('IDS_Admin_Tournament_Main_NAME', $config);
	$_str_type = _T('IDS_Admin_Tournament_Main_TYPE', $config);
	$_str_count = _T('IDS_Admin_Tournament_Main_COUNT', $config);
	$_str_cutoff = _T('IDS_Admin_Tournament_Main_CUTOFF', $config);
	$_str_start = _T('IDS_Admin_Tournament_Main_START', $config);
	$_str_comment = _T('IDS_Admin_Tournament_Main_COMMENT', $config);
	$_str_status = _T('IDS_Admin_Tournament_Main_STATUS', $config);
	$_str_edit = _T('IDS_Admin_Tournament_Table_EDIT', $config);
	$_str_games = _T('IDS_Admin_Tournament_Table_GAMES', $config);
	$_str_manage_info = _T('IDS_Admin_Tournament_Main_MANAGEINFO', $config);
	$_str_manage_games = _T('IDS_Admin_Tournament_Main_MANAGEGAMES', $config);
	$_str_no_records = _T('IDS_Admin_Tournament_Main_NORECORDS', $config);
	$_str_add = _T('IDS_Admin_Tournament_Main_ADD', $config);
	
		
	// This page lists tournaments that exist in a table. New tournaments can be added
	// and existing tournaments can be updated.
	
	// Check if a status filter was selected by the user.
	if(isset($_GET['status']))
	{
		$s = $_GET['status'];
		if(! in_array($s, array('N', 'P', 'A', 'S', 'C', 'F')))
			$s = $_str_all;
	}
	else
	{
		$s = $_str_all;
	}

	$__pd['tournaments'] = __get_tournaments($oR3DCQuery, $s, $config);
	$__pd['active_filter'] = $s;
	$__pd['available_status_filters'] = array('All' => $_str_all, 'N' => $_str_new, 'P' => $_str_planned, 'A' => $_str_accepted, 'S' => $_str_started, 'C' => $_str_completed, 'F' => $_str_finalised);

	function __get_tournaments($main, $status, $config)
	{
		$_str_all = _T('IDS_Admin_Tournament_Filter_ALL', $config);
		$_str_new = _T('IDS_Admin_Tournament_Filter_NEW', $config);
		$_str_planned = _T('IDS_Admin_Tournament_Filter_planned', $config);
		$_str_accepted = _T('IDS_Admin_Tournament_Filter_accepted', $config);
		$_str_started = _T('IDS_Admin_Tournament_Filter_started', $config);
		$_str_completed = _T('IDS_Admin_Tournament_Filter_completed', $config);
		$_str_finalised = _T('IDS_Admin_Tournament_Filter_finalised', $config);
		
		if($status !== 'All')
			$query = sprintf("SELECT * FROM c4m_tournament WHERE t_status = '%s'", mysql_real_escape_string($status));
		else
			$query = "SELECT * FROM c4m_tournament";
		$return = mysql_query($query, $main->link) or die("Query to fetch tournaments failed");
		$num = mysql_numrows($return);
		$tournaments = array();
		if($num != 0){
			while($row = mysql_fetch_array($return, MYSQL_ASSOC)){
				$t = array();
				$t['id'] = $row["t_id"];
				$t['name'] = $row["t_name"];
				$t['type'] = $row["t_type"];
				$t['count'] = $row["t_playernum"];
				$t['cutoff'] = $row["t_cutoffdate"];
				$t['start'] = $row["t_startdate"];
				$t['comment'] = $row["t_comment"];
				$s = trim($row["t_status"]);
				if($s == "N") $s = $_str_new;
				elseif($s == "P") $s = $_str_planned;
				elseif($s == "A") $s = $_str_accepted;
				elseif($s == "C") $s = $_str_completed;
				elseif($s == "F") $s = $_str_finalised;
				elseif($s == 'S') $s = $_str_started;
				else $s = "UNKNOWN";
				$t['status'] = $s;
				// Make the datetime display as just a date in mm-dd-yyyy format.
				// $date = DateTime::createFromFormat("Y-m-d H:i:s", $t['cutoff']);
                // if($date) $t['cutoff'] = $date->format("Y-m-d");
				// $date = DateTime::createFromFormat("Y-m-d H:i:s", $t['start']);
                // if($date) $t['start'] = $date->format("Y-m-d");
				$ts = strtotime($t['cutoff']);
				$t['cutoff'] = date("Y-m-d", $ts);
				$ts = strtotime($t['start']);
				$t['start'] = date("Y-m-d", $ts);
				
				$tournaments[] = $t;
			}
		}
		return $tournaments;
	}
  
	//var_dump($__pd);
  
?>

<html>
<head>
<title><?php echo GetStringFromStringTable("IDS_PAGETITLES_62", $config);?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<?php include($Root_Path."includes/javascript_admin.php");?>
<script type="text/javascript" src="<?php echo $Root_Path; ?>includes/jquery/jquery-1.7.1.min.js"></script>
</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>