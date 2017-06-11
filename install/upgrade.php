<?php

define('CHECK_PHPCHESS', true);

$host = $_SERVER['HTTP_HOST'];
$self = $_SERVER['PHP_SELF'];
$query = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
$url = !empty($query) ? "http://$host$self?$query" : "http://$host$self";

$nMajor = 4;
$nMinor = 3;
$nBuild = 0;

header("Content-Type: text/html; charset=utf-8");


mysqli_report(MYSQLI_REPORT_STRICT);
session_start();

$g_stages = array(
	'upgrade_instructions', 'validate_existing_install', 'backup', 'upgrade_database', 'upgrade_config_file', 'hash_passwords', 'upgrade_finalise', 'upgrade_finished'
);
$g_stage_message = NULL;
$g_params = array();
$menu_items = array('Instructions', 'Validate Install', 'Data Backup', 'Update Database', 'Update Config File', 'Hash Passwords', 'Finalising');

if(isset($_SESSION['stage']))
	$g_stage = $_SESSION['stage'];
else
{
	$_SESSION['stage'] = $g_stage = 0;
}
//echo '<pre style="font-size: 0.7em">'; var_dump($_SESSION); echo '</pre>';
if($g_stage == 0)	// Instructions
{
	// User pressed the next button?
	if(isset($_POST['next']))
	{
		$g_stage = 1;
		$_SESSION['stage'] = 1;
		unset($_POST['next']);
	}
}

if($g_stage == 1)	// Install validation
{
	$errors = "";
	$g_params['validated'] = FALSE;
	$g_params['errors'] = '';
	// // User pressed the next button?
	// if(isset($_POST['next']))
	// {
		// $g_stage = 2;
		// $_SESSION['stage'] = 2;
		// unset($_POST['next']);
	// }
	// else
	if(isset($_POST['check']))
	{
		$result = validate_existing_install();
		$g_params['validated'] = $result['success'];
		$g_params['errors'] = $result['errors'];
		if($result['success'] === TRUE)
		{
			$_SESSION['stage'] = $g_stage = 2;
		}
	}
}
if($g_stage == 2)		// Database Backup
{
	$g_params['did_backup'] = FALSE;
	$g_params['success'] = FALSE;
	$g_params['error'] = '';
	if(isset($_POST['next']))
	{
		$g_stage = 3;
		$_SESSION['stage'] = 3;
		unset($_POST['next']);
	}
	elseif(isset($_POST['do_backup']))		// User wants to download db backup file
	{
		$result = make_backup();
		$g_params['success'] = $result['success'];
		$g_params['error'] = $result['error'];
		if($result['success'])
		{
			//$g_params['did_backup'] = TRUE;
			header('Content-Type: "sql"');
			header('Content-Disposition: attachment; filename="phpchess_backup.sql"');
			header("Content-Transfer-Encoding: binary");
			header('Expires: 0');
			header('Pragma: no-cache');
			header("Content-Length: ".strlen($result['sql']));
			echo $result['sql'];
			exit();
		}
	}
}
if($g_stage == 3)		// Upgrade the database
{
	$g_params['cahnges_required'] = FALSE;
	$g_params['upgraded'] = FALSE;
	$g_params['errors'] = NULL;
	include('../bin/config.php');
	$config = array(
		'hostname' => $conf['database_host'],
		'username' => $conf['database_login'],
		'password' => $conf['database_pass'],
		'database' => $conf['database_name']
	);
	if(isset($_POST['next']))
	{
		$_SESSION['stage'] = $g_stage = 4;
		unset($_POST['next']);
	}
	elseif(isset($_POST['upgrade']))	// upgrade db
	{
		$diff = work_out_db_changes("db_structure_$nMajor.$nMinor.$nBuild.json", $config);
		$result = apply_db_changes($diff, $config);
		$g_params['upgraded'] = TRUE;
		$g_params['success'] = $result['success'];
		$g_params['errors'] = $result['errors'];
		//echo '<pre>' ; var_dump($result); echo '</pre>';
	}
	else	// Determine if db changes are required.
	{
		$diff = work_out_db_changes("db_structure_$nMajor.$nMinor.$nBuild.json", $config);
		// echo '<pre>' ; var_dump($diff); echo '</pre>';
		if(count($diff) > 0)
		{
			$g_params['changes_required'] = TRUE;
		}
	}
}

if($g_stage == 4)
{
	$g_params['updated'] = FALSE;
	$g_params['success'] = '';
	if(isset($_POST['next']))
	{
		$_SESSION['stage'] = $g_stage = 5;
		unset($_POST['next']);
	}
	elseif(isset($_POST['update']))
	{
		$g_params['ran_update'] = TRUE;
		$g_params['error'] = FALSE;
		$g_params['updated'] = FALSE;
		$msg = '';
		$salt = gen_salt();
		$result = upgrade_config_file($salt);
		//var_dump($result);
		if($result['success'] === FALSE)
			$g_params['error'] = $result['error'];
		if($result['upgraded'])
			$g_params['updated'] = "The config file was updated to include these missing settings: '" . implode("', '", $result['missing']) . "'<br/>";
		//var_dump($g_params);
	}
}

if($g_stage == 5)
{
	if(isset($_POST['next']))
	{
		$_SESSION['stage'] = $g_stage = 6;
		unset($_POST['next']);
	}
	if(isset($_POST['hash']))
	{
		$g_params['ran_hashing'] = TRUE;
		$result = hash_passwords();
		$g_params['success'] = $result['success'];
		if($result['success'] === FALSE)
		{
			$g_params['error'] = $result['error'];
			$g_params['error_type'] = $result['error_type'];
		}
	}
	
}

// Check if the config file needs to be upgraded.
if($g_stage == 6)
{
	if(isset($_POST['next']))
	{
		$_SESSION['stage'] = $g_stage = 7;
	}
	elseif(isset($_POST['validate']))
	{
		$msg = '';
		
		include('Upgrade_Validator.php');
		include('../bin/config.php');
		$host = $conf['database_host'];
		$db = $conf['database_name'];
		$user = $conf['database_login'];
		$pass = $conf['database_pass'];
		
		if(!is_skins_default_cells_empty())
		{
			$g_params['warnings'] = "The folder '/skins/default/cells' is not empty. Please make sure the folder is empty.";
		}
		
		// Is db access possible?
		try {
			$dbh = new mysqli($host, $user, $pass, $db);
			if ($dbh->connect_errno) {
				echo "FAILED TO CONNECT TO THE DB. ERROR: " . $dbh->connect_error;
				exit();
			}
		} catch (mysqli_sql_exception $e) {
			echo "FAILED TO CONNECT TO THE DB. ERROR: " . $e->getMessage();
			exit();
		}
		$UV = new Upgrade_Validator($dbh);
		$result = $UV->validate("$nMajor.$nMinor.$nBuild");
		if(count($result['errors']) > 0)
			$g_params['errors'] = "Errors occurred during validation:<br/>" . implode('<br/>', $result['errors']);
		elseif(isset($g_params['warnings']))
			$msg .= "Validation of the install completed. Some issues where found which you should look at:";
		else
			$msg .= "Validation of the install completed. No problems encountered.";
		$g_params['validated'] = TRUE;
		$g_params['result'] = $msg;
		
		
		
	}
}

// Upgrade is completed.
if($g_stage == 7)
{ 
	//require_once("../admin/rsslib.php");
	//$g_params['news'] = RSS_Links("http://www.phpchess.com/?feed=rss2", 15);
}

// Generate the view

include ('./views/layout.php');


function validate_existing_install()
{
	$result = array('success' => TRUE, 'errors' => '');

	include('../bin/config.php');
	$host = $conf['database_host'];
	$db = $conf['database_name'];
	$user = $conf['database_login'];
	$pass = $conf['database_pass'];
	
	// Is db access possible?
	try{
		$dbh = new mysqli($host, $user, $pass, $db);
		if ($dbh->connect_errno) {
			$result['success'] = FALSE;
			$result['errors'] = "FAILED TO CONNECT TO THE DB. ERROR: " . $dbh->connect_error;
		}
	} catch (mysqli_sql_exception $e) {
		// Connection failed: SQLSTATE[HY000] [2005] Unknown MySQL server host 'hmm' (11004)
		// Connection failed: SQLSTATE[28000] [1045] Access denied for user 'root'@'localhost' (using password: YES)
		// Connection failed: SQLSTATE[42000] [1049] Unknown database 'blob'
		preg_match('/SQLSTATE\[\w+\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
		$code = $matches[1];
		if($code == '2005')
		{
			$err = "MySQL server host address is incorrect";
		}
		elseif($code == '1045')
		{
			$err = "Username and/or password are incorrect";
		}
		elseif($code == '1049')
		{
			$err = "Database name is unknown";
		}
		else
		{
			$err = 'Connection failed: ' . $e->getMessage();
		}
		$result['success'] = FALSE;
		$result['errors'] = 'Unable to connect to the database. The error is: ' . $err . ' .<br/><br/>Please check the config file to make sure the database values are correct.';
	
		return $result;
	}
	
	// Do the db tables exist? Will just check for c4m_admin, game and player and assume the rest is ok.
	
	// Does the /bin/installed.txt file exist?
	if(!file_exists('../bin/installed.txt'))
	{
		$result['success'] = FALSE;
		$result['errors'] = 'Unable to find the file `installed.txt` in the `bin` folder. Cannot progress with the upgrade.';
	}
	
	return $result;
}

function make_backup()
{
	include('../bin/config.php');
	include('archive.php');
	$host = $conf['database_host'];
	$db = $conf['database_name'];
	$user = $conf['database_login'];
	$pass = $conf['database_pass'];

	$result = make_db_backup('./myBackups', array('host' => $host, 'user' => $user, 'pass' => $pass, 'db' => $db));

	return $result;
}

function upgrade_config_file($salt)
{
	// Load the existing config file and see if all the required settings exist. If not, need to
	// update the new config file using the old values and new default values where settings are missing.
	
	include('../bin/config.php');
	
	$required = array('database_host', 'database_name', 'database_login', 'database_pass', 'site_name', 'site_url', 'registration_email', 'password_salt', 'session_timeout_sec', 'new_user_requires_approval', 'chat_refresh_rate', 'absolute_directory_location', 'avatar_absolute_directory_location', 'avatar_image_disk_size_in_bytes', 'avatar_image_width', 'avatar_image_height', 'view_chess_games_refresh_rate', 'last_move_check_rate');
	
	// Want to include blank lines in the config file. Order specifies the order of the setting in the file.
	$order = array('database_host', 'database_name', 'database_login', 'database_pass', NULL, 'site_name', 'site_url', 'registration_email', NULL, 'password_salt', NULL, 'session_timeout_sec', NULL, 'new_user_requires_approval', NULL, 'chat_refresh_rate', NULL, 'absolute_directory_location', NULL, 'avatar_absolute_directory_location', 'avatar_image_disk_size_in_bytes', 'avatar_image_width', 'avatar_image_height', NULL, 'view_chess_games_refresh_rate', 'last_move_check_rate');
	
	// Default values to apply to missing settings.
	$site_url = $_SERVER['SERVER_NAME'] . strrev(substr(strrev($_SERVER['SCRIPT_NAME']), 19));
	if(substr($url, 0, 7) != 'http://')
		$url = 'http://' . $url;
	$abs = strrev(substr(strrev($_SERVER['SCRIPT_FILENAME']), 19));
	$abs_avatar = strrev(substr(strrev($_SERVER['SCRIPT_FILENAME']), 19)) . 'avatars' . DIRECTORY_SEPARATOR ;
	$defaults = array(
		'database_host' => '',
		'database_name' => '',
		'database_login' => '',
		'database_pass' => '',
		'site_name' => '',
		'site_url' => $site_url,
		'registration_email' => '',
		'password_salt' => $salt,
		'session_timeout_sec' => 3600,
		'new_user_requires_approval' => TRUE,
		'chat_refresh_rate' => 30,
		'absolute_directory_location' => $abs,
		'avatar_absolute_directory_location' => $abs_avatar,
		'avatar_image_disk_size_in_bytes' => 102400,
		'avatar_image_width' => 100,
		'avatar_image_height' => 100,
		'view_chess_games_refresh_rate' => 30,
		'last_move_check_rate' => 10
	);
	
	// Find out what settings are missing.
	$missing = array();
	$settings = array();
	foreach($required as $setting)
	{
		if(!isset($conf[$setting]))
		{
			$missing[] = $setting;
			$settings[$setting] = $defaults[$setting];
		}
		else
		{
			$settings[$setting] = $conf[$setting];
		}
	}
	
	if(count($missing) == 0) // nothing to do.
		return array('success' => TRUE, 'upgraded' => FALSE, 'missing' => array());

	//var_dump($settings, $missing);
	
	$content .= "<?php\r\n";
	$content .= "////////////////////////////////////////////////////////////////////////\r\n";
	$content .= "//Configuration file for the phpChess\r\n";
	$content .= "////////////////////////////////////////////////////////////////////////\r\n";
	$content .= "\r\n";

	foreach($order as $setting)
	{
		if($setting === NULL)
		{
			$content .= "\r\n";
		}
		else
		{
			$val = $settings[$setting];
			if(is_bool($val))
			{
				$content .= '$conf[\'' . $setting . '\'] = ' . ($val === TRUE ? 'true' : 'false') . ';';
			}
			elseif(is_numeric($val))
			{
				$content .= '$conf[\'' . $setting . '\'] = ' . $val . ';';
			}
			else
			{
				$content .= '$conf[\'' . $setting . '\'] = "' . addslashes($val) . '";';
			}
			if($setting == 'view_chess_games_refresh_rate')
				$content .= '            // Number of seconds between updates when viewing games available.';
			elseif($setting == 'last_move_check_rate')
				$content .= '            // Number of seconds between new move checks in realtime games.';
			$content .= "\r\n";
		}
	}
	$content .= "\r\n?>";
	
	$success = file_put_contents('../bin/config.php', $content);
	if($success === FALSE)
	{
		$error = "Unable to write to file '/bin/config.php'.";
	}
	else
	{
		$success = TRUE;
	}
	//echo $content;
	
	return array('success' => $success, 'upgraded' => TRUE, 'missing' => $missing, 'error' => $error);
}

function hash_passwords()
{
	include('../bin/config.php');
	$host = $conf['database_host'];
	$db = $conf['database_name'];
	$user = $conf['database_login'];
	$pass = $conf['database_pass'];
	if(!isset($conf['password_salt']))
	{
		return array('success' => FALSE, 'error' => 'The password salt is missing from the config file!', 'error_type' => 'no_salt');
	}
	$salt = $conf['password_salt'];
	
	$result = array('success' => TRUE, 'error' => FALSE);
	
	$q = '';
	try {
		$dbh = mysqli_connect($host, $user, $pass, $db);
		$q = "SELECT `player_id`, `password` FROM `player` WHERE is_hashed = 0";

		$results = $dbh->query($q);
		$players = array();
		if($results !== FALSE)
		{
			while($player = $results->fetch_assoc())
			{
				$players[$player['player_id']] = $player['password'];
			}
		}
		else
		{
			return array('success' => FALSE, 'error' => "Failed to run query ($q). Error: " . $dbh->error, 'error_type' => 'select_error');
		}
		$q = "UPDATE player SET password = ?, is_hashed = 1 WHERE player_id = ?";
		$sth = $dbh->prepare($q);
		foreach($players as $id => $pass)
		{
			$hash = md5($salt . $pass);
			$sth->bind_param('si', $hash, $id);
			if(!$sth->execute())
			{
				$result['success'] = FALSE;
				$result['error'] = "Failed to run query ($q). Error: " . $dbh->error;
				$result['error_type'] = 'update_error';
				return $result;
			}
		}
		
		// Hash the admin's password.
		$q = "SELECT `a_id`, `a_password` FROM `c4m_admin` WHERE is_hashed = 0";
		$results = $dbh->query($q);
		$admins = array();
		if($results !== FALSE)
		{
			while($admin = $results->fetch_assoc())
			{
				// Do a length check as a hack to check if the password was likely already hashed before the
				// is_hashed field was added. This is not 100% safe.
				if(strlen($admin['a_password']) != 32)
					$admins[$admin['a_id']] = $admin['a_password'];
				else	
					$admins[$admin['a_id']] = array('pass' => $admin['a_password']);
			}
		}
		else
		{
			return array('success' => FALSE, 'error' => "Failed to run query ($q). Error: " . $dbh->error, 'error_type' => 'select_error');
		}
		$q = "UPDATE c4m_admin SET a_password = ?, is_hashed = 1 WHERE a_id = ?";
		$sth = $dbh->prepare($q);
		foreach($admins as $id => $pass)
		{
			if(is_array($pass))		// hack to maintain previously hashed password.
				$hash = $pass['pass'];
			else
				$hash = md5($salt . $pass);
			$sth->bind_param('si', $hash, $id);
			if(!$sth->execute())
			{
				$result['success'] = FALSE;
				$result['error'] = "Failed to run query ($q). Error: " . $dbh->error;
				$result['error_type'] = 'update_error';
				return $result;
			}
		}
		
	} catch (mysqli_sql_exception $e) {
		$result['error'] = array('msg' => "Error on line " . $e->getLine() . ': ' . $e->getMessage() . '<br/>Current query: ' . $q);
		$result['error_type'] = 'exception';
		$result['success'] = FALSE;
		//break;
	}
	//var_dump($existing);
	return $result;
}

function is_skins_default_cells_empty()
{
	$found = FALSE;
	foreach (new DirectoryIterator('../skins/default/cells') as $fileInfo)
	{
		if($fileInfo->isDot()) continue;
		if($fileInfo->isFile())
		{
			$found = TRUE;
			break;
		}
	} 
	return !$found;
}

function work_out_db_changes($file, $config)
{
	$contents = file_get_contents($file);
	$new_struct = json_decode($contents, TRUE);
	include('DBCompare.php');
	$dbm = new DBCompare($config);
	$old_structure = $dbm->get_db_structure($config['database']);
	$diff = $dbm->compare_structures($old_structure, $new_struct);
	return $diff;
	//var_dump($diff);
	//apply_db_changes($diff, $config);
	//exit();
}

function apply_db_changes($diff, $config)
{
	$db = $config['database'];
	$removes = array();
	$alters = array();
	$adds = array();
	$queries = array();
	foreach($diff as $table => $change)
	{
		if($change['action'] == 'add')
		{
			$queries[] = gen_sql_for_table_create($table, $change);
		}
		elseif($change['action'] == 'remove')
		{
			//exit('TODO: removing tables');
		}
		else
		{
			//exit('TODO: altering tables');
			$queries[] = "ALTER TABLE `$db`.`$table` " . gen_sql_for_alter($change);
		}
	}
	//echo '<pre>' ; var_dump($queries); echo '</pre>';
	
	$dbh = mysqli_connect($config['hostname'], $config['username'], $config['password'], $config['database' ]);
	$result = array('success' => TRUE, 'errors' => array(), 'torun' => array_merge($adds, $queries), 'upto' => 0);
	foreach($queries as $query)
	{
		try {
			if (!$dbh->query($query)) {
				$result['errors'][] = array('msg' => $dbh->error, 'query' => $query);
				$result['success'] = FALSE;
				//break;
			}
		} catch (mysqli_sql_exception $e) {
			$result['errors'][] = array('msg' => "Error on line " . $e->getLine() . ': ' . $e->getMessage(), 'query' => $query);
			$result['success'] = FALSE;
			//break;
		}
		$result['upto']++;
	}
	
	return $result;
}

function gen_sql_for_table_create($table, $change)
{
	$sql = "CREATE TABLE `$table` (";
	$parts = array();
	foreach($change['columns'] as $name => $opts)
	{
		$parts[] = "`$name` " . gen_column_def($opts['options']);
	}
	foreach($change['constraints'] as $name => $opts)
	{
		$first = TRUE;
		$const_sql = '';
		// Constraints can be assigned to multiple columns. First iteration provides the constraint
		// settings and the first column. Any subsequent iterations will add columns to the constraint.
		foreach($opts['options'] as $opt)		
		{
			if($first)
			{
				if($name == 'PRIMARY')
				{
					$const_sql .= 'PRIMARY KEY';
				}
				else
				{
					if($opt['non_unique'] == 0)
						$const_sql .= "UNIQUE KEY `$name`";
					else
						$const_sql .= "KEY `$name`";
				}
				if($opt['index_type'] !== '')
					$const_sql .= ' USING ' . $opt['index_type'];
				$const_sql .= ' (`' . $opt['column_name'] . '`';
				$first = FALSE;
			}
			else
			{
				$const_sql .= ', `' . $opt['column_name'] . '`';
			}
		}
		$const_sql .= ')';
		$parts[] = $const_sql;
	}
	$sql .= implode(', ', $parts);
	$sql .= ")";
	return $sql;
}

function gen_sql_for_alter($change)
{
	$sql = array();
	//var_dump($change);
	foreach($change['columns'] as $name => $opts)
	{
		if($opts['action'] == 'add')
		{
			$sql[] = "ADD `$name` " . gen_column_def($opts['options']);
		}
		elseif($opts['action'] == 'modify')
		{
			$sql[] = "MODIFY `$name` " . gen_column_def($opts['options']);
		}
		elseif($opts['action'] == 'remove')
		{
			$sql[] = "DROP `$name` ";
		}
	}
	foreach($change['constraints'] as $name => $opts)
	{
		if($opts['action'] == 'add')
		{
			$sql[] = gen_constraint_def('add', $name, $opts['options']);
		}
		elseif($opts['action'] == 'modify')
		{
			$sql[] = gen_constraint_def('modify', $name, $opts['options']);
		}
		if($opts['action'] == 'remove')
		{
			$sql[] = "DROP INDEX `$name`";
		}
	}

	return implode(', ', $sql);
}

function gen_column_def($opts)
{
	$data_type = $opts['type'];
	$not_null = ($opts['null'] == 'NO' ? ' NOT NULL' : ' NULL');
	$default = $opts['default'];
	if($default === NULL)
	{
		if($opts['null'] == 'YES')
			$default = " DEFAULT NULL";
		else
			$default = '';
	}
	else
	{
		$default = " DEFAULT '$default'";
	}
		
	if($opts['extra'] == 'auto_increment') $auto_increment = ' AUTO_INCREMENT';
	if($opts['comment'] !== '') $comment = " COMMENT '" . $opts['comment'] . "'";
	
	$sql = $data_type.$not_null.$default.$auto_increment.$comment;
	
	return $sql;
}

function gen_constraint_def($action, $name, $opts)
{
	$sql = '';
	if($action == 'modify')		// Must drop and add constraint for 'modify'
	{
		$sql .= "DROP KEY `$name`, ";
	}
	$first = TRUE;
	// Constraints can be assigned to multiple columns. First iteration provides the constraint
	// settings and the first column. Any subsequent iterations will add columns to the constraint.
	foreach($opts as $opt)		
	{
		if($first)
		{
			if($name == 'PRIMARY')
			{
				$sql .= 'ADD PRIMARY KEY';
			}
			else
			{
				if($opt['non_unique'] == 0)
					$sql .= "ADD UNIQUE KEY `$name`";
				else
					$sql .= "ADD KEY `$name`";
			}
			if($opt['index_type'] !== '')
				$sql .= ' USING ' . $opt['index_type'];
			$sql .= ' (`' . $opt['column_name'] . '`';
			$first = FALSE;
		}
		else
		{
			$sql .= ', `' . $opt['column_name'] . '`';
		}
	}
	$sql .= ')';

	return $sql;
}

function gen_salt()
{
	$chars = array(
		'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
		'1','2','3','4','5','6','7','8','9','0','-','_','+','=','[','{','}',']','|',',','<','.','>'.'?','/','~',
		'`','!','@','#','$','%','^','&','*','(',')'
	);
	$salt = '';
	for($i = 0; $i < 16; $i++)
		$salt .= $chars[rand(1, 89)];
	return $salt;
}

?>