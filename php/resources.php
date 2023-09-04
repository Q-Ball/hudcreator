<?php

//$element = preg_replace("/[^A-Za-z0-9-_]/", "", $_GET['element']);

$hudlayout = file_get_contents('../scripts/hudlayout.res');
$hudplayerhealth = file_get_contents('../resource/ui/HudPlayerHealth.res');
$hudammoweapons = file_get_contents('../resource/ui/HudAmmoWeapons.res');
$clientscheme = file_get_contents('../resource/ClientScheme.res');
$huddemomanpipes = file_get_contents('../resource/ui/HudDemomanPipes.res');
$huddemomancharge = file_get_contents('../resource/ui/HudDemomanCharge.res');
$hudobjectivestatus = file_get_contents('../resource/ui/HudObjectiveStatus.res');
$huddamageaccount = file_get_contents('../resource/ui/HudDamageAccount.res');
$hudmediccharge = file_get_contents('../resource/ui/HudMedicCharge.res');
$huditemeffectmeter = file_get_contents('../resource/ui/HudItemEffectMeter.res');
$hudbowcharge = file_get_contents('../resource/ui/HudBowCharge.res');
$hudaccountpanel = file_get_contents('../resource/ui/HudAccountPanel.res');
$targetid = file_get_contents('../resource/ui/TargetID.res');
$classselection = file_get_contents('../resource/ui/ClassSelection.res');
$teammenu = file_get_contents('../resource/ui/Teammenu.res');
$winpanel = file_get_contents('../resource/ui/winpanel.res');
$scoreboard = file_get_contents('../resource/ui/ScoreBoard.res');
$disguisestatuspanel = file_get_contents('../resource/ui/DisguiseStatusPanel.res');
$kothtimepanel = file_get_contents('../resource/ui/HudObjectiveKothTimePanel.res');
$stopwatch = file_get_contents('../resource/ui/HudStopWatch.res');
$killstreak = file_get_contents('../resource/ui/huditemeffectmeter_killstreak.res');
$spectatorguihealth = file_get_contents('../resource/ui/SpectatorGUIHealth.res');
$huditemeffectmeter_spyknife = file_get_contents('../resource/ui/HudItemEffectMeter_SpyKnife.res');
$huditemeffectmeter_scout = file_get_contents('../resource/ui/HudItemEffectMeter_Scout.res');
$freezepanelbasic = file_get_contents('../resource/ui/FreezePanel_Basic.res');
$freezepanelhealth = file_get_contents('../resource/ui/FreezePanelKillerHealth.res');
$mainmenuoverride = file_get_contents('../resource/ui/MainMenuOverride.res');
$hudobjectivetimepanel = file_get_contents('../resource/ui/HudObjectiveTimePanel.res');
$hudtournament = file_get_contents('../resource/ui/HudTournament.res');
$hudtournamentsetup = file_get_contents('../resource/ui/HudTournamentSetup.res');
$spectator = file_get_contents('../resource/ui/Spectator.res');
$spectatortournament = file_get_contents('../resource/ui/SpectatorTournament.res');
$hudmatchstatus = file_get_contents('../resource/ui/HudMatchStatus.res');


function res2json($string) {

	$string = str_replace("\t","",$string); // remove tabs
	$string = preg_replace("|\n([A-Za-z])([^\"]*?)([A-Za-z0-9])\r\n|","\n\"$1$2$3\"\n",$string); // add quotes around single words
//	$string = str_replace("\r\n\r\n","\r\n",$string); // remove extra \n

	do { $string = preg_replace('|\r\n\r\n|',"\r\n", $string, -1, $count); }
	while ($count > 0);

//	$string = preg_replace('|\/\/(.*)|', '', $string); // remove comments
	$string = preg_replace('|"(.*?){|ism', '"$1: {', $string); // add : between " and {
	$string = preg_replace('|"(.*?)"(.*?)"(.*?)"|', ',"$1":"$3"', $string); // add : between parameter and value
	$string = preg_replace('|: {(.*?),"|is', ': { $1"', $string); // remove extra , at the end
	$string = preg_replace('|}(.*?)"|is', '} $1, "', $string); // add , after }
	$string = str_replace('"2":"resource/tfd.otf"','"2":"resource/tfd.otf", ',$string); // fix for clientscheme.res
	$string = preg_replace("|\"\n\"|is","\"\n,\"",$string); // add , before some nested elements like model
	$string = preg_replace("|\"\r\n\"|is","\"\n,\"",$string); // add , before some nested elements like model
	$string = preg_replace("|,\"if_mvm\"\r\n: { \r\n\"visible\":\"1\"\r\n} \r\n}\r\n}\r\n, \"MapLabel\"\r\n: { |is",",\"if_mvm\"\r\n: { \r\n\"visible\":\"1\"\r\n}\r\n}\r\n, \"MapLabel\"\r\n: { ",$string); // fix for broken spectatortournament.res
	$string = str_replace("http //", "http:--", $string); // http links fix
	$string = preg_replace("|\/\/(.*?)\n|", "\n", $string); // remove comments
	$string = str_replace("http:--", "http://", $string); // http links fix

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

//echo $json_spectatortournament;

// http://stackoverflow.com/questions/4381352/find-path-of-a-key-in-a-multi-dimensional-tree-like-array
/*function getkeypath($arr, $lookup) {
	if (array_key_exists($lookup, $arr)) {
		return array($lookup);
	}
	else {
		foreach ($arr as $key => $subarr) {
			if (is_array($subarr)) {
				$ret = getkeypath($subarr, $lookup);
				if ($ret) {
					$ret[] = $key;
					return $ret;
				}
			}
		}
	}
	return null;
}

if ( $element != "font" ) {
	$result["elementpath"] = array_reverse( getkeypath( $result, $element ) );
}
*/
echo json_encode($result);

?>