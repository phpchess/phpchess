<?
	if (!isset($_SERVER["HTTP_VAR_GAME_ID"]))
		$game_id = $_SERVER["HTTP_VAR_GAME_ID"];
	else
		$game_id = "";

	$sqlquery = "SELECT * FROM game WHERE game_id ='".$game_id."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	if (!mysqli_num_rows($result))
	{
		header("var_error: failed");
		header("var_status: none");
	} else
	{
		header("var_error: ok");
		$game = mysqli_fetch_array($result);

		if (($game["status"] == 'W') && ($game["completion_status"] == 'I'))
			header("var_status: awaiting");
		else
			header("var_status: accepted");
	}
?>