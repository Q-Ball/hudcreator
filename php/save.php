<?php
session_start();
error_reporting(0);
require_once('FixZipArchive.php');

$array = $_POST['Output'];
$result = json_decode($array, true);

function json2res($string) {
	$string = str_replace('{',"\n{\n",$string);
	$string = str_replace('}',"\n}",$string);
	$string = str_replace(',', "\n",$string);
	$string = str_replace(':',' ',$string);
	$string = str_replace('\\','',$string);
	$string = preg_replace('/{/', '', $string, 1); // remove first { symbol
	$string = preg_replace("/\}$/","",$string); // remove last } symbol
	$string = str_replace("\" []\n","\"\n{\n}\n",$string);
	$string = str_replace("\"\n\n\n}\"","{ }",$string);
	$string = str_replace("[]","{ }",$string);
	$string = str_replace("\"ParticleEffects\" [\n{","\"ParticleEffects\"\n{\n\"0\"\n{",$string);
	$string = str_replace("}]","}\n}",$string);
	return $string;
}


//**********************************************************************//
//			Create temp folder and copy base hud files there			//
//**********************************************************************//
// http://stackoverflow.com/questions/2050859/copy-entire-contents-of-a-directory-to-another-using-php
function recurse_copy($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src.'/'.$file) ) {
				recurse_copy($src.'/'.$file, $dst.'/'.$file);
            }
			else {
				copy($src.'/'.$file, $dst.'/'.$file);
			}
		}
	}
	closedir($dir);
}

$dst_folder = "../userhuds/".session_id();

mkdir($dst_folder);
mkdir($dst_folder."/resource");
mkdir($dst_folder."/resource/customfonts");

$oldpathres = "../resource/ui";
$newpathres = $dst_folder."/resource/ui";

//$oldpathfonts = "../resource/customfonts";
//$newpathfonts = $dst_folder."/resource/customfonts";

$oldpathscr = "../scripts";
$newpathscr = $dst_folder."/scripts";

recurse_copy($oldpathres,$newpathres);
recurse_copy($oldpathscr,$newpathscr);
//recurse_copy($oldpathfonts,$newpathfonts);
copy("../resource/ClientScheme.res", $dst_folder."/resource/ClientScheme.res");
copy("../resource/GameMenu.res", $dst_folder."/resource/GameMenu.res");
copy("../info.vdf", $dst_folder."/info.vdf");


//**************************************************************************//
//			Copy fonts used in clientscheme to the resource folder			//
//**************************************************************************//
$numberOfFonts = sizeof($result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]);
for ($i=1; $i <= $numberOfFonts; $i++) {
	if (isset($result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"])) {
		$temp = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"];
		copy("../".$temp, $dst_folder.'/'.$temp);
	} else {
		$temp = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"];
		copy("../".$temp, $dst_folder.'/'.$temp);
	}
}


//**************************************************//
//			Save files to the temp folder			//
//**************************************************//
file_put_contents($dst_folder.'/scripts/hudlayout.res', json2res(json_encode($result["jsonhudlayout"], true)));
file_put_contents($dst_folder.'/resource/ui/HudPlayerHealth.res', json2res(json_encode($result["jsonhudplayerhealth"], true)));
file_put_contents($dst_folder.'/resource/ui/HudAmmoWeapons.res', json2res(json_encode($result["jsonhudammoweapons"], true)));
file_put_contents($dst_folder.'/resource/ClientScheme.res', json2res(json_encode($result["jsonclientscheme"], true)));
file_put_contents($dst_folder.'/resource/ui/HudDemomanPipes.res', json2res(json_encode($result["jsonhuddemomanpipes"], true)));
file_put_contents($dst_folder.'/resource/ui/HudDemomanCharge.res', json2res(json_encode($result["jsonhuddemomancharge"], true)));
file_put_contents($dst_folder.'/resource/ui/HudMedicCharge.res', json2res(json_encode($result["jsonhudmediccharge"], true)));
file_put_contents($dst_folder.'/resource/ui/HudObjectiveStatus.res', json2res(json_encode($result["jsonhudobjectivestatus"], true)));
file_put_contents($dst_folder.'/resource/ui/HudDamageAccount.res', json2res(json_encode($result["jsonhuddamageaccount"], true)));
file_put_contents($dst_folder.'/resource/ui/HudItemEffectMeter.res', json2res(json_encode($result["jsonhuditemeffectmeter"], true)));
file_put_contents($dst_folder.'/resource/ui/HudBowCharge.res', json2res(json_encode($result["jsonhudbowcharge"], true)));
file_put_contents($dst_folder.'/resource/ui/HudAccountPanel.res', json2res(json_encode($result["jsonhudaccountpanel"], true)));
file_put_contents($dst_folder.'/resource/ui/TargetID.res', json2res(json_encode($result["jsontargetid"], true)));
file_put_contents($dst_folder.'/resource/ui/ClassSelection.res', json2res(json_encode($result["jsonclassselection"], true)));
file_put_contents($dst_folder.'/resource/ui/Teammenu.res', json2res(json_encode($result["jsonteammenu"], true)));
file_put_contents($dst_folder.'/resource/ui/winpanel.res', json2res(json_encode($result["jsonwinpanel"], true)));
file_put_contents($dst_folder.'/resource/ui/ScoreBoard.res', json2res(json_encode($result["jsonscoreboard"], true)));
file_put_contents($dst_folder.'/resource/ui/DisguiseStatusPanel.res', json2res(json_encode($result["jsondisguisestatuspanel"], true)));
file_put_contents($dst_folder.'/resource/ui/HudObjectiveKothTimePanel.res', json2res(json_encode($result["jsonkothtimepanel"], true)));
file_put_contents($dst_folder.'/resource/ui/HudStopWatch.res', json2res(json_encode($result["jsonstopwatch"], true)));
file_put_contents($dst_folder.'/resource/ui/huditemeffectmeter_killstreak.res', json2res(json_encode($result["jsonkillstreak"], true)));
file_put_contents($dst_folder.'/resource/ui/SpectatorGUIHealth.res', json2res(json_encode($result["jsonspectatorguihealth"], true)));
file_put_contents($dst_folder.'/resource/ui/HudItemEffectMeter_SpyKnife.res', json2res(json_encode($result["jsonhuditemeffectmeterspyknife"], true)));
file_put_contents($dst_folder.'/resource/ui/HudItemEffectMeter_Scout.res', json2res(json_encode($result["jsonhuditemeffectmeterscout"], true)));
file_put_contents($dst_folder.'/resource/ui/FreezePanel_Basic.res', json2res(json_encode($result["jsonfreezepanelbasic"], true)));
file_put_contents($dst_folder.'/resource/ui/FreezePanelKillerHealth.res', json2res(json_encode($result["jsonfreezepanelhealth"], true)));
file_put_contents($dst_folder.'/resource/ui/MainMenuOverride.res', json2res(json_encode($result["jsonmainmenuoverride"], true)));
file_put_contents($dst_folder.'/resource/ui/HudObjectiveTimePanel.res', json2res(json_encode($result["jsonhudobjectivetimepanel"], true)));
//file_put_contents($dst_folder.'/resource/ui/HudTournament.res', json2res(json_encode($result["jsonhudtournament"], true)));
file_put_contents($dst_folder.'/resource/ui/HudTournament.res', json2res(json_encode($result["jsonhudtournament"], JSON_FORCE_OBJECT)));
file_put_contents($dst_folder.'/resource/ui/HudTournamentSetup.res', json2res(json_encode($result["jsonhudtournamentsetup"], true)));
file_put_contents($dst_folder.'/resource/ui/Spectator.res', json2res(json_encode($result["jsonspectator"], true)));
file_put_contents($dst_folder.'/resource/ui/SpectatorTournament.res', json2res(json_encode($result["jsonspectatortournament"], true)));
file_put_contents($dst_folder.'/resource/ui/HudMatchStatus.res', json2res(json_encode($result["jsonhudmatchstatus"], true)));


//**************************************************//
//			Add folder to the zip archive			//
//**************************************************//
// http://ninad.pundaliks.in/blog/2011/05/recursively-zip-a-directory-with-php/
$zip_file_name = '../userhuds/hud-'.session_id().'-'.md5(serialize($array)).'.zip';

try {
	rmdir($zip_file_name);
} catch (Exception $e) {
}

sleep(1);

$za = new FlxZipArchive;
$res = $za->open($zip_file_name, ZipArchive::CREATE);
if($res === TRUE) {
    $za->addDir($dst_folder, basename($dst_folder));
    $za->close();
} else {
	echo 'Could not create a zip archive';
}


//******************************************************//
//				Remove folder recursively				//
//******************************************************//
// http://aidanlister.com/2004/04/recursively-deleting-a-folder-in-php/
function rmdirr($dirname) {
	if (!file_exists($dirname)) {
		return false;
	}
	if (is_file($dirname) || is_link($dirname)) {
		return unlink($dirname);
	}
	$dir = dir($dirname);
	while (false !== $entry = $dir->read()) {
		if ($entry == '.' || $entry == '..') {
			continue;
		}
		rmdirr($dirname . DIRECTORY_SEPARATOR . $entry);
	}
	$dir->close();
	return rmdir($dirname);
}

sleep(2);

rmdirr($dst_folder."/");

echo "../hudcreator/".str_replace('../','',$zip_file_name);

?>