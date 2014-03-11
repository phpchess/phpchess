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
	$Contentpage = "cell_admin_tournament_edit.php";  

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

	// Ensure the requested tournament id is valid and a record for it exists.
	if(!isset($_GET['id']))
	{
		$__pd['error'] = _T('IDS_Admin_Tournament_Edit_IDMISSING', $config);
	}
	else
	{
		$id = (int)$_GET['id'];
		$query = sprintf("SELECT * FROM c4m_tournament WHERE t_id = '%s'", mysql_real_escape_string($id));

		$result = mysql_query($query, $oR3DCQuery->link);
		if($result === false)
		{
			exit(_T('IDS_Admin_Tournament_Edit_QUERYFAILED', $config));
		}
		$cnt = mysql_num_rows($result);
		if($cnt == 0)
		{
			$__pd['error'] = _T('IDS_Admin_Tournament_Edit_IDINVALID', $config);
		}
		else
		{
			$t = mysql_fetch_assoc($result);

			// Field settings. Used for validation purposes and setting-up/customising the form.
			$fields = array();
			$fields['t_name'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Edit_NAME', $config), 'max_length' => '32', 'required' => TRUE, 'width' => 40);
			$fields['t_type'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Edit_TYPE', $config), 'max_length' => '15', 'required' => TRUE, 'items' => get_tournament_types($config), 'render_type' => 'select');
			$fields['t_playernum'] = array('type' => 'int', 'label' => _T('IDS_Admin_Tournament_Edit_COUNT', $config), 'required' => FALSE, 'width' => 10);
			$fields['t_cutoffdate'] = array('type' => 'date', 'label' => _T('IDS_Admin_Tournament_Edit_CUTOFF', $config), 'required' => TRUE, 'render_type' => 'date', 'width' => 20, 'id' => '_date_cutoff');
			$fields['t_startdate'] = array('type' => 'date', 'label' => _T('IDS_Admin_Tournament_Edit_START', $config), 'required' => TRUE, 'render_type' => 'date', 'width' => 20, 'id' => '_date_start');
			$fields['t_comment'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Edit_COMMENT', $config), 'required' => FALSE, 'render_type' => 'description', 'width' => 30);
			$fields['t_status'] = array('type' => 'string', 'label' => _T('IDS_Admin_Tournament_Edit_STATUS', $config), 'max_length' => '1', 'required' => TRUE, 'items' => get_status_filters($config), 'render_type' => 'select');
			$__pd['fields'] = $fields;
			
			$result = check_form_submit($id, $fields, $oR3DCQuery, $config);

			if($result['submit'] == FALSE) 	// No submission.
			{
				$__pd['tournament'] = $t;
			
			}
			elseif($result['submit'] && count($result['errors']) != 0)	// Was submitted, but has errors.
			{
				$__pd['tournament'] = $result['new_values'];
				$__pd['errors'] = $result['errors'];
			}
			else		// Was submitted with no errors.
			{
				$__pd['tournament'] = $result['new_values'];
				$__pd['success'] = _T('IDS_Admin_Tournament_Edit_UPDATED', $config);
			}
			
		}
	}
	
	// Checks if a form submit occurred. This would be an update request.
	// Must validate all values before saving. If there are errors, they are
	// returned, along with all the new values entered.
	// id - the id of the record. IN this case this is field t_id.
	// fields - array of field options.
	// oR3DCQuery - the heart of phpchess. Needed to access the db link.
	function check_form_submit($id, $fields, $oR3DCQuery, $config)
	{
		if(!isset($_POST['update']))
		{
			return array('submit' => FALSE);
		}
		
		$new_values = array();
		$errors = array();
		
		foreach($fields as $field => $opts)
		{
			$val = "";
			if(isset($_POST[$field]))
				$val = trim($_POST[$field]);
			
			if($opts['type'] == 'date')
			{
				$val = $val . " 00:00:00";	// Don't care about time component.
				//$date = DateTime::createFromFormat("M-d-Y H:i:s", $val);
                //if($date) $val = $date->format("Y-m-d H:i:s");
			}
			
			$new_values[$field] = $val;
			
			if($opts['required'] && $val == "")
			{
				$errors[$field] = _T('IDS_Form_Message_REQUIRED', $config);
				continue;
			}
			
			// Got a value. Must validate it.
			$res = validate_field($val, $opts, $config);
			if($res['error'])
			{
				$errors[$field] = $res['msg'];
				continue;
			}
		}
		
		//var_dump($errors);
		//var_dump($new_values);
		
		// If there were no errors, can update the object. 
		if(count($errors) == 0)
		{
			$query = sprintf("UPDATE `c4m_tournament` SET `t_name`='%s', `t_type`='%s', `t_playernum`='%s', `t_cutoffdate`='%s', `t_startdate`='%s', `t_comment`='%s', `t_status`='%s' WHERE `t_id`='%s'",
				mysql_real_escape_string($new_values['t_name']),
				mysql_real_escape_string($new_values['t_type']),
				mysql_real_escape_string($new_values['t_playernum']),
				mysql_real_escape_string($new_values['t_cutoffdate']),
				mysql_real_escape_string($new_values['t_startdate']),
				mysql_real_escape_string($new_values['t_comment']),
				mysql_real_escape_string($new_values['t_status']),
				mysql_real_escape_string($id));
			//echo $query;
			$result = mysql_query($query, $oR3DCQuery->link);
			if($result === FALSE)
			{
				exit(_T('IDS_Admin_Tournament_Edit_UPDATEFAILED', $config));
			}
		}
		
		return array('submit' => TRUE, 'errors' => $errors, 'new_values' => $new_values);
		
	}
	
	function validate_field($val, $opts)
	{
		$error = FALSE;
		if($opts['type'] == 'int')
		{
			if(preg_match('/^[!0-9]+$/', $val) != 0)
			{
				//$error = "Value is not an integer";
			}
		}
		elseif($opts['type'] == 'string')
		{
			if(isset($opts['max_length']))
			{
				if(strlen($val) > $opts['max_length'])
				{
					$error = _T('IDS_Form_Message_LENGTH', $config);
					$error = preg_replace("/\{max_length\}/", $opts['max_length'], $error);
				}
			}
		}
		
		if($error !== FALSE)
		{
			return array('error' => TRUE, 'msg' => $error);
		}
		return array('error' => FALSE);
	}
	
	function get_status_filters($config)
	{
		$_str_all = _T('IDS_Admin_Tournament_Filter_ALL', $config);
		$_str_new = _T('IDS_Admin_Tournament_Filter_NEW', $config);
		$_str_planned = _T('IDS_Admin_Tournament_Filter_planned', $config);
		$_str_accepted = _T('IDS_Admin_Tournament_Filter_accepted', $config);
		$_str_started = _T('IDS_Admin_Tournament_Filter_started', $config);
		$_str_completed = _T('IDS_Admin_Tournament_Filter_completed', $config);
		$_str_finalised = _T('IDS_Admin_Tournament_Filter_finalised', $config);
		return array('N' => $_str_new, 'P' => $_str_planned, 'A' => $_str_accepted, 'S' => $_str_started, 'C' => $_str_completed, 'F' => $_str_finalised);
	}
	
	function get_tournament_types($config)
	{
		$_str_duel = _T('IDS_Admin_Tournament_Type_DUEL', $config);
		$_str_league = _T('IDS_Admin_Tournament_Type_LEAGUE', $config);
		$_str_swiss = _T('IDS_Admin_Tournament_Type_SWISS', $config);
		$_str_knockout = _T('IDS_Admin_Tournament_Type_KNOCKOUT', $config);
		$_str_round = _T('IDS_Admin_Tournament_Type_ROUNDROBIN', $config);
		$_str_best = _T('IDS_Admin_Tournament_Type_BESTOF', $config);
		return array('duel' => $_str_duel, 'league' => $_str_league, 'swiss' => $_str_swiss, 'knock-out' => $_str_knockout, 'round-robin' => $_str_round, 'best-of' => $_str_best);
	}
  
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
<link href="<?php echo $Root_Path;?>includes/jquery/cupertino/jquery-ui-1.8.16.custom.css" type="text/css" rel="stylesheet">
<?php include($Root_Path."includes/javascript_admin.php");?>
<script type="text/javascript" src="<?php echo $Root_Path; ?>includes/jquery/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="<?php echo $Root_Path; ?>includes/jquery/jquery-ui-1.8.16.custom.min.js"></script>


</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>