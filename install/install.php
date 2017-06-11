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
	'instructions', 'database', 'phpchess_server', 'administrator', 'installation', 'initial_settings', 'finishing', 'finished'
);
$g_stage_message = NULL;
$g_params = array();
$menu_items = array('Instructions', 'Database', 'phpChess Server', 'Administrator', 'Installation', 'Initial Settings', 'Finishing');

if(isset($_SESSION['stage']))
	$g_stage = $_SESSION['stage'];
else
{
	$_SESSION['stage'] = $g_stage = 0;
}
//echo '<pre style="font-size: 0.7em">'; var_dump($_SESSION); echo '</pre>';
if($g_stage == 0)
{
	// User pressed the next button?
	if(isset($_POST['action']))
	{
		$g_stage = 1;
		$_SESSION['stage'] = 1;
	}
}

if($g_stage == 1)	// Database settings
{
	if(isset($_POST['next']))		// Proceed to next stage.
	{
		$_SESSION['stage'] = $g_stage = 2;
		unset($_POST['next']);
	}
	elseif(isset($_POST['test']))	// User pressed the 'test connection' button. Test if a connection can be achieved.
	{
		$host = $_POST['hostname'];
		$user = $_POST['username'];
		$pass = $_POST['password'];
		$db = $_POST['database'];
		
		try{
			$dbh = new mysqli($host, $user, $pass, $db);
			if ($dbh->connect_errno) {
				$result['success'] = FALSE;
				$result['errors'] = "FAILED TO CONNECT TO THE DB. ERROR: " . $dbh->connect_error;
			}
			else
			{
				$g_stage_message = "Can connect to database.";
				$g_params['connection_established'] = TRUE;
				$_SESSION['dbcon'] = array('host' => $host, 'user' => $user, 'pass' => $pass, 'db' => $db);
			}
		} catch (mysqli_sql_exception $e) {
			$host_ok = $user_ok = $pass_ok = $db_ok = FALSE;
			// Connection failed: SQLSTATE[HY000] [2005] Unknown MySQL server host 'hmm' (11004)
			// Connection failed: SQLSTATE[28000] [1045] Access denied for user 'root'@'localhost' (using password: YES)
			// Connection failed: SQLSTATE[42000] [1049] Unknown database 'blob'
			preg_match('/SQLSTATE\[\w+\] \[(\w+)\] (.*)/', $e->getMessage(), $matches);
			$code = $matches[1];
			if($code == '2005')
			{
				$g_stage_message = "MySQL server host address is incorrect";
			}
			elseif($code == '1045')
			{
				$g_stage_message = "Username and/or password are incorrect";
				$host_ok = TRUE;
			}
			elseif($code == '1049')
			{
				$g_stage_message = "Database name is unknown";
				$host_ok = $user_ok = $pass_ok = TRUE;
			}
			else
			{
				$g_stage_message = 'Connection failed: ' . $e->getMessage();
			}
			$g_params['connection_established'] = FALSE;
			$g_params['host'] = $host;
			$g_params['host_ok'] = $host_ok;
			$g_params['user'] = $user;
			$g_params['user_ok'] = $user_ok;
			$g_params['pass'] = $pass;
			$g_params['pass_ok'] = $pass_ok;
			$g_params['db'] = $db;
			$g_params['db_ok'] = $db_ok;
		}

	}
	elseif(isset($_SESSION['dbcon']))	// Use previously submitted values
	{
		$g_stage_message = '';
		$g_params['connection_established'] = FALSE;
		$g_params['host'] = $_SESSION['dbcon']['host'];
		$g_params['host_ok'] = TRUE;
		$g_params['user'] = $_SESSION['dbcon']['user'];
		$g_params['user_ok'] = TRUE;
		$g_params['pass'] = $_SESSION['dbcon']['pass'];
		$g_params['pass_ok'] = TRUE;
		$g_params['db'] = $_SESSION['dbcon']['db'];
		$g_params['db_ok'] = TRUE;
	}
	else	// First time viewing this page.
	{
		$g_stage_message = '';
	}
	
}

if($g_stage == 2)	// Server settings
{
	if(isset($_POST['next']))		// Server info was submitted.
	{
		$name = $_POST['site_name'];
		$url = $_POST['site_url'];
		$abs = $_POST['absolute_path'];
		$abs_avatar = $_POST['absolute_avatar_path'];
		$_SESSION['server'] = array('name' => $name, 'url' => $url, 'abs' => $abs, 'abs_avatar' => $abs_avatar);
		$g_stage = 3;
		$_SESSION['stage'] = 3;
		unset($_POST['next']);
	}
	elseif(isset($_POST['back']))	// Go back to previous stage.
	{
		$_SESSION['stage'] = $g_stage = 1;
		$g_stage_message = '';
		$g_params['connection_established'] = FALSE;
		$g_params['host'] = $_SESSION['dbcon']['host'];
		$g_params['host_ok'] = TRUE;
		$g_params['user'] = $_SESSION['dbcon']['user'];
		$g_params['user_ok'] = TRUE;
		$g_params['pass'] = $_SESSION['dbcon']['pass'];
		$g_params['pass_ok'] = TRUE;
		$g_params['db'] = $_SESSION['dbcon']['db'];
		$g_params['db_ok'] = TRUE;
	}
	
	// Work out default values.
	$url = $_SERVER['SERVER_NAME'] . strrev(substr(strrev($_SERVER['SCRIPT_NAME']), 19));
	if(substr($url, 0, 7) != 'http://')
		$url = 'http://' . $url;
	$abs = strrev(substr(strrev($_SERVER['SCRIPT_FILENAME']), 19));
	$abs_avatar = strrev(substr(strrev($_SERVER['SCRIPT_FILENAME']), 19)) . 'avatars' . DIRECTORY_SEPARATOR ;
	$g_params['name'] = '';
	$g_params['url'] = $url;
	$g_params['absolute_path'] = $abs;
	$g_params['absolute_avatar_path'] = $abs_avatar;
	
	if(isset($_SESSION['server']))	// Use previously submitted values
	{
		$g_params['name'] = $_SESSION['server']['name'];
		$g_params['url'] = $_SESSION['server']['url'];
		$g_params['absolute_path'] = $_SESSION['server']['abs'];
		$g_params['absolute_avatar_path'] = $_SESSION['server']['abs_avatar'];
	}
}

if($g_stage == 3)	// Administrator settings
{
	if(isset($_POST['next']))		// Server info was submitted.
	{
		$user = $_POST['username'];
		$pass = $_POST['password'];
		$email = $_POST['email'];
		$_SESSION['admin'] = array('user' => $user, 'pass' => $pass, 'email' => $email);
		$_SESSION['password_salt'] = gen_salt();
		$g_stage = 4;
		$_SESSION['stage'] = 4;
		unset($_POST['next']);
	}
	elseif(isset($_POST['back']))	// Go back to previous stage
	{
		$_SESSION['stage'] = $g_stage = 2;
		$g_params['name'] = $_SESSION['server']['name'];
		$g_params['url'] = $_SESSION['server']['url'];
		$g_params['absolute_path'] = $_SESSION['server']['abs'];
		$g_params['absolute_avatar_path'] = $_SESSION['server']['abs_avatar'];
	}
	if(isset($_SESSION['admin']))	// Use previously submitted values
	{
		$g_params['user'] = $_SESSION['admin']['user'];
		$g_params['pass'] = $_SESSION['admin']['pass'];
		$g_params['email'] = $_SESSION['admin']['email'];
	}
	else
	{
		$g_params['user'] = '';
		$g_params['pass'] = '';
		$g_params['email'] = '';
	}
}

if($g_stage == 4)	// Installation page
{
	// check /bin/ folder is writable
	$can_install = TRUE;
	if(!is_writeable('../bin/'))
		$can_install = FALSE;
	$g_params['can_install'] = $can_install;
	$g_params['ran_install'] = FALSE;
	if(isset($_POST['install']))
	{
		// Do install. Create config file and create database tables.
		$g_params['ran_install'] = TRUE;
		
		if(generate_config('../bin/config.php') === FALSE)
			$g_params['result'] = array('success' => FALSE, 'error' => 'Unable to write the config file (/bin/config.php)', 'progress' => '');
		else
			$g_params['result'] = runSQL();
		//var_dump($result);
		
		//echo 'do install';
	}
	elseif(isset($_POST['next']))
	{
		$_SESSION['stage'] = $g_stage = 5;
		unset($_POST['next']);
	}
	elseif(isset($_POST['back']))
	{
		$_SESSION['stage'] = $g_stage = 3;
		$g_params['user'] = $_SESSION['admin']['user'];
		$g_params['pass'] = $_SESSION['admin']['pass'];
		$g_params['email'] = $_SESSION['admin']['email'];
	}
}

if($g_stage == 5)	// Initial settings page
{
	if(isset($_POST['next']))
	{
		$_SESSION['admin']['require_approval'] = ($_POST['require_approval'] == '1' ? TRUE : FALSE);
		if(generate_config('../bin/config.php') === FALSE)
		{
			$g_params['errors'] = 'Unable to write the config file (/bin/config.php)';
		}
		else
		{
			// Other settings that require changes in db tables.
			$data = array();
			$data['allow_uploads'] = $_POST['uploads'] == '1' ? 1 : 0;
			$data['timeout_snail'] = (float)$_POST['timeout_snail'];
			$data['timeout_slow'] = (float)$_POST['timeout_slow'];
			$data['timeout_normal'] = (float)$_POST['timeout_normal'];
			$data['timeout_fast'] = (float)$_POST['timeout_fast'];
			$data['timeout_blitz'] = (float)$_POST['timeout_blitz'];
			$data['language'] = $_POST['language'] . '.txt';
			$data['max_players'] = (int)$_POST['max_players'];
			$result = update_settings($data);
			if($result['success'])
			{
				unset($_POST['next']);
				$_SESSION['stage'] = $g_stage = 6;
				$_SESSION['allow_uploads'] = $data['allow_uploads'];
			}
			else
			{
				// Display the error message.
				$g_params['errors'] = implode('<br/>', $result['errors']);
			}
		}
	}
	elseif(isset($_POST['continue']))	// Errors displayed, but will continue with install.
	{
		$_SESSION['stage'] = $g_stage = 6;
	}
	else
	{
		$g_params['languages'] = array('english');
		$g_params['errors'] = NULL;
	}
}

if($g_stage == 6)	// Finishing page
{
	$err = ini_get('error_reporting');
	if($err != 0)
	{
		$showing = TRUE;
		$g_params['showing_all'] = ($err == E_ALL ? TRUE : FALSE);
	}
	$g_params['showing_errors'] = $showing;
	$g_params['check_failed'] = FALSE;
	if(isset($_POST['check']))
	{
		$result = check_permissions();
		if($result['success'])
		{
			$_SESSION['stage'] = $g_stage = 7;
		}
		else
		{
			$g_params['check_failed'] = TRUE;
			$g_params['errors'] = $result['errors'];
		}
	}
	elseif(isset($_POST['skip']))
	{
		$_SESSION['stage'] = $g_stage = 7;
	}
	//$_SESSION['stage'] = $g_stage = 5;
}

if($g_stage == 7)	// Finish the install by creating the installed.txt file.
{
	file_put_contents('../bin/installed.txt', 'Install Completed');
	// Destroy session to allow the upgrade module to be run if needed.
	session_destroy();
}


// Generate the view

include ('./views/layout.php');

function runSQL()
{
	$progress = '';
	$progress .= "Starting Install<br>";
	$bError = false;
	$link = @mysqli_connect($_SESSION['dbcon']['host'], $_SESSION['dbcon']['user'], $_SESSION['dbcon']['pass']);

	if($link)
	{
		$progress .= "DB Connection<br>";
		// Check if the Database exists
		$db_selected = @mysqli_select_db($link,$_SESSION['dbcon']['db']);

		if(!$db_selected)
		{
			return array('success' => FALSE, 'error' => 'Failed to select database (' . $_SESSION['dbcon']['db'] . ')' , 'progress' => $progress);
		}
		else
		{
			$progress .= 'DB Selected<br>';
			// execute the sql scripts.
			$file = $_SESSION['server']['abs'] . 'install/cmd.sql';

			if(file_exists($file))
			{
				$progress .= "Loading SQL script<br>";
				// Get the text from the file
				$SQLStatements = implode("", file($file));
				$progress .= "Parsing SQL statements<br>";
				$aStatements = explode(";", $SQLStatements);

				$ncout = count($aStatements);
				$progress .= "Preparing SQL to run ($ncout statements to run)<br>";
				$i=0;
				while($i < $ncout)
				{
					// Run the statements
					$insert = $aStatements[$i];

					//echo $i." ".$insert."<br>";

					if(trim($insert) != "")
					{
						if(!mysqli_query($link,$insert))
							return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
					}
					$i++;
				}
				$progress .= "SQL Statements complete - preparing pre-fill:<br/>";

				// Create the admin user info
				$insert = "INSERT INTO c4m_skins VALUES(1, 'default')";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Create skin, ";

				// Create the admin user info
				$hash = md5($_SESSION['password_salt'] . $_SESSION['admin']['pass']);
				$insert = "INSERT INTO c4m_admin VALUES(NULL, '" . $_SESSION['admin']['user'] . "', '" . $hash . "', 1)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "AdminUser, ";

				// Insert the temporary news item
				$insert = "INSERT INTO c4m_frontnews VALUES(NULL, 'Command Center Installed', 'Welcome to your new command center installation. To set up your features please navigate over to your admin page.', NOW())";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "WelcomeMessage, ";

				// Create the admin user info
				$insert = "INSERT INTO c4m_commandconfig VALUES(1, 1000, 'y', 'y')";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "MaxUser, ";

				// Create point values
				$insert = "INSERT INTO chess_point_value VALUES(1, 1200)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Points, ";

				// Create the admin user info
				$insert = "INSERT INTO c4m_paypalaccount VALUES(1, 'email@email.com', 'USD', 5)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Payment, ";

				// Insert the server language
				$insert = "INSERT INTO server_language VALUES(1, 'english.txt')";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress); 
				$progress .= "Language, ";

				// Insert the Initial Game Options
				$insert = "INSERT INTO admin_game_options VALUES(1, 30, 10, 5, 2, 1, 1)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "GameOptions, ";

				// Create default chessboard colors
				$insert = "INSERT INTO admin_chessboard_colors VALUES(1, '#CCFFCC', '#FFFFFF')";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress); 
				$progress .= "Boardcolors, ";

				// Create default avatar settings
				$insert = "INSERT INTO admin_avatar_settings VALUES(1, 2)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Avatars, ";

				// Create default activity settings
				$insert = "INSERT INTO activity_config VALUES(1, 'n', 10)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Activites, ";

				// Create default player chat settings
				$insert = "INSERT INTO cfg_player_chat VALUES(1, 1)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Chat, ";

				// Create default cron job settings
				$insert = "INSERT INTO cfg_cron_job VALUES(1, 2)";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "CronJob, ";

				// Create default user for open challanges
				$insert = 'INSERT INTO `player` (`player_id`, `userid`, `password`, `signup_time`, `status`, `email`) VALUES (0, "ANYONE", "20091111", 0, "F", "001@phpchess.com")';
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$insert = 'UPDATE `player` SET `player_id` = 0, `email` = "001@phpchess.com" WHERE `player_id` = 1 LIMIT 1';
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "DefaultUser, ";

				////////////////////////////////////////////////////////////////////////
				// Insert the server version
				////////////////////////////////////////////////////////////////////////
				$insert = "INSERT INTO server_version VALUES(1, 'FVP', " . $GLOBALS['nMajor'] . ", " . $GLOBALS['nMinor'].", " . $GLOBALS['nBuild'] . ")";
				if(!mysqli_query($link,$insert))
					return array('success' => FALSE, 'error' => mysqli_error($link), 'progress' => $progress);
				$progress .= "Server Version.";
				////////////////////////////////////////////////////////////////////////
				$progress .= "<br/>SQL Inserts completed";
			}
			else
			{
				return array('success' => FALSE, 'error' => 'SQL install file (' . $file . ') was not found.', 'progress' => $progress);
			}
		}
	}
	else
	{
		return array('success' => FALSE, 'error' => 'Failed to connect to MySQL server (' . $_SESSION['dbcon']['host'] . ')', 'progress' => $progress);
	}

	if($link)
	{
		mysqli_close($link);
	}

	return array('success' => TRUE, 'progress' => $progress);
}

function update_settings($data)
{
	$result = array('success' => TRUE, 'errors' => array());
	
	$link = @mysqli_connect($_SESSION['dbcon']['host'], $_SESSION['dbcon']['user'], $_SESSION['dbcon']['pass']);
	if($link)
	{
		$db_selected = @mysqli_select_db($link,$_SESSION['dbcon']['db']);
		if(!$db_selected)
		{
			return array('success' => FALSE, 'errors' => array('Failed to select database (' . $_SESSION['dbcon']['db'] . ')'));
		}
		else
		{
			$sql = "UPDATE admin_avatar_settings SET o_setting = '" . $data['allow_uploads'] . "' WHERE o_id = 1";
			if(!mysqli_query($link,$sql))
			{
				$result['success'] = FALSE;
				$result['errors'][] = 'Unable to set avatar upload setting.';
			}
			$sql = "UPDATE admin_game_options SET o_snail = " . $data['timeout_snail'] . ", o_slow = " . $data['timeout_slow'] . ", o_normal = " . $data['timeout_normal'] . ", o_short = " . $data['timeout_fast'] . ", o_blitz = " . $data['timeout_blitz'] . " WHERE o_id = 1";
			if(!mysqli_query($link,$sql))
			{
				$result['success'] = FALSE;
				$result['errors'][] = 'Unable to set default game timeouts.';
			}
			
			$sql = "UPDATE `c4m_commandconfig` SET `o_userlimit` = '" . $data['max_players'] . "' WHERE o_id = 1";
			if(!mysqli_query($link,$sql))
			{
				$result['success'] = FALSE;
				$result['errors'][] = 'Unable to set player limit. Error: ' . mysqli_error($link);
			}
			
			$sql = "UPDATE `server_language` SET `o_languagefile` = '" . $data['language'] . "' WHERE o_id = 1";
			if(!mysqli_query($link,$sql))
			{
				$result['success'] = FALSE;
				$result['errors'][] = 'Unable to set the language. Error: ' . mysqli_error($link);
			}
		}
	}
	else
	{
		$result['success'] = FALSE;
		$result['errors'] = array('Unable to connect to the database. Error: ' . mysqli_error($link) );
	}
	
	return $result;
}

function generate_config($file)
{
	
	$content = <<<qq
<?php
////////////////////////////////////////////////////////////////////////
//Configuration file for the phpChess
////////////////////////////////////////////////////////////////////////

\$conf['database_host'] = "{host}";
\$conf['database_name'] = "{db}";
\$conf['database_login'] = "{user}";
\$conf['database_pass'] = "{pass}";

\$conf['site_name'] = "{site_name}";
\$conf['site_url'] = "{site_url}";
\$conf['registration_email'] = "{email}";

\$conf['session_timeout_sec'] = {session_timeout};

\$conf['password_salt'] = "{password_salt}";

\$conf['new_user_requires_approval'] = {require_approval};

\$conf['chat_refresh_rate'] = {chat_refresh_rate};

\$conf['absolute_directory_location'] = "{abs_dir}";

\$conf['avatar_absolute_directory_location'] = "{abs_avatar_dir}";
\$conf['avatar_image_disk_size_in_bytes'] = {avatar_max_size};
\$conf['avatar_image_width'] = {avatar_img_width};
\$conf['avatar_image_height'] = {avatar_img_height};

\$conf['view_chess_games_refresh_rate'] = {game_refresh_rate};		// Number of seconds between updates when viewing games available.
\$conf['last_move_check_rate'] = {new_move_refresh_rate};			// Number of seconds between new move checks in realtime games.

?>
qq;

	$content = preg_replace('/{host}/', $_SESSION['dbcon']['host'], $content);
	$content = preg_replace('/{user}/', $_SESSION['dbcon']['user'], $content);
	$content = preg_replace('/{pass}/', $_SESSION['dbcon']['pass'], $content);
	$content = preg_replace('/{db}/', $_SESSION['dbcon']['db'], $content);
	
	$content = preg_replace('/{site_name}/', $_SESSION['server']['name'], $content);
	$content = preg_replace('/{site_url}/', $_SESSION['server']['url'], $content);
	
	$content = preg_replace('/{password_salt}/', $_SESSION['password_salt'], $content);
	// to times addslashes because the value is used as a regex, meaning the \\ is turned into \
	$content = preg_replace('/{abs_dir}/', addslashes(addslashes($_SESSION['server']['abs'])), $content);
	$content = preg_replace('/{abs_avatar_dir}/', addslashes(addslashes($_SESSION['server']['abs_avatar'])), $content);
	
	$content = preg_replace('/{email}/', $_SESSION['admin']['email'], $content);
	
	$content = preg_replace('/{session_timeout}/', pick($_SESSION['admin']['session_timeout'], 3600), $content);
	$content = preg_replace('/{require_approval}/', pick($_SESSION['admin']['require_approval'] ? 'true' : 'false', 'true'), $content);
	$content = preg_replace('/{avatar_max_size}/', pick($_SESSION['admin']['avatar_max_size'], 102400), $content);
	$content = preg_replace('/{avatar_img_width}/', pick($_SESSION['admin']['avatar_img_width'], 100), $content);
	$content = preg_replace('/{avatar_img_height}/', pick($_SESSION['admin']['avatar_img_height'], 100), $content);
	$content = preg_replace('/{chat_refresh_rate}/', pick($_SESSION['admin']['chat_refresh_rate'], 10), $content);
	$content = preg_replace('/{game_refresh_rate}/', pick($_SESSION['admin']['game_refresh_rate'], 30), $content);
	$content = preg_replace('/{new_move_refresh_rate}/', pick($_SESSION['admin']['new_move_refresh_rate'], 10), $content);

	return file_put_contents($file, $content);

}

// Check that folders/files can be written to and read.
function check_permissions()
{
	$result = array('success' => TRUE, 'errors' => array());
	$errors = array();
	
	// Can read bin directory?
	if(!is_readable('../bin/'))
		$errors[] = 'The `bin` directory cannot be read.';
		
	// Uploading avatars?
	if($_SESSION['allow_uploads'])
	{
		// Can write to /avatar/user and /avatar/user/tmp
		if(!is_writeable('../avatars/USER/'))
			$errors[] = 'The `/avatars/USER` folder cannot be written to';
		if(!is_writeable('../avatars/USER/tmp/'))
			$errors[] = 'The `/avatars/USER/tmp` folder cannot be written to';
	}
	if(count($errors))
		$result = array('success' => FALSE, 'errors' => $errors);
	
	return $result;
}

function pick($var1, $var2)
{
	return ($var1 !== NULL ? $var1 : $var2);
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