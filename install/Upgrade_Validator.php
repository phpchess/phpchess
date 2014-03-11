<?php


class Upgrade_Validator
{
	private $dbh;
	private $UV;

	public function Upgrade_Validator($dbh)
	{
		$this->dbh = $dbh;
	}
	
	public function validate($version)
	{
		if($version == '4.2.0')
			$this->UV = new Upgrade_Validator_4_2_0();
			
		$this->UV->dbh = $this->dbh;
		$this->UV->validate();
		return $this->UV->correct();
	}
	
}

class Upgrade_Validator_4_2_0
{
	public $dbh;
	private $version;
	
	private $list_required_records;			// List of tables with records that are required.
	private $list_default_records;			// List of tables with records to apply if the tables are empty.
	
	private $fix_missing_records;			// Contains from $list_required_records entries that have to be inserted.
	private $fix_empty_tables;				// Contains from $list_default_records entries that have to be inserted.
	private $correct_version;				// Boolean, indicates if the server version stored is correct.
	private $player_anyone_exists;			// Indicates if player 'ANYONE' exists.
	
	public function Upgrade_Validator_4_2_0()
	{
		include('../bin/config.php');
		$salt = $conf['password_salt'];
	
		$this->version = '4.2.0';
		// Entries listing records for tables that MUST exist. These records are inserted if they
		// are found to be missing.
		$this->list_required_records = array(
			
		);
		
		// Entries listing records to insert for tables that are empty.
$msg = <<<qq

This site is your home for chess playing against like minded people. Simply sign up and play chess, chat, and have fun. When you register it may take a bit to be accepted. You can choose your board colors, play in real-time, or in correspondence mode. You can study your past games or play against an AI opponent. Have fun and keep it clean.

PS: We are now running version 4.2 of phpChess (www.phpchess.com).
qq;
		$this->list_default_records = array(
			'admin_avatar_settings' => array(
				'o_id' => 1, 'o_setting' => 2
			),
			'admin_game_options' => array(
				'o_id' => 1, 'o_snail' => 30, 'o_slow' => 10, 'o_normal' => 5, 'o_short' => 2, 'o_blitz' => 1
			),
			'c4m_admin' => array(
				'a_id' => 1, 'a_username' => 'admin', 'a_password' => md5($salt . 'pass')
			),
			'c4m_commandconfig' => array(
				'o_id' => 1, 'o_userlimit' => 1000, 'o_enabletournament' => 'y', 'o_enableplayerimport' => 'y'
			),
			'c4m_frontnews' => array(
				'f_id' => 1, 'f_title' => 'Installed phpChess 4.2.0', 'f_msg' => $msg, 'f_date' => Date('Y-m-d H:i:s')
			),
			'c4m_servermessage' => array(
			 //c4m_servermessage ?  put upgrade message here?
			),
			'c4m_skins' => array(
				'id' => 1, 'name' => 'default'
			),
			'chess_point_value' => array(
				'o_id' => 1, 'o_points' => 1200
			),
			'server_language' => array(
				'o_id' => 1, 'o_languagefile' => 'english.txt'
			)
		);
		
	}
	
	// Looks for problems using the defined lists in the constructor. Stores list of things to fix
	// in class variables.
	public function validate()
	{
		$result = array();

		$result = $this->test_player_anyone_exists();
		$this->player_anyone_exists = $result['exists'];
		$result = $this->test_records_exist($this->list_required_records);
		$this->fix_missing_records = $result['missing'];
		
		$result = $this->test_tables_not_empty($this->list_default_records);
		$this->fix_empty_tables = $result['empty'];
		$result = $this->test_correct_version($this->version);
		$this->correct_version = $result['correct_version'];
		return $result;
	}
	
	// Processes various lists to correct problems found in validate().
	public function correct()
	{
		$result1 = $this->add_records($this->fix_missing_records);
		$result2 = $this->add_records($this->fix_empty_tables);
		if(!$this->correct_version)
			$result3 = $this->fix_server_version($this->version);
		else
			$result3 = array('errors' => array());
		if(!$this->player_anyone_exists)
			$result4 = $this->fix_player_anyone();
		else
			$result4 = array('errors' => array());
		$result5 = $this->fix_games_with_no_timeout();
		$result6 = $this->fix_language_filename();
		
		$errors = array_merge($result1['errors'], $result2['errors'], $result3['errors'], $result4['errors'], $result5['errors'], $result6['errors']);
		return array('errors' => $errors);
	}
	
	// Tests if specific records exist in tables.
	private function test_records_exist($matches)
	{
		$errors = array();
		$result = array('success' => TRUE, 'missing' => array());
		foreach($matches as $table => $records)
		{
			foreach($records as $record)
			{
				if(empty($record)) continue;
				$q = "SELECT count(*) as cnt FROM $table WHERE ";
				$parts = array();
				$values = array();
				$bindtypes = '';
				foreach(array_keys($record) as $key)
				{
					 $parts[] = "$key = ?";
					 $values[] = $record[$key];
					 $bindtypes .= 's';
				}
				$q .= implode(' AND ', $parts);
				//echo "full query: $q<br/>";
				try 
				{
					$stmt = $this->dbh->prepare($q);
					call_user_func_array(array($stmt, 'bind_param'), array_merge(array($bindtypes), $values));
					if (!$stmt->execute())
					{
						$errors[] = "Error running query ($q) with values (" . implode(', ', $values) . "): " . $this->dbh->error;
					}
					else
					{
						$results = $stmt->get_result();
						$row = $results->fetch_assoc();
						if($row['cnt'] == 0)
						{
							if(!isset($result['missing'][$table]))
								$result['missing'][$table] = array();
							$result['missing'][$table][] = $record;
						}
					}
					
				}
				catch(mysqli_sql_exception $e)
				{
					$errors[] = "ERROR: " . $e->getMessage();
					$result['success'] = FALSE;
				}
			}
		}
		
		$result['errors'] = $errors;
		
		return $result;
	}
	
	// Tests if tables are not empty.
	private function test_tables_not_empty($tables)
	{
		$errors = array();
		$result = array('success' => TRUE, 'empty' => array());
		foreach($tables as $table => $record)
		{
			if(empty($record)) continue;
			$q = "SELECT count(*) as cnt FROM $table";
			try 
			{
				$results = $this->dbh->query($q);
				if (!$results)
				{
					$errors[] = "Error running query ($q): " . $this->dbh->error;
				}
				else
				{
					$row = $results->fetch_assoc();
					if($row['cnt'] == 0)
					{
						if(!isset($result['empty'][$table]))
							$result['empty'][$table] = array();
						$result['empty'][$table][] = $record;
					}
				}
				
			}
			catch(mysqli_sql_exception $e)
			{
				$errors[] = "ERROR: " . $e->getMessage();
				$result['success'] = FALSE;
			}
		}
		$result['errors'] = $errors;
		return $result;
	}
	
	// Tests if the correct version is stored.
	private function test_correct_version($version)
	{
		$errors = array();
		$result = array('success' => TRUE, 'correct_version' => FALSE);
		$parts = preg_split('/\./', $version);
		$q = "SELECT `o_major`, `o_minor`, `o_build` FROM `server_version` WHERE `o_id` = 1";
		try 
		{
			$results = $this->dbh->query($q);
			if(!$results)
			{
				$errors[] = "Error running query ($q): " . $this->error;
			}
			else
			{
				$row = $results->fetch_assoc();
				if($results->num_rows == 0)		// No version record exists
				{
					$errors[] = "No version record exists";
				}
				else
				{
					if($row['o_major'] == $parts[0] && $row['o_minor'] == $parts[1] && $row['o_build'] == $parts[2])
					{
						$result['correct_version'] = TRUE;
					}
				}
			}
		}
		catch(mysqli_sql_exception $e)
		{
			$errors[] = "ERROR: " . $e->getMessage();
			$result['success'] = FALSE;
		}
		
		$result['errors'] = $errors;
		return $result;
	}
	
	
	private function add_records($entries)
	{
		$result = array('success' => TRUE, 'errors' => array());
		$errors = array();
		foreach($entries as $table => $records)
		{
			foreach($records as $record)
			{
				$fields = implode('`, `', array_keys($record));
				$values = array();
				foreach(array_values($record) as $value)
				{
					$values[] = '"' . addslashes($value) . '"';
				}
				$values = implode(', ', $values);
				$q = "INSERT INTO `$table` (`$fields`) VALUES ($values)";
				//echo "fix: $q";
				try 
				{
					if (!$this->dbh->query($q))
					{
						$errors[] = "Error running query ($q): " . $this->error;
					}
				}
				catch(mysqli_sql_exception $e)
				{
					$errors[] = "ERROR: " . $e->getMessage();
					$result['success'] = FALSE;
				}
			}
		}
		
		$result['errors'] = $errors;
		return $result;
	}
	
	// Sets the server version to be correct.
	private function fix_server_version($version)
	{
		$errors = array();
		$result = array('success' => TRUE);
		try 
		{
			$q = "SELECT * FROM `server_version` WHERE `o_id` = 1";
			$results = $this->dbh->query($q);
			if (!$results)
			{
				$errors[] = "Error running query: " . $this->dbh->error;
			}
			else
			{
				$parts = preg_split('/\./', $version);
				
				if($results->num_rows == 0)		// No version record exists
				{
					$q = "INSERT INTO `server_version` VALUES (1, \"FVP\", " . $parts[0] . ", " . $parts[1] . ", " . $parts[2] . ")";
				}
				else		// Replace existing values
				{
					$q = "UPDATE `server_version` SET `o_major` = " . $parts[0] . ", `o_minor` = " . $parts[1] . ", `o_build` = " . $parts[2] . " WHERE `o_id` = 1";
				}
				if (!$this->dbh->query($q))
				{
					$errors[] = "Error running query ($q): " . $this->error;
					$result['success'] = FALSE;
				}
			}
		}
		catch(mysqli_sql_exception $e)
		{
			$errors[] = "ERROR: " . $e->getMessage();
			$result['success'] = FALSE;
		}

		$result['errors'] = $errors;
		return $result;
	}
	
	private function test_player_anyone_exists()
	{
		$result = array('success' => TRUE, 'exists' => TRUE, 'error' => NULL);
		$q = "SELECT count(*) as cnt FROM `player` WHERE player_id = '0'";
		try 
		{
			$results = $this->dbh->query($q);
			if (!$results)
			{
				$result['error'] = "Error running query ($q): " . $this->dbh->error;
			}
			else
			{
				$row = $results->fetch_assoc();
				if($row['cnt'] == 0)
				{
					$result['exists'] = FALSE;
				}
			}
			
		}
		catch(mysqli_sql_exception $e)
		{
			$result['error'] = "ERROR: " . $e->getMessage();
			$result['success'] = FALSE;
		}
		return $result;
	}
	private function fix_player_anyone()
	{
		// After insert, need to change the inserted record's id to 0 because mysql ignores the id value 
		// provided on the insert (probably because it is 0).
		$result = array('success' => TRUE, 'errors' => array());
		try 
		{
			$q = 'INSERT INTO `player` (`player_id`, `userid`, `password`, `signup_time`, `status`, `email`) VALUES (0, "ANYONE", "20091111", 0, "F", "001@phpchess.com")';
			if (!$this->dbh->query($q))
			{
				$result['errors'][] = "Error running query ($q): " . $this->dbh->error;
				$result['success'] = FALSE;
			}
			else
			{
				$id = $this->dbh->insert_id;
				$q = 'UPDATE `player` SET `player_id` = 0, `email` = "001@phpchess.com" WHERE `player_id` = ' . $id. ' LIMIT 1';
				if (!$this->dbh->query($q))
				{
					$result['errors'][] = "Error running query ($q): " . $this->dbh->error;
					$result['success'] = FALSE;
				}
			}
		}
		catch(mysqli_sql_exception $e)
		{
			$result['errors'][] = "ERROR: " . $e->getMessage();
			$result['success'] = FALSE;
		}
		return $result;
	}
	
	// Fixes games with no records in the cfm_game_options table which specifies the game timeout.
	// Old games do not appear in this table and will always show up if they are not finished. To 
	// fix this, these games are added to the cfm_game_options table.
	private function fix_games_with_no_timeout()
	{
		$result = array('errors' => array(), 'success' => TRUE);
		try 
		{
			$games = array();
			$q = "SELECT * FROM game WHERE game_id NOT IN (SELECT o_gameid FROM cfm_game_options) AND completion_status = 'I'";
			$results = $this->dbh->query($q);
			if (!$results)
			{
				$result['errors'][] = "Error running query ($q): " . $this->dbh->error;
				$result['success'] = FALSE;
			}
			else
			{
				while($row = $results->fetch_assoc())
				{
					$games[] = $row['game_id'];
				}
				$q = 'INSERT INTO cfm_game_options (`o_gameid`, `o_rating`, `o_timetype`) VALUES (?, "gunrated", "C-Normal")';
				$stmt = $this->dbh->prepare($q);
				foreach($games as $game)
				{
					$stmt->bind_param('s', $game);
					if(!$stmt->execute())
					{
						$result['errors'][] = "Failed to add game $game into table cfm_game_options. Error: " . $this->dbh->error;
						$result['success'] = FALSE;
					}
				}
			}
		}
		catch(mysqli_sql_exception $e)
		{
			$result['errors'][] = "ERROR: " . $e->getMessage();
			$result['success'] = FALSE;
		}
	
		return $result;
	}
	
	// If the value for the language file being used is english.1.0.txt, then rename it to 
	// english.txt.
	private function fix_language_filename()
	{
		$result = array('success' => TRUE, 'errors' => array());
		try {
			if(!$this->dbh->query("UPDATE `server_language` SET o_languagefile = 'english.txt' WHERE o_languagefile = 'english.1.0.txt'"))
			{
				$result = array('success' => FALSE, 'errors' => array($this->dbh->error));
			}
		} catch (mysqli_sql_exception $e) {
			$result['errors'][] = $e->getMessage();
		}
		return $result;
	}
		
}


?>