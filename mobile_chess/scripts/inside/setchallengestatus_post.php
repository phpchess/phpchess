<?
	$game_id = $_SERVER["HTTP_VAR_CHALLENGEID"];

	if ($_GET["action"] == "acceptchallenge")
	{
		$sqlquery = "UPDATE game SET status='A' WHERE game_id='".$game_id."' AND initiator != '".$_SESSION["id"]."' AND (w_player_id = '".$_SESSION["id"]."' OR b_player_id = '".$_SESSION["id"]."')";
		$result = mysql_query($sqlquery) or die("Unable to execute query.");
	} else
	{
		$sqlquery = "DELETE FROM game WHERE game_id='".$game_id."' AND initiator != '".$_SESSION["id"]."' AND (w_player_id = '".$_SESSION["id"]."' OR b_player_id = '".$_SESSION["id"]."')";
		$result = mysql_query($sqlquery) or die("Unable to execute query.");
	}

	header("var_error: ok");
?>