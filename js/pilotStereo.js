
//constructor
function PilotStereo() {

  this.originalHeight = 0;
  this.totalLimit = 200;
  this.open=false;
  this.matches = [];
  this.intersectsAll = [];
  this.intersects = [];
  this.trashed = [];
  this.container = '#stereoMatcherResults';
  this.formId = '#upcSearchForm';
  this.hotId1 = null;
  this.hotId2 = null;
  this.lastHighlight = null;
  this.matchOnLoad = false;
  this.keepSliders = false;
  this.useSolarSeparation = false;

  this.astroVector = null;
  this.color = '#ffd700';
  this.pilotAjax=new pilotAJAX();
};


//
PilotStereo.prototype.autoFire = function() {
  pilotStereo.match();
};

//
PilotStereo.prototype.check = function(i, upc1, upc2) {

  if($('#pilotStereoSelect' + i + ':checked').length > 0) {
    if (! $('#pilotSelect' + upc1).prop('checked')) {
      $('#pilotSelect' + upc1).click();
    }
    if (! $('#pilotSelect' + upc2).prop('checked')) {
      $('#pilotSelect' + upc2).click();
    }
  } else {
    //check other stereo selects
    var upc1checks = 0, upc2checks = 0;
    for (var j= this.intersects.lengthx2 -1; j >= 0; j--) {
      if ($('#pilotStereoSelect' + j).prop('checked')) {
	if ((this.intersects[j].upcida == upc1) || (this.intersects[j].upcidb == upc1)) {
	  upc1checks++;
	}
	if ((this.intersects[j].upcida == upc2) || (this.intersects[j].upcidb == upc2)) {
	  upc2checks++;
	}
      }
    }
    if (upc1checks == 0) {
      $('#pilotSelect' + upc1).click();
    }
    if (upc2checks == 0) {
      $('#pilotSelect' + upc2).click();
    }
  }

};


//
PilotStereo.prototype.clean = function() {
  this.unRenderAll();
  $('#stereoNumber').val('');
  $('#stereoMatches').html('');
  this.matchesAll = [];
  this.intersects = [];
  this.lastHighlight = null;
  this.matchOnLoad = false;
  this.astroVector = null;
};

//
PilotStereo.prototype.clearSliders = function() {
  this.trashed = [];
  this.sliders();
  this.autoFire();
};


//
PilotStereo.prototype.close = function() {
  if (!this.keepSliders) {
    this.trashed = [];
    $('#stereoTab').html('');
    if ($('#tabs').tabs('option','active') == 4) {
      $('#tabs').tabs('option','active',1);
    }
  }
  $('#bottomRight').css('display','none');
  $('#pilotInfoContainer').height(this.originalHeight);
  $('#pilotInfo').height(this.originalHeight);
  $('#farRight').height(this.originalHeight);
  $('#nearRight').height(this.originalHeight);
  $('#bottomRight').height(0);
  this.clean();
  this.open=false;
};

//
PilotStereo.prototype.display = function() {

  if (!this.open) {
    this.originalHeight = $('#farRight').height();
    var halfHeight = (this.originalHeight/2);
    $('#bottomRight').css('display','block');
    $('#pilotInfoContainer').height(halfHeight);
    $('#pilotInfo').height(halfHeight);
    $('#farRight').animate({height: halfHeight}, 1000, function(){});
    $('#nearRight').animate({height: halfHeight}, 1000, function(){});
    $('#bottomRight').animate({height: halfHeight}, 1000, function(){});
    $('#stereoMatches').animate({height: halfHeight - 52}, 1000, function(){});
  }
  this.open=true;

  $('#stereoNumber').val(this.intersects.length);

  var iStyle = 'intersectGray';
  var stereoHTML = '';
  for (var i=0; i < this.intersects.length; i++) {
    stereoHTML = '<div class="' + iStyle + '"><div style="float:left;font-weight:bold;padding: 5px 15px 5px 5px;">' + (i+1) +
      ' <input alt="Select" type="checkbox" class="pilotStereoCheckbox" id="pilotStereoSelect' + i + '" onclick=\'pilotStereo.check(' + i + ',"' + this.intersects[i]['upcida'] + '","' + this.intersects[i]['upcidb'] + '");\' />' +
    ' </div><div style="float:left" id="stereoPair' + i + '"><small>';
    stereoHTML += '<span class="stereoLink ' + this.intersects[i]['upcida'] + 'Link" onclick="pilotStereo.select(String(' + this.intersects[i]['keya'] + '),' + i + ');">' + this.matches[this.intersects[i]['keya']].displayname + ': ' + this.intersects[i]['productida']
      + ' <span class="stereoNote">' + Number(this.matches[this.intersects[i]['keya']].surfacearea).toFixed(2) + ' km&sup2; | </span>'
      + ' <span class="stereoNote">' + Number(this.matches[this.intersects[i]['keya']].meangroundresolution).toFixed(2) + ' mpp</span>'
      + '</span><br/>';
    stereoHTML += '<span class="stereoLink ' + this.intersects[i]['upcidb'] + 'Link" onclick="pilotStereo.select(String(' + this.intersects[i]['keyb'] + '),' + i + ');">' + this.matches[this.intersects[i]['keyb']].displayname + ': ' + this.intersects[i]['productidb']
      + ' <span class="stereoNote">' + Number(this.matches[this.intersects[i]['keyb']].surfacearea).toFixed(2) + ' km&sup2; | </span>'
      + ' <span class="stereoNote">' + Number(this.matches[this.intersects[i]['keyb']].meangroundresolution).toFixed(2) + ' mpp</span>'
    + '</span>';
    stereoHTML += '</small></div>';
    var solarSeparation = (typeof this.intersects[i]['solarseparationangle'] != 'undefined') ? ' Solar Separation Angle: ' + this.intersects[i]['solarseparationangle'].toFixed(2) : '';
    stereoHTML += '<div style="float:right;cursor:pointer;" title="Solar Azimuth Difference: ' + this.intersects[i]['solarazimuthdifference'] + solarSeparation + ' Incidence Angle Difference: ' + this.intersects[i]['incidenceangledifference'] + ' Centroid: ' + this.intersects[i]['centroid'] + ' Base Height Ratio: ' + Number(this.intersects[i]['baseheightratio']).toFixed(1) + ' Shadow Difference: ' + Number(this.intersects[i]['shadowdifference']).toFixed(1) + ' Expected Precision: ' + Number(this.intersects[i]['expectedprecision']).toFixed(1) + '" ><div style="float:left;">' + this.intersects[i]['area'].toFixed(2) + ' km&sup2; <br>'
      + '<span class="stereoNote">convergence ctr&nbsp; ' + Number(this.intersects[i]['convergenceangle']).toFixed(1) + '&deg;</span></div>'
      //+ ' st: ' + Number(this.intersects[i]['shadowtipdistance']).toFixed(0) + ' km</span></div>'
		  + '<div style="float:right;margin:15px 2px 0px 2px;"><img id="stereoTrashButton' + i  + '" src="images/trashcan.gif" title="cull" class="stereoIcon" onclick="pilotStereo.trashPair(\'' + this.intersects[i]['orgKey'] + '\');" /></div>'
		  + '<div style="float:right;margin:15px 2px 0px 2px;"><img id="stereoMapButton' + i  + '" src="images/globe.gif" title="map" class="stereoIcon" onclick="pilotStereo.map(\'' + i + '\');" /></div>'
		  + '</div></div>';
    $('#stereoMatches').append(stereoHTML);
    iStyle = (iStyle == 'intersectGray') ? 'intersectWhite' : 'intersectGray';
  }

  $('#stereoMatcherButton').attr("disabled", "disabled");
};

//
PilotStereo.prototype.download = function(anchor) {

  var csvHeader = "data:application/csv;charset=utf-8,";
  var csvContent = "INSTRUMENT 1, PRODUCT ID 1, SOLAR AZIMUTH 1, SOLAR LONGITUDE 1, INCIDENCE ANGLE 1 (MIN), INSTRUMENT 2, PRODUCT ID 2, SOLAR AZIMUTH 2, SOLAR LONGITUDE 2, INCIDENCE ANGLE 2 (MIN), CONVERENGE ANGLE (CENTER), SHADOW TIP DIFF, INTERSECT AREA (KMxKM), CENTROID (WKT), INTERSECT (WKT)\n";
  for (var j= this.intersects.length -1; j >= 0; j--) {
    i = this.intersects[j];
    csvContent += this.matches[i['keya']].displayname + ',' + i.productida + ',' + this.matches[i['keya']].subsolargroundazimuth + ',' + this.matches[i['keya']].solarlongitude + ',' + this.matches[i['keya']].minimumincidenceangle + ',' + this.matches[i['keyb']].displayname + ',' + i.productidb + ',' + this.matches[i['keyb']].subsolargroundazimuth + ',' + this.matches[i['keyb']].solarlongitude + ',' + this.matches[i['keyb']].minimumincidenceangle + ',' + i.convergenceangle.toFixed(1) + ',' + i.shadowdifference.toFixed(1) + ',' + i.area.toFixed(2) + ',"' + i.centroid + '","' + i.intersect + '"' + "\n";
  }
  var csvData = csvHeader + encodeURI(csvContent);

  $(anchor).attr({
		   'download': "stereoMatches.csv",
		   'href': csvData,
		   'target': '_blank'
		 });
};

//
PilotStereo.prototype.highlight = function(id, intersect) {

  $('.stereoLink').css('color', '#000000');
  $('.' + id + 'Link').css('color', '#6600ff');
};


//
PilotStereo.prototype.map = function(key) {
  if (this.intersects[key]['index'] != null) {
    this.unRender(key);
  } else {
    this.render(key);
  }
};


//
PilotStereo.prototype.match = function(clean) {

  if (this.open && !clean) {
    //slider call - already did server-side work
    this.postProcess();
    return;
  }

  this.clean();
  totalN = Number($('#totalNumber').val());

  //make tab
  //$('#tabs').tabs('option','active',4);

  if (totalN == 0) {
    $('#stereoTab').html('<span class="orangeText"><br/><br/>Please retrieve search results before stereo matching!</span>');
    return;
  }
  if (totalN == 1) {
    $('#stereoTab').html('<span class="orangeText"><br/><br/>Stereo Matching requires more than one image!</span>');
    return;
  }
  if (totalN > this.totalLimit) {
    $('#stereoTab').html('<span class="orangeText"><br/><br/>Stereo Matching is limited to ' + this.totalLimit + ' images!</span>');
    return;
  }
  if (totalN > pilotSearch.step) {
    //load all
    this.matchOnLoad = true;
    pilotSearch.stopScroll();
    pilotSearch.search();
    return;
  }

  //process search results
  this.process();
};


//server side process
PilotStereo.prototype.process = function() {

  rKeys = '';
  results = pilotSearch.imageArray;
  for(var key in results) {
    rKeys += (rKeys == '') ? '' : ',';
    rKeys += key;
  }
  //ajax call
  this.pilotAjax.stereoProcess(rKeys, $('#stereoOrder').val());
};


PilotStereo.prototype.postProcess = function() {

  pilotLockout.on('stereoPostProcess');
  this.clean();
  setTimeout(function(){pilotStereo._postProcess();},100);
};


PilotStereo.prototype._postProcess = function() {

  //grab sliders
  var sliderParams = $(this.formId + " :input[name^=stereo]").serializeArray();
  var filter;
  var keya, keyb;
  var x1, x2, y1, y2;
  this.intersects = this.intersectsAll.slice();

  //remove trashed
  for (var k= this.intersects.length -1; k >= 0; k--) {
    if ($.inArray(this.intersects[k]['orgKey'], this.trashed) !== -1) {
      this.intersects.splice(k,1);
      continue;
    }
  }

  for (var j= this.intersects.length -1; j >= 0; j--) {

    keya = this.intersects[j].keya;
    keyb = this.intersects[j].keyb;
    for (var i=0; i < sliderParams.length; i++) {

      filter = false;
      switch (sliderParams[i].name) {
      case 'stereo__convergenceangle_LT':
	filter = (Number(this.intersects[j].convergenceangle) >= Number(sliderParams[i].value));
	break;
      case 'stereo__convergenceangle_GT':
	filter = (Number(this.intersects[j].convergenceangle) <= Number(sliderParams[i].value));
	break;
      case 'stereo__shadowdiff_LT':
	filter = (Number(this.intersects[j].shadowdifference) >= Number(sliderParams[i].value));
	break;
      case 'stereo__azimuthdiff_LT':
	filter = (Math.abs(Number(this.matches[keya].subsolargroundazimuth) - Number(this.matches[keyb].subsolargroundazimuth)) >= Number(sliderParams[i].value));
	break;
      case 'stereo__resolutiondiff_LT':
	filter = (((this.matches[keya].meangroundresolution/this.matches[keyb].meangroundresolution) >= sliderParams[i].value) || ((this.matches[keyb].meangroundresolution/this.matches[keya].meangroundresolution) >= sliderParams[i].value));
	break;
      case 'stereo__incidencediff_LT':
	filter = (Math.abs(Number(this.matches[keya].minimumincidenceangle) - Number(this.matches[keyb].minimumincidenceangle)) >= Number(sliderParams[i].value));
	break;
      case 'stereo__incidenceangle_LT':
	filter = ((Number(this.matches[keya].minimumincidenceangle) >= Number(sliderParams[i].value)) || (Number(this.matches[keyb].minimumincidenceangle) >= Number(sliderParams[i].value)));
	break;
      case 'stereo__incidenceangle_GT':
	filter = ((Number(this.matches[keya].minimumincidenceangle) < Number(sliderParams[i].value)) || (Number(this.matches[keyb].minimumincidenceangle) < Number(sliderParams[i].value)));
	break;
      case 'stereo__intersectarea_LT':
	filter = (Number(this.intersects[j].area) >= Number(sliderParams[i].value));
	break;
      case 'stereo__intersectarea_GT':
	filter = (Number(this.intersects[j].area) <= Number(sliderParams[i].value));
	break;
      case 'stereo__baseheightratio_LT':
	filter = (Number(this.intersects[j].baseheightratio) >= Number(sliderParams[i].value));
	break;
      case 'stereo__baseheightratio_GT':
	filter = (Number(this.intersects[j].baseheightratio) <= Number(sliderParams[i].value));
	break;
      case 'stereo__solarlongitude_LT':
	filter = ((Number(this.matches[keya].solarlongitude) >= Number(sliderParams[i].value)) || (Number(this.matches[keyb].solarlongitude) >= Number(sliderParams[i].value)));
	break;
      case 'stereo__solarlongitude_GT':
	filter = ((Number(this.matches[keya].solarlongitude) < Number(sliderParams[i].value)) || (Number(this.matches[keyb].solarlongitude) < Number(sliderParams[i].value)));
	break;
      case 'stereo__solarlongitudediff_LT':
	filter = (Math.abs(Number(this.matches[keya].solarlongitude) - Number(this.matches[keyb].solarlongitude)) >= Number(sliderParams[i].value));
	break;
      case 'stereo__solarseparationangle_LT':
	filter = (Number(this.intersects[j].solarseparationangle) >= Number(sliderParams[i].value));
	break;
      case 'stereo__solarseparationangle_GT':
	filter = (Number(this.intersects[j].solarseparationangle) <= Number(sliderParams[i].value));
	break;
      }

      if (filter) {
	this.intersects.splice(j,1);
	break;
      }

    }//for

  }
  this.display();
  pilotLockout.off('stereoPostProcess');
};


//
PilotStereo.prototype.processComplete = function(data) {

  this.matches = data[0];
  this.intersects = data[1];
  this.intersectsAll = this.intersects.slice(0);
  this.limits = data[2];
  this.useSolarSeparation = (typeof this.limits['solarseparationangle'] != 'undefined');

  this.display();
  this.sliders();
  this.postProcess();
};

//
PilotStereo.prototype.render = function(key) {

  $('#tabs').tabs('option','active',2);
  if (!this.astroVector) {
    this.astroVector = new AstroVector(astroMap, astroMap.vectorSource);
  }
  $('#stereoMapButton' + key).attr('src', 'images/globe-hot.gif');
  if (this.intersects[key]['index'] == null) {
    astroMap.vectorLayer.setOpacity(.8);
    this.intersects[key]['index'] = this.astroVector.drawAndStore(String(this.intersects[key]['intersect']), 's' + key, this.color, 'footprint', false, false).index;
  }

};

//
PilotStereo.prototype.renderAll = function() {

  var total = Number($('#stereoNumber').val());
  var limit = 500;

  if (total > limit) {
    alert('Cannot DRAW ALL for intersects greater than ' + limit + '!');
  } else {
    for(var iKey in this.intersects) {
	this.render(iKey);
    }
  }
};


//
PilotStereo.prototype.reorder = function() {
  this.keepSliders=true;
  pilotStereo.close();
  pilotStereo.process();
};


PilotStereo.prototype.select = function(key, intersect) {

  if (key) {
    var id = String(this.matches[key].upcid);
    pilotStereo.highlight(id, intersect);
    pilotSearch.image(id);
  }

  if (intersect != pilotStereo.lastHighlight) {
    if (pilotStereo.lastHighlight != null) {
      $('#stereoPair' + pilotStereo.lastHighlight).css('background','none');
    }
    $('#stereoPair' + intersect).css("background", '#ffd700');
    pilotStereo.lastHighlight = intersect;
  }
};



PilotStereo.prototype.setSliders = function() {

  $("#stereo__convergenceangle").slider("values", [7,40]);
  $("#stereo__convergenceangleAmountMin").val('7');
  $("#stereo__convergenceangleAmountMin").attr('name', "stereo__convergenceangle_GT");
  $("#stereo__convergenceangleAmountMax").val('40');
  $("#stereo__convergenceangleAmountMax").attr('name', "stereo__convergenceangle_LT");

  $("#stereo__baseheightratio").slider("values", [0.3,0.6]);
  $("#stereo__baseheightratioAmountMin").val('0.3');
  $("#stereo__baseheightratioAmountMin").attr('name', "stereo__baseheightratio_GT");
  $("#stereo__baseheightratioAmountMax").val('0.6');
  $("#stereo__baseheightratioAmountMax").attr('name', "stereo__baseheightratio_LT");

  $("#stereo__incidenceangle").slider("values", [30,65]);
  $("#stereo__incidenceangleAmountMin").val('30');
  $("#stereo__incidenceangleAmountMin").attr('name', "stereo__incidenceangle_GT");
  $("#stereo__incidenceangleAmountMax").val('65');
  $("#stereo__incidenceangleAmountMax").attr('name', "stereo__incidenceangle_LT");

  $("#stereo__incidencediff").slider("value", 50);
  $("#stereo__incidencediffAmountMax").val('50');
  $("#stereo__incidencediffAmountMax").attr('name', "stereo__incidencediff_LT");

  $("#stereo__resolutiondiff").slider("value", 3);
  $("#stereo__resolutiondiffAmountMax").val('3');
  $("#stereo__resolutiondiffAmountMax").attr('name', "stereo__resolutiondiff_LT");

  $("#stereo__shadowdiff").slider("value", 2.6);
  $("#stereo__shadowdiffAmountMax").val('2.6');
  $("#stereo__shadowdiffAmountMax").attr('name', "stereo__shadowdiff_LT");

  $("#stereo__azimuthdiff").slider("value", 20);
  $("#stereo__azimuthdiffAmountMax").val('20');
  $("#stereo__azimuthdiffAmountMax").attr('name', "stereo__azimuthdiff_LT");

  $("#stereo__solarlongitudediff").slider("value", 30);
  $("#stereo__solarlongitudediffAmountMax").val('30');
  $("#stereo__solarlongitudediffAmountMax").attr('name', "stereo__solarlongitudediff_LT");

  $("#stereo__solarseparationangle").slider("values", [0,50]);
  $("#stereo__solarseparationangleAmountMin").val('0');
  $("#stereo__solarseparationangleAmountMin").attr('name', "stereo__solarseparationangle_GT");
  $("#stereo__solarseparationangleAmountMax").val('50');
  $("#stereo__solarseparationangleAmountMax").attr('name', "stereo__solarseparationangle_LT");

  this.postProcess();

};



PilotStereo.prototype.sliders = function() {

  if (this.keepSliders) {
    this.keepSliders= false;
    return;
  }

  var e = '#stereoTab';
  $('#stereoTab').html('');
  var ed = $('<div/>', {id: 'stereoSliders'}).appendTo(e);
  var eL = $('<div/>', {id: 'stereoSlidersL', "class": 'stereoSlideLeft'}).appendTo(ed);
  var eR = $('<div/>', {id: 'stereoSlidersR', "class": 'stereoSlideRight'}).appendTo(ed);

  var id = 'stereo'; //instrument id does not matter
  var histogram = false;
  var sliders = [
		 {typename: 'convergenceangle', displayname: 'Convergence Angle', maximum: 179, minimum: 0},
		 {typename: 'baseheightratio', displayname: 'Base Height Ratio', maximum: 0, minimum: 0, step: 0.1},
		 {typename: 'incidenceangle', displayname: 'Incidence Angles', maximum: 89, minimum: 0},
		 {typename: 'incidencediff', displayname: 'Incidence Angle Difference Maximum', maximum: 89, minimum: 0, fixed: true},
    		 {typename: 'intersectarea', displayname: 'Intersect Area (km&sup2;)', maximum: this.limits['area']['max'], minimum: 0},
		 {typename: 'resolutiondiff', displayname: 'Resolution Difference Maximum (multiplier)', maximum: 4, minimum: 1, fixed: true},
		 {typename: 'shadowdiff', displayname: 'Shadow Tip Difference Maximum', maximum: 9, minimum: 0, step: 0.1, fixed: true},
    		 {typename: 'azimuthdiff', displayname: 'Solar Azimuth Difference Maximum', maximum: 89, minimum: 0, fixed: true},
		 {typename: 'solarlongitude', displayname: 'Solar Longitudes', maximum: 359, minimum: 0},
        	 {typename: 'solarlongitudediff', displayname: 'Solar Longitude Difference Maximum', maximum: 359, minimum: 0, fixed: true}
  ];

  if (this.useSolarSeparation) {
    sliders.push({typename: 'solarseparationangle', displayname: 'Solar Separation Angle', maximum: 179, minimum: 0});
  }

  for (var j=0; j < sliders.length; j++) {
    pilotConstrain.makeSlider(eL, id, sliders[j], histogram, pilotStereo.autoFire);
  }

  $('<span/>', {html: '<span class="purpleText">The PILOT Stereo Matcher</span><br/><span class="medText"><ul><li>Matching is restricted to search results under 200 images.</li><li>The panel to the lower right represents overlapping images and possible stereo matches.</li><li>Use the sliders and input boxes to the left to cull the result set.</li><li>One slider may limit another slider (e.g. base height ratio is a function of the convergence angle, emission angle is used to compute convergence angle, etc.)</li><li>Derived values (convergence angle, shadow tip difference, etc.) are taken from the center of the pair of images and may not reflect the exact values of the intersect. If more exact information is necessary, please download images and perform further processing.</li><li>Culling a set of matches greater than 500 pairs may cause slowness in the browser.</li><li>Please use the <i>support</i> link in the lower left corner (the ISIS forum) to report problems, questions or suggestions.</li><li><a href="http://www.hou.usra.edu/meetings/lpsc2015/pdf/1074.pdf" target="_blank"><span class="orangeText">Background </span><span class="smallText">(LPSC 2015 Abs #1074)</span></a></li><li><a href="http://www.hou.usra.edu/meetings/lpsc2015/pdf/2703.pdf" target="_blank"><span class="orangeText">Recommendations </span><span class="smallText">(LPSC 2015 Abs #2703)</span></a></li></ul></span>', "class": ''}).appendTo(eR);
  $('<input/>', {type: 'button', value: 'Clear Culling', id: 'stereoClearButton', "class": 'advClearButton'}).appendTo(eR);
  $('#stereoClearButton').click(function() {pilotStereo.clearSliders();});
  $('<input/>', {type: 'button', value: 'Suggested First Cull', id: 'stereoSuggestButton', "class": 'advClearButton'}).appendTo(eR);
  $('#stereoSuggestButton').click(function() {pilotStereo.setSliders();});


};


//
PilotStereo.prototype.trashPair = function(key) {
    this.trashed.push(Number(key));
    this.postProcess();
};


//
PilotStereo.prototype.unRender = function(key) {
  this.astroVector.removeAndUnstore(this.intersects[key]['index']);
  this.intersects[key]['index'] = null;
  $('#stereoMapButton' + key).attr('src', 'images/globe.gif');
};

PilotStereo.prototype.unRenderAll = function() {

    for(var iKey in this.intersects) {
      if (this.intersects[iKey]['index'] != null) {
	  this.unRender(iKey);
	}
    }
};


var pilotStereo = new PilotStereo();
