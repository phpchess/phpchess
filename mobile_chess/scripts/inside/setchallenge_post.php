<?
	settype($_SERVER["HTTP_VAR_CHALLENGED_ID"], "integer");

	if ($_SERVER["HTTP_VAR_COLOR"] != "w")
	{
		$white = $_SESSION["id"];
		$black = $_SERVER["HTTP_VAR_CHALLENGED_ID"];
	} else
	{
		$white = $_SERVER["HTTP_VAR_CHALLENGED_ID"];
		$black = $_SESSION["id"];
	}

	if ($_SERVER["HTTP_VAR_CHALLENGED_ID"] == $_SERVER["id"])
	{
		header("var_error: Cannot challenge oneself.");
		exit;
	}

	$sqlquery = "SELECT * FROM player WHERE player_id = '".$_SERVER["HTTP_VAR_CHALLENGED_ID"]."' AND status != 'F'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	if (!mysql_numrows($result))
	{
		header("var_error: Invalid player id.");
		exit;
	}

	$sqlquery = "INSERT INTO game SET game_id = '".time().session_id()."', initiator = '".$_SESSION["id"]."', w_player_id = '".$white."', b_player_id = '".$black."', start_time = '".time()."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	$sqlquery = "UPDATE player SET status = 'E' WHERE player_id = '".$_SESSION["id"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_error: Player notified of challenge.");
	exit;
	/*
	$char[0]="0";
    $char[1]="1";
    $char[2]="2";
    $char[3]="3";
    $char[4]="4";
    $char[5]="5";
    $char[6]="6";
    $char[7]="7";
    $char[8]="8";
    $char[9]="9";
    $char[10]="A";
    $char[11]="B";
    $char[12]="C";
    $char[13]="D";
    $char[14]="E";
    $char[15]="F";

    $name="";

    for($i=0;$i<32;$i++){
      $name=$name.$char[rand(0,15)];
    }


	$game_id = $this->gen_unique();

      $st = "INSERT INTO game(game_id, initiator, w_player_id, b_player_id, start_time) VALUES('".$game_id."',".$requestor.",".$w_player_id.",".$b_player_id.",".time().")";
      mysql_query($st) or die(mysql_error());

      // immediately update the status of the requestor
      $st = "UPDATE player SET status='E' WHERE player_id=".$requestor."";
      mysql_query($st) or die(mysql_error());

      //////////////////////////////////////////////
      // notify the challenged
      $st = "INSERT INTO message_queue(player_id, message, posted) VALUES(".$other.",'".$this->add_header("G",$game_id.$this->zero_pad($requestor,8),"C")."',".time().")";
      mysql_query($st) or die(mysql_error());

      //Instantiate theCR3DCQuery Class
      $oR3DCQuery = new CR3DCQuery($this->ChessCFGFileLocation);

      if($oR3DCQuery->ChallangeNotification($other) == true){

        $requestorname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $requestor);
        $otherguysname = $oR3DCQuery->GetUserIDByPlayerID($this->ChessCFGFileLocation, $other);

        $otheremail = $oR3DCQuery->GetEmailByPlayerID($this->ChessCFGFileLocation, $other);

        $subject = "Challange Notification";

        $bodyp1 = "Hello, ".$otherguysname.".<br>
                  ".$requestorname.", has challanged you to a game.<br>
                  Game ID  : ".$game_id."<br>
                  ".$requestorname." vs ".$otherguysname."<br><br>
                  ".$conf['site_url']." <br>The home of ".$conf['site_name']."<br>";

       $this->SendEmail($otheremail, $conf['registration_email'], $conf['site_name'], $subject, $bodyp1);
      }

      unset($oR3DCQuery);*/
?>