<?
	$sqlquery = "SELECT * FROM game WHERE (w_player_id = '".$_SESSION["id"]."' OR b_player_id = '".$_SESSION["id"]."') AND completion_status = 'C' AND status != 'W'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
  	header("var_games_no: ".mysql_numrows($result));

  	$i = 0;
  	while ($challenge = mysql_fetch_array($result))
  	{
  		if ($challenge["w_player_id"] == $_SESSION["id"])
  			$sqlquery = "SELECT userid FROM player WHERE player_id = '".$challenge["b_player_id"]."'";
  		else
  			$sqlquery = "SELECT userid FROM player WHERE player_id = '".$challenge["w_player_id"]."'";
      	$result1 = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

      	$player = mysql_fetch_array($result1);

      	header("var_game_".$i."_id: ".$challenge["game_id"]);
      	header("var_player_".$i."_name: ".$player["userid"]);
	  	if ($challenge["w_player_id"] == $_SESSION["id"])
			header("var_".$i."_color: w");
		else
			header("var_".$i."_color: b");
		if (isset($challenge["next_move"]))
			header("var_".$i."_move: ".$challenge["next_move"]);
		else
			header("var_".$i."_move: w");
		$i++;
  	}
?>