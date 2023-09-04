<?php

session_start();

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "PASSWORD";
$dbname = "HUDS";

$name = $_SESSION['playersteamid64'];

try {
	$db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$query = $db->query("SELECT * FROM `$name`");
	$result = $query->fetchAll(PDO::FETCH_ASSOC); $result = array_filter($result);
	for ($i=0;$i<sizeof($result);$i++) {
		$output[$result[$i]["hudname"]] = [];
		$output[$result[$i]["hudname"]]["data"] = $result[$i]["data"];
		$output[$result[$i]["hudname"]]["hid"] = $result[$i]["id"];
		$output[$result[$i]["hudname"]]["image"] = $result[$i]["image"];
		$output[$result[$i]["hudname"]]["uid"] = $name;
	}
	echo json_encode($output);
} catch(PDOException $e) {
	echo $e->getMessage(); // Remove or change message in production code
}

//echo "HUD successfully saved into the database. You can access it in your profile.";

?>