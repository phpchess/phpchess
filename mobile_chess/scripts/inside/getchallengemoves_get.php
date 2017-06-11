<?

	$game_id = $_SERVER["HTTP_VAR_GAMEID"];

	$sqlquery = "SELECT * FROM move_history WHERE game_id = '".$game_id."' ORDER BY time ASC";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
	header("var_moves_no: ".mysqli_num_rows($result));

	$i = 0;
	while ($move = mysqli_fetch_array($result))
	{
		
		if (strstr($move["move"], "O-O w")){
			header("var_move_".$i++.": e1g1");
		}else
		{
			if (strstr($move["move"], "O-O b")){
				header("var_move_".$i++.": e8c8");
			}else
			{
				if (strstr($move["move"], "O-O-O w")){
					header("var_move_".$i++.": e1c1");

				}else
				{
					if (strstr($move["move"], "O-O-O b")){
						header("var_move_".$i++.": e8g8");

					}else
					{
						header("var_move_".$i++.": ".str_replace(",","",$move["move"]));

					}
				}
			}
		}


	}
	exit;
?>