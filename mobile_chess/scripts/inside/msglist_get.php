<?
	$sqlquery = "INSERT INTO c4m_msginbox (player_id, message, msg_posted) SELECT player_id, message, posted FROM message_queue";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$sqlquery = "DELETE FROM message_queue";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

    $sqlquery = "SELECT * FROM c4m_msginbox WHERE player_id = '".$_SESSION["id"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	if (!mysql_numrows($result))
	{
		header("var_msg_no: 0");
	} else
	{
		$msg_no = mysql_numrows($result);


		$i = -1;

		while ($msg = mysql_fetch_array($result))
		{
			$i++;
			$inbox_id = trim($msg["inbox_id"]);
        	$message = trim($msg["message"]);
        	$posted = trim($msg["msg_posted"]);

			if (substr($message, 0, 2) == "M0") // Move Message
			{
				$msg_no--;
				$i--;
				/*
				$player_id = substr($message, 11,8);

				$sqlquery = "SELECT * FROM player WHERE player_id = '".$player_id."'";
				$result1 = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
                $player = mysql_fetch_array($result);
				header("var_msg_".$i."_id: ".$inbox_id);
				header("var_msg_".$i."_title: ".$player["user_id"]);
				header("var_msg_".$i."_type: move");*/
			}

			if (substr($message, 0, 2) == "C0") // Text messages
			{
				$msg = substr($message, 10);
          		LIST($name, $message) = explode("-", $msg);
				header("var_msg_".$i."_id: ".$inbox_id);
				header("var_msg_".$i."_title: ".$name);
				header("var_msg_".$i."_type: text");
			}

			if (substr($message, 0, 2) == "GC") // Challenge Message
			{
				$player_id = substr($message, (strlen($message)-8), 8);
				$sqlquery = "SELECT * FROM player WHERE player_id = '".$player_id."'";
				$result1 = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
                $player = mysql_fetch_array($result);
				header("var_msg_".$i."_id: ".$inbox_id);
				header("var_msg_".$i."_title: ".$player["user_id"]);
				header("var_msg_".$i."_type: challenge");
			}

			if (substr($message, 0, 2) == "T0") // Tournament Message
			{
				$tid = substr($message, 3,(strlen($message)-3));
				$sqlquery = "SELECT * FROM c4m_tournament WHERE t_id = '".$tid."'";
				$result1 = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());
				$tournament = mysql_fetch_array($result);
				header("var_msg_".$i."_id: ".$inbox_id);
				header("var_msg_".$i."_title: ".$tournament["t_name"]);
				header("var_msg_".$i."_type: tournamet");
			}
		}

		header("var_msg_no: ".$msg_no);
	}
?>