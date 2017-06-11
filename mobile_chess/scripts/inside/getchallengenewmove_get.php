<?

	settype($_SERVER["HTTP_VAR_NOMOVE"], "integer");
	$moves = $_SERVER["HTTP_VAR_NOMOVE"];

	$sqlquery = "SELECT COUNT(move_id) FROM move_history WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error(3));
	$move = mysqli_fetch_array($result);

	if ($moves != $move["COUNT(move_id)"])
	{
		$sqlquery = "SELECT * FROM move_history WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."' ORDER BY time DESC LIMIT 1";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error(3));
		$move = mysqli_fetch_array($result);
		
		if (strstr($move["move"], "O-O w")){
			header("var_move: e1,g1");
		}else
		{
			if (strstr($move["move"], "O-O b")){
				header("var_move: e8,c8");
			}else
			{
				if (strstr($move["move"], "O-O-O w")){
					header("var_move: e1,c1");
				}else
				{
					if (strstr($move["move"], "O-O-O b")){
						header("var_move: e8,g8");
					}else
					{
						header("var_move: ".$move["move"]);
					}
				}
			}
		}


	}else{
		header("var_move: *");
        }
	exit;
?>

