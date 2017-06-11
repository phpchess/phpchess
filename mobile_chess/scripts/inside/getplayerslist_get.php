<?
	$sqlquery = "SELECT * FROM player ORDER BY userid ASC";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_players_no: ".mysqli_num_rows($result));
	$i = -1;
	while ($user = mysqli_fetch_array($result))
	{
		$i++;
		header("var_player_".$i."_id: ".$user["player_id"]);
		header("var_player_".$i."_name: ".$user["userid"]);
	}
?>