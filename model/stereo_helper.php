<?php

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );

class StereoHelper {

  var $matchArray = array();
  var $limits = array();
  var $datafilesTable='datafiles';
  var $target;
  var $targetWhere;
  var $instrument;
  var $keyword;
  var $hashKey = '';

  function __construct($target="") {
    $this->target = $target;
    $this->db = new DatabasePG('new');
  }


  function get($stereos, $order) {
    
    //get keywords
    //$KEYSELECT='';$KEYJOIN='';$KEYWHERE='';
    //$keywordsHelper = new KeywordsHelper($this->target);

    $KEYSELECT='';
    $searchwords = array('emissionangle','incidenceangle','minimumemission','maximumemission','minimumincidence','maximumincidence','meangroundresolution','solarlongitude','surfacearea');
    $keywords2 = array('centerlatitude','centerlongitude','centerradius','subsolarlatitude','subsolarlongitude','subsolargroundazimuth','subsolarazimuth','subspacecraftgroundazimuth','subspacecraftlongitude','subspacecraftlatitude','targetcenterdistance');
    foreach ($keywords2 as $kVal) {
      $limits[$kVal] = array('min'=>100000, 'max'=>0);
    };
    foreach ($searchwords as $sVal) {
      $limits[$sVal] = array('min'=>100000, 'max'=>0);
    }

    $upcids = explode(',',$stereos);
    //pull info
    $i=0;
    foreach ($upcids as $upcid) { 

      $query = 'SELECT d.upcid, d.productid, d.source, ' .
	'i.instrument, i.displayname, ' . 
	"j.jsonkeywords, " .
	's.*, ST_AsText(s.isisfootprint) AS footprint  ' . 
	'FROM datafiles as d ' .
	"JOIN search_terms s ON (d.upcid = s.upcid) " . 
	"JOIN instruments i ON (d.instrumentid = i.instrumentid) " .
	"JOIN json_keywords j ON (d.upcid = j.upcid) " .
	'WHERE d.upcid =  ' . $upcid . ' ORDER BY d.productid';
      //echo $query;
      $this->db->query($query);
      $record = $this->db->getResultRow();
      $this->stereoArray[$i] = $record;

      $jsonArray = json_decode($this->stereoArray[$i]['jsonkeywords'], true);
      $geoArray = array_column($jsonArray, 'geometry');
      foreach ($keywords2 as $kVal) {
	$this->stereoArray[$i][$kVal] = $geoArray[0][$kVal];
      }
      $this->stereoArray[$i]['surfacearea'] = $jsonArray['caminfo']['polygon']['surfacearea'][0];;

      //set limits
      $allkeywords = $searchwords + $keywords2;
      foreach ($allkeywords as $key) {
	$limits[$key]['min'] = (floor($this->stereoArray[$i][$key]) < $limits[$key]['min']) ? floor($this->stereoArray[$i][$key]) : $limits[$key]['min'];
	$limits[$key]['max'] = (ceil($this->stereoArray[$i][$key]) > $limits[$key]['max']) ? ceil($this->stereoArray[$i][$key]) : $limits[$key]['max'];
      }

      $i++;
    }

    //print_r($this->stereoArray);
    //die();


    //compute intersects
    $sLength = count($this->stereoArray);
    $intersects = array();
    $areaMax = 0;
    $index = 0;

    require_once(dirname(__FILE__) . '/../tools/geoPHP/geoPHP.inc' );
    require_once(dirname(__FILE__) . '/../tools/planetRadii.php' );
    foreach ($this->stereoArray as $sKey => $sVal) {

      for ($i=($sKey+1); $i < $sLength; $i++) {

	if (($sVal['footprint'] == '') || ($this->stereoArray[$i]['footprint'] == '')) {continue;}
	
	$aGeo = "'" . $sVal['footprint'] . "'::geometry";
	$bGeo = "'" . $this->stereoArray[$i]['footprint']. "'::geometry";
	$query = "SELECT ST_AsText(ST_Intersection(" . $aGeo . "," . $bGeo . ")) as intersect ";
	//, ST_AsText(ST_Centroid(ST_Intersection(" . $aGeo . "," . $bGeo . "))) as centroid ";
	//echo $query;
	$this->db->query($query);
	$row = $this->db->getResultRow();
	if ($row['intersect'] == 'GEOMETRYCOLLECTION EMPTY') {continue;}
	if (strpos($row['intersect'], 'POLY') === false) {continue;} //only work when intersect is poly
	if (strpos($row['intersect'], 'MULTI') !== false) {
	  //dateline crosser's are multipolygon's. . . union them to find centroid
	  //$query = "SELECT ST_AsText(multi) AS intersect FROM ST_Union(ARRAY(SELECT (ST_DUMP(ST_MakeValid(ST_Shift_Longitude(ST_GeomFromEWKT('" . $row['intersect'] . "'))))).geom AS geom)) AS multi";
	  $query = "SELECT ST_AsText(multi) AS intersect FROM ST_Union(ARRAY(SELECT (ST_DUMP(ST_MakeValid(ST_ShiftLongitude(ST_GeomFromEWKT('" . $row['intersect'] . "'))))).geom AS geom)) AS multi";
	  $this->db->query($query);
	  $rowUnion = $this->db->getResultRow();
	  $row['intersectUnion'] = $rowUnion['intersect'];
	}
	$prefix1 = (strnatcmp($this->stereoArray[$i]['productid'], $sVal['productid']) < 0) ? 'a' : 'b';
	$prefix2 = ($prefix1 == 'a') ? 'b' : 'a';
	$row['upcid' . $prefix1] = $this->stereoArray[$i]['upcid'];
	$row['upcid' . $prefix2] = $sVal['upcid'];
	$row['key' . $prefix1] = $i;
	$row['key' . $prefix2] = $sKey;
	$row['productid' . $prefix1] = $this->stereoArray[$i]['productid'];
	$row['productid' . $prefix2] = $sVal['productid'];

	//get area
	$radius = $planetRadii[ucfirst(strtolower($this->target))] / 1000;
	$intersectPoly = isset($row['intersectUnion']) ? $row['intersectUnion'] : $row['intersect'];
	$polygon = geoPHP::load($intersectPoly,'wkt');
	$row['area'] = round($polygon->geodesicArea($radius),2); 

	//get centroid of intersect
	$icentroid = $polygon->geodesicCentroid($radius);
        $row['centroid'] = $icentroid->asText(); 

	$areaMax = ($areaMax > $row['area']) ? $areaMax : $row['area']; 

	//shadow tip distance
	$x1 = tan($this->stereoArray[$sKey]['minimumincidence']) * cos($this->stereoArray[$sKey]['subsolargroundazimuth']);
	$y1 = tan($this->stereoArray[$sKey]['minimumincidence']) * sin($this->stereoArray[$sKey]['subsolargroundazimuth']);
	$x2 = tan($this->stereoArray[$i]['minimumincidence']) * cos($this->stereoArray[$i]['subsolargroundazimuth']);
	$y2 = tan($this->stereoArray[$i]['minimumincidence']) * sin($this->stereoArray[$i]['subsolargroundazimuth']);
	$p1 = new Point($x1, $y1);
	$p2 = new Point($x2, $y2);
	$line = new LineString(array($p1, $p2));
	$stdistance = $line->greatCircleLength($radius);
	$row['shadowtipdistance'] = round($stdistance,2);

	//convergence angle
	/*
	$instrumentidPushBrooms = array(2,3,4,13,32);
	$useBodyFixedFormula = (in_array($this->stereoArray[$sKey]['instrumentid'], $instrumentidPushBrooms) && in_array($this->stereoArray[$i]['instrumentid'], $instrumentidPushBrooms)); 
	if ($useBodyFixedFormula) {
	  //convergence body-fixed (Ken's formula)
	  //SURFACE POINT BF XYZ
	  //image 1
	  $c1X = ($this->stereoArray[$sKey]['centerradius'] /1000) * cos(deg2rad($this->stereoArray[$sKey]['centerlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['centerlatitude']));
	  $c1Y = ($this->stereoArray[$sKey]['centerradius'] /1000) * sin(deg2rad($this->stereoArray[$sKey]['centerlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['centerlatitude']));
	  $c1Z = ($this->stereoArray[$sKey]['centerradius'] /1000) * sin(deg2rad($this->stereoArray[$sKey]['centerlatitude']));
	  //image 2
	  $c2X = ($this->stereoArray[$i]['centerradius'] /1000) * cos(deg2rad($this->stereoArray[$i]['centerlongitude'])) * cos(deg2rad($this->stereoArray[$i]['centerlatitude']));
	  $c2Y = ($this->stereoArray[$i]['centerradius'] /1000) * sin(deg2rad($this->stereoArray[$i]['centerlongitude'])) * cos(deg2rad($this->stereoArray[$i]['centerlatitude']));
	  $c2Z = ($this->stereoArray[$i]['centerradius'] /1000) * sin(deg2rad($this->stereoArray[$i]['centerlatitude']));
	  //SPACECRAFT LOCATION BF XYZ
	  //image 1
	  $s1X = $this->stereoArray[$sKey]['targetcenterdistance'] * cos(deg2rad($this->stereoArray[$sKey]['subspacecraftlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['subspacecraftlatitude']));
	  $s1Y = $this->stereoArray[$sKey]['targetcenterdistance'] * sin(deg2rad($this->stereoArray[$sKey]['subspacecraftlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['subspacecraftlatitude']));
	  $s1Z = $this->stereoArray[$sKey]['targetcenterdistance'] * sin(deg2rad($this->stereoArray[$sKey]['subspacecraftlatitude']));
	  //image 2
	  $s2X = $this->stereoArray[$i]['targetcenterdistance'] * cos(deg2rad($this->stereoArray[$i]['subspacecraftlongitude'])) * cos(deg2rad($this->stereoArray[$i]['subspacecraftlatitude']));
	  $s2Y = $this->stereoArray[$i]['targetcenterdistance'] * sin(deg2rad($this->stereoArray[$i]['subspacecraftlongitude'])) * cos(deg2rad($this->stereoArray[$i]['subspacecraftlatitude']));
	  $s2Z = $this->stereoArray[$i]['targetcenterdistance'] * sin(deg2rad($this->stereoArray[$i]['subspacecraftlatitude']));
	  //VIEWING VECTOR 
	  // image 1 (v1 = s1 - c1)
	  $v1X = $s1X - $c1X;
	  $v1Y = $s1Y - $c1Y;
	  $v1Z = $s1Z - $c1Z;
	  // image 2 (v2 = s2 - c2)
	  $v2X = $s2X - $c2X;
	  $v2Y = $s2Y - $c2Y;
	  $v2Z = $s2Z - $c2Z;
	  //CONVERGENCE
	  $convNum = ($v1X * $v2X) + ($v1Y * $v2Y) +($v1Z * $v2Z);
	  $convDen = sqrt(pow($v1X,2) + pow($v1Y,2) + pow($v1Z,2)) * sqrt(pow($v2X,2) + pow($v2Y,2) + pow($v2Z,2));
	  $row['convergenceangle'] = rad2deg(acos($convNum / $convDen));
	} else {
	*/

	  //normal convergence angle formula
	  $getConvergence = function($e1, $e2, $a1, $a2) {
	    $e1 = deg2rad($e1);
	    $e2 = deg2rad($e2);
	    $a1 = deg2rad($a1);
	    $a2 = deg2rad($a2);
	    $cAngle = rad2deg(acos((cos($e1) * cos($e2)) + (sin($e1) * sin($e2) * cos($a1-$a2))));
	    return (round($cAngle,2));
	  };
	  $row['convergenceangle'] = $getConvergence($this->stereoArray[$sKey]['emissionangle'], $this->stereoArray[$i]['emissionangle'],$this->stereoArray[$sKey]['subspacecraftgroundazimuth'], $this->stereoArray[$i]['subspacecraftgroundazimuth']);

	  //}

	//solar separation
	  /*
	$computeSolarSeparation = ($useBodyFixedFormula && isset($planetSunDistance[ucfirst(strtolower($this->target))]));
	if ($computeSolarSeparation) {
	  $psd = $planetSunDistance[ucfirst(strtolower($this->target))];
	  $icenterlon = $icentroid->x(); 
	  $icenterlat = $icentroid->y();
	  //intersect surface point bf xyz
	  $iX = ($this->stereoArray[$sKey]['centerradius'] /1000) * cos(deg2rad($icenterlon)) * cos(deg2rad($icenterlat));
	  $iY = ($this->stereoArray[$sKey]['centerradius'] /1000) * sin(deg2rad($icenterlon)) * cos(deg2rad($icenterlat));
	  $iZ = ($this->stereoArray[$sKey]['centerradius'] /1000) * sin(deg2rad($icenterlat));
	  //sun location bf xyz
	  //image1
	  $ss1X = $psd * cos(deg2rad($this->stereoArray[$sKey]['subsolarlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['subsolarlatitude']));
	  $ss1Y = $psd * sin(deg2rad($this->stereoArray[$sKey]['subsolarlongitude'])) * cos(deg2rad($this->stereoArray[$sKey]['subsolarlatitude']));
	  $ss1Z = $psd * sin(deg2rad($this->stereoArray[$sKey]['subsolarlatitude']));
	  //image2
	  $ss2X = $psd * cos(deg2rad($this->stereoArray[$i]['subsolarlongitude'])) * cos(deg2rad($this->stereoArray[$i]['subsolarlatitude']));
	  $ss2Y = $psd * sin(deg2rad($this->stereoArray[$i]['subsolarlongitude'])) * cos(deg2rad($this->stereoArray[$i]['subsolarlatitude']));
	  $ss2Z = $psd * sin(deg2rad($this->stereoArray[$i]['subsolarlatitude']));
	  //solar sep viewing vector 
	  // image 1 (v1 = s1 - i)
	  $ssv1X = $ss1X - $iX;
	  $ssv1Y = $ss1Y - $iY;
	  $ssv1Z = $ss1Z - $iZ;
	  // image 2 (v2 = s2 - i)
	  $ssv2X = $ss2X - $iX;
	  $ssv2Y = $ss2Y - $iY;
	  $ssv2Z = $ss2Z - $iZ;
	  //solar separation angle
	  $ssNum = ($ssv1X * $ssv2X) + ($ssv1Y * $ssv2Y) +($ssv1Z * $ssv2Z);
	  $ssDen = sqrt(pow($ssv1X,2) + pow($ssv1Y,2) + pow($ssv1Z,2)) * sqrt(pow($ssv2X,2) + pow($ssv2Y,2) + pow($ssv2Z,2));
	  $row['solarseparationangle'] = rad2deg(acos($ssNum / $ssDen));
	}
	*/

	//parallax (from Bonnie's Perl script)
	$parX1 = (-1)*tan(deg2rad($this->stereoArray[$sKey]['emissionangle']))*(cos(deg2rad($this->stereoArray[$sKey]['subspacecraftgroundazimuth'])));
	$parX2 = (-1)*tan(deg2rad($this->stereoArray[$i]['emissionangle']))*(cos(deg2rad($this->stereoArray[$i]['subspacecraftgroundazimuth'])));
	$parY1 = tan(deg2rad($this->stereoArray[$sKey]['emissionangle']))*sin(deg2rad($this->stereoArray[$sKey]['subspacecraftgroundazimuth']));
	$parY2 = tan(deg2rad($this->stereoArray[$i]['emissionangle']))*sin(deg2rad($this->stereoArray[$i]['subspacecraftgroundazimuth']));

	//shadow (from Bonnie's Perl script)
	$shadX1 = (-1)*tan(deg2rad($this->stereoArray[$sKey]['incidenceangle']))*(cos(deg2rad($this->stereoArray[$sKey]['subsolargroundazimuth'])));
	$shadX2 = (-1)*tan(deg2rad($this->stereoArray[$i]['incidenceangle']))*(cos(deg2rad($this->stereoArray[$i]['subsolargroundazimuth'])));
	$shadY1 = tan(deg2rad($this->stereoArray[$sKey]['incidenceangle']))*sin(deg2rad($this->stereoArray[$sKey]['subsolargroundazimuth']));
	$shadY2 = tan(deg2rad($this->stereoArray[$i]['incidenceangle']))*sin(deg2rad($this->stereoArray[$i]['subsolargroundazimuth']));

	//ratios (from Bonnie's Perl script)
	$row['baseheightratio'] = sqrt(pow($parX1-$parX2,2)+pow($parY1-$parY2,2));
	$row['shadowdifference'] = sqrt(pow($shadX1-$shadX2,2)+pow($shadY1-$shadY2,2));
	$gsd = sqrt((pow($this->stereoArray[$sKey]['meangroundresolution'],2)+pow($this->stereoArray[$i]['meangroundresolution'],2))/2);
	$row['expectedprecision'] = ($row['baseheightratio'] > 0) ? (.2*($gsd/$row['baseheightratio'])) : 0;

	//differences
	$row['incidenceangledifference'] = round(abs($this->stereoArray[$sKey]['minimumincidence'] - $this->stereoArray[$i]['minimumincidence']),2);
	$row['solarazimuthdifference'] = round(abs($this->stereoArray[$sKey]['subsolargroundazimuth'] - $this->stereoArray[$i]['subsolargroundazimuth']),2);

	$row['orgKey'] = $index; //store before sorting
	$index++;
	$intersects[] = $row;
      }
    }

    //extra limits
    $limits['area']['max'] = $areaMax;
    $limits['area']['min'] = 0;
    if ($computeSolarSeparation) {
      $limits['solarseparationangle']['min'] = 0;
      $limits['solarseparationangle']['max'] = 180;
    }

    //order
    if (count($intersects) > 0 ) {
      switch($order) {
      case 'conv':
	foreach ($intersects as $iKey => $iVal) {
	  $iConv[$iKey]  = $iVal['convergenceangle'];
	}
	array_multisort($iConv, SORT_ASC, $intersects);
	break;
      case 'productid':
	foreach ($intersects as $iKey => $iVal) {
	  $iPida[$iKey]  = $iVal['productida'];
	  $iPidb[$iKey]  = $iVal['productidb'];
	}
	array_multisort($iPida, SORT_ASC, $iPidb, SORT_ASC, $intersects);
	break;
      case 'area':
      default:
	foreach ($intersects as $iKey => $iVal) {
	  $iArea[$iKey]  = $iVal['area'];
	}
      array_multisort($iArea, SORT_DESC, $intersects);
      }
    }

    //*** output JSON data structure ***/
    require_once(dirname(__FILE__) . '/../tools/json.php' );
    $stereoOutput = @json_encode(array($this->stereoArray, $intersects, $limits));
    return($stereoOutput);

  }

}

?>