<?php

class DBCompare
{
	private $dbh = NULL;		// Database handle

	// Initialises class and database connection using the provided connection settings.
	// $config: array containing:
	//          hostname, username and password
	function __construct($config)
	{
		$host = $config['hostname'];
		$user = $config['username'];
		$pass = $config['password'];
		$db = $config['database'];

		try {
			$this->dbh = new mysqli($host, $user, $pass, $db);
			if ($mysqli->connect_errno) {
				echo "FAILED TO CONNECT TO THE DB. ERROR: " . $mysqli->connect_error;
				exit();
			}
		} catch (mysqli_sql_exception $e) {
			echo "FAILED TO CONNECT TO THE DB. ERROR: " . $e->getMessage();
			exit();
		}
		
	}
	
	/*
		- read db structure
		- save db structure to a file
		- read db structure from a file
		- compare db structure with that in a file to get changes required
		- apply changes to db
	*/
	
	function get_db_structure($db)
	{
		// foreach table in db get table structure
		$query = "SHOW TABLES FROM `$db`";
		$results = $this->dbh->query($query);
		$tables = array();
		$structure = array();
		if($results)
		{
			while($row = $results->fetch_array(MYSQLI_NUM))
			{
				$tables[] = $row[0];
			}
		}
		// $tables = array('game', 'player');
		//$tables = array('test');
		foreach($tables as $table)
		{
			$structure[$table] = $this->get_table_structure($db, $table);
		}
		//var_dump($structure);
		return $structure;
	}
	
	function get_table_structure($db, $table)
	{
		// get columns
		// get constraints
		// get relationships
		$structure['columns'] = $this->get_columns($db, $table);
		$structure['constraints'] = $this->get_constraints($db, $table);
		return $structure;
	}
	
	function get_columns($db, $table)
	{
		$query = "SHOW FULL COLUMNS FROM `$db`.`$table`";
		$results = $this->dbh->query($query);
		$columns = array();
		if($results)
		{
			while($row = $results->fetch_assoc())
			{
				$column = array();
				$column['type'] = $row['Type'];
				//$column['collation'] = $row['Collation'];
				$column['null'] = $row['Null'];
				//$column['key'] = $row['Key'];
				$column['default'] = $row['Default'];
				$column['extra'] = $row['Extra'];
				// $columns['privileges'] = $row['Priviliges'];
				$column['comment'] = $row['Comment'];
				$columns[$row['Field']] = $column;
			}
		}

		return $columns;
	}
	
	function get_constraints($db, $table)
	{
		$query = "SHOW INDEX FROM `$db`.`$table`";
		$results = $this->dbh->query($query);
		$constraints = array();
		if($results)
		{
			while($row = $results->fetch_assoc())
			{
				$constraint = array();
				$constraint['non_unique'] = $row['Non_unique'];
				// $constraint['seq_in_index'] = $row['Seq_in_index'];
				$constraint['column_name'] = $row['Column_name'];
				//$constraint['collation'] = $row['Collation'];
				// $constraint['cardinality'] = $row['Cardinality'];
				// $constraint['sub_part'] = $row['Sub_part'];
				// $constraint['packed'] = $row['Packed'];
				//$constraint['null'] = $row['Null'];
				$constraint['index_type'] = $row['Index_type'];
				//$constraint['comment'] = $row['Comment'];
				if(!isset($constraints[$row['Key_name']]))
					$constraints[$row['Key_name']] = array();
				$constraints[$row['Key_name']][] = $constraint;
			}
		}

		return $constraints;
	}
	
	
	function get_relationships($table)
	{
		// TODO
	}
	
	// Compares two database structures and returns an array of all the changes required.
	// $old - the old structure
	// $new - the new structure
	// Returns array listing all changes required to turn the old structure into the new.
	function compare_structures($old, $new)
	{
		/* 
		Renamed tables and columns cannot be detected. As such if a table or column 
		no longer exists it should be kept so the data is not lost. Manual intervention
		is required to change these. Constraints can be renamed because it will just
		result in a remove and add with no problem.
		
		ORDER:
		
		remove:
		 - relationship ~ in case the fk or target column/table no longer exist
		 - table ~ ignore removed table.
		 - constraint ~ can remove constraints
		 - column ~ set to allow NULL values and remove unique/primary key constraints if they exist. column can stay but should not interfer with adding new records.
		
		modify:
		 - table ~ nothing to modify in tables
		 - constraint ~ can modify all except the key name
		 - column ~ can modify all except the name
		 
		add:
		 - table ~ just the table. Columns and constraints added afterwards.
		 - column ~ add the column except for constraints.
		 - constraint ~ add the constraint
		 - relationship ~ fk and target column/table should now exist
		
		
		'Change' structure:
		
		table
			action = add, options = ...
			columns
				col1
					action = add, options = ...
				col2
					action = remove
			constraints
				cnstrnt1
					add
		table
			columns
				col3
					action = modify, options = ...
		
		*/
		$changes = array();
		// First compare whats in the new structure and not in the old. If the new table doesn't exist,
		// add it. Then compare old and new table details. Only include tables that have changes.
		foreach($new as $table => $opts)
		{
			$include = FALSE;
			$change = array('action' => NULL, 'columns' => array(), 'constraints' => array());
			$old_opts = array('columns' => array(), 'constraints' => array());
			if(!isset($old[$table]))
			{
				$change['action'] = 'add';
				$include = TRUE;
			}
			else
				$old_opts = $old[$table];
			
			$change['columns'] = $this->compare_column_structure($old_opts['columns'], $opts['columns']);
			$change['constraints'] = $this->compare_constraints_structure($old_opts['constraints'], $opts['constraints']);
			if(!empty($change['columns'])) $include = TRUE;
			if(!empty($change['constraints'])) $include = TRUE;
			if($include)
				$changes[$table] = $change;
		}
		// Now check what tables are in the old structure and not in the new.
		foreach($old as $table => $opts)
		{
			if(!isset($new[$table]))
				$changes[$table]['action'] = 'remove';
		}
		
		//var_dump($changes);
		return $changes;
	}
	
	
	function compare_column_structure($old, $new)
	{
		$changes = array();
		// First compare whats in the new structure and not in the old. If the new column doesn't exist
		// then add it. Then compare the old and new column properties.
		foreach($new as $column => $opts)
		{
			if(!isset($old[$column]))
			{
				$changes[$column] = array('action' => 'add', 'options' => $opts);
				continue;
			}
			
			// Now check if column properties have changed. If so the column is marked as modified.
			$old_opts = $old[$column];
			$is_different = FALSE;
			$differences = array();
			foreach($opts as $key => $value)
			{
				if($old_opts[$key] != $value)
				{
					$is_different = TRUE;
					$differences[$key] = array('new' => $value, 'old' => $old_opts[$key]);
				}
			}
			if($is_different)
			{
				$changes[$column]['action'] = 'modify';
				$changes[$column]['options'] = $opts;
				$changes[$column]['differences'] = $differences;		// <-- storing this for people to see what has actually changed
			}
		}
		// Now compare whats in the old that is not in the new.
		foreach($old as $column => $opts)
		{
			if(!isset($new[$column]))
			{
				$changes[$column]['action'] = 'remove';
			}
		}
		
		return $changes;
	}
	
	function compare_constraints_structure($old, $new)
	{
		$changes = array();
		
		// First compare whats in the new structure and not in the old. If the new constraint does
		// exist then add it. Then compare the old and new constraint elements to see if there are
		// any changes.
		foreach($new as $constraint => $opts)
		{
			if(!isset($old[$constraint]))
			{
				$changes[$constraint] = array('action' => 'add', 'options' => $opts);
				continue;
			}
			
			// A constraint can have multiple elements, each specifying a column included in
			// the constraint. Compare each element by index and any difference will mean
			// this constraint has to be modified.
			$old_elements = $old[$constraint];
			if(count($opts) != count($old_elements))
			{
				$changes[$constraint] = array('action' => 'modify', 'options' => $opts);
			}
			else
			{
				for($i = 0; $i < count($opts); $i++)
				{
					foreach($opts[$i] as $key => $value)
					{
						if($value != $old_elements[$i][$key])
						{
							$changes[$constraint] = array('action' => 'modify', 'options' => $opts);
							break;
						}
					}
				}
			}
		}
		// Now compare whats in the old and not in the new.
		foreach($old as $constraint => $opts)
		{
			if(!isset($new[$constraint]))
			{
				$changes[$constraint]['action'] = 'remove';
			}
		}
		
		return $changes;
	}

}



?>