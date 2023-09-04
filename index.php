<?php

session_start();
include "./php/openid.php";
$steamapikey = "";
$domain = '';
$location = 'index.php';

$OpenID = new LightOpenID($domain);
if (!$OpenID->mode) {
	if(isset($_GET['login'])){
		$OpenID->identity = "http://steamcommunity.com/openid";
		header("Location: {$OpenID->authUrl()}");
	}

	if(!isset($_SESSION['T2SteamAuth'])) {
		$login = '<div id="login"><a href="?login"><img style="padding-top:8px;padding-right:8px;" src="http://cdn.steamcommunity.com/public/images/signinthroughsteam/sits_small.png"/></a></div>';
		$savetodb0 = '';
		$savetodb1 = '';
		$savetodb2 = '';
	}

} elseif($OpenID->mode == "cancel"){
	echo "User has canceled Authenticiation.";
} else {
	if(!isset($_SESSION['T2SteamAuth'])){
		$_SESSION['T2SteamAuth'] = $OpenID->validate() ? $OpenID->identity : null;
		$_SESSION['T2SteamID64'] = str_replace("http://steamcommunity.com/openid/id/", "", $_SESSION['T2SteamAuth']);
		if ($_SESSION['T2SteamAuth'] !== null){
			$Steam64 = str_replace("http://steamcommunity.com/openid/id/", "", $_SESSION['T2SteamAuth']);
			$profile = file_get_contents("http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key={$steamapikey}&steamids={$Steam64}");
			$buffer = fopen("./cache/{$Steam64}.json", "w+");
			fwrite($buffer, $profile);
			fclose($buffer);
		}
		header("Location: $location");
	}
}

$steam = json_decode(file_get_contents("./cache/{$_SESSION['T2SteamID64']}.json"));
$_SESSION['playersteamid64'] = $playersteamid = $steam->response->players[0]->steamid;
$playernickname = $steam->response->players[0]->personaname;
$profileurl = $steam->response->players[0]->profileurl;
$avatar = "<img src=\"{$steam->response->players[0]->avatar}\"/>";

if (isset($_SESSION['T2SteamAuth'])) {
	$login = '
	<div id="login">
	<div id="profile-left">'.$avatar.'</div>
	<div id="profile-right">
	<a data-dropdown="#dropdown" id="myProfile" class="classicButton">Profile &#9660;</a>
	</div>
	</div>';

	$savetodb0 = '
	//**********************************************//
	//			Save HUD to database button			//
	//**********************************************//
	$( "#saveToDBButton" ).button().click(function() {
		$("#dialog-newhudname").dialog("open");
	});
	$( "#saveToDBDialogOk" ).button().click(function() {
		var newname = $("#newhudname").val();
		if (newname !== "") {
			$("#loaderlogo").text("Saving your HUD to database, please wait...");
			$("#dialog-newhudname").dialog("close");
			$("#loaderwrapper").show();
			autoSave();

			html2canvas($(".hud-canvas"), {
				onrendered: function(canvas) {
					var imageCanvas = canvas.toDataURL("image/png");
					$.ajax({
						type: "POST",
						async: false,
						url: "./php/savetodb.php",
						data: { data : JSON.stringify(data), newhudname : newname, image : imageCanvas },
						success: function(incdata) {
							alert("HUD saved successfully.");
							location.reload();
							//$("#loaderwrapper").hide();
						}
					});
				}
			});

		} else {
			alert("The name field is empty. Please pick a name for your HUD first.");
		}
	});
	$( "#saveToDBDialogCancel" ).button().click(function() {
		$("#dialog-newhudname").dialog("close");
	});

	$("#dialog-hudslist").on("click","a",function(){
		$("#loaderlogo").text("Loading selected HUD, please wait...");
		$("#dialog-hudslist").dialog("close");
		$("#loaderwrapper").show();
		var name = $(this).attr("id").replace("Ahref","");
		var hudslist = JSON.parse(localStorage.hudslist);
		data = JSON.parse(hudslist[name]["data"]);
		localStorage.resourcedata = JSON.stringify(data);
		autoSave();
		alert("HUD has been loaded successfully.");
		location.reload();
	});

	$("#dialog-hudslist").on("click",".delete",function(){
		var name = $(this).attr("id").replace("dbrem-","");
		$("#loaderlogo").text("Removing your HUD from database, please wait...");
		$("#dialog-hudslist").dialog("close");
		$("#loaderwrapper").show();
		$.ajax({
			type: "POST",
			async: false,
			url: "./php/removefromdb.php",
			data: { hudname : name },
			success: function(incdata) {
				alert("HUD has been successfully removed.");
				$("#loaderwrapper").hide();
			}
		});
	});

	$("#dialog-hudslist").on("click",".share",function(){
		var temphid = $(this).attr("hid");
		var tempuid = $(this).attr("uid");
		function copyToClipboard(text) {
		  window.prompt("Copy to clipboard: Ctrl+C, Enter", text);
		}
		copyToClipboard("http://q-ball.io/hudcreator?uid="+tempuid+"&hid="+temphid);
	});

	$("body").on("click","#hudsList",function(){
		$.ajax({
			type: "POST",
			async: false,
			url: "./php/gethudlist.php",
			success: function(incdata) {
				function IsJsonString(str) {
					try { JSON.parse(str);}
					catch (e) { return false; }
					return true;
				}
				if (IsJsonString(incdata)) {
					localStorage.hudslist = incdata;
					var hudslist = JSON.parse(localStorage.hudslist);
					var templist = "<table style=\"width:100%;\">";
					$.each( hudslist, function( key, value ) {
						templist = templist + "<tr style=\"line-height:30px;padding-left:10px;border-bottom: 1px dotted #CFCFCF;\"><td><img style=\"width:100px;height:67px;\" src=\""+hudslist[key]["image"]+"\"/></td><td><a id=\""+key+"Ahref\">"+key+"</a></td><td><button class=\"delete\" id=\"dbrem-"+key+"\"></button><button class=\"share\" uid=\""+hudslist[key]["uid"]+"\" hid=\""+hudslist[key]["hid"]+"\"></button></td></tr>";
						$("#dialog-hudslist").html(templist);
					});
					templist = templist + "</table>";
				} else {
					$("#dialog-hudslist").html("No HUDs found.");
				}
			}
		});
		$("#dialog-hudslist").dialog("open");
	});';


	$savetodb1 = '
	<div id="dialog-newhudname" title="Save HUD">
		<label>New HUD name:</label><input style="margin-left:15px;" type="text" id="newhudname" class="text ui-widget-content ui-corner-all"/>
		<div style="text-align:center;margin-top:10px;">
			<button id="saveToDBDialogOk" class="classicButton">Ok</button>
			<button id="saveToDBDialogCancel" class="classicButton">Cancel</button>
		</div>
	</div>

	<div id="dialog-hudslist" title="Your HUDs list"></div>

	<div id="dropdown" class="dropdown dropdown-tip">
		<ul class="dropdown-menu">
			<li><a id="hudsList">My HUDS</a></li>
			<li><a href="?logout">Logout</a></li>
		</ul>
	</div>';

	$savetodb2 = '
	<button id="saveToDBButton" class="classicButton custom-icon icon-save">Save HUD</button>';

}

if (isset($_GET['logout'])) {
	unset($_SESSION['T2SteamAuth']);
	unset($_SESSION['T2SteamID64']);
	header("Location: $location");
}

?>


<!DOCTYPE html>
<html>

<title>hudcreator - TF2 Online HUD Creator</title>
<head>

<link rel="icon" type="image/png" href="../hudcreator/images/icons/favicon.ico">
<link href='./css/flick/jquery-ui-1.10.4.custom.css' rel='stylesheet'/>
<link href="./css/hudeditor.css" rel="stylesheet">
<link href='./css/spectrum.css' rel='stylesheet'/>
<link href='./css/dropdown.css' rel='stylesheet'/>
<style type="text/css">@font-face { font-family: 'TF2Glyphs'; src: url('images/icons/tf2_glyphs.otf') format('opentype'); }</style>

<meta name="description" content="hudcreator - Online HUD creation tool which allows you to create your own Team Fotress 2 HUDs" />

<script src='./js/jquery-1.10.2.js' type='text/javascript'></script>
<script src='./js/spectrum.js' type='text/javascript'></script>
<script src='./js/jquery-ui-1.11.0.min.js' type='text/javascript'></script>
<script src='./js/jquery.uploadfile.js' type='text/javascript'></script>
<script src='./js/jquery.dropdown.js' type='text/javascript'></script>
<script src='./js/html2canvas.js' type='text/javascript'></script>

<script>
$(function() {

// "sv_cheats 1"
// "vgui_drawtree 1"
// https://github.com/SteamDatabase/GameTracking-TF2/tree/master/tf/tf2_misc_dir

// Done
// Fixed after the latest update
// Fixed hud uploader not working with certain huds
// Fixed item notifications icon
// Fixed main menu background size

// To-do
// fix input fields width
// find out why koth timers are not working - zpos - CHECK
// add more shadows

// Converter:
// winpanel
// charge meters / sticky counter
// freezecam
// Main menu either not editable or default

	var data;
	var shadowSize;
	var shadowColor;

//	var team1_player_delta_x = 0;
//	var team1_player_delta_y = 30;

	function getid(url,id) {
		id = id.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
		var regexS = "[\\?&]"+id+"=([^&#]*)";
		var regex = new RegExp( regexS );
		var results = regex.exec( url );
		if (results == null) { return ""; }
		else { return results[1]; }
	}

	if (getid(document.URL,"reset") === "1") {
		localStorage.reset = "true";
	}

	if (getid(document.URL,"hid") && getid(document.URL,"uid")) {
		var hid = getid(document.URL,"hid");
		var uid = getid(document.URL,"uid");
		$.ajax({
			type: "POST",
			async: false,
			url: "./php/share.php",
			data: { uid : uid, hid : hid },
			success: function(incdata) {
				function IsJsonString(str) {
					try { JSON.parse(str);}
					catch (e) { return false; }
					return true;
				}
				if (IsJsonString(incdata)) {
					data = JSON.parse(incdata);
					localStorage.resourcedata = JSON.stringify(data);
					autoSave();
					alert("HUD has been loaded successfully.");
//					location.reload();
				} else {
					alert("Something went wrong while accessing shared HUD.");
					console.log(incdata);
				}
			}
		});
	}


//**********************************************************//
//				Core elements initialization				//
//**********************************************************//
	$("#loaderlogo").text("Loading, please wait...");
	$( document ).tooltip({ track: true, delay: 100 });
	$("#sortable").sortable({ disabled: true });
	$("#dialog-newhudname").dialog({ autoOpen: false });
	$("#dialog-hudslist").dialog({ autoOpen: false });
	$("#credits-dialog").dialog({ autoOpen: false });
	$("#donations-dialog").dialog({ autoOpen: false });
	$("#outofdate-dialog").dialog({ autoOpen: true });
	$("#slider-fontsize").slider();
	$("#dialog-form").dialog({
		autoOpen: false,
		height: 250,
		width: 300,
		modal: true,
		buttons: {
			"Ok": function() {
				var canvaswidthtemp = $( "#canvas-width" ).val();
				var canvasheighttemp = $( "#canvas-height" ).val();
				
				var coeff = canvaswidthtemp/canvasheighttemp;
				var canvasheight = localStorage.canvasheight = 480;
				var canvaswidth = localStorage.canvaswidth = canvasheight * coeff;
 
				$(".hud-canvas").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-classmenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-teammenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-winpanel").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-scoreboard").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-freezecam").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-mainmenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
/*				$(".hud-canvas-tournamentsetup").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$(".hud-canvas-spectator").height( canvasheight + "px" ).width( canvaswidth + "px" );*/
				$(".hud-canvas-misclabels").height( canvasheight + "px" ).width( canvaswidth + "px" );
				$("#grid").height( canvasheight + "px" ).width( canvaswidth + "px" );

				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		},
		close: function() {
//			allFields.val( "" ).removeClass( "ui-state-error" );
		}
	});

	function showCanvas(name) {
		$("."+name).show();
		$.each($("#canv-region").children(), function(i, v) {
			if ($(this).attr("class") !== "grid") {
				if ($(this).attr("class") !== name) {
					$(this).hide();
				}
			}
		});
	}

	$( "#visibleCanvas" ).selectmenu({
		position: { my : "left", at: "left-24 top-122" },
		select: function( event, ui ) {
			var itemname = ui["item"]["element"]["0"]["id"];
			if (itemname === "showMainScreen") {
				showCanvas("hud-canvas");
			} else if (itemname === "showClassmenu") {
				showCanvas("hud-canvas-classmenu");
			} else if (itemname === "showTeammenu") {
				showCanvas("hud-canvas-teammenu");
			} else if (itemname === "showWinPanel") {
				showCanvas("hud-canvas-winpanel");
			} else if (itemname === "showScoreboard") {
				showCanvas("hud-canvas-scoreboard");
			} else if (itemname === "showFreezecam") {
				showCanvas("hud-canvas-freezecam");
			} else if (itemname === "showMainmenu") {
				showCanvas("hud-canvas-mainmenu");
/*			} else if (itemname === "showTournamentSetup") {
				showCanvas("hud-canvas-tournamentsetup");
			} else if (itemname === "showSpectator") {
				showCanvas("hud-canvas-spectator");*/
			} else if (itemname === "showMiscLabels") {
				showCanvas("hud-canvas-misclabels");
			}
		}
	});

	$("#canv-region").on("mousedown", function (event) {
		if ( event.target.className === "hud-canvas" || event.target.className === "hud-canvas-classmenu" || event.target.className === "hud-canvas-teammenu" || event.target.className === "hud-canvas-winpanel" || event.target.className === "hud-canvas-scoreboard" || event.target.className === "hud-canvas-freezecam" || event.target.className === "hud-canvas-mainmenu" || event.target.className === "grid" || event.target.className === "hud-canvas-tournamentsetup" || event.target.className === "hud-canvas-spectator" || event.target.className === "hud-canvas-misclabels") {
			$('#canv-region *').removeClass('selected'); // remove selected class from all elements
			$('#sortable *').removeClass('sortableSelected');
//			$(".region-sidebar-content").animate({scrollTop :0}, 0,function(){
			$(".region-sidebar-content").scrollTop(0);
			$("#hudElementParameters").hide();
//			});
		} else if ( event.target.id === "targetidwrapper" && $('#targetidwrapper').children().length === 0 ) {
			$('#canv-region *').removeClass('selected'); // remove selected class from all elements
			$('#sortable *').removeClass('sortableSelected');
//			$(".region-sidebar-content").animate({scrollTop :0}, 0,function(){
			$(".region-sidebar-content").scrollTop(0);
			$("#hudElementParameters").hide();
//			});
		}
	});

	function getFGColorString(fgcolor) {
		var fgcolornumber = "";
		if (fgcolor.match(/^[\d\s+-]+$/)) { fgcolornumber = fgcolor; } else { fgcolornumber = data["jsonclientscheme"]["Scheme"]["Colors"][fgcolor]; }
		return fgcolornumber;
	}

//**********************************************************//
//				Get data from current session				//
//**********************************************************//
	if (localStorage.canvaswidth && localStorage.canvasheight) {
		var canvasheight = localStorage.canvasheight;
		var canvaswidth = localStorage.canvaswidth;

		$(".hud-canvas").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-classmenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-teammenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-winpanel").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-scoreboard").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-freezecam").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-mainmenu").height( canvasheight + "px" ).width( canvaswidth + "px" );
/*		$(".hud-canvas-tournamentsetup").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$(".hud-canvas-spectator").height( canvasheight + "px" ).width( canvaswidth + "px" );*/
		$(".hud-canvas-misclabels").height( canvasheight + "px" ).width( canvaswidth + "px" );
		$("#grid").height( canvasheight + "px" ).width( canvaswidth + "px" );

	} else {
		$( "#dialog-form" ).dialog( "open" );
	}

	function getDataFromRes() {
		data = function() {
			var tmp = null;
			$.ajax({
				type: 'GET',
				async: false,
				url: './php/resources.php',
				dataType: 'json',
				success: function(json) {
					tmp = json;
					$('#loaderwrapper').hide();
				}
			});
			return tmp;
		}();
	}

	if (localStorage.reset === "false") {
		$.each( JSON.parse(localStorage.resourcedata), function( key, value ) {
			if (!JSON.parse(localStorage.resourcedata)[key]){
				console.log("There is a problem with "+key);
				//console.log(JSON.parse(localStorage.resourcedata));
			}
			if ( JSON.parse(localStorage.resourcedata)[key] !== null ) {
				if ( JSON.parse(localStorage.resourcedata)[key]["length"] === 0 ) {
					localStorage.reset = "true";
				}
			} else {
				console.log("The "+key+" is NULL");
			}
		});
	}

	if (localStorage.reset == "true") {
		localStorage.reset = "false";
		localStorage.removeItem(canvasstate);
		localStorage.removeItem(canvasstatetargetid);
		localStorage.removeItem(canvasstateinnerid);
		localStorage.removeItem(canvasstateclassmenu);
		localStorage.removeItem(canvasstateteammenu);
		localStorage.removeItem(canvasstatewinpanel);
		localStorage.removeItem(canvasstatescoreboard);
		localStorage.removeItem(canvasstatefreezecam);
		localStorage.removeItem(canvasstatemainmenu);
/*		localStorage.removeItem(canvasstatetournamentsetup);
		localStorage.removeItem(canvasstatespectator);*/
		localStorage.removeItem(canvasstatemisclabels);
		getDataFromRes();
		// Get shadow offset from resources

/*		if ( typeof data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"] != 'undefined' ) {
			yposhealthvalue = data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["ypos"];
			yposhealthvalueshadow = data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["ypos"];
			if ( yposhealthvalue.indexOf("c") > -1 ) { yposhealthvalue = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposhealthvalue.replace("c","")))); }
			else if ( yposhealthvalue.indexOf("r") > -1 ) { yposhealthvalue = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposhealthvalue.replace("r","")))); }
			if ( yposhealthvalueshadow.indexOf("c") > -1 ) { yposhealthvalueshadow = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposhealthvalueshadow.replace("c","")))); }
			else if ( yposhealthvalueshadow.indexOf("r") > -1 ) { yposhealthvalueshadow = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposhealthvalueshadow.replace("r","")))); }
			shadowSize = Number(yposhealthvalueshadow) - Number(yposhealthvalue);
			if ( typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] == 'undefined' ) {
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = "0 0 0 255";
			}
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["fgcolor"] = "QHUDShadow";
			var shadowColorTemp = getFGColorString(data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["fgcolor"]).split(' ');
			shadowColor = "rgba("+shadowColorTemp[0]+","+shadowColorTemp[1]+","+shadowColorTemp[2]+"," +(Math.round((Number(shadowColorTemp[3])*1.0/255) * 100) / 100)+ ")";
		} else {
			shadowSize = 2;
			shadowColor = "rgba(0, 0, 0, 1.0)";
		}*/

		if ( typeof data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"] != 'undefined' ) {
			yposammovalue = data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClip"]["ypos"];
			yposammovalueshadow = data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["ypos"];
			if ( yposammovalue.indexOf("c") > -1 ) { yposammovalue = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposammovalue.replace("c","")))); }
			else if ( yposammovalue.indexOf("r") > -1 ) { yposammovalue = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposammovalue.replace("r","")))); }
			if ( yposammovalueshadow.indexOf("c") > -1 ) { yposammovalueshadow = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposammovalueshadow.replace("c","")))); }
			else if ( yposammovalueshadow.indexOf("r") > -1 ) { yposammovalueshadow = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposammovalueshadow.replace("r","")))); }
			shadowSize = Number(yposammovalueshadow) - Number(yposammovalue);
			if ( typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] == 'undefined' ) {
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = "0 0 0 255";
			}
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["fgcolor"] = "QHUDShadow";
			var shadowColorTemp = getFGColorString(data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["fgcolor"]).split(' ');
			shadowColor = "rgba("+shadowColorTemp[0]+","+shadowColorTemp[1]+","+shadowColorTemp[2]+"," +(Math.round((Number(shadowColorTemp[3])*1.0/255) * 100) / 100)+ ")";
		} else {
			shadowSize = 2;
			shadowColor = "rgba(0, 0, 0, 1.0)";
		}

		$("#shadowoffset").val(shadowSize);
		$('#shadowOverallColor').val(shadowColor);
//		$('#colorpicker-shadowOverallColor').spectrum("set", shadowColor);
		$('#colorpicker-shadowOverallColor').spectrum("set", $('#shadowOverallColor').val());
		$('#colorpicker-shadowOverallColor')["0"]["value"] = $('#shadowOverallColor').val();

		autoSave();
	} else {
		if (localStorage.resourcedata) {
			$('#loaderwrapper').show();
			data = JSON.parse(localStorage.resourcedata);
			
			
			JSON.parse(localStorage.resourcedata)

			// Get shadow offset from resources
/*			if ( typeof data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"] != 'undefined' ) {
				yposhealthvalue = data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValue"]["ypos"];
				yposhealthvalueshadow = data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["ypos"];
				if ( yposhealthvalue.indexOf("c") > -1 ) { yposhealthvalue = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposhealthvalue.replace("c","")))); }
				else if ( yposhealthvalue.indexOf("r") > -1 ) { yposhealthvalue = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposhealthvalue.replace("r","")))); }
				if ( yposhealthvalueshadow.indexOf("c") > -1 ) { yposhealthvalueshadow = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposhealthvalueshadow.replace("c","")))); }
				else if ( yposhealthvalueshadow.indexOf("r") > -1 ) { yposhealthvalueshadow = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposhealthvalueshadow.replace("r","")))); }
				shadowSize = Number(yposhealthvalueshadow) - Number(yposhealthvalue);
				if ( typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] == 'undefined' ) {
					data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = "0 0 0 255";
				}
				data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["fgcolor"] = "QHUDShadow";
				var shadowColorTemp = getFGColorString(data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["fgcolor"]).split(' ');
				shadowColor = "rgba("+shadowColorTemp[0]+","+shadowColorTemp[1]+","+shadowColorTemp[2]+"," +(Math.round((Number(shadowColorTemp[3])*1.0/255) * 100) / 100)+ ")";
			} else {
				shadowSize = 2;
				shadowColor = "rgba(0, 0, 0, 1.0)";
			}*/

			if ( typeof data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"] != 'undefined' ) {
				yposammovalue = data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClip"]["ypos"];
				yposammovalueshadow = data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["ypos"];
				if ( yposammovalue.indexOf("c") > -1 ) { yposammovalue = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposammovalue.replace("c","")))); }
				else if ( yposammovalue.indexOf("r") > -1 ) { yposammovalue = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposammovalue.replace("r","")))); }
				if ( yposammovalueshadow.indexOf("c") > -1 ) { yposammovalueshadow = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(yposammovalueshadow.replace("c","")))); }
				else if ( yposammovalueshadow.indexOf("r") > -1 ) { yposammovalueshadow = String(Math.floor((Number(localStorage.canvasheight)) - Number(yposammovalueshadow.replace("r","")))); }
				shadowSize = Number(yposammovalueshadow) - Number(yposammovalue);
				if ( typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] == 'undefined' ) {
					data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = "0 0 0 255";
				}
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["fgcolor"] = "QHUDShadow";
				var shadowColorTemp = getFGColorString(data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["fgcolor"]).split(' ');
				shadowColor = "rgba("+shadowColorTemp[0]+","+shadowColorTemp[1]+","+shadowColorTemp[2]+"," +(Math.round((Number(shadowColorTemp[3])*1.0/255) * 100) / 100)+ ")";
			} else {
				shadowSize = 2;
				shadowColor = "rgba(0, 0, 0, 1.0)";
			}

			$("#shadowoffset").val(shadowSize);
			$('#shadowOverallColor').val(shadowColor);
//			$('#colorpicker-shadowOverallColor').spectrum("set", shadowColor);
			$('#colorpicker-shadowOverallColor').spectrum("set", $('#shadowOverallColor').val());
			$('#colorpicker-shadowOverallColor')["0"]["value"] = $('#shadowOverallColor').val();
			console.log("shadowOverallColor: " + $('#shadowOverallColor').val());
			console.log("colorpicker-shadowOverallColor: " + $('#colorpicker-shadowOverallColor')["0"]["value"]);

			$('#loaderwrapper').hide();
			if (localStorage.canvasstate) {
				var canvasstate = JSON.parse(localStorage.canvasstate);
				$.each( canvasstate, function( key, value ) {
					drawElement( key, value, ".hud-canvas" );
				});
			}
			if (localStorage.canvasstatetargetid) {
				var canvasstatetargetid = JSON.parse(localStorage.canvasstatetargetid);
				$.each( canvasstatetargetid, function( key, value ) {
					drawElement( key, value, "#targetidwrapper" );
				});
			}
			if (localStorage.canvasstateinnerid) { // fix for older hud versions
				var canvasstateinnerid = JSON.parse(localStorage.canvasstateinnerid);
				$.each( canvasstateinnerid, function( key, value ) {
					drawElement( key, value, "#targetidwrapper" );
				});
			}
			if (localStorage.canvasstateclassmenu) {
				var canvasstateclassmenu = JSON.parse(localStorage.canvasstateclassmenu);
				$.each( canvasstateclassmenu, function( key, value ) {
					drawElement( key, value, ".hud-canvas-classmenu" );
				});
			}
			if (localStorage.canvasstateteammenu) {
				var canvasstateteammenu = JSON.parse(localStorage.canvasstateteammenu);
				$.each( canvasstateteammenu, function( key, value ) {
					drawElement( key, value, ".hud-canvas-teammenu" );
				});
			}
			if (localStorage.canvasstatewinpanel) {
				var canvasstatewinpanel = JSON.parse(localStorage.canvasstatewinpanel);
				$.each( canvasstatewinpanel, function( key, value ) {
					drawElement( key, value, ".hud-canvas-winpanel" );
				});
			}
			if (localStorage.canvasstatescoreboard) {
				var canvasstatescoreboard = JSON.parse(localStorage.canvasstatescoreboard);
				$.each( canvasstatescoreboard, function( key, value ) {
					drawElement( key, value, ".hud-canvas-scoreboard" );
				});
			}
			if (localStorage.canvasstatefreezecam) {
				var canvasstatefreezecam = JSON.parse(localStorage.canvasstatefreezecam);
				$.each( canvasstatefreezecam, function( key, value ) {
					drawElement( key, value, ".hud-canvas-freezecam" );
				});
			}
			if (localStorage.canvasstatemainmenu) {
				var canvasstatemainmenu = JSON.parse(localStorage.canvasstatemainmenu);
				$.each( canvasstatemainmenu, function( key, value ) {
					drawElement( key, value, ".hud-canvas-mainmenu" );
				});
			}
/*			if (localStorage.canvasstatetournamentsetup) {
				var canvasstatetournamentsetup = JSON.parse(localStorage.canvasstatetournamentsetup);
				$.each( canvasstatetournamentsetup, function( key, value ) {
					drawElement( key, value, ".hud-canvas-tournamentsetup" );
				});
			}
			if (localStorage.canvasstatespectator) {
				var canvasstatespectator = JSON.parse(localStorage.canvasstatespectator);
				$.each( canvasstatespectator, function( key, value ) {
					drawElement( key, value, ".hud-canvas-spectator" );
				});
			}*/
			if (localStorage.canvasstatemisclabels) {
				var canvasstatemisclabels = JSON.parse(localStorage.canvasstatemisclabels);
				$.each( canvasstatemisclabels, function( key, value ) {
					drawElement( key, value, ".hud-canvas-misclabels" );
				});
			}

		} else {
			$('#loaderwrapper').show();
			getDataFromRes();
		}
	}


//**********************************************************//
//				Get sub object for hud element				//
//**********************************************************//
	function getObjPath(data, name, path, key) {
		if (key !== "") {
			path = path + '["' + key + '"]';
		}
		for (var key in data) {
			if (key == name) {
				return path + '["' + name + '"]';
			} else if ( typeof data[key] === 'object' ) {
				value = getObjPath(data[key], name, path, key);
				if ( typeof value != 'undefined' ) { path = ""; return value; }
			}
		}
	}


//**************************************************************//
//		Create css styles and select options for each font		//
//**************************************************************//
	$.each(data["jsonclientscheme"]["Scheme"]["CustomFontFiles"], function(i, v) {
		var tempfont = v.font;
		if ( typeof tempfont != 'undefined' ) {
			var fontlength = tempfont.length - 3;
			var fontsubstr = tempfont.substr(fontlength);
			var format = "";
			if ( fontsubstr === "ttf" ) {
				format = " format('truetype')";
			} else if ( fontsubstr === "otf" ) {
				format = " format('opentype')";
			}
			if (v.name != null) {
				$("<style type='text/css'> @font-face { font-family: '"+v.name+"'; src: url('"+v.font+"')"+format+"; } </style>").appendTo("head");
				$("<option style='font-family:"+v.name+";' value='"+v.name+"'>"+v.name+"</option>").appendTo("#element-fontname");
				$("<option style='font-family:"+v.name+";' value='"+v.name+"'>"+v.name+"</option>").appendTo("#element-damage-fontname");
//				$("<option style='font-family:"+v.name+";' value='"+v.name+"'>"+v.name+"</option>").appendTo("#element-default-fontname");
			}
		}
	});


//**************************************************************//
//					Create default body font					//
//**************************************************************//
	var defaultfontname = data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["name"];
	var defaultfonttall = data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["tall"];
	var defaultfontweight = data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["weight"];
//	$("<style type='text/css'> #canv-region { font-family:"+defaultfontname+"; font-size:"+defaultfonttall+"px; font-weight:"+defaultfontweight+"; } </style>").appendTo("head");
	$("#canv-region").css("font-family",defaultfontname);
	$("#canv-region").css("font-size",defaultfonttall);
	$("#canv-region").css("font-weight",defaultfontweight);
//	console.log(data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["name"]);
//	$("#element-default-fontname").val(data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["name"]);
	


//******************************************************//
//					Reset hud elements					//
//******************************************************//
	$( "#Reset" ).button().click(function() {
		localStorage.reset = "true";
//		location.reload();
		window.location.replace("http://"+window.location.host+"/hudcreator/");
	});


//******************************************************//
//				Change resolution any time				//
//******************************************************//
	$( "#ChangeResolution" ).button().click(function() {
		$( "#dialog-form" ).dialog( "open" );
	});


//******************************************************//
//					 Upload custom font					//
//******************************************************//
// http://hayageek.com/docs/jquery-upload-file.php
	var uploadObj = $("#fileuploader").uploadFile({
		url:"./php/upload.php",
		allowedTypes:"otf",
		autoSubmit:true,
		//multiple:false,
		uploadButtonClass:"invisible",
		dynamicFormData: function() {
			var formdata = {input : JSON.stringify(data["jsonclientscheme"]["Scheme"]["CustomFontFiles"])};
			return formdata;
		},
		onSuccess:function(files,incdata,xhr) {
			function IsJsonString(str) {
				try { JSON.parse(str);}
				catch (e) { return false; }
				return true;
			}

			if (IsJsonString(incdata)) {
				//data["jsonclientscheme"]["Scheme"]["CustomFontFiles"] = JSON.parse(incdata)["CustomFontFiles"];
				var newfontname = JSON.parse(incdata)["newfontdata"]["name"];
				var newfontpath = JSON.parse(incdata)["newfontdata"]["path"];
				var fontNumber = String(Number(Object.keys(data["jsonclientscheme"]["Scheme"]["CustomFontFiles"]).length) + 1);
				data["jsonclientscheme"]["Scheme"]["CustomFontFiles"][fontNumber] = {};
				data["jsonclientscheme"]["Scheme"]["CustomFontFiles"][fontNumber]["font"] = newfontpath;
				data["jsonclientscheme"]["Scheme"]["CustomFontFiles"][fontNumber]["name"] = newfontname;

				$("<style type='text/css'> @font-face { font-family: '"+newfontname+"'; src: url('"+newfontpath+"'); } </style>").appendTo("head");
				$("<option style='font-family:"+newfontname+";' value='"+newfontname+"'>"+newfontname+"</option>").appendTo("#element-fontname");
				$("<option style='font-family:"+newfontname+";' value='"+newfontname+"'>"+newfontname+"</option>").appendTo("#element-damage-fontname");
				$("<option style='font-family:"+newfontname+";' value='"+newfontname+"'>"+newfontname+"</option>").appendTo("#element-default-fontname");
				
				autoSave();
				alert('New font file was successfully added to the hud.');
			} else {
				alert(incdata);
			}
		}
	});

	$('body').on('click','#showCustomFont',function(){
		$( "#ajax-upload-id-fileuploader" ).trigger( "click" );
	});


//******************************************************//
//				Fix for older HUD versions				//
//******************************************************//
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterBG"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterBG"] = "255 255 255 100";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterFG"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterFG"] = "255 255 255 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeLabel"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeLabel"] = "255 255 255 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInClip"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInClip"] = getFGColorString(data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClip"]["fgcolor"]);
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInReserve"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInReserve"] = getFGColorString(data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserve"]["fgcolor"]);
	}

	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLow"] !== 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"] = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLow"];
	} else if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"] = "255 49 49 255";
	}

	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowReserve"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowReserve"] = "255 49 49 255";
	}

	data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpellMenu"]["ypos"] = "r95";

	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClip"]["fgcolor"] = "QHUDAmmoInClip";
	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["fgcolor"] = "QHUDAmmoInClip";
	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserve"]["fgcolor"] = "QHUDAmmoInReserve";

	data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["fgcolor"] = "QHUDChargeLabel";
	data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	data["jsonhuddemomancharge"]["Resource/UI/HudDemomanCharge.res"]["ChargeMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhuddemomancharge"]["Resource/UI/HudDemomanCharge.res"]["ChargeMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";

	data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	data["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhuditemeffectmeterscout"]["Resource/UI/HudItemEffectMeter_Scout.res"]["ItemEffectMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";
	data["jsonhuditemeffectmeter"]["Resource/UI/HudItemEffectMeter.res"]["ItemEffectMeter"]["bgcolor_override"] = "QHUDChargeMeterBG";
	data["jsonhuditemeffectmeter"]["Resource/UI/HudItemEffectMeter.res"]["ItemEffectMeter"]["fgcolor_override"] = "QHUDChargeMeterFG";

	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["TargetHealthBG"] == 'undefined') {
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"] = {};
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["ControlName"] = "CExImageButton";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["fieldName"] = "TargetHealthBG";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["xpos"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["ypos"] = "285";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["zpos"] = "-9";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["wide"] = "40";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["tall"] = "22";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["autoResize"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["visible"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["enabled"] = "1";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["defaultBgColor_Override"] = "QHUDSmallBarNormal";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["PaintBackgroundType"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["textinsety"] = "99";
	}

	if (data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"] !== "0") {
		var targetidy = data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["ypos"] = String(Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["ypos"]) + Number(targetidy));
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["ypos"] = String(Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["ypos"]) + Number(targetidy));
		data["jsontargetid"]["Resource/UI/TargetID.res"]["SpectatorGUIHealth"]["ypos"] = String(Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["SpectatorGUIHealth"]["ypos"]) + Number(targetidy));
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["ypos"] = String(Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["ypos"]) + Number(targetidy));
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["ypos"] = String(Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetHealthBG"]["ypos"]) + Number(targetidy));
		data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"] = String(Number(data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"]) - Number(targetidy));

		data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["tall"] = "480";
	}

	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["xpos"] = "9999";
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Red"]["xpos"] = "9999";

/*	if (data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["ypos"] === "0") {
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["ypos"] = "307";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["tall"] = "15";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Red"]["ypos"] = "307";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Red"]["tall"] = "15";
	} else {
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Red"]["tall"] = data["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["tall"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["tall"] = data["jsonhudlayout"]["Resource/HudLayout.res"]["CSpectatorTargetID"]["tall"];
	}
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Red"]["wide"] = "500";
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetIDBG_Spec_Blue"]["wide"] = "500";*/

	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuitButton"]["font"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CreateServerButton"]["SubButton"]["font"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TrainingButton"]["SubButton"]["font"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ServerBrowserButton"]["SubButton"]["font"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuickplayButton"]["SubButton"]["font"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["font"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["font"] = data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuickplayButton"]["SubButton"]["font"];
	}
	if (typeof data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["fgcolor"] == 'undefined') {
		data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["fgcolor"] = "255 255 255 255";
	}

	data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabelBG"]["xpos"] = "9999";

	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarHigh"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarHigh"] = "6 146 255 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"] = "255 49 49 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarNormal"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarNormal"] = "0 0 0 0";
	}

	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["wide"] = "640";
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["wide"] = "280";

	if (typeof data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"] == 'undefined') {
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"] = {};
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["fieldName"] = "TargetNameLabelShadow";
		if ( shadowColor.indexOf("rgba") > -1 ) { // rgba
			var colorarray = shadowColor.replace("rgba(","").replace(")","").split(",");
			data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["fgcolor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
		} else { // rgb
			var colorarray = shadowColor.replace("rgb(","").replace(")","").split(",");
			data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["fgcolor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
		}
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["visible"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["ControlName"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["ControlName"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["font"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["font"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["zpos"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["zpos"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["wide"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["wide"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["tall"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["tall"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["autoResize"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["autoResize"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["pinCorner"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["pinCorner"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["enabled"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["enabled"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["labelText"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["labelText"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["textAlignment"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["textAlignment"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["dulltext"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["dulltext"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["brighttext"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["brighttext"];
		var tempxpos = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["xpos"];
		var tempypos = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabel"]["ypos"];
		if ( tempxpos.indexOf("c") > -1 ) { tempxpos = String(Math.floor((Number(localStorage.canvaswidth) / 2) + Number(tempxpos.replace("c","")))); }
		else if ( tempxpos.indexOf("r") > -1 ) { tempxpos = String(Math.floor((Number(localStorage.canvaswidth)) - Number(tempxpos.replace("r","")))); }
		if ( tempypos.indexOf("c") > -1 ) { tempypos = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(tempypos.replace("c","")))); }
		else if ( tempypos.indexOf("r") > -1 ) { tempypos = String(Math.floor((Number(localStorage.canvasheight)) - Number(tempypos.replace("r","")))); }
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["xpos"] = String(Number(tempxpos) + shadowSize);
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["ypos"] = String(Number(tempypos) + shadowSize);
	}
	if (typeof data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"] == 'undefined') {
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"] = {};
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["fieldName"] = "TargetDataLabelShadow";
		if ( shadowColor.indexOf("rgba") > -1 ) { // rgba
			var colorarray = shadowColor.replace("rgba(","").replace(")","").split(",");
			data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["fgcolor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
		} else { // rgb
			var colorarray = shadowColor.replace("rgb(","").replace(")","").split(",");
			data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["fgcolor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
		}
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["visible"] = "0";
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["ControlName"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["ControlName"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["font"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["font"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["zpos"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["zpos"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["wide"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["wide"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["tall"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["tall"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["autoResize"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["autoResize"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["pinCorner"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["pinCorner"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["enabled"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["enabled"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["labelText"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["labelText"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["textAlignment"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["textAlignment"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["dulltext"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["dulltext"];
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["brighttext"] = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["brighttext"];
		var tempxpos = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["xpos"];
		var tempypos = data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabel"]["ypos"];
		if ( tempxpos.indexOf("c") > -1 ) { tempxpos = String(Math.floor((Number(localStorage.canvaswidth) / 2) + Number(tempxpos.replace("c","")))); }
		else if ( tempxpos.indexOf("r") > -1 ) { tempxpos = String(Math.floor((Number(localStorage.canvaswidth)) - Number(tempxpos.replace("r","")))); }
		if ( tempypos.indexOf("c") > -1 ) { tempypos = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(tempypos.replace("c","")))); }
		else if ( tempypos.indexOf("r") > -1 ) { tempypos = String(Math.floor((Number(localStorage.canvasheight)) - Number(tempypos.replace("r","")))); }
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["xpos"] = String(Number(tempxpos) + shadowSize);
		data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["ypos"] = String(Number(tempypos) + shadowSize);
	}

	if (typeof data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"] === 'undefined' || data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"] === null) {
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"] = {};
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["ControlName"] = "ImagePanel";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["fieldName"] = "PlayerStatus_Parachute";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["xpos"] = "25";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["ypos"] = "405";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["zpos"] = "7";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["wide"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["tall"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["visible"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["enabled"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["scaleImage"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["image"] = "";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_Parachute"]["fgcolor"] = "TanDark";
	}

	//
	// Gun Mettle Update FIX
	//
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["medal_width"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["medal_width"] = "14";
//		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["avatar_width"] = String(Number(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["avatar_width"]) - 5);
//		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["name_width"] = String(Number(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["name_width"]) - 5);
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["name_width_short"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["name_width_short"] = "65";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["spacer"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["spacer"] = "5";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["killstreak_width"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["killstreak_width"] = "15";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["killstreak_image_width"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["killstreak_image_width"] = "15";
	}

	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["avatar_width"] = "45";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["name_width"] = "0";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["status_width"] = "15";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["nemesis_width"] = "15";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["class_width"] = "20";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["score_width"] = "20";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"]["ping_width"] = "20";

	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"]["1"]["tall"] = "8";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"]["1"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemFontAttribSmallv2"]["1"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["additive"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_Desc"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescGlow"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["blur"] = "3";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["additive"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["antialias"] = "1";
		//data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["custom"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ItemTrackerScore_InGame"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["tall"] = "10";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["yres"] = "480 599";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["yres"] = "600 1023";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["3"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["tall"] = "18";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["yres"] = "1024 6000";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestFlavorText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["tall"] = "10";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["weight"] = "800";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["yres"] = "480 599";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["weight"] = "800";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["yres"] = "600 1023";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["3"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["tall"] = "18";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["weight"] = "800";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["yres"] = "1024 6000";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["tall"] = "16";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestLargeText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["name"] = "TF2 Professor";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["tall"] = "20";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["custom"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestStickyText"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["tall"] = "10";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_ItemName"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["tall"] = "8";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_AdText"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"]["tall"] = "8";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["AdFont_PurchaseButton"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QuestGold"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestGold"] = "208 147 75 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemLimitedQuantity"] = "225 209 0 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QualityColorPaintkitWeapon"] = "250 250 250 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityDefault"] = "131 126 119 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityCommon"] = "176 195 217 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityUncommon"] = "94 152 217 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityRare"] = "75 105 255 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityMythical"] = "136 71 255 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityLegendary"] = "211 44 230 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityAncient"] = "235 75 75 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityDefault_GreyedOut"] = "44 42 40 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityCommon_GreyedOut"] = "59 65 72 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityUncommon_GreyedOut"] = "31 50 72 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityRare_GreyedOut"] = "25 35 85 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityMythical_GreyedOut"] = "45 24 85 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityLegendary_GreyedOut"] = "70 15 77 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["ItemRarityAncient_GreyedOut"] = "78 25 25 255";
	}
	/*if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["ControlName"] = "EditablePanel";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["fieldName"] = "QuestLogContainer";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"]["tall"] = "f0";
	}*/
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["fieldName"] = "QuestNotificationPanel";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestNotificationPanel"]["tall"] = "f0";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["fieldName"] = "HudMiniGame";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMiniGame"]["tall"] = "480";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["fieldName"] = "ItemAttributeTracker";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["wide"] = "f5";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["tall"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["ItemAttributeTracker"]["PaintBackgroundType"] = "0";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["fieldName"] = "HudEurekaEffectTeleportMenu";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["xpos"] = "c-125";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["ypos"] = "c-55";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["wide"] = "500";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["tall"] = "200";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudEurekaEffectTeleportMenu"]["PaintBackgroundType"] = "0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["ControlName"] = "CItemModelPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["fieldName"] = "mouseoveritempanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["xpos"] = "c-70";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["ypos"] = "270";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["zpos"] = "100";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["wide"] = "300";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["tall"] = "300";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["bgcolor_override"] = "0 0 0 0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["noitem_textcolor"] = "117 107 94 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["PaintBackgroundType"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["paintborder"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["border"] = "MainMenuBGBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["text_ypos"] = "20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["text_center"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["model_hide"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["resize_to_text"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["padding_height"] = "15";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["font"] = "ItemFontAttribLarge";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["ypos"] = "30";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["zpos"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["wide"] = "140";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["tall"] = "60";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["pinCorner"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["labelText"] = "%attriblist%";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["textAlignment"] = "center";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["fgcolor"] = "117 107 94 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["mouseoveritempanel"]["attriblabel"]["centerwrap"] = "1";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["fieldName"] = "QuestLogButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["xpos"] = "c228";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["ypos"] = "28";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["wide"] = "32";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["tall"] = "32";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["pinCorner"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["tabPosition"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["navUp"] = "Notifications_Panel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["navLeft"] = "SettingsButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["ControlName"] = "CExImageButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["fieldName"] = "SubButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["pinCorner"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["tabPosition"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["textinsetx"] = "25";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["labelText"] = "";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["use_proportional_insets"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["font"] = "HudFontSmallBold";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["command"] = "questlog";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["textAlignment"] = "west";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["dulltext"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["brighttext"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["default"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["sound_depressed"] = "UI/buttonclick.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["sound_released"] = "UI/buttonclickrelease.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["actionsignallevel"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["sound_depressed"] = "UI/buttonclick.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["sound_released"] = "UI/buttonclickrelease.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["paintbackground"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["paintborder"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["image_drawcolor"] = "235 226 202 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["image_armedcolor"] = "255 255 255 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["ControlName"] = "ImagePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["fieldName"] = "SubImage";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["ypos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["scaleImage"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["image"] = "button_quests_pda";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["keyboardinputenabled"] = "0";
		/*data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["fieldName"] = "NotificationsContainer";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["xpos"] = "rs1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["zpos"] = "10";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["wide"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["tall"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["keyboardinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["ControlName"] = "ImagePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["fieldName"] = "SubImage";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["ypos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["zpos"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["wide"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["tall"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["image"] = "glyph_achievements";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["scaleImage"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["drawcolor"] = "210 125 33 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["SubImage"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["ControlName"] = "CExLabel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["fieldName"] = "Notifications_CountLabel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["font"] = "HudFontSmallestBold";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["labelText"] = "%noticount%";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["textAlignment"] = "center";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["ypos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["zpos"] = "4";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["wide"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["tall"] = "16";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["pinCorner"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["fgcolor_override"] = "255 255 255 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"]["Notifications_CountLabel"]["proportionaltoparent"] = "1";*/
	}

	// general shadow color fix
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = "0 0 0 255";
	}
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentInstructionsLabelShadow"]["if_mvm"]["fgcolor"] = "QHUDShadow";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabelShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["DamageAccountValueShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthValueShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["PipesPresentPanel"]["NumPipesLabelShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInClipShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoInReserveShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["fgcolor"] = "QHUDShadow";
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetNameLabelShadow"]["fgcolor"] = "QHUDShadow";
	data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetDataLabelShadow"]["fgcolor"] = "QHUDShadow";
	//data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["specgui"]["playerpanels_kv"]["chargeamountShadow"]["fgcolor"] = "QHUDShadow";
	//data[""]["PlayerStatusHealthValueTargetIDShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["BlueTeamScoreScoreboardShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["RedTeamScoreScoreboardShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamScoreShadow"]["fgcolor"] = "QHUDShadow";
	data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamScoreShadow"]["fgcolor"] = "QHUDShadow";

	// fix for ServerTimeLimitLabel
	var serverTimeLimitTemp = data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["font"];
	data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["font_minmode"] = serverTimeLimitTemp;
	data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["font_hidef"] = serverTimeLimitTemp;
	data["jsonhudobjectivetimepanel"]["Resource/UI/HudObjectiveTimePanel.res"]["ServerTimeLimitLabel"]["font_lodef"] = serverTimeLimitTemp;

	// fix for pickup plugin - hud
	if (data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["xpos"] == "c-125" && data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["wide"] == "250") {
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["xpos"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["wide"] = "f0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_base_offset_x"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_base_y"] = "86";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_delta_x"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_delta_y"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team2_player_base_offset_x"] = "25";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team2_player_base_y"] = "40";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team2_player_delta_x"] = "45";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team2_player_delta_y"] = "18";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["if_mvm"]["xpos"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["if_mvm"]["wide"] = "f0";

		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournamentBG"]["xpos"] = "c-88";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentLabel"]["xpos"] = "c-117";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournamentBLUEBG"]["xpos"] = "c-85";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentBLUELabel"]["xpos"] = "c-79";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentBLUEStateLabel"]["xpos"] = "c-70";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournamentREDBG"]["xpos"] = "c0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentREDLabel"]["xpos"] = "c14";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentREDStateLabel"]["xpos"] = "c6";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentConditionBG"]["xpos"] = "c-85";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentConditionLabel"]["xpos"] = "c-125";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournamentBGHelp"]["xpos"] = "c-125";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentInstructionsLabel"]["xpos"] = "c-125";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentInstructionsLabel"]["if_mvm"]["xpos"] = "c30";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentInstructionsLabelShadow"]["xpos"] = "c-125";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["TournamentInstructionsLabelShadow"]["if_mvm"]["xpos"] = "c31";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownBG"]["xpos"] = "c105";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabel"]["xpos"] = "c105";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabelShadow"]["xpos"] = "c106";
	}

	// add damage to scoreboard
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["ControlName"] = "CExLabel";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["fieldName"] = "DamageLabel";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["font"] = "m0refont10";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["fgcolor"] = "m0rewhite";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["labelText"] = "#TF_Scoreboard_Damage";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["textAlignment"] = "north-east";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["xpos"] = "580";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["ypos"] = "377";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["zpos"] = "3";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["wide"] = "50";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["tall"] = "20";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["autoResize"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["pinCorner"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["visible"] = "1";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageLabel"]["enabled"] = "1";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["ControlName"] = "CExLabel";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["fieldName"] = "Damage";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["font"] = "m0refont10";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["fgcolor"] = "m0rewhite";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["labelText"] = "%damage%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["textAlignment"] = "north-west";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["zpos"] = "3";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["wide"] = "35";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["tall"] = "20";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["autoResize"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["pinCorner"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["visible"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Damage"]["enabled"] = "1";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["ControlName"] = "CExLabel";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["fieldName"] = "DamageFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["font"] = "m0refont10";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["fgcolor"] = "m0rewhite";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["labelText"] = "%damage%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["textAlignment"] = "north-west";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["xpos"] = "640";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["ypos"] = "377";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["zpos"] = "3";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["wide"] = "35";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["tall"] = "20";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["autoResize"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["pinCorner"] = "0";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["visible"] = "1";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DamageFix"]["enabled"] = "1";
	}

	// move "MapName" from "LocalPlayerStatsPanel" to the root
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MapName"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MapName"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["MapName"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"])));
		delete data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["MapName"];
	}
	// remove "GameType" from the scoreboard
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"] !== 'undefined') {
		delete data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["GameType"];
	}


	//
	// Tough Break Update scoreboard fix
	//
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsFix"]["fieldName"] = "KillsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["KillsFix"]["labelText"] = "%kills%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Kills"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsFix"]["fieldName"] = "DeathsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DeathsFix"]["labelText"] = "%deaths%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Deaths"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsFix"]["fieldName"] = "AssistsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["AssistsFix"]["labelText"] = "%assists%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Assists"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionFix"]["fieldName"] = "DestructionFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DestructionFix"]["labelText"] = "%destruction%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Destruction"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesFix"]["fieldName"] = "CapturesFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["CapturesFix"]["labelText"] = "%captures%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Captures"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesFix"]["fieldName"] = "DefensesFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DefensesFix"]["labelText"] = "%defenses%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Defenses"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationFix"]["fieldName"] = "DominationFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["DominationFix"]["labelText"] = "%dominations%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Domination"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeFix"]["fieldName"] = "RevengeFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["RevengeFix"]["labelText"] = "%Revenge%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Revenge"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingFix"]["fieldName"] = "HealingFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HealingFix"]["labelText"] = "%healing%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Healing"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnFix"]["fieldName"] = "InvulnFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["InvulnFix"]["labelText"] = "%invulns%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Invuln"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsFix"]["fieldName"] = "TeleportsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["TeleportsFix"]["labelText"] = "%teleports%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Teleports"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsFix"]["fieldName"] = "HeadshotsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["HeadshotsFix"]["labelText"] = "%headshots%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Headshots"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsFix"]["fieldName"] = "BackstabsFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BackstabsFix"]["labelText"] = "%backstabs%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Backstabs"]["visible"] = "0";
	}
	if (typeof data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusFix"] == 'undefined') {
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusFix"] = {};
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusFix"] = (JSON.parse(JSON.stringify(data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"])));
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusFix"]["fieldName"] = "BonusFix";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["BonusFix"]["labelText"] = "%bonus%";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"]["xpos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"]["ypos"] = "9999";
		data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["LocalPlayerStatsPanel"]["Bonus"]["visible"] = "0";
	}


	//
	// Matchmaking update fix
	//
//	data["jsonhudobjectivestatus"]["Resource/UI/HudObjectiveStatus.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["visible"] = "0"; // Hide deprecated timers
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownBG"]["xpos"] = "c-20";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownBG"]["ypos"] = "c-115";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabel"]["xpos"] = "c-20";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabel"]["ypos"] = "c-115";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabelShadow"]["xpos"] = "c-19";
	data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["CountdownLabelShadow"]["ypos"] = "c-114";
	
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamLabel"]["zpos"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueTeamLabel"]["zpos"] = "10";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamLabel"]["zpos"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedTeamLabel"]["zpos"] = "10";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"]["name"] = "TF2";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"]["tall"] = "36";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryTeamScores"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryStatsAndMedals"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"]["tall"] = "20";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MatchSummaryWinner"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["CompMatchStartTeamNames"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["fieldName"] = "MatchSummary";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["visible"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["MatchSummary"]["tall"] = "f0";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["fieldName"] = "HudMatchStatus";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["zpos"] = "2";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudMatchStatus"]["tall"] = "f0";
	}

	if (typeof data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["teams_player_delta_x_comp"] == 'undefined') {
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["teams_player_delta_x_comp"] = "50";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["avatar_width"] = "63";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["spacer"] = "5";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["name_width"] = "57";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["horiz_inset"] = "2";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"] = {};
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["ControlName"] = "ImagePanel";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["fieldName"] = "ModeImage";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["xpos"] = "cs-0.5";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["ypos"] = "60";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["zpos"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["wide"] = "60";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["tall"] = "60";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["autoResize"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["pinCorner"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["visible"] = "0";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["enabled"] = "1";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["image"] = "competitive/competitive_logo_laurel";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["scaleImage"] = "1";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["proportionaltoparent"] = "1";
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["if_competitive"] = {};
		data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["ModeImage"]["if_competitive"]["visible"] = "1";
	}
	
	if (typeof data["jsonhudmatchstatus"] == 'undefined' || data["jsonhudmatchstatus"].length == 0) {
		data["jsonhudmatchstatus"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["fieldName"] = "RoundCounter";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["ypos"] = "-2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["zpos"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["wide"] = "300";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["tall"] = "100";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RoundCounter"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"] = (JSON.parse(JSON.stringify(data["jsonhudobjectivestatus"]["Resource/UI/HudObjectiveStatus.res"]["ObjectiveStatusTimePanel"])));
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["zpos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["zpos"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["visible"] = "1";
//		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["labelText"] = "0:00";
	}
	data["jsonhudobjectivestatus"] = {};

	// Koth timer z-index fix
	if (typeof data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"] == 'undefined') {
		data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"] = {};
		data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"] = {};
		data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"]["zpos"] = "5";
	}
	// Normal timer z-index fix
	data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["zpos"] = "2";
	data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["zpos"] = "3";
	
	// Removing "HudTeamStatus" (+ commented code at line ~1697 that creates this element)
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["HudTeamStatus"] != 'undefined') { // if exists -> remove
		delete data["jsonhudlayout"]["Resource/HudLayout.res"]["HudTeamStatus"];
	}

	// Add spectator_extras and HudSpectatorExtras
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["fieldName"] = "HudSpectatorExtras";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["xpos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["ypos"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["wide"] = "f0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["HudSpectatorExtras"]["tall"] = "f0";
	}
	if (typeof data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"] == 'undefined') {
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"] = {};
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["ControlName"] = "EditablePanel";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["fieldName"] = "spectator_extras";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["xpos"] = "0";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["ypos"] = "0";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["wide"] = "f0";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["tall"] = "480";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["autoResize"] = "0";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["pinCorner"] = "0";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["visible"] = "1";
		data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["spectator_extras"]["enabled"] = "1";
	}
	
	// Fixing winpanel
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["ControlName"] = "EditablePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["fieldName"] = "WinPanelBGBorder";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["WinPanelBGBorder"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBGFix"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBGFix"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBGFix"] = (JSON.parse(JSON.stringify(data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"])));
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBGFix"]["fieldName"] = "BlueScoreBGFix";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["ControlName"] = "EditablePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["fieldName"] = "BlueScoreBG";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueScoreBG"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBGFix"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBGFix"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBGFix"] = (JSON.parse(JSON.stringify(data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"])));
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBGFix"]["fieldName"] = "RedScoreBGFix";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["ControlName"] = "EditablePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["fieldName"] = "RedScoreBG";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedScoreBG"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["ControlName"] = "CAvatarImagePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["fieldName"] = "BlueLeaderAvatar";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatar"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["ControlName"] = "EditablePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["fieldName"] = "BlueLeaderAvatarBG";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["BlueLeaderAvatarBG"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["ControlName"] = "CAvatarImagePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["fieldName"] = "RedLeaderAvatar";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatar"]["enabled"] = "0";
	}
	if (typeof data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"] == 'undefined') {
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"] = {};
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["ControlName"] = "EditablePanel";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["fieldName"] = "RedLeaderAvatarBG";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["wide"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["tall"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["visible"] = "0";
		data["jsonwinpanel"]["Resource/UI/winpanel.res"]["TeamScoresPanel"]["RedLeaderAvatarBG"]["enabled"] = "0";
	}

	// MainMenu stream fix
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["fieldName"] = "WatchStreamButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["xpos"] = "c188";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["ypos"] = "28";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["wide"] = "32";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["tall"] = "32";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["pinCorner"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["tabPosition"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["navUp"] = "Notifications_Panel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["navLeft"] = "SettingsButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["ControlName"] = "CExImageButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["fieldName"] = "SubButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["pinCorner"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["tabPosition"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["textinsetx"] = "25";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["labelText"] = "";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["use_proportional_insets"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["font"] = "HudFontSmallBold";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["command"] = "watch_stream";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["textAlignment"] = "west";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["dulltext"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["brighttext"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["default"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["actionsignallevel"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["sound_depressed"] = "UI/buttonclick.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["sound_released"] = "UI/buttonclickrelease.wav";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["paintbackground"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["paintborder"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["image_drawcolor"] = "235 226 202 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["image_armedcolor"] = "255 255 255 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["ControlName"] = "ImagePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["fieldName"] = "SubImage";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["ypos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["scaleImage"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["image"] = "button_streaming";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["WatchStreamButton"]["SubButton"]["SubImage"]["keyboardinputenabled"] = "0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["ControlName"] = "CTFStreamListPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["fieldName"] = "StreamListPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["xpos"] = "c5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["ypos"] = "65";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["wide"] = "300";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["tall"] = "350";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["PaintBackgroundType"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["paintbackground"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["border"] = "MainMenuHighlightBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["navDown"] = "SettingsButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["StreamListPanel"]["navLeft"] = "WatchStreamButton";
	}
	
	//
	// April 25, 2016 Patch - resource/ui/hudtournament.res <-> resource/ui/hudmatchstatus.res
	//
	if (typeof data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["FrontParticlePanel"] != 'undefined') { // if exists -> remove
		delete data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["FrontParticlePanel"];
	}
	if (typeof data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["MatchStartingBG"] != 'undefined') { // if exists -> remove
		delete data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["MatchStartingBG"];
	}
	if (typeof data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["BlueTeamPanel"] != 'undefined') { // if exists -> remove
		delete data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["BlueTeamPanel"];
	}
	if (typeof data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["RedTeamPanel"] != 'undefined') { // if exists -> remove
		delete data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["RedTeamPanel"];
	}

	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["fieldName"] = "HudMatchStatus";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["avatar_width"] = "63";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["spacer"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["name_width"] = "57";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["horiz_inset"] = "2";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["fieldName"] = "CountdownLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["font"] = "HudFontGiant";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["ypos"] = "cs-0.1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["wide"] = "40";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["tall"] = "40";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["wrap"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["labelText"] = "%countdown%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["fgcolor"] = "TanLight";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["if_readymode"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["if_readymode"]["xpos"] = "300";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabel"]["if_readymode"]["ypos"] = "130";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["fieldName"] = "CountdownLabelShadow";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["font"] = "HudFontGiant";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["xpos"] = "cs-0.48";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["ypos"] = "cs-0.08";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["wide"] = "40";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["tall"] = "40";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["zpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["wrap"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["labelText"] = "%countdown%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["fgcolor"] = "QHUDShadow";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["if_readymode"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["if_readymode"]["xpos"] = "300";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["CountdownLabelShadow"]["if_readymode"]["ypos"] = "130";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["ControlName"] = "CTFParticlePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["fieldName"] = "FrontParticlePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["zpos"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["tall"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["paintbackground"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["ParticleEffects"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["FrontParticlePanel"]["ParticleEffects"] = (JSON.parse("{ \"0\":{\"particle_xpos\":\"c0\",\"particle_ypos\":\"c0\",\"particle_scale\":\"2\",\"particleName\":\"versus_door_slam\",\"start_activated\":\"0\",\"loop\":\"0\" } }"));
	}
	//if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["fieldName"] = "BGFrame";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["xpos"] = "9999";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["ypos"] = "9999";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["zpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["wide"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["tall"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["enabled"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BGFrame"]["proportionaltoaparent"] = "1";
	//}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["fieldName"] = "BlueTeamPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["xpos"] = "-155";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["ypos"] = "125";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["zpos"] = "50";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["wide"] = "150";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["tall"] = "260";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["fieldName"] = "BlueTeamBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["ypos"] = "10";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["zpos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["wide"] = "147";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["tall"] = "36";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamBG"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["ControlName"] = "ImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["fieldName"] = "BlueTeamImage";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["xpos"] = "9";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["wide"] = "56";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["tall"] = "56";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["image"] = "../hud/team_blue";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamImage"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["fieldName"] = "BlueTeamLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["font"] = "CompMatchStartTeamNames";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["labelText"] = "%blueteamname%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["xpos"] = "48";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["ypos"] = "13";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["zpos"] = "20";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["wide"] = "95";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["tall"] = "30";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueTeamLabel"]["centerwrap"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["ControlName"] = "CAvatarImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["fieldName"] = "BlueLeaderAvatar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["xpos"] = "11";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["ypos"] = "10";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["wide"] = "35";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["tall"] = "35";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["image"] = "";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatar"]["color_outline"] = "52 48 45 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["fieldName"] = "BlueLeaderAvatarBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["xpos"] = "9";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["ypos"] = "8";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["zpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["wide"] = "39";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["tall"] = "39";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["PaintBackgroundType"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BlueLeaderAvatarBG"]["bgcolor_override"] = "117 107 94 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["ControlName"] = "SectionedListPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["fieldName"] = "BluePlayerList";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["xpos"] = "6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["ypos"] = "38";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["zpos"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["wide"] = "136";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["tall"] = "205";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["tabPosition"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["autoresize"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["linespacing"] = "26";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerList"]["linegap"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["fieldName"] = "BluePlayerListBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["xpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["ypos"] = "30";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["zpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["wide"] = "139";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["tall"] = "215";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["BluePlayerListBG"]["enabled"] = "1";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["fieldName"] = "RedTeamPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["xpos"] = "r-5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["ypos"] = "125";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["zpos"] = "50";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["wide"] = "150";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["tall"] = "260";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["fieldName"] = "RedTeamBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["ypos"] = "10";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["zpos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["wide"] = "147";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["tall"] = "36";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamBG"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["ControlName"] = "ImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["fieldName"] = "RedTeamImage";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["xpos"] = "84";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["ypos"] = "-9";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["wide"] = "70";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["tall"] = "70";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["image"] = "../hud/team_Red";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamImage"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["fieldName"] = "RedTeamLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["font"] = "CompMatchStartTeamNames";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["labelText"] = "%redteamname%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["xpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["ypos"] = "13";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["zpos"] = "20";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["wide"] = "95";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["tall"] = "30";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedTeamLabel"]["centerwrap"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["ControlName"] = "CAvatarImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["fieldName"] = "RedLeaderAvatar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["xpos"] = "102";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["ypos"] = "10";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["wide"] = "35";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["tall"] = "35";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["image"] = "";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatar"]["color_outline"] = "52 48 45 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["fieldName"] = "RedLeaderAvatarBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["xpos"] = "100";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["ypos"] = "8";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["zpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["wide"] = "39";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["tall"] = "39";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["PaintBackgroundType"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedLeaderAvatarBG"]["bgcolor_override"] = "117 107 94 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["ControlName"] = "SectionedListPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["fieldName"] = "RedPlayerList";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["xpos"] = "6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["ypos"] = "38";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["zpos"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["wide"] = "136";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["tall"] = "205";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["tabPosition"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["autoresize"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["linespacing"] = "26";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerList"]["linegap"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["fieldName"] = "RedPlayerListBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["xpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["ypos"] = "30";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["zpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["wide"] = "139";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["tall"] = "215";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["RedPlayerListBG"]["enabled"] = "1";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["ControlName"] = "CModelPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["fieldName"] = "MatchDoors";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["zpos"] = "2";	
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["tall"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["fov"] = "70";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["modelname"] = "models/vgui/versus_doors.mdl";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["skin"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["angles_x"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["angles_y"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["angles_z"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["origin_x"] = "120";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["origin_y"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["MatchDoors"]["model"]["origin_z"] = "-77";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["ControlName"] = "CTFTeamStatus";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["fieldName"] = "TeamStatus";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["zpos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["tall"] = "75";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["max_size"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["6v6_gap"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["12v12_gap"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team1_grow_dir"] = "west";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team1_base_x"] = "c-45";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team1_max_expand"] = "133";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team2_grow_dir"] = "east";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team2_base_x"] = "c47";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["team2_max_expand"] = "133";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["wide"] = "25";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["tall"] = "50";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["zpos"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_bg_red"] = "119 62 61 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_bg_blue"] = "62 81 101 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_bg_red_dead"] = "79 54 52 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_bg_blue_dead"] = "44 49 51 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_bar_health_high"] = "84 191 58 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_bar_health_med"] = "191 183 58 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["percentage_health_med"] = "0.6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_bar_health_low"] = "191 58 58 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["percentage_health_low"] = "0.3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_blend_dead_red"] = "255 255 255 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["color_portrait_blend_dead_blue"] = "255 255 255 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["fieldName"] = "playername";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["font"] = "DefaultVerySmall";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["xpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["ypos"] = "24";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["wide"] = "50";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["tall"] = "8";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["playername"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["ControlName"] = "CTFClassImage";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["fieldName"] = "classimage";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["zpos"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["wide"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["tall"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["image"] = "../hud/class_scoutred";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimage"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["ControlName"] = "Panel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["fieldName"] = "classimagebg";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["zpos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["tall"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["PaintBackgroundType"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["classimagebg"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["ControlName"] = "ContinuousProgressBar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["fieldName"] = "healthbar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["font"] = "Default";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["ypos"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["tall"] = "2";			
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["textAlignment"] = "Left";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["dulltext"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["brighttext"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["bgcolor_override"] = "80 80 80 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["healthbar"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["ControlName"] = "ContinuousProgressBar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["fieldName"] = "overhealbar";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["font"] = "Default";														
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["xpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["ypos"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["zpos"] = "6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["tall"] = "2";		
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["textAlignment"] = "Left";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["dulltext"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["brighttext"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["bgcolor_override"] = "0 0 0 0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["fgcolor_override"] = "255 255 255 160";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["overhealbar"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["ControlName"] = "EditablePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["fieldName"] = "HealthIcon";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["xpos"] = "22";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["ypos"] = "-3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["zpos"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["wide"] = "32";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["tall"] = "32";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["HealthBonusPosAdj"] = "10";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["HealthDeathWarning"] = "0.49";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["TFFont"] = "HudFontSmallest";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["HealthDeathWarningColor"] = "HUDDeathWarning";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["HealthIcon"]["TextColor"] = "HudOffWhite";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["ControlName"] = "ScalableImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["fieldName"] = "ReadyBG";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["xpos"] = "30";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["ypos"] = "6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["zpos"] = "-1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["wide"] = "16";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["tall"] = "16";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["image"] = "../HUD/tournament_panel_brown";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["src_corner_height"] = "22";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["src_corner_width"] = "22";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["draw_corner_width"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyBG"]["draw_corner_height"] = "3";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["ControlName"] = "ImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["fieldName"] = "ReadyImage";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["xpos"] = "32";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["ypos"] = "8";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["zpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["wide"] = "12";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["tall"] = "12";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["image"] = "hud/checkmark";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["ReadyImage"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["fieldName"] = "respawntime";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["font"] = "PlayerPanelPlayerName";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["tall"] = "19";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["labelText"] = "%respawntime%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["respawntime"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["fieldName"] = "chargeamount";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["font"] = "DefaultSmall";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["xpos"] = "25";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["ypos"] = "17";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["zpos"] = "6";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["wide"] = "25";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["tall"] = "15";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["labelText"] = "%chargeamount%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["textAlignment"] = "north";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["chargeamount"]["fgcolor"] = "0 255 0 255";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["fieldName"] = "specindex";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["font"] = "DefaultVerySmall";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["xpos"] = "4";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["ypos"] = "2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["wide"] = "50";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["tall"] = "8";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["labelText"] = "%specindex%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["specindex"]["textAlignment"] = "north-west";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["ControlName"] = "ImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["fieldName"] = "DeathPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["ypos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["zpos"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["wide"] = "f0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["tall"] = "24";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["image"] = "../HUD/comp_player_status";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["DeathPanel"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["ControlName"] = "ImagePanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["fieldName"] = "SkullPanel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["zpos"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["wide"] = "o1.2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["tall"] = "p0.15";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["visible"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["image"] = "../HUD/comp_player_status_skull";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["scaleImage"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["TeamStatus"]["playerpanels_kv"]["SkullPanel"]["proportionaltoparent"] = "1";
	}

	//
	// July 7, 2016 Patch - Meet your match
	//

	// resource/ui/hudmatchstatus.res
	data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["BlueTeamPanel"]["visible"] = "0";
	data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RedTeamPanel"]["visible"] = "0";
	if (data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["fieldName"] !== "HudMatchStatus") {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["HudMatchStatus"]["fieldName"] = "HudMatchStatus";
	}

	// resource/ui/hudobjectivekothtimepanel.res
	if (typeof data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_comp"] != 'undefined') { // if exists -> remove
		//data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"] = {};
		//data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"]["zpos"] = data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_comp"]["zpos"];
		delete data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_comp"];
		data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"] = {};
		data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["HudKothTimeStatus"]["if_match"]["zpos"] = "5";
	}
	
	// scripts/hudlayout.res
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"] != 'undefined') { // if exists -> remove
		delete data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"];
	}

	// resource/clientscheme.res
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"]["name"] = "TF2 Secondary";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"]["tall"] = "9";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MMenuPlayListDesc"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["color"] = "178 83 22 255";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["image"] = "button_glow";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["src_corner_height"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["src_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow"]["draw_corner_height"] = "4";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["color"] = "238 103 17 255";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["image"] = "button_glow";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["src_corner_height"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["src_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["MainMenuButtonGlow2"]["draw_corner_height"] = "4";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["image"] = "featured_corner";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["src_corner_height"] = "32";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["src_corner_width"] = "32";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBorder"]["draw_corner_height"] = "4";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["image"] = "store/store_featured_item_bg01";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["src_corner_height"] = "80";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["src_corner_width"] = "30";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["draw_corner_width"] = "0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["StoreHighlightedBackgroundBorder"]["draw_corner_height"] = "0";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"]["Right"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"]["Right"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"]["Right"]["1"]["color"] = "TanDark";
		data["jsonclientscheme"]["Scheme"]["Borders"]["SortCategoryBorder"]["Right"]["1"]["offset"] = "1 0";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["image"] = "inner_shadow_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["src_corner_height"] = "5";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["src_corner_width"] = "5";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["draw_corner_width"] = "5";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorder"]["draw_corner_height"] = "5";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["image"] = "inner_shadow_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["src_corner_height"] = "5";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["src_corner_width"] = "5";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["InnerShadowBorderThin"]["draw_corner_height"] = "4";
	}

	// resource/ui/mainmenuoverride.res
	//if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["ControlName"] == "ImagePanel") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["ControlName"] = "ScalableImagePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["wide"] = "o1.6";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["tall"] = "f0";
	//}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_3"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_3"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_3"]["image"] = "../console/title_team_halloween2014_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_4"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_4"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_4"]["image"] = "../console/title_team_halloween2015_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_wider"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_wider"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_wider"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_wider"]["tall"] = "o0.628";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_taller"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_taller"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_taller"]["wide"] = "o1.6";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_taller"]["tall"] = "f0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_spy_vs_engy_war"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_spy_vs_engy_war"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_spy_vs_engy_war"]["image"] = "../console/background_sve_01";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_0"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_0"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_0"]["xpos"] = "rs1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_0"]["image"] = "../console/title_team_heavy01_blu_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_1"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_1"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_1"]["xpos"] = "rs1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_1"]["image"] = "../console/title_team_heavy01_red_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_2"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_2"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_2"]["xpos"] = "rs1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_2"]["image"] = "../console/title_team_pyro01_blu_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_3"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_3"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_3"]["xpos"] = "rs1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_3"]["image"] = "../console/title_team_pyro01_red_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_4"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_4"] = {};
		//data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_4"]["xpos"] = "rs1";
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_4"]["xpos"];
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_meet_your_match_4"]["image"] = "../console/title_team_competitive_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_eotl_launch"] != 'undefined') {
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_eotl_launch"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_operation"] != 'undefined') {
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_operation"];
	}
	/*if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["fieldName"] = "PlayListContainer";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["xpos"] = "c-250";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["ypos"] = "100";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["zpos"] = "-52";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["wide"] = "260";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["tall"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["fieldName"] = "PlaylistBGPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["ypos"] = "-260";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["zpos"] = "-1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["wide"] = "p0.98";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["tall"] = "261";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PaintBackgroundType"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["border"] = "MainMenuBGBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["pinCorner"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["autoResize"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["fieldName"] = "PlaylistBGPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["ypos"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["zpos"] = "100";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["wide"] = "p0.95";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["tall"] = "p0.95";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["PaintBackgroundType"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["border"] = "InnerShadowBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListDropShadow"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ControlName"] = "CExScrollingEditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["fieldName"] = "PlayListContainer";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ypos"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["wide"] = "p0.95";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["tall"] = "p0.95";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["restrict_width"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["fieldName"] = "CasualEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["ypos"] = "3";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["image_name"] = "main_menu/main_menu_button_casual";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["button_token"] = "#MMenu_PlayList_Casual_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["button_command"] = "play_casual";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CasualEntry"]["desc_token"] = "#MMenu_PlayList_Casual_Desc";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["fieldName"] = "CompetitiveEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["ypos"] = "53";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["image_name"] = "main_menu/main_menu_button_competitive";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["button_token"] = "#MMenu_PlayList_Competitive_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["button_command"] = "play_competitive";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CompetitiveEntry"]["desc_token"] = "#MMenu_PlayList_Competitive_Desc";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["fieldName"] = "MvMEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["ypos"] = "103";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["image_name"] = "main_menu/main_menu_button_mvm";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["button_token"] = "#MMenu_PlayList_MvM_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["button_command"] = "play_mvm";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["MvMEntry"]["desc_token"] = "#MMenu_PlayList_MvM_Desc";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["fieldName"] = "ServerBrowserEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["ypos"] = "153";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["image_name"] = "main_menu/main_menu_button_community_server";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["button_token"] = "#MMenu_PlayList_ServerBrowser_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["button_command"] = "OpenServerBrowser";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ServerBrowserEntry"]["desc_token"] = "#MMenu_PlayList_ServerBrowser_Desc";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["fieldName"] = "TrainingEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["ypos"] = "203";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["image_name"] = "main_menu/main_menu_button_training";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["button_token"] = "#MMenu_PlayList_Training_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["button_command"] = "play_training";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["TrainingEntry"]["desc_token"] = "#MMenu_PlayList_Training_Desc";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["ControlName"] = "ScrollBar";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["FieldName"] = "ScrollBar";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["xpos"] = "rs1-1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["wide"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["zpos"] = "1000";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["nobuttons"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["Slider"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["Slider"]["fgcolor_override"] = "TanDark";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["UpButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["UpButton"]["ControlName"] = "Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["UpButton"]["FieldName"] = "UpButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["UpButton"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["DownButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["DownButton"]["ControlName"] = "Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["DownButton"]["FieldName"] = "DownButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["ScrollBar"]["DownButton"]["visible"] = "0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["ControlName"] = "CMainMenuPlayListEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["fieldName"] = "CreateServerEntry";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["xpos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["ypos"] = "253";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["tall"] = "45";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["wide"] = "p1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["image_name"] = "main_menu/main_menu_button_custom_server";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["button_token"] = "#MMenu_PlayList_CreateServer_Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["button_command"] = "OpenCreateMultiplayerGameDialog";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"]["PlaylistBGPanel"]["PlayListContainer"]["CreateServerEntry"]["desc_token"] = "#MMenu_PlayList_CreateServer_Desc";
	}*/

	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["ControlName"] = "CCompetitiveAccessInfoPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["fieldName"] = "CompetitiveAccessInfoPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["ypos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["zpos"] = "1000";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveAccessInfoPanel"]["visible"] = "0";
	}

	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveButton"] != 'undefined') { // if exists -> remove
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CompetitiveButton"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayPVEButton"] != 'undefined') { // if exists -> remove
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayPVEButton"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["MutePlayersButton"] != 'undefined') { // if exists -> remove
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["MutePlayersButton"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["VRBGPanel"] != 'undefined') { // if exists -> remove
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["VRBGPanel"];
	}

	// Fix item and motd notifications
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Notifications_ShowButtonPanel"]["xpos"] = "c148";
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Notifications_ShowButtonPanel"]["Notifications_ShowButtonPanel_SB"]["actionsignallevel"] = "2";
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Notifications_ShowButtonPanel"]["Notifications_ShowButtonPanel_SB"]["navActivate"] = "<QuickplayButton";
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Notifications_Panel"]["Notifications_CloseButton"]["actionsignallevel"] = "2";
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["MOTD_ShowButtonPanel"]["MOTD_ShowButtonPanel_SB"]["actionsignallevel"] = "2";
	data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["MOTD_Panel"]["MOTD_CloseButton"]["actionsignallevel"] = "2";


	//
	// September, 27th Patch
	//
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"]["name"] = "Verdana";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"]["tall"] = "6";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["ScoreboardSmallest"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["name"] = "TF2 Build";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["tall"] = "11";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["additive"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["name"] = "TF2 Build";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["tall"] = "11";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["blur"] = "3";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["additive"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["antialias"] = "1";
		//data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["custom"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["image"] = "outer_shadow_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["src_corner_height"] = "8";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["src_corner_width"] = "8";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["draw_corner_width"] = "8";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorder"]["draw_corner_height"] = "8";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["image"] = "outer_shadow_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["src_corner_height"] = "8";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["src_corner_width"] = "8";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["OuterShadowBorderThin"]["draw_corner_height"] = "4";
	}
	
	
	//
	// October, 21th Patch - Halloween
	//
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_0"]["image"] == "../console/title_team_halloween2011") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_0"]["image"] = "../console/title_team_halloween2011_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_1"]["image"] == "../console/title_team_halloween2012") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_1"]["image"] = "../console/title_team_halloween2012_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_2"]["image"] == "../console/title_team_halloween2013") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_2"]["image"] = "../console/title_team_halloween2013_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_3"]["image"] == "../console/title_team_halloween2014") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_3"]["image"] = "../console/title_team_halloween2014_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_4"]["image"] == "../console/title_team_halloween2015") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_halloween_4"]["image"] = "../console/title_team_halloween2015_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_fullmoon"]["image"] == "../console/title_fullmoon") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_fullmoon"]["image"] = "../console/title_fullmoon_widescreen";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_christmas"]["image"] == "../console/background_xmas2011") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_christmas"]["image"] = "../console/background_xmas2011_widescreen";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"] != 'undefined') { // if exists -> remove
		delete data["jsonhudlayout"]["Resource/HudLayout.res"]["QuestLogContainer"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["AchievementTracker_NameGlow"]["1"]["custom"] != 'undefined') { // if exists -> remove
		delete data["jsonclientscheme"]["Scheme"]["Fonts"]["AchievementTracker_NameGlow"]["1"]["custom"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["1"]["custom"] != 'undefined') { // if exists -> remove
		delete data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestObjectiveTracker_DescBlur"]["1"]["custom"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["custom"] != 'undefined') { // if exists -> remove
		delete data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource"]["1"]["custom"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["custom"] != 'undefined') { // if exists -> remove
		delete data["jsonclientscheme"]["Scheme"]["Fonts"]["XPSource_Glow"]["1"]["custom"];
	}


	//
	// November, 10th Patch
	//
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["image"] = "button_holder_central";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["src_corner_height"] = "32";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["src_corner_width"] = "32";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["draw_corner_width"] = "4";
		data["jsonclientscheme"]["Scheme"]["Borders"]["NotificationHighPriority"]["draw_corner_height"] = "4";
	}
	
	
	//
	// December, 21st Patch - Christmas
	//
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["name"] = "TF2 Build";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["tall"] = "12";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["weight"] = "500";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["antialias"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["MapVotesPercentage"]["1"]["dropshadow"] = "1";
	}
	
	
	//
	// October, 20th Patch - Jungle Inferno
	//
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["Purple"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["Purple"] = "137 69 99 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QuestUncommitted"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestUncommitted"] = "137 69 99 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestUncommitted"] = "183 147 100 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestMap_Bonus"] = "222 217 166 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestMap_ActiveOrange"] = "212 127 25 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestMap_InactiveGrey"] = "100 100 100 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["QuestMap_BGImages"] = "56 58 60 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember1"] = "124 173 255 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember2"] = "99 232 167 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember3"] = "229 255 121 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember4"] = "232 184 99 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember5"] = "255 118 108 255";
		data["jsonclientscheme"]["Scheme"]["Colors"]["PartyMember6"] = "255 133 255 255";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QuestStandardHighlight"] != 'undefined') {
		delete data["jsonclientscheme"]["Scheme"]["Colors"]["QuestStandardHighlight"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Colors"]["QuestBonusHighlight"] != 'undefined') {
		delete data["jsonclientscheme"]["Scheme"]["Colors"]["QuestBonusHighlight"];
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["name"] = "ocra";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["weight"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["blur"] = "3";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["additive"] = "1";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small_Blur"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"]["name"] = "ocra";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"]["tall"] = "7";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Small"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"]["name"] = "ocra";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"]["tall"] = "10";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Medium"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"]["name"] = "ocra";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"]["tall"] = "14";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Large"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"]["name"] = "ocra";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"]["tall"] = "30";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"]["weight"] = "400";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"]["additive"] = "0";
		data["jsonclientscheme"]["Scheme"]["Fonts"]["QuestMap_Huge"]["1"]["antialias"] = "1";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["image"] = "cyoa/cyoa_map_screen_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["src_corner_height"] = "63";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["src_corner_width"] = "63";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["draw_corner_width"] = "26";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAScreenBorder"]["draw_corner_height"] = "26";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["image"] = "cyoa/node_view_border";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["src_corner_height"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["src_corner_width"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["draw_corner_width"] = "24";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder"]["draw_corner_height"] = "24";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["image"] = "cyoa/node_view_border_active";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["src_corner_height"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["src_corner_width"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["draw_corner_width"] = "24";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Active"]["draw_corner_height"] = "24";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["image"] = "cyoa/node_view_border_inactive";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["src_corner_height"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["src_corner_width"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["draw_corner_width"] = "24";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_Inactive"]["draw_corner_height"] = "24";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["bordertype"] = "scalable_image";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["backgroundtype"] = "2";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["image"] = "cyoa/node_view_border_turnin";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["src_corner_height"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["src_corner_width"] = "127";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["draw_corner_width"] = "24";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOANodeViewBorder_TurnIn"]["draw_corner_height"] = "24";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["inset"] = "0 0 1 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["1"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["1"]["offset"] = "0 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["2"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Left"]["2"]["offset"] = "0 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["1"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["1"]["offset"] = "1 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["2"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Right"]["2"]["offset"] = "1 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["1"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["1"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["2"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Top"]["2"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["1"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["1"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["2"]["color"] = "QuestMap_ActiveOrange";
		data["jsonclientscheme"]["Scheme"]["Borders"]["CYOAPopupBorder"]["Bottom"]["2"]["offset"] = "0 0";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["inset"] = "0 0 1 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Left"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Left"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Left"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Left"]["1"]["offset"] = "0 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Right"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Right"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Right"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Right"]["1"]["offset"] = "1 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Top"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Top"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Top"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Top"]["1"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Bottom"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Bottom"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Bottom"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorder"]["Bottom"]["1"]["offset"] = "0 0";
	}
	if (typeof data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"] == 'undefined') {
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["inset"] = "0 0 1 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["1"]["offset"] = "0 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["2"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Left"]["2"]["offset"] = "0 1";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["1"]["offset"] = "1 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["2"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Right"]["2"]["offset"] = "1 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["1"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["2"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Top"]["2"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["1"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["1"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["1"]["offset"] = "0 0";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["2"] = {};
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["2"]["color"] = "CreditsGreen";
		data["jsonclientscheme"]["Scheme"]["Borders"]["FriendHighlightBorderThick"]["Bottom"]["2"]["offset"] = "0 0";
	}
	if (typeof data["jsonclassselection"]["Resource/UI/ClassSelection.res"]["TFPlayerModel"]["model"]["animation"] != 'undefined') {
		delete data["jsonclassselection"]["Resource/UI/ClassSelection.res"]["TFPlayerModel"]["model"]["animation"];
	}
	if (typeof data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"] == 'undefined') {
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"] = {};
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["ControlName"] = "CExLabel";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["fieldName"] = "PlayerStatusMaxHealthValue";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["xpos"] = "9999";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["ypos"] = "9999";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["zpos"] = "6";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["wide"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["tall"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["visible"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["enabled"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["labelText"] = "%MaxHealth%";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["textAlignment"] = "center";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["font"] = "DefaultSmall";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMaxHealthValue"]["fgcolor"] = "TanDark";
	}
	if (typeof data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"] == 'undefined') {
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"] = {};
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["ControlName"] = "ImagePanel";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["fieldName"] = "PlayerStatusGasImage";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["xpos"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["ypos"] = "405";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["zpos"] = "7";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["wide"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["tall"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["visible"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["enabled"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["scaleImage"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["image"] = "../vgui/covered_in_gas";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusGasImage"]["fgcolor"] = "TanDark";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"] = {};
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["ControlName"] = "ImagePanel";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["fieldName"] = "PlayerStatusSlowed";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["xpos"] = "0";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["ypos"] = "405";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["zpos"] = "7";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["wide"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["tall"] = "32";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["visible"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["enabled"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["scaleImage"] = "1";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["image"] = "../vgui/slowed";
		data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusSlowed"]["fgcolor"] = "TanDark";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_operation"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_operation"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["Background"]["if_operation"]["image"] = "../console/title_team_jungle_inferno_2017_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["ControlName"] = "ImagePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["fieldName"] = "JungleInfernoImage";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["xpos"] = "c170";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["ypos"] = "64";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["zpos"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["wide"] = "o4";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["tall"] = "32";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["image"] = "../logo/inferno_logo_anim";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["scaleImage"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["if_operation"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["if_operation"]["visible"] = "1";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TFCharacterImage"]["if_operation"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TFCharacterImage"]["if_operation"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TFCharacterImage"]["if_operation"]["image"] = "../console/title_team_jungle_inferno_2017_widescreen";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["ControlName"] = "CPvPRankPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["fieldName"] = "RankModelPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["xpos"] = "cs-0.5+160";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["ypos"] = "cs-0.5-120";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["zpos"] = "-51";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["wide"] = "1000";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["tall"] = "1000";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["mouseinputenabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["matchgroup"] = "MatchGroup_Casual_12v12";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["show_progress"] = "0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["ControlName"] = "CPvPRankPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["fieldName"] = "RankPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xpos"] = "c75";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["ypos"] = "72";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["zpos"] = "-52";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["wide"] = "320";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["tall"] = "100";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["matchgroup"] = "MatchGroup_Casual_12v12";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xp_source_notification_center_x"] = "-75";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["show_model"] = "0";
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"] != 'undefined') {
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["NotificationsContainer"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"] != 'undefined') {
		delete data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["PlayListContainer"];
	}
	if (typeof data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"] == 'undefined') {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["fieldname"] = "FriendsContainer";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["xpos"] = "c135";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["ypos"] = "260";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["zpos"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["wide"] = "260";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["tall"] = "150";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["border"] = "MainMenuBGBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["ControlName"] = "CExLabel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["fieldName"] = "TitleLabel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["font"] = "HudFontSmallBold";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["labelText"] = "#TF_Competitive_Friends";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["textAlignment"] = "west";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["xpos"] = "12";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["wide"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["tall"] = "30";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["autoResize"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["pinCorner"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["enabled"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["textinsetx"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["TitleLabel"]["fgcolor_override"] = "235 227 203 255";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["fieldname"] = "InnerShadow";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["ypos"] = "rs1-10";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["zpos"] = "501";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["wide"] = "f20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["tall"] = "110";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["PaintBackgroundType"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["paintborder"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["InnerShadow"]["border"] = "InnerShadowBorder";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ControlName"] = "CSteamFriendsListPanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["fieldname"] = "SteamFriendsList";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ypos"] = "rs1-10";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["zpos"] = "500";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["wide"] = "f20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["tall"] = "110";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["visible"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["columns_count"] = "2";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["inset_x"] = "10";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["inset_y"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["row_gap"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["column_gap"] = "20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["restrict_width"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["friendpanel_kv"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["friendpanel_kv"]["wide"] = "100";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["friendpanel_kv"]["tall"] = "20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["ControlName"] = "ScrollBar";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["FieldName"] = "ScrollBar";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["xpos"] = "rs1-1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["ypos"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["tall"] = "f0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["wide"] = "5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["zpos"] = "1000";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["nobuttons"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["Slider"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["Slider"]["fgcolor_override"] = "TanDark";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["UpButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["UpButton"]["ControlName"] = "Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["UpButton"]["FieldName"] = "UpButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["UpButton"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["DownButton"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["DownButton"]["ControlName"] = "Button";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["DownButton"]["FieldName"] = "DownButton";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["SteamFriendsList"]["ScrollBar"]["DownButton"]["visible"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"] = {};
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["ControlName"] = "EditablePanel";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["fieldname"] = "BelowDarken";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["xpos"] = "cs-0.5";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["ypos"] = "rs1-10";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["zpos"] = "499";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["wide"] = "f20";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["tall"] = "110";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["visible"] = "1"	;
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["PaintBackgroundType"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["proportionaltoparent"] = "1";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["mouseinputenabled"] = "0";
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["BelowDarken"]["bgcolor_override"] = "0 0 0 100";
	}
	if (typeof data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"] == 'undefined') {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"] = {};
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["fieldName"] = "QueueHUDStatus";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["visible"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["enabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["xpos"] = "rs1-5";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["ypos"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["zpos"] = "1001";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["wide"] = "200";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["tall"] = "18";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["proportionaltoparent"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["keyboardinputenabled"] = "1";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["mouseinputenabled"] = "0";
		data["jsonhudlayout"]["Resource/HudLayout.res"]["QueueHUDStatus"]["alpha"] = "100";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TooltipPanel"]["zpos"] = "1") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["TooltipPanel"]["zpos"] = "1000";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["image"] = "button_quests") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["QuestLogButton"]["SubButton"]["SubImage"]["image"] = "button_quests_pda";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["xpos"] == "c-290+64") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["JungleInfernoImage"]["xpos"] = "c170";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["xpos"] == "c-290") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["FriendsContainer"]["xpos"] = "c135";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["xpos"] == "cs-0.5-256") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankModelPanel"]["xpos"] = "cs-0.5+160";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xpos"] == "c-350") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xpos"] = "c75";
	}
	if (data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xp_source_notification_center_x"] == "350") {
		data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RankPanel"]["xp_source_notification_center_x"] = "-75";
	}


	//
	// March, 28th Patch - Matchmaking
	//
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["fieldName"] = "RankUpLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["font"] = "HudFontMediumSmallBold";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["xpos"] = "cs-0.5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["ypos"] = "80";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["wide"] = "600";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["tall"] = "60";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["wrap"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["centerwrap"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["alpha"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["labelText"] = "%rank_possibility%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpLabel"]["fgcolor"] = "TanLight";
	}
	if (typeof data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"] == 'undefined') {
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"] = {};
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["ControlName"] = "CExLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["fieldName"] = "RankUpShadowLabel";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["font"] = "HudFontMediumSmallBold";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["xpos"] = "cs-0.5+2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["ypos"] = "80+2";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["wide"] = "600";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["tall"] = "60";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["zpos"] = "5";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["autoResize"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["pinCorner"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["visible"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["enabled"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["wrap"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["centerwrap"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["alpha"] = "0";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["labelText"] = "%rank_possibility%";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["textAlignment"] = "center";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["proportionaltoparent"] = "1";
		data["jsonhudmatchstatus"]["Resource/UI/Competitive.res"]["RankUpShadowLabel"]["fgcolor"] = "QHUDShadow";
	}


//**********************************************************************//
//			Function to update inputs according to div style			//
//**********************************************************************//
	function controlElement(name) {
		$("#hudElementParameters").show();
//		$(".region-sidebar-content").animate({ scrollTop: 478 }, 0);
		$(".region-sidebar-content").scrollTop(478);

		$('#canv-region *').removeClass('selected');
		$('#sortable *').removeClass('sortableSelected');
		$("#"+name).addClass('selected');
		$("#"+name+"Ahref").addClass('sortableSelected');
		$('#element-name').val( name );
		$('#element-xpos').val( Number($("#"+name).css("left").replace('px','')) );
		$('#element-ypos').val( Number($("#"+name).css("top").replace('px','')) );
		$('#element-wide').val( Number($("#"+name).css("width").replace('px','')) );
		$('#element-tall').val( Number($("#"+name).css("height").replace('px','')) );

		var tempfontname = $("#"+name).css("font-family").replace(/\'/g,"").replace(/\"/g,""); // fix for chrome
		var tempfontsize = $("#"+name).css("font-size").replace("px","");

		// in data
		if (tempfontname === "Crosshairs") {
			tempfontsize = String(Number(tempfontsize) - 5);
		} else if (tempfontname === "TF2 Build") {
			tempfontsize = String(Math.round((Number(tempfontsize)*10)/(10 - 1)));
		} else if (tempfontname === "PF Tempesta Seven") {
			tempfontsize = String(Math.round((Number(tempfontsize)*2.5)/(2.5 - 1)));
		} else if (tempfontname === "Counter-Strike") {
			tempfontsize = String(Number(tempfontsize) - 7);
		} else {
			tempfontsize = String(Math.round((Number(tempfontsize)*5)/(5 - 1)));
		}

		$("#element-fontname" ).val( tempfontname );
		$('#element-fontsize').val( tempfontsize );

		if ( name === "HealthBG" || name === "TargetBGshade" || name === "HorizontalLine2" || name === "VerticalLineScoreboard" || name === "MainMenuBG" || name.substring(0, 11) === "coloredBox_" || name === "TargetHealthBG" || name === "FreezePanelBGTitle" ) {
			$('#element-color-normal').val( $("#"+name).css("background-color") );
			$('#colorpicker-normal').spectrum( "set", $("#"+name).css("background-color") );
		} else {
			$('#element-color-normal').val( $("#"+name).css("color") );
			$('#colorpicker-normal').spectrum( "set", $("#"+name).css("color") );
		}
		
		$('#colorpicker-teamblue').spectrum( "set", $('#element-color-teamblue').val() );
		$('#colorpicker-teamred').spectrum( "set", $('#element-color-teamred').val() );
		$('#colorpicker-basebg').spectrum( "set", $('#element-color-basebg').val() );
		$('#colorpicker-localbg').spectrum( "set", $('#element-color-localbg').val() );
		$('#colorpicker-damagecolor').spectrum( "set", $('#element-color-damagecolor').val() );
		$('#colorpicker-healingcolor').spectrum( "set", $('#element-color-healingcolor').val() );
		$('#colorpicker-chargefg').spectrum( "set", $('#element-chargefg').val() );
		$('#colorpicker-chargebg').spectrum( "set", $('#element-chargebg').val() );

		if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton" || name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton" || name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
			$('#element-color-hover').val( $("#"+name).attr("hovercolor") );
			$('#colorpicker-hover').spectrum( "set", $("#"+name).attr("hovercolor") );
		}

		//**********************************************************************//
		//			Get high and low health colors from clientscheme			//
		//**********************************************************************//
		if (name === "PlayerStatusHealthValue") {
			var varhighhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverheal"].split(' ');
			var varlowhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLow"].split(' ');
			varhighhealth = "rgba("+varhighhealth[0]+","+varhighhealth[1]+","+varhighhealth[2]+"," +(Math.round((Number(varhighhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-high').val( varhighhealth );
			$('#colorpicker-high').spectrum( "set", $('#element-color-high').val() );
			varlowhealth = "rgba("+varlowhealth[0]+","+varlowhealth[1]+","+varlowhealth[2]+"," +(Math.round((Number(varlowhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-low').val( varlowhealth );
			$('#colorpicker-low').spectrum( "set", $('#element-color-low').val() );
		} else if (name === "HealthBG") {
			var varhighhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverhealBar"].split(' ');
			var varlowhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLowBar"].split(' ');
			varhighhealth = "rgba("+varhighhealth[0]+","+varhighhealth[1]+","+varhighhealth[2]+"," +(Math.round((Number(varhighhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-high-bar').val( varhighhealth );
			$('#colorpicker-high-bar').spectrum( "set", $('#element-color-high-bar').val() );
			varlowhealth = "rgba("+varlowhealth[0]+","+varlowhealth[1]+","+varlowhealth[2]+"," +(Math.round((Number(varlowhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-low-bar').val( varlowhealth );
			$('#colorpicker-low-bar').spectrum( "set", $('#element-color-low-bar').val() );
		} else if (name === "ChargeMeter") {
			varmediccharge1 = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge1"].split(' ');
			varmediccharge2 = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge2"].split(' ');
			varmediccharge1 = "rgba("+varmediccharge1[0]+","+varmediccharge1[1]+","+varmediccharge1[2]+"," +(Math.round((Number(varmediccharge1[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-uber1').val( varmediccharge1 );
			$('#colorpicker-uber1').spectrum( "set", $('#element-color-uber1').val() );
			varmediccharge2 = "rgba("+varmediccharge2[0]+","+varmediccharge2[1]+","+varmediccharge2[2]+"," +(Math.round((Number(varmediccharge2[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-uber2').val( varmediccharge2 );
			$('#colorpicker-uber2').spectrum( "set", $('#element-color-uber2').val() );
		} else if (name === "xHair") {
			varhitcolor = data["jsonclientscheme"]["Scheme"]["Colors"]["xHairHit"].split(' ');
			varhitcolor = "rgba("+varhitcolor[0]+","+varhitcolor[1]+","+varhitcolor[2]+"," +(Math.round((Number(varhitcolor[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-hit').val( varhitcolor );
			$('#colorpicker-hit').spectrum( "set", $('#element-color-hit').val() );
		} else if (name === "AmmoInClip") {
			varlowammocolor = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"].split(' ');
			varlowammocolor = "rgba("+varlowammocolor[0]+","+varlowammocolor[1]+","+varlowammocolor[2]+"," +(Math.round((Number(varlowammocolor[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-ammo-low-clip').val( varlowammocolor );
			$('#colorpicker-ammo-low-clip').spectrum( "set", $('#element-color-ammo-low-clip').val() );
		} else if (name === "AmmoInReserve") {
			varlowammocolor = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowReserve"].split(' ');
			varlowammocolor = "rgba("+varlowammocolor[0]+","+varlowammocolor[1]+","+varlowammocolor[2]+"," +(Math.round((Number(varlowammocolor[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-ammo-low-reserve').val( varlowammocolor );
			$('#colorpicker-ammo-low-reserve').spectrum( "set", $('#element-color-ammo-low-reserve').val() );
		} else if (name === "TimePanelValue") {
			var bluecolor = data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["fgcolor"].split(' ');
			var redcolor = data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["fgcolor"].split(' ');
			bluecolor = "rgba("+bluecolor[0]+","+bluecolor[1]+","+bluecolor[2]+"," +(Math.round((Number(bluecolor[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-kothblue').val( bluecolor );
			$('#colorpicker-kothblue').spectrum( "set", $('#element-color-kothblue').val() );
			redcolor = "rgba("+redcolor[0]+","+redcolor[1]+","+redcolor[2]+"," +(Math.round((Number(redcolor[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-kothred').val( redcolor );
			$('#colorpicker-kothred').spectrum( "set", $('#element-color-kothred').val() );
		}

		if (name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ItemEffectMeterSpycicle") {
			var meterfg = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterFG"].split(' ');
			var meterbg = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterBG"].split(' ');
			meterfg = "rgba("+meterfg[0]+","+meterfg[1]+","+meterfg[2]+"," +(Math.round((Number(meterfg[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-chargefg').val( meterfg );
			$('#colorpicker-chargefg').spectrum( "set", $('#element-chargefg').val() );
			meterbg = "rgba("+meterbg[0]+","+meterbg[1]+","+meterbg[2]+"," +(Math.round((Number(meterbg[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-chargebg').val( meterbg );
			$('#colorpicker-chargebg').spectrum( "set", $('#element-chargebg').val() );
		}

		if (name === "TargetHealthBG") {
			var varhighhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarHigh"].split(' ');
			var varlowhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"].split(' ');
			var varnormalhealth = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarNormal"].split(' ');
			varhighhealth = "rgba("+varhighhealth[0]+","+varhighhealth[1]+","+varhighhealth[2]+"," +(Math.round((Number(varhighhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-high-targetbar').val( varhighhealth );
			$('#colorpicker-high-targetbar').spectrum( "set", $('#element-color-high-targetbar').val() );
			varlowhealth = "rgba("+varlowhealth[0]+","+varlowhealth[1]+","+varlowhealth[2]+"," +(Math.round((Number(varlowhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-low-targetbar').val( varlowhealth );
			$('#colorpicker-low-targetbar').spectrum( "set", $('#element-color-low-targetbar').val() );
			varnormalhealth = "rgba("+varnormalhealth[0]+","+varnormalhealth[1]+","+varnormalhealth[2]+"," +(Math.round((Number(varnormalhealth[3])*1.0/255) * 100) / 100)+ ")";
			$('#element-color-normal-targetbar').val( varnormalhealth );
			$('#colorpicker-normal-targetbar').spectrum( "set", $('#element-color-normal-targetbar').val() );
		}

		$('#element-cornerradius').val( $("#"+name).css("border-top-left-radius").replace('px','') );

// if text-shadow style exists => check or uncheck checkbox
//		if ( $("#"+name+"[style*='text-shadow']").length ) {
//			$('#element-shadow').prop( "checked", true );
//		} else {
//			$('#element-shadow').prop( "checked", false );
//		}
		
		// small damageaccountvalue fix
		if (name === "DamageAccountValue") {
			// on screen
			var tempdmgfont = $("#"+name).attr("huddamagefont");
			var tempdmgsize = $("#"+name).attr("huddamagesize");
			var tempdmgoutline = $("#"+name).attr("huddamageoutline");
			$('#element-damage-fontname').val( tempdmgfont );
			$('#element-damage-fontsize').val( tempdmgsize );
			if (tempdmgoutline === "1") {
				$('#element-damage-fontoutline').prop( "checked", true );
			} else {
				$('#element-damage-fontoutline').prop( "checked", false );
			}
		}

		if (name === "HudDeathNotice") {
			var temp = eval("data" + getObjPath(data,name,"",""));
			$('#element-killjustify').val(temp["RightJustify"]);
			$("#divjustify").show();
		}

		if (name.substring(0, 12) === "customLabel_") {
			var temp = eval("data" + getObjPath(data,name,"",""));
			$('#element-labeltext').val(temp["labelText"]);
		}

		$( "#divxpos" ).show();
		$( "#divypos" ).show();
		$( "#highhealth" ).hide();
		$( "#lowhealth" ).hide();
		$( "#div-fontname" ).hide();
		$( "#div-fontsize" ).hide();
		$( "#divshadow" ).hide();
		$( "#damagecolor" ).hide();
		$( "#healingcolor" ).hide();
		$( "#div-damage-fontname" ).hide();
		$( "#div-damage-fontsize" ).hide();
		$( "#div-damage-outline" ).hide();
		$( "#normalhealth" ).hide();
		$( "#divwide" ).show();
		$( "#divtall" ).show();
		$( "#cornerradius" ).hide();
		$( "#teambluediv" ).hide();
		$( "#teamreddiv" ).hide();
		$( "#divbasebg" ).hide();
		$( "#divlocalbg" ).hide();
		$( "#divbuff" ).hide();
		$( "#divmedicoffset" ).hide();
		$( "#divxhairtype" ).hide();
		$( "#divxhairsize" ).hide();
		$( "#divxhairoutline" ).hide();
		$( "#highhealthbar" ).hide();
		$( "#lowhealthbar" ).hide();
		$( "#hitmarker" ).hide();
		$( "#divuber1" ).hide();
		$( "#divuber2" ).hide();
		$( "#divhover" ).hide();
		$( "#divalign" ).hide();
		$( "#divjustify" ).hide();
		$( "#divchargefg" ).hide();
		$( "#divchargebg" ).hide();
		$( "#lowammo-inclip" ).hide();
		$( "#lowammo-inreserve" ).hide();
		$( "#divkothblue" ).hide();
		$( "#divkothred" ).hide();
		$( "#divtargetlow" ).hide();
		$( "#divtargetnormal" ).hide();
		$( "#divtargethigh" ).hide();
		$( "#divoutline" ).hide();
		$( "#divlabeltext" ).hide();

		if (typeof getObjPath(data,name,"","") !== 'undefined') {
			var temp = eval("data" + getObjPath(data,name,"",""));
			if (typeof temp["font"] != 'undefined') {
				$( "#div-fontname" ).show();
				$( "#div-fontsize" ).show();
				$( "#divoutline" ).show();
				if ($("#"+name).hasClass("outline")) {
					$('#element-outline').prop( "checked", true );
				} else {
					$('#element-outline').prop( "checked", false );
				}
			}
			if (typeof temp["fgcolor"] != 'undefined' || typeof temp["fillcolor"] != 'undefined' || typeof temp["TextColor"] != 'undefined' || typeof temp["fgcolor_override"] != 'undefined') {
				$( "#normalhealth" ).show();
			}
			if (typeof temp["CornerRadius"] != 'undefined' || typeof temp["draw_corner_width"] != 'undefined') {
				$( "#cornerradius" ).show();
			}
			if (typeof temp["textAlignment"] != 'undefined') {
				if (temp["textAlignment"] === "center" || temp["textAlignment"] === "north") {
					$('#element-alignment').val("north");
				} else if (temp["textAlignment"] === "right" || temp["textAlignment"] === "east" || temp["textAlignment"] === "north-east") {
					$('#element-alignment').val("north-east");
				} else if (temp["textAlignment"] === "left" || temp["textAlignment"] === "west" || temp["textAlignment"] === "north-west") {
					$('#element-alignment').val("north-west");
				}
				$( "#divalign" ).show();
			}
			if ( temp["fgcolor"] === 'default_color' ) {
				$( "#normalhealth" ).hide();
				$( "#div-fontname" ).hide();
				$( "#div-fontsize" ).hide();
			}
		}

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
		}

		if ( typeof tempShadow != 'undefined' ) {
			var visibleShadow = tempShadow["visible"];
			$( "#divshadow" ).show();
			if ( visibleShadow === "1" ) {
				$('#element-shadow').prop( "checked", true );
			} else if ( visibleShadow === "0" ) {
				$('#element-shadow').prop( "checked", false );
			}
		}

		if ($("#"+name).attr("fontfix") === "Default") {
			$( "#div-fontname" ).hide();
			$( "#div-fontsize" ).hide();
		}

		// Hide unrelated input fields
		if (name === "PlayerStatusHealthValue") {
			$( "#highhealth" ).show();
			$( "#lowhealth" ).show();
			$( "#divbuff" ).show();
		}
		if (name === "HudDeathNotice") {
			$( "#divwide" ).hide();
			$( "#divtall" ).hide();
			$( "#normalhealth" ).hide();
			$( "#teambluediv" ).show();
			$( "#teamreddiv" ).show();
			$( "#divbasebg" ).show();
			$( "#divlocalbg" ).show();
		}
		if (name === "DamageAccountValue") {
			$( "#damagecolor" ).show();
			$( "#healingcolor" ).show();
			$( "#div-damage-fontname" ).show();
			$( "#div-damage-fontsize" ).show();
			$( "#div-damage-outline" ).show();
		}
		if (name === "TargetBGshade" || name === "TargetNameLabel" || name === "TargetDataLabel") {
			$( "#divwide" ).hide();
		}
		if (name === "TargetBGshade" || name === "SpectatorGUIHealth" || name === "TargetNameLabel" || name === "TargetDataLabel") {
			$( "#divxpos" ).hide();
		}
		if (name === "TargetBGshade" || name === "SpectatorGUIHealth" || name === "TargetNameLabel" || name === "TargetDataLabel" || name === "TargetHealthBG") {
			$( "#divmedicoffset" ).show();
		}
		if (name === "RedPlayerList" || name === "BluePlayerList" || name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ItemEffectMeterSpycicle") {
			$( "#div-fontname" ).hide();
			$( "#div-fontsize" ).hide();
			$( "#divalign" ).hide();
		}
		if (name === "RedPlayerList" || name === "BluePlayerList" || name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ItemEffectMeterSpycicle" || name === "HudDeathNotice" || name === "xHair") {
			$( "#divoutline" ).hide();
		}
		if (name === "SpectatorGUIHealth" || name === "SpectatorGUIHealthSpy") {
			$( "#normalhealth" ).hide();
			$( "#div-fontname" ).show();
			$( "#div-fontsize" ).show();
		}
		if (name === "xHair") {
			$( "#divwide" ).hide();
			$( "#divtall" ).hide();
			$( "#div-fontname" ).hide();
			$( "#div-fontsize" ).hide();
			$( "#divxhairtype" ).show();
			$( "#divxhairsize" ).show();
			$( "#hitmarker" ).show();
			$( "#divxhairoutline" ).show();
		}
		if (name === "xHair" || name === "TargetBGshade" || name === "PlayerStatusHealthBonusImage" || name === "PlayerStatusHealthImageBG" || name === "PlayerStatusHealthImage" || name === "RandomFrame" || name === "SpectateFrame" || name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ShadedBarWP" || name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatusMarkedForDeathImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom" || name === "PlayerStatus_MedicUberBulletResistImage") {
			$( "#divalign" ).hide();
		}

		if (name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatusMarkedForDeathImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom" || name === "PlayerStatus_MedicUberBulletResistImage") {
			$( "#normalhealth" ).hide();
		}

		if (name.substring(0, 11) === "coloredBox_") {
			$( "#divalign" ).hide();
			$( "#normalhealth" ).show();
		}
		if (name === "HealthBG") {
			$( "#highhealthbar" ).show();
			$( "#lowhealthbar" ).show();
		}
		if (name === "TargetHealthBG") {
			$( "#divtargetlow" ).show();
			$( "#divtargetnormal" ).show();
			$( "#divtargethigh" ).show();
		}

		if (name === "FreezeLabelKiller" || name === "PlayerStatusHealthValueFreezecam" || name === "QHUDKillerName") {
			$( "#div-fontname" ).show();
			$( "#div-fontsize" ).show();
		}
		if (name === "ChargeMeter") {
			$( "#divuber1" ).show();
			$( "#divuber2" ).show();
		}
		if (name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ItemEffectMeterSpycicle") {
			$( "#divchargefg" ).show();
			$( "#divchargebg" ).show();
		}
		if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton" || name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
			$( "#normalhealth" ).show();
			$( "#div-fontname" ).show();
			$( "#div-fontsize" ).show();
			$( "#divhover" ).show();
		}
		if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
			$( "#normalhealth" ).show();
			$( "#divhover" ).show();
			$( "#div-fontname" ).hide();
			$( "#div-fontsize" ).hide();
		}
		if (name === "AmmoInClip") {
			$( "#lowammo-inclip" ).show();
		}
		if (name === "AmmoInReserve") {
			$( "#lowammo-inreserve" ).show();
		}
		if (name === "TimePanelValue") {
			$( "#divkothblue" ).show();
			$( "#divkothred" ).show();
		}
		if (name.substring(0, 12) === "customLabel_") {
			$( "#divlabeltext" ).show();
		}

	}


//**********************************************************//
//			Function to draw each element of hud			//
//**********************************************************//
	function drawElement(name, label, canvasname) {
		// first check if element is already placed on canvas
		if ($("#"+name).length > 0) {
			return;
		} else {
			//**********************************************************//
			//				Get xpos,ypos,wide,tall values				//
			//**********************************************************//
			var temp;
			var defined = 0;
			if (name === "SpectatorGUIHealthSpy") {
				var tempname = "SpectatorGUIHealth";
				temp = eval('data["jsondisguisestatuspanel"]' + getObjPath(data["jsondisguisestatuspanel"],tempname,"",""));
				defined = 1;
			} else if (name === "ItemEffectMeterSpycicle") {
				var tempname = "ItemEffectMeter";
				temp = eval('data["jsonhuditemeffectmeterscout"]' + getObjPath(data["jsonhuditemeffectmeterscout"],tempname,"",""));
				defined = 1;
			} else {
				if (typeof getObjPath(data,name,"","") !== 'undefined') {
					temp = eval("data" + getObjPath(data,name,"",""));
					defined = 1;
				} else {
					if (name === "blueframe") {
						name = "teambutton0";
						temp = eval("data" + getObjPath(data,name,"",""));
						defined = 1;
					} else if (name === "redframe") {
						name = "teambutton1";
						temp = eval("data" + getObjPath(data,name,"",""));
						defined = 1;
					} else if (name === "RandomFrame") {
						name = "teambutton2";
						temp = eval("data" + getObjPath(data,name,"",""));
						defined = 1;
					} else if (name === "SpectateFrame") {
						name = "teambutton3";
						temp = eval("data" + getObjPath(data,name,"",""));
						defined = 1;
					} else {
						console.log("Element " + name + " does not exist in resource files.");
						defined = 0;
//						console.log(data);
					}
				}
			}

			if ( defined === 1 ) {

				var tempShadow;
				if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
					tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
				} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
					tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
				} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
					tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
				}

				if ( typeof tempShadow != 'undefined' ) {
					var visibleShadow = tempShadow["visible"];
				}

				if (temp === null) { console.log(temp); }

				var xpos, ypos;
				if (name === "playerpanels_kv") {
					xpos = data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_base_offset_x"];
					ypos = data["jsonhudtournament"]["Resource/UI/HudTournament.res"]["HudTournament"]["team1_player_base_y"];
				} else {
					xpos = temp["xpos"];
					ypos = temp["ypos"];
				}
				var wide = temp["wide"];
				var tall = temp["tall"];

				if (tall === "f0") { tall = "480"; }

				// check if xpos and ypos have "c" or "r" letters in them
				if ( xpos.indexOf("c") > -1 ) { xpos = String(Math.floor((Number(localStorage.canvaswidth) / 2) + Number(xpos.replace("c","")))); }
				else if ( xpos.indexOf("r") > -1 ) { xpos = String(Math.floor((Number(localStorage.canvaswidth)) - Number(xpos.replace("r","")))); }
				if ( ypos.indexOf("c") > -1 ) { ypos = String(Math.floor((Number(localStorage.canvasheight) / 2) + Number(ypos.replace("c","")))); }
				else if ( ypos.indexOf("r") > -1 ) { ypos = String(Math.floor((Number(localStorage.canvasheight)) - Number(ypos.replace("r","")))); }
			
				if (temp["xpos"] === "9001") {
					temp["xpos"] = "240";
					xpos = "240";
				}
				
				if (name === "TargetBGshade") {
					wide = "150";
					var offset = Number(data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"]) - Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["tall"]) - Number(data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"]);
					$('#element-medicoffset').val( offset );
				}

				if (name === "TargetNameLabel" || name === "TargetDataLabel") {
					wide = "100";
				}

				var position = "absolute";

				if (typeof temp["visible"] != 'undefined') {
					temp["visible"] = "1";
					if (name === "PlayerStatusMarkedForDeathImage") {
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["visible"] = "1";
					}
					if (name === "PlayerStatus_MedicUberBulletResistImage") {
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["visible"] = "1";
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["visible"] = "1";
					}
				}

				if ( name === "PlayerStatusHealthImageBG") {
//					if  ( temp["visible"] === "1" )  {
//						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["visible"] = "1";
//					}
					if ( data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["visible"] === "1" ) {
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["xpos"] = xpos;
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["ypos"] = ypos;
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["wide"] = wide;
						data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["tall"] = tall;
					}
				}

				if ( data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["visible"] == "1" ) {
					$('#element-buffed').prop( "checked", true );
				} else {
					$('#element-buffed').prop( "checked", false );
				}


				//******************************************************//
				//				Get element color and font				//
				//******************************************************//
				var fgcolor = "";
				if (name === "HealthBG") {
					fgcolor = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLowBar"];
				} else if (name === "TargetHealthBG") {
					fgcolor = data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"];
				} else if (name === "TargetNameLabel" || name === "TargetDataLabel" || name === "ItemEffectMeterLabelKillstreak" || name === "ItemEffectMeterCountKillstreak" || name === "DisguiseNameLabel" || name === "WeaponNameLabel" ) {
					if (typeof temp["fgcolor_override"] != 'undefined') {
						fgcolor = temp["fgcolor_override"];
					} else {
						fgcolor = temp["fgcolor"];
					}
				} else if (name === "SpectatorGUIHealth") {
					fgcolor = temp["TextColor"];
				} else if (name === "SpectatorGUIHealthSpy") {
					fgcolor = data["jsontargetid"]["Resource/UI/TargetID.res"]["SpectatorGUIHealth"]["TextColor"];
				} else if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
					fgcolor = temp["SubButton"]["defaultFgColor_override"];
				} else if (name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
					fgcolor = temp["defaultFgColor_override"];
				} else if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
					fgcolor = temp["image_drawcolor"];
				} else if (name.substring(0, 11) === "coloredBox_") {
					fgcolor = temp["defaultBgColor_Override"];
				} else {
					fgcolor = temp["fgcolor"];
				}

				if (typeof temp["fillcolor"] != 'undefined') {
					fgcolor = temp["fillcolor"];
				}

				if (temp["fgcolor"] === 'default_color') {
					fgcolor = "m0rewhite";
				}

				var font = "";
				if (name === "HudDeathNotice") {
					font = temp["TextFont"];
				} else if (name === "SpectatorGUIHealth" || name === "SpectatorGUIHealthSpy") {
					font = data["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["font"];
				} else if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
					font = temp["SubButton"]["font"];
				} else {
					font = temp["font"];
				}

				function getFGColor(fgcolor) {
					if (typeof fgcolor != 'undefined') {
						var fgcolornumber = "";
						if (fgcolor.match(/^[\d\s+-]+$/)) {
							fgcolornumber = fgcolor;
						} else {
							if (fgcolor === "black" || fgcolor === "white" || fgcolor === "blue" || fgcolor === "red") {
								fgcolor = fgcolor.charAt(0).toUpperCase() + fgcolor.slice(1);
							}
							fgcolornumber = data["jsonclientscheme"]["Scheme"]["Colors"][fgcolor];
						}
						return fgcolornumber = fgcolornumber.split(" "); // fgcolornumber - array of numbers for color
					}
				}

				var fontfix = "";
				if (typeof font != 'undefined') {
					var fontname;
					var fontsize;
					if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"][font] == 'undefined' && font.substring(0, 8) === "m0refont") {
						font = font.charAt(0).toUpperCase() + font.slice(1);
					}
					if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"][font] == 'undefined') {
						fontname = "";
//						fontsize = data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["tall"];
						fontfix = "Default";
					} else {
						fontname = data["jsonclientscheme"]["Scheme"]["Fonts"][font]["1"]["name"]; // font resource name
						fontsize = data["jsonclientscheme"]["Scheme"]["Fonts"][font]["1"]["tall"]; // font size = tall
					}
					if (name === "BluePlayerList" || name === "RedPlayerList" || name === "Player1Name" || name === "Player1Class" || name === "Player1Score" || name === "Player2Name" || name === "Player2Class" || name === "Player2Score" || name === "Player3Name" || name === "Player3Class" || name === "Player3Score" || name === "KillStreakPlayer1Name" || name === "KillStreakPlayer1Class" || name === "KillStreakPlayer1Score") {
						fontname = "";
//						fontsize = data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["tall"];
						fontfix = "Default";
					}
					
//					if (fontname === "BoomBox 2") { fontname = "boombox2"; } // garmenHUD fix
//					if (fontname === "Roboto Medium") { fontname = "Roboto-Medium"; } // morgHUD fix

					// on screen
					if (fontname === "Crosshairs") {
						fontsize = String(Number(fontsize) + 5);
					} else if (fontname === "TF2 Build") {
						fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 10)));
					} else if (fontname === "PF Tempesta Seven") {
						fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 2.5)));
					} else if (fontname === "Counter-Strike") {
						fontsize = String(Number(fontsize) + 7);
					} else {
						fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 5)));
					}
				}

				if ( canvasname === "#TargetBGshade" || canvasname === "#targetidwrapper" ) {
					$("#sortable li").children("#SubUl-hud-canvas").append('<li id="'+name+'-Li" class="ui-state-default"><a id="'+name+'Ahref">'+name+'</a><button id="remove"></button></li>');
				} else {
					$("#sortable li").children("#SubUl-"+canvasname.replace(".","")).append('<li id="'+name+'-Li" class="ui-state-default"><a id="'+name+'Ahref">'+name+'</a><button id="remove"></button></li>');
				}

				if (name === "xHair") {
					ypos = String( Math.round( Number($(".hud-canvas").height())/2 - Number(tall)/2 ) );
					xpos = String( Math.round( Number($(".hud-canvas").width())/2 - Number(wide)/2 ) + 11 );
					temp["xpos"] = xpos;
					temp["ypos"] = ypos;
					fontsize = data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["tall"];
					$("#element-xhair-size").val(data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["tall"]);
					$("<option value='`'>`</option>").appendTo("#element-xhair-type");
					$("<option value='0'>0</option>").appendTo("#element-xhair-type");
					$("<option value='1'>1</option>").appendTo("#element-xhair-type");
					$("<option value='2'>2</option>").appendTo("#element-xhair-type");
					$("<option value='3'>3</option>").appendTo("#element-xhair-type");
					$("<option value='4'>4</option>").appendTo("#element-xhair-type");
					$("<option value='5'>5</option>").appendTo("#element-xhair-type");
					$("<option value='6'>6</option>").appendTo("#element-xhair-type");
					$("<option value='7'>7</option>").appendTo("#element-xhair-type");
					$("<option value='8'>8</option>").appendTo("#element-xhair-type");
					$("<option value='9'>9</option>").appendTo("#element-xhair-type");
					$("<option value='-'>-</option>").appendTo("#element-xhair-type");
					$("<option value='='>=</option>").appendTo("#element-xhair-type");
					$("<option value='['>[</option>").appendTo("#element-xhair-type");
					$("<option value=']'>]</option>").appendTo("#element-xhair-type");
					$("<option value='\'>\</option>").appendTo("#element-xhair-type");
					$("<option value='/'>/</option>").appendTo("#element-xhair-type");
					$("<option value=','>,</option>").appendTo("#element-xhair-type");
					$("<option value='.'>.</option>").appendTo("#element-xhair-type");
					$("<option value='a'>a</option>").appendTo("#element-xhair-type");
					$("<option value='b'>b</option>").appendTo("#element-xhair-type");
					$("<option value='c'>c</option>").appendTo("#element-xhair-type");
					$("<option value='d'>d</option>").appendTo("#element-xhair-type");
					$("<option value='e'>e</option>").appendTo("#element-xhair-type");
					$("<option value='f'>f</option>").appendTo("#element-xhair-type");
					$("<option value='g'>g</option>").appendTo("#element-xhair-type");
					$("<option value='h'>h</option>").appendTo("#element-xhair-type");
					$("<option value='i'>i</option>").appendTo("#element-xhair-type");
					$("<option value='j'>j</option>").appendTo("#element-xhair-type");
					$("<option value='k'>k</option>").appendTo("#element-xhair-type");
					$("<option value='l'>l</option>").appendTo("#element-xhair-type");
					$("<option value='m'>m</option>").appendTo("#element-xhair-type");
					$("<option value='n'>n</option>").appendTo("#element-xhair-type");
					$("<option value='o'>o</option>").appendTo("#element-xhair-type");
					$("<option value='p'>p</option>").appendTo("#element-xhair-type");
					$("<option value='q'>q</option>").appendTo("#element-xhair-type");
					$("<option value='r'>r</option>").appendTo("#element-xhair-type");
					$("<option value='s'>s</option>").appendTo("#element-xhair-type");
					$("<option value='t'>t</option>").appendTo("#element-xhair-type");
					$("<option value='u'>u</option>").appendTo("#element-xhair-type");
					$("<option value='v'>v</option>").appendTo("#element-xhair-type");
					$("<option value='w'>w</option>").appendTo("#element-xhair-type");
					$("<option value='x'>x</option>").appendTo("#element-xhair-type");
					$("<option value='y'>y</option>").appendTo("#element-xhair-type");
					$("<option value='z'>z</option>").appendTo("#element-xhair-type");
				}
				
				if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
					fontname = "TF2Glyphs";
					fontsize = String(Number(temp["SubImage"]["wide"]) + 8);
				}

				if (typeof fontname === 'undefined') {
					console.log("Error getting element font: "+name+" - "+font+" - "+fontname+". Clearing this value.");
					fontname = "";
					fontfix = "Default";
				}

//				if (typeof temp["labelText"] !== 'undefined') {
				if (name.substring(0, 12) === "customLabel_") {
					label = temp["labelText"];
				}



//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				$(canvasname).append('<div id="'+name+'" style="width:'+wide+'px;height:'+tall+'px;top:'+ypos+'px;left:'+xpos+'px;font-family:'+fontname+';font-size:'+fontsize+'px;position:'+position+';" fontfix = "'+fontfix+'">'+label+'</div>');
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



				if (typeof fgcolor != 'undefined') {
					var fgcolornumber = getFGColor(fgcolor);
					if (name === "HealthBG" || name === "TargetBGshade" || name === "HorizontalLine2" || name === "VerticalLineScoreboard" || name === "MainMenuBG" || name.substring(0, 11) === "coloredBox_" || typeof temp["fillcolor"] != 'undefined' || name === "TargetHealthBG" || name === "FreezePanelBGTitle") {
						$("#"+name).css("background-color","rgba("+fgcolornumber[0]+","+fgcolornumber[1]+","+fgcolornumber[2]+"," +(Math.round((Number(fgcolornumber[3])*1.0/255) * 100) / 100)+ ")");
					} else if (name === "teambutton0" || name === "teambutton1" || name === "teambutton2" || name === "teambutton3") {
						if (typeof temp["paintborder"] === 'undefined') {
							if (temp["paintborder"] === "0") {
								$("#"+name).css("background-color","rgba("+fgcolornumber[0]+","+fgcolornumber[1]+","+fgcolornumber[2]+"," +(Math.round((Number(fgcolornumber[3])*1.0/255) * 100) / 100)+ ")");
							} else {
								$("#"+name).css("color","rgba("+fgcolornumber[0]+","+fgcolornumber[1]+","+fgcolornumber[2]+"," +(Math.round((Number(fgcolornumber[3])*1.0/255) * 100) / 100)+ ")");
							}
						}
					} else {
						$("#"+name).css("color","rgba("+fgcolornumber[0]+","+fgcolornumber[1]+","+fgcolornumber[2]+"," +(Math.round((Number(fgcolornumber[3])*1.0/255) * 100) / 100)+ ")");
					}
				}

				$("#"+name).css("overflow", "hidden");
				$("#"+name).css("white-space", "nowrap");
				
				if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
					var fgtemp = temp["SubButton"]["armedFgColor_override"];
					fgtemp = getFGColor(fgtemp);
					$("#"+name).attr("hovercolor", "rgba("+fgtemp[0]+","+fgtemp[1]+","+fgtemp[2]+"," +(Math.round((Number(fgtemp[3])*1.0/255) * 100) / 100)+ ")");
				} else if (name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
					var fgtemp = temp["armedFgColor_override"];
					fgtemp = getFGColor(fgtemp);
					$("#"+name).attr("hovercolor", "rgba("+fgtemp[0]+","+fgtemp[1]+","+fgtemp[2]+"," +(Math.round((Number(fgtemp[3])*1.0/255) * 100) / 100)+ ")");
				} else if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
					var fgtemp = temp["image_armedcolor"];
					fgtemp = getFGColor(fgtemp);
					$("#"+name).attr("hovercolor", "rgba("+fgtemp[0]+","+fgtemp[1]+","+fgtemp[2]+"," +(Math.round((Number(fgtemp[3])*1.0/255) * 100) / 100)+ ")");
					$("#"+name+" p").css( "margin-top", "-"+String(Number(temp["tall"])/2)+"px" );
				}

				if (name === "xHair") {
					$("#xHair").text(temp["labelText"]);
					$("#element-xhair-type").val(temp["labelText"]);
					$("#xHair").css("line-height", tall+"px");
				}

				if (typeof temp["textAlignment"] != 'undefined') {
					if (temp["textAlignment"] === "center" || temp["textAlignment"] === "north" ) {
						$("#"+name).css("text-align", "center");
					} else if (temp["textAlignment"] === "east" || temp["textAlignment"] === "right" || temp["textAlignment"] === "north-east") {
						$("#"+name).css("text-align", "right");
					} else if (temp["textAlignment"] === "west" || temp["textAlignment"] === "left" || temp["textAlignment"] === "north-west") {
						$("#"+name).css("text-align", "left");
					} else {
						$("#"+name).css("text-align", "");
					}
				}

				if (visibleShadow === "1") {
					$("#"+name).addClass("shadow");
					$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
				}

				if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"][font] != 'undefined') {
					if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"][font]["1"]["outline"] != 'undefined') {
						if (data["jsonclientscheme"]["Scheme"]["Fonts"][font]["1"]["outline"] === "1") {
							$("#"+name).addClass("outline");
//							if ($("#"+name).hasClass("shadow")) {
							if (visibleShadow === "1") {
								$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
							} else {
								$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/ + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
							}			
							$('#element-outline').prop( "checked", true );
						} else {
							$("#"+name).removeClass("outline");
//							if ($("#"+name).hasClass("shadow")) {
							if (visibleShadow === "1") {
								$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							} else {
								$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/);
							}
							$('#element-outline').prop( "checked", false );
						}
					} else {
						data["jsonclientscheme"]["Scheme"]["Fonts"][font]["1"]["outline"] = "0";
						$("#"+name).removeClass("outline");
//						if ($("#"+name).hasClass("shadow")) {
						if (visibleShadow === "1") {
							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
						} else {
							$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/);
						}
						$('#element-outline').prop( "checked", false );
					}
				}

				if ( data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["outline"] == "1" ) {
					$('#element-xhair-outline').prop( "checked", true );
					$('#xHair').css("text-shadow", "-1px -1px 0 rgb(0, 0, 0), 1px -1px 0 rgb(0, 0, 0), -1px 1px 0 rgb(0, 0, 0), 1px 1px 0 rgb(0, 0, 0)");
				} else {
					$('#element-xhair-outline').prop( "checked", false );
					$('#xHair').css("text-shadow", "");
				}
				
				if (name === "ChargeMeter" || name === "ItemEffectMeter" || name === "ItemEffectMeterSpycicle") {
					$("#"+name).css("background-color", "#FFF");
					$("#"+name).css("background-clip", "content-box");
				}
				if (name === "playerpanels_kv") {
					$("#"+name).css("background-color", "rgba(96,131,154,0.89)");
					$("#"+name).css("z-index", "95");
				}

				if (name == "HealthBG") {
					$("#"+name).css("top", String(Number(ypos) - 17) + "px");
					$("#"+name).css("z-index", "95");
				} else if (name.substring(0, 11) === "coloredBox_" || name.substring(0, 12) === "customLabel_") {
					$("#"+name).css("top", String(Number(ypos) - 17) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name == "DamageAccountValue") {
					$("#"+name).css("top", String(Number(ypos) - 2) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name == "AmmoInReserve") {
					$("#"+name).css("top", String(Number(ypos) - 19) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name == "PlayerStatusHealthImageBG" || name === "PlayerStatusHealthImage" || name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatusMarkedForDeathImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom" || name === "PlayerStatus_MedicUberBulletResistImage") {
					$("#"+name).css("top", String(Number(ypos) - 17) + "px");
//					$("#"+name).css("z-index", "98");
//				} else if (name == "PlayerStatusHealthImage") {
//					$("#"+name).css("top", String(Number(ypos) - 17) + "px");
					$("#"+name).css("z-index", "99");
				} else if (name === "TeamMenuAuto" || name === "TeamMenuSpectate") {
					$("#"+name).css("top", String(Number(ypos) - 2) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name === "BluePlayerList" || name === "RedPlayerList") {
					$("#"+name).css("left", String(Number(xpos) + 5) + "px");
					$("#"+name).css("width", String(Number(wide) - 5) + "px");
					$("#"+name).css("z-index", "100");
					$("#"+name).css("overflow", "hidden");
				} else if (name === "AmmoInClip") {
					$("#"+name).css("top", String(Number(ypos) - 15) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name === "PlayerStatusHealthValue") {
					$("#"+name).css("top", String(Number(ypos) - 15) + "px");
					$("#"+name).css("z-index", "100");
				} else if (name === "NumPipesLabel") {
					$("#"+name).css("top", String(Number(ypos) + 2) + "px");
					$("#"+name).css("z-index", "100");
				} else {
//					$("#"+name).css("left", String(xpos) + "px");
//					$("#"+name).css("top", String(ypos) + "px");
					$("#"+name).css("z-index", "100");
				}
				
				if (name.substring(0, 11) === "coloredBox_" || typeof temp["fillcolor"] != 'undefined' || name === "TargetHealthBG") {
					$("#"+name).css("z-index", "95");
				}
				
				if (name === "BlueScoreBGFix" || name === "RedScoreBGFix") {
					$("#"+name).css("z-index", "96");
				}

				if (typeof temp["image"] != 'undefined') {
					if (typeof temp["teambg_3"] != 'undefined') {
						$("#"+name).css("background-color","rgba(96,131,154,0.89)");
					} else {
						if (temp["image"] === "../hud/color_panel_brown" || temp["image"] === "../hud/objectives_timepanel_suddendeath") $("#"+name).css("background-color","rgba(53,50,49,0.89)");
						else if ( temp["image"] === "../HUD/tournament_panel_red" || temp["image"] === "../hud/winpanel_red_bg_team" || temp["image"] === "../hud/color_panel_red" || temp["image"] === "../hud/score_panel_red_bg" || temp["image"] === "../hud/objectives_timepanel_red_bg" ) $("#"+name).css("background-color","rgba(180,67,68,0.89)");
						else if ( temp["image"] === "../HUD/tournament_panel_blu" || temp["image"] === "../hud/winpanel_blue_bg_team" || temp["image"] === "../hud/color_panel_blu" || temp["image"] === "../hud/score_panel_blue_bg" || temp["image"] === "../hud/objectives_timepanel_blue_bg" ) $("#"+name).css("background-color","rgba(96,131,154,0.89)");
					}
					$("#"+name).css("z-index", "95");
				}

				if (name === "classimage") {
					$("#"+name).css("z-index", "100");
				}

				if (typeof temp["CornerRadius"] != 'undefined') {
					$("#"+name).css("border-radius", temp["CornerRadius"] + "px");
				} else if (typeof temp["draw_corner_width"] != 'undefined') {
					$("#"+name).css("border-radius", temp["draw_corner_width"] + "px");
				}

				if (name === "HudDeathNotice") {
					$("#"+name).css("left", String(Number(xpos) + 330) + "px");
					$("#"+name).css("height", temp["LineHeight"] + "px");
					$("#"+name).css("width", "");
					$("#"+name).css("background-clip", "content-box");
					$("#"+name).css("display", "table");
					$("#noticeicon").css("height", String(Number($("#"+name).css("font-size").replace("px",""))/2) + "px");
					if (temp["RightJustify"] === "1") {
						$("#"+name).css("text-align", "right");
					} else if (temp["RightJustify"] === "0") {
						$("#"+name).css("text-align", "left");
					}

					var tempcolor = getFGColor(temp["TeamBlue"]);
					var tempcolor2 = "rgba("+tempcolor[0]+","+tempcolor[1]+","+tempcolor[2]+"," +(Math.round((Number(tempcolor[3])*1.0/255) * 100) / 100)+ ")";
					$("#noticeblue").css("color", tempcolor2);
					$('#element-color-teamblue').val(tempcolor2);
					$('#colorpicker-teamblue')["0"]["value"] = $('#element-color-teamblue').val();

					var tempcolor = getFGColor(temp["TeamRed"]);
					var tempcolor2 = "rgba("+tempcolor[0]+","+tempcolor[1]+","+tempcolor[2]+"," +(Math.round((Number(tempcolor[3])*1.0/255) * 100) / 100)+ ")";
					$("#noticered").css("color", tempcolor2);
					$('#element-color-teamred').val(tempcolor2);
					$('#colorpicker-teamred')["0"]["value"] = $('#element-color-teamred').val();

					var tempcolor = getFGColor(temp["BaseBackgroundColor"]);
					var tempcolor2 = "rgba("+tempcolor[0]+","+tempcolor[1]+","+tempcolor[2]+"," +(Math.round((Number(tempcolor[3])*1.0/255) * 100) / 100)+ ")";
					$('#element-color-basebg').val(tempcolor2);
					$('#colorpicker-basebg')["0"]["value"] = $('#element-color-basebg').val();

					var tempcolor = getFGColor(temp["LocalBackgroundColor"]);
					var tempcolor2 = "rgba("+tempcolor[0]+","+tempcolor[1]+","+tempcolor[2]+"," +(Math.round((Number(tempcolor[3])*1.0/255) * 100) / 100)+ ")";
					$('#element-color-localbg').val(tempcolor2);
					$('#colorpicker-localbg')["0"]["value"] = $('#element-color-localbg').val();
					$("#"+name).css("background-color", tempcolor2);
				}

				if (name === "DamageAccountValue") {
					var huddamagecolortemp = getFGColor(data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["NegativeColor"]);
					var huddamagecolor = "rgba("+huddamagecolortemp[0]+","+huddamagecolortemp[1]+","+huddamagecolortemp[2]+"," +(Math.round((Number(huddamagecolortemp[3])*1.0/255) * 100) / 100)+ ")";
					$('#element-color-damagecolor').val( huddamagecolor );
					$('#colorpicker-damagecolor')["0"]["value"] = $('#element-color-damagecolor').val();
					
					var huddamagehealingcolortemp = getFGColor(data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["PositiveColor"]);
					var huddamagehealingcolor = "rgba("+huddamagehealingcolortemp[0]+","+huddamagehealingcolortemp[1]+","+huddamagehealingcolortemp[2]+"," +(Math.round((Number(huddamagehealingcolortemp[3])*1.0/255) * 100) / 100)+ ")";
					$('#element-color-healingcolor').val( huddamagehealingcolor );
					$('#colorpicker-healingcolor')["0"]["value"] = $('#element-color-healingcolor').val();

					var huddamagetemp = data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["delta_item_font"];
					var huddamagefont = data["jsonclientscheme"]["Scheme"]["Fonts"][huddamagetemp]["1"]["name"];
					var huddamagesize = data["jsonclientscheme"]["Scheme"]["Fonts"][huddamagetemp]["1"]["tall"];

					var huddamageoutline = "0";
					if (typeof data["jsonclientscheme"]["Scheme"]["Fonts"][huddamagetemp]["1"]["outline"] != 'undefined') {
						huddamageoutline = data["jsonclientscheme"]["Scheme"]["Fonts"][huddamagetemp]["1"]["outline"];
					}

					$('#element-damage-fontname').val( huddamagefont );
					$('#element-damage-fontsize').val( huddamagesize );
					if (huddamageoutline === "1") {
						$('#element-damage-fontoutline').prop( "checked", true );
					} else {
						$('#element-damage-fontoutline').prop( "checked", false );
					}
					
					$("#"+name).attr("huddamagefont", huddamagefont);
					$("#"+name).attr("huddamagesize", huddamagesize);
					$("#"+name).attr("huddamageoutline", huddamageoutline);
				}

				if (name === "TargetNameLabel") {
					$("#TargetNameLabel").css("left", String($("#SpectatorGUIHealth").width() + $("#SpectatorGUIHealth").position().left) + "px");
				} else if (name === "TargetDataLabel") {
					$("#TargetDataLabel").css("left", String($("#SpectatorGUIHealth").width() + $("#SpectatorGUIHealth").position().left) + "px");
				}
				
/*				if (name === "playerpanels_kv") {
					for (i = 1; i < 6; i++) {
						$("#"+name).clone().attr("id", "playerpanels_kv_shadowcopy"+String(i)).appendTo(".hud-canvas-spectator");
						$("#playerpanels_kv_shadowcopy"+String(i)).css("left", String(Number($("#"+name).css("left").replace("px","")) + team1_player_delta_x*i) + "px");
						$("#playerpanels_kv_shadowcopy"+String(i)).css("top", String(Number($("#"+name).css("top").replace("px","")) + team1_player_delta_y*i) + "px");
					}
				}*/

				if (name === "teambutton0" || name === "teambutton1" || name === "teambutton2" || name === "teambutton3") {
					if ( typeof temp["fgcolor"] !== 'undefined' ) {
						$("#"+name).css("color",getFGColor(temp["fgcolor"]));
					} else {
						$("#"+name).css("color","#FFF");
					}
					$("#"+name).text((temp["labelText"]).replace("&",""));
				}

				//**********************************************************************//
				//			Update input fields according to selected element			//
				//**********************************************************************//
				$("#"+name)
				.draggable({
					grid: [ 5, 5 ],
					drag: function( event, ui ) {
						$('#element-xpos').val( Number($("#"+name).css("left").replace('px','')) );
						$('#element-ypos').val( Number($("#"+name).css("top").replace('px','')) );
/*						if (name === "playerpanels_kv") {
							$( "div[id^='playerpanels_kv_shadowcopy']" ).each(function() {
								$(this).css("left", Number($("#"+name).css("left").replace('px','')) + Number($(this).attr("id").replace("playerpanels_kv_shadowcopy",""))*team1_player_delta_x );
								$(this).css("top", Number($("#"+name).css("top").replace('px','')) + Number($(this).attr("id").replace("playerpanels_kv_shadowcopy",""))*team1_player_delta_y );
							});
						}*/
					},
					stop: function( event, ui ) {
						if (name === "TimePanelValue") {
							data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["xpos"] = String($("#"+name).css("left").replace('px',''));
							data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["ypos"] = String($("#"+name).css("top").replace('px',''));
							data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["xpos"] = String($("#"+name).css("left").replace('px',''));
							data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 20);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) - 40);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 20);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) - 20);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 20);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) + 40);
							data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 20);
						}

						if (name === "HudDeathNotice") {
							temp["xpos"] = String(Number($("#"+name).css("left").replace('px','')) - 330);
							temp["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "AmmoInReserve") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 19);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name === "HealthBG" || name.substring(0, 11) === "coloredBox_" || name.substring(0, 12) === "customLabel_") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name == "DamageAccountValue") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 2);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name === "PlayerStatusHealthImageBG" || name === "PlayerStatusHealthImage" || name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
//							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) + 4);
//							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17 + 4);
//						} else if (name === "PlayerStatusHealthImage") {
//							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17);
//							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name === "PlayerStatusMarkedForDeathImage") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["ypos"] = temp["ypos"];
						} else if (name === "PlayerStatus_MedicUberBulletResistImage") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 17);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["ypos"] = temp["ypos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["xpos"] = temp["xpos"];
							data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["ypos"] = temp["ypos"];
						} else if (name === "NumPipesLabel") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) - 2);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name === "TeamMenuAuto" || name === "TeamMenuSpectate") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 2);
							temp["xpos"] = $("#"+name).css("left").replace('px','');
						} else if (name === "BluePlayerList" || name === "RedPlayerList") {
							temp["ypos"] = $("#"+name).css("top").replace('px','');
							temp["xpos"] = String(Number($("#"+name).css("left").replace('px','')) - 5);
						} else if (name === "PlayerStatusHealthValue") {
							temp["xpos"] = $("#"+name).css("left").replace('px','');
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 15);
						} else {
							temp["xpos"] = $("#"+name).css("left").replace('px','');
							temp["ypos"] = $("#"+name).css("top").replace('px','');
						}

						var tempShadow;
						if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
						} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
						} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
						}
//						try { var visibleShadow = tempShadow["visible"]; }
//						catch (e) { }

						if (name === "TargetBGshade") {
							data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"] = String(Number(data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"]) + Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["tall"]) + Number($('#element-medicoffset').val()));
						}

						if (name === "WinningTeamLabel") {
							data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["xpos"] = temp["xpos"];
							data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["ypos"] = temp["ypos"];
						}
						
						if (name === "AmmoInClip") {
							temp["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 15);
							data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + 15);
//							if (visibleShadow === "1") {
//								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) + shadowSize);
//								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) + shadowSize + 15);
//							}
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) - 16 + 15);
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) - 5);
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["ypos"] = $("#"+name).css("top").replace('px','');
						}

						if (name === "ChargeMeter") {
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["ypos"] = $("#"+name).css("top").replace('px','');
							data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["ypos"] = $("#"+name).css("top").replace('px','');
							data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ypos"] = $("#"+name).css("top").replace('px','');
						}

						if (name === "ItemEffectMeterSpycicle") {
							data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["ypos"] = $("#"+name).css("top").replace('px','');
						}

						if (name === "blueframe") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "redframe") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "RandomFrame") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "SpectateFrame") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["ypos"] = $("#"+name).css("top").replace('px','');
						}

/*						if (name === "teambutton0") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "teambutton1") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "teambutton2") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "teambutton3") {
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["ypos"] = $("#"+name).css("top").replace('px','');
						}*/

						if (name === "NumPipesLabel") {
							data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) - 2);
//							if (data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["visible"] == "1") {
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) + shadowSize);
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) - 2 + shadowSize);
//							}
//							if (visibleShadow === "1") {
//								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number($("#"+name).css("left").replace('px','')) + shadowSize);
//								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number($("#"+name).css("top").replace('px','')) - 2 + shadowSize);
//							}
						}

						if (name === "ReinforcementsLabel") {
							data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["ypos"] = $("#"+name).css("top").replace('px','');
						}

						if (name === "QuitButton") {
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "CreateServerButton") {
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "TrainingButton") {
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "ServerBrowserButton") {
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["ypos"] = $("#"+name).css("top").replace('px','');
						} else if (name === "QuickplayButton") {
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["xpos"] = $("#"+name).css("left").replace('px','');
							data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["ypos"] = $("#"+name).css("top").replace('px','');
						}
						
//						if (visibleShadow === "1") {
//							tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
//							tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
//						}

						if ( typeof tempShadow !== 'undefined') {
							if ( typeof tempShadow["visible"] !== 'undefined' && temp["xpos"] < "9000" ) {
								tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
								tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
							}
						}

					}
				})
				.bind('mousedown mouseup', function(event) {
					if ( $(this).is('.ui-draggable-dragging') ) {
						return;
					}
					if (name === "PlayerStatusHealthImageBG" || name === "PlayerStatusHealthImage" || name === "HudDeathNotice" || name === "BluePlayerList" || name === "RedPlayerList" || name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton" || name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatusMarkedForDeathImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom" || name === "PlayerStatus_MedicUberBulletResistImage") {
						controlElement(name);
					} else {
						controlElement(event.target.id);
					}
				});
				if (name === "TargetBGshade" || name === "SpectatorGUIHealth" || name === "TargetNameLabel" || name === "TargetDataLabel") {
					$("#"+name).draggable({ containment: "parent", axis: "y" });
				}
				if (name === "TargetNameLabel" || name === "SpectatorGUIHealth" || name === "TargetDataLabel" || name === "TargetHealthBG") {
					$("#"+name).draggable({ containment: "#targetidwrapper" });
				}
			}
		}
	}


//**********************************************************//
//				 Detect click on specific element			//
//**********************************************************//
	function findSelectedName() {
		return $('.selected').attr("id");
	}

	$( "#colorpicker-normal" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$("#"+name).css("color",String(color));
			$('#element-color-normal').val( $("#"+name).css("color") );
			var temp = eval("data" + getObjPath(data,name,"",""));

			var colorarray;
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				colorarray = String(color).replace("rgba(","").replace(")","").split(",");
			} else { // rgb
				colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				colorarray[3] = "1";
			}

			if (name.substring(0, 11) === "coloredBox_") {
				temp["defaultBgColor_Override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				$("#"+name).css("background-color",String(color));
			} else if (name === "xHair") {
				data["jsonclientscheme"]["Scheme"]["Colors"]["xHairWhite"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else if (name === "PlayerStatusHealthValue") {
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDNormal"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
				temp["SubButton"]["defaultFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else if (name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
				temp["defaultFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
				temp["image_drawcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else if (name === "AmmoInClip") {
				var tempcolor = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInClip"] = tempcolor;
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeLabel"] = tempcolor;
				data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["fgcolor"] = tempcolor;
			} else if (name === "AmmoInReserve") {
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoInReserve"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				if (typeof temp["fgcolor"] != 'undefined') {
					temp["fgcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				}
				if (typeof temp["fillcolor"] != 'undefined') {
					$("#"+name).css("background-color",String(color));
					temp["fillcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				}
				if (typeof temp["TextColor"] != 'undefined') {
					temp["TextColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				}
				if (typeof temp["fgcolor_override"] != 'undefined') {
					temp["fgcolor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				}
			}

			if (name === "TimePanelValue") {
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["fgcolor"] = temp["fgcolor"];
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["fgcolor"] = temp["fgcolor"];
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["fgcolor"] = temp["fgcolor"];
			}

			if (name === "NumPipesLabel") {
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["fgcolor"] = temp["fgcolor"];
			}

			if (name === "ReinforcementsLabel") {
				data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["fgcolor_override"] = temp["fgcolor_override"];
			}

			if (name === "WinningTeamLabel") {
				data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["fgcolor"] = temp["fgcolor"];
			}
			
			if (name === "QuitButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["defaultFgColor_override"] = temp["defaultFgColor_override"];
			} else if (name === "CreateServerButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["defaultFgColor_override"] = temp["defaultFgColor_override"];
			} else if (name === "TrainingButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["defaultFgColor_override"] = temp["defaultFgColor_override"];
			} else if (name === "ServerBrowserButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["defaultFgColor_override"] = temp["defaultFgColor_override"];
			} else if (name === "QuickplayButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["defaultFgColor_override"] = temp["defaultFgColor_override"];
			}

		}
	});
	$( "#colorpicker-high" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-high').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverheal"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else { // rgb
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverheal"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-low" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-low').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLow"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLow"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-high-bar" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-high-bar').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverhealBar"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDOverhealBar"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-low-bar" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-low-bar').val( String(color) );
			$("#HealthBG").css("background-color", String(color));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLowBar"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDLowBar"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-high-targetbar" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-high-targetbar').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarHigh"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarHigh"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-low-targetbar" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-low-targetbar').val( String(color) );
			$("#TargetHealthBG").css("background-color", String(color));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarLow"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-normal-targetbar" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-normal-targetbar').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarNormal"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDSmallBarNormal"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-kothblue" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-kothblue').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["fgcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["fgcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-kothred" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-kothred').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["fgcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["fgcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-uber1" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-uber1').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge1"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge1"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-uber2" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-uber2').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge2"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDMedicCharge2"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-hover" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			var temp = eval("data" + getObjPath(data,name,"",""));
			$('#element-color-hover').val( String(color) );
			$("#"+name).attr("hovercolor", String(color));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
					temp["SubButton"]["armedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
					temp["SubButton"]["depressedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				} else if (name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
					temp["armedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
					temp["depressedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				} else if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
					temp["image_armedcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				}
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				if (name === "CreateServerButton" || name === "TrainingButton" || name === "QuickplayButton" || name === "ServerBrowserButton" || name === "CharacterSetupButton" || name === "GeneralStoreButton" || name === "ReplayBrowserButton" || name === "SteamWorkshopButton") {
					temp["SubButton"]["armedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
					temp["SubButton"]["depressedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
				} else if (name === "TF2SettingsButton" || name === "SettingsButton" || name === "QuitButton") {
					temp["armedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
					temp["depressedFgColor_override"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
				} else if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
					temp["image_armedcolor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
				}
			}
			
			if (name === "QuitButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["armedFgColor_override"] = temp["armedFgColor_override"];
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["depressedFgColor_override"] = temp["depressedFgColor_override"];
			} else if (name === "CreateServerButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["armedFgColor_override"] = temp["armedFgColor_override"];
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["depressedFgColor_override"] = temp["depressedFgColor_override"];
			} else if (name === "TrainingButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["armedFgColor_override"] = temp["armedFgColor_override"];
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["depressedFgColor_override"] = temp["depressedFgColor_override"];
			} else if (name === "ServerBrowserButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["armedFgColor_override"] = temp["armedFgColor_override"];
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["depressedFgColor_override"] = temp["depressedFgColor_override"];
			} else if (name === "QuickplayButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["armedFgColor_override"] = temp["armedFgColor_override"];
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["depressedFgColor_override"] = temp["depressedFgColor_override"];
			}

		}
	});


	$( "#colorpicker-teamred" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$("#noticered").css("color",String(color));
			$('#element-color-teamred').val( String(color) );
			var temp = eval("data" + getObjPath(data,name,"",""));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				temp["TeamRed"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				temp["TeamRed"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-teamblue" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$("#noticeblue").css("color",String(color));
			$('#element-color-teamblue').val( String(color) );
			var temp = eval("data" + getObjPath(data,name,"",""));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				temp["TeamBlue"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				temp["TeamBlue"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-basebg" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$('#element-color-basebg').val( String(color) );
			var temp = eval("data" + getObjPath(data,name,"",""));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				temp["BaseBackgroundColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				temp["BaseBackgroundColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-localbg" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$('#element-color-localbg').val( String(color) );
			$("#"+name).css("background-color", String(color));
			var temp = eval("data" + getObjPath(data,name,"",""));
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				temp["LocalBackgroundColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				temp["LocalBackgroundColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-damagecolor" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$('#element-color-damagecolor').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["NegativeColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["NegativeColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-healingcolor" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			var name = findSelectedName();
			$('#element-color-healingcolor').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["PositiveColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["PositiveColor"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-hit" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-hit').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["xHairHit"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["xHairHit"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-ammo-low-clip" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-ammo-low-clip').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowClip"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-ammo-low-reserve" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-color-ammo-low-reserve').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowReserve"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDAmmoLowReserve"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$( "#colorpicker-chargefg" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-chargefg').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterFG"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterFG"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});
	$( "#colorpicker-chargebg" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#element-chargebg').val( String(color) );
			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterBG"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else {
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDChargeMeterBG"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
		}
	});

	$('#element-xpos').change(function() {
		var name = findSelectedName();
		$("#"+name).css("left", $(this).val()+"px");

		var temp;
		if (name === "SpectatorGUIHealthSpy") {
			var tempname = "SpectatorGUIHealth";
			temp = eval('data["jsondisguisestatuspanel"]' + getObjPath(data["jsondisguisestatuspanel"],tempname,"",""));
		} else if (name === "ItemEffectMeterSpycicle") {
			var tempname = "ItemEffectMeter";
			temp = eval('data["jsonhuditemeffectmeterscout"]' + getObjPath(data["jsonhuditemeffectmeterscout"],tempname,"",""));
		} else {
			temp = eval("data" + getObjPath(data,name,"",""));
		}

		if (name === "HudDeathNotice") {
			temp["xpos"] = String(Number($(this).val()) - 330);
		} else {
			temp["xpos"] = $(this).val();
		}

		if (name === "blueframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["xpos"] = $(this).val();
		} else if (name === "redframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["xpos"] = $(this).val();
		} else if (name === "RandomFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["xpos"] = $(this).val();
		} else if (name === "SpectateFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["xpos"] = $(this).val();
		}

/*		if (name === "teambutton0") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["xpos"] = temp["xpos"];
		} else if (name === "teambutton1") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["xpos"] = temp["xpos"];
		} else if (name === "teambutton2") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["xpos"] = temp["xpos"];
		} else if (name === "teambutton3") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["xpos"] = temp["xpos"];
		}*/

		if (name === "WinningTeamLabel") {
			data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["xpos"] = $(this).val();
		}
		
		if (name === "ChargeMeter") {
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["xpos"] = $(this).val();
			data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["xpos"] = $(this).val();
			data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["xpos"] = $(this).val();
		} else if (name === "ItemEffectMeterSpycicle") {
			data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["xpos"] = $(this).val();
		} else if (name === "NumPipesLabel") {
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["xpos"] = $(this).val();
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number($(this).val()) + shadowSize);
		} else if (name === "ReinforcementsLabel") {
			data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["xpos"] = $(this).val();
		} else if (name === "PlayerStatusMarkedForDeathImage") {
			temp["xpos"] = $(this).val();
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["xpos"] = temp["xpos"];
		} else if (name === "PlayerStatus_MedicUberBulletResistImage") {
			temp["xpos"] = $(this).val();
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["xpos"] = temp["xpos"];
//		} else if (name === "PlayerStatusHealthImageBG") {
//			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["xpos"] = String(Number($(this).val()) + 4);
		} else if (name == "TargetDataLabel") {
//			temp["xpos"] = String(Number($(this).val()) - 6);
//		} else if (name == "SpectatorGUIHealth") {
//			temp["xpos"] = String(Number($(this).val()) - 24);
//		} else if (name === "SpectatorGUIHealthSpy") {
//			temp["xpos"] = String(Number($(this).val()) - 22);
		} else if (name == "BluePlayerList" || name === "RedPlayerList") {
			temp["xpos"] = String(Number($(this).val()) - 5);
		}

		if (name === "TimePanelValue") {
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["xpos"] = $(this).val();
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["xpos"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["xpos"] = String(Number($(this).val()) - 40);
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["xpos"] = String(Number($(this).val()) - 20);
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["xpos"] = String(Number($(this).val()) + 40);
		}

		if (name === "AmmoInClip") {
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["xpos"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["xpos"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["xpos"] = String(Number($(this).val()) - 5);
		}

		if (name === "QuitButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["xpos"] = temp["xpos"];
		} else if (name === "CreateServerButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["xpos"] = temp["xpos"];
		} else if (name === "TrainingButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["xpos"] = temp["xpos"];
		} else if (name === "ServerBrowserButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["xpos"] = temp["xpos"];
		} else if (name === "QuickplayButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["xpos"] = temp["xpos"];
		}

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
			if (tempShadow["visible"] === "1") {
				tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
			}
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
			if (tempShadow["visible"] === "1") {
				tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
			}
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
			if (tempShadow["visible"] === "1") {
				tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
			}
		}

		if (name === "AmmoInClip" && tempShadow["visible"] === "1") {
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number($(this).val()) + shadowSize);
		}

	});

	$('#element-ypos').change(function() {
		var name = findSelectedName();
		$("#"+name).css("top", $(this).val()+"px");

		var temp;
		if (name === "SpectatorGUIHealthSpy") {
			var tempname = "SpectatorGUIHealth";
			temp = eval('data["jsondisguisestatuspanel"]' + getObjPath(data["jsondisguisestatuspanel"],tempname,"",""));
		} else if (name === "ItemEffectMeterSpycicle") {
			var tempname = "ItemEffectMeter";
			temp = eval('data["jsonhuditemeffectmeterscout"]' + getObjPath(data["jsonhuditemeffectmeterscout"],tempname,"",""));
		} else {
			temp = eval("data" + getObjPath(data,name,"",""));
		}

		if (name === "WinningTeamLabel") {
			data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["ypos"] = $(this).val();
		}

		if (name === "ChargeMeter") {
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["ypos"] = $(this).val();
			data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["ypos"] = $(this).val();
			data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["ypos"] = $(this).val();
		}
		if (name === "ItemEffectMeterSpycicle") {
			data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["ypos"] = $(this).val();
		}

		if (name === "blueframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["ypos"] = $(this).val();
		} else if (name === "redframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["ypos"] = $(this).val();
		} else if (name === "RandomFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["ypos"] = $(this).val();
		} else if (name === "SpectateFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["ypos"] = $(this).val();
		}

/*		if (name === "teambutton0") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["ypos"] = temp["ypos"];
		} else if (name === "teambutton1") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["ypos"] = temp["ypos"];
		} else if (name === "teambutton2") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["ypos"] = temp["ypos"];
		} else if (name === "teambutton3") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["ypos"] = temp["ypos"];
		}*/

		if (name === "TimePanelValue") {
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["ypos"] = $(this).val();
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["ypos"] = String(Number($(this).val()) + 20);
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["ypos"] = String(Number($(this).val()) + 20);
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["ypos"] = String(Number($(this).val()) + 20);
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["ypos"] = String(Number($(this).val()) + 20);
		}

		if (name === "AmmoInReserve") {
			temp["ypos"] = String(Number($(this).val()) + 19);
		} else if (name === "HealthBG" || name.substring(0, 11) === "coloredBox_" || name.substring(0, 12) === "customLabel_") {
			temp["ypos"] = String(Number($(this).val()) + 17);
		} else if (name === "DamageAccountValue") {
			temp["ypos"] = String(Number($(this).val()) + 2);
		} else if (name === "PlayerStatusHealthImageBG" || name === "PlayerStatusHealthImage" || name === "PlayerStatusBleedImage" || name === "PlayerStatusMilkImage" || name === "PlayerStatus_Parachute" || name === "PlayerStatus_WheelOfDoom") {
			temp["ypos"] = String(Number($(this).val()) + 17);
//			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["ypos"] = String(Number($(this).val()) + 17 + 4);
//		} else if (name === "PlayerStatusHealthImage") {
//			temp["ypos"] = String(Number($(this).val()) + 17);
		} else if (name === "PlayerStatusMarkedForDeathImage") {
			temp["ypos"] = String(Number($(this).val()) + 17);
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["ypos"] = temp["ypos"];
		} else if (name === "PlayerStatus_MedicUberBulletResistImage") {
			temp["ypos"] = String(Number($(this).val()) + 17);
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["ypos"] = temp["ypos"];
		} else if (name === "NumPipesLabel") {
			temp["ypos"] = String(Number($(this).val()) - 2);
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["ypos"] = String(Number($(this).val()) - 2);
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number($(this).val()) - 2 + shadowSize);
		} else if (name === "ReinforcementsLabel") {
			data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["ypos"] = $(this).val();
		} else if (name === "TeamMenuAuto" || name === "TeamMenuSpectate") {
			temp["ypos"] = String(Number($(this).val()) + 2);
		} else if (name === "PlayerStatusHealthValue") {
			temp["ypos"] = String(Number($(this).val()) + 15);
		} else {
			temp["ypos"] = $(this).val();
		}

		if (name === "TargetBGshade") {
			data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"] = String( Number($(this).val()) + Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["tall"]) + Number($('#element-medicoffset').val()));
		}

		if (name === "AmmoInClip") {
			temp["ypos"] = String(Number($(this).val()) + 15);
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["ypos"] = String(Number($(this).val()) + 15);
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["ypos"] = String(Number($(this).val()) + shadowSize + 15);
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["ypos"] = String(Number($(this).val()) - 16 + 15);
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["ypos"] = $(this).val();
		}

		if (name === "QuitButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["ypos"] = temp["ypos"];
		} else if (name === "CreateServerButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["ypos"] = temp["ypos"];
		} else if (name === "TrainingButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["ypos"] = temp["ypos"];
		} else if (name === "ServerBrowserButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["ypos"] = temp["ypos"];
		} else if (name === "QuickplayButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["ypos"] = temp["ypos"];
		}

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
			tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
			tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
			tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
		}
	});

	$('#element-wide').change(function() {
		var name = findSelectedName();
		$("#"+name).css("width", $(this).val());

		var temp;
		if (name === "SpectatorGUIHealthSpy") {
			var tempname = "SpectatorGUIHealth";
			temp = eval('data["jsondisguisestatuspanel"]' + getObjPath(data["jsondisguisestatuspanel"],tempname,"",""));
		} else if (name === "ItemEffectMeterSpycicle") {
			var tempname = "ItemEffectMeter";
			temp = eval('data["jsonhuditemeffectmeterscout"]' + getObjPath(data["jsonhuditemeffectmeterscout"],tempname,"",""));
		} else {
			temp = eval("data" + getObjPath(data,name,"",""));
		}

		temp["wide"] = $(this).val();

		if (name === "WinningTeamLabel") {
			data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["wide"] = $(this).val();
		}

		if (name === "ChargeMeter") {
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["wide"] = $(this).val();
			data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["wide"] = $(this).val();
			data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["wide"] = $(this).val();
		}
		if (name === "ItemEffectMeterSpycicle") {
			data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["wide"] = $(this).val();
		}

		if (name === "NumPipesLabel") {
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["wide"] = $(this).val();
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["wide"] = $(this).val();
		}

		if (name === "ReinforcementsLabel") {
			data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["wide"] = $(this).val();
		}

		if (name === "TimePanelValue") {
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["wide"] = $(this).val();
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["wide"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["wide"] = $(this).val();
		}

		if (name === "AmmoInClip") {
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["wide"] = $(this).val();
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["wide"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["wide"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["wide"] = $(this).val();
		}
	
		if (name === "blueframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["wide"] = $(this).val();
		} else if (name === "redframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["wide"] = $(this).val();
		} else if (name === "RandomFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["wide"] = $(this).val();
		} else if (name === "SpectateFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["wide"] = $(this).val();
		}

/*		if (name === "teambutton0") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["wide"] = temp["wide"];
		} else if (name === "teambutton1") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["wide"] = temp["wide"];
		} else if (name === "teambutton2") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["wide"] = temp["wide"];
		} else if (name === "teambutton3") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["wide"] = temp["wide"];
		}*/

		if (name == "BluePlayerList" || name === "RedPlayerList") {
			temp["wide"] = String(Number($(this).val()) + 5);
		}

//		if (name === "PlayerStatusHealthImageBG") {
//			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["wide"] = String(Number($(this).val()) - 8);
//		}

		if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
			temp["SubImage"]["wide"] = $(this).val();
			temp["SubImage"]["tall"] = $(this).val();
			temp["tall"] = $(this).val();
			$('#element-tall').val($(this).val());
			$("#"+name).css("height", String($(this).val())+"px");
			$("#"+name).css("font-size", String($(this).val())+"px");
			$("#"+name+" p").css( "margin-top", "-"+String(Number(temp["tall"])/2)+"px" );
		}

		if (name === "QuitButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["wide"] = temp["wide"];
		} else if (name === "CreateServerButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["wide"] = temp["wide"];
		} else if (name === "TrainingButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["wide"] = temp["wide"];
		} else if (name === "ServerBrowserButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["wide"] = temp["wide"];
		} else if (name === "QuickplayButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["wide"] = temp["wide"];
		}

		if (name === "TargetNameLabel") {
			temp["wide"] = "640";
		}
		if (name === "TargetDataLabel") {
			temp["wide"] = "280";
		}
		if (name === "SpectatorGUIHealth") {
			$("#TargetNameLabel").css("left", String($("#SpectatorGUIHealth").width() + $("#SpectatorGUIHealth").position().left) + "px");
			$("#TargetDataLabel").css("left", String($("#SpectatorGUIHealth").width() + $("#SpectatorGUIHealth").position().left) + "px");
		}
		if (name === "PlayerStatusMarkedForDeathImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["wide"] = temp["wide"];
		}
		if (name === "PlayerStatus_MedicUberBulletResistImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["wide"] = temp["wide"];
		}

/*		if (name === "playerpanels_kv") {
			$( "div[id^='playerpanels_kv_shadowcopy']" ).each(function() {
				console.log($(this).attr("id"));
				$(this).css("width", temp["wide"]+"px");
			});
		}*/

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
			tempShadow["wide"] = temp["wide"];
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
			tempShadow["wide"] = temp["wide"];
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
			tempShadow["wide"] = temp["wide"];
		}

	});

	$('#element-tall').change(function() {
		var name = findSelectedName();
		$("#"+name).css("height", $(this).val());

		var temp;
		if (name === "SpectatorGUIHealthSpy") {
			var tempname = "SpectatorGUIHealth";
			temp = eval('data["jsondisguisestatuspanel"]' + getObjPath(data["jsondisguisestatuspanel"],tempname,"",""));
		} else if (name === "ItemEffectMeterSpycicle") {
			var tempname = "ItemEffectMeter";
			temp = eval('data["jsonhuditemeffectmeterscout"]' + getObjPath(data["jsonhuditemeffectmeterscout"],tempname,"",""));
		} else {
			temp = eval("data" + getObjPath(data,name,"",""));
		}

		if (name === "HudDeathNotice") {
			temp["LineHeight"] = $(this).val();
		} else {
			temp["tall"] = $(this).val();
		}

		if (name === "TimePanelValue") {
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["tall"] = $(this).val();
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["tall"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["tall"] = $(this).val();
		}

		if (name === "AmmoInClip") {
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["tall"] = $(this).val();
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["tall"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["tall"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["tall"] = $(this).val();
		}

		if (name === "WinningTeamLabel") {
			data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["tall"] = $(this).val();
		}

		if (name === "ChargeMeter") {
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["tall"] = $(this).val();
			data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["tall"] = $(this).val();
			data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["tall"] = $(this).val();
		}
		if (name === "ItemEffectMeterSpycicle") {
			data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["tall"] = $(this).val();
		}

		if (name === "NumPipesLabel") {
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["tall"] = $(this).val();
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["tall"] = $(this).val();
		}

		if (name === "ReinforcementsLabel") {
			data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["tall"] = $(this).val();
		}

//		if (name === "PlayerStatusHealthImageBG") {
//			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["tall"] = String(Number($(this).val()) - 8);
//		}

		if (name === "blueframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton0"]["tall"] = $(this).val();
		} else if (name === "redframe") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton1"]["tall"] = $(this).val();
		} else if (name === "RandomFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton2"]["tall"] = $(this).val();
		} else if (name === "SpectateFrame") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["teambutton3"]["tall"] = $(this).val();
		}

/*		if (name === "teambutton0") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["blueframe"]["tall"] = temp["tall"];
		} else if (name === "teambutton1") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["redframe"]["tall"] = temp["tall"];
		} else if (name === "teambutton2") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RandomFrame"]["tall"] = temp["tall"];
		} else if (name === "teambutton3") {
			data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateFrame"]["tall"] = temp["tall"];
		}*/
		
		if (name === "NewUserForumsButton" || name === "AchievementsButton" || name === "CommentaryButton" || name === "CoachPlayersButton" || name === "ReportBugButton") {
			temp["SubImage"]["tall"] = $(this).val();
			temp["SubImage"]["wide"] = $(this).val();
			temp["wide"] = $(this).val();
			$('#element-wide').val($(this).val());
			$("#"+name).css("width", String($(this).val())+"px");
			$("#"+name).css("font-size", String($(this).val())+"px");
			$("#"+name+" p").css( "margin-top", "-"+String(Number(temp["tall"])/2)+"px" );
		}

		if (name === "QuitButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["tall"] = temp["tall"];
		} else if (name === "CreateServerButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["tall"] = temp["tall"];
		} else if (name === "TrainingButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["tall"] = temp["tall"];
		} else if (name === "ServerBrowserButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["tall"] = temp["tall"];
		} else if (name === "QuickplayButton") {
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["tall"] = temp["tall"];
		}

		if (name === "PlayerStatusMarkedForDeathImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["tall"] = temp["tall"];
		}

		if (name === "PlayerStatus_MedicUberBulletResistImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["tall"] = temp["tall"];
		}

/*		if (name === "playerpanels_kv") {
			$( "div[id^='playerpanels_kv_shadowcopy']" ).each(function() {
				$(this).css("height", temp["tall"]+"px");
			});
		}*/

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
			tempShadow["tall"] = temp["tall"];
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
			tempShadow["tall"] = temp["tall"];
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
			tempShadow["tall"] = temp["tall"];
		}

	});


	$('#element-fontname').change(function() {
		var name = findSelectedName();
		$("#"+name).css("font-family", $(this).val().replace(/\'/g,"").replace(/\"/g,"")); // fix for chrome
		if (name === "SpectatorGUIHealth" || name === "SpectatorGUIHealthSpy") {
			$("#SpectatorGUIHealth").css("font-family", $(this).val());
			$("#SpectatorGUIHealthSpy").css("font-family", $(this).val());
		}
	});
	$('#element-fontsize').change(function() {
		var name = findSelectedName();
		var fontname = $("#"+name).css("font-family");
		var fontsize = $(this).val();
		if (Number(fontsize) > 72) {
			fontsize = 72;
			$(this).val(fontsize);
		} else if (Number(fontsize) < 8) {
			fontsize = 8;
			$(this).val(fontsize);
		}

		// on screen
		if (fontname === "Crosshairs") {
			fontsize = String(Number(fontsize) + 5);
		} else if (fontname === "TF2 Build") {
			fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 10)));
		} else if (fontname === "PF Tempesta Seven") {
			fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 2.5)));
		} else if (fontname === "Counter-Strike") {
			fontsize = String(Number(fontsize) + 7);
		} else {
			fontsize = String(Math.round(Number(fontsize) - (Number(fontsize) / 5)));
		}

		$("#"+name).css("font-size", fontsize+"px");
		if (name === "SpectatorGUIHealth" || name === "SpectatorGUIHealthSpy") {
			$("#SpectatorGUIHealth").css("font-size", fontsize+"px");
			$("#SpectatorGUIHealthSpy").css("font-size", fontsize+"px");
		}
		if (name === "HudDeathNotice") {
			$("#noticeicon").css("height", (fontsize/2)+"px");
		}
	});

	$('#element-damage-fontname').change(function() {
		var name = findSelectedName();
		$("#"+name).attr("huddamagefont", $(this).val());
	});
	$('#element-damage-fontsize').change(function() {
		var name = findSelectedName();
		var fontsize = $(this).val();
		if (Number(fontsize) > 72) {
			fontsize = 72;
			$(this).val(fontsize);
		} else if (Number(fontsize) < 8) {
			fontsize = 8;
			$(this).val(fontsize);
		}
		$("#"+name).attr("huddamagesize", $(this).val());
	});
	$("#element-damage-fontoutline").click( function(){
		var name = findSelectedName();
		if ( $(this).is(':checked') ) { // add outline
			$("#"+name).attr("huddamageoutline", "1");
		} else { // remove outline
			$("#"+name).attr("huddamageoutline", "0");
		}
	});

	$('#element-medicoffset').change(function() {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["CSecondaryTargetID"]["ypos"] = String(Number(data["jsonhudlayout"]["Resource/HudLayout.res"]["CMainTargetID"]["ypos"]) + Number(data["jsontargetid"]["Resource/UI/TargetID.res"]["TargetBGshade"]["tall"]) + Number($(this).val()));
	});

	$('#element-xhair-size').change(function() {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["tall"] = String( $(this).val() );
		$("#xHair").css("font-size", $(this).val()+"px");
	});
	$('#element-xhair-type').change(function() {
		data["jsonhudlayout"]["Resource/HudLayout.res"]["xHair"]["labelText"] = String( $(this).val() );
		$("#xHair").text($(this).val());
	});


	$("#element-shadow").click( function(){
		var name = findSelectedName();
		var temp = eval("data" + getObjPath(data,name,"",""));

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
		}

		if ( $(this).is(':checked') ) {
			$("#"+name).addClass("shadow");
			if ($("#"+name).hasClass("outline")) {
				$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
			} else {
				$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
			}
			tempShadow["visible"] = "1";
			tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
			if (name === "AmmoInClip") {
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["visible"] = "1";
			}
			if (name === "NumPipesLabel") {
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["visible"] = "1";
			}
			if (name === "ReinforcementsLabel") {
				data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["visible"] = "1";
			}
		} else {
			$("#"+name).removeClass("shadow");
			if ($("#"+name).hasClass("outline")) {
				$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/ + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
			} else {
				$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/);
			}
			tempShadow["visible"] = "0";
			tempShadow["xpos"] = "9001";
			if (name === "AmmoInClip") {
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = "9001";
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["visible"] = "0";
			}
			if (name === "NumPipesLabel") {
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = "9001";
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["visible"] = "0";
			}
			if (name === "ReinforcementsLabel") {
				data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["visible"] = "0";
			}
		}
	});
	$("#element-outline").click( function(){
		var name = findSelectedName();
		var temp = eval("data" + getObjPath(data,name,"",""));
		if ( $(this).is(':checked') ) { // add outline
			$("#"+name).addClass("outline");
			if ($("#"+name).hasClass("shadow")) {
				$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
			} else {
				$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/ + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
			}
		} else { // remove outline
			$("#"+name).removeClass("outline");
			if ($("#"+name).hasClass("shadow")) {
				$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
			} else {
				$("#"+name).css("text-shadow", "0px 0px 0px " + "rgba(0, 0, 0, 0.0)"/*shadowColor*/);
			}
		}
	});
	$("#element-buffed").click( function(){
		var temp = eval("data" + getObjPath(data,"PlayerStatusHealthImageBG","",""));
		if ( $(this).is(':checked') ) {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["xpos"] = temp["xpos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["ypos"] = temp["ypos"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["wide"] = temp["wide"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["tall"] = temp["tall"];
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["visible"] = "1";
		} else {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthBonusImage"]["visible"] = "0";
		}
	});
	$("#element-xhair-outline").click( function(){
		if ( $(this).is(':checked') ) {
			data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["outline"] = "1";
			$('#xHair').css("text-shadow", "-1px -1px 0 rgb(0, 0, 0), 1px -1px 0 rgb(0, 0, 0), -1px 1px 0 rgb(0, 0, 0), 1px 1px 0 rgb(0, 0, 0)");
		} else {
			data["jsonclientscheme"]["Scheme"]["Fonts"]["xHair"]["1"]["outline"] = "0";
			$('#xHair').css("text-shadow", "");
		}
	});

	$('#element-cornerradius').change(function() {
		var name = findSelectedName();
		var temp = eval("data" + getObjPath(data,name,"",""));
		
		$("#"+name).css("border-radius", $(this).val() + "px");
		
		if (typeof temp["CornerRadius"] != 'undefined') {
			temp["CornerRadius"] = $(this).val();
		} else if (typeof temp["draw_corner_width"] != 'undefined') {
			temp["draw_corner_width"] = $(this).val();
			temp["draw_corner_height"] = $(this).val();
		}
	});

	$('#element-alignment').change(function() {
		var name = findSelectedName();
		var temp = eval("data" + getObjPath(data,name,"",""));

		temp["textAlignment"] = $(this).val();

		if (temp["textAlignment"] === "north" ) {
			$("#"+name).css("text-align", "center");
		} else if (temp["textAlignment"] === "north-east") {
			$("#"+name).css("text-align", "right");
		} else if (temp["textAlignment"] === "north-west") {
			$("#"+name).css("text-align", "left");
		}

		if (name === "WinningTeamLabel") {
			data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["textAlignment"] = $(this).val();
		}

		if (name === "TimePanelValue") {
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["textAlignment"] = $(this).val();
			data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["textAlignment"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["textAlignment"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["textAlignment"] = $(this).val();
			data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["textAlignment"] = $(this).val();
		} else if (name === "AmmoInClip") {
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["textAlignment"] = $(this).val();
			data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["textAlignment"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["textAlignment"] = $(this).val();
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["textAlignment"] = $(this).val();
		} else if (name === "ChargeMeter") {
			data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["textAlignment"] = $(this).val();
			data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["textAlignment"] = $(this).val();
			data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["textAlignment"] = $(this).val();
		} else if (name === "ItemEffectMeterSpycicle") {
			data["jsonhuditemeffectmeterspyknife"]["Resource/UI/HudItemEffectMeter_SpyKnife.res"]["ItemEffectMeter"]["textAlignment"] = $(this).val();
		} else if (name === "NumPipesLabel") {
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["textAlignment"] = $(this).val();
			data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["textAlignment"] = $(this).val();
		} else if (name === "ReinforcementsLabel") {
			data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["textAlignment"] = $(this).val();
		}

		var tempShadow;
		if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
			tempShadow["textAlignment"] = $(this).val();
		} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
			tempShadow["textAlignment"] = $(this).val();
		} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
			tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
			tempShadow["textAlignment"] = $(this).val();
		}

	});

	$('#element-killjustify').change(function() {
		var name = findSelectedName();
		var temp = eval("data" + getObjPath(data,name,"",""));
		$("#"+name).css("text-align", "left");
		temp["RightJustify"] = $(this).val();
		if ($(this).val() === "1") {
			$("#"+name).css("text-align", "right");
		} else if ($(this).val() === "0") {
			$("#"+name).css("text-align", "left");
		}
	});

	$('#shadowoffset').change(function() {
		shadowSize = Number($(this).val());

		function fixShadow(name) {
//			console.log(name);
//			if ( name !== "targetidwrapper" && name.substring(0, 26) !== "playerpanels_kv_shadowcopy" ) {
			if ( name !== "targetidwrapper" ) {
				if ( typeof getObjPath(data,name,"","") !== 'undefined' ) {
					var temp = eval("data" + getObjPath(data,name,"",""));
					var tempShadow;
					if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
						tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
						if (tempShadow["visible"] === "1") {
							tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
							tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							if (name === "AmmoInClip") {
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							} else if (name === "NumPipesLabel") {
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							}
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px" + shadowColor);
							if (typeof temp["font"] != 'undefined') {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
						}
					} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
						tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
						if (tempShadow["visible"] === "1") {
							tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
							tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							if (name === "AmmoInClip") {
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							} else if (name === "NumPipesLabel") {
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							}
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							if (typeof temp["font"] != 'undefined') {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
						}
					} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
						tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
						if (tempShadow["visible"] === "1") {
							tempShadow["xpos"] = String(Number(temp["xpos"]) + shadowSize);
							tempShadow["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							if (name === "AmmoInClip") {
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							} else if (name === "NumPipesLabel") {
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["xpos"] = String(Number(temp["xpos"]) + shadowSize);
								data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["ypos"] = String(Number(temp["ypos"]) + shadowSize);
							}
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							if (typeof temp["font"] != 'undefined') {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
						}
					}
				}
			}
		}

		$(".hud-canvas, .hud-canvas-classmenu, .hud-canvas-teammenu, .hud-canvas-winpanel, .hud-canvas-scoreboard, .hud-canvas-freezecam, .hud-canvas-mainmenu, .hud-canvas-tournamentsetup, .hud-canvas-spectator, .hud-canvas-misclabels").children().each(function() {
			fixShadow($(this).attr("id"));
		});
		$("#targetidwrapper").children().each(function() {
			fixShadow($(this).attr("id"));
		});

	});

	$( "#colorpicker-shadowOverallColor" ).spectrum({
		showInput: true,
		showAlpha: true,
		preferredFormat: "rgb",
		change: function(color) {
			$('#shadowOverallColor').val( String(color) );
			shadowColor = String(color); // RGB
			var shadowColorSource;


			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
			} else { // rgb
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				data["jsonclientscheme"]["Scheme"]["Colors"]["QHUDShadow"] = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
			}
			$(".hud-canvas, .hud-canvas-classmenu, .hud-canvas-teammenu, .hud-canvas-winpanel, .hud-canvas-scoreboard, .hud-canvas-freezecam, .hud-canvas-mainmenu, .hud-canvas-tournamentsetup, .hud-canvas-spectator, .hud-canvas-misclabels").children().each(function() {
				fixColor($(this).attr("id"));
			});
			$("#targetidwrapper").children().each(function() {
				fixColor($(this).attr("id"));
			});
/*			function fixColor(name) {
				if ($("#"+name).hasClass("outline")) {
					$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
				} else {
					$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
				}
			}*/


/*			if ( String(color).indexOf("rgba") > -1 ) { // rgba
				var colorarray = String(color).replace("rgba(","").replace(")","").split(",");
				shadowColorSource = colorarray[0]+colorarray[1]+colorarray[2]+" "+(Math.round(Number(colorarray[3])*255));
				$(".hud-canvas, .hud-canvas-classmenu, .hud-canvas-teammenu, .hud-canvas-winpanel, .hud-canvas-scoreboard, .hud-canvas-freezecam, .hud-canvas-mainmenu, .hud-canvas-tournamentsetup, .hud-canvas-spectator, .hud-canvas-misclabels").children().each(function() {
					fixColor($(this).attr("id"));
				});
				$("#targetidwrapper").children().each(function() {
					fixColor($(this).attr("id"));
				});
			} else { // rgb
				var colorarray = String(color).replace("rgb(","").replace(")","").split(",");
				shadowColorSource = colorarray[0]+colorarray[1]+colorarray[2]+" 255";
				$(".hud-canvas, .hud-canvas-classmenu, .hud-canvas-teammenu, .hud-canvas-winpanel, .hud-canvas-scoreboard, .hud-canvas-freezecam, .hud-canvas-mainmenu, .hud-canvas-tournamentsetup, .hud-canvas-spectator, .hud-canvas-misclabels").children().each(function() {
					fixColor($(this).attr("id"));
				});
				$("#targetidwrapper").children().each(function() {
					fixColor($(this).attr("id"));
				});
			}*/

			function fixColor(name) {
//				if ( name !== "targetidwrapper" && name.substring(0, 26) !== "playerpanels_kv_shadowcopy" ) {
				if ( name !== "targetidwrapper" ) {
					if ( typeof getObjPath(data,name,"","") !== 'undefined' ) {
						var temp = eval("data" + getObjPath(data,name,"",""));
						var tempShadow;
						if ( typeof getObjPath(data,name+"Shadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"Shadow","",""));
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							if (typeof temp["font"] != 'undefined' && tempShadow["visible"] == "1") {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
							if ( typeof tempShadow["fgcolor"] !== 'undefined' ) { tempShadow["fgcolor"] = shadowColorSource; }
							else if ( typeof tempShadow["fgcolor_override"] !== 'undefined' ) { tempShadow["fgcolor_override"] = shadowColorSource; }
						} else if ( typeof getObjPath(data,name+"shadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"shadow","",""));
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							if (typeof temp["font"] != 'undefined' && tempShadow["visible"] == "1") {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
							if ( typeof tempShadow["fgcolor"] !== 'undefined' ) { tempShadow["fgcolor"] = shadowColorSource; }
							else if ( typeof tempShadow["fgcolor_override"] !== 'undefined' ) { tempShadow["fgcolor_override"] = shadowColorSource; }
						} else if ( typeof getObjPath(data,name+"Dropshadow","","") !== 'undefined' ) {
							tempShadow = eval("data" + getObjPath(data,name+"Dropshadow","",""));
//							$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
							if (typeof temp["font"] != 'undefined' && tempShadow["visible"] == "1") {
//								if (data["jsonclientscheme"]["Scheme"]["Fonts"][temp["font"]]["1"]["outline"] === "1") {
								if ($("#"+name).hasClass("outline")) {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor + ", rgb(0, 0, 0) -1px -1px 0px, rgb(0, 0, 0) 1px -1px 0px, rgb(0, 0, 0) -1px 1px 0px, rgb(0, 0, 0) 1px 1px 0px");
								} else {
									$("#"+name).css("text-shadow", String(shadowSize) + "px " + String(shadowSize) + "px 0px " + shadowColor);
								}
							}
							if ( typeof tempShadow["fgcolor"] !== 'undefined' ) { tempShadow["fgcolor"] = shadowColorSource; }
							else if ( typeof tempShadow["fgcolor_override"] !== 'undefined' ) { tempShadow["fgcolor_override"] = shadowColorSource; }
						}
					}
				}

			}

		}
	});

/*	$('#element-default-fontname').change(function() {
		data["jsonclientscheme"]["Scheme"]["Fonts"]["Default"]["1"]["name"] = $(this).val();
		$("#canv-region").css("font-family",$(this).val());
//		autoSave();
//		location.reload();
	});*/

	$('#element-labeltext').change(function() {
		var name = findSelectedName();
		if (name.substring(0, 12) === "customLabel_") {
			var temp = eval("data" + getObjPath(data,name,"",""));
			$("#"+name).text($(this).val());
			temp["labelText"] = $(this).val();
		}
	});



//**********************************************************//
//					 Detect button press					//
//**********************************************************//
	$( "#PlayerStatusHealthValueButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "125", ".hud-canvas" );

//		$.each(data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"], function(i, v) {
//			if (i.substring(0, 11) === "coloredBox_") {
//				drawElement( i, "", ".hud-canvas" );
//			}
//			if (i.substring(0, 12) === "customLabel_") {
//				drawElement( i, "", ".hud-canvas" );
//			}
//		});

		showCanvas("hud-canvas");
	});

	$( "#AmmoInClipButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "6", ".hud-canvas" );
		drawElement( "AmmoInReserve", "32", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#AmmoInReserveButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "32", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#NumPipesLabelButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "8", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#ChargeMeterButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "", ".hud-canvas" );
		drawElement( "ItemEffectMeter", "", ".hud-canvas" );
		drawElement( "ItemEffectMeterSpycicle", "", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#HudDeathNoticeButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "<span id='noticered' style='padding-left:15px;'>somebody</span> <img id='noticeicon' src='../hudcreator/images/killicon_scattergun.png'/> <span style='margin-right:15px;' id='noticeblue'>roombody</span>", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#TimePanelValueButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
//		Hide deprecated timers
		drawElement( name, "9:59", ".hud-canvas" );
		drawElement( "ServerTimeLimitLabel", "25:59", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#DamageAccountValueButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "-67", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#PlayerStatusHealthImageBGButton" ).button().click(function() {
//		var name = $(this).attr('id').replace('Button','');
		drawElement( "PlayerStatusHealthImageBG", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/healthcross_bg.png'>", ".hud-canvas" );
		drawElement( "PlayerStatusHealthImage", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/healthcross.png'>", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#HealthBGButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#xHairButton" ).button().click(function() {
		var name = $(this).attr('id').replace('Button','');
		drawElement( name, "", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#targetIDButton" ).button().click(function() {
		drawElement( "TargetBGshade", "", "#targetidwrapper" );
		drawElement( "SpectatorGUIHealth", "125", "#targetidwrapper" );
		drawElement( "TargetNameLabel", "Lemon Curry", "#targetidwrapper" );
		drawElement( "TargetDataLabel", "Ubercharge: 50%", "#targetidwrapper" );
//		drawElement( "TargetHealthBG", "", "#targetidwrapper" );
		showCanvas("hud-canvas");
	});

	$( "#disguiseStatusPanelButton" ).button().click(function() {
		drawElement( "DisguiseStatusBG", "", ".hud-canvas" );
		drawElement( "DisguiseNameLabel", "Playername", ".hud-canvas" );
		drawElement( "WeaponNameLabel", "Scattergun", ".hud-canvas" );
		drawElement( "SpectatorGUIHealthSpy", "125", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#killStreakButton" ).button().click(function() {
		drawElement( "ItemEffectMeterCountKillstreak", "5", ".hud-canvas" );
		drawElement( "ItemEffectMeterLabelKillstreak", "STREAK", ".hud-canvas" );
		showCanvas("hud-canvas");
	});

	$( "#classMenuButton" ).button().click(function() {
		showCanvas("hud-canvas-classmenu");
		drawElement( "BGBorder", "", ".hud-canvas-classmenu" );
		drawElement( "teamname", "Select Class", ".hud-canvas-classmenu" );
		drawElement( "random", "? Random", ".hud-canvas-classmenu" );
		drawElement( "scout", "1 Scout", ".hud-canvas-classmenu" );
		drawElement( "soldier", "2 Soldier", ".hud-canvas-classmenu" );
		drawElement( "pyro", "3 Pyro", ".hud-canvas-classmenu" );
		drawElement( "demoman", "4 Demoman", ".hud-canvas-classmenu" );
		drawElement( "heavyweapons", "5 Heavy", ".hud-canvas-classmenu" );
		drawElement( "engineer", "6 Engineer", ".hud-canvas-classmenu" );
		drawElement( "medic", "7 Medic", ".hud-canvas-classmenu" );
		drawElement( "sniper", "8 Sniper", ".hud-canvas-classmenu" );
		drawElement( "spy", "9 Spy", ".hud-canvas-classmenu" );
		drawElement( "numScout", "1", ".hud-canvas-classmenu" );
		drawElement( "numSoldier", "2", ".hud-canvas-classmenu" );
		drawElement( "numPyro", "3", ".hud-canvas-classmenu" );
		drawElement( "numDemoman", "4", ".hud-canvas-classmenu" );
		drawElement( "numHeavy", "5", ".hud-canvas-classmenu" );
		drawElement( "numEngineer", "6", ".hud-canvas-classmenu" );
		drawElement( "numMedic", "7", ".hud-canvas-classmenu" );
		drawElement( "numSniper", "8", ".hud-canvas-classmenu" );
		drawElement( "numSpy", "9", ".hud-canvas-classmenu" );
		// rayshud fix
		if (typeof data["jsonclassselection"]["Resource/UI/ClassSelection.res"]["SidePanelBG"] != 'undefined') { drawElement( "SidePanelBG", "", ".hud-canvas-classmenu" ); }
		// broeselhud fix
		if (typeof data["jsonclassselection"]["Resource/UI/ClassSelection.res"]["BGPanel"] != 'undefined') { drawElement( "BGPanel", "", ".hud-canvas-classmenu" ); }
	});

	$( "#teamMenuButton" ).button().click(function() {
		showCanvas("hud-canvas-teammenu");
		drawElement( "mapname", "cp_badlands", ".hud-canvas-teammenu" );
		drawElement( "BlueLabel", "BLU", ".hud-canvas-teammenu" );
		drawElement( "RedLabel", "RED", ".hud-canvas-teammenu" );
		drawElement( "BlueCount", "6", ".hud-canvas-teammenu" );
		drawElement( "RedCount", "6", ".hud-canvas-teammenu" );
		drawElement( "TeamMenuAuto", "RANDOM", ".hud-canvas-teammenu" );
		drawElement( "TeamMenuSpectate", "SPECTATE", ".hud-canvas-teammenu" );
		drawElement( "blueframe", "", ".hud-canvas-teammenu" );
		drawElement( "redframe", "", ".hud-canvas-teammenu" );
		drawElement( "RandomFrame", "", ".hud-canvas-teammenu" );
		drawElement( "SpectateFrame", "", ".hud-canvas-teammenu" );
		// rayshud fix
		if (typeof data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["BlueTeamBG"] != 'undefined') { drawElement( "BlueTeamBG", "", ".hud-canvas-teammenu" ); }
		if (typeof data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["RedTeamBG"] != 'undefined') { drawElement( "RedTeamBG", "", ".hud-canvas-teammenu" ); }
		if (typeof data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["AutojoinBackground"] != 'undefined') { drawElement( "AutojoinBackground", "", ".hud-canvas-teammenu" ); }
		if (typeof data["jsonteammenu"]["Resource/UI/TeamMenu.res"]["SpectateBackground"] != 'undefined') { drawElement( "SpectateBackground", "", ".hud-canvas-teammenu" ); }
	});

	$( "#winPanelButton" ).button().click(function() {
		showCanvas("hud-canvas-winpanel");
		drawElement( "BlueScoreBGFix", "", ".hud-canvas-winpanel" );
		drawElement( "RedScoreBGFix", "", ".hud-canvas-winpanel" );
		drawElement( "BlueTeamLabel", "BLU", ".hud-canvas-winpanel" );
		drawElement( "BlueTeamScore", "4", ".hud-canvas-winpanel" );
		drawElement( "RedTeamLabel", "RED", ".hud-canvas-winpanel" );
		drawElement( "RedTeamScore", "3", ".hud-canvas-winpanel" );
		drawElement( "WinningTeamLabel", "RED TEAM WINS!", ".hud-canvas-winpanel" );
		drawElement( "ShadedBarWP", "", ".hud-canvas-winpanel" );
		drawElement( "Player1Name", "Player 1", ".hud-canvas-winpanel" );
		drawElement( "Player1Class", "Medic", ".hud-canvas-winpanel" );
		drawElement( "Player1Score", "12", ".hud-canvas-winpanel" );
		drawElement( "Player2Name", "Player 2", ".hud-canvas-winpanel" );
		drawElement( "Player2Class", "Soldier", ".hud-canvas-winpanel" );
		drawElement( "Player2Score", "10", ".hud-canvas-winpanel" );
		drawElement( "Player3Name", "Player 3", ".hud-canvas-winpanel" );
		drawElement( "Player3Class", "Scout", ".hud-canvas-winpanel" );
		drawElement( "Player3Score", "8", ".hud-canvas-winpanel" );
		drawElement( "KillStreakLeaderLabel", "Highest killstreak:", ".hud-canvas-winpanel" );
		drawElement( "KillStreakMaxCountLabel", "Count:", ".hud-canvas-winpanel" );
		drawElement( "HorizontalLine2", "", ".hud-canvas-winpanel" );
		drawElement( "KillStreakPlayer1Name", "Player 4", ".hud-canvas-winpanel" );
		drawElement( "KillStreakPlayer1Class", "Demoman", ".hud-canvas-winpanel" );
		drawElement( "KillStreakPlayer1Score", "3", ".hud-canvas-winpanel" );
//		drawElement( "WinReasonLabel", "RED captured all control points", ".hud-canvas-winpanel" );
//		drawElement( "DetailsLabel", "Winning capture: roombody", ".hud-canvas-winpanel" );
	});

	$( "#scoreboardButton" ).button().click(function() {
		showCanvas("hud-canvas-scoreboard");
		function createPlayerTable(listname, color) {
			var stylecolor = "";
			if (color === "blue") { stylecolor = "color:rgba(153,204,255,1.0);"; }
			else if (color === "red") { stylecolor = "color:rgba(244,65,64,1.0);"; }
			var tempdata = eval("data" + getObjPath(data,listname,"",""));
			var classFix = 35;
			var streakWidth = 30;
			var classimageFix = 12;
			var tablewidthFix = 25;
			var scoresWidth = data["jsonscoreboard"]["Resource/UI/Scoreboard.res"]["scores"];
			var tableWidth = String(Number(scoresWidth["avatar_width"])+Number(scoresWidth["name_width_short"])+classFix+Number(scoresWidth["score_width"])+streakWidth+Number(scoresWidth["ping_width"])+tablewidthFix) + "px";
			var playertable = "<table id='score-table-"+color+"' style='"+stylecolor+"width:"+tableWidth+"'>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;color:#FFF;border-bottom:1px solid #FFF;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Name</td><td style='width:"+classFix+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>Score</td><td style='text-align:right;width:"+streakWidth+"px;'>Streak</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>Ping</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Heavy</td><td rowspan=6 style='width:"+classFix+"px'><img style='width:"+classimageFix+"px;' src='../hudcreator/images/scoreboard_classes.png'/></td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>43</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Scout</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>87</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Spy</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>16</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Medic</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>32</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Pyro</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>14</td></tr>"+
			"<tr style='height:"+tempdata["linespacing"]+"px;'><td style='width:"+scoresWidth["medal_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["avatar_width"]+"px'>&nbsp;</td><td style='width:"+scoresWidth["name_width_short"]+"px'>Soldier</td><td style='text-align:right;width:"+scoresWidth["score_width"]+"px'>0</td><td style='text-align:right;width:"+streakWidth+"px;'>&nbsp;</td><td style='text-align:right;width:"+scoresWidth["ping_width"]+"px'>66</td></tr>"+
			"</table>";
			return playertable;
		}
		drawElement( "BlueScoreboardBG", "", ".hud-canvas-scoreboard" );
		drawElement( "RedScoreboardBG", "", ".hud-canvas-scoreboard" );
		drawElement( "MainBG", "", ".hud-canvas-scoreboard" );
		drawElement( "BlueTeamLabelScoreboard", "BLU", ".hud-canvas-scoreboard" );
		drawElement( "BlueTeamScoreScoreboard", "4", ".hud-canvas-scoreboard" );
		drawElement( "BlueTeamPlayerCount", "6 players", ".hud-canvas-scoreboard" );
		drawElement( "RedTeamLabelScoreboard", "RED", ".hud-canvas-scoreboard" );
		drawElement( "RedTeamScoreScoreboard", "3", ".hud-canvas-scoreboard" );
		drawElement( "RedTeamPlayerCount", "6 players", ".hud-canvas-scoreboard" );
		drawElement( "ServerLabel", "Server name", ".hud-canvas-scoreboard" );
		drawElement( "ServerTimeLeft", "Server map time left: 9:59", ".hud-canvas-scoreboard" );
		drawElement( "VerticalLineScoreboard", "", ".hud-canvas-scoreboard" );
		drawElement( "ShadedBarScoreboard", "", ".hud-canvas-scoreboard" );
		drawElement( "BluePlayerList", createPlayerTable("BluePlayerList", "blue"), ".hud-canvas-scoreboard" );
		drawElement( "RedPlayerList", createPlayerTable("RedPlayerList", "red"), ".hud-canvas-scoreboard" );
		drawElement( "AssistsLabel", "Assists:", ".hud-canvas-scoreboard" );
		drawElement( "DestructionLabel", "Destruction:", ".hud-canvas-scoreboard" );
		drawElement( "CapturesLabel", "Captures:", ".hud-canvas-scoreboard" );
		drawElement( "DefensesLabel", "Defenses:", ".hud-canvas-scoreboard" );
		drawElement( "DominationLabel", "Domination:", ".hud-canvas-scoreboard" );
		drawElement( "RevengeLabel", "Revenge:", ".hud-canvas-scoreboard" );
		drawElement( "HealingLabel", "Healing:", ".hud-canvas-scoreboard" );
		drawElement( "InvulnLabel", "Invulns:", ".hud-canvas-scoreboard" );
		drawElement( "TeleportsLabel", "Teleports:", ".hud-canvas-scoreboard" );
		drawElement( "HeadshotsLabel", "Headshots:", ".hud-canvas-scoreboard" );
		drawElement( "BackstabsLabel", "Backstabs:", ".hud-canvas-scoreboard" );
		drawElement( "BonusLabel", "Bonus:", ".hud-canvas-scoreboard" );
		drawElement( "DamageLabel", "Damage:", ".hud-canvas-scoreboard" );
		drawElement( "KillsFix", "21", ".hud-canvas-scoreboard" );
		drawElement( "DeathsFix", "12", ".hud-canvas-scoreboard" );
		drawElement( "AssistsFix", "3", ".hud-canvas-scoreboard" );
		drawElement( "DestructionFix", "4", ".hud-canvas-scoreboard" );
		drawElement( "CapturesFix", "5", ".hud-canvas-scoreboard" );
		drawElement( "DefensesFix", "6", ".hud-canvas-scoreboard" );
		drawElement( "DominationFix", "7", ".hud-canvas-scoreboard" );
		drawElement( "RevengeFix", "8", ".hud-canvas-scoreboard" );
		drawElement( "HealingFix", "9", ".hud-canvas-scoreboard" );
		drawElement( "InvulnFix", "10", ".hud-canvas-scoreboard" );
		drawElement( "TeleportsFix", "11", ".hud-canvas-scoreboard" );
		drawElement( "HeadshotsFix", "12", ".hud-canvas-scoreboard" );
		drawElement( "BackstabsFix", "13", ".hud-canvas-scoreboard" );
		drawElement( "BonusFix", "14", ".hud-canvas-scoreboard" );
		drawElement( "DamageFix", "1337", ".hud-canvas-scoreboard" );
		drawElement( "MapName", "cp_badlands", ".hud-canvas-scoreboard" );
//		drawElement( "GameType", "Payload", ".hud-canvas-scoreboard" );
		drawElement( "Spectators", "2 Spectators: Turtle, Sausage", ".hud-canvas-scoreboard" );
		drawElement( "SpectatorsInQueue", "SpectatorsInQueue", ".hud-canvas-scoreboard" );
	});

	$( "#freezecamButton" ).button().click(function() {
		showCanvas("hud-canvas-freezecam");
		drawElement( "FreezePanelBG", "", ".hud-canvas-freezecam" );
		drawElement( "FreezeLabel", "You were killed by", ".hud-canvas-freezecam" );
		drawElement( "PlayerStatusHealthValueFreezecam", "125", ".hud-canvas-freezecam" );
		drawElement( "QHUDKillerName", "Killername", ".hud-canvas-freezecam" );
		if (typeof data["jsonfreezepanelbasic"]["Resource/UI/FreezePanel_Basic.res"]["FreezePanelBase"]["FreezePanelBGTitle"] != 'undefined') { drawElement( "FreezePanelBGTitle", "", ".hud-canvas-freezecam" ); }
	});

	$( "#mainmenuButton" ).button().click(function() {
		showCanvas("hud-canvas-mainmenu");
		drawElement( "MainMenuBG", "", ".hud-canvas-mainmenu" );
		drawElement( "CreateServerButton", "Create", ".hud-canvas-mainmenu" );
		drawElement( "TrainingButton", "Training", ".hud-canvas-mainmenu" );
		drawElement( "QuickplayButton", "Multiplayer", ".hud-canvas-mainmenu" );
		drawElement( "ServerBrowserButton", "Servers", ".hud-canvas-mainmenu" );
		drawElement( "CharacterSetupButton", "Items", ".hud-canvas-mainmenu" );
		drawElement( "GeneralStoreButton", "Store", ".hud-canvas-mainmenu" );
		drawElement( "ReplayBrowserButton", "Replays", ".hud-canvas-mainmenu" );
		drawElement( "SteamWorkshopButton", "Workshop", ".hud-canvas-mainmenu" );

		drawElement( "TF2SettingsButton", "Advanced", ".hud-canvas-mainmenu" );
		drawElement( "SettingsButton", "Options", ".hud-canvas-mainmenu" );
		drawElement( "QuitButton", "Quit", ".hud-canvas-mainmenu" );

		drawElement( "NewUserForumsButton", "<p>1</p>", ".hud-canvas-mainmenu" );
		drawElement( "AchievementsButton", "<p>2</p>", ".hud-canvas-mainmenu" );
		drawElement( "CommentaryButton", "<p>3</p>", ".hud-canvas-mainmenu" );
		drawElement( "CoachPlayersButton", "<p>4</p>", ".hud-canvas-mainmenu" );
		drawElement( "ReportBugButton", "<p>5</p>", ".hud-canvas-mainmenu" );
	});

/*	$( "#tournamentsetupButton" ).button().click(function() {
		showCanvas("hud-canvas-tournamentsetup");
		
		drawElement( "HudTournamentSetupBG", "", ".hud-canvas-tournamentsetup" );
		drawElement( "TournamentSetupLabel", "tournamentstatelabel", ".hud-canvas-tournamentsetup" );
		drawElement( "TournamentTeamNameLabel", "Tournament_TeamNamePanel", ".hud-canvas-tournamentsetup" );
		drawElement( "TournamentNameEdit", "teamname", ".hud-canvas-tournamentsetup" );
		drawElement( "HudTournamentNameBG", "", ".hud-canvas-tournamentsetup" );
		drawElement( "TournamentNotReadyButton", "Not Ready", ".hud-canvas-tournamentsetup" );
		drawElement( "TournamentReadyButton", "Ready", ".hud-canvas-tournamentsetup" );
	});*/

/*	$( "#spectatorButton" ).button().click(function() {
		showCanvas("hud-canvas-spectator");
		
		drawElement( "playerpanels_kv", "", ".hud-canvas-spectator" );
		drawElement( "playername", "Playername", ".hud-canvas-spectator" );
		drawElement( "classimage", "<img style='width:16px;height:16px;' src='../hudcreator/images/classimage_redscout.png'>", ".hud-canvas-spectator" );
		drawElement( "chargeamount", "100%", ".hud-canvas-spectator" );
		drawElement( "respawntime", "10s", ".hud-canvas-spectator" );
		drawElement( "Healthicon", "125", ".hud-canvas-spectator" );
		drawElement( "ReinforcementsLabel", "Respawn in: 10 seconds", ".hud-canvas-spectator" );
	});*/

	$( "#miscLabelsButton" ).button().click(function() {
		showCanvas("hud-canvas-misclabels");
		drawElement( "ReinforcementsLabel", "Respawn in: 10 seconds", ".hud-canvas-misclabels" );
		drawElement( "StopWatchLabel", "StopWatchLabel", ".hud-canvas-misclabels" );
		drawElement( "WaitingForPlayersLabel", "WaitingForPlayersLabel", ".hud-canvas-misclabels" );
		drawElement( "OvertimeLabel", "OvertimeLabel", ".hud-canvas-misclabels" );
		drawElement( "SuddenDeathLabel", "SuddenDeathLabel", ".hud-canvas-misclabels" );
		drawElement( "SetupLabel", "SetupLabel", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatusBleedImage", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/bleed_drop.png'>", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatusMilkImage", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/milk_drop.png'>", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatusMarkedForDeathImage", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/marked_for_death.png'>", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatus_Parachute", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/hud_parachute_active.png'>", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatus_WheelOfDoom", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/death_wheel_icon.png'>", ".hud-canvas-misclabels" );
		drawElement( "PlayerStatus_MedicUberBulletResistImage", "<img style='width:100%;height:100%;z-index:99;' src='../hudcreator/images/vaccinator_buff.png'>", ".hud-canvas-misclabels" );
	});


	$( "#coloredBoxButton" ).button().click(function() {
		var currentBoxNumber = $("div[id^='coloredBox_']").length; // number of elements on canvas
		// count number of boxes in scheme, and draw them on canvas
		var NumberOfBoxes = 0;
		$.each(data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"], function(i, v) {
			if (i.substring(0, 11) === "coloredBox_") {
				NumberOfBoxes = NumberOfBoxes + 1;
				drawElement( i, "", ".hud-canvas" );
			}
		});

		function addBoxToScheme() {
			var newBoxName = "coloredBox_" + String(NumberOfBoxes + 1);
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName] = {};
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["ControlName"] = "CExImageButton";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["fieldName"] = newBoxName;
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["xpos"] = "20";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["ypos"] = "20";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["zpos"] = "-10";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["wide"] = "150";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["tall"] = "50";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["autoResize"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["ControlName"] = "CExImageButton";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["visible"] = "1";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["enabled"] = "1";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["defaultBgColor_Override"] = "0 0 0 100";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["PaintBackgroundType"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newBoxName]["textinsety"] = "99";
			drawElement( newBoxName, "", ".hud-canvas" );
		}

		if (NumberOfBoxes === 0 || currentBoxNumber !== 0) { // add new element only if others are already placed
			addBoxToScheme();
		}

		showCanvas("hud-canvas");
	});


	$( "#customLabelButton" ).button().click(function() {
		var currentLabelNumber = $("div[id^='customLabel_']").length; // number of elements on canvas
		// count number of customlabel elements in scheme, and draw them on canvas
		var NumberOfLabels = 0;
		$.each(data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"], function(i, v) {
			if (i.substring(0, 12) === "customLabel_") {
				NumberOfLabels = NumberOfLabels + 1;
				drawElement( i, "", ".hud-canvas" );
			}
		});

		function addLabelToScheme() {
			var newLabelName = "customLabel_" + String(NumberOfLabels + 1);
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName] = {};
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["ControlName"] = "CExLabel";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["fieldName"] = newLabelName;
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["xpos"] = "20";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["ypos"] = "20";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["zpos"] = "10";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["wide"] = "150";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["tall"] = "24";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["visible"] = "1";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["enabled"] = "1";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["labelText"] = newLabelName;
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["textAlignment"] = "north-west";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["font"] = "HudFontGiant";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"][newLabelName]["fgcolor"] = "255 255 255 255";

			drawElement( newLabelName, "", ".hud-canvas" );
		}

		if (NumberOfLabels === 0 || currentLabelNumber !== 0) { // add new element only if others are already placed
			addLabelToScheme();
		}

		showCanvas("hud-canvas");
	});

//******************************************************//
//			Detect a click on the sortable list			//
//******************************************************//
	$('#sortable').on('click','a',function(){
		var name = $(this).attr("id").replace("Ahref","");
		var canvas = $(this).parent().parent().attr("id").replace("SubUl-","");
		controlElement(name);
		showCanvas(canvas);
	});


//**************************************************//
//				Show/hide canvas grid				//
//**************************************************//
	$( "#toggleGrid" ).button().click(function() {
		$("#grid").toggle();
	});

	$( "#changeGrid" ).selectmenu({
		position: { my : "left", at: "left-24 top-89" },
		select: function( event, ui ) {
			$("#grid").css( "background-image", "url('../hudcreator/images/grid_"+ui.item.label+".png')" );
			$("#grid").show();
		}
	});


//**************************************************************//
//				Show credits/donations/custom font				//
//**************************************************************//
	$( "#showCredits" ).button().click(function() {
		$("#credits-dialog").dialog( "open" );
	});
	$( "#showDonations" ).button().click(function() {
		$("#donations-dialog").dialog( "open" );
	});


//**************************************************************//
//			Remove elements from the list and canvas			//
//**************************************************************//
	function removeElement(id) {
		$("#"+id+"-Li").remove(); // remove from list
		$("#"+id).remove(); // remove from canvas
		var temp = eval("data" + getObjPath(data,id,"","")); // make invisible
		temp["visible"] = "0";

		if (id === "PlayerStatusHealthImageBG" || id === "PlayerStatusHealthImage" || id === "HealthBG" || id === "PlayerStatusBleedImage" || id === "PlayerStatusMilkImage" || id === "PlayerStatusMarkedForDeathImage" || id === "PlayerStatus_Parachute" || id === "PlayerStatus_WheelOfDoom" || name === "PlayerStatus_MedicUberBulletResistImage") {
			temp["xpos"] = "9001";
//			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusHealthImage"]["visible"] = "0";
//		} else if (id === "HealthBG") {
//			temp["xpos"] = "9001";
		}

		if (id === "PlayerStatusMarkedForDeathImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatusMarkedForDeathSilentImage"]["visible"] = "0";
		}
		if (id === "PlayerStatus_MedicUberBulletResistImage") {
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberBlastResistImage"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicUberFireResistImage"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBulletResistImage"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallBlastResistImage"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_MedicSmallFireResistImage"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierOffenseBuff"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierDefenseBuff"]["visible"] = "0";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["xpos"] = "9001";
			data["jsonhudplayerhealth"]["Resource/UI/HudPlayerHealth.res"]["PlayerStatus_SoldierHealOnHitBuff"]["visible"] = "0";
		}

		if (id === "QuitButton") {
			temp["visible"] = "0";
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["visible"] = "0";
		} else if (id === "CreateServerButton") {
			temp["SubButton"]["visible"] = "0";
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["visible"] = "0";
		} else if (id === "TrainingButton") {
			temp["SubButton"]["visible"] = "0";
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["visible"] = "0";
		} else if (id === "ServerBrowserButton") {
			temp["SubButton"]["visible"] = "0";
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["visible"] = "0";
		} else if (id === "QuickplayButton") {
			temp["SubButton"]["visible"] = "0";
			data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["visible"] = "0";
		}

	}

	$('body').on('click','#remove',function(){
		var id = $(this).parent().attr('id').replace('-Li','');
		removeElement(id);
	});

	$(document).keyup(function(e) {
		if (e.keyCode == 46) {
			var name = findSelectedName();
			removeElement(name);
		}
	});


//**************************************************//
//			 New font checking functions			//
//**************************************************//
	function existsInScheme(tempfont,tempsize,tempoutline) {
		var result = false;
		$.each(data["jsonclientscheme"]["Scheme"]["Fonts"], function(i, v) {
			if ( v["1"]["name"] === tempfont && v["1"]["tall"] === tempsize && v["1"]["outline"] === tempoutline) {
				result = true;
			}
		});
		return result;
	}
	function checkScheme(tempfont,tempsize,tempid,tempoutline) {
		var newfontname;
		if ( !existsInScheme(tempfont,tempsize,tempoutline) ) { // add to list
			newfontname = tempfont.replace(/ /g,"").replace("-","").replace("_","").replace(/\'/g,"").replace(/\"/g,"") + tempsize;
			if (tempoutline === "1") {
				newfontname = newfontname + "Outline";
			}
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname] = {};
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"] = {};
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"]["name"] = tempfont;
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"]["tall"] = tempsize;
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"]["additive"] = "0";
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"]["antialias"] = "1";
			data["jsonclientscheme"]["Scheme"]["Fonts"][newfontname]["1"]["outline"] = tempoutline;
		} else { // exists in list
			$.each(data["jsonclientscheme"]["Scheme"]["Fonts"], function(i, v) {
				if ( v["1"]["name"] === tempfont && v["1"]["tall"] === tempsize && v["1"]["outline"] === tempoutline) {
					newfontname = i;
					return false;
				}
			});
		}

		if (tempid === "DamageAccountValueFloat" ) {
			$("#DamageAccountValue").attr("huddamagefont", tempfont);
			$("#DamageAccountValue").attr("huddamagesize", tempsize);
			$("#DamageAccountValue").attr("huddamageoutline", tempoutline);
			data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["delta_item_font"] = newfontname;
			data["jsonhuddamageaccount"]["Resource/UI/HudDamageAccount.res"]["CDamageAccountPanel"]["delta_item_font_big"] = newfontname;
		} else if (typeof getObjPath(data,tempid,"","") !== 'undefined') {
			var temp = eval("data" + getObjPath(data,tempid,"",""));
			if (tempid === "HudDeathNotice") {
				temp["TextFont"] = newfontname;
			} else if (tempid === "CreateServerButton" || tempid === "TrainingButton" || tempid === "QuickplayButton" || tempid === "ServerBrowserButton" || tempid === "CharacterSetupButton" || tempid === "GeneralStoreButton" || tempid === "ReplayBrowserButton" || tempid === "SteamWorkshopButton") {
				temp["SubButton"]["font"] = newfontname;
			} else {
				temp["font"] = newfontname;
			}

			var tempShadow;
			if ( typeof getObjPath(data,tempid+"Shadow","","") !== 'undefined' ) {
				tempShadow = eval("data" + getObjPath(data,tempid+"Shadow","",""));
				tempShadow["font"] = temp["font"];
			} else if ( typeof getObjPath(data,tempid+"shadow","","") !== 'undefined' ) {
				tempShadow = eval("data" + getObjPath(data,tempid+"shadow","",""));
				tempShadow["font"] = temp["font"];
			} else if ( typeof getObjPath(data,tempid+"Dropshadow","","") !== 'undefined' ) {
				tempShadow = eval("data" + getObjPath(data,tempid+"Dropshadow","",""));
				tempShadow["font"] = temp["font"];
			}

			if (tempid === "TimePanelValue") {
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["BlueTimer"]["TimePanelValue"]["font"] = newfontname;
				data["jsonkothtimepanel"]["Resource/UI/HudObjectiveKothTimePanel.res"]["RedTimer"]["TimePanelValue"]["font"] = newfontname;
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchScoreToBeat"]["font"] = newfontname;
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["StopWatchPointsLabel"]["font"] = newfontname;
				data["jsonstopwatch"]["Resource/UI/HudStopWatch.res"]["ObjectiveStatusTimePanel"]["TimePanelValue"]["font"] = newfontname;
			}
			if (tempid === "AmmoInClip") {
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClip"]["font"] = newfontname;
				data["jsonhudammoweapons"]["Resource/UI/HudAmmoWeapons.res"]["AmmoNoClipShadow"]["font"] = newfontname;
				data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeLabel"]["font"] = newfontname;
				data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["IndividualChargesLabel"]["font"] = newfontname;
			}

			if (tempid === "WinningTeamLabel") {
				data["jsonwinpanel"]["Resource/UI/winpanel.res"]["AdvancingTeamLabel"]["font"] = newfontname;
			}

			if (tempid === "ChargeMeter") {
				data["jsonhudmediccharge"]["Resource/UI/HudMedicCharge.res"]["ChargeMeter"]["font"] = newfontname;
				data["jsonhudbowcharge"]["Resource/UI/HudBowCharge.res"]["ChargeMeter"]["font"] = newfontname;
				data["jsonhuddemomanpipes"]["Resource/UI/HudDemomanPipes.res"]["ChargeMeter"]["font"] = newfontname;
			}
			if (tempid === "NumPipesLabel") {
				data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValue"]["font"] = newfontname;
				if ( typeof data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"] != 'undefined' ) { data["jsonhudaccountpanel"]["Resource/UI/HudAccountPanel.res"]["AccountValueShadow"]["font"] = newfontname; }
			}
			if (tempid === "ReinforcementsLabel") {
				data["jsonspectatortournament"]["Resource/UI/SpectatorTournament.res"]["ReinforcementsLabel"]["font"] = newfontname;
			}
			if (tempid === "SpectatorGUIHealth" || tempid === "SpectatorGUIHealthSpy") {
				data["jsonspectatorguihealth"]["Resource/UI/SpectatorGUIHealth.res"]["PlayerStatusHealthValue"]["font"] = newfontname;
			}
			if (tempid === "QuitButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["DisconnectButton"]["font"] = newfontname;
			} else if (tempid === "CreateServerButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ChangeServerButton"]["SubButton"]["font"] = newfontname;
			} else if (tempid === "TrainingButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["RequestCoachButton"]["SubButton"]["font"] = newfontname;
			} else if (tempid === "ServerBrowserButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["ResumeGameButton"]["SubButton"]["font"] = newfontname;
			} else if (tempid === "QuickplayButton") {
				data["jsonmainmenuoverride"]["Resource/UI/MainMenuOverride.res"]["CallVoteButton"]["SubButton"]["font"] = newfontname;
			}
		}
	}

	function updateFontnames() {
		$('.hud-canvas, .hud-canvas-classmenu, .hud-canvas-teammenu, .hud-canvas-winpanel, .hud-canvas-scoreboard, .hud-canvas-freezecam, .hud-canvas-mainmenu, .hud-canvas-tournamentsetup, .hud-canvas-spectator, .hud-canvas-misclabels, #targetidwrapper').find('*').each(function() {
//			if ( $(this).attr("id") !== "targetidwrapper" && name.substring(0, 26) !== "playerpanels_kv_shadowcopy" ) {
			if ( $(this).attr("id") !== "targetidwrapper" ) {
				var tempid = $(this).attr("id");
				var tempfont = $(this).css("font-family").replace(/\'/g,"").replace(/\"/g,"");
				var tempfontfix = "";
				if (typeof $(this).attr("fontfix") !== 'undefined') {
					tempfontfix = $(this).attr("fontfix").replace(/\'/g,"").replace(/\"/g,"");
				}
//				console.log(tempfont);
				var tempsize = $(this).css("font-size").replace("px","");
				var tempoutline;
				if ($(this).hasClass("outline")) {
					tempoutline = "1";
				} else {
					tempoutline = "0";
				}
				// in data
				if (tempfont === "Crosshairs") {
					tempsize = String(Number(tempsize) - 5);
				} else if (tempfont === "TF2 Build") {
					tempsize = String(Math.round((Number(tempsize)*10)/(10 - 1)));
				} else if (tempfont === "PF Tempesta Seven") {
					tempsize = String(Math.round((Number(tempsize)*2.5)/(2.5 - 1)));
				} else if (tempfont === "Counter-Strike") {
					tempsize = String(Number(tempsize) - 7);
				} else {
					tempsize = String(Math.round((Number(tempsize)*5)/(5 - 1)));
				}

				if ( tempfont !== 'undefined' && tempid !== 'undefined' && typeof tempfont !== 'undefined' && typeof tempid !== 'undefined' && tempfontfix !== "Default" ) { // if both name and size exist -> check in clientscheme (exclude "Default")
					checkScheme(tempfont,tempsize,tempid,tempoutline);
					if (tempid === "DamageAccountValue") {
						var damagefont = $("#"+tempid).attr("huddamagefont");
						var damagesize = $("#"+tempid).attr("huddamagesize");
						var damageoutline = $("#"+tempid).attr("huddamageoutline");
						checkScheme(damagefont,damagesize,tempid+"Float",damageoutline);
					}
				}
			}
		});
	}


//**************************************************//
//				 Download hud button				//
//**************************************************//
	$( "#downloadButton" ).button().click(function() {
		autoSave();
		$('#loaderlogo').text("Creating your hud, please wait...");
		$('#loaderwrapper').show();
//		console.log(data);
//		var popup = window.open('', '', '');
//		popup.document.write("<div id='popupdlwrapper'><div id='popupdl'><div id='popupdllogo'>Creating your hud, please wait...</div></div></div>");
		$.ajax({
			type: 'POST',
//			async: false,
			async: true,
			url: './php/save.php',
			data: { Output : JSON.stringify(data) },
			success: function(link) {
				window.open(link);
//				popup.location = link;
				$('#loaderwrapper').hide();
			}
		});
	});


//**********************************************//
//				 Save hud button				//
//**********************************************//
/*	$( "#saveButton" ).button().click(function() {
		autoSave();
		$.ajax({
			type: 'POST',
			async: false,
			url: './php/savelocal.php',
			data: { Output : JSON.stringify(data) },
			success: function(link) {
//				console.log(link);
				alert("saved");
			}
		});
	});*/

<?php echo $savetodb0 ?>

//**************************************************//
//				 Auto-Save hud elements				//
//**************************************************//
	function autoSave() {
		$('#autosave').fadeIn('slow');
		var canvasstate = {};
		var canvasstatetargetid = {};
		var canvasstateinnerid = {};

		var canvasstateclassmenu = {};
		var canvasstateteammenu = {};
		var canvasstatewinpanel = {};
		var canvasstatescoreboard = {};
		var canvasstatefreezecam = {};
		var canvasstatemainmenu = {};
/*		var canvasstatetournamentsetup = {};
		var canvasstatespectator = {};*/
		var canvasstatemisclabels = {};

		updateFontnames();

		$('.hud-canvas').children().each(function() {
			if ( $(this).attr("id") !== "targetidwrapper" ) {
				canvasstate[$(this).attr("id")] = $(this).html();
			}
		});
		$('#targetidwrapper').children().each(function() {
			canvasstatetargetid[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-classmenu').children().each(function() {
			canvasstateclassmenu[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-teammenu').children().each(function() {
			canvasstateteammenu[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-winpanel').children().each(function() {
			canvasstatewinpanel[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-scoreboard').children().each(function() {
			canvasstatescoreboard[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-freezecam').children().each(function() {
			canvasstatefreezecam[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-mainmenu').children().each(function() {
			canvasstatemainmenu[$(this).attr("id")] = $(this).html();
		});
/*		$('.hud-canvas-tournamentsetup').children().each(function() {
			canvasstatetournamentsetup[$(this).attr("id")] = $(this).html();
		});
		$('.hud-canvas-spectator').children().each(function() {
			canvasstatespectator[$(this).attr("id")] = $(this).html();
		});*/
		$('.hud-canvas-misclabels').children().each(function() {
			canvasstatemisclabels[$(this).attr("id")] = $(this).html();
		});

		localStorage.canvasstate = JSON.stringify(canvasstate);
		localStorage.canvasstatetargetid = JSON.stringify(canvasstatetargetid);
//		localStorage.canvasstateinnerid = JSON.stringify(canvasstateinnerid);
		localStorage.canvasstateclassmenu = JSON.stringify(canvasstateclassmenu);
		localStorage.canvasstateteammenu = JSON.stringify(canvasstateteammenu);
		localStorage.canvasstatewinpanel = JSON.stringify(canvasstatewinpanel);
		localStorage.canvasstatescoreboard = JSON.stringify(canvasstatescoreboard);
		localStorage.canvasstatefreezecam = JSON.stringify(canvasstatefreezecam);
		localStorage.canvasstatemainmenu = JSON.stringify(canvasstatemainmenu);
/*		localStorage.canvasstatetournamentsetup = JSON.stringify(canvasstatetournamentsetup);
		localStorage.canvasstatespectator = JSON.stringify(canvasstatespectator);*/
		localStorage.canvasstatemisclabels = JSON.stringify(canvasstatemisclabels);

		localStorage.resourcedata = JSON.stringify(data);
		$('#autosave').fadeOut('slow');
	}
	setInterval(autoSave, 20000);


//******************************************************//
//					 Upload custom HUD					//
//******************************************************//
	$("#HUDuploaderHidden").uploadFile({
		url:"./php/upload_hud.php",
		allowedTypes:"zip",
		autoSubmit:true,
		uploadButtonClass:"invisible",
		onSubmit:function(files) {
			$('#loaderlogo').text("Uploading your custom HUD, please wait...");
			$('#loaderwrapper').show();
		},
		onSuccess:function(files,incdata,xhr) {
			function IsJsonString(str) {
				try { JSON.parse(str);}
				catch (e) { return false; }
				return true;
			}
			if (IsJsonString(incdata)) {
				$('#loaderwrapper').hide();
				if (JSON.parse(incdata)["jsonhudlayout"] !== null) {
					if ( JSON.parse(incdata)["jsonhudlayout"]["length"] === 0 ) {
						alert("Your custom HUD seems to be corrupted");
					} else {
						data = JSON.parse(incdata);
						alert('Custom hud uploaded successfully.');
						localStorage.resourcedata = JSON.stringify(data);
						autoSave();
						location.reload();
					}
				} else {
					alert("Your custom HUD seems to be corrupted - 2");
					console.log(incdata);
				}
			} else {
				console.log(incdata);
				alert(incdata);
			}
		}
	});
	
	$('body').on('click','#HUDuploader',function(){
		$( "#ajax-upload-id-HUDuploaderHidden" ).trigger( "click" );
	});


//**************************************************//
//				 Convert HUD button				//
//**************************************************//
	$( "#convertButton" ).button().click(function() {
//		autoSave();
		$('#loaderlogo').text("Converting your custom hud...");
		$('#loaderwrapper').show();
		$.ajax({
			type: 'GET',
			async: true,
			url: "./php/convert.php",
			data: { width : Math.round(localStorage.canvaswidth), height : localStorage.canvasheight },
			success: function(incdata) {
				function IsJsonString(str) {
					try { JSON.parse(str);}
					catch (e) { return false; }
					return true;
				}
				if (IsJsonString(incdata)) {
					data = JSON.parse(incdata);
					autoSave();
					location.reload();
				} else {
					$('#loaderwrapper').hide();
					alert("Something went wrong while converting your HUD. Check console for php script output.");
					console.log(incdata);
				}
			}
		});
	});


});

</script>

</head>
<body>

<?php echo $savetodb1 ?>

<div id="dialog-form" title="Pick screen resolution">
	<form>
		<fieldset>	
			<label>Width:</label><input style="margin-left:15px;" type="text" id="canvas-width" class="text ui-widget-content ui-corner-all" value="1280"/>
			<div style="height:10px;">&nbsp;</div>
			<label>Height:</label><input style="margin-left:11px;" type="text" id="canvas-height" class="text ui-widget-content ui-corner-all" value="720"/>
		</fieldset>
	</form>
</div>

<div id="outofdate-dialog" title="Out of date">
	<p>This project is not maintained anymore. I make some small changes to it, once every blue moon, but otherwise it's pretty much dead.</p>
</div>

<div id="credits-dialog" title="Credits">
	<p>Project developed by <a href="http://steamcommunity.com/id/qball91">Q-Ball</a></p>
	<p>Special thanks goes to developer of <a href="https://code.google.com/p/keredhud/" target="_blank">keredhud</a> and <a href="http://visualhud.pk69.com/" target="_blank">Visual HUD: Quake Live online HUD generator</a>, from whom I pinched the design layout.</p>
	<p>Thanks to Fog for his <a href="http://etf2l.org/forum/customise/topic-21559/" target="_blank">Custom HUD Crosshairs</a></p>
	<p><a href="http://jquery.com/" target="_blank">jQuery</a><br>Copyright 2014 The jQuery Foundation. jQuery License</p>
	<p><a href="http://jqueryui.com/" target="_blank">jQuery UI</a><br>Copyright 2014 The jQuery Foundation. jQuery License</p>
	<p><a href="http://hayageek.com/" target="_blank">jQuery Upload File Plugin</a><br>Copyright (c) 2013 Ravishanker Kusuma http://hayageek.com/</p>
	<p><a href="https://github.com/bgrins/spectrum/" target="_blank">Spectrum Colorpicker v1.3.4</a><br>Copyright (c) 2013 Ravishanker Kusuma https://github.com/bgrins/spectrum/</p>
	<p><a href="http://abeautifulsite.net/" target="_blank">jQuery dropdown: A simple dropdown plugin</a><br>Copyright 2013 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)</p>
	<p><a href="http://html2canvas.hertzen.com/" target="_blank">html2canvas</a><br>Created by Niklas von Hertzen. Licensed under the MIT License. (http://html2canvas.hertzen.com/)</p>
</div>
<div id="donations-dialog" title="Donations">
<!--	<p>If this was helpful for you in any way, please, consider donating to support my project.</p>
	<p align="center">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----MIIHNwYJKoZIhvcNAQcEoIIHKDCCByQCAQExggEwMIIBLAIBADCBlDCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20CAQAwDQYJKoZIhvcNAQEBBQAEgYATPKu37gjjQ6FL1QwNlzbsflvH9pNYlFYKk1Rx1Hv9trcUh3LmrnDg5IzPwm/567IrG/NAycasjfezGQx9xnbPfqozLq0JBF1jOryI7x2jdLmssiAdeB+D11k1umH0qZxeQsljaGZOmHdAP5zuiJbvRDLTn6Le6q5pjGbmGwQflTELMAkGBSsOAwIaBQAwgbQGCSqGSIb3DQEHATAUBggqhkiG9w0DBwQIHkPFldNTdumAgZAV63JEz/S5TRq9sGtOBAgD3IaBAS0cyHhmFPdhbGcjCcwoQNwZIkG06rHziMXXOucDiTG6524O8GX2T2kPzF+IfY8XKiCekeAP4zbO32zOHgaN066lq5J1nk4E1E9udRpt8fk7lYXwIcKKRaMqoNhLl2+UgutZ8zBzzVYOmbh9TSFQDn7g9Bxudrj5i7mTx0mgggOHMIIDgzCCAuygAwIBAgIBADANBgkqhkiG9w0BAQUFADCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wHhcNMDQwMjEzMTAxMzE1WhcNMzUwMjEzMTAxMzE1WjCBjjELMAkGA1UEBhMCVVMxCzAJBgNVBAgTAkNBMRYwFAYDVQQHEw1Nb3VudGFpbiBWaWV3MRQwEgYDVQQKEwtQYXlQYWwgSW5jLjETMBEGA1UECxQKbGl2ZV9jZXJ0czERMA8GA1UEAxQIbGl2ZV9hcGkxHDAaBgkqhkiG9w0BCQEWDXJlQHBheXBhbC5jb20wgZ8wDQYJKoZIhvcNAQEBBQADgY0AMIGJAoGBAMFHTt38RMxLXJyO2SmS+Ndl72T7oKJ4u4uw+6awntALWh03PewmIJuzbALScsTS4sZoS1fKciBGoh11gIfHzylvkdNe/hJl66/RGqrj5rFb08sAABNTzDTiqqNpJeBsYs/c2aiGozptX2RlnBktH+SUNpAajW724Nv2Wvhif6sFAgMBAAGjge4wgeswHQYDVR0OBBYEFJaffLvGbxe9WT9S1wob7BDWZJRrMIG7BgNVHSMEgbMwgbCAFJaffLvGbxe9WT9S1wob7BDWZJRroYGUpIGRMIGOMQswCQYDVQQGEwJVUzELMAkGA1UECBMCQ0ExFjAUBgNVBAcTDU1vdW50YWluIFZpZXcxFDASBgNVBAoTC1BheVBhbCBJbmMuMRMwEQYDVQQLFApsaXZlX2NlcnRzMREwDwYDVQQDFAhsaXZlX2FwaTEcMBoGCSqGSIb3DQEJARYNcmVAcGF5cGFsLmNvbYIBADAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBBQUAA4GBAIFfOlaagFrl71+jq6OKidbWFSE+Q4FqROvdgIONth+8kSK//Y/4ihuE4Ymvzn5ceE3S/iBSQQMjyvb+s2TWbQYDwcp129OPIbD9epdr4tJOUNiSojw7BHwYRiPh58S1xGlFgHFXwrEBb3dgNbMUa+u4qectsMAXpVHnD9wIyfmHMYIBmjCCAZYCAQEwgZQwgY4xCzAJBgNVBAYTAlVTMQswCQYDVQQIEwJDQTEWMBQGA1UEBxMNTW91bnRhaW4gVmlldzEUMBIGA1UEChMLUGF5UGFsIEluYy4xEzARBgNVBAsUCmxpdmVfY2VydHMxETAPBgNVBAMUCGxpdmVfYXBpMRwwGgYJKoZIhvcNAQkBFg1yZUBwYXlwYWwuY29tAgEAMAkGBSsOAwIaBQCgXTAYBgkqhkiG9w0BCQMxCwYJKoZIhvcNAQcBMBwGCSqGSIb3DQEJBTEPFw0xNDA4MDIxMDI1NDhaMCMGCSqGSIb3DQEJBDEWBBR1aqcvg9H1Uu0nJwnceNLHYdpsqzANBgkqhkiG9w0BAQEFAASBgKO8C10SBYsAIlYRI+3N6mvCT2IofKcCraS3O4z4SloDzrOJpP12cBa7wfcC9o223grBVgm0+A+kxoTKKWfoHuFSf/QRO52PJY2shhm3nWh0AvzkLdkzKalaB8F7xnpNgeAuB2rZKLcird85jrjjHw9n/YusO0XV6xo3qfYxc44L-----END PKCS7-----
			">
			<input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal  The safer, easier way to pay online.">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
		</form>
	</p>
-->
</div>

<div class="region-topbar">
		<button id="downloadButton" class="classicButton custom-icon icon-download" style="margin-top:10px;margin-left:110px;">Download HUD</button>
		<button id="HUDuploader" class="classicButton custom-icon icon-upload" style="margin-top:10px;">Upload HUD</button>
		<?php echo $savetodb2; ?>
<!--		<button id="saveButton" class="classicButton custom-icon icon-save">Save HUD</button> -->
<!--		<button id="convertButton" class="classicButton custom-icon">Convert custom HUD</button> -->
		<div style="float:right;margin-right:12px;margin-top:10px;">
			<?php echo $login; ?>
			<button id="showCredits" class="classicButton">Credits</button>
			<button id="showDonations" class="classicButton">Donations</button>
		</div>
</div>

<div class="region-sidebar">
	<div class="region-sidebar-content">
		<div class="stage-controlls-area">
			<div class="sidebar-block">
				<h3>Hud elements</h3>
				<h5>Main screen</h5>
				<div style="width:360px;" class="marginbot10 height100">
					<button class="iconButton" id="PlayerStatusHealthValueButton" title="Add health"></button>
					<button class="iconButton" id="AmmoInClipButton" title="Add ammo"></button>
					<button class="iconButton" id="NumPipesLabelButton" title="Add sticky counter"></button>
					<button class="iconButton" id="ChargeMeterButton" title="Add charge meter"></button>
					<button class="iconButton" id="HudDeathNoticeButton" title="Add killfeed"></button>
					<button class="iconButton" id="TimePanelValueButton" title="Add round time"></button>
					<button class="iconButton" id="DamageAccountValueButton" title="Add damage numbers"></button>
					<button class="iconButton" id="PlayerStatusHealthImageBGButton" title="Add health cross image"></button>
					<button class="iconButton" id="HealthBGButton" title="Add health block"></button>
					<button class="iconButton" id="killStreakButton" title="Add killstreak label"></button>
					<button class="iconButton" id="xHairButton" title="Add crosshair"></button>
					<button class="iconButton" id="coloredBoxButton" title="Add colored box"></button>
					<button class="iconButton" id="customLabelButton" title="Add custom label"></button>
				</div>
				<h5>TargetID</h5>
				<div style="width:360px;" class="marginbot10 height50">
					<button class="iconButton" id="targetIDButton" title="Edit target id"></button>
					<button class="iconButton" id="disguiseStatusPanelButton" title="Edit disguise status"></button>
				</div>
				<h5>Others</h5>
				<div style="width:360px;" class="marginbot10 height100">
					<button class="iconButton" id="classMenuButton" title="Edit class menu" ></button>
					<button class="iconButton" id="teamMenuButton" title="Edit team menu"></button>
					<button class="iconButton" id="winPanelButton" title="Edit winpanel"></button>
					<button class="iconButton" id="scoreboardButton" title="Edit scoreboard"></button>
					<button class="iconButton" id="freezecamButton" title="Edit freezecam"></button>
					<button class="iconButton" id="mainmenuButton" title="Edit main menu"></button>
<!--					<button class="iconButton" id="tournamentsetupButton" title="Edit setup menu"></button> -->
<!--					<button class="iconButton" id="spectatorButton" title="Edit spectators menu"></button> -->
					<button class="iconButton" id="miscLabelsButton" title="Edit misc labels"></button>
				</div>
			</div>
			<div id="hudElementParameters" style="display:none;" class="sidebar-block">
				<h3>Element parameters</h3>
				<div style="padding-left:10px;">
					<div><label>name:</label><input type="text" id="element-name" style="width:200px; margin-left:14px; margin-bottom:5px;" disabled="disabled"/></div>
					<div id="divxpos"><label>xpos:</label><input type="text" id="element-xpos" style="width:200px; margin-left:20px; margin-bottom:5px;" /></div>
					<div id="divypos"><label>ypos:</label><input type="text" id="element-ypos" style="width:199px; margin-left:20px; margin-bottom:5px;" /></div>
					<div id="divwide" style="display:none;"><label>wide:</label><input type="text" id="element-wide" style="width:200px; margin-left:20px; margin-bottom:5px;" /></div>
					<div id="divtall" style="display:none;"><label>tall:</label><input type="text" id="element-tall" style="width:200px; margin-left:29px; margin-bottom:5px;" /></div>

					<div id="div-fontname"><label>Font name:</label><select id="element-fontname" style="width:176px; margin-left:12px; margin-bottom:5px; height: 40px;" /></select></div>
					<div id="div-fontsize"><label>Font size:</label><input type="text" id="element-fontsize" style="width:176px;margin-left:23px;margin-bottom:5px;" /></div>

					<div id="highhealth" style="display:none;"><label>High health color:</label><input type="text" id="element-color-high" style="width:135px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-high"/></div>
					<div id="normalhealth" style="display:none;"><label>Normal color:</label><input type="text" id="element-color-normal" style="width:157px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-normal"/></div>
					<div id="lowhealth" style="display:none;"><label>Low health color:</label><input type="text" id="element-color-low" style="width:139px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-low"/></div>

					<div id="lowammo-inclip" style="display:none;"><label>Low ammo color:</label><input type="text" id="element-color-ammo-low-clip" style="width:137px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-ammo-low-clip"/></div>
					<div id="lowammo-inreserve" style="display:none;"><label>Low ammo color:</label><input type="text" id="element-color-ammo-low-reserve" style="width:137px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-ammo-low-reserve"/></div>

					<div id="hitmarker" style="display:none;"><label>Hit marker color:</label><input type="text" id="element-color-hit" style="width:141px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-hit"/></div>

					<div id="divhover" style="display:none;"><label>Hover font color:</label><input type="text" id="element-color-hover" style="width:141px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-hover"/></div>

					<div id="highhealthbar" style="display:none;"><label>High health color:</label><input type="text" id="element-color-high-bar" style="width:135px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-high-bar"/></div>
					<div id="lowhealthbar" style="display:none;"><label>Low health color:</label><input type="text" id="element-color-low-bar" style="width:139px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-low-bar"/></div>

					<div id="divtargethigh" style="display:none;"><label>High health color:</label><input type="text" id="element-color-high-targetbar" style="width:135px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-high-targetbar"/></div>
					<div id="divtargetnormal" style="display:none;"><label>Normal health color:</label><input type="text" id="element-color-normal-targetbar" style="width:139px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-normal-targetbar"/></div>
					<div id="divtargetlow" style="display:none;"><label>Low health color:</label><input type="text" id="element-color-low-targetbar" style="width:139px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-low-targetbar"/></div>

					<div id="divkothblue" style="display:none;"><label>Koth blue team color:</label><input type="text" id="element-color-kothblue" style="width:116px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-kothblue"/></div>
					<div id="divkothred" style="display:none;"><label>Koth red team color:</label><input type="text" id="element-color-kothred" style="width:122px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-kothred"/></div>

					<div id="divchargefg" style="display:none;"><label>Charge foreground color:</label><input type="text" id="element-chargefg" style="width:104px; margin-left:10px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-chargefg"/></div>
					<div id="divchargebg" style="display:none;"><label>Charge background color:</label><input type="text" id="element-chargebg" style="width:100px; margin-left:10px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-chargebg"/></div>
					<div id="divuber1" style="display:none;"><label>Uber color 1:</label><input type="text" id="element-color-uber1" style="width:163px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-uber1"/></div>
					<div id="divuber2" style="display:none;"><label>Uber color 2:</label><input type="text" id="element-color-uber2" style="width:163px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-uber2"/></div>

					<div id="cornerradius" style="display:none;"><label>Corner radius:</label><input type="text" id="element-cornerradius" style="width:156px; margin-left:18px; margin-bottom:5px;"/></div>
					<div id="teamreddiv" style="display:none;"><label>Team red color:</label><input type="text" id="element-color-teamred" style="width:146px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-teamred"/></div>
					<div id="teambluediv" style="display:none;"><label>Team blue color:</label><input type="text" id="element-color-teamblue" style="width:139px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-teamblue"/></div>
					<div id="divbasebg" style="display:none;"><label>Base color:</label><input type="text" id="element-color-basebg" style="width:171px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-basebg"/></div>
					<div id="divlocalbg" style="display:none;"><label>Local color:</label><input type="text" id="element-color-localbg" style="width:167px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-localbg"/></div>
					<div id="divjustify" style="display:none;"><label>Justify:</label>
						<select id="element-killjustify" style="width:152px; margin-left:23px; margin-bottom:5px; height: 40px;" />
							<option value="0">Left</option>
							<option value="1">Right</option>
						</select>
					</div>

					<div id="divalign"><label>Text alignment:</label>
						<select id="element-alignment" style="width:140px; margin-left:23px; margin-bottom:5px; height: 40px;" />
							<option value="north-west">Left</option>
							<option value="north">Center</option>
							<option value="north-east">Right</option>
						</select>
					</div>

					<div id="divxhairtype"><label>Crosshair style:</label><select id="element-xhair-type" style="font-family:Crosshairs;font-size:24px;width:146px;margin-left:23px;margin-bottom:5px;" /></select></div>
					<div id="divxhairsize"><label>Crosshair size:</label><input type="text" id="element-xhair-size" style="width:151px;margin-left:23px;margin-bottom:5px;" /></div>
					<div id="divxhairoutline" style="margin-bottom: 5px;margin-top: 5px;"><input type="checkbox" id="element-xhair-outline" value=""/><label for="element-xhair-outline">Crosshair outline</label></div>

					<div id="damagecolor" style="display:none;"><label>Float damage color:</label><input type="text" id="element-color-damagecolor" style="width:149px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-damagecolor"/></div>
					<div id="healingcolor" style="display:none;"><label>Float healing color:</label><input type="text" id="element-color-healingcolor" style="width:149px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-healingcolor"/></div>
					<div id="div-damage-fontname"><label>Float text font name:</label><select id="element-damage-fontname" style="width:128px; margin-left:23px; margin-bottom:5px; height: 40px;" /></select></div>
					<div id="div-damage-fontsize"><label>Font text font size:</label><input type="text" id="element-damage-fontsize" style="width:129px;margin-left:23px;margin-bottom:5px;" /></div>
					<div id="divmedicoffset"><label>Medic targetid offset:</label><input type="text" id="element-medicoffset" style="width:115px; margin-left:20px; margin-bottom:5px;" /></select></div>
					<div id="divlabeltext"><label>Label text:</label><input type="text" id="element-labeltext" style="width:175px; margin-left:20px; margin-bottom:5px;" /></div>

					<div id="divshadow" style="margin-bottom: 5px;margin-top: 5px;"><input type="checkbox" id="element-shadow" value=""/><label for="element-shadow">Add shadow</label></div>
					<div id="divoutline" style="margin-bottom: 5px;margin-top: 5px;"><input type="checkbox" id="element-outline" value=""/><label for="element-outline">Add outline</label></div>
					<div id="div-damage-outline" style="margin-bottom: 5px;margin-top: 5px;"><input type="checkbox" id="element-damage-fontoutline" value=""/><label for="element-damage-fontoutline">Float text outline</label></div>
					<div id="divbuff" style="margin-bottom: 5px;margin-top: 5px;"><input type="checkbox" id="element-buffed" value=""/><label for="element-buffed">Health buffed effect</label></div>
				</div>
			</div>
			<div class="sidebar-block">
				<h3>General settings</h3>
				<div><label style="margin-right:5px;">Shadow offset:</label><input type="text" id="shadowoffset" style="width:165px;margin-left:10px;margin-bottom:5px;"/></div>
				<div><label>Shadow color:</label><input type="text" id="shadowOverallColor" style="width:165px; margin-left:18px; margin-bottom:5px;" disabled="disabled"/><input type='text' id="colorpicker-shadowOverallColor"/></div>
<!--				<div><label>Default font name:</label><select id="element-default-fontname" style="width:177px; margin-left:23px; margin-bottom:5px; height: 40px;" /></select></div> -->
			</div>
			<div class="sidebar-block">
				<h3>Custom font</h3>
				<div style="width:110px;padding-left:35px;" class="classicButton custom-icon icon-add" id="showCustomFont">Add custom font</div>
			</div>
			<div class="sidebar-block">
				<h3>List of current elements</h3>
				<div id="listOfCurrentElements">
					<ul id="sortable">
						<li>Main screen<ul id="SubUl-hud-canvas"></ul></li>
						<li>Class menu<ul id="SubUl-hud-canvas-classmenu"></ul></li>
						<li>Team menu<ul id="SubUl-hud-canvas-teammenu"></ul></li>
						<li>Win panel<ul id="SubUl-hud-canvas-winpanel"></ul></li>
						<li>Scoreboard<ul id="SubUl-hud-canvas-scoreboard"></ul></li>
						<li>Freezecam<ul id="SubUl-hud-canvas-freezecam"></ul></li>
						<li>Main menu<ul id="SubUl-hud-canvas-mainmenu"></ul></li>
<!--						<li>Tournament Setup<ul id="SubUl-hud-canvas-tournamentsetup"></ul></li> -->
<!--						<li>Spectators panel<ul id="SubUl-hud-canvas-spectator"></ul></li> -->
						<li>Misc labels<ul id="SubUl-hud-canvas-misclabels"></ul></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="region-bottombar">
	<button id="ChangeResolution" class="classicButton custom-icon icon-resolution">Change resolution</button>
	<button id="toggleGrid" class="classicButton custom-icon icon-grid">Toggle Grid</button>
	<button id="Reset" class="classicButton custom-icon icon-reset">Reset</button>
	<div style="float:right;margin-top:10px;margin-right:12px;">
		<label style="margin-right:5px;margin-left:12px;">Grid size:</label>
		<select id="changeGrid">
			<option id="0">5px</option>
			<option id="1">25px</option>
			<option id="2">50px</option>
			<option id="3">100px</option>
			<option id="4">240px</option>
		</select>

		<label style="margin-left:15px;margin-right:5px;">Canvas:</label>
		<select id="visibleCanvas">
			<option id="showMainScreen">Main screen</option>
			<option id="showClassmenu">Class menu</option>
			<option id="showTeammenu">Team menu</option>
			<option id="showWinPanel">Win panel</option>
			<option id="showScoreboard">Scoreboard</option>
			<option id="showFreezecam">Freezecam</option>
			<option id="showMainmenu">Main menu</option>
<!--			<option id="showTournamentSetup">Tournament Setup</option> -->
<!--			<option id="showSpectator">Spectators panel</option> -->
			<option id="showMiscLabels">Misc labels</option>
		</select>
	</div>
</div>

<div id="canv-region" class="region-center canvas-1">
	<div class="grid" id="grid"></div>
	<div class="hud-canvas">
		<div id="targetidwrapper"></div>
	</div>
	<div class="hud-canvas-classmenu" style="display:none;"></div>
	<div class="hud-canvas-teammenu" style="display:none;"></div>
	<div class="hud-canvas-winpanel" style="display:none;"></div>
	<div class="hud-canvas-scoreboard" style="display:none;"></div>
	<div class="hud-canvas-freezecam" style="display:none;"></div>
	<div class="hud-canvas-mainmenu" style="display:none;"></div>
<!--	<div class="hud-canvas-tournamentsetup" style="display:none;"></div> -->
<!--	<div class="hud-canvas-spectator" style="display:none;"></div> -->
	<div class="hud-canvas-misclabels" style="display:none;"></div>
</div>

<div id="loaderwrapper">
    <div id="loader">
        <div id="loaderlogo"></div>
    </div>
</div>

<div id="autosave">Autosaving...</div>

<div id="HUDuploaderHidden"></div>
<div id="fileuploader"></div>
</body>
</html>
