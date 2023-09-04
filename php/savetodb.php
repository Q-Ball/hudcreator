<?php

session_start();

$dbhost = "localhost";
$dbuser = "root";
$dbpass = "PASSWORD";
$dbname = "HUDS";

$name = $_SESSION['playersteamid64'];
$data = $_POST['data'];
$image = $_POST['image'];
$newhudname = trim(preg_replace('/[^A-Za-z0-9]/', '', urldecode(html_entity_decode(strip_tags($_POST['newhudname'])))));
$id = md5($newhudname);

function scale_image($image,$target) {
    if(!empty($image)) {
        $source_image = imagecreatefrompng($image);
        $source_imagex = imagesx($source_image);
        $source_imagey = imagesy($source_image);
        $dest_imagex = 150;
        $dest_imagey = 100;
        $image2 = imagecreatetruecolor($dest_imagex, $dest_imagey);
        imagecopyresampled($image2, $source_image, 0, 0, 0, 0, $dest_imagex, $dest_imagey, $source_imagex, $source_imagey);
        imagepng($image2, $target, 9);
    }
}
//$image = str_replace('data:image/png;base64,', '', $image);
$image = substr($image, strpos($image, ",")+1);
$decoded = base64_decode($image);
$imagepath = "../images/thumbs/".$name."-".$id.".png";
$imagepath_db = "../hudcreator/images/thumbs/".$name."-".$id.".png";
file_put_contents($imagepath, $decoded);
scale_image($imagepath,$imagepath);

try {
	$db = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbcreate = "CREATE TABLE IF NOT EXISTS `$name` (`id` varchar(50) NOT NULL,`hudname` varchar(30) NOT NULL PRIMARY KEY, `data` MEDIUMTEXT NOT NULL, `image` MEDIUMTEXT NOT NULL);";
	$db->exec($dbcreate);

	try {
		$addcolumn = "ALTER IGNORE TABLE `$name` ADD COLUMN image MEDIUMTEXT;";
		$db->exec($addcolumn);
	} catch(PDOException $e) {
//		echo $e->getMessage();
	}
	try {
		$columnsize = "ALTER IGNORE TABLE `$name` MODIFY `id` varchar(50);";
		$db->exec($columnsize);
	} catch(PDOException $e) {
//		echo $e->getMessage();
	}

	$dbinsert = $db->prepare("INSERT INTO `$name` (`id`, `hudname`, `data`, `image`) VALUES(:id, :hudname, :data, :image) ON DUPLICATE KEY UPDATE data= :data, image= :image");
	$dbinsert->bindParam(':id', $id);
	$dbinsert->bindParam(':hudname', $newhudname, PDO::PARAM_STR);
	$dbinsert->bindParam(':data', $data, PDO::PARAM_STR);
	$dbinsert->bindParam(':image', $imagepath_db, PDO::PARAM_STR);
	$dbinsert->execute();
} catch(PDOException $e) {
	echo $e->getMessage(); // Remove or change message in production code
}

//echo "HUD successfully saved into the database. You can access it in your profile.";

?>