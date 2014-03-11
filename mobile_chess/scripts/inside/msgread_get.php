<?
	settype($_SERVER["HTTP_VAR_MSG_ID"], "integer");
	$sqlquery = "SELECT * FROM c4m_msginbox WHERE inbox_id = '".$_SERVER["HTTP_VAR_MSG_ID"]."' AND player_id = '".$_SESSION["id"]."'";
    $result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$message = mysql_fetch_array($result);

	$message["inbox_id"] = trim($message["inbox_id"]);
	$message["message"] = trim($message["message"]);
    $message["msg_posted"] = trim($message["msg_posted"]);

	if (substr($message["message"], 0, 2) == "C0")
	{
		$msg = substr($message["message"], 10);
		list($name, $msg) = explode("-", $msg);
		header("var_sender_id: ".$message["player_id"]);
		header("var_sender_name: ".$name);
		header("var_message_id: ".$message["inbox_id"]);
		header("var_message_content: ".$msg);
	};

	if (substr($message["message"], 0, 2) == "M0")
	{
		$player_id = substr($message["message"], 11,8);
		$Move = substr($message["message"], (strlen($message["message"])-5),5);
		$gameid = substr($message["message"], 19, (strlen($message["message"])-24));

		$sqlquery = "SELECT * FROM player WHERE player_id = '".$player_id."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
        $player = mysql_fetch_array($result);

		header("var_sender_id: ".$player["player_id"]);
		header("var_sender_name: ".$player["userid"]);
		header("var_message_id: ".$message["inbox_id"]);
		header("var_message_content: ".$player["userid"]." from game id: ".$gameid." made the following move: ".$Move);
	}

	if (substr($message["message"], 0, 2) == "GC")
	{
		$TextCount = substr($message["message"], 2,8);
        $gameid = substr($message["message"], 10,((int)$TextCount-8));
        $player_id = substr($message["message"], (strlen($message["message"])-8), 8);

		$sqlquery = "SELECT * FROM player WHERE player_id = '".$player_id."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
        $player = mysql_fetch_array($result);

		header("var_sender_id: ".$player["player_id"]);
		header("var_sender_name: ".$player["userid"]);
		header("var_message_id: ".$message["inbox_id"]);
		header("var_message_content: ".$player["userid"]." has created a game and invites you to accept. Game ID: ".$gameid);
	}

	if (substr($message["message"], 0, 2) == "T0")
	{
		$tid = substr($message, 3,(strlen($message)-3));

		$sqlquery = "SELECT * FROM c4m_tournament WHERE t_id = '".$tid."'";
		$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
		$tournament = mysql_fetch_array($result);

		header("var_sender_id: 0");
		header("var_sender_name: Tournament Invitation");
		header("var_message_id: ".$message["inbox_id"]);
		header("var_message_content: You have been invited to the ".$tournament["t_name"]."' tournament game.");
	}
?>