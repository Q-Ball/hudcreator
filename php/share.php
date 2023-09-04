<?php

session_start();

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "PASSWORD";
$dbname = "HUDS";

//$name = $_SESSION['playersteamid64'];
$userid = preg_replace("/[^0-9]/", "", $_POST['uid']);
$hudid = preg_replace("/[^A-Za-z0-9]/", "", $_POST['hid']);

try {
	$db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbinsert = $db->prepare("SELECT * FROM `$userid` WHERE `id` = :hudid");
	$dbinsert->bindParam(':hudid', $hudid);
	$dbinsert->execute();
	$result = $dbinsert->fetchAll(PDO::FETCH_ASSOC); $result = array_filter($result);
	print_r($result[0]["data"]);

} catch(PDOException $e) {
	echo $e->getMessage(); // Remove or change message in production code
}

//echo "HUD successfully saved into the database. You can access it in your profile.";

?>