<?
	settype($_SERVER["HTTP_VAR_MSG_ID"], "integer");
	$sqlquery = "DELETE FROM c4m_msginbox WHERE player_id = '".$_SESSION["id"]."'";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_ok: ok");
?>