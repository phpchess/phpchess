<?
	settype($_SERVER["HTTP_VAR_MSG_TARGET"], "integer");
	$sqlquery = "SELECT userid FROM player WHERE player_id = '".$_SESSION["id"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
	if (mysqli_num_rows($result))
	{
		$player = mysqli_fetch_array($result);

    	$message = "C000000000".$player["userid"]."-".$_SERVER["HTTP_VAR_MSG_CONTENT"];

		$sqlquery = "INSERT INTO message_queue SET player_id = '".$_SERVER["HTTP_VAR_MSG_TARGET"]."', message = '".$message."', posted = '".time()."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

		header("var_ok: Message sent successfully.");
		exit;
	} else
	{
		header("var_ok: Failed to send message.");
	}
?>