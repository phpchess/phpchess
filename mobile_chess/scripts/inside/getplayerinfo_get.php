<?
	settype($_SERVER["HTTP_VAR_PLAYER_ID"], "integer");

	$sqlquery = "SELECT * FROM player, c4m_personalinfo WHERE player_id = '".$_SERVER["HTTP_VAR_PLAYER_ID"]."' AND player_id = p_playerid";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$player = mysqli_fetch_array($result);


	$wins = 0;
	$losses = 0;
	$draws = 0;

	$sqlquery = "SELECT COUNT(*) FROM game WHERE b_player_id = '".$player["player_id"]."' AND completion_status  = 'B'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$wins += $game["COUNT(*)"];

	$sqlquery = "SELECT COUNT(*) FROM game WHERE w_player_id = '".$player["player_id"]."' AND completion_status  = 'W'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$wins += $game["COUNT(*)"];

	$sqlquery = "SELECT COUNT(*) FROM game WHERE b_player_id = '".$player["player_id"]."' AND completion_status  = 'W'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$losses += $game["COUNT(*)"];

	$sqlquery = "SELECT COUNT(*) FROM game WHERE w_player_id = '".$player["player_id"]."' AND completion_status  = 'B'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$losses += $game["COUNT(*)"];


	$sqlquery = "SELECT COUNT(*) FROM game WHERE b_player_id = '".$player["player_id"]."' AND completion_status  = 'D'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$draws += $game["COUNT(*)"];

 	$sqlquery = "SELECT COUNT(*) FROM game WHERE w_player_id = '".$player["player_id"]."' AND completion_status  = 'D'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$game = mysqli_fetch_array($result);
	$draws += $game["COUNT(*)"];

    $points = 1200 + ($wins * 10) - ($losses * 5);

	if($wins == 0){
      $ranking = "Unrated with ".$points." points.";
    }

    if($wins > 0 && $wins <= 25){
      $ranking = "Provisional with ".$points." points.";
    }

    if($wins > 25){
      $ranking = "Established with ".$points." points.";
    }

	header("var_statistics_wins: ".$wins);
	header("var_statistics_losses: ".$losses);
	header("var_statistics_draws: ".$draws);
    header("var_statistics_ranking: ".$ranking);

	header("var_personal_name: ".$player["p_fullname"]);
	header("var_personal_location: ".$player["p_location"]);
	header("var_personal_age: ".$player["p_age"]);
	header("var_personal_selfrating: ".$player["p_selfrating"]);
	header("var_personal_commentmottor: ".$player["p_commentmotto"]);
	header("var_personal_favouritechessplayer: ".$player["p_favouritechessplayer"]);

	header("var_user_register_date: ".date("d-m-Y", $player["signup_time"]));
?>