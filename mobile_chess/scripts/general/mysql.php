<?
	$connection = @mysql_connect($mysql["host"], $mysql["username"], $mysql["password"]) or die("Unable to connect to database.");
	$result = mysql_select_db($mysql["database"]) or die("Unable to select database.");
?>