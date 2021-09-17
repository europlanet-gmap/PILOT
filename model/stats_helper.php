<?php

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );


class StatsHelper {

  var $timeStats; //array of timeStats
  var $stats; //array of timeStats

  function getInstrumentsFromTarget($name) {

    $statArray = array();
    $db = new DatabasePG();
    $query = 'SELECT * ' .
      'FROM stats ' .
      "WHERE (UPPER(targetname)=UPPER('" . $name . "')) " . //OR (UPPER(system)=UPPER('" . $name . "')) " .
      'ORDER BY targetname';
    $db->query($query);
    $statsArray = $db->getResultArray();
    //print_r($statsArray);
    return($statsArray);

  }


  function getTargetArray() {

    $statArray = array();
    $db = new DatabasePG();
    $query = 'SELECT targetname, system, sum(image_count) as image_count ' .
      'FROM stats ' .
      'GROUP BY targetname, system ' .
      'ORDER BY system, targetname';
    $db->query($query);
    $statsArray = $db->getResultArray();
    //print_r($statsArray);
    return($statsArray);

  }



  function getUPCTargetArray() {

    $statArray = array();
    $db = new DatabasePG();
    $query = 'SELECT * ' .
      'FROM stats ' .
      'ORDER BY targetname';
    $db->query($query);
    $statsArray = $db->getResultArray();
    //print_r($statsArray);
    return($statsArray);

  }



  function getValidTargets($targets) {

    $validTargets = array();
    foreach($targets as $tVal) {
      if (isset($tVal['image_count']) && ($tVal['image_count'] > 0)) {
	if (!in_array($tVal['targetname'], $validTargets)) {
	  $validTargets[] = $tVal['targetname'];
	}
	if (!in_array($tVal['system'], $validTargets)) {
	  $validTargets[] = $tVal['system'];
	}
      }
    }
    return(implode(",",$validTargets));
  }


  function getSubtargetsFromTarget($name) {

    $statsArray = array();
    $db = new DatabasePG();
    $query = 'SELECT t.targetname, sum(s.image_count) AS image_count ' .
      'FROM targets_meta t JOIN stats s USING (targetid) ' .
      "WHERE (UPPER(t.system)=UPPER('" . $name . "')) and (UPPER(t.targetname) != UPPER('" . $name . "')) " .
      'GROUP BY t.targetname';
    $db->query($query);
    $statsArray = $db->getResultArray();
    return($statsArray);
  }


function getJSONStats2($type) {

    require_once(dirname(__FILE__) . '/../configure.php' );
    date_default_timezone_set('America/Phoenix');
    $config = new Config();
    $upcStatsURL = ($type == 'target') ? $config->upcTargetStatsURL : $config->upcVolumeStatsURL;
    $json = file_get_contents($upcStatsURL);
    //print_r($json);
    return($json);
  }


function getStats() {

    $statArray = array();
    $db = new DatabasePG();
    $query = 'SELECT * ' .
      'FROM new_stats ns ' .
      'ORDER BY system, targetname, mission, instrument, start_date';
    $statsArray = $db->multiDBQueryResultArray($query);
    $this->stats = $statsArray;
    return($statsArray);

  }


function getJSONUnknownStats() {

    $statArray = array();
    $db = new DatabasePG();
    $query = "SELECT count(d.upcid) as errors, 0 as total, -1 as targetid, 'untargeted' as targetname, 'untargeted' as system,  d.instrumentid, i.mission, i.displayname, i.instrument " .
      'FROM datafiles d ' .
      'JOIN instruments_meta AS i USING (instrumentid) ' .
      'WHERE d.targetid IS NULL ' .
      'GROUP BY d.instrumentid, i.mission, i.displayname, i.instrument ' .
      'ORDER BY i.mission, i.displayname, i.instrument';
    $statsArray = $db->multiDBQueryResultArray($query);
    //$db->query($query);
    //$statsArray = $db->getResultArray();
    $JSONstats = json_encode($statsArray);
    return($JSONstats);
  }



function getJSONStats() {

  require_once(dirname(__FILE__) . '/../tools/json.php' );
  
  if (empty($this->stats)) {
    $this->getStats();
  }
  $JSONstats = json_encode($this->stats);
  return($JSONstats);

}



function getTimeStats() {

    $statArray = array();
    $db = new DatabasePG();
    $query = 'SELECT ts.*, t.system, t.displayname AS targetname, i.displayname AS instrumentname, i.mission ' .
      'FROM time_stats ts ' .
      'JOIN targets_meta AS t USING (targetid) ' .
      'JOIN instruments_meta AS i USING (instrumentid) ' .
      'ORDER BY t.system, t.displayname, ts.instrumentid, ts.year, ts.month';
    $statsArray = $db->multiDBQueryResultArray($query);
    $this->timeStats = $statsArray;
    return($statsArray);

  }

 function getJSONTimeStats() {

    require_once(dirname(__FILE__) . '/../tools/json.php' );

    if (empty($this->timeStats)) {
      $this->getTimeStats();
    }

    $iSparks = array();$iSparksE = array();
    foreach($this->timeStats as $tKey => $tVal) {
      if (!isset($iTotal[$tVal['targetname']][$tVal['mission']])) {
	$iTotal[$tVal['targetname']][$tVal['mission']]['total'] = 0;
	$iTotal[$tVal['targetname']][$tVal['mission']]['errors'] = 0;
	$iTotal[$tVal['targetname']][$tVal['mission']]['startyear'] = $tVal['year'];
	$iTotal[$tVal['targetname']][$tVal['mission']]['endyear'] = $tVal['year'];
      }
      $iTotal[$tVal['targetname']][$tVal['mission']]['total'] += $tVal['total'];
      $iTotal[$tVal['targetname']][$tVal['mission']]['errors'] += $tVal['errors'];
      if ($iTotal[$tVal['targetname']][$tVal['mission']]['startyear'] > $tVal['year']) {
	$iTotal[$tVal['targetname']][$tVal['mission']]['startyear'] = $tVal['year'];
      }
      if ($iTotal[$tVal['targetname']][$tVal['mission']]['endyear'] < $tVal['year']) {
	$iTotal[$tVal['targetname']][$tVal['mission']]['endyear'] = $tVal['year'];
      }
      if (!isset($iTotal[$tVal['targetname']][$tVal['mission']]['i'][$tVal['instrumentname']])) {
	$iTotal[$tVal['targetname']][$tVal['mission']]['i'][$tVal['instrumentname']]['total'] = 0;
	$iTotal[$tVal['targetname']][$tVal['mission']]['i'][$tVal['instrumentname']]['errors'] = 0;
      }
      
      $iTotal[$tVal['targetname']][$tVal['mission']]['i'][$tVal['instrumentname']]['total'] += $tVal['total'];
      $iTotal[$tVal['targetname']][$tVal['mission']]['i'][$tVal['instrumentname']]['errors'] += $tVal['errors'];
      //$iSparks[$tVal['mission']][$tVal['instrumentname']][$tVal['year']][$tVal['month']] = '[' . ($tVal['total'] - $tVal['errors']) . ',' . $tVal['errors'] . ']';
      $iSparks[$tVal['targetname']][$tVal['mission']][$tVal['instrumentname']][$tVal['year']][$tVal['month']] = $tVal['total'];
      $iSparksE[$tVal['targetname']][$tVal['mission']][$tVal['instrumentname']][$tVal['year']][$tVal['month']] = $tVal['errors'];
    }
    foreach($iTotal as $tKey => $tVal) {
      foreach($tVal as $mKey => $mVal) {
	$iTotal[$tKey][$mKey]['total'] = number_format($mVal['total']);
	$iTotal[$tKey][$mKey]['errors'] = number_format($mVal['errors']);
	foreach($mVal['i'] as $iKey => $iVal) {
	    $iTotal[$tKey][$mKey]['i'][$iKey]['total'] = number_format($iVal['total'] - $iVal['errors']);
	    $iTotal[$tKey][$mKey]['i'][$iKey]['errors'] = number_format($iVal['errors']);
	    //sparklines (monthly charts)
	    $iTotal[$tKey][$mKey]['i'][$iKey]['spark'] = '[';
	    $iTotal[$tKey][$mKey]['i'][$iKey]['sparke'] = '[';
	    for($y = $iTotal[$tKey][$mKey]['startyear']; $y <= $iTotal[$tKey][$mKey]['endyear']; $y++) {
	      for ($m=1;$m <=12;$m++) {
		$iTotal[$tKey][$mKey]['i'][$iKey]['spark'] .= ($iTotal[$tKey][$mKey]['i'][$iKey]['spark'] == '[') ? '' : ',';
		$iTotal[$tKey][$mKey]['i'][$iKey]['sparke'] .= ($iTotal[$tKey][$mKey]['i'][$iKey]['sparke'] == '[') ? '' : ',';
		$iTotal[$tKey][$mKey]['i'][$iKey]['spark'] .= (isset($iSparks[$tKey][$mKey][$iKey][$y][$m])) ? $iSparks[$tKey][$mKey][$iKey][$y][$m] : 0;   //'[0,0]'; 
		$iTotal[$tKey][$mKey]['i'][$iKey]['sparke'] .= (isset($iSparksE[$tKey][$mKey][$iKey][$y][$m])) ? $iSparksE[$tKey][$mKey][$iKey][$y][$m] : 0;   //'[0,0]'; 
	      }
	    }
	    $iTotal[$tKey][$mKey]['i'][$iKey]['spark'] .= ']';
	    $iTotal[$tKey][$mKey]['i'][$iKey]['sparke'] .= ']';
	}
      }
    }


    //*** output JSON data structure ***/
    //$JSONstats = json_encode($this->timeStats);
    $JSONstats = json_encode($iTotal);
    return($JSONstats);
 }


}

?>