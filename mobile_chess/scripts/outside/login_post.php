<?
	$_POST["username"] = $_SERVER["HTTP_VAR_USERNAME"];
    $_POST["password"] = $_SERVER["HTTP_VAR_PASSWORD"];

	$sqlquery = "SELECT player_id FROM player WHERE userid = '".trim($_POST["username"])."' AND password = '".trim($_POST["password"])."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	if (mysqli_num_rows($result))
	{
		session_register("id");
		$player = mysqli_fetch_array($result);
		$_SESSION["id"] = $player["player_id"];

		$sqlquery = "INSERT INTO active_sessions SET session = '".session_id()."', player_id = '".$_SESSION["id"]."', session_time = '".time()."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

		header("var_session: ".session_id());
		header("var_login_result: OK");
	} else
	{
		header("var_login_result: Failed");
	}
?>                                                                        .