<?php

//paths
$componentPath = "http://" . $_SERVER['SERVER_NAME'] . str_replace('//','/',dirname($_SERVER['PHP_SELF'])) . "/";
$jsPath = $componentPath . "js/";
$cssPath = $componentPath . "css/";
$imagePath = $componentPath . "images/";
$mapLinkPath = "http://" . $_SERVER['SERVER_NAME'] . str_replace('//','/', dirname($_SERVER['PHP_SELF']));
$linkPath = "http://" . $_SERVER['SERVER_NAME'] . str_replace('//','/',dirname($_SERVER['PHP_SELF']));
$formAction = htmlentities("http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF']);
$planetArray = array('MERCURY','VENUS','EARTH','MARS','JUPITER','SATURN','SMALL BODIES','URANUS','NEPTUNE');

?>
<div id="left" class="shadow">

<!--FORM-->
<form id="upcSearchForm" name="upcSearchForm" enctype="multipart/form-data" action="<?php echo $formAction; ?>" method="post" >
  <input type="hidden" name="target" id="target" value="<?php echo $controller->target; ?>" />
  <input type="hidden" name="MAX_FILE_SIZE" value="30000" />
  <input type="hidden" name="upcQuery" id="upcQuery" value="1" />
  <input type="hidden" name="astroBBDatelineWKT" id="astroBBDatelineWKT" value="" />
  <input type="hidden" name="hashItem" id="hashItem" value="" />
  <input type="hidden" name="pj" id="pj" value="" />
  <input type="hidden" name="act" id="act" value="" />
  <input type="hidden" name="groupBy" id="groupBy" value="starttime-a" />
  <input type="hidden" name="render" id="render" value="100" />
  <input type="hidden" name="select" id="select" value="" />
  <input type="hidden" name="unselect" id="unselect" value="" />
  <input type="hidden" name="output" id="output" value="" />
  <input type="hidden" name="downloadProductId" id="downloadProductId" value="" />


<div id="tabs" style="background:none;">
   <ul id="tabsUL" >
    <!-- <span id="solarSystemReturn" onclick="$('#tabs').tabs('select',0);">Home</span> -->
    <img src="" id="missionsIcon" /><span id="missionsText">Planetary Image Locator Tool</span>
    <li id="solarSystemNav" ><a href="#solarSystemTab">Solar System</a></li>
    <li id="missionNav" ><a href="#missionsTab"><img id="missionsTabImg" src="images/missions.gif" /></span><span class="screen-only"> Missions</span></a></li>
    <li id="mapNav" ><a href="#mapTab"><img id="mapTabImg" src="images/globe.gif" /></span><span class="screen-only"> Map</span></a></li>
    <li id="constrainNav" ><a href="#constrainTab"><img id="constrainTabImg" src="images/wrench.gif" /><span class="screen-only"> Advanced</span></a></li>
    <li id="stereoNav" ><a href="#stereoTab"><img id="stereoTabImg" src="images/stereo.gif" /><span class="screen-only"> Stereo</span></a></li>
  </ul>
  <div id="solarSystemTab" >
<!--
  <div class="hotspot" onclick="$('#solarSystemTab').css('background-image','url(images/apollo-cockpit2.png)');stereoHot=true;"></div>
-->
  </div> <!--solarSystemTab-->
  <div id="missionsTab">
  </div>
  <div id="mapTab">
    <div id="map" style="width:100%;height:100%;" ><img id="mapLoader" src="images/ajax-loader-reverse.gif" /></div>

    <table id="mapBottom">

     <tr><td rowspan="2"><div class="astroConsole">
      <div id="astroConsoleTargetInfo"></div>
      <div id="astroConsoleProjectionButtons"></div>
      <div id="astroConsoleLonLatSelects"></div>
      <div id="astroConsoleKey"></div>
     </td>

    <td><div class="upcSmallGray" style="margin-top:10px;" ><input type="button" id="pilotSetBounds" style="" value="Set" onclick="astroMap.boundingBoxDrawer.drawFromBounds(true);pilotConstrain.mapSearch();" />
 bounding box below. . .<br/></div>
       <div class="upcLatLonCenterBox" style="">Max Lat<br/><input type="text" id="astroBBTopLeftLat" size="10" value="" /></div>
       <div class="upcLatLonLeftBox">Min Lon<br/><input type="text" id="astroBBTopLeftLon" size="10" value="" /></div>
       <div class="upcLatLonRightBox">Max Lon<br/><input type="text" id="astroBBBotRightLon" size="10" value="" /></div>
       <div class="upcLatLonCenterBox" >Min Lat<br/><input type="text" id="astroBBBotRightLat" size="10" value="" /></div>
       <input type="hidden" name='astroBBWKT' id="astroBBWKT" onchange="astroMap.boundingBoxDrawer.drawFromForm();" />
       <input type="hidden" name="queryType" value="intersects" />
    </td>

     <td>
	<div style="float:right;">
         <input type="button" name="upcClearBoundingBoxButton" id="pilotBBClear" style="margin: 10px 5px 0px 0px;" id="upcClearBoundingBoxButton" value="Clear Bounding Box" onclick="astroMap.boundingBoxDrawer.removeAndUnstoreAll();//pilotConstrain.mapSearch();" /> 
        </div>
	<div style="float:right;clear:both;margin-top:5px;"">
            <div class="upcSmallGray">Feature Finder</div><select id="upcFeatureType" onchange="call= new pilotAJAX('featureAjaxGet');call.loadFeatureNames(this.value);" ></select><select id="upcFeatureName" disabled="disabled" onchange="call= new pilotAJAX('featureLatLonAjaxGet');call.loadFeatureLatLon(this.value)" ></select>
	</div>
     </td></tr>
    </table>



  </div><!--mapTabl-->
  <div id="constrainTab">
     <div id="constrainTabs"></div>										   
     <div id="sliderDiv"></div>
  </div>

  <div id="stereoTab">
  </div>


</div><!--tabs-->



<script type="text/javascript" >

  var statsJSON= <?php echo $controller->model->statsJSON; ?>;
  var missionLinks= <?php echo $controller->model->missionLinks; ?>;
  var powURL= '<?php echo $controller->powURL; ?>';
  var initTarget = '<?php echo $controller->target; ?>';  

  $(function() {

<?php if (isset($controller->searchIdPreload) && ($controller->searchIdPreload != '')) { ?>
      $('#upcSearchId').val('<?php echo $controller->searchIdPreload; ?>');
      $('#upcSearchType').val('<?php echo $controller->searchSelect; ?>');
      pilotSearch.searchId();
      $( "#tabs" ).tabs({active: 1, fx: [{opacity:'toggle', duration:'fast'}] });
<?php }?>

$( "#tabs" ).tabs({
  beforeActivate: function (event, ui) {
      switch(ui.newPanel.attr('id')) {
      case "mapTab":
	showMap();
	break;
      case "constrainTab":
	showConstrain();
	break;
      case "solarSystemTab":
	showSolarSystem();
	break;
      case "stereoTab":
	showStereo();
	break;
      }
    }
  });
	$("#tabs").tabs('disable',2);
	$("#tabs").tabs('disable',3);
	$("#tabs").tabs('disable',4);
	$("#pilotBigImage").draggable({handle:'span'});
	$("#pilotStats").draggable({handle:'#pilotStatsDragImage'});

        showSolarSystem();
  <?php
  echo isset($totalLoad) ? $totalLoad : '';
  ?>
      document.getElementById('target').value='';

  });
</script>
<script>
var stereoHot=true;
var astroWebMapsSemaphore = false;
var pilotAlert = "<?php $config = new Config();echo $config->pilotAlert; ?>";
window.onload = function() {
  $.getScript("<?php $config = new Config(); echo $config->openLayersURL; ?>", function() {
		$.getScript("<?php echo $config->atlasURL; ?>", function() {
			      $.getScript( "<?php echo $config->astroWebMapsURL; ?>", function() {
					    astroWebMapsSemaphore = true;
					  });
			    });
	      });
  var heightOffset = 140;
  var newHeight = ($(window).height() -heightOffset);
  var subHeight = ($(window).height() -heightOffset - 75);
  $('#left').height(newHeight - 10);
  $('#solarSystemTab').height(subHeight);
  $('#missionsTab').height(subHeight);
  $('#constrainTab').height(subHeight);
  $('#mapTab').height(subHeight);
  $('#stereoTab').height(subHeight);
  $('#right').height(newHeight);
  $('#nearRight').height(newHeight -40);
  $('#farRight').height(newHeight -40);
  $('#pilotInfoContainer').height(newHeight -40);
  $('#pilotInfo').height(newHeight -40);
  $('#map').height(subHeight - 120);
  $('#left').css('display', 'block');
  $('#right').css('display', 'block');

  //preload images
  $('<img/>')[0].src = "images/search-hover.png";
  $('<img/>')[0].src = "images/search-hot.png";
  $('<img/>')[0].src = "images/globe-set.gif";
  $('<img/>')[0].src = "images/missions-set.gif";
  $('<img/>')[0].src = "images/wrench-set.gif";

  if (initTarget != '') { pilotSearch.enable(initTarget);}

}
</script>

</form>
</div>



