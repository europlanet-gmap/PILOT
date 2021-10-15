
//handlers (out of scope)
var pilotSearchButtonHandler = function() {
  pilotSearch.search(true);
};

var pilotSearchBoundingBoxHandler = function() {

  bb = $('#astroBBWKT').val();
  if (bb == '') {
    setTimeout(function(){pilotSearchBoundingBoxHandler();},100);
    return;
  }
  pilotConstrain.mapSearch();
};



//constructor
function PilotSearch() {

  this.basePath = location.href.slice(0,location.href.lastIndexOf('/'));
  this.imageDir = this.basePath+'/images/';
  this.downloadIcon = this.imageDir + 'download.gif';
  this.imageIcon = this.imageDir + 'imageSmall.gif';
  this.infoIcon = this.imageDir + 'infoIcon.gif';
  this.labelIcon = this.imageDir + 'labelDownload.png';
  this.tabImgsSet = {'constrain':'images/wrench-set.gif','map':'images/globe-set.gif','missions':'images/missions-set.gif'};
  this.footprintImagePrefix = this.imageDir + 'glowBox_';
  this.badGeometry = 'POINT(361 0)';
  this.totalMax = 20000;
  this.container = 'upcCarousel';
  this.containerClass = '';

  this.pilotAjax=new pilotAJAX();
  this.maxQueryDownload = 100;
  this.imageArray = []; //sparse array, upcid index
  this.hotId = null;
  this.step = null;
  this.renderLoads = false;
  this.infoLoad = false;
  this.searchLoad = false;
  this.selectAllHit = false;

  this.astroVector = null;
  this.lastChecked = null;
};


PilotSearch.prototype.bigImage = function(id, url) {
  this.pilotAjax.bigImage(id, url);
  this.render(id);
  this.highlightImage(id);
  this.highlightFootprint(id);
  this.highlightThumb(id);
};


PilotSearch.prototype.check = function(e) {

  id = e.target.id;
  var upcid = id.substring(11);
  if($('#pilotSelect' + upcid + ':checked').length > 0) {

    if (e.shiftKey) {
      var $chkboxes = $('.pilotSearchCheckbox');
      var start = $chkboxes.index($('#' + id));
      var end = $chkboxes.index(pilotSearch.lastChecked);
      $chkboxes.slice(Math.min(start, end) +1, Math.max(start, end)).each(function() {
									    if (!$(this).prop('checked')) {
									      this.click();
									      }
									   });
    }

    pilotSearch.lastChecked = $('#' + id);
    this.select(upcid);
  } else {
    this.unselect(upcid);
  }


};


PilotSearch.prototype.cleanId = function(oldId) {
  var newId = String(oldId).replace(/[^a-zA-Z0-9_]+/g ,'_');
  return(newId);
};


PilotSearch.prototype.clear = function() {
  if (pilotStereo.open) {
    pilotStereo.close();
    $("#tabs").tabs('disable',4);
  }
  this.stopScroll();
  e = '#upcCarousel';
  $(e).html('');
  this.closeInfo();
  $('#totalNumber').val('');
  $('#selectNumber').val('');
  $('#panelGroupBy').attr("disabled","disabled");
  this.imageArray = [];
  this.selectAllHit = false;
  this.hotId = null;
  this.step = null;
  if (this.astroVector) {
    this.astroVector.removeAndUnstoreAll();
    this.astroVector = null;
  }
};


PilotSearch.prototype.closeInfo = function() {
  $('#farRight').css('width','0px');
  $('#nearRight').css('width','100%');
  $('#farRight').css('display','none');
};


PilotSearch.prototype.downloadSingle = function(id, url) {
  var iframe;
  iframe = document.getElementById("hiddenDownloader");
  if (iframe === null) {
	  iframe = document.createElement('iframe');
	  iframe.id = "hiddenDownloader";
	  iframe.style.visibility = 'hidden';
	  document.body.appendChild(iframe);
  }
  iframe.src = url;
};


PilotSearch.prototype.downloadSelectForm = function(id, url) {

  var selected = Number($('#selectNumber').val());
  if ( selected > 0 ) {
    $('#nearRight').animate({width: '60%'}, 1000, function(){});
    $('#farRight').css('display','block');
    $('#farRight').animate({width:'40%'}, 1000, function(){});
    var close = '<span class="closeInfo" onclick="pilotSearch.closeInfo();" >x</span>';
    //form
    var downloadHTML ='<div style="padding:5px;"><span class="upcBig"><b>Download or Process</b></span><br/><br/>';
    downloadHTML += '<form id="pilotDownloadForm" name="pilotDownloadForm" enctype="multipart/form-data" action="<?php echo $formAction; ?>" method="post" >' +
      '<span class="upcBig"><input id="upcOutputFormat0" name="upcOutputFormat" value="csv" type="radio" alt="Download CSV file" title="Download CSV File" checked="checked" style="margin-left:5px;" /><label for="upcOutputFormat0">&nbsp;Download CSV file</label><br />' +
      '<input id="upcOutputFormat1" name="upcOutputFormat" value="wget" type="radio" alt="Download BASH script" title="Download WGET File" style="margin-left:5px;" /><label for="upcOutputFormat1">&nbsp;Download BASH script with <i>wget</i> calls</label><br />';
    if (powURL) {
      downloadHTML += '<input id="upcOutputFormat2" name="upcOutputFormat" value="pow" type="radio" alt="Projection on the Web (POW)" title="Projection on the Web (POW)" style="margin-left:5px;" /><label for="upcOutputFormat2">&nbsp;Projection on the Web (POW)</label><br/><br/>';
    }
    downloadHTML += '</span><input type="button" value="Go!" onclick="pilotSearch.downloadSelect();" /></form></div>';
    $('#pilotInfoContainer').html(close + downloadHTML);
  }
};


PilotSearch.prototype.downloadSelect = function() {
  var selectN = 0;
  var selects = [];

  selectN = Number($('#selectNumber').val());
  if (selectN <1) {return;}
  for (key in this.imageArray) {
    if (this.imageArray[key]['select'] === true) {
      selects.push(key);
    }
  }
  $('#select').val(selects.join());
  $('#output').val($('#pilotDownloadForm  input[name="upcOutputFormat"]:checked').val());
  this.pilotAjax.downloadSelect();
};


PilotSearch.prototype.enable = function(target) {

  //console.log('enabling '+ target);
  found = false;
  var st = statsJSON[0]['json_agg'];
  for (i in st) {
      if (target == String(st[i]['displayname']).toLowerCase()) {
	found = true;
      }
  }
  if (!found){
    initTarget = '';
    return;
  }

  $('#totalButton').css('display', 'inline');
  $('#searchBoxes').css('display', 'inline');
  $('#solarSystemReturn').css('display', 'inline');
  $('#mapNav').css('display', 'block');
  $('#constrainNav').css('display', 'block');
  $('#missionNav').css('display', 'block');
  $('#missionsText').html(titleCase(target));
  $('#missionsIcon').attr('src', 'images/' + cleanStr(target).toLowerCase() + "-icon.png");
  $('#missionsIcon').css('display', 'inline');
  $('#tabs').tabs('select', 1);
  showMissions(target);
};


PilotSearch.prototype.getTitle = function(id) {
  return($('#upcCarouselDiv' + this.cleanId(id)).attr('title'));
};


PilotSearch.prototype.highlightFootprint = function(id) {

  this.astroVector.unhighlightAll();
  if (this.imageArray[id]['index'] != null) {
    this.astroVector.highlight(this.imageArray[id]['index']);
  }
};


PilotSearch.prototype.highlightImage = function(id) {

  if (!this.infoLoad) {
    this.pilotAjax.image(id, $('#' + id).attr('src'));
  }
  this.infoLoad = false;
};


PilotSearch.prototype.highlightThumb = function(id) {

  this.stopScroll();
  var e = '#upcCarouselDiv' + this.cleanId(id);
  $('#upcCarousel').animate({scrollTop: ($('#upcCarousel').scrollTop() + $(e).position().top - $('#upcCarousel').height()/2) },750, function(){pilotSearch.infiniteScroll();});
  if (this.hotId) {
      $('#upcCarouselDiv' + this.cleanId(this.hotId)).removeClass('upcCarouselDivHot');
  }
  $(e).addClass('upcCarouselDivHot');
  this.hotId = id;
};


PilotSearch.prototype.image = function(id) {
  if (id.indexOf("Matt") != -1) {
    //click came from matt, nix prefix on id
    id = id.substring(15);
  }
  this.render(id);
  this.highlightImage(id);
  this.highlightFootprint(id);
  this.highlightThumb(id);
  if (pilotStereo.open) {pilotStereo.highlight(id);}
};


PilotSearch.prototype.infiniteScroll = function() {

  var total = Number($('#totalNumber').val());
  var e = '#upcCarousel';
  if (Number(this.step -1) < total) {
    $(e).scroll(function(){
      var buffer = 40;
      var cHeight = Number($(e).scrollTop()) + Number($(e).innerHeight());
      var sHeight = Number($(e)[0].scrollHeight);
      if ((cHeight + buffer) >= sHeight ) {
	pilotSearch.stopScroll();
	pilotSearch.search();
      }
    });
  }
};


PilotSearch.prototype.info = function(id) {
  var eId = escape(id);
  this.infoLoad = true;
  this.pilotAjax.info(eId);
  this.render(eId);
  this.highlightFootprint(eId);
  this.highlightThumb(eId);
};


PilotSearch.prototype.loadAlertOn = function() {
  pilotSearch.searchLoad = true;
  var e = '#upcCarousel';
  $('<img/>', {id: 'loadingImg', src: 'images/ajax-loader.gif'}).appendTo(e);
};


PilotSearch.prototype.loadAlertOff = function() {
  $('#loadingImg').remove();
  pilotSearch.searchLoad = false;
};


PilotSearch.prototype.loadStart = function() {
  this.stopScroll();
};


PilotSearch.prototype.loadComplete = function(id) {
  this.infiniteScroll();
  pilotSearch.highlightThumb(id);
};


PilotSearch.prototype.mapSelect = function(feature) {

  if (feature) {
    if (typeof feature.attributes == 'string') {
      //stereo selected
      var sid = feature.attributes.slice(1);
      pilotStereo.select(null, sid);
    } else {
      var id = feature.attributes['upcid'];
      if (this.hotId != id) {
	pilotSearch.highlightImage(id);
	pilotSearch.highlightFootprint(id);
	pilotSearch.highlightThumb(id);
      }
    }
  }
};


PilotSearch.prototype.map = function(id, url) {
  if (this.imageArray[id]['index'] != null) {
    this.unRender(id);
  } else {
    this.render(id);
    if (id != this.hotId) {
      this.highlightImage(id);
    }
    this.highlightFootprint(id);
    this.highlightThumb(id);
  }
};


PilotSearch.prototype.powDialog = function(jobs)  {

  var casURL = "https://astrocloud-dev.wr.usgs.gov";
  var powLimit=200;
  var powJobs = eval(jobs);
  $('#powDownloadDiv').remove();
  $('<div/>', {id: 'powDownloadDiv', title: 'Projection on the Web'}).appendTo('#farRight');
  var powHTML = '';
  var disText = '';
  var errMsg = '';
  var powIntro = '<p class="smallText purpleText"><b>Projection on the Web (POW)</b> is a beta web service provided by the USGS Astrogeology Science Center. Submission requires account registration. If you decide to submit a job to POW, you will be transferred to another website to complete image processing.<p>';
      powHTML += '<p><span class="smallText" style="cursor:pointer;margin:5px 0px 0px 0px;" onclick="alert(\' WARNING: Google Chrome may request your username and password using an authentication pop-up. Please dismiss this pop-up until the proper login webpage (not a pop-up) is displayed from our sercure login server (https://astrocas.wr.usgs.gov/cas/login).\');return false;"><span class="orangeText">CLICK HERE</span> for additional login advice for <em>Chrome</em> users.</span></p>';

  var powButtons = {};
//  if (powJobs[0]['user'].length == 0) {
  if (false) {
    powHTML += 'Login required. Please <a href="https://astrocas.wr.usgs.gov/cas/login" target="_blank" ><span class="orangeText">login/register</span></a>.</span>';
    $( "#powDownloadDiv" ).dialog("option","buttons", {});
  } else {
    if (powJobs.length == 0) {
      powHTML += 'POW does not support the selected images. Please refer to the <a href="https://astrocloud.wr.usgs.gov/signup/" target="_blank" ><span class="orangeText">Projection on the Web (POW) sign-up page</span></a> for a list of currently supported missions and instruments.';
      powButtons["Close"] = function () {$('#powDownloadDiv').dialog( "close" ); };
    } else {
      for (var i =0; i < powJobs.length; i++) {
	if (powJobs[i]['count'] > powLimit) {
	  disText = ' disabled="disabled" ';
	  errMsg = '<span class="orangeText"> ERROR: limit ' + powLimit + '!</span>';
	} else if (i==0) {
	  disText = ' checked="checked" ';
	}
	imgText = (powJobs[i]['count'] > 1) ? 'images' : 'image';
	powHTML += '<span class="smallText"><input type="radio" name="powJob" ' + disText + ' value="' + powJobs[i]['upcids'] + '" id="powJob" />Submit ' + powJobs[i]['count'] + ' ' + powJobs[i]['displayname'] + ' ' + imgText + ' to POW. ' + errMsg + '<br/></span>';
	errMsg = ''; disText = '';
      }
      powButtons["Cancel"] = function () {$('#powDownloadDiv').dialog( "close" ); };
      //powButtons["Login"] = function() {window.open(casURL);};
      powButtons["Submit"] =  function() {
	var pilotAjax=new pilotAJAX();
	var upcids = $('#powJob:checked').val();
	if (upcids) {
	  pilotAjax.powRequest(upcids);
	}
	$('#powDownloadDiv').dialog( "close" );
      };
    }

  }
  $( "#powDownloadDiv" ).dialog({
    height: 300,
    width: 400,
    zIndex: 2000,
    modal: true,
    buttons: powButtons
  });
  $( "#powDownloadDiv" ).html(powIntro + powHTML);
  $('.ui-button-text').each(function(i){$(this).html($(this).parent().attr('text'));});
};


PilotSearch.prototype.preload = function(json)  {

  if (!json || !json['images'] || (json['images'].length == 0)) {
    alert('Image not found!');
    this.clear();
    return;
  }
  var emptyFootprint = 'POINT(361 0)';
  var i = json['images'][0]['instrumentid'];
  var target = (json['images'][0]['targetname']) ? json['images'][0]['targetname'].toLowerCase() : 'untargeted';
  this.enable(target);
  if (json['images'][0]['footprint'].indexOf(emptyFootprint) == -1) {
    $('#mapped_' + i).prop('checked', true);
  } else {
    $('#unmapped_' + i).prop('checked', true);
  }
  toggleConstrain(i);
  var id = json['images'][0]['upcid'];
  this.show(json['images']);
  $('#tabs').tabs('select', 2);
  setTimeout("pilotSearch.info(" + id + ")",1000);
};


PilotSearch.prototype.quickTotal = function() {

  params = $(this.pilotAjax.formId).find("select,input:not(input[name^=stereo])").serialize();
  if (params.match(/astroBBWKT=\w+|__/)){
    return(false);
  }

  //check over max total
  var total = 0;
  $('#missionList input:checked').each(function() {
    numId = "num_" + $(this).attr('id');
    numE = $('#' + numId).attr('data-number');
    total += Number(numE);
  });
  if (total > this.totalMax) {
    $('#totalNumber').val(total);
    showHelpPanel(true);
    return(true);
  } else {
    return (false);
  }

};

PilotSearch.prototype.render = function(id, dontShowMap, dontCenter) {

  if (!astroWebMapsSemaphore) {
    setTimeout("pilotSearch.render(" + id + ")",3000);
    return;
  }

  var i = this.imageArray[id];
  if (i['footprint'] == this.badGeometry) {
    return;
  }
  if (!dontShowMap) {
    $('#tabs').tabs('select', 2);
    showMap();
  }
  $('#upcCarouselMapButton' + id).attr('src', 'images/globe-hot.gif');

  if (!this.astroVector) {
    this.astroVector = new AstroVector(astroMap, astroMap.vectorSource);
  }

  if (i['index'] == null) {
    var color = instruments[i['instrumentid']]['color'];
    i['index'] = this.astroVector.drawAndStore(i['footprint'], i, color, 'footprint', false, true).index;
  }

  if(!dontCenter) {
    this.astroVector.centerOnStoredVector(i['index']);
  }
};


PilotSearch.prototype.renderAll = function() {

  var total = Number($('#totalNumber').val());
  var limit = 500;

  if (total > limit) {
    alert('Cannot DRAW ALL for search results greater than ' + limit + '!');
  } else {
    if (total > this.step) {
      //load and render
      this.renderLoads = true;
      this.pilotAjax.searchResults(this.step, total);
    } else {
      //only render
      for(var iKey in this.imageArray) {
	this.render(iKey, true, true);
      }
    }
  }
};


PilotSearch.prototype.search = function(clear, loadAll) {

  if (clear || !this.step) {
    this.clear();
  }
  if ($('#missionList input:checked').length < 1) {
    showHelpPanel();
    return;
  }

  var total = Number($('#totalNumber').val());
  if (this.step && (this.step > total)) return;

  if (! pilotSearch.quickTotal()) {
    pilotSearch.loadAlertOn();
    pilotSearch.pilotAjax.search(this.step);
  }
};


PilotSearch.prototype.searchAbortOn = function() {
  //kill ajax call
  this.pilotAjax.abortResults();
  this.loadAlertOff();
};


PilotSearch.prototype.searchAmbiguous = function() {
  this.loadAlertOff();
  this.total(0);
  //clear search panel/warn
  var mainDiv = '#' + this.container;
  $('<div/>', {id: 'pilotAbortMessage'}).appendTo(mainDiv);
  $('#pilotAbortMessage').html('<p><span class="orangeText">SEARCH RESULTS UNAVAILABLE: </span>Your search results are ambiguous (multi-target). Please redefine your search id or select a target and instrument, and then use the <img src="images/globe.gif" />&nbsp;<b>Map</b> and <img src="images/wrench.gif" />&nbsp;<b>Advanced</b> tabs to limit your results. To retrieve full image sets click <i>downloads</i> at the bottom of the  page.</p>');
};


PilotSearch.prototype.searchId = function()  {

  if ($('#upcSearchId').val().indexOf('Id Search') == -1) {
    this.clear();
    pilotSearch.loadAlertOn();
    this.pilotAjax.searchId($('#upcSearchId').val(), $('#upcSearchType').val());
  }
};


PilotSearch.prototype.select = function(id, counted) {
  this.imageArray[id]['select'] = true;
  if (!counted) {
    $('#selectNumber').val(Number($('#selectNumber').val()) + 1);
  }
};


PilotSearch.prototype.selectAll = function() {

  var total = Number($('#totalNumber').val());
  var limit = 500;

  if (total > limit) {
    alert('Cannot SELECT ALL for search results greater than ' + limit + '!');
    return;
  }

  pilotLockout.on('searchSelectAll');
  if ((this.step -1) < total) {
    this.selectAllHit = true;
    this.search();
    return;
  }

  for(var iKey in this.imageArray) {
    if(!$('#pilotSelect' + iKey).is(':checked')) {
      $('#pilotSelect' + iKey).prop('checked', true);
      this.select(iKey);
    }
  }
  pilotLockout.off('searchSelectAll');

};


PilotSearch.prototype.show = function(footprints)  {

  var noimageURL = "images/no-image-thumb.png";
  this.loadAlertOff();

  if (footprints.length == 0) {
    $('<div/>', {id: 'pilotAbortMessage'}).appendTo('#' + this.container);
    $('#pilotAbortMessage').html('<p><span class="orangeText">NO SEARCH RESULTS</span></p>');
    return;
  }
  var mainDiv = document.getElementById(this.container);
  var i = (this.step) ? this.step : 1;
  if (i <= 1) {
    $('#' + this.container).scrollTop(0);
    $('#' + this.container).empty();
  }
  var upcid = '';
  var units = '';
  var thumb = '';
  var shrink = '';
  var ImageInfoHTML = '';
  var groupBy = String($('#groupBy').val());
  var crop = Number($('#cropImages').val());
  if (groupBy.indexOf("angle") != -1) {units = '&deg;';}
  if (groupBy.indexOf("resolution") != -1) {units = 'm/p';}
  $('#panelGroupBy').removeAttr("disabled");
  for(var fKey in footprints) {
    //console.log(footprints[fKey]);
      upcid = footprints[fKey]['upcid'];
      thumb = (footprints[fKey]['thumbnailurl']) ? footprints[fKey]['thumbnailurl'].replace(/['"]+/g,'') : ((footprints[fKey]['Thumbnail Image']) ? footprints[fKey]['Thumbnail Image'] : noimageURL);
      ImageInfoHTML = '';
      this.imageArray[upcid] = {upcid: upcid, productid: footprints[fKey]['productid'], thumbnailurl: thumb, footprint: footprints[fKey]['footprint'], edr_source: footprints[fKey]['edr_source'], instrumentid: footprints[fKey]['instrumentid']};
      var newTitle = i + '. ' + footprints[fKey]['displayname'] + ': ' + footprints[fKey]['productid'];
      var newDiv = $('<div/>', {id: 'upcCarouselDiv' + this.cleanId(upcid), title: newTitle, "class": 'upcCarouselDiv' }).appendTo('#' + this.container);
      var leftDiv = $('<div/>', {"class": 'upcCarouselImageDiv' }).appendTo(newDiv);
      var mattDiv = $('<div/>', {id: 'upcCarouselMatt' + upcid, title: newTitle, "class": 'upcCarouselMattDiv' }).appendTo(newDiv);
      var newImage = $('<img/>', {id: upcid, "class": 'upcCarouselImage', title: newTitle, src: thumb });
      var checked = false;
      var orderBy = '';
      if (groupBy == 'starttime-d' || groupBy == 'starttime-a') {
	var oDate = footprints[fKey][groupBy.substring(0, groupBy.length -2)];
	orderBy = (oDate) ? oDate.slice(0,10) : 'no date';
      } else if (groupBy == 'productid-d' || groupBy == 'productid-a') {
	orderBy = '<span class="medText">' + footprints[fKey]['productid'] + '</span>';
      } else {
	orderBy = Number(footprints[fKey][groupBy.substring(0, groupBy.length -2)]).toFixed(2) + ' ' + units;
      }
      shrink = (i<1000) ? '""' : 'medText';
      var leftInput = $('<input/>', {type: 'checkbox', "class": 'pilotSearchCheckbox', id: 'pilotSelect' + upcid}).appendTo(leftDiv);
      leftInput.prop('checked', checked);
      leftInput.click(function(e) { pilotSearch.check(e);});
      var leftSpan = $('<span/>', {"class": shrink, html: i}).appendTo(leftDiv);
      leftSpan.css('color', instruments[footprints[fKey]['instrumentid']]['color']);
      $('<span/>', {"class": "upcSmall", "html": '&nbsp;' + orderBy}).appendTo(leftDiv);
      newImage.appendTo(mattDiv);
      mattDiv.appendTo(leftDiv);
      if (crop == 1) {
	if (newImage.width() > newImage.height()) {
	  newImage.css('height','100px');
	  newImage.css('width','auto');
	} else {
	  newImage.css('width','100px');
	  newImage.css('height','auto');
	}
      } else {
	if (newImage.width() > newImage.height()) {
	  newImage.css('width','100px');
	  newImage.css('height','auto');
	} else {
	  newImage.css('height','100px');
	  newImage.css('width','auto');
	}
      }
       //$('#upcCarouselDiv' + this.cleanId(upcid)).click(function() {pilotSearch.image(upcid, thumb); });
      //$('#' + upcid).click(function() {pilotSearch.image($(this).attr('id'), $(this).attr('src')); });
      $('#upcCarouselMatt' + upcid).click(function() {pilotSearch.image($(this).attr('id'), $(this).attr('src')); });
      var rightDiv = document.createElement("DIV");
      rightDiv.setAttribute('id', 'upcCarouselInfoDiv' + this.cleanId(upcid));
      rightDiv.className ='upcCarouselInfo';
      ImageInfoHTML += '<div class="upcCarouselLinks">';
      ImageInfoHTML += '<img src="images/infoIcon.gif" title="info" class="upcTreeIcon" onclick="pilotSearch.info(\'' + upcid + '\');" />';
      ImageInfoHTML += '<a href="' + footprints[fKey]['source'] + '" download ><img src="images/download.gif" title="download image" class="upcTreeIcon" /></a>';
      if (footprints[fKey]['detached_label'] && (footprints[fKey]['detached_label'] != '')) {
	ImageInfoHTML += '<a href="' + footprints[fKey]['detached_label'] + '" target="_blank" download ><img src="images/labelDownload.png" title="download label" class="upcTreeIcon" /></a>';
      }
      if (footprints[fKey]['footprint'] != this.badGeometry) {
	ImageInfoHTML += '<img id="upcCarouselMapButton' + upcid + '" src="images/globe.gif" title="map" class="upcTreeIcon" onclick="pilotSearch.map(\'' + upcid + '\',\'' + thumb + '\');" />';
      }
      ImageInfoHTML += '<img src="images/imageSmall.gif" title="image" class="upcTreeIcon" onclick="pilotSearch.bigImage(\'' + upcid + '\',\'' + thumb + '\');" />';
      ImageInfoHTML += '</div>';
      newDiv.append(rightDiv);
      rightDiv.innerHTML = ImageInfoHTML;
      i++;
  }
  this.step = i;
  setTimeout("pilotSearch.infiniteScroll()",500);

  if (this.renderLoads) {
    this.renderLoads = false;
    this.renderAll();
  }

  if (this.selectAllHit) {
    this.selectAll();
  }

  if ($('#stereoNav').css('display') == 'block') {
    $("#tabs").tabs('enable',4);
  }

};


PilotSearch.prototype.stopScroll = function(num) {
  $('#upcCarousel').unbind("scroll");
};


PilotSearch.prototype.toggleThumbs = function() {

  var crop = Number($('#cropImages').val());
  //toggle
  if (crop == 0) {
    crop = 1;
    $('#cropButton').attr('src', 'images/crop-hot.gif');
    $('#cropButton').attr('title', 'Crop Thumbnails (on)');
  } else {
    crop = 0;
    $('#cropButton').attr('src', 'images/crop.gif');
    $('#cropButton').attr('title', 'Crop Thumbnails (off)');
  }
  $('#cropImages').val(crop);

  if (pilotSearch.imageArray.length > 0) {
    for(var iKey in pilotSearch.imageArray) {
      var newImage = $('#' + iKey);
      if (crop == 1) {
	if (newImage.width() > newImage.height()) {
	  newImage.css('height','100px');
	  newImage.css('width','auto');
	} else {
	  newImage.css('width','100px');
	  newImage.css('height','auto');
	}
      } else {
	if (newImage.width() > newImage.height()) {
	  newImage.css('width','100px');
	  newImage.css('height','auto');
	} else {
	  newImage.css('height','100px');
	  newImage.css('width','auto');
	}
      }
    }
  }
};


PilotSearch.prototype.total = function(total) {

  if (total == -1) {
    this.searchAbortOn();
    showHelpPanel();
    return;
  }

  $('#totalNumber').val(total);
  if (Number(total) > this.totalMax) {
    this.searchAbortOn();
    showHelpPanel(true);
  }

};


PilotSearch.prototype.unHighlightFootprint = function(id) {
  if (this.imageArray[id]['index'] != null) {
    this.astroVector.unhighlight(this.imageArray[id]['index']);
  }
};


PilotSearch.prototype.unRender = function(id) {
  $('#tabs').tabs('select', 2);
  this.astroVector.removeAndUnstore(this.imageArray[id]['index']);
  this.imageArray[id]['index'] = null;
  $('#upcCarouselMapButton' + id).attr('src', 'images/globe.gif');
};


PilotSearch.prototype.unRenderAll = function() {

  for(var iKey in this.imageArray) {
      if (this.imageArray[iKey]['index'] != null) {
	this.unRender(iKey);
      }
    }
};


PilotSearch.prototype.unselect = function(id) {
  this.imageArray[id]['select'] = false;
  $('#selectNumber').val(Number($('#selectNumber').val()) -1);
  this.selectAllHit = false;

};


PilotSearch.prototype.unselectAll = function() {

  for(var iKey in this.imageArray) {
    if (this.imageArray[iKey]['select']) {
      this.imageArray[iKey]['select'] = false;
    }
  }
  this.selectAllHit = false;
  $('#selectNumber').val('0');
  $('#upcCarousel input').each(function() { $(this).attr("checked",false); });
};

var pilotSearch = new PilotSearch();
