<?
	$connection = @mysqli_connect($mysql["host"], $mysql["username"], $mysql["password"]) or die("Unable to connect to database.");
	$result = mysqli_select_db($connection,$mysql["database"]) or die("Unable to select database.");
?>