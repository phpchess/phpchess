<?php

class CSession
{
	static $session_timeout;
	static $db_link;
	static $initialised = false;
	// static $pdo_dbh;
	static $dbh;
	
	static function initialise($ConfigFile)
	{
		if($initialised) return;	// Run this only once!
		include($ConfigFile);
		$host = $conf['database_host'];
		$db = $conf['database_name'];
		$user = $conf['database_login'];
		$pass = $conf['database_pass'];
		CSession::$session_timeout = $conf['session_timeout_sec'];

		// connect to mysql and open database
		CSession::$db_link = mysql_connect($host, $user, $pass) or die("Couldn't connect to the database");
		@mysql_select_db($db, CSession::$db_link) or die("Unable to select database");
		
		// // Create a db connection using PDO. Should migrate everything over to use PDO.
		// CSession::$pdo_dbh = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
		
		// Connection using mysqli for the newer code. Should move all mysql code to mysqli!
		mysqli_report(MYSQLI_REPORT_STRICT);
		try {
			CSession::$dbh = new mysqli($host, $user, $pass, $db);
			if (CSession::$dbh->connect_errno) {
				die( "FAILED TO CONNECT TO THE DB. ERROR: " . CSession::$dbh->connect_error );
				exit();
			}
		} catch (mysqli_sql_exception $e) {
			die( "FAILED TO CONNECT TO THE DB. ERROR: " . $e->getMessage() );
		}
		
	}


	/**********************************************************************
	* check_session
	* 
	* Params: $orig_session
	*/
	static function check_session($orig_session)
	{ 
		$ret=1;
		$online_status="F";

		if($orig_session != "")
		{
			return 0;
		}

		if($orig_session != 1)
		{
			$session = base64_decode($orig_session);
			list($uniq,$player_id) = preg_split("/\|/", $session);

			$st = "SELECT session_time FROM active_sessions WHERE session LIKE '".$orig_session."%' and player_id=".$player_id." ORDER BY session_time ASC";
			$return = mysql_query($st, CSession::$db_link) or die(mysql_error());
			$num = mysql_numrows($return); 

			if($num != 0)
			{
				$time = mysql_result($return,0,0);

				if((time() - CSession::$session_timeout) > $time)
				{
					// the session has timed out, so remove it and return failure (0)
					$st = "DELETE FROM active_sessions WHERE session='".$orig_session."'";
					mysql_query($st, CSession::$db_link) or die(mysql_error());

					$ret=0;
				}
				else
				{
					// update the session time (like a touch)
					$st = "UPDATE active_sessions SET session_time=".time()." WHERE session LIKE '".$orig_session."%'";
					mysql_query($st, CSession::$db_link) or die(mysql_error());

					$online_status="N";
				}
				$st = "UPDATE player SET status='".$online_status."' WHERE player_id=".$player_id."";
				mysql_query($st, CSession::$db_link) or die(mysql_error());
			}
			else
			{
				$ret=0;
			}

		}

		return $ret;
	}


	/**********************************************************************
	 * CheckSIDTimeout
	 *
	 */
	static function CheckSIDTimeout()
	{
		// delete sessions that have timed out and not logged out or 
		// deleted by check_session() and mark the user offline 
		$st = "SELECT session FROM active_sessions WHERE session_time<=".(time() - CSession::$session_timeout)."";
		$streturn = mysql_query($st, CSession::$db_link) or die(mysql_error());
		$stnum = mysql_numrows($streturn); 

		$i=0;
		while($i < $stnum)
		{
			$orig_session = mysql_result($streturn, $i, "session");

			// the session has timed out, so remove it and return failure (0)
			$st = "DELETE FROM active_sessions WHERE session='".$orig_session."'";
			mysql_query($st, CSession::$db_link) or die(mysql_error()); 

			$i++;
		}

	}




	/**********************************************************************
	 * UpdateSIDTimeout
	 *
	 */
	static function UpdateSIDTimeout($orig_session)
	{
		$st = "UPDATE active_sessions SET session_time =".time()." WHERE session='".$orig_session."'";
		mysql_query($st, CSession::$db_link) or die(mysql_error());
	}
	
	/**********************************************************************
	 * delete_session
	 * 
	 * Params: $orig_session
	 */
	function delete_session($orig_session)
	{ 
		CSession::housekeep();

		if($orig_session == ""){
			return 0;
		}

		$session = base64_decode($orig_session);
		list($uniq, $player_id) = preg_split("/\|/", $session);
		
		$st = "DELETE FROM active_sessions WHERE session LIKE '".$orig_session."%'";
		mysql_query($st, CSession::$db_link) or die(mysql_error());

		$st = "UPDATE player SET status='F' WHERE player_id=".$player_id."";
		mysql_query($st, CSession::$db_link) or die(mysql_error());

		return 1;
	}
	
	
	
	/**********************************************************************
	 * housekeep
	 * runs various house-keeping functions to keep the tables
	 * Params: 
	 */
	static function housekeep()
	{
		// delete sessions that have timed out and not logged out or 
		// deleted by check_session() and mark the user offline 
		$st = "SELECT session FROM active_sessions WHERE session_time<=".(time() - CSession::$session_timeout)."";
		$streturn = mysql_query($st, CSession::$db_link) or die(mysql_error());
		$stnum = mysql_numrows($streturn); 

		$i=0;
		while($i < $stnum)
		{
			CSession::check_session(mysql_result($streturn,0,0)); 
			$i++;
		}
	}
	
	/**********************************************************************
	 * CheckLogin
	 *
	 */
	function CheckLogin($SID)
	{
		$bIsLoggedIn = false;

		$query = "SELECT * FROM active_sessions WHERE session Like '".$SID."%'";
		$return = mysql_query($query, CSession::$db_link) or die(mysql_error());
		$num = mysql_numrows($return);

		if($num != 0){
			$bIsLoggedIn = true;
		}

		return $bIsLoggedIn;
	}
  
}


?>