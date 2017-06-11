<?
	$sqlquery = "SELECT DISTINCT player.player_id as id, player.userid as username FROM player, active_sessions WHERE player.player_id = active_sessions.player_id AND ".time()." - active_sessions.session_time < ".$server["timelimit"]." ORDER BY player.userid ASC";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	if (!mysqli_num_rows($result))
	{
		header("var_online_users: 0");
	} else
	{
		$tmp = mysqli_num_rows($result).", ";

		while ($user = mysqli_fetch_array($result))
		{
			$tmp .= $user["id"].", ".$user["username"].", ";
		}

		header("var_online_users: ".$tmp);
	}
	exit;
?>