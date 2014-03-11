<?
	$sqlquery = "SELECT * FROM game WHERE game_id = '".$_SERVER["HTTP_VAR_GAME_ID"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
	$game = mysql_fetch_array($result);

	$sqlquery  = "UPDATE game SET status = 'C', completion_status = ";
	switch ($_SERVER["HTTP_VAR_STATUS"])
	{
		case "win" :
		{
			if ($game["w_player_id"] == $_SESSION["id"])
				$sqlquery .= "'W'";
			else
				$sqlquery .= "'B'";
		}; break;
		case "lose" :
		{
			if ($game["w_player_id"] == $_SESSION["id"])
				$sqlquery .= "'B'";
			else
				$sqlquery .= "'W'";
		}; break;
		default :
		{
			$sqlquery .= "'D'";
		}
	}

	$sqlquery .= " WHERE game_id = '".$game["game_id"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("status: ok");
	exit;
?>