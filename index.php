<?php

$baseURL = 'https://' . $_SERVER['SERVER_NAME'] . str_replace('//','/',dirname($_SERVER['PHP_SELF']) . '/');
$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : ''; 
ini_set('max_execution_time', 60);

//controller
include dirname(__FILE__) . "/controller.php"; 
$controller = new UpcqueryController($act);

// Set header to make ie behave.
header('X-UA-Compatible: IE=edge');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="https://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta name="robots" content="index, follow" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
  <meta name="keywords" content="pilot, planet, raw, images mapping, usgs, nasa, cartography, astrogeology, mars, moon, jupiter, saturn, voyager, cassini" />
  <meta name="description" content="PILOT - Planetary Image Locator Tool - Direct Access to Raw Archive of NASA Spacecraft Images" />
  <title>PILOT - Planetary Image Locator Tool - USGS Astrogeology Science Center - NASA Archive of Raw Planet Images of Moon, Mars, Jupiter, Saturn, Mercury . . .</title>
  <link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />
  <link rel="stylesheet" href="css/pilot.css" type="text/css" />
<?php
//css
foreach($controller->cssArray as $cVal) {
  print('  <link rel="stylesheet" href="' . $cVal . '" type="text/css" />' . "\n");
}
//javascript 
foreach($controller->scriptArray as $sVal) {
  print('  <script type="text/javascript" src="' . $sVal . '"></script>' . "\n");
}
?>
</head>

<body>
<a name="Top" id="Top"></a>
<div id="wrapper">

 <div id="header">
	<div id="banner">
            <a href="https://astrogeology.usgs.gov">
                <div id="usgsLogo" alt="USGS" ></div>
            </a>
            <div id="bannerLogo"><a href="<?php echo $baseURL; ?>"><img alt="Pilot" src="images/pilot-logo.png" /></a></div>

            <div id="upcSearchIdDiv" >
               <input type="hidden" id="upcSearchId" name="upcSearchId" value="Id Search" size="20" />
               <input type="hidden" id="upcSearchType" name="upcSearchType" value="Id Search Type" size="20" />
               <img id="upcSearchIdButton" src="<?php echo $baseURL; ?>images/search.png" />
            </div>

            <div id="nasaLogo" >
            <a href="https://www.usgs.gov/centers/astrogeology-science-center/science/cartography-and-imaging-sciences-node-nasa-planetary">
                <img style="float:left;height:75px;" alt="PDS" src="images/pds_logo-inviso.png" />
                <img style="float:left;" alt="NASA" src="images/nasa-logo.png" />
  
<!--
                <span id="pdsImagingNode"><span style="font-weight:bold;font-size: 1.2em;">PDS</span><br/>Imaging Node</span>
-->
            </a>
            </div>
	</div>
 </div>

  <?php include dirname(__FILE__) . '/views/' . $controller->view . "/default.php"; ?>

  <?php include dirname(__FILE__) . '/views/' . $controller->view . "/panel.php"; ?>

  <div id="footer" >
    <div class="footerLeft">
        <a href="<?php echo $baseURL; ?>">home</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?php echo $baseURL; ?>index.php?view=downloads">downloads</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="https://astrogeology.usgs.gov/maps/contact" >contact</a>&nbsp;&nbsp;|&nbsp;&nbsp;
        <a href="<?php echo $baseURL; ?>index.php?view=faq">FAQ</a>


   </div>

	<div class="footerRight">
	    <span class="footerCredit">&nbsp;PILOT was developed by the USGS Astrogeology Science Center / NASA PDS Cartography and Imaging Science Node&nbsp;</span>
        </div>
  </div>

 </div>
</div>

<?php echo $controller->analyticsTag; ?>

</body>
</html>
