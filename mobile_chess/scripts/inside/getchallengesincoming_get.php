<?
 	$sqlquery = "SELECT * FROM game WHERE (w_player_id = '".$_SESSION["id"]."' OR b_player_id = '".$_SESSION["id"]."') AND initiator != '".$_SESSION["id"]."' AND completion_status = 'I' AND status = 'W'";
  	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
  	header("var_challenges_no: ".mysqli_num_rows($result));

  	$i = 0;
  	while ($challenge = mysqli_fetch_array($result))
	{
    	$sqlquery = "SELECT userid FROM player WHERE player_id = '".$challenge["initiator"]."'";
      	$result1 = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

      	$player = mysqli_fetch_array($result1);

      	header("var_game_".$i."_id: ".$challenge["game_id"]);
      	header("var_player_".$i++."_name: ".$player["userid"]);
  	}
?>
