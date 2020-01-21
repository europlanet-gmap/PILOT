<?php

//target settings
$currentUrl = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'] . "?view=map" . $controller->model->urlParams;
$projection = (isset($_REQUEST['pj']) && ($_REQUEST['pj'] != '')) ? $_REQUEST['pj'] : 'cylindrical';

?>

 <div id="full" style="margin:10px;overflow:hidden;" class="shadow">
  <div id="downloadPanel" >

  <h2>Downloads</h2>

  <h3><span style="background: #ffffff;">References to PILOT and the UPC Database</span></h3>
  &nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.hou.usra.edu/meetings/lpsc2015/pdf/1074.pdf"/>LPSC 2015 Abstract 1074: FINDING STEREO PAIRS WITH THE PDS PLANETARY IMAGE LOCATOR TOOL (PILOT)<a/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.lpi.usra.edu/meetings/lpsc2013/pdf/2246.pdf"/>LPSC 2013 Abstract 2246: USING THE PDS PLANETARY IMAGE LOCATOR TOOL (PILOT) TO IDENTIFY AND 
DOWNLOAD SPACECRAFT DATA FOR RESEARCH<a/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.lpi.usra.edu/meetings/lpsc2011/pdf/2214.pdf"/>LPSC 2011 Abstract 2214: IMPROVEMENTS TO THE PDS PLANETARY IMAGE LOCATOR TOOL<a/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.lpi.usra.edu/meetings/lpsc2009/pdf/2002.pdf"/>LPSC 2009 Abstract 2002: STATUS OF THE PDS UNIFIED PLANETARY COORDINATES DATABASE AND THE PLANETARY IMAGE LOCATOR TOOL (PILOT).<a/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.lpi.usra.edu/meetings/lpsc2007/pdf/2022.pdf"/>LPSC 2007 Abstract 2022: THE UNIFIED PLANETARY COORDINATES DATABASE<a/><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a target="_blank" href="http://www.lpi.usra.edu/meetings/lpsc2005/pdf/1369.pdf"/>LPSC 2005 Abstract 1369: UNIFIED PLANETARY COORDINATES SYSTEM: A SEARCHABLE DATABASE OF GEODETIC INFORMATION<a/><br/><br/>
  <h3><span style="background: #ffffff;">PILOT source and UPC Database</span></h3>
   &nbsp;&nbsp;&nbsp;&nbsp;PILOT is now available on the <a style="text-decoration:underline;" href="https://github.com/USGS-Astrogeology/PILOT">USGS Astrogeology Github Site</a>.
<!--
&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://astropedia.astrogeology.usgs.gov/download/Software/PILOT/pilot.tar.gz"/>PILOT source code</a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://planetarymaps.usgs.gov/upcExport/" target="_blank"/>UPC Database Exports</a><br/>
&nbsp;&nbsp;&nbsp;&nbsp;<a href="http://planetarymaps.usgs.gov/upcExport/UPCDatabaseDownloadDocumentation.pdf" target="_blank" />UPC Database Documentation</a><br/>
-->


<?php
//SHAPEFILES AND KML . . .  loop through stats table to find target and instrument list

function outputDownloadRow($html, $iArray, $suffix) {

  $row = '';
  if (empty($iArray)) {return;}
  $url = ' http://planetarynames.wr.usgs.gov/upc_shapefiles/';
  $row .= '<tr><td style="vertical-align:top;text-align:right;background:#ffffff;">' . $html . '</td><td style="border-bottom:1px solid #cccccc;">';
  sort($iArray);
  foreach($iArray as $iVal) {
      $row .= '<a href="' . $url . strtoupper($iVal[4]) . '_' . str_replace(array(' '),'', $iVal[5] . '_' . $iVal[2]) . $suffix . '"><span class="planetSpan"><span class="upcSmallGray">' . str_replace(' ', '&nbsp;',$iVal[0]) . '&nbsp;' . str_replace(' ', '&nbsp;',$iVal[1]) . '&nbsp;&nbsp;&nbsp;&nbsp; </span></span></a>'; 
  }
  $row .= '</td></tr>';
  return($row);
}

$currentTargetName = '';
$currentHTML = '';
$instruments = null;
$shapefileHTML = array();
$kmlHTML = array();

foreach($controller->model->stats as $tKey => $tVal ) {
  $newTargetName = strtolower($tVal['targetname']);
  if ($newTargetName != $currentTargetName) {
    if ($currentTargetName != '') {
      $shapefileHTML[$currentTargetName] = outputDownloadRow($currentHTML, $instruments, ".zip");
      $kmlHTML[$currentTargetName] = outputDownloadRow($currentHTML, $instruments, ".kmz");
      $instruments = null;
    }
    $currentTargetName = $newTargetName;
    $currentHTML =  '<span class="upcHighlightText">' . ucwords($currentTargetName) . '&nbsp;&nbsp;</span>';
  }
  if (($tVal['total'] != 0) && ($tVal['total'] != $tVal['errors'])) {
    $instruments[$tVal['displayname']] = array(0 => $tVal['mission'], 1 => $tVal['displayname'], 2 => $tVal['instrument'], 3 => $tVal['total'], 4 => $tVal['targetname'], 5 => $tVal['spacecraft']);
  }
}
ksort($shapefileHTML);
ksort($kmlHTML);
?>


<br/><br/>
<h3><span style="background: #ffffff;">UPC Shapefiles</span><span class="medText orangeText"> (mapped images only)</span></h3>
<table style="width:900px">
  <?php foreach ($shapefileHTML as $sVal){ echo $sVal; } ?>
</table>

<br/><br/>
<h3><span style="background: #ffffff;">UPC KML Files</span><span class="medText orangeText"> (mapped images only)</span></h3>
<table style="width:900px">
  <?php foreach ($kmlHTML as $kVal){ echo $kVal; } ?>
</table>

<br/><br/><br/><br/>

 </div>
</div>

<script>
window.onload = function() {
  var heightOffset = 140;
  var newHeight = ($(window).height() -heightOffset);
  $('#full').height(newHeight);
};
</script>