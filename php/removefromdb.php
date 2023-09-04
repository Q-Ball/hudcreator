<?php

session_start();

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "PASSWORD";
$dbname = "HUDS";

$name = $_SESSION['playersteamid64'];

$hudname = $_POST['hudname'];
//if ($hudname == "") { $hudname = $_GET['hudname']; }

try {
	$db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass); // connect to the database
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	try {
		$query0 = $db->query("SELECT * FROM `$name` WHERE `hudname` = $hudname");
		$result = $query0->fetchAll(PDO::FETCH_ASSOC); $result = array_filter($result);
		$hid = $result[0]["hid"];
		$imagepath = "../images/thumbs/".$name."-".$hid.".png";
		unlink($imagepath);
	} catch(PDOException $e) {
		echo $e->getMessage();//Remove or change message in production code
	}

	$query = "DELETE FROM `$name` WHERE `hudname` = :hudname";
	$dbremove = $db->prepare($query);
	$dbremove->execute( array( ":hudname" => $hudname ) );

//	print_r($result);
} catch(PDOException $e) {
	echo $e->getMessage();//Remove or change message in production code
}

//echo "HUD successfully saved into the database. You can access it in your profile.";

?>