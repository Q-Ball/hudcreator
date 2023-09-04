<?php
error_reporting(0);

include 'ttfInfo.class.php';
$output_dir = "../resource/customfonts/";

if (isset($_FILES["file"])) {
	$array = array();

	$fileName = $_FILES["file"]["name"];
	$path = $output_dir.$fileName;
	$temppath = $_FILES["file"]["tmp_name"];

	// get font name
	$fontinfo = getFontInfo($temppath);
	$fontname = $fontinfo[1];
//	var_dump($fontinfo);

	if ($fontinfo[2] === "Black") {
		$fontname = $fontinfo[1]." Black";
	} else if ($fontinfo[2] === "Bold") {
		$fontname = $fontinfo[1]." Bold";
	}

	if (isset($fontname)) {
		$fontpath = str_replace("../","",$path);

		$array["CustomFontFiles"] = json_decode($_POST["input"], true);
		$fontexist = "false";
		$arraysize = sizeof($array["CustomFontFiles"]);
		for ($i = 3; $i <= $arraysize; $i++) {
			if ($fontname == $array["CustomFontFiles"]["$i"]["name"]) {
				$fontexist = "true";
				break;
			}
		}

		if ($fontexist == "false") {
			if ( !file_exists($path) || filesize($path) !== filesize($temppath) ) {
				move_uploaded_file($_FILES["file"]["tmp_name"], $path);
			}
			$j = $arraysize + 1;
			$array["CustomFontFiles"]["$j"] = array("font" => $fontpath, "name" => $fontname);
			$array["newfontdata"] = array("path" => $fontpath, "name" => $fontname);
			echo json_encode($array);

		} else {
			echo "Font with that name already exists in Clientscheme.";
		}

	} else {
		echo "Your font appears to be damaged.";
	}

}

?>