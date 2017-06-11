<?
	$sqlquery = "SELECT * FROM c4m_servermessage ORDER BY sm_date DESC";
	$result = mysql_query($sqlquery) or die("Unable to execute query: ".mysql_error());

	header("var_msg_no: ".mysqli_num_rows($result));

	$i = -1;
	while ($message = mysqli_fetch_array($result))
	{
		header("var_msg_".$i."_content: ".$message["sm_msg"]);
	}
?>