<?php
session_start();

$folder = "../tempconvert/";
$canvaswidth = $_GET['width'];
$canvasheight = $_GET['height'];

// copy hud_animations from root
// copy mainmenu

//$element = preg_replace("/[^A-Za-z0-9-_]/", "", $_GET['element']);

function getContent($folder, $path) {
	if (file_exists($folder.$path)) {
		return file_get_contents($folder.$path);
	} else {
		return file_get_contents('../'.$path);
	}
}

$hudlayout = getContent($folder, 'scripts/hudlayout.res');
$hudplayerhealth = getContent($folder, 'resource/ui/HudPlayerHealth.res');
$hudammoweapons = getContent($folder, 'resource/ui/HudAmmoWeapons.res');
$clientscheme = getContent($folder, 'resource/ClientScheme.res');
$huddemomanpipes = getContent($folder, 'resource/ui/HudDemomanPipes.res');
$huddemomancharge = getContent($folder, 'resource/ui/HudDemomanCharge.res');
$hudobjectivestatus = getContent($folder, 'resource/ui/HudObjectiveStatus.res');
$huddamageaccount = getContent($folder, 'resource/ui/HudDamageAccount.res');
$hudmediccharge = getContent($folder, 'resource/ui/HudMedicCharge.res');
$huditemeffectmeter = getContent($folder, 'resource/ui/HudItemEffectMeter.res');
$hudbowcharge = getContent($folder, 'resource/ui/HudBowCharge.res');
$hudaccountpanel = getContent($folder, 'resource/ui/HudAccountPanel.res');
$targetid = getContent($folder, 'resource/ui/TargetID.res');
$classselection = getContent($folder, 'resource/ui/ClassSelection.res');
$teammenu = getContent($folder, 'resource/ui/Teammenu.res');
$winpanel = getContent($folder, 'resource/ui/winpanel.res');
$scoreboard = getContent($folder, 'resource/ui/ScoreBoard.res');
$disguisestatuspanel = getContent($folder, 'resource/ui/DisguiseStatusPanel.res');
$kothtimepanel = getContent($folder, 'resource/ui/HudObjectiveKothTimePanel.res');
$stopwatch = getContent($folder, 'resource/ui/HudStopWatch.res');
$killstreak = getContent($folder, 'resource/ui/huditemeffectmeter_killstreak.res');
$spectatorguihealth = getContent($folder, 'resource/ui/SpectatorGUIHealth.res');
$huditemeffectmeter_spyknife = getContent($folder, 'resource/ui/HudItemEffectMeter_SpyKnife.res');
$huditemeffectmeter_scout = getContent($folder, 'resource/ui/HudItemEffectMeter_Scout.res');
$freezepanelbasic = getContent($folder, 'resource/ui/FreezePanel_Basic.res');
$freezepanelhealth = getContent($folder, 'resource/ui/FreezePanelKillerHealth.res');
$mainmenuoverride = file_get_contents('../resource/ui/MainMenuOverride.res');
$hudobjectivetimepanel = getContent($folder, 'resource/ui/HudObjectiveTimePanel.res');
$hudtournament = getContent($folder, 'resource/ui/HudTournament.res');
$hudtournamentsetup = getContent($folder, 'resource/ui/HudTournamentSetup.res');
$spectator = getContent($folder, 'resource/ui/Spectator.res');
$spectatortournament = getContent($folder, 'resource/ui/SpectatorTournament.res');
$hudmatchstatus = getContent($folder, '/resource/ui/HudMatchStatus.res');

function res2json($string) {
	$string = trim($string);

	$string = str_replace("http //", "http:--", $string); // http links fix
	$string = preg_replace("|\/\/(.*?)\n|", "\n", $string); // remove comments
	$string = preg_replace("|\/\/(.*?)\z|", "\n", $string); // remove comments in the end of file - ellshud fix
	$string = str_replace("http:--", "http://", $string); // http links fix

	$string = str_replace("\t","",$string); // remove tabs
	$string = preg_replace("/[\r\n]+/", "\n", $string); // replace \r\n with \n
	$string = str_replace("\n  ", "\n", $string); // replace "\n  " with just "\n" - rayshud fix
	$string = str_replace("\n  ", "\n", $string); // do it again - ellshud fix

	$string = str_replace("[\$WIN32]","",$string); // remove [$WIN32] from string
	$string = str_replace("[!\$OSX]","",$string); // remove [$OSX] from string
	$string = preg_replace("|(.*?)X360(.*?)\n|im", "\n", $string); // remove line if it contains [$X360]
	$string = preg_replace("|(.*?)OSX(.*?)\n|im", "\n", $string); // remove line if it contains [$OSX]

	$string = preg_replace("|\"( +)\n|is", "\"\n", $string); // replace "\n with \n

	$string = str_replace('" ','"',$string);
	$string = str_replace('  "','"',$string);

	$string = preg_replace("|\n([A-Za-z])([^\"]*?)([A-Za-z0-9])\n|","\n\"$1$2$3\"\n",$string); // add quotes around single words
	$string = preg_replace("|\n([A-Za-z])([^\"]*?)([A-Za-z0-9]) \n|","\n\"$1$2$3\"\n",$string); // add quotes around single words

	$string = str_replace("MainMenuHighlightBorder ", "\"MainMenuHighlightBorder\"", $string); // yaHUD fix
	$string = str_replace(" if_mvm\n", ", \"if_mvm\"\n", $string); // yaHUD fix
	$string = str_replace("} \n CDamageAccountPanel\n{", "} \n \"CDamageAccountPanel\"\n{", $string); // ells hud fix
	$string = str_replace("}\nAchievementNotificationPanel  \n{", "}\n\"AchievementNotificationPanel\"  \n{", $string); // ells hud fix

	// add quotes to the BaseSettings elements recursively
	do { $string = preg_replace('|\n([A-Za-z])(.*?)"(.*?)"\n|',"\n\"$1$2\"\"$3\"\n", $string, -1, $count); }
	while ($count > 0);

	$string = preg_replace('|"(.*?){|ism', '"$1: {', $string); // add : between " and {
	$string = preg_replace('|"(.*?)"(.*?)"(.*?)"|', ',"$1":"$3"', $string); // add : between parameter and value
	$string = preg_replace('|: {(.*?),"|is', ': { $1"', $string); // remove extra , at the end
	$string = preg_replace('|}(.*?)"|is', '} $1, "', $string); // add , after }
	$string = str_replace('"2":"resource/tfd.otf"','"2":"resource/tfd.otf", ',$string); // fix for clientscheme.res

	// fix broken stuff after removing X360 and OSX
	$string = preg_replace("/, \"(GameUIButtonsSmall|GameUIButtonsSmallest)\"\n: { \n\n{(.*?)} \n}/is", "", $string);
	$string = preg_replace("/, \"(GameUIButtonsSmall|GameUIButtonsSmallest)\"\n: { \n\n  {(.*?)} \n}/is", "", $string);
	$string = preg_replace("|\n} \n: { \n([^{]*)\n} \n}|is", "\n}\n}", $string);
	$string = preg_replace("|\n}\n: { \n([^{]*)\n} \n}|is", "\n}\n}", $string);
	$string = preg_replace("|\n} \n\n: { \n([^{]*)\n} \n}|is", "\n}\n}", $string);
	$string = preg_replace("|\n}\n\n: { \n([^{]*)\n} \n}|is", "\n}\n}", $string);

	$string = preg_replace("|\n} \n: { \n(.*?)\n} \n}|is", "\n}\n", $string);
	$string = preg_replace("|\n}\n: { \n(.*?)\n} \n}|is", "\n}\n", $string);
	$string = preg_replace("|\n} \n\n: { \n(.*?)\n} \n}|is", "\n}\n", $string);
	$string = preg_replace("|\n}\n\n: { \n(.*?)\n} \n}|is", "\n}\n", $string);
	$string = preg_replace("|} \n\n  : {(.*?)\n  } |is", "}", $string);

	// ellshud fix
	$string = str_replace("\n  Left\n  : { \n", "\n, \"Left\"\n  : { \n", $string);
	$string = preg_replace("/}\n  (Right|Top|Bottom)\n  : { \n  ,/is", "}\n, \"$1\"\n  : { \n", $string);
	$string = str_replace("\n  Bottom\n  : { \n", "\n, \"Bottom\"\n  : { \n", $string);
	$string = str_replace("\"\n\n\"PipeIcon\"\n: { ", "\"\n\n, \"PipeIcon\"\n: { ", $string);
	$string = str_replace("\"\n\n\"MoveableIconBG\"\n: { ", "\"\n\n, \"MoveableIconBG\"\n: { ", $string);
	$string = str_replace("\"\n\n\"if_mvm\"\n: { ", "\"\n\n, \"if_mvm\"\n: { ", $string);
	$string = str_replace("\"\n\n\"AvatarBGPanel\"\n  : { ", "\"\n\n, \"AvatarBGPanel\"\n: { ", $string);

	// add , before some nested elements like model
	$string = preg_replace("|\"\n\"|is","\"\n,\"",$string);
	$string = preg_replace("|\"\r\n\"|is","\"\n,\"",$string);

	$string = preg_replace("|\n}\n\n: { \n([^{]*)\n}\n\n, |is", "\n}\n, ", $string);

	$string = preg_replace("|\n}\n: { \n([^{]*)\n} \n|is", "\n}", $string); // fix for older hud versions
	$string = preg_replace("|\n}: { \n(.*?)\n}\n}|is", "\n}", $string); // fix for older hud versions

	$string = preg_replace("|\n: { \n\n{\n(.*?)\n} \n}\n, \"itempanel\"|is", ": { \n\"itempanel\"", $string); // oxide/grape fix - freezepanel
	$string = preg_replace("|\n([^,]*?)\"TimePanelValue\"|", ", \"TimePanelValue\"", $string); // yaHUD fix - objective status

	$string = str_replace("Scheme\n{", "\"Scheme\"\n: {", $string); // fix for older hud versions
	$string = str_replace("Scheme{Colors{\n,", "\"Scheme\" : {\n \"Colors\" : {\n", $string); // morehud fix - clientscheme

	// remove xbox and osx related bugs
	$string = preg_replace("|} \n}\n\n: { \n,(.*?)\n}\n}|is", "}\n}\n", $string); // fix for older hud versions - ellshud

	$string = preg_replace("|}\n\n: { \n,(.*?)\n} |is", "}", $string); // fix for older hud versions - oxide
	$string = preg_replace("|}\n\n: { \n,(.*?)\n}\n}|is", "}\n", $string); // fix for older hud versions - oxide
	$string = preg_replace("|} \n\n: { \n,(.*?)\n}\n}|is", "}\n", $string); // fix for older hud versions - oxide

//	$string = str_replace("Colors\n", "\"Colors\" : \n", $string); // fix for older hud versions
//	$string = str_replace("BaseSettings\n", ", \"BaseSettings\"\n", $string); // fix for older hud versions
//	$string = str_replace(", \"ReplayBrowser.BgColor\", \"BaseSettings\"\n", $string); // fix for older hud versions

	$string = str_replace("\n  HudDeathNotice\n  : { \n    , \"fieldName\"","\n, \"HudDeathNotice\"\n: { \n\"fieldName\"",$string); // new HUDs fix
	$string = str_replace("\"\n\n\"playername\"\n","\"\n, \"playername\"\n",$string); // beavern HUD fix
	$string = str_replace(",\"xpos\":\"c-238\"\n,\"ypos\":\"c5\"\n,\"xpos\":\"32\"", ",\"xpos\":\"c-238\"\n,\"ypos\":\"c5\"", $string); // fix duplicate keys in RaysHUD
	$string = str_replace("\"\nif_mvm : { ", "\"\n, \"if_mvm\" : { ", $string); // broeselhud fix

	$string = "{".$string."}";
	return $string;
}

$json_hudlayout = res2json($hudlayout);
$json_hudplayerhealth = res2json($hudplayerhealth);
$json_hudammoweapons = res2json($hudammoweapons);
$json_clientscheme = res2json($clientscheme);
$json_huddemomanpipes = res2json($huddemomanpipes);
$json_huddemomancharge = res2json($huddemomancharge);
$json_hudobjectivestatus = res2json($hudobjectivestatus);
$json_huddamageaccount = res2json($huddamageaccount);
$json_hudmediccharge = res2json($hudmediccharge);
$json_huditemeffectmeter = res2json($huditemeffectmeter);
$json_hudbowcharge = res2json($hudbowcharge);
$json_hudaccountpanel = res2json($hudaccountpanel);
$json_targetid = res2json($targetid);
$json_classselection = res2json($classselection);
$json_teammenu = res2json($teammenu);
$json_winpanel = res2json($winpanel);
$json_scoreboard = res2json($scoreboard);
$json_disguisestatuspanel = res2json($disguisestatuspanel);
$json_kothtimepanel = res2json($kothtimepanel);
$json_stopwatch = res2json($stopwatch);
$json_killstreak = res2json($killstreak);
$json_spectatorguihealth = res2json($spectatorguihealth);
$json_huditemeffectmeter_spyknife = res2json($huditemeffectmeter_spyknife);
$json_huditemeffectmeter_scout = res2json($huditemeffectmeter_scout);
$json_freezepanelbasic = res2json($freezepanelbasic);
$json_freezepanelhealth = res2json($freezepanelhealth);
$json_mainmenuoverride = res2json($mainmenuoverride);
$json_hudobjectivetimepanel = res2json($hudobjectivetimepanel);
$json_hudtournament = res2json($hudtournament);
$json_hudtournamentsetup = res2json($hudtournamentsetup);
$json_spectator = res2json($spectator);
$json_spectatortournament = res2json($spectatortournament);
$json_hudmatchstatus = res2json($hudmatchstatus);

$result = array(
	"jsonhudlayout" => json_decode($json_hudlayout, true),
	"jsonhudplayerhealth" => json_decode($json_hudplayerhealth, true),
	"jsonhudammoweapons" => json_decode($json_hudammoweapons, true),
	"jsonclientscheme" => json_decode($json_clientscheme, true),
	"jsonhuddemomancharge" => json_decode($json_huddemomancharge, true),
	"jsonhuddemomanpipes" => json_decode($json_huddemomanpipes, true),
	"jsonhudobjectivestatus" => json_decode($json_hudobjectivestatus, true),
	"jsonhuddamageaccount" => json_decode($json_huddamageaccount, true),
	"jsonhudmediccharge" => json_decode($json_hudmediccharge, true),
	"jsonhuditemeffectmeter" => json_decode($json_huditemeffectmeter, true),
	"jsonhudbowcharge" => json_decode($json_hudbowcharge, true),
	"jsonhudaccountpanel" => json_decode($json_hudaccountpanel, true),
	"jsontargetid" => json_decode($json_targetid, true),
	"jsonclassselection" => json_decode($json_classselection, true),
	"jsonteammenu" => json_decode($json_teammenu, true),
	"jsonwinpanel" => json_decode($json_winpanel, true),
	"jsonscoreboard" => json_decode($json_scoreboard, true),
	"jsondisguisestatuspanel" => json_decode($json_disguisestatuspanel, true),
	"jsonhudmatchstatus" => json_decode($json_hudmatchstatus, true),
	"jsonkothtimepanel" => json_decode($json_kothtimepanel, true),
	"jsonstopwatch" => json_decode($json_stopwatch, true),
	"jsonkillstreak" => json_decode($json_killstreak, true),
	"jsonspectatorguihealth" => json_decode($json_spectatorguihealth, true),
	"jsonhuditemeffectmeterspyknife" => json_decode($json_huditemeffectmeter_spyknife, true),
	"jsonhuditemeffectmeterscout" => json_decode($json_huditemeffectmeter_scout, true),
	"jsonfreezepanelbasic" => json_decode($json_freezepanelbasic, true),
	"jsonfreezepanelhealth" => json_decode($json_freezepanelhealth, true),
	"jsonmainmenuoverride" => json_decode($json_mainmenuoverride, true),
	"jsonhudobjectivetimepanel" => json_decode($json_hudobjectivetimepanel, true),
	"jsonhudtournament" => json_decode($json_hudtournament, true),
	"jsonhudtournamentsetup" => json_decode($json_hudtournamentsetup, true),
	"jsonspectator" => json_decode($json_spectator, true),
	"jsonspectatortournament" => json_decode($json_spectatortournament, true)
);

//echo $json_scoreboard;
//echo $json_scoreboard;

$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"] = [];
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"] = [];
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["name"] = "Crosshairs";
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["tall"] = "20";
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["weight"] = "0";
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["antialias"] = "1";
$result["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["outline"] = "0";

$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"] = [];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["controlName"] = "CExLabel";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["fieldName"] = "xHair";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["visible"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["enabled"] = "1";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["zpos"] = "2";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["xpos"] = "388";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["ypos"] = "189";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["wide"] = "100";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["tall"] = "100";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["font"] = "xHair";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["labelText"] = "h";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["textAlignment"] = "center";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["fgcolor"] = "xHairWhite";

$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"] = [];
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["ControlName"] = "CExImageButton";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["fieldName"] = "HealthBG";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["xpos"] = "240";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["ypos"] = "413";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["zpos"] = "-10";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["wide"] = "130";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["tall"] = "50";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["autoResize"] = "0";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["visible"] = "0";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["enabled"] = "1";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["defaultBgColor_Override"] = "QHUDBlank";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["PaintBackgroundType"] = "0";
$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HealthBG"]["textinsety"] = "99";


if (!isset($result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]) && isset($result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG"])) {
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"] = $result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG"];
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["fieldName"] = "TargetBGshade";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["ControlName"] = "CTFImagePanel";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["PaintBackgroundType"] = "1";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["fillcolor"] = "0 0 0 95";
	unset($result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG"]);
}

if (!isset($result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"])) {
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"] = [];
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["ControlName"] = "ImagePanel";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["fieldName"] = "TargetBGshade";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["xpos"] = "0";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["ypos"] = "0";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["zpos"] = "-10";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["wide"] = "500";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["tall"] = "22";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["autoResize"] = "0";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["pinCorner"] = "0";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["visible"] = "1";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["enabled"] = "1";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["fillcolor"] = "0 0 0 95";
	//$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["textAlignment"] = "north-west";
	$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["PaintBackgroundType"] = "1";
}

$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["fgcolor"]= "QHUDNormal";
$result["jsontargetid"]["Resource/UI/TargetID.res"]["SpectatorGUIHealth"]["TextColor"] = "QHUDNormal";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["fgcolor"] = "QHUDNormal";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["xpos"] = "0";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["ypos"] = "0";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["zpos"] = "7";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["wide"] = "f0";
$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["tall" ] = "480";

$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverheal"] = "6 146 255 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDNormal"] = "255 255 255 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLow"] = "255 49 49 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverhealBar"] = "6 146 255 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDBlank"] = "0 0 0 0";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLowBar"] = "255 49 49 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge1"] = "61 202 53 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge2"] = "19 165 12 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["xHairWhite"] = "255 255 255 255";
$result["jsonclientscheme"]["Scheme"]["Colors"]["xHairHit"] = "255 255 255 255";

//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDeathNotice"]["wide"] = "f0";
//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDeathNotice"]["tall"] = "480";

// ------------------------------------------------------------------------------------------//
if (isset($result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBar"])) {
	$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBarWP"] = $result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBar"];
	$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBarWP"]["fieldName"] = "ShadedBarWP";
	$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBar"]["xpos"] = "9999";
	$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBar"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabelScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabel"];
$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabelScoreboard"]["fieldName"] = "BlueTeamLabelScoreboard";
$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabel"]["xpos"] = "9999";
$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabel"]["visible"] = "0";
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScore"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScoreScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScore"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScoreScoreboard"]["fieldName"] = "BlueTeamScoreScoreboard";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScore"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScore"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabel"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabelScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabelScoreboard"]["fieldName"] = "RedTeamLabelScoreboard";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabel"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabel"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScore"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScoreScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScore"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScoreScoreboard"]["fieldName"] = "RedTeamScoreScoreboard";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScore"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScore"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLine"])) {
	if ($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLine"]["visible"] === "1") {
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLineScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLine"];
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLineScoreboard"]["fieldName"] = "VerticalLineScoreboard";
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLine"]["xpos"] = "9999";
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLine"]["visible"] = "0";
	}
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBar"])) {
	if ($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBar"]["visible"] === "1") {
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBarScoreboard"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBar"];
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBarScoreboard"]["fieldName"] = "ShadedBarScoreboard";
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBar"]["xpos"] = "9999";
		$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBar"]["visible"] = "0";
	}
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueBG"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreboardBG"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueBG"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreboardBG"]["fieldName"] = "BlueScoreboardBG";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueBG"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueBG"]["visible"] = "0";
}
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreBG"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreboardBG"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreBG"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreboardBG"]["fieldName"] = "BlueScoreboardBG";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreBG"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreBG"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedBG"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreboardBG"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedBG"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreboardBG"]["fieldName"] = "RedScoreboardBG";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedBG"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedBG"]["visible"] = "0";
}
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreBG"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreboardBG"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreBG"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreboardBG"]["fieldName"] = "RedScoreboardBG";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreBG"]["xpos"] = "9999";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreBG"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezePanel"])) { // fix for rayshud
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"] = $result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezePanel"];
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["fieldName"] = "PlayerStatusHealthValueFreezecam";
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezePanel"]["xpos"] = "9999";
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezePanel"]["visible"] = "0";
} else if (isset($result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValue"])) {
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"] = $result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValue"];
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["fieldName"] = "PlayerStatusHealthValueFreezecam";
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValue"]["xpos"] = "9999";
	$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValue"]["visible"] = "0";
}
// ------------------------------------------------------------------------------------------//
if ($result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller"]["xpos"] === "9999" && isset($result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller2"])) {
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["QHUDKillerName"] = $result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller2"];
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller2"]["xpos"] = "9999";
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller2"]["visible"] = "0";
} else {
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["QHUDKillerName"] = $result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller"];
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller"]["xpos"] = "9999";
	$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabelKiller"]["visible"] = "0";
}
$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["QHUDKillerName"]["fieldName"] = "QHUDKillerName";
// ------------------------------------------------------------------------------------------//
if (!isset($result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"])) {
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["ControlName"] = "CExLabel";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["fieldName"] = "BlueLabel";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["xpos"] = "367";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["ypos"] = "289";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["zpos"] = "2";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["wide"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["tall"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["autoResize"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["pinCorner"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["visible"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["enabled"] = "1";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["labelText"] = "BLU";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["textAlignment"] = "north";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["dulltext"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["brighttext"] = "1";
//	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["font"] = "m0refont14";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["font"] = $result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["font"];
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueLabel"]["fgcolor"] = "255 255 255 255";
}
// ------------------------------------------------------------------------------------------//
if (!isset($result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"])) {
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["ControlName"] = "CExLabel";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["fieldName"] = "RedLabel";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["xpos"] = "431";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["ypos"] = "289";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["zpos"] = "2";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["wide"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["tall"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["autoResize"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["pinCorner"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["visible"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["enabled"] = "1";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["labelText"] = "BLU";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["textAlignment"] = "north";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["dulltext"] = "0";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["brighttext"] = "1";
//	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["font"] = "m0refont14";
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["font"] = $result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["font"];
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedLabel"]["fgcolor"] = "255 255 255 255";
}
// ------------------------------------------------------------------------------------------//
/*if (!isset($result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"])) {
	$result["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]

	["ControlName"] = "CTFImagePanel";
	["fieldName"] = "blueframe";
	["xpos"]
	["ypos"]
	["zpos"] = "1";
	["wide"]
	["tall"]
	["autoResize"] = "0";
	["pinCorner"] = "0";
	["visible"] = "1";
	["enabled"] = "1";
	["image"] "../hud/color_panel_blu"
	"src_corner_height" "23"
	"src_corner_width" "23"
	"draw_corner_width" "0"
	"draw_corner_height" "0"

	"redframe"
	"RandomFrame"
	"SpectateFrame"

}*/
// ------------------------------------------------------------------------------------------//
if (!isset($result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"])) {
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["ControlName"] = "CExLabel";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["fieldName"] = "ServerTimeLimitLabel";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["xpos"] = "c-20";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["ypos"] = "60";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["zpos"] = "5";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["wide"] = "78";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["tall"] = "19";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["visible"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["enabled"] = "1";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["labelText"] = "%servertimeleft%";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["textAlignment"] = "center";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["dulltext"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["brighttext"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["wrap"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["font"] = "ClockSubText";
}
if (!isset($result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"])) {
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["ControlName"] = "CTFImagePanel";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["fieldName"] = "ServerTimeLimitLabelBG";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["xpos"] = "9999";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["ypos"] = "9999";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["wide"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["tall"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["visible"] = "0";
	$result["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["enabled"] = "1";
}
// ------------------------------------------------------------------------------------------//
if (!isset($result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"])) {
	$result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"] = $result["jsondisguisestatuspanel"]["Resource/UI/DisguiseStatusPanel.res"];
	unset($result["jsondisguisestatuspanel"]["Resource/UI/DisguiseStatusPanel.res"]);
}
// ------------------------------------------------------------------------------------------//
if (isset($result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["healthValue"]) && $result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["visible"] === "0") { // ellshud fix
	$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"] = $result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["healthValue"];
	$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["fieldName"] = "PlayerStatusHealthValue";
	$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["fgcolor"] = $result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["healthValue"]["fgColor"];
	unset($result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["healthValue"]);
}
// ------------------------------------------------------------------------------------------//

function getRealPositionX($canvaswidth, $tempx) {
	if (strpos($tempx,'c') !== false) {
		return strval(round((intval($canvaswidth)/2) + intval(str_replace("c","",$tempx))));
	} else if (strpos($tempx,'r') !== false) {
		return strval(round(intval($canvaswidth) - intval(str_replace("r","",$tempx))));
	} else {
		return strval($tempx);
	}
}

function getRealPositionY($canvasheight, $tempy) {
	if (strpos($tempy,'c') !== false) {
		return strval(round((intval($canvasheight)/2) + intval(str_replace("c","",$tempy))));
	} else if (strpos($tempy,'r') !== false) {
		return strval(round(intval($canvasheight) - intval(str_replace("r","",$tempy))));
	} else {
		return strval($tempy);
	}
}

function fixFontName(&$child) {
	if (isset($child["font"])) $param = "font";
	if (isset($child["TextFont"])) $param = "TextFont";

	if ($child[$param] === "default") {
		$child[$param] = "Default";
	} else if ($child[$param] === "defaultsmall") {
		$child[$param] = "DefaultSmall";
	} else if ($child[$param] === "Defaultsmall") {
		$child[$param] = "DefaultSmall";
	}
}

function fixTextAlignment(&$child, $result) {
	$fontname = "";
	if (isset($child["font"])) {
		$fontname = $child["font"];
	} else if (isset($child["TextFont"])) {
		$fontname = $child["TextFont"];
	}

//	if (!isset($result["jsonclientscheme"]["Scheme"]["Fonts"][$fontname])) {
//	if ($child["fieldName"] === "WeaponNameLabel") {
//		echo $child["fieldName"]."---".$child["font"];
//	}

	if (!isset($result["jsonclientscheme"]["Scheme"]["Fonts"][$fontname])) {
		$fontname = "Default";
	}
	$fontsize = $result["jsonclientscheme"]["Scheme"]["Fonts"][$fontname]["1"]["tall"];

	if ( strtolower($child["textAlignment"]) === "center" ) {
		$child["textAlignment"] = "north";
		$child["ypos"] = strval(round(intval($child["ypos"]) + (intval($child["tall"])/2) - (intval($fontsize)/2)));
	} else if ( strtolower($child["textAlignment"]) === "left" || strtolower($child["textAlignment"]) === "west" ) {
		$child["textAlignment"] = "north-west";
		$child["ypos"] = strval(round(intval($child["ypos"]) + (intval($child["tall"])/2) - (intval($fontsize)/2)));
	} else if ( strtolower($child["textAlignment"]) === "right" || strtolower($child["textAlignment"]) === "east" ) {
		$child["textAlignment"] = "north-east";
		$child["ypos"] = strval(round(intval($child["ypos"]) + (intval($child["tall"])/2) - (intval($fontsize)/2)));
	}
}

// might need to make an array of elements and do the fixing for each element
function fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result) {
	if ($parents !== "") {
		foreach ($parents as $test => &$parent) {

			// morghud fix
			if ( !isset($parent["ypos"]) && isset($parent["ypos_minmode"])) {
				$parent["ypos"] = $parent["ypos_minmode"];
			}
			
//			echo $parent["fieldName"]." - ".$parent["xpos"]."<br>";

			$tempx = $parent["xpos"];
			$tempy = $parent["ypos"];

			$yfix = "0";
			if ($parent["fieldName"] === "HudPlayerHealth" || $parent["fieldName"] === "HudWeaponAmmo") {
				$yfix = "-17";
			}
			
			if ($tempx !== "0" || $tempy !== $yfix) {
				$parent["xpos"] = "0";
				$parent["ypos"] = $yfix;
				$parent["wide"] = "f0";
				$parent["tall"] = "480";
			}

			foreach ($children as $key => &$child) {
				if (sizeof($child) > 1) {

					// broeselhud fix
					if ( $child["fieldName"] === "KillStreakMaxCountLabel" || $child["fieldName"] === "HorizontalLine2" ) {
						if (!isset($child["xpos"])) { $child["xpos"] = "0"; }
						if (!isset($child["ypos"])) { $child["ypos"] = "0"; }
					}

					$xpos = getRealPositionX($canvaswidth, $child["xpos"]);
					$ypos = getRealPositionY($canvasheight, $child["ypos"]);
					
					$child["xpos"] = strval(intval($xpos) + intval(getRealPositionX($canvaswidth, $tempx)));
					$child["ypos"] = strval(intval($ypos) - intval($yfix) + intval(getRealPositionY($canvasheight, $tempy)));
					if (isset($child["font"]) || isset($child["TextFont"])) {
						fixFontName($child);
					}
					if (isset($child["textAlignment"])) {
						fixTextAlignment($child, $result);
					}
//				} else {
//					$children[$key] = null;
//					unset($children[$key]);
//					$child = null;
//					var_dump($children);
				}
			}
		}
	} else {
		foreach ($children as &$child) {
			if (sizeof($child) > 1) {
				if (isset($child["font"]) || isset($child["TextFont"])) {
					fixFontName($child);
				}
				if (isset($child["textAlignment"])) {
					fixTextAlignment($child, $result);
				}
			}
		}
	}
}

////////////////////////	Disguise Status		////////////////////////////////
$children = [
	&$result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"]["DisguiseStatusBG"],
	&$result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"]["DisguiseNameLabel"],
	&$result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"]["WeaponNameLabel"],
	&$result["jsondisguisestatuspanel"]["Resource/UI/ItemModelPanel.res"]["SpectatorGUIHealth"]
];
$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["DisguiseStatus"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	HudObjectiveStatus - time	////////////////////////
$children = [ &$result["jsonhudobjectivestatus"]["Resource/UI/HudObjectiveStatus.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"] ];
$parents = [ &$result["jsonhudobjectivestatus"]["Resource/UI/HudObjectiveStatus.res"]["ObjectiveStatusTimePanel"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Player health value	////////////////////////////////
$children = [
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"],
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"],
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImageBG"],

	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusBleedImage"],
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMilkImage"],
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathImage"],
	&$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]
];
if (isset($result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"])) {
	$childrenfix = [ &$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"] ];
	$children = array_merge($children,$childrenfix);
}
$parents = [ &$result["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["HudPlayerHealth"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Player ammo value	////////////////////////////////
$children = [
	&$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClip"],
	&$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserve"],
	&$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]
];

if (isset($result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"])) {
	$childrenfix = [ &$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"] ];
	$children = array_merge($children,$childrenfix);
}
if (isset($result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserveShadow"])) {
	$childrenfix = [ &$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserveShadow"] ];
	$children = array_merge($children,$childrenfix);
}
if (isset($result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"])) {
	$childrenfix = [ &$result["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"] ];
	$children = array_merge($children,$childrenfix);
}

$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudWeaponAmmo"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Damage account	////////////////////////////////////
if (!isset($result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"])) {
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["ControlName"] = "CExLabel";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["fieldName"] = "DamageAccountValue";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["xpos"] = "250";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["ypos"] = "350";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["zpos"] = "2";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["wide"] = "100";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["tall"] = "50";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["visible"] = "1";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["enabled"] = "1";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["labelText"] = "%metal%";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["textAlignment"] = "north-west";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["fgcolor"] = "255 255 255 255";
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"]["font"] = $result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["delta_item_font"];
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"] = $result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"];
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"]["xpos"] = strval(intval($result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"]["xpos"]) + 2);
	$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"]["ypos"] = strval(intval($result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"]["ypos"]) + 2);
}
$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["CDamageAccountPanel"] ];
$children = [ &$result["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValue"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Demoman stickies counter	////////////////////////
$children = [ &$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["PipesPresentPanel"]["NumPipesLabel"] ];
$parents = [
	&$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["PipesPresentPanel"],
//	&$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanPipes"],
	&$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"]
];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
if (!isset($result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["PipesPresentPanel"]["NumPipesLabel"]["fgcolor"])) {
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["PipesPresentPanel"]["NumPipesLabel"]["fgcolor"] = "255 255 255 255";
}
//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanPipes"]["xpos"] = "0";
//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanPipes"]["ypos"] = "0";
//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanPipes"]["wide"] = "f0";
//$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanPipes"]["tall"] = "480";
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Charge meters - stickies/uber/bow	////////////////
// old huds fix (m0re hud 2009)
if (!isset($result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"])) {
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"] = [];
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["ControlName"] = "CExLabel";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["fieldName"] = "ChargeLabel";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["xpos"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["ypos"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["zpos"] = "2";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["wide"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["tall"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["autoResize"] = "1";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["pinCorner"] = "2";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["visible"] = "1";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["enabled"] = "1";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["tabPosition"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["labelText"] = "#TF_Charge";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["textAlignment"] = "north-west";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["dulltext"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["brighttext"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeLabel"]["font"] = "TFFontSmall";
}
if (!isset($result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"])) {
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"] = [];
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ControlName"] = "ContinuousProgressBar";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["fieldName"] = "ChargeMeter";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["font"] = "HudFontMediumBold";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["xpos"] = "365";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ypos"] = "415";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["zpos"] = "2";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["wide"] = "115";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["tall"] = "6";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["autoResize"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["pinCorner"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["visible"] = "1";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["enabled"] = "1";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["textAlignment"] = "north-west";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["dulltext"] = "0";
	$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["brighttext"] = "0";
}
$children = [
	&$result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"],
	&$result["jsonhuddemomancharge"]["Resource/UI/HudDemomanCharge.res"]["ChargeMeter"]
];
$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudMedicCharge"]["xpos"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["xpos"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudMedicCharge"]["ypos"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["ypos"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudMedicCharge"]["wide"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["wide"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudMedicCharge"]["tall"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["tall"];

$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudBowCharge"]["xpos"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["xpos"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudBowCharge"]["ypos"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["ypos"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudBowCharge"]["wide"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["wide"];
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudBowCharge"]["tall"] = $result["jsonhudlayout"]["Resource/HudLayout.res"]["HudDemomanCharge"]["tall"];

$result["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["xpos"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["xpos"];
$result["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["ypos"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ypos"];
$result["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["wide"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["wide"];
$result["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["tall"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["tall"];
$result["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["xpos"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["xpos"];
$result["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["ypos"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ypos"];
$result["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["wide"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["wide"];
$result["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["tall"] = $result["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["tall"];
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Charge meters - itemeffectmeter	////////////////////
$children = [ &$result["jsonhuditemeffectmeter"]["Resource/UI/HudItemEffectMeter.res"]["ItemEffectMeter"] ];
$parents = [ &$result["jsonhuditemeffectmeter"]["Resource/UI/HudItemEffectMeter.res"]["HudItemEffectMeter"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);

$children = [ &$result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"] ];
$parents = [ &$result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["HudItemEffectMeter"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
/*
$children = [
	&$result["jsonhuditemeffectmeter"]["Resource/UI/HudItemEffectMeter.res"]["ItemEffectMeter"],
	&$result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]
];
$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
*/
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"]["xpos"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"]["ypos"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"]["wide"] = "f0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["HudItemEffectMeter"]["tall"] = "480";

$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["xpos"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["xpos"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["ypos"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["ypos"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["wide"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["wide"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["tall"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["tall"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["HudItemEffectMeter"]["xpos"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["HudItemEffectMeter"]["xpos"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["HudItemEffectMeter"]["ypos"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["HudItemEffectMeter"]["ypos"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["HudItemEffectMeter"]["wide"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["HudItemEffectMeter"]["wide"];
$result["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["HudItemEffectMeter"]["tall"] = $result["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["HudItemEffectMeter"]["tall"];
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Killstreaks		////////////////////////////////////
if (!file_exists($folder."resource/ui/huditemeffectmeter_killstreak.res")) {
	$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCountKillstreak"] = $result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCount"];
	$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabelKillstreak"] = $result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabel"];
}

if (!isset($result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCountKillstreak"]["fgcolor_override"])) { $result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCountKillstreak"]["fgcolor_override"] = "255 255 255 255"; }
if (!isset($result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabelKillstreak"]["fgcolor_override"])) { $result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabelKillstreak"]["fgcolor_override"] = "255 255 255 255"; }

$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterBG"]["xpos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterBG"]["ypos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterBG"]["visible"] = "0";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeter"]["xpos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeter"]["ypos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeter"]["visible"] = "0";

$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCount"]["xpos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCount"]["ypos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCount"]["visible"] = "0";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabel"]["xpos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabel"]["ypos"] = "9999";
$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabel"]["visible"] = "0";
$children = [
	&$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterCountKillstreak"],
	&$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["ItemEffectMeterLabelKillstreak"]
];
$parents = [ &$result["jsonkillstreak"]["Resource/UI/HudItemEffectMeter_Demoman.res"]["HudItemEffectMeter"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////	TargetID	////////////////////////////////////////
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["xpos"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["wide"] = "f0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["tall"] = "480";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["xpos"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["wide"] = "f0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["tall"] = "480";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["xpos"] = "0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["wide"] = "f0";
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["tall"] = "480";
if (isset($result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueTarget"])) {
	$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"] = $result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueTarget"];
	$result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueShadow"] = $result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueTargetShadow"];
	unset($result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueTarget"]);
	unset($result["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValueTargetShadow"]);
}
$children = [
	&$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"],
	&$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"],
	&$result["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"],
	&$result["jsontargetid"]["Resource/UI/TargetID.res"]["SpectatorGUIHealth"]
];

$tempy = getRealPositionY($canvasheight, $result["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"]);
foreach ($children as &$child) {
	$ypos = getRealPositionY($canvasheight, $child["ypos"]);
	$child["ypos"] = strval(intval($ypos) + intval($tempy));
	if (isset($child["font"]) || isset($child["TextFont"])) {
		fixFontName($child);
	}
	if (isset($child["textAlignment"])) {
		fixTextAlignment($child, $result);
	}
}
$result["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"] = "0";
////////////////////////////////////////////////////////////////////////////////

////////////////////////	Class selection		////////////////////////////////
if (!isset($result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"])) {
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"] = [];
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["ControlName"] = "CTFImagePanel";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["fieldName"] = "BGBorder";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["xpos"] = "9999";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["ypos"] = "9999";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["zpos"] = "1";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["wide"] = "680";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["tall"] = "120";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["visible"] = "0";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["enabled"] = "1";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["image"] = "../hud/color_panel_brown";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["teambg_2"] = "../hud/color_panel_red";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["teambg_3"] = "../hud/color_panel_blu";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["scaleImage"] = "1";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["src_corner_height"] = "23";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["src_corner_width"] = "23";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["draw_corner_width"] = "0";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGBorder"]["draw_corner_height"] = "0";
}
if (!isset($result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"])) {
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"] = [];
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["ControlName"] = "CExLabel";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["fieldName"] = "TeamName";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["xpos"] = "9999";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["ypos"] = "9999";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["zpos"] = "3";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["wide"] = "136";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["tall"] = "24";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["visible"] = "0";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["enabled"] = "1";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["labelText"] = "Select Class";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["textAlignment"] = "north-west";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["font"] = "Default";
	$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["teamname"]["fgcolor"] = "255 255 255 215";
}
$children = [
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["random"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["scout"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["soldier"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["pyro"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["demoman"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["heavyweapons"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["engineer"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["medic"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["sniper"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["spy"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numScout"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numSoldier"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numPyro"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numDemoman"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numHeavy"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numEngineer"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numMedic"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numSniper"],
	&$result["jsonclassselection"]["Resource/UI/ClassSelection.res"]["numSpy"]
];
foreach ($children as &$child) {
	if (isset($child["font"]) || isset($child["TextFont"])) {
		fixFontName($child);
	}
}
////////////////////////////////////////////////////////////////////////////////

////////////////////////////	Freeze panel	////////////////////////////////
$tempx = getRealPositionX($canvaswidth, $result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["xpos"]);
$tempy = getRealPositionY($canvasheight, $result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["ypos"]);
$xpos = getRealPositionX($canvaswidth, $result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["xpos"]);
$ypos = getRealPositionY($canvasheight, $result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["ypos"]);
$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["xpos"] = strval(intval($xpos) + intval($tempx));
$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]["ypos"] = strval(intval($ypos) + intval($tempy));
$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["xpos"] = "0";
$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["ypos"] = "0";
$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["wide"] = "f0";
$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelHealth"]["tall"] = "480";
////////////////////////////////////////////////////////////////////////////////
$children = [
	&$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelBG"],
	&$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezeLabel"],
	&$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["QHUDKillerName"],
	&$result["jsonfreezepanelhealth"]["Resource/UI/FreezePanelKillerHealth.res"]["PlayerStatusHealthValueFreezecam"]
];

if (isset($result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelBGTitle"])) {
	$children[] = &$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelBGTitle"];
}

$parents = [
	&$result["jsonhudlayout"]["Resource/HudLayout.res"]["FreezePanel"],
	&$result["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]
];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////	Win panel	////////////////////////////////
$children = [
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamLabel"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamScore"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamLabel"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamScore"]
];
$parents = [
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]
];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);

$children = [
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamLabel"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamScore"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamLabel"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamScore"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinningTeamLabel"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["ShadedBarWP"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player1Name"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player1Class"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player1Score"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player2Name"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player2Class"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player2Score"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player3Name"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player3Class"],
	&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["Player3Score"]
];
if (isset($result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakLeaderLabel"])) {
	$childrenfix = [
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakLeaderLabel"],
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakMaxCountLabel"],
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["HorizontalLine2"],
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakPlayer1Name"],
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakPlayer1Class"],
		&$result["jsonwinpanel"]["Resource/UI/winpanel.res"]["KillStreakPlayer1Score"]
	];
	$children = array_merge($children,$childrenfix);
}
$parents = [
	&$result["jsonhudlayout"]["Resource/HudLayout.res"]["WinPanel"]
];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////	Scoreboard	////////////////////////////////
/*
$children = [
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueScoreboardBG"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedScoreboardBG"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MainBG"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamLabelScoreboard"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScoreScoreboard"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamPlayerCount"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamLabelScoreboard"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScoreScoreboard"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamPlayerCount"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ServerLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ServerTimeLeft"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["VerticalLineScoreboard"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["ShadedBarScoreboard"],

	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BluePlayerList"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedPlayerList"],

	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusLabel"],

	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"],

	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Spectators"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["SpectatorsInQueue"],
];
$parents = [ &$result["jsonhudlayout"]["Resource/HudLayout.res"]["ScorePanel"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
*/

if (!isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"])) {
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["ControlName"] = "EditablePanel";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["fieldName"] = "LocalPlayerStatsPanel";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["textAlignment"] = "north-west";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["xpos"] = "0";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["ypos"] = "0";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["zpos"] = "3";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["wide"] = "f0";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["tall"] = "480";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["autoResize"] = "0";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["pinCorner"] = "0";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["visible"] = "1";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["enabled"] = "1";
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusLabel"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"] = [];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["KillsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DeathsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["AssistsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DestructionLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Kills"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Deaths"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MapName"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["GameType"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Assists"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Destruction"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["CapturesLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DefensesLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DominationLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RevengeLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Captures"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Defenses"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Domination"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Revenge"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["HealingLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["InvulnLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["TeleportsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["HeadshotsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Healing"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Invuln"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Teleports"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Headshots"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BackstabsLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Backstabs"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusLabel"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BonusLabel"];
	$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"] = $result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Bonus"];
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["KillsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DeathsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["AssistsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DestructionLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Kills"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Deaths"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MapName"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["GameType"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Assists"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Destruction"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["CapturesLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DefensesLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["DominationLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RevengeLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Captures"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Defenses"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Domination"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Revenge"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["HealingLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["InvulnLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["TeleportsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["HeadshotsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Healing"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Invuln"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Teleports"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Headshots"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BackstabsLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Backstabs"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BonusLabel"]);
	unset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["Bonus"]);
}
$children = [
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["LocalPlayerStatsPanel"]["RevengeLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusLabel"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"],
	&$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"]
];
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsShadow"])) {
	$childrenfix = [ &$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsShadow"] ];
	$children = array_merge($children,$childrenfix);
}
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsShadow"])) {
	$childrenfix = [ &$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsShadow"] ];
	$children = array_merge($children,$childrenfix);
}
if (isset($result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsShadow"])) {
	$childrenfix = [ &$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsShadow"] ];
	$children = array_merge($children,$childrenfix);
}
$parents = [ &$result["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"] ];
fixElementPosition($parents, $children, $canvaswidth, $canvasheight, $result);
////////////////////////////////////////////////////////////////////////////////



// extract fonts
$arraysize = sizeof($result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]);
$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"][$arraysize+1] = [];
$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"][$arraysize+1]["font"] = "resource/crosshairs.otf";
$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"][$arraysize+1]["name"] = "Crosshairs";
for ($i = 3; $i <= $arraysize; $i++) {
	$fontname = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["name"];
	if ($fontname !== "TF2" && $fontname !== "TF2 Secondary" && $fontname !== "TF2 Professor" && $fontname !== "TF2 Build") {
		$fontpath_old = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"];
		$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"] = preg_replace('|resource(.*)/|','resource/customfonts/',$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"]);
		$fontpath_new = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"];
		$fontpath = $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"];
		copy($folder.$fontpath_old, "../".$fontpath_new);
	} else {
		$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"] = str_replace('.ttf','.otf',$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"]);
	}
// GarmenHUD fix
	if ($result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["font"] === "resource/customfonts/HelveticaNeueLT-BoldExt.otf" && $result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["name"] === "HelveticaNeueLT-BoldExt") {
		$result["jsonclientscheme"]["Scheme"]["CustomFontFiles"]["$i"]["name"] = "Helvetica Neue LT";
		
	}
}

// output

//echo $json_hudobjectivestatus;
//echo $json_hudlayout;
//echo $json_mainmenuoverride;
//echo $json_freezepanelbasic;
//echo $json_scoreboard;
//echo $json_teammenu;
//echo $json_clientscheme;
//echo $json_classselection;
//echo $json_hudtournament;
//echo $json_huddamageaccount;

echo json_encode($result);

?>