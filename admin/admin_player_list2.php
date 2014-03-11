<?php

  ////////////////////////////////////////////////////////////////////////////
  //
  // (c) phpChess Limited.
  // All rights reserved. Please observe respective copyrights.
  // phpChess - Chess at its best
  // You can find us at http://www.phpchess.com. 
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

  // This is the vairable that sets the root path of the website
  $Root_Path = "../";
  $config = $Root_Path."bin/config.php";
  $Contentpage = "cell_admin_player_list2.php";  

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
  require($Root_Path."bin/DataRenderers.php");
  require($Root_Path."bin/LanguageParser.php");

  //////////////////////////////////////////////////////////////
  //Instantiate the Classes
  $oR3DCQuery = new CR3DCQuery($config);
  $oAdmin = new CAdmin($config);
  $bCronEnabled = $oR3DCQuery->IsCronManagementEnabled();
  //////////////////////////////////////////////////////////////

  LanguageFile::load_language_file2($conf);
  
  ////////////////////////////////////////////////
  //Login Processing
  ////////////////////////////////////////////////
  //Check if admin is logged in already
  if(!isset($_SESSION['LOGIN'])){
     $login = "no";
     header('Location: ./index.php');
    
  }else{

    if($_SESSION['LOGIN'] != true){

      if (isset($_SESSION['UNAME'])){
        unset($_SESSION['UNAME']);
      }

      if (isset($_SESSION['LOGIN'])) { 
        unset($_SESSION['LOGIN']);
      }

      $login = "no";
      header('Location: ./index.php');

    }else{
      $login = "yes";
    }

  }
  ////////////////////////////////////////////////

  // $action = trim($_GET['action']);
  // $index = trim($_GET['index']);

  // if($action == ""){
    // $action = trim($_POST['action']);
  // }

  // if($index == ""){
    // $index = trim($_POST['index']);
  // }

  // $cmdDisable = $_POST['cmdDisable'];
  // $cmdEnable = $_POST['cmdEnable'];

  // $rdodlt = $_POST['rdodlt'];

  // if($cmdDisable != "" && $rdodlt != ""){
    // $oAdmin->DisablePlayer($rdodlt);
  // }

  // $bLimit = false;

  // if($cmdEnable != "" && $rdodlt != ""){

    // $bLimit = $oR3DCQuery->IsUserLimitReached();

    // if($bLimit == false){
      // $oAdmin->EnablePlayer($rdodlt);
    // }else{
      // $bLimit = true;
    // }

  // }

  if(!$bCronEnabled){

    if($oR3DCQuery->ELOIsActive()){
      $oR3DCQuery->ELOCreateRatings();
    }
    $oR3DCQuery->MangeGameTimeOuts();
  }
  
	DB::init($config);
	// $mark = new Model("phpchess::test");
	// var_dump($mark->toArray());
	//$mark->id = "blah";
	// $mark->name = "bob2";
	// $mark->dob = "1982-04-25";
	// $mark->gender = "male";
	// $mark->likes_icecream = false;
	// var_dump($mark->toArray());
	// $mark->validate();
	// $mark->save();
	
	// $p = ModelManager::find("phpchess::test", 1);
	// $p->delete();
	
	// $persons = ModelManager::find_where("phpchess::test", 'SELECT * FROM phpchess.test WHERE id>?', array(4));
	// foreach($persons as $p)
	// {
		// var_dump($p->toArray());
	// }
	
	// $players = ModelManager::get_all("phpchess::test");
	// foreach($players as $player)
		// var_dump($player->toArray());
	
	// $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    // $rp = isset($_POST['rp']) ? (int)$_POST['rp'] : 10;
    // $sortname = isset($_POST['sortname']) && $_POST['sortname'] != 'undefined' ? $_POST['sortname'] : FALSE;
    // $sortorder = isset($_POST['sortorder']) ? ($_POST['sortorder'] == 'asc' ? 'ASC' : 'DESC') : 'DESC';
    // $search_string = isset($_POST['query']) ? ($_POST['query'] != '' ? $_POST['query'] : false) : false;
    // $search_field = isset($_POST['qtype']) ? ($_POST['qtype'] != '' ? $_POST['qtype'] : false) : false;
	
	// if($_POST['page']=='blah')	// should be something else to indicate a request by flexigrid
	// {
		// if(isset($_GET['Add'])){ // this is for adding records

			// // $rows = $_SESSION['Example4'];
			// // $rows[$_GET['EmpID']] = 
			// // array(
				// // 'name'=>$_GET['Name']
				// // , 'favorite_color'=>$_GET['FavoriteColor']
				// // , 'favorite_pet'=>$_GET['FavoritePet']
				// // , 'primary_language'=>$_GET['PrimaryLanguage']
			// // );
			// // $_SESSION['Example4'] = $rows;

		// }
		// elseif(isset($_GET['Edit'])){ // this is for Editing records
			// // $rows = $_SESSION['Example4'];
			
			// // unset($rows[trim($_GET['OrgEmpID'])]);  // just delete the original entry and add.
			
			// // $rows[$_GET['EmpID']] = 
			// // array(
				// // 'name'=>$_GET['Name']
				// // , 'favorite_color'=>$_GET['FavoriteColor']
				// // , 'favorite_pet'=>$_GET['FavoritePet']
				// // , 'primary_language'=>$_GET['PrimaryLanguage']
			// // );
			// // $_SESSION['Example4'] = $rows;
		// }
		// elseif(isset($_GET['Delete'])){ // this is for removing records
			// // $rows = $_SESSION['Example4'];
			// // unset($rows[trim($_GET['Delete'])]);  // to remove the \n
			// // $_SESSION['Example4'] = $rows;
		// }
		// else{
			// header("Content-type: application/json");
			// $jsonData = array('page'=>$page,'total'=>0,'rows'=>array());

			// // $all = ModelManager::get_all('phpchess::test');
			// $query = "SELECT * FROM `phpchess`.`test` ";
			// $params = array();
			// if($search_string !== FALSE && $search_field !== FALSE)
			// {
				// $query .= "WHERE $search_field LIKE ? ";
				// //$params[] = $search_field;
				// $params[] = "%$search_string%";
			// }
			// $offset = ($page - 1) * $rp;
			// if($sortname)
				// $query .= "ORDER BY $sortname $sortorder ";
			// $query .= "LIMIT $offset, $rp";
			// //var_dump($params);
			// //echo $query;
			// $all = ModelManager::find_where('phpchess::test', $query, $params);
			// foreach($all as $item)
			// {
				// $entry = array('id' => $item->id,
					// 'cell' => array(
						// 'id' => $item->id,
						// 'name' => $item->name,
						// 'gender' => $item->gender,
						// 'dob' => $item->dob,
						// 'likes_icecream' => $item->likes_icecream
					// )
				// );
				// $jsonData['rows'][] = $entry;
			// }
			
			// $jsonData['total'] = count($all);
			// echo json_encode($jsonData);
			// exit();

		// }
	// }
	
	 
	$db = DB::query_getone('select database() as `db`');
	$db = $db['db'];
	$tbl_opts = array(
		'url' => 'admin_player_list2.php',
		'model' => "$db::player",
		'title' => __l('All Players'),
		'order' => array('player_id', 'userid', 'email', 'signup_time'),
		'columns' => array(
			'player_id' => array('label' => __l('ID')),
			'userid' => array('label' => __l('Username')),
			'email' => array('label' => __l('Email'), 'width' => 300, 'render_as' => 'email'),
			//'password' => array('label' => __l('Password'), 'render_as' => 'short_text'),
			'signup_time' => array('label' => __l('Signup Time'), 'render_as' => 'date_time', 'width' => 150)
		),
		'delete' => FALSE,
		//'controller_order' => array('delete', 'create', 'update', 'view'),
		'controllers' => array(
			'create' => array('label' => __l('Add')),
			'update' => array('label' => __l('Edit'))
		),
		'form_options' => array(
			'default' => array(
				'order' => array('player_id', 'userid', 'email', 'password', 'signup_time'),
				'fields' => array(
					'password' => array('required' => TRUE, 'render_as' => 'password')
				),
				'callbacks' => array(
					'process_values' => 'process_values'
				)
			),
			'update' => array(
				'fields' => array(
					'password' => array('required' => FALSE, 'render_as' => 'password')
				)
			),
			'create' => array(
				'order' => array('userid', 'email', 'password', 'signup_time'),
				'fields' => array(
					'signup_time' => array('value' => time())
				)
			)
		),
		'usepager' => TRUE,
		'useRp' => TRUE,
		'rp' => 15,
		'height' => 500,
		'action_callback' => 'admin_player_list2.php',
		'findtext' => __l('Find'),
		'pagestat' => __l('Displaying {from} to {to} of {total} items'),
		'pagetext' => __l('Page'),
		'outof' => __l('of'),
		'findtext' => __l('Find'),
		'procmsg' => __l('Processing, please wait ...'),
		'nomsg' => __l('No items'),
		'errormsg' => __l('Connection Error')
	);
	$table = new Table();
	//$table->model_value_processors['client']['phpchess::player'] = test;
	if($_POST['page'] || $_POST['tbl_action'] || $_POST['form_action'])
	{
		header("Content-type: application/json");
		if(isset($_POST['page']))
		{
			$result = $table->handle_table_action('data', $tbl_opts);
		}
		elseif(isset($_POST['tbl_action']))
		{
			$result = $table->handle_table_action($_POST['tbl_action'], $tbl_opts);
			
		}
		echo json_encode($result);
		exit();
	}
	else
	{
		$result = $table->initialise($tbl_opts);
		if(!$result['success'])
		{
			$table_init_options = FALSE;
		}
		else
		{
			
				$table_init_options = json_encode($result['table_init_options']);
		}
	}
	
	// function test($data)
	// {
		// for($i = 0; $i < count($data['instances']); $i++)
		// {
			// $data['instances'][$i]['cell']['blah'] = "Userid has " . strlen($data['instances'][$i]['cell']['userid']) . " characters";
		// }
	// }
	
	function process_values($args)
	{
		include('../bin/config.php');
		$salt = $conf['password_salt'];
		$pass = $args['values']['password'];
		if($pass == '')
			unset($args['values']['password']);
		else
			$args['values']['password'] = md5($salt . $pass);
	}
	
?>

<html>
<head>
<title><?php echo __l('Administration Page - Players');?></title>

<META NAME="keywords" CONTENT="">
<META NAME="DESCRIPTION" CONTENT="">
<META NAME="OWNER" CONTENT="Christian">
<META NAME="RATING" CONTENT="General">
<META NAME="ROBOTS" CONTENT="index,follow">
<META HTTP-EQUIV="CONTENT-LANGUAGE" CONTENT="English">
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">

<link rel="stylesheet" href="<?php echo $Root_Path."skins/".$SkinName."/";?>layout.css" type="text/css">
<link rel="stylesheet" href="../includes/flexigrid/css/flexigrid.pack.css" type="text/css">
<link rel="stylesheet" href="../includes/jquery/cupertino/jquery-ui-1.8.16.custom.css" type="text/css">
<script type="text/javascript" src="../includes/jquery/jquery-1.7.1.min.js"></script>
<script type="text/javascript" src="../includes/jquery/jquery-ui-1.8.16.custom.min.js"></script>
<script type="text/javascript" src="../includes/flexigrid/js/flexigrid.js"></script>
<script type="text/javascript" src="../includes/data render/controls.js"></script>
<script type="text/javascript" src="../includes/data render/table.js"></script>
<script type="text/javascript" src="../includes/data render/form.js"></script>
<script type="text/javascript" src="../includes/data render/common.js"></script>
<?php include($Root_Path."includes/javascript_admin.php");?>
</head>
<body>

<?php include("../skins/".$SkinName."/layout_admin_cfg.php");?>

</body>
</html>

<?php
  //////////////////////////////////////////////////////////////
  $oR3DCQuery->Close();
  $oAdmin->Close();
  unset($oR3DCQuery);
  unset($oAdmin);
  //////////////////////////////////////////////////////////////
?>