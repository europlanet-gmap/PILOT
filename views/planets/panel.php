<div id="right"  class="shadow" >

<div id="rightContent">

<div id="totalBar" class="panelBar" >
  <div id="searchBoxes" >

   <div id="totalBox">
    <span id="totalTitle" class="screen-only">Total </span>
    <input type="text" id="totalNumber" readonly="readonly" size="8" value="" class="astroConsoleGenericInput" />
   </div>

         <div class="astroConsoleDrawButtonDiv" >
           <img title="Clear All Footprints" id="pilotFPClear" class="astroConsoleDrawButton" src="images/undraw-all.gif" onclick="pilotSearch.unRenderAll();" /> 
           <img title="Draw All Footprints" id="pilotFPDraw" class="astroConsoleDrawButton" src="images/draw-all.gif"  onclick="pilotSearch.renderAll();" /> 
         </div>



   <div class="selectBox">
    <span class="panelSubTitle">Select </span>
    <input type="text" id="selectNumber" readonly="readonly" size="7" value="" class="panelSubTitle" />
    <img id="pilotDownloadSelect" class="selectButton" title="Download or Process" alt="Download or Process" src="<?php echo $imagePath . 'download.gif'; ?>" onclick="pilotSearch.downloadSelectForm();" />
    <img id="pilotSelectAll" class="selectButton" title="Select All" alt="Select All" src="<?php echo $imagePath . 'select-all.gif'; ?>" onclick="pilotSearch.selectAll();" />
    <img id="pilotUnSelectAll" class="selectButton" title="Unselect All" alt="Unselect All" src="<?php echo $imagePath . 'unselect-all.gif'; ?>" onclick="pilotSearch.unselectAll();" />
   </div> 

    <div id="cropButtonDiv">
     <img id="cropButton" alt="Crop Thumbnails" title="Crop Thumbnails (on)" onclick="pilotSearch.toggleThumbs();" src="<?php echo $imagePath . 'crop-hot.gif'; ?>" /><input type="hidden" value="1" id="cropImages" />
    </div>

   <div id="groupbyBox">
    <span class="panelSubTitle screen-only">Order</span>
    <select id="panelGroupBy" disabled="disabled" onchange="$('#groupBy').val($('#panelGroupBy').val());pilotSearch.search(true);">
    <option value="starttime-a">Date (ASC)</option>
    <option value="starttime-d">Date (DEC)</option>
    <option value="maximumemission-a">Max Emission Angle (ASC)</option>
    <option value="maximumemission-d">Max Emission Angle (DEC)</option>
    <option value="maximumincidence-a">Max Incidence Angle (ASC)</option>
    <option value="maximumincidence-d">Max Incidence Angle (DEC)</option>
    <option value="maximumphase-a">Max Phase Angle (ASC)</option>
    <option value="maximumphase-d">Max Phase Angle (DEC)</option>
    <option value="meangroundresolution-a">Mean Ground Resolution (ASC)</option>
    <option value="meangroundresolution-d">Mean Ground Resolution (DEC)</option>
    <option value="minimumemission-a">Min Emission Angle (ASC)</option>
    <option value="minimumemission-d">Min Emission Angle (DEC)</option>
    <option value="minimumincidence-a">Min Incidence Angle (ASC)</option>
    <option value="minimumincidence-d">Min Incidence Angle (DEC)</option>
    <option value="minimumphase-a">Min Phase Angle (ASC)</option>
    <option value="minimumphase-d">Min Phase Angle (DEC)</option>
    <option value="productid-a">Product Id (ASC)</option>
    <option value="solarlongitude-a">Solar Longitude (ASC)</option>
    <option value="solarlongitude-d">Solar Longitude (DEC)</option>
    </select>
   </div>

  </div>
</div>

 <div id="topRight" >
   <div id="farRight" >
    <div id="pilotInfo">
         <div id="pilotInfoContainer"></div>
    </div>
   </div>

   <div id="nearRight">
      <div id="upcCarousel"></div>
   </div>
</div>

 <div id="bottomRight">

   <div id="stereoBuffer"></div>

   <div id="stereoBar" class="panelBar" >
   <div id="stereoBox">
    <span id="stereoTitle" class="screen-only">Stereo Matches </span>
    <input type="text" id="stereoNumber" readonly="readonly" size="8" value="" class="astroConsoleGenericInput" />
    <span class="closeStereo" onclick="pilotStereo.close();" >x</span>
    <img title="Clear All Intersects" id="pilotIClear" class="astroConsoleDrawButton" src="images/undraw-all.gif" onclick="pilotStereo.unRenderAll();" /> 
   <img title="Draw All Intersects" id="pilotIDraw" class="astroConsoleDrawButton" src="images/draw-all.gif"  onclick="pilotStereo.renderAll();" /> 
   <a href="#" class="stereoExport"><img title="Download CSV of Intersects" id="pilotIStereoDownload" class="astroConsoleDrawButton" src="images/download.gif" /></a> 

   <span class="stereoOrderBox">
    <span class="panelSubTitle screen-onlyr">Order</span>
    <select id="stereoOrder" onchange="pilotStereo.reorder();">
     <option value="area">Area</option>
     <option value="conv">Convergence Angle</option>
     <option value="productid">Product ID</option>
    </select>
   </span>


<script type="text/javascript" >
//set up export function
$(".stereoExport").on('click',function (e) {
    pilotStereo.download(this);
		      });
</script>
   </div>
   </div>
   <div id="stereoMatches"></div>

 </div>

</div>

</div>



<div id="pilotBigImage" style="display:none;" class="shadow" >

    <div id="pilotBigImageDiv">
      <div id="upcMoreInfoDragImageDiv">
         <span><img class="upcMoreInfoButtons" id="upcMoreInfoDragImage" src="<?php echo $imagePath . 'pan_off.png'; ?>" onmouseover="document.getElementById('upcMoreInfoDragImage').src='<?php echo $imagePath . 'pan_hot.png'; ?>';" onmouseout="document.getElementById('upcMoreInfoDragImage').src='<?php echo $imagePath . 'pan_off.png'; ?>';" alt="Drag" /></span>
         <img id="upcMoreInfoClose" class="upcMoreInfoButtons" alt="close" src="<?php echo $imagePath . 'close.png'; ?>" onclick="document.getElementById('pilotBigImage').style.display='none';" onmouseover="document.getElementById('upcMoreInfoClose').src='<?php echo $imagePath . 'closeHot.png'; ?>';" onmouseout="document.getElementById('upcMoreInfoClose').src='<?php echo $imagePath . 'close.png'; ?>';" />
      </div>
      <div id="pilotBigImageContainer"></div>
    </div>

</div>


<div id="pilotStats" class="shadow" style="display:none;" >

    <div id="pilotStatsDiv">
      <div id="pilotStatsHeader" ></div>
      <div id="pilotStatsControl">
         <span><img class="upcMoreInfoButtons" id="pilotStatsDragImage" src="images/pan_off.png" onmouseover="$('#pilotStatsDragImage').attr('src', 'images/pan_hot.png');" onmouseout="$('#pilotStatsDragImage').attr('src', 'images/pan_off.png');" alt="Drag" /></span>
         <img id="pilotStatsClose" class="upcMoreInfoButtons" alt="close" src="images/close.png" onclick="$('#pilotStats').css('display','none');" onmouseover="$('#pilotStatsClose').attr('src', 'images/closeHot.png');" onmouseout="$('#pilotStatsClose').attr('src', 'images/close.png');" />
      </div>
      <div id="pilotStatsContainer"></div>
    </div>

</div>

