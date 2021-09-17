var colorArray = ["#006600", "#009900", "#6600ff", "#9966ff", "#996600", "#cc9900", "#0066ff", "#0099ff", "#666600", "#999900", "#660066", "#990099", "#006699", "#009999", "#993300", "#ff6600", "#cc0066", "#cc6666", "#0000cc", "#0066cc"];
var instruments = [];
var astroMap =  null;
var unknownStatsJSON = null;
var stereoHot = false;


function titleCase(str) {
  return str.replace(/\w\S*/g, function(txt) {return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();});
}


function numberWithCommas(x) {
  return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

//
function cleanStr(str) {
  return str.replace(/[^a-zA-Z0-9_]+/g ,'_');
};


function dateFormatToolTip(number) {
  var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct' , 'Nov', 'Dec'];
  return(months[Number(String(number).slice(4))] + ' ' + String(number).slice(0,4));
}


function showSolarSystem(target) {

  //seatch
  pilotSearch.clear();
  //status bar
  $('#searchBoxes').css('display', 'none');
  $('#solarSystemReturn').css('display', 'none');
  $('#mapNav').css('display', 'none');
  $('#constrainNav').css('display', 'none');
  $('#missionNav').css('display', 'none');
  $('#missionsText').html('Planetary Image Locator Tool <span class="smallText screen-only">&nbsp;explore NASA\'s largest raw spacecraft imagery archive</span>');
  $('#missionsIcon').css('display', 'none');
  $('#stereoNav').css('display', 'none');
  $('#tabs').css('background', 'none');
  $('#missionsTabImg').attr('src', 'images/missions.gif');
  //missions
  instruments = [];
  $('#target').attr("value",'');
  //map
  pilotMapClear();
  $('#mapTabImg').attr('src', 'images/globe.gif');
  //constraints
  pilotConstrain.clear();
  //news
  if (!pilotSearch.searchLoad && (initTarget == '')) {
    //newsCall=new pilotAJAX();
    //newsCall.loadNews();
    var homeHTML = '<div id="pilotNews">';
    homeHTML += '<br/><h3>Resources</h3><div class="pilotNewsItem"><ul><li><a target="_blank" href="doc/PILOT_Tutorial_lg_12.16.19.pdf" class="purpleText">Tutorial for New Users</a></li><li><a target="_blank" href="https://astrogeology.usgs.gov/maps/contact" class="purpleText">Contact</a></li><li><a target="_blank" href="index.php?view=faq" class="purpleText">FAQ</a></li><li><a target="_blank" href="https://github.com/USGS-Astrogeology/PILOT" class="purpleText">Source Code on GitHub</a></li></ul></div>';
    homeHTML += '<br/><br/><h3>News</h3><div class="pilotNewsItem">  The UPC database on which PILOT is based is currently being updated. During this time, product searches using PILOT may produce different results from other PDS search tools. Please refer back to this page for updates. In the meantime, consider searching for PDS products on the <a href="https://pds-imaging.jpl.nasa.gov/search/"><u>Image Atlas</u></a> or the <a href="http://ode.rsl.wustl.edu/"><u>Orbital Data Explorers</u></a>.</div>';
    homeHTML += '</div>';
    $('#upcCarousel').append(homeHTML);
  }

  //
  $('#solarSystemTab').html('');
  $('<div/>', {id: 'barChooserLeft'}).appendTo('#solarSystemTab');
  $('<div/>', {id: 'barChooserMid'}).appendTo('#solarSystemTab');
  $('<div/>', {id: 'barChooserRight'}).appendTo('#solarSystemTab');
  $('<div/>', {id: 'stereoHotspot'}).appendTo('#solarSystemTab');
  $('#stereoHotspot').addClass('hotspot');
  $('#stereoHotspot').click(function() {$('#solarSystemTab').fadeIn("slow",function(){
								      $('#solarSystemTab').css('background-image','url(images/apollo-cockpit2.png)');});
								    stereoHot=true;
								   });
  var targetName = '';
  var targetTotal = [];
  var targetBarArray = [];
  var st = statsJSON[0]['json_agg'];
  for (i in st) {
      //console.log(st[i]);
      currentTargetName = String(st[i]['displayname']).toLowerCase().replace(/\//g, '');
      targetNameCap = currentTargetName.charAt(0).toUpperCase() + currentTargetName.slice(1);
      if ((currentTargetName != null) && (targetName != currentTargetName)) {
	planetKey = String(st[i]['system']).toUpperCase();
	currentType = (planetKey == currentTargetName.toUpperCase()) ? 'planet' : 'moon';
	targetBar = '';
	targetBar += '<span class="targetTitle" onclick="pilotSearch.enable(\''+ currentTargetName+ '\');" >'+ targetNameCap + '</span>';
	targetBarTotal = '<span style="margin-right:5px;" class="upcSmallGray" id="total'+ currentTargetName + '"></span><br/>';
	if (targetBarArray[planetKey] == undefined) {targetBarArray[planetKey] = '';}
	if (currentType == 'planet') {
	  targetBar +=  targetBarTotal;
	  targetBarArray[planetKey] = '<span id="'+ currentTargetName + 'Bar" class="barPlanet"  >' + targetBar + '</span>' + targetBarArray[planetKey];
	} else {
	  targetBar += '<span>' + targetBarTotal + '</span>';
	  targetBarArray[planetKey] += '<span id="' + currentTargetName + 'Bar" class="barMoon" style="" ><!--<img style="float:left;margin-right:30px;" src="images/bar-moon-left.png"/><img style="float:right;" src="images/bar-moon-right.png"/>-->' + targetBar + '</span>';
	}
	targetName = currentTargetName;
      }
      if (targetTotal[currentTargetName] == undefined) {targetTotal[currentTargetName] = 0;}
      targetTotal[currentTargetName] = (Number(targetTotal[currentTargetName]) + Number(st[i]['target_count']));
  }

  var planetOrder = ['MERCURY','VENUS','EARTH','MARS','JUPITER','SATURN','SMALL BODIES','URANUS','NEPTUNE'];
  var  barCount = 0;
  var barDiv = '#barChooserLeft';
  for (var j=0; j< planetOrder.length; j++) {
    if (targetBarArray[planetOrder[j]] != undefined) {
      if (planetOrder[j] == 'SATURN') {
	  barDiv = '#barChooserMid';
      }
      if (planetOrder[j] == 'SMALL BODIES') {
	  barDiv = '#barChooserRight';
	  $(barDiv).append('<span id="SmallBodiesBar" class="barPlanet" ><span class="targetTitleCold">Small Bodies</span></span>');
      }
      $(barDiv).append(targetBarArray[planetOrder[j]]);
      barCount++;
    }
  }

  for (t in targetTotal) {
    $('#total' + t).html('&nbsp;' + targetTotal[t].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' images');
  }

  $('<span/>', {id: 'UnknownBar', 'class': 'barPlanet', html: '<span class="targetTitle" onclick="pilotSearch.enable(\'untargeted\');" >Untargeted Images</span>'}).appendTo($('#barChooserRight'));


};


function showConstrain(instrument) {

  $('#tabs').tabs('select', 3);
  var missions = [];
  var mCheck = [];
  var navHTML = '';
  var textHTML = '';
  var limitCall=new pilotAJAX();
  $('#missionList input:checked').each(function() {
    if ( ($(this).attr('id').slice(0,6) == 'mapped') && ($.inArray($(this).val(), mCheck) == -1) ){
      missions.push({id: $(this).val(), name: instruments[$(this).attr('id').slice(7)]['name']});
    }
    if ( ($(this).attr('id').slice(0,8) == 'unmapped') && ($.inArray($(this).val(), mCheck) == -1) ){
      missions.push({id: $(this).val(), name: instruments[$(this).attr('id').slice(9)]['name']});
    }
    mCheck.push($(this).val());
  });
  //create tabs
  if (!$('#constrainTabs').data("tabs")) {
    navHTML += '<ul>';
    for (var i=0; i< missions.length; i++) {
      navHTML += '<li id="constrain' + missions[i]['id'] + '"><a href="#iTab' + missions[i]['id'] + '" ><img title="' + instruments[missions[i]['id']]['missionTitle'] + '" src="images/' + instruments[missions[i]['id']]['mission'] + '-icon.png" class="missionIcon" />' + missions[i]['name'] + '</a></li>';
      textHTML += '<div class="iTab" id="iTab' + missions[i]['id'] + '"></div>';
    }
    navHTML += '</ul>';
    $('#constrainTabs').html(navHTML + textHTML);
    $('#constrainTabs').tabs();
    for (i=0; i< missions.length; i++) {
      pilotConstrain.show(missions[i]['id']);
    }
  } else {
    var cTabs = $('#constrainTabs').tabs();
    var cUl = cTabs.find("ul");
    //del old
    $('#constrainTabs .ui-tabs-nav a').each(function() {
					    var id = $(this).attr('href').substring(5);
					    if (!$('#mapped_' + id).is(':checked') && !$('#unmapped_' + id).is(':checked')) {
					      var index = $(this).parent().index();
					      $('#constrainTabs').tabs("remove", index);
					    }
					  });
    //add new
    for (var i=0; i< missions.length; i++) {
      if (!$('#iTab' + missions[i]['id']).length) {
	cTabs.find(".ui-tabs-nav").append('<li id="constrain' + missions[i]['id'] + '"><a href="#iTab' + missions[i]['id'] + '" ><img title="' + instruments[missions[i]['id']]['missionTitle'] + '" src="images/' + instruments[missions[i]['id']]['mission'] + '-icon.png" class="missionIcon" />' + missions[i]['name'] + '</a></li>');
	cTabs.append('<div class="iTab" id="iTab' + missions[i]['id'] + '"></div>');
	limitCall.limits(missions[i]['id']);
	cTabs.tabs("refresh");
	$('#constrainTabs').tabs("option", "active", 0);
      } else {
	pilotConstrain.errorPanel(missions[i]['id']);
      }
    }
  }
  //find hot
  if (instrument) {
    $('#constrainTabs .ui-tabs-nav a').each(function() {
					      if (Number(instrument) == Number($(this).attr('href').substring(5))) {
						var index = $(this).parent().index();
						$('#constrainTabs').tabs("option", "active", Number(index));
					      }
					    });
  }

};


function showMissions(target) {

  var panel='#missionsTab';
  var targetT = target.charAt(0).toUpperCase() + target.substr(1).toLowerCase();
  var currentTarget = '';
  var currentMission= '';
  var startYear, stopYear = '';
  var mission, missionClean, currentI, currentIClean, yearString = '';
  var iClass = 'instrumentWhite';
  var sparkSplit = 80;
  var spark1, spark2 = [];
  var sparkYear = [];
  var missionHTML = '<table id="missionTable">';
  var checkBoxHTML, footprintHTML = '';
  var mTotal = 0;

  $('#tabs').css('background', '#ffffff');
  if ((target != 'untargeted') && ($('#target').attr("value").toLowerCase() == target.toLowerCase())) {
    return;
  }
  pilotSearch.clear();
  $(panel).html('');
  $("#tabs").tabs('disable',2);
  $("#tabs").tabs('disable',3);
  //stereo
  $("#tabs").tabs('disable',4);
  $('#stereoNav').css('display', 'none');

  $('<span/>', {id: 'missionPanelTitle', html: 'Select one or more image sets. . .'}).appendTo(panel);
  $('<div/>', {id: 'missionList'}).appendTo(panel);
  $('#target').val(target);

  var missionList = [];
  if (target == 'untargeted') {
    if (unknownStatsJSON) {
      missionList = eval(unknownStatsJSON);;
    } else {
      statsCall=new pilotAJAX();
      statsCall.loadUnknownStats();
      return;
    }
  } else {
    missionList = missionStatsJSON[0]['json_agg'];
  }
  var iCount = 0;
  for (var i=0; i< missionList.length; i++) {
    currentTarget = missionList[i]['targetname'];
    yearString = '';
    if (currentTarget && (currentTarget.toLowerCase() == target)) {
      mission = (missionList[i]['mission']) ? missionList[i]['mission'] : 'Untargeted';
      missionClean = mission.replace(/\W/g, '');
      if (currentMission != mission) {
	if (missionList[i]['start_date']) {
	  startYear = missionList[i]['start_date'].slice(0,4);
	  stopYear = missionList[i]['stop_date'].slice(0,4);
	  yearString = '<span class="upcHighlightText">(' + startYear;
	  yearString += (startYear == stopYear) ? ')</span>' : ' - ' + stopYear + ')</span>';
	}
	missionHTML += '<tr style="height:3px;"><td colspan="10"></td></tr>';
	missionHTML += '<tr class="instrumentMain"><td id="' + missionClean + 'List" class="missionTitle" colspan="5" ><span style="" class="barMission"><img src="images/' + missionClean + '-icon.png" class="missionIcon" />' + mission +
	  '<span style="margin-left:10px;" >' + yearString + '</span><a target="_blank" href="' + missionLinks[missionList[i]['mission']] + '"><img src="images/infoLink.png" alt="PDS Mission Page" title="PDS Mission Page" class="infoLinkIcon" style="margin-top:3px;"  /></a></span></td></tr>';
	currentMission = mission;
      }
      currentI = (missionList[i]['displayname']) ? missionList[i]['displayname'] : 'Untargeted';
      currentId = missionList[i]['instrumentid'];
      //currentT = Number(missionList[i]['total']) - Number(missionList[i]['errors']);
      currentT = Number(missionList[i]['image_count']);
      currentE = Number(missionList[i]['errors']);
      currentIClean = cleanStr(currentI);
      instruments[currentId] = [];
      instruments[currentId]['name'] = currentI;
      instruments[currentId]['color'] = colorArray[iCount];
      instruments[currentId]['mission'] = missionClean;
      instruments[currentId]['missionTitle'] = mission;
      upcHTML = (currentT > 0) ? '&nbsp;<input type="checkbox" name="mapped_' + currentId + '" id="mapped_' + currentId + '" onclick="toggleConstrain(\'' + currentId + '\');pilotSearchButtonHandler();" value="' + currentId + '" /><label for="mapped_' + currentId + '" class="upcSmallGray" style="cursor:pointer;" title="Images have been SUCCESSFULLY mapped through camera processing." ><span id="num_mapped_' + currentId + '" class="purpleText"  data-number="' + currentT+ '">' + numberWithCommas(currentT) + '&nbsp;</span>mapped</label>' : '';
      errorHTML = (currentE > 0) ? '&nbsp;<input type="checkbox" name="unmapped_' + currentId + '" id="unmapped_' + currentId + '" onclick="toggleConstrain(\'' + currentId + '\');pilotSearchButtonHandler();" value="' + currentId + '" /><label for="unmapped_' + currentId + '" class="upcSmallGray" style="cursor:pointer;" title="Images have FAILED during camera processing." ><span id="num_mapped_' + currentId + '" class="orangeText" data-number="' + currentE + '">' + numberWithCommas(currentE) + '&nbsp;</span>unmapped</label>' : '';
      missionHTML += '<tr id="instrumentRow' + currentId + '" ><td><span style="font-size:1.2em;line-height:.9em;font-weight:bold;color:' + instruments[currentId]['color'] + '">&bull;</span><span class="smallText">&nbsp;' + currentI + '&nbsp;</span></td>' +
      '<td>' + upcHTML + '</td>' +
      '<td>' + errorHTML + '</td>' +
	'<td>';
	missionHTML += (target != 'untargeted') ? '<div title="Stats" class="pilotLog" id="missionStats' + currentId + '" onclick="pilotConstrain.missionStats(' +currentId +',\''+ currentI+'\');"/></div>' : '';
	//missionHTML += '<div title="Advanced Search Constraints" class="pilotWrenchDisabled" id="wrench' + currentId + '" /></div>';
	missionHTML += '</td></tr>';
      iCount++;
    }
  }
  missionHTML += '</table>';
  $('#missionList').append(missionHTML);

  showHelpPanel();

}


function showHelpPanel(limitAlert) {

  //right
  var e = '#upcCarousel';
  $('<div/>', {id: 'pilotHelpMessage'}).appendTo(e);
  $('#pilotHelpMessage').html('<span style="font-size: 1.2em;"><span class="orangeText" style="font-weight:bold;">HOW TO SEARCH FOR IMAGES</span><br/><ul><li>Select one or more image sets (on the <img src="images/missions.gif" /> <b>Missions</b> tab)<br/><br/></li><li>The <b>Total</b> will show up above. Search results will show up here unless your <b>Total</b> is greater than ' + pilotSearch.totalMax + ' images. <span id="overLimitAlert">Restrict your search by using the steps below:</span><br/><br/></li><li><span class="orangeText"><b>Restrict by area</b></span>: select <img src="images/globe.gif" /> <b>Map</b> tab and create a bounding box using one of the following methods:<br/><ul><li>Click <img src="images/polygon.png" /> button, draw a polygon on the map, double click to complete bounding box</li><li>Enter max and min latitudes and longitudes</li><li>Use <b>feature finder</b> to set the bounds to a geologic feature</li></ul><br/></li><li><span class="orangeText"><b>Restrict by metadata</b></span>: select <img src="images/wrench.gif" /> <b>Advanced</b> tab and set ranges for mission dates and/or photometric keywords<br/><br/></ul></span>');

  if (limitAlert) {
    $('#overLimitAlert').css('background','yellow');
  }
}


function toggleConstrain(i) {

  var useMap = ($('#target').val().indexOf('untargeted') == -1);

  if ($('#missionList input:checked').length < 1) {
    $("#tabs").tabs('disable',2);
    $("#tabs").tabs('disable',3);
    $("#tabs").tabs('disable',4);
    $('#missionsTabImg').attr('src', 'images/missions.gif');
  } else {
    if (useMap) {$("#tabs").tabs('enable',2);}
    $("#tabs").tabs('enable',3);
    $('#missionsTabImg').attr('src', 'images/missions-set.gif');
  }
  if ($('#mapped_' + i).is(':checked') || $('#unmapped_' + i).is(':checked')) {
    $('#wrench' + i).removeClass('pilotWrenchDisabled');
    $('#wrench' + i).addClass('pilotWrench');
    $('#wrench' + i).attr('title','Advanced Search');
    $('#wrench' + i).click(function() {showConstrain(i);});
  } else {
    $('#wrench' + i).addClass('pilotWrenchDisabled');
    $('#wrench' + i).removeClass('pilotWrench');
    $('#wrench' + i).attr('title','');
    $('#wrench' + i).unbind();
  }

  //stereo tab activation
  if (stereoHot) {
    $('#stereoNav').css('display', 'block');
  } else {
    $("#tabs").tabs('disable',4);
    $('#stereoNav').css('display', 'none');
  }
}

function showMap() {

  if (($('#map').length == 0) || !astroWebMapsSemaphore) {
    setTimeout("showMap()",3000);
    return;
  }
  $('#mapLoader').remove();

  var target = $('#target').val();

  if (astroMap) {
    if (astroMap.target == target) {
      //refresh
      return;
    }
    astroMap.destroy();
    $('#map').html('');
    $('#astroConsoleTargetInfo').html('');
    $('#astroConsoleProjectionButtons').html('');
    $('#astroConsoleLonLatSelects').html('');
  }

  var currentProjection = 'cylindrical';

  var consoleSettings = {
    astroWebMapsBasePath: 'http://astrowebmaps.wr.usgs.gov/webmapatlas/',
    target: target,
    projButtons: true,
    lonLatSelects: true,
    mouseLonLat: true,
    renderCounter: false,
    footprintInfo: false
  };

  var mapSettings = {
    mapDiv: 'map',
    target: target,
    projection: currentProjection,
    showNomenclature: false,
    datelineWrap: true,
    vectorLayerName: 'Footprints',
    defaultZoomLevel: 2,
    defaultCenterLat: 0,
    defaultCenterLon: 180,
    //imagePath: 'AstroWebMaps/images/'
    imagePath: '/astrowebmaps/images/',
    projectionSwitchTrigger: pilotProjectionSwitchTrigger
  };

  var controlSettings = {
    zoomBar: true,
    layerSwitcher: true,
    graticule: false,
    featureSearch: false,
    scaleLine: true,
    overviewMap: false,
    mousePosition: true,
    zoomButton: true,
    boundingBoxDrawer: true,
    downloadButton: true,
    homeButton: true,
    selectButton: true,
    navButton: true,
    defaultControl: "select",
    decimalPlaces: 2,
    selectHandler: pilotSearch.mapSelect,
    boundingBoxDrawHandler: pilotSearchBoundingBoxHandler
    //  unselectHandler: pilotSearch.mapUnselect
  };

  astroMap = new AstroMap(mapSettings, controlSettings, consoleSettings, null);
  featureCall=new pilotAJAX();
  featureCall.loadFeatureTypes(target);
}


function showStereo() {

  if (!pilotStereo.open) {
    pilotStereo.match();
  }
}


//
function  pilotPanelClear() {

  //missions
  instruments = [];
  //map
  pilotMapClear();
  //constraints
  $('#sliderDiv').html('');

}


//
function  pilotMapClear() {
  if (astroMap && astroMap.boundingBoxDrawer) {
    astroMap.boundingBoxDrawer.removeAndUnstoreAll();
    pilotSearch.unRenderAll();
    if (pilotStereo.open) {
      pilotStereo.unRenderAll();
    }
  }
};


//
function pilotProjectionSwitchTrigger() {

  if (pilotSearch.astroVector) {
    pilotSearch.astroVector.updateLayer(astroMap.vectorSource);
  }
  if (pilotStereo.open) {
    pilotStereo.astroVector.updateLayer(astroMap.vectorSource);
  }
};