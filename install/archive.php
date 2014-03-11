<?php

// Code adapted from example found here: http://tournasdimitrios1.wordpress.com/2012/12/09/a-php-script-to-backup-mysql-databases-for-shared-hosted-websites-3/

function make_db_backup($backup_dir, $conn_details)
{
	$archiveName = 'phpchess_backup.sql' ;
	// Set execution time limit
	if(function_exists('max_execution_time')) {
		if( ini_get('max_execution_time') > 0 ) 	set_time_limit(0) ;
	}

	return get_db_dump_sql($conn_details);
	
	/*
	 Create backupDir (if it's not yet created ) , with proper permissions .
	 Create a ".htaccess" file to restrict web-access
	*/
	// if (!file_exists($backup_dir)) mkdir($backup_dir , 0700) ;
	// if (!is_writable($backup_dir)) chmod($backup_dir , 0700) ;
	// // Create an ".htaccess" file , it will restrict direct access to the backup-directory .
	// $content = 'deny from all' ;
	// $file = new SplFileObject($backup_dir . '/.htaccess', "w") ;
	// $written = $file->fwrite($content) ;
	// // Verify that ".htaccess" is written , if not , die the script
	// if($written <13)
		// return array('success' => FALSE, 'error' => 'Could not create a ".htaccess" file , Backup task canceled');

	// return createNewArchive($archiveName, $backup_dir, $conn_details) ;
}

// Function createNewArchive
function createNewArchive($archiveName, $backup_dir, $conn_details)
{
	$mysqli = new mysqli($conn_details['host'] , $conn_details['user'] , $conn_details['pass'] , $conn_details['db']) ;
	if (mysqli_connect_errno())
	{
		return array('success' => FALSE, 'error' => 'Connect failed: ' . mysqli_connect_error());
	}
	// Introduction information
	$return = "--\n";
	$return .= "-- A Mysql Backup System \n";
	$return .= "--\n";
	$return .= '-- Export created: ' . date("Y/m/d") . ' on ' . date("h:i") . "\n\n\n";
	$return .= "--\n";
	$return .= "-- Database : " . $conn_details['db'] . "\n";
	$return .= "--\n";
	$return .= "-- --------------------------------------------------\n";
	$return .= "-- ---------------------------------------------------\n";
	$return .= 'SET AUTOCOMMIT = 0 ;' ."\n" ;
	$return .= 'SET FOREIGN_KEY_CHECKS=0 ;' ."\n" ;
	$tables = array() ;
	// Exploring what tables this database has
	$result = $mysqli->query('SHOW TABLES' ) ;
	// Cycle through "$result" and put content into an array
	while ($row = $result->fetch_row())
	{
		$tables[] = $row[0] ;
	}
	// Cycle through each  table
	foreach($tables as $table)
	{
		// Get content of each table
		$result = $mysqli->query('SELECT * FROM '. $table) ;
		// Get number of fields (columns) of each table
		$num_fields = $mysqli->field_count  ;
		// Add table information
		$return .= "--\n" ;
		$return .= '-- Tabel structure for table `' . $table . '`' . "\n" ;
		$return .= "--\n" ;
		$return.= 'DROP TABLE  IF EXISTS `'.$table.'`;' . "\n" ;
		// Get the table-shema
		$shema = $mysqli->query('SHOW CREATE TABLE '.$table) ;
		// Extract table shema
		$tableshema = $shema->fetch_row() ;
		// Append table-shema into code
		$return.= $tableshema[1].";" . "\n\n" ;
		// Cycle through each table-row
		while($rowdata = $result->fetch_row())
		{
			// Prepare code that will insert data into table
			$return .= 'INSERT INTO `'.$table .'`  VALUES ( '  ;
			// Extract data of each row
			for($i=0; $i<$num_fields; $i++)
			{
				$return .= '"'.$rowdata[$i] . "\"," ;
			}
			// Let's remove the last comma
			$return = substr("$return", 0, -1) ;
			$return .= ");" ."\n" ;
		}
		$return .= "\n\n" ;
	}
	// Close the connection
	$mysqli->close() ;
	$return .= 'SET FOREIGN_KEY_CHECKS = 1 ; '  . "\n" ;
	$return .= 'COMMIT ; '  . "\n" ;
	$return .= 'SET AUTOCOMMIT = 1 ; ' . "\n"  ;
	//$file = file_put_contents($archiveName , $return) ;
	$zip = new ZipArchive() ;
	$resOpen = $zip->open($backup_dir . '/' .$archiveName.".zip" , ZIPARCHIVE::CREATE) ;
	if( $resOpen ){
		$zip->addFromString( $archiveName , "$return" ) ;
	}
	$zip->close() ;

	return array('success' => TRUE, 'error' => '');
}

// Returns the database dump SQL.
function get_db_dump_sql($conn_details)
{
	$mysqli = new mysqli($conn_details['host'] , $conn_details['user'] , $conn_details['pass'] , $conn_details['db']) ;
	if (mysqli_connect_errno())
	{
		return array('success' => FALSE, 'error' => 'Connect failed: ' . mysqli_connect_error());
	}
	// Introduction information
	$return = "--\n";
	$return .= "-- A Mysql Backup System \n";
	$return .= "--\n";
	$return .= '-- Export created: ' . date("Y/m/d") . ' on ' . date("h:i") . "\n\n\n";
	$return .= "--\n";
	$return .= "-- Database : " . $conn_details['db'] . "\n";
	$return .= "--\n";
	$return .= "-- --------------------------------------------------\n";
	$return .= "-- ---------------------------------------------------\n";
	$return .= 'SET AUTOCOMMIT = 0 ;' ."\n" ;
	$return .= 'SET FOREIGN_KEY_CHECKS=0 ;' ."\n" ;
	$tables = array() ;
	// Exploring what tables this database has
	$result = $mysqli->query('SHOW TABLES' ) ;
	// Cycle through "$result" and put content into an array
	while ($row = $result->fetch_row())
	{
		$tables[] = $row[0] ;
	}
	// Cycle through each  table
	foreach($tables as $table)
	{
		// Get content of each table
		$result = $mysqli->query('SELECT * FROM '. $table) ;
		// Get number of fields (columns) of each table
		$num_fields = $mysqli->field_count  ;
		// Add table information
		$return .= "--\n" ;
		$return .= '-- Tabel structure for table `' . $table . '`' . "\n" ;
		$return .= "--\n" ;
		$return.= 'DROP TABLE  IF EXISTS `'.$table.'`;' . "\n" ;
		// Get the table-shema
		$shema = $mysqli->query('SHOW CREATE TABLE '.$table) ;
		// Extract table shema
		$tableshema = $shema->fetch_row() ;
		// Append table-shema into code
		$return.= $tableshema[1].";" . "\n\n" ;
		// Cycle through each table-row
		while($rowdata = $result->fetch_row())
		{
			// Prepare code that will insert data into table
			$return .= 'INSERT INTO `'.$table .'`  VALUES ( '  ;
			// Extract data of each row
			for($i=0; $i<$num_fields; $i++)
			{
				$return .= '"'.$rowdata[$i] . "\"," ;
			}
			// Let's remove the last comma
			$return = substr("$return", 0, -1) ;
			$return .= ");" ."\n" ;
		}
		$return .= "\n\n" ;
	}
	// Close the connection
	$mysqli->close() ;
	$return .= 'SET FOREIGN_KEY_CHECKS = 1 ; '  . "\n" ;
	$return .= 'COMMIT ; '  . "\n" ;
	$return .= 'SET AUTOCOMMIT = 1 ; ' . "\n"  ;

	return array('success' => TRUE, 'error' => '', 'sql' => $return);
} 

?>