<?
	$sqlquery = "SELECT * FROM c4m_servermessage ORDER BY sm_date DESC";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_msg_no: ".mysql_numrows($result));

	$i = -1;
	while ($message = mysql_fetch_array($result))
	{
		header("var_msg_".$i."_content: ".$message["sm_msg"]);
	}
?>