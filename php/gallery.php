<?php

session_start();

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "PASSWORD";
$dbname = "HUDS";

try {
	$db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$query = $db->query("show tables");
	while ($row = $query->fetch(PDO::FETCH_NUM)) {
		$tablename = $row[0];
		$query1 = $db->query("SELECT * FROM `$tablename`");
		$result = $query1->fetchAll(PDO::FETCH_ASSOC); $result = array_filter($result);
		for ($i=0;$i<sizeof($result);$i++) {
			$output[$tablename] = [];
			$output[$tablename]["id"] = $result[$i]["id"];
			$output[$tablename]["name"] = $result[$i]["hudname"];
			$output[$tablename]["image"] = $result[$i]["image"];
		}
		var_dump($output);
	}

} catch(PDOException $e) {
	echo $e->getMessage(); // Remove or change message in production code
}

//echo "HUD successfully saved into the database. You can access it in your profile.";

?>