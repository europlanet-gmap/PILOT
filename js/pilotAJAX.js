/*
 *  pilotAJAX Class - mbailen
 */

var pilotLockout = new PilotLockout();
var upcSearchHistory = new Array();  //global to hold search history


if (!Array.prototype.indexOf) {
  Array.prototype.indexOf = function(obj, start) {
    for (var i = (start || 0), j = this.length; i < j; i++) {
      if (this[i] == obj) {return i;}
    }
    return -1;
  };
}


// constructor
function pilotAJAX() {

  //form url
  this.formId = '#upcSearchForm';
  this.basePath = location.href.slice(0,location.href.lastIndexOf('?'));
  this.paramArray = {};
  this.instrumentArray = new Array();
  this.imagePath = this.basePath+'/images/';
  this.upcAjaxURL = this.basePath+'/index.php?';
  this.upcAjaxURLNoParams = this.basePath+'/index.php?';
  this.hitTotalElement = 'astroFootprintTotalNumber';
  this.pageLimit = 100;
  this.zoomHardcodes = [['MOCWA', 1], ['NAC 1', 1], ['VIS 1A',3], ['VIS 1B',3], ['ISSNA', 1], ['ISSWA', 1]];
  this.resultsAjaxCall = null;
};


pilotAJAX.prototype.abortResults = function() {
  this.resultsAjaxCall.abort();
  pilotLockout.off('searchResults');

};


pilotAJAX.prototype.bigImage = function(id, thumbnailurl) {

  var encodedId = encodeURIComponent(id);
  this.setAct('bigImageAjaxGet');
  pilotLockout.on('bigImage');
  $.ajax({
      url: this.upcAjaxURLNoParams,
      dataType: 'json',
      data: 'upcid=' + encodedId + '&' + this.formParams,
      type: 'POST',
      success: function(json) {
	pilotLockout.off('bigImage');
	if (!json || !json['fullimageurl'] || (json['fullimageurl'] == '')) {
	  alert('No Image Found!');
	} else {
	  $('#pilotBigImage').css('display', 'block');
	  $('#pilotBigImageContainer').html('');
	  $("<img/>", {
	      src: json['fullimageurl'],
	      title: pilotSearch.getTitle(id),
	      id: 'upcFootprintImage'+id,
	      "class": 'upcThumbnailFootprint'
	  }).appendTo('#pilotBigImageContainer');
          $('#upcFootprintImage'+id).on("load", function() {
		var newH = (this.height > ($(window).height() - 180)) ? $(window).height() - 180 : this.height;
		var newW = ((this.width + 100) < ($(window).width() - 300)) ? this.width + 100 : $(window).width() - 300;
		$('#pilotBigImageContainer').css('height', newH);
		$('#pilotBigImageContainer').css('width', newW);
		$('#pilotBigImage').css('width', newW);
		$('#pilotBigImage').css('right', 100);
	      });
        }
      }
    });
    this.image(id, thumbnailurl);
};


pilotAJAX.prototype.downloadSelect = function(id) {

  this.setAct('ajaxDownload');
  if ($('#output').val() == 'pow') {
    pilotLockout.on('downloadPOW');
    $.ajax({
      url: this.upcAjaxURLNoParams,
      data: this.formParams,
      type: 'POST',
      success: function(json) {
	pilotLockout.off('downloadPOW');
	$('#act').val('');
	$('#select').val('');
	$('#unselect').val('');
	$('#output').val('');
	if (!json) {
	  alert('No Images Found!');
	} else {
	  pilotSearch.powDialog(json);
	}
     }
    });
  } else {
    document.upcSearchForm.submit();
    $('#act').val('');
    $('#select').val('');
    $('#unselect').val('');
    $('#output').val('');
  }
};


pilotAJAX.prototype.histogram = function(instrument, keyword) {

  //pilotLockout.on('histogram');
  this.setAct('histogramAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'histogram=' + instrument + '&keyword=' + keyword + '&' + this.formParams,
	   type: 'POST',
	   success: function(json) {
	     pilotConstrain.showHistogram(json);
	     //pilotLockout.off('histogram');
	   }
  });
};


pilotAJAX.prototype.image = function(id, thumbnailurl) {

  var c = '#pilotInfoContainer';
  var close = '<span class="closeInfo" onclick="pilotSearch.closeInfo();" >x</span>';
  $(c).html(close);
  pilotLockout.on('lilImage');
  $("<img/>", {
      src: thumbnailurl,
      title: pilotSearch.getTitle(id),
      id: 'upcFootprintImage'+id,
      "class": 'upcThumbnailFootprint'
    }).appendTo(c);

  $('#upcFootprintImage'+id).on("load", function() {
    pilotSearch.loadStart();
    var nearWidth = '48%';
    var farWidth = '52%';
    var space = $('#right').width();
    if ((space * .4) > this.width) {
      farWidth = String(this.width + 75) + 'px';
      nearWidth = String(space - this.width - 80)   + 'px';
    }
    $('#farRight').css('display','block');
    $('#nearRight').animate({width: nearWidth}, 1000, function(){});
    $('#farRight').animate({width: farWidth}, 1000, function(){pilotSearch.loadComplete(id);pilotLockout.off('lilImage');});
  });
};


pilotAJAX.prototype.info = function(id) {

  var encodedId = encodeURIComponent(id);
  pilotLockout.on('info');
  this.setAct('infoAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'upcid=' + encodedId + '&' + this.formParams,
	   type: 'POST',
	   success: function(json) {
	     if (json) {
	       pilotSearch.loadStart();
	       var tipText='';
	       var productName='', productText='';
	       var header = [];
	       for (var item in json) {
		 switch(item) {
		 case 'Full Size Image':
		 case 'Thumbnail Image':
		 case 'instrumentid':
		 case 'instrument':
		   break;
		 case 'productid':
		   productName += json[item];
		   productText = '<span class="upcBig"><b>Product ID:</b> ' + json[item] + '</span><br/>';
		   break;
		 case 'isisid':
		   header['ISIS ID'] = json[item];
		   break;
		 case 'targetname':
		   header['Target'] = json[item];
		   break;
		 case 'displayname':
		   header['Instrument'] = json[item];
		   break;
		 case 'edr_source':
		   header['EDR Source'] = json[item];
		   break;
		 case 'WKT':
		 case 'Footprint':
		 case 'footprint':
		   if (json[item] == pilotSearch.badGeometry) {
		     tipText = tipText + '<b>WKT:</b> no geometry<br/>';
		   } else {
		     tipText = tipText + '<b>WKT:</b><div style="height:30px;overflow:auto;"> ' + json[item] + '</div>';
		   }
		   break;
		 case 'edr_source':
		   tipText = tipText + '<b>EDR:</b> ' + json[item] + '<br/>';
		   break;
		 default:
		   tipText = tipText + "<b>"+ item + ':</b> ' + json[item] + '<br/>';
		 }
	       }
	       $('#nearRight').animate({width: '60%'}, 1000, function(){});
	       $('#farRight').css('display','block');
	       $('#farRight').animate({width:'40%'}, 1000, function(){pilotSearch.loadComplete(json['upcid']);});
	       var head = '';
	       for (var key in header) {
		 head = head + "<b>" + key + ':</b> ' + header[key] + '<br/>';
	       };
	       var close = '<span class="closeInfo" onclick="pilotSearch.closeInfo();" >x</span>';
	       $('#pilotInfoContainer').html(close + '<div style="padding:5px;">' + productText + '<br/>' + head + tipText + '</div>');
	     }
	     pilotLockout.off('info');
	   }
  });
};


pilotAJAX.prototype.limits = function(instrument) {

  pilotLockout.on('limits');
  this.setAct('limitsAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'limits=' + instrument + '&' + this.formParams,
	   type: 'POST',
	   success: function(json) {
	     if (json) {
	       pilotConstrain.show(json);
	     }
	     pilotLockout.off('limits');
	   }
  });
};


// nomen call
pilotAJAX.prototype.loadFeatureTypes = function(target) {

    $.ajax({
      url: this.upcAjaxURLNoParams,
      dataType: 'json',
      data: 'act=featureTypesAjaxGet&target=' + target,
      type: 'POST',
      success: function(json) {
	if (!json) {
	} else {
	  var e = document.getElementById('upcFeatureType');
	  e.options.length = 0;
	  var newOption=document.createElement("option");
	  newOption.innerHTML= 'Select Type. . .';
	  e.appendChild(newOption);
	  var ft =  json['featureTypes']['type'];
	  for (var type in json['featureTypes']['type']) {
	    newOption=document.createElement("option");
	    newOption.text = ft[type];
	    newOption.innerHTML= ft[type];
	    newOption.setAttribute('value',ft[type]);
	    e.appendChild(newOption);
	  }
	}
	}
      });
};


// nomen call
pilotAJAX.prototype.loadFeatureNames = function(featureType) {

  pilotLockout.on('loadFeatureNames');
  var target = $('#target').val();
  $.ajax({
      url: this.upcAjaxURLNoParams,
      dataType: 'json',
      data: 'act=featureAjaxGet&target=' + target+'&featureType='+featureType,
      type: 'POST',
      success: function(json) {
	if (!json) {
	  alert('No Features Found!');
	} else {
	  var e =document.getElementById('upcFeatureName');
	  e.options.length = 0;
	  e.disabled = false;
	  var newOption=document.createElement("option");
	  newOption.innerHTML= 'Select Feature. . .';
	  e.appendChild(newOption);
	  var features = json['results'];
	  for (var feature in json['results']) {
	    fname = features[feature]['feature_name'];
	    if (!fname)  {continue;}
	    newOption=document.createElement("option");
	    newOption.text = fname;
	    newOption.innerHTML= fname;
	    newOption.setAttribute('value',features[feature]['id']);
	    e.appendChild(newOption);
	  }
	}
	pilotLockout.off('loadFeatureNames');
      }
    });
};


// nomen call
pilotAJAX.prototype.loadFeatureLatLon = function(featureId) {

  pilotLockout.on('loadFeatureLatLon');
  $.ajax({
    url: this.upcAjaxURLNoParams,
    dataType: 'json',
    data: 'act=featureLatLonAjaxGet&featureId='+featureId,
    type: 'POST',
    success: function(json) {
      if (!json) {
	alert('Feature not Found!');
      } else {
	var geometry = json['geometry'];
	document.getElementById('astroBBWKT').value = geometry;
	astroMap.boundingBoxDrawer.drawFromForm();
	pilotConstrain.mapSearch();
      }
      pilotLockout.off('loadFeatureLatLon');
    }
  });
};


//
// NON-AJAX call to load ISIS template (uses IFRAME)
//
// passed in: global object: astroMap
//            index of hash: index
//
pilotAJAX.prototype.loadTemplate = function() {

  //create iframe
  var tIframe = document.createElement("IFRAME");
  tIframe.style.width = '0px';
  tIframe.style.height = '0px';
  tIframe.setAttribute('name','templateIframe');
  tIframe.setAttribute('id','templateIframe');
  document.body.appendChild(tIframe);
  //form
  var tForm=document.createElement("form");
  tForm.method = 'POST';
  tForm.action = this.upcAjaxURL;
  tIframe.document.body.appendChild(tForm);
  //file element

  //submit
  tForm.submit();
  //remove iframe
  document.body.removeChild(iframe);

};


pilotAJAX.prototype.loadNews = function() {

  $.get('tools/rss.php',
    'xml' , function(data) {
      if (data) {
	var rssHTML = '<div id="pilotNews"><h3>News</h3>';
	var xml = $.parseXML($.trim(data));
	var item = $(xml).find('item');
	var newsHREF = '';
	$(item).each(function(index, value) {
                       newsHREF = $(value).children('link').text().replace('jane-d.wr','astrogeology');
		       rssHTML += '<div class="pilotNewsItem"><a target="_blank" href="' + newsHREF + '" class="purpleText">' + $(value).children('title').text() + '</a><br/>';
		       rssHTML += '<span class="upcSmallGray">&nbsp;' + $.datepicker.formatDate('M d, yy',  new Date($(value).children('pubdate').text())) +  '</span><br/>';
		       rssHTML += '<span class="smallText">' + $(value).children('description').text().replace(/<p>|<\/p>/g,'').replace(/<span.*>|<\/span>/g,'').replace(/<br \/>/g,'.').substring(0,300) + '. . .<a href="' + newsHREF + '" target="_blank" ><span class="purpleText"> Read more</span></a><br/></span></div>';
		     });
	rssHTML += '<br/><span><a href="http://astrogeology.usgs.gov/news/pilot" target="_blank">More news. . . </a></span>';
	$('#upcCarousel').append(rssHTML + '</div>');
      } else {
	$('#upcCarousel').append('PILOT and UPC news is unvailable at this time.');
      }
    });

  if ((typeof pilotAlert != 'undefined') && (pilotAlert)) {
    alertHTML = '<div class="pilotNewsItem"><span class="orangeText">ALERT: </span><span class="smallText">' + pilotAlert + '</span><br/></div>';
    $('#upcCarousel').prepend(alertHTML);
  }
};


pilotAJAX.prototype.loadUnknownStats = function() {

  pilotLockout.on('unknownStats');
  this.setAct('unknownStatsAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: this.formParams,
	   type: 'POST',
	   success: function(json) {
	     if (json) {
	       unknownStatsJSON = json; //global defined in pilotPanels.js
	       showMissions('untargeted');
	     }
	     pilotLockout.off('unknownStats');
	   }
  });
};


pilotAJAX.prototype.missionStats = function(id) {

  this.setAct('missionStatsAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'histogram=' + id + '&' + this.formParams,
	   type: 'POST',
	   success: function(json) {
	     pilotConstrain.showMissionStats(json);
	   }
  });
};


pilotAJAX.prototype.powRequest = function(upcids) {

  $('#pilotToPOWForm').remove();
  var target = $('#target').val();
  var londir = $('#astroConsoleLonDirSelect').val();
  var londom = $('#astroConsoleLonDomSelect').val();
  var lattype = $('#astroConsoleLatTypeSelect').val();
  //post the ids to POW
  $("<form/>", {
      method: "POST",
      action: powURL, //GLOBAL... comes from configure.php (eval'd in default.php)
      id: 'pilotToPOWForm',
      target: "_blank"}
   ).appendTo('body');
  $("<input type='hidden' name='view' value='addjob' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='type' value='POW' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='upcids' value='" + upcids + "' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='target' value='" + target + "' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='londir' value='" + londir + "' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='londom' value='" + londom + "' />").appendTo("#pilotToPOWForm");
  $("<input type='hidden' name='lattype' value='" + lattype + "' />").appendTo("#pilotToPOWForm");
  console.log($('#pilotToPOWForm').serializeArray());
  $('#pilotToPOWForm').submit();

};


pilotAJAX.prototype.setAct = function(act, ignoreForm) {
  $('#act').val(act);
  if (!ignoreForm) {
    this.formParams = $(this.formId).find("select,input:not(input[name^=stereo])").serialize();
  } else {
    this.formParams = this.formParams.replace(new RegExp('act=[^&]*'),'act=' + act);
  }
};


pilotAJAX.prototype.setParam = function(param, val) {
  var regStr = param + '=[^&]*';
  this.formParams = this.formParams.replace(new RegExp(regStr), param + '=' + val);
};


pilotAJAX.prototype.search = function(step, render) {

  if (!step) {
    //get total
    this.searchTotal();
  }
  //async - get results
  this.searchResults(step, render);

};


pilotAJAX.prototype.searchTotal = function(index,hashKey,render) {

  pilotLockout.on('searchTotal');
  this.setAct('totalAjaxGet');
  $.ajax({
	   url: this.upcAjaxURL,
	   dataType: 'json',
	   data: this.formParams,
	   type: 'POST',
	   success: function(json) {
	     if (json > -1) {
	       pilotSearch.total(json);
	     }
	     pilotLockout.off('searchTotal');
	   }
	 });

};


pilotAJAX.prototype.searchResults = function(step, render) {

  //step
  var stepParam = '';
  if ((step != null) && (step > 0)) {
    stepParam = '&step='+step;
    this.setAct('resultsAjaxGet', true);
  } else {
    step = 1;
    this.setAct('resultsAjaxGet');
  }

  //render
  if ((render != null) && (render > 0)) {
    this.setParam('render', render);
  }

  pilotLockout.on('searchResults');
  var imagePath = this.imagePath;
  this.resultsAjaxCall = $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: this.formParams + stepParam,
	   type: 'POST',
	   success: function(json) {
	     pilotSearch.show(json['images']);
	     pilotLockout.off('searchResults');
	     if (pilotStereo.matchOnLoad) {
	       pilotStereo.match();
	     }
	   }
  });
};


pilotAJAX.prototype.searchId = function(id, type) {

  //get total
  this.searchIdTotal(id, type);

  //async - get results
  this.searchIdResults(id, type);
};


pilotAJAX.prototype.searchIdTotal = function(id, type) {

  var encodedId = encodeURIComponent(id);
  var encodedType = encodeURIComponent(type);
  pilotLockout.on('searchIdTotal');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'act=searchIdTotalAjaxGet&upcSearchId=' + encodedId + '&upcSearchSelect=' + encodedType,
	   type: 'POST',
	   success: function(json) {
	     pilotSearch.total(json);
	     pilotLockout.off('searchIdTotal');
	   }
	 });
};


pilotAJAX.prototype.searchIdResults = function(id, type) {

  var encodedId = encodeURIComponent(id);
  var encodedType = encodeURIComponent(type);
  pilotLockout.on('searchIdResults');
  this.resultsAjaxCall = $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: 'act=searchIdAjaxGet&upcSearchId=' + encodedId + '&upcSearchSelect=' + encodedType,
	   type: 'POST',
	   success: function(json) {
	     if (json) {
	       if (json == -1) {
		 pilotSearch.searchAmbiguous();
	       } else {
		 pilotSearch.preload(json);
	       }
	     }
	     pilotLockout.off('searchIdResults');
	   }
  });
};


pilotAJAX.prototype.stereoProcess = function(stereos, order) {

  pilotLockout.on('stereoProcess');
  this.setAct('stereoProcessAjaxGet');
  $.ajax({
	   url: this.upcAjaxURLNoParams,
	   dataType: 'json',
	   data: this.formParams + '&stereos='+stereos + '&stereoOrder='+order,
	   type: 'POST',
	   success: function(json) {
	     pilotLockout.off('stereoProcess');
	     if (json) {
	       pilotStereo.processComplete(json);
	     }
	   }
  });
};

