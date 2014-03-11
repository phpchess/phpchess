<?
	$sqlquery = "INSERT INTO move_history SET time = '".time()."', player_id = '".$_SESSION["id"]."', move = '".$_SERVER["HTTP_VAR_MOVE"]."', game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$sqlquery = "SELECT * FROM game WHERE game_id = '".$_SERVER["HTTP_GAMEID"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysql_fetch_array($result);

	if ($game["w_player_id"] == $_SESSION["id"])
 	{
		if ($game["next_move"] == 'w')
			$sqlquery = "UPDATE game SET next_move = 'b' WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";
		else
			$sqlquery = "UPDATE game SET next_move = 'w' WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";
	} else
	{
		if ($game["next_move"] == 'b')
			$sqlquery = "UPDATE game SET next_move = 'b' WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";
		else
			$sqlquery = "UPDATE game SET next_move = 'w' WHERE game_id = '".$_SERVER["HTTP_VAR_GAMEID"]."'";

	}
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_error: ok");
	exit;
?>