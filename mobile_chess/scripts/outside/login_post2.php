<?
	echo $_GET["username"];
	$sqlquery = "SELECT player_id FROM player WHERE userid = '".trim($_POST["username"])."' AND password = '".trim($_POST["password"])."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
	echo $result; 
	if (mysql_numrows($result))
	{
		session_register("id");
		$player = mysql_fetch_array($result);
		echo '<cr>';
		echo $player;
		$_SESSION["id"] = $player["player_id"];

		$sqlquery = "INSERT INTO active_sessions SET session = '".session_id()."', player_id = '".$_SESSION["id"]."', session_time = '".time()."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

		echo("var_session: ".session_id());
		echo("var_login_result: OK");
	} else
	{
		echo("var_login_result: Failed");
	}
?>                        