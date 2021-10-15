<?php

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
//require_once(dirname(__FILE__) . '/targets_metaHelper.php' );
//require_once(dirname(__FILE__) . '/keywords_metaHelper.php' );


class HistogramHelper {

  var $histogramArray; 
  var $db;
  var $target;
  var $targetWhere;
  var $instrument;
  var $keyword;
  var $keywordDisplayname;
  var $hashKey = '';

  function __construct($target="") {
    $this->target = $target;
    $this->db = new DatabasePG($target);

    if ($this->target == 'unknown') {
      $this->targetWhere = "h.targetid IS NULL";
    } else {
      $targetsHelper = new targetsHelper();
      $currentTargetId = (is_numeric($this->target)) ? $this->target : $targetsHelper->getIdFromName($this->target);
      $this->targetWhere = 'h.targetid = ' . $currentTargetId; 
    }
  }


  function get($instrumentid, $keyword) {
    
    $this->keyword = $keyword;
    $this->instrument = $instrumentid;

    $this->histogramArray = array();
    
    //$keywordsHelper = new KeywordsHelper($this->target);
    //$keywordid = $keywordsHelper->getTypeIdFromKeyword($keyword);
    //$keywordRecord = $keywordsHelper->getRecordFromKeyword($keyword);
    //$keywordid = $keywordRecord['typeid'];
    //$this->keywordDisplayname = $keywordRecord['displayname'];
    /*
    switch($keyword) {
    case 'starttime':
    case 'processdate':
      $this->hashKey = 'year';
      $this->getDate($instrumentid, $keywordid);
      return($this->getJSONRecordDate());
      break;
    case 'meangroundresolution':
      $this->hashKey = 'bucket';
      $this->getResolution($instrumentid, $keywordid);
      return($this->getJSONRecord());
      break;
    default:
      $this->hashKey = 'bucket';
      $this->getDegrees($instrumentid, $keywordid);
      return($this->getJSONRecord());
    }
    */
    return(null);
  }

  //
  function getDate($instrumentid, $keywordid) {

    $table = ($this->keyword == 'starttime') ? 'time_stats' : 'process_time_stats';

    $query = 'SELECT h.*, i.instrumentid, i.displayname AS instrumentname, i.mission ' .
      'FROM ' . $table . ' h ' .
      'JOIN targets_meta AS t USING (targetid) ' .
      'JOIN instruments_meta AS i USING (instrumentid) ' .
      'WHERE ' . $this->targetWhere . ' ' .
      'AND h.instrumentid = ' . $instrumentid . ' ' . 
      'ORDER BY h.year, h.month';
    $this->histogramArray = $this->db->multiDBQueryResultArray($query);
  }


  //
  function getDegrees($instrumentid, $keywordid) {

    $query = 'SELECT h.*, i.instrumentid, i.displayname AS instrumentname, i.mission ' .
      'FROM new_histogram_degrees h ' .
      'JOIN targets_meta AS t USING (targetid) ' .
      'JOIN instruments_meta AS i USING (instrumentid) ' .
      'WHERE ' . $this->targetWhere . ' ' . 
      'AND h.instrumentid = ' . $instrumentid . ' AND h.typeid = ' . $keywordid . ' ' . 
      'ORDER BY h.bucket';
    $this->histogramArray = $this->db->multiDBQueryResultArray($query);
    //echo $query;
  }


  //
  function getResolution($instrumentid, $keywordid) {
  
    $query = 'SELECT h.*, h.bucket_range as range, i.instrumentid, i.displayname AS instrumentname, i.mission ' .
      'FROM histogram_resolution h ' .
      'JOIN targets_meta AS t USING (targetid) ' .
      'JOIN instruments_meta AS i USING (instrumentid) ' .
      'WHERE ' . $this->targetWhere . ' ' . 
      'AND h.instrumentid = ' . $instrumentid . ' AND h.typeid = ' . $keywordid . ' ' . 
      'ORDER BY h.bucket';
    $this->histogramArray = $this->db->multiDBQueryResultArray($query);
    //echo $query;
  }

  //
  function getJSONRecordDate() {

    if (empty($this->histogramArray)) {
      return null;
    }

    $start=9999999;$end=0;$yArray=array();
    foreach($this->histogramArray as $hKey => $hVal) {
      $start = ($start > $hVal[$this->hashKey]) ? $hVal[$this->hashKey] : $start;
      $end = ($start < $hVal[$this->hashKey]) ? $hVal[$this->hashKey]: $start + 1;
      $yArray[$hVal[$this->hashKey]][$hVal['month']]['total'] = $hVal['total'];
      $yArray[$hVal[$this->hashKey]][$hVal['month']]['errors'] = $hVal['errors'];
    }

    //sparklines 
    $spark = '[';
    $sparkE = '[';
    $max = 0;
    for($y = $start; $y <= $end; $y++) {
      for ($m=1;$m <=12;$m++) {
	$spark .= ($spark == '[') ? '' : ',';
	$sparkE .= ($sparkE == '[') ? '' : ',';
	if (isset($yArray[$y][$m])) {
	    $total = $yArray[$y][$m]['total'];
	    $errors = (isset($yArray[$y][$m]['errors'])) ? $yArray[$y][$m]['errors']: 0;
	    $spark .= ($total - $errors);
	    $sparkE .= $errors;
	    $max = (($total - $errors) > $max) ? $total - $errors : $max;
	    $max = ($errors > $max) ? $errors : $max;
	    //$spark .=  $yArray[$y][$m]['total'] - (isset($yArray[$y][$m]['errors']) ? $yArray[$y][$m]['errors']: 0): 0;  
	    //$spark .= (isset($yArray[$y][$m])) ? $yArray[$y][$m]['total'] - (isset($yArray[$y][$m]['errors']) ? $yArray[$y][$m]['errors']: 0): 0;  
	    //$sparkE .= (isset($yArray[$y][$m])) ? $yArray[$y][$m]['errors'] : 0; 
	} else {
	  $spark .= 0;
	  $sparkE .= 0;
	}
      }
    }
    $spark .= ']';
    $sparkE .= ']';

    return('{"keyword":"' . $this->keyword . '","displayname":"' . $this->keywordDisplayname . '","start":"' . $start . '","end":"' . $end . '","spark": ' . $spark . ' , "sparke": ' . $sparkE . ' , "max": ' . $max . '}');
 }


  //
  function getJSONRecord() {

    if (empty($this->histogramArray)) {
      return null;
    }

    $start=9999999;$end=0;$yArray=array();
    foreach($this->histogramArray as $hKey => $hVal) {
      $start = ($start > $hVal[$this->hashKey]) ? $hVal[$this->hashKey] : $start;
      $end = ($start < $hVal[$this->hashKey]) ? $hVal[$this->hashKey]: $end;
      $yArray[$hVal[$this->hashKey]]['total'] = $hVal['total'];
      $yArray[$hVal[$this->hashKey]]['range'] = $hVal['range'];
    }

    //sparklines 
    $spark = '[';
    $sparkE = '[';
    $sparkT = '[';
    $max = 0;
    for($y = $start; $y <= $end; $y++) {
      $spark .= ($spark == '[') ? '' : ',';
      $sparkT .= ($sparkT == '[') ? '' : ',';
      $spark .= (isset($yArray[$y])) ? $yArray[$y]['total'] : 0;  
      $sparkT .= (isset($yArray[$y]) && isset($yArray[$y]['range'])) ? '"' . $yArray[$y]['range'] . '"' : 0;
      $max = (isset($yArray[$y]) && ($yArray[$y]['total'] > $max)) ? $yArray[$y]['total'] : $max;
    }
    $spark .= ']';
    $sparkE .= ']';
    $sparkT .= ']';

    return('{"keyword":"' . $this->keyword . '","displayname":"' . $this->keywordDisplayname . '","start":"' . $start . '","end":"' . $end . '","spark": ' . $spark . ' , "sparke": [], "sparkt": ' . $sparkT . ' , "max": ' . $max . '}');
 }

}

?>