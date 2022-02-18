<?php
 
/*

  upcquery - model file by mbailen@usgs.gov

*/


require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
require_once(dirname(__FILE__) . '/targets_helper.php' );
require_once(dirname(__FILE__) . '/json_keywords_helper.php' );
require_once(dirname(__FILE__) . '/datafiles_helper.php' );


class upcQuery {

  //generation variables
  var $wkt; //well-known text
  var $tlx, $tly, $brx, $bry; //bounding box variables
  var $target; //target
  var $queryType; //sets geofunction
  var $mappedArray = array(); //instruments to use for upc images
  var $unmappedArray = array(); //instruments to for unmapped images
  var $constraintArray; //constraints
  var $searchIdArray; //search Id constraints
  var $searchSelect; //type of Id to search on (upcid, productid. . )
  var $dateCheckArray; //date check
  var $productId; //productid substr
  var $output; //output format
  var $isisId; //isisid substr
  var $upcId; //upcid substr
  var $groupBy; //group results by (hash table)
  var $groupDir; //Asc or Desc
  var $upcCrossingDateline; //boolean, whether dateline is crossed (0-360)
  var $renderList; //list of productids to restrict result set to
  var $whitelist; //boolean for renderlist. . . whether to select/unselect images in list
  var $downloadSet; //how much to grab for downloads (all, page, renderlist)
  var $upcDatabaseSchema; //table schema - affects _buildQuery
  var $limitNumber; 
  var $fullMapWKT; //grabbed from config

  //result variables
  var $db;
  var $result; 
  var $_data;
  var $queryText;
  var $countText;
  var $time;
  var $explain;
  var $total = null;
  var $footprints;
  //var $resultKeys;
  var $hashArray;
  var $hashItem;
  var $viewParams;
  var $urlParams;
  var $multiDBResult;


  function __construct($target="")  {

    $this->db = null;
    $this->countText = null;
    $this->queryText = null;

    //create db connection
    $this->target = $target;
    $this->db = new DatabasePG($target,true);

    $this->multiDBResult = false;
  }


  //
  //  build query for meta schema of tables... 
  //
  function _buildQuery() {

    //config stuff
    $config = new Config();
    $this->fullMapWKT = $config->fullMapWKT;
    $this->thumbnailURL = $config->thumbnailURL;


    //without instrument, bomb
    //if (empty($this->mappedArray) && empty($this->unmappedArray)) return('');


    //CHECK FOR SELECT/UNSELECT LIST
    if (isset($this->select) && ($this->select != '')) {
      //select list for download. . .expand limit
      $this->renderList = explode(',',$this->select);
      $this->whitelist = true;
    } else if (isset($this->unselect) && ($this->unselect != '')) {
      //unselect list for download. . .expand limit
      $this->renderList = explode(',',$this->unselect);
      $this->whitelist = false;
    }

    //SET LIMIT
    $limitClause = '';
    $this->limitNumber = (isset($this->output) && ($this->output != '')) ? $config->maxQueryDownload : $this->render; 
    if ($this->limitNumber) {
      $limitClause = 'LIMIT ' . $this->limitNumber;
      if (isset($this->step) && is_numeric($this->step) && ($this->step > 0)) {
	$limitClause .= ' OFFSET ' . ($this->step -1) . ' ';
      }
    }

    //TARGET AND DATEFILES SETTINGS
    $fromClause =  "FROM datafiles as d JOIN search_terms AS s ON (d.upcid = s.upcid) ";
    $targetSelect = '';
    $targetJoin = '';
    if (!isset($this->target) || ($this->target == '')) {
      //if no target, need to pull it. . . 
      $targetSelect = ', tm.targetname ';
      $targetJoin = 'LEFT JOIN targets tm ON (d.targetid = tm.targetid) '; 
      $targetWhereClause='';
    } else if ($this->target == 'untargeted') {
      $targetWhereClause = 'AND d.targetid IS NULL ';
    } else {
      $targetsHelper = new targetsHelper();
      $currentTargetId = (is_numeric($this->target)) ? $this->target : $targetsHelper->getIdFromName($this->target);
      $targetWhereClause = 'AND d.targetid= ' . $currentTargetId . ' ';
    }


    //GEO CLAUSE
    $geoSelect = ", ST_AsText(s.isisfootprint) AS footprint ";    
    $geoJoin = '';
    if ($this->wkt == $this->fullMapWKT || $this->wkt == '') {
      //no bounding box, js returned max box
      $geoWhereClause = '';
    } else {
      //geo function
      switch($this->queryType) {
      case 'intersects':
	$geoFunction = 'ST_Intersects';
	break;
      case 'within':
      default:
	$geoFunction = 'ST_Within';
	break;
      }
      //bb check
      $matchWKT = (!isset($this->astroBBDatelineWKT) || ($this->astroBBDatelineWKT == '')) ? $this->wkt :  $this->astroBBDatelineWKT;
      $geoWhereClause = 'AND ' . $geoFunction . "(s.isisfootprint, ST_GeomFromText('" . $matchWKT . "')) ";
    } 


    //INSTRUMENT CLAUSE
    $instrumentWhereClause ='';
    $instrumentSelect = ',i.instrument, i.displayname ';
    $instrumentJoin = 'JOIN instruments i ON (d.instrumentid = i.instrumentid) ';
    if (!empty($this->mappedArray) || !empty($this->unmappedArray)) {
      $instrumentWhereClause = 'AND (';
      $instrumentsHelper = new InstrumentsHelper($this->target);
      $iArray = array_unique(array_merge($this->mappedArray, $this->unmappedArray));
      foreach ($iArray as $iKey => $iVal) {
	$currentInstrumentId = is_numeric($iVal) ? $iVal : $instrumentsHelper->getIdFromDisplayName($iVal);
	$cleanInstrumentName = str_replace(' ','',$iVal);
	$instrumentWhereClause .= ($iKey > 0) ? "OR (d.instrumentid = '" . $currentInstrumentId . "' " : "(d.instrumentid = '" . $currentInstrumentId . "' ";
	$mappedSearch = in_array($iVal, $this->mappedArray);
	$unmappedSearch = in_array($iVal, $this->unmappedArray);
	if ($mappedSearch && !$unmappedSearch) {
	  //$instrumentWhereClause .= "AND s.err_flag = 'f') ";
	  $instrumentWhereClause .= "AND s.isisfootprint IS NOT NULL) ";
	} else if ($unmappedSearch && !$mappedSearch) {
	  //$instrumentWhereClause .= "AND s.err_flag = 't') ";
	  $instrumentWhereClause .= "AND s.isisfootprint IS NULL) ";
	} else if ($unmappedSearch && $mappedSearch) {
 	  //$instrumentWhereClause .= ') '; 
	}
      }
      $instrumentWhereClause .=  ') ';
    }


    //THUMBNAIL CLAUSE
    $thumbnailWhereClause ='';$thumbnailSelect='';$thumbnailJoin='';
    if (true) {
      $jsonKeywordsHelper = new JsonKeywordsHelper($this->target);
      $thumbnailSelect = ", thumbnailTable.jsonkeywords::jsonb->'thumbnail' AS thumbnailurl ";
      $thumbnailJoin = 'LEFT JOIN json_keywords AS thumbnailTable ON (d.upcid = thumbnailTable.upcid) '; 
      $thumbnailWhereClause = '';
   }

    //ORDER BY CLAUSE
    $orderBySelect = '';
    $orderByJoin = '';
    $orderByWhereClause = '';
    $orderByClause = '';
    switch ($this->groupBy) {
    case 'instrument':
      $orderByClause = 'ORDER BY i.instrument, s.pdsproductid ';
      break;
    case 'productid':
    case 'pdsproductid':
      $orderByClause = 'ORDER BY s.pdsproductid ';
      break;
    default:
      //keyword-based order
      $groupByKeyword = $this->groupBy;
      $orderByJoin = '';
      $orderByWhereClause = '';
      $orderBySelect = ', s.' . $groupByKeyword . ' ';
      $orderByClause = 'ORDER BY s.' . $groupByKeyword . ' ' . $this->groupDir . ' ';

      break;
    }


    //CHECK FOR HASH CONSTRAINT . . legacy
    $hashSelect = '';
    $hashWhereClause = '';
    if (isset($this->hashItem) && ($this->hashItem != '')) {
      //if not renderlist
      if (substr($this->hashItem,0,10) != 'renderlist') {
	switch ($this->groupBy) {
	case 'productid':
	  $hashSelect = 'SELECT results.* FROM (';
	  $hashWhereClause .= ") AS results WHERE results.productid LIKE '" . $this->hashItem . "%' ";
	  break;
	case 'starttime':
	  list($starttimeY, $starttimeM) = explode('-', $this->hashItem);
	  $hashSelect = 'SELECT results.* FROM (';
	  $hashWhereClause .= ") AS results WHERE (EXTRACT(YEAR FROM results.starttime) = '" . $starttimeY . "') AND (EXTRACT(MONTH FROM results.starttime) = '" . $starttimeM . "') ";
	  break;
	case 'instrument':
	  $hashSelect = 'SELECT results.* FROM (';
	  $hashWhereClause .= ") AS results WHERE results.displayname = '" . $this->hashItem . "' ";
	  break;
	default:
	  //constraint grouping - get proper constraint keyword
	  list($greater, $lesser) = explode(' - ', urldecode($this->hashItem));
	  $orderByWhereClause .= 'AND orderby_' . $groupByKeyword . '.typeid = ' . $groupByTypeId . ' AND orderby_' . $groupByKeyword . '.value >= ' . $greater . ' AND orderby_' . $groupByKeyword . '.value < ' . $lesser . ' ';
	  break;
	}
      } else {
	//renderlist restriction... create array to use in post-processing
	$this->renderList = explode(',',substr($this->hashItem,11));
      }
    } //if


    //CHECK FOR FILTER
    $filterJoin = '';
    $filterWhereClause = '';
    if (isset($this->filterArray) && (count($this->filterArray) > 0)) {
      $filterJoin = 'JOIN json_keywords AS fTable ON (d.upcid = fTable.upcid) '; 
      foreach ($this->filterArray as $iKey => $iVal) {
	//$filterWhereClause .= 'AND ((d.instrumentid != ' . $iKey . ') OR (d.upcid IN (SELECT mb.upcid FROM meta_bands mb WHERE ';
	$filterWhereClause .= 'AND ((d.instrumentid != ' . $iKey . ") OR (fTable.jsonkeywords->'caminfo'->'isislabel'->'isiscube'->'bandbin'->'center'->> 0 IN ( ";
	//$thumbnailSelect = ", thumbnailTable.jsonkeywords::jsonb->'thumbnail' AS thumbnailurl ";
	//$thumbnailJoin = 'LEFT JOIN json_keywords AS thumbnailTable ON (d.upcid = thumbnailTable.upcid) '; 

	$filterOrs = '';
	foreach ($iVal as $fKey => $fVal) {
	  //$filterOrs .= ($filterOrs == '') ? '' : 'OR ';
	  //$filterOrs .=   "(mb.filter = '" . $fVal . "') ";
	  if ($filterOrs != '') {$filterOrs .= ",";}
	  $filterOrs .=  "'" .  $fVal . "'";
	}
	$filterWhereClause .= $filterOrs . '))) ';
      }
    }


    //CHECK FOR ERROR TYPES
    $errorTypeWhereClause = '';
    if (false) {
    //if (isset($this->errorTypesArray) && (count($this->errorTypesArray) > 0)) {
      $errorTypeId = KeywordsHelper::getTypeIdFromKeyword('errortype');
      foreach ($this->errorTypesArray as $iKey => $iVal) {
	$errorTypeWhereClause .= "AND ((d.instrumentid != " . $iKey . ") OR (d.upcid IN (SELECT err.upcid FROM meta_string err WHERE err.typeid = '" . $errorTypeId . "' AND ";
	$errorTypeOrs = '';
	foreach ($iVal as $fKey => $fVal) {
	  $errorTypeOrs .= ($errorTypeOrs == '') ? '' : 'OR ';
	  $errorTypeOrs .=   "(err.value = '" . $fVal . "') ";
	}
	$errorTypeWhereClause .= $errorTypeOrs . '))) ';
      }
    }

    //CHECK FOR SUB HASH (always based on productid
    if (isset($this->subhash) && ($this->subhash != '')) {
      $hashWhereClause .= "AND results.productid LIKE '" . $this->subhash . "%' ";
    }


    //CONSTRAINT RESTRICTION
    $constraintSelect = '';
    $constraintJoin = '';
    $constraintWhereClause = '';
    if (!empty($this->constraintArray)) {
      $constraintCompares = array();
      foreach ($this->constraintArray as $cKey => $cVal) {
	//check for instrument-specific prefix
	$instrumentId = 0;
	if (strpos($cKey, '__') !== FALSE) {
	  list($instrumentId, $keyword) = explode('__', $cKey);
	} else {
	  $keyword = $cKey;
	}
	//set up compare clause
	$oper = substr($keyword,-2);
	$keyword = substr($keyword,0,-3);
	$constraintKeyword = $keyword; //KeywordsHelper::getSpecificKeyword(strtolower(str_replace('_','',$keyword)), $oper, $instrumentId);
	//$constraintKeyword .= ($instrumentId > 0) ?  '_' . $instrumentId : ''; //suffix to protect against ambiguity
	if  (isset($constraintCompares[$constraintKeyword])) {
	  $constraintCompares[$constraintKeyword] .= ' AND ';
	} else {
	  $constraintCompares[$constraintKeyword] = '';
	}
	//deal with dates
	if (($oper != 'ST') && (!is_numeric($cVal)) && ($cVal != 'true') && ($cVal != 'false')) {
	  $toTimeStart = 'to_timestamp(';
	  $toTimeEnd = ($oper == 'GT') ? ",'YYYY-MM-DD')" : ",'YYYY-MM-DD') + INTERVAL '1 day' ";	  
	  $cValAdjust = $toTimeStart . "'" .  $cVal .  "'" . $toTimeEnd;
	} else {
	  $toTimeStart = '';
	  $toTimeEnd = '';
	  $cValAdjust = $cVal;
	}
	switch($oper) {
	case 'GT':
	  $constraintCompares[$constraintKeyword] .=  'st_' . $constraintKeyword . '.' . $constraintKeyword . ' >= ' . $cValAdjust;
	  break;
	case 'LT':
	  $constraintCompares[$constraintKeyword] .=  'st_' . $constraintKeyword . '.' . $constraintKeyword  . ' <= ' . $cValAdjust;
	  break;
	case 'EQ':
	  $constraintCompares[$constraintKeyword] .=  'st_' . $constraintKeyword . '.' . $constraintKeyword  . ' = ' . $cValAdjust;
	  break;
	case 'ST':
	  $constraintCompares[$constraintKeyword] .= 'UPPER(' . 'st_' . $constraintKeyword . '.' . $constraintKeyword . ") SIMILAR TO '%" . strtoupper($cVal) . "%'"; 
	  break;
	}
      }
      //construct sql 
      // **  note: made second loop because above parses compares, below joins - eg. centerincidence may have both < and >
      // **  note: added instrument to keyword because two identical keywords may apply to diff instruments
      foreach ($constraintCompares as $cKey => $cVal) {

	//undo instrument again
	$cKeywordCurrent = explode('_', $cKey);
	$cKeywordOnly = $cKeywordCurrent[0]; 
	$instrumentId = isset($cKeywordCurrent[1]) ? $cKeywordCurrent[1] : NULL;

	//select
	$constraintSelect .= ', st_' . $cKey . '.' . $cKey . ' ';

	//joins/wheres
	//if (is_numeric($instrumentId) && ($instrumentId > 0)) {
	  //$constraintJoin .= 'LEFT JOIN (SELECT * FROM search_terms WHERE typeid=' . $constraintRecord['typeid'] . ') AS meta_' . $cKey . ' ON (d.upcid = meta_' . $cKey . '.upcid) ';
	  //$constraintWhereClause .= 'AND ((d.instrumentid != ' . $instrumentId . ') OR (' . $cVal . ')) ';
	//  $constraintJoin .= 'JOIN search_terms AS st_' . $cKey . ' ON (d.upcid = st_' . $cKey . '.upcid) ';
	//  $constraintWhereClause .= 'AND ((d.instrumentid != ' . $instrumentId . ') OR (' . $cVal . ')) ';
	//} else {
	  //use keyword only... not instrument specific
	  //$constraintJoin .= 'JOIN meta_' . $constraintRecord['datatype'] . ' as meta_' . $cKeywordOnly . ' ON (d.upcid = meta_' . $cKeywordOnly . '.upcid) ';
	  //$constraintWhereClause .= 'AND meta_' . $cKeywordOnly . '.typeid = ' . $constraintRecord['typeid'] . ' ';
	  //$constraintWhereClause .= 'AND (' .  $cVal . ') ';

	  $constraintJoin .= 'JOIN search_terms AS st_' . $cKey . ' ON (d.upcid = st_' . $cKey . '.upcid) ';
	  $constraintWhereClause .= 'AND (' .  $cVal . ') ';

	  //}
      }
    }

    //ID CONSTRAINTS
    $idWhereClause = '';$idWhereFragment = '';
    if (!empty($this->searchIdArray)) {
      foreach ($this->searchIdArray as $sKey => $sVal) {
	//check for instrument-specific prefix
	$instrumentId = 0;
	if (strpos($sKey, '__') !== FALSE) {
	  list($instrumentId, $idKey) = explode('__', $sKey);
	}
	//check for value prefix
	if (isset($this->searchSelect)) {$idKey = $this->searchSelect;} //set with $_GET
	if (!isset($idKey) || ($idKey == 'searchId')) {$idKey = 'productid';} //default for id requests
	$idVal = trim($sVal);
	if (substr($sVal, 0, 5) == 'isis:') {
	  $idVal = trim(substr($sVal, 5));
	  $idKey = 'isisid';
	}	
	if ((substr($sVal, 0, 4) == 'edr:') || (substr($sVal, 0, 4) == 'EDR:')) {
	  $idVal = trim(substr($sVal, 4));
	  $idKey = 'source';
	}
	if ($idKey == 'upcid') {
	  $idWhereFragment = "d." . $idKey . " = " . $idVal;
	} else {
	  $idWhereFragment = "d." . $idKey . " SIMILAR TO '%" . $idVal . "%' ";
	}
	//instrument check
	if (is_numeric($instrumentId) && ($instrumentId > 0)) {
	  $idWhereClause .= "AND ((d.instrumentid != " . $instrumentId . ") OR (" . $idWhereFragment . ")) ";
	} else {
	  $idWhereClause .= "AND " . $idWhereFragment . " ";
	}
      }
    }

    //QUERY
    $this->queryText =  $hashSelect . 
      "SELECT d.upcid, d.productid, d.source, d.detached_label, d.isisid, d.instrumentid, s.* " . $instrumentSelect . $targetSelect . $constraintSelect . $orderBySelect . $thumbnailSelect . $geoSelect .
      $fromClause .
      $instrumentJoin .
      $targetJoin . 
      $geoJoin .
      $orderByJoin .
      $constraintJoin . 
      $thumbnailJoin .
      $filterJoin . 
      'WHERE TRUE ' . 
      $geoWhereClause . 
      $instrumentWhereClause .
      $targetWhereClause . 
      $idWhereClause .
      $constraintWhereClause .
      $thumbnailWhereClause .
      $filterWhereClause .
      $errorTypeWhereClause .
      $orderByWhereClause . 
      $orderByClause . 
      $limitClause .
      $hashWhereClause;

    //COUNT
    if ($geoWhereClause == '') {$geoJoin = '';} //avoid left join when not needed
    $this->countText = "SELECT count(d.upcid) as total " . 
      $fromClause .
      $instrumentJoin .
      $geoJoin . 
      $constraintJoin .
      $filterJoin . 
      'WHERE TRUE ' . 
      $geoWhereClause . 
      $instrumentWhereClause .
      $targetWhereClause . 
      $idWhereClause .
      $constraintWhereClause .
      $filterWhereClause .
      $errorTypeWhereClause;    

    //print('this->queryText:' . $this->queryText . '<br/>');
    //print('this->countText:' . $this->countText . '<br/>');
    error_log('this->queryText:' . $this->queryText . ' ');
    error_log('this->countText:' . $this->countText . ' ');
    return($this->queryText);
  }


  //
  //
  function getResult($limitstart=0, $limit=0) {
    if (isset($this->select) && ($this->select != '')) {
      //select list for download. . .expand limit
      $this->renderList = explode(',',$this->select);
      $this->whitelist = true;
      $this->getResultSelect();
      return;
    }

    if (!$this->queryText) {
      $this->_buildQuery();
    }

    if ($this->queryText == '') return(0);

    //run query
    set_time_limit(0);
    $this->db->query($this->queryText);
    $this->time = $this->db->time;
    $this->result = $this->db->result;
  }


  function getResultSelect() {

    $this->renderList = explode(',',$this->select);
    $datafilesHelper = new DatafilesHelper($this->target);
    foreach($this->renderList as $rVal) {
      $datafilesHelper->getCSVRecord($rVal, 'upcId');
      //print_r($datafilesHelper->record);
      $results[] = $datafilesHelper->record;
    }
    $this->time = -1; //not normal query
    //$this->resultKeys = $datafilesHelper->csvKeys;
    $this->result = $results;
  }


  function getData() {

    if (empty($this->_data)) {
      $query = $this->_buildQuery();
      if ($query == '') return NULL;
      //$this->_data = $this->_getList($query, $this->getState('limitstart'), $this->getState('limit')); 
      $this->_data = $this->_getList($query);
    }
    return($this->_data);
  }



  function getTotal() {

    if (!$this->countText) {
    	$this->_buildQuery();
    }

    //get total
    set_time_limit(0);
    $this->db->query($this->countText);
    $row = $this->db->getResultRow();
    $this->total = $row['total'];
    $this->time = $this->db->time;

    return($this->total);
  }


  //
  function getMultiDBTotal() {
    //use if target is unknown

    if (!$this->countText) {
      $this->_buildQuery();
    }

    //get total
    set_time_limit(0);
    $row = $this->db->multiDBQueryColumnSum($this->countText);
    $this->total = $row['total'];
    $this->time = $this->db->time;
    $this->multiDBResult = $this->db->multiDBResult;
    return($this->total);
  }


  function ambiguousTarget() {

    return false;  //MAKE MULTI_DB CHECK

    if (!$this->countText) {
      $this->_buildQuery();
    }

    $tempdb = new DatabasePG();
    //amend query to check for ambiguous targets (multiple rows)
    $ambiguousCheckQuery = $this->countText . ' GROUP BY d.targetid ';
    //    $tempdb->query($ambiguousCheckQuery);
    $tempdb->multiDBQueryResultArray($ambiguousCheckQuery);
    return ($tempdb->total > 1);
  }


  function getRestrictedResultsHTL() {

    require_once(dirname(__FILE__) . '/../tools/json.php' );

    
    if (empty($this->result)) {
      $this->getResult();
    }

    //need db class
    $this->db->setResult($this->result);
    $currentPrefix = '';
    $this->footprints = array();

    //loop through data
    $output = ''; $renderOutput = '';
    while ($row = $this->db->getResultRow()) {
      $output[] = $row;
    }

    //*** output JSON data structure ***/
    $renderOutput = json_encode($output);
    return($renderOutput);
  }


  //
  //  returns true if filtered
  //
  function filterByRenderList($id) {

    if (empty($this->renderList)) {
      return false;
    }

    if ($this->whitelist) {
      return (array_search($id, $this->renderList) === FALSE);
    } else {
      return (!(array_search($id, $this->renderList) === FALSE));      
    }
  }


  function selectResultToCSVFile() {

    //RESULT KEYS - used to determine columns displayed after pull
    $resultKeys = array('productid','source','detached_label','instrument','starttime','minimumemission','minimumincidence','minimumphase','maximumemission','maximumincidence','maximumphase','meangroundresolution','solarlongitude');

    //get results
    if (empty($this->result)) {
      $this->getResult();
    }

    //get filename
    $title = 'upcquery';
    $tail = 'csv';
    $TMPDIR = dirname(__FILE__) . '/../tmp/';
    $filename = $TMPDIR . $title . '-' . date("Ymd") . '.' . $tail;
    //print('filename:' . $filename . '<br/>');

    //need db class
    $fp = fopen($filename, 'w');

    //write column headers
    $output = '';
    foreach($resultKeys as $rVal) {
      $output .= ($output == '') ? '' : ', ';
      $output .= $rVal;
    }
    fwrite($fp, $output . "\r\n");

    //write data
    foreach($this->result as $row) {
      $output = '';
  
      foreach($resultKeys as $rVal) {
      if (preg_match($omitPattern, $rVal)) {continue;}
	$output .= ($output == '') ? '' : ', ';
	//$output .= str_replace(',','\\,', $row[$rVal]);
	$output .= $row[$rVal];
      }
      fwrite($fp, $output . "\r\n");
    }

    //set headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'. basename($filename) . '"');
    readfile($filename);
    //die();
  }


  function selectResultToWGETScript() {

    //get results
    if (empty($this->result)) {
      $this->getResult();
    }

    //get filename
    $title = 'upcget';
    $tail = 'sh';
    $TMPDIR = dirname(__FILE__) . '/../tmp/';
    $filename = $TMPDIR . $title . '-' . date("Ymd") . '.' . $tail;
    //print('filename:' . $filename . '<br/>');

    $fp = fopen($filename, 'w');

    //write header
    $header = '#!/bin/bash' . "\n" .
      "#\n" .
      "# Pull UPC Query results generated on " . date("Ymd") . "\n" .
      "# questions: mbailen@usgs.gov\n" .
      "#\n\n" .
      'USAGE="USAGE: ' . $title . '-' . date("Ymd") . '.sh -t TARGET_DIR"' . "\n" .
      'while [ $# -ge 1 ]; do' . "\n" . 
      '    case $1 in ' . "\n" . 
      '    -t)    shift; $TARGET=$1 ;;' . "\n" .
      '    -*)    echo $USAGE; exit 1 ;;' . "\n" .
      '    esac' . "\n" . 
      '    shift' . "\n" .
      'done' . "\n\n" .      
      'CURRENTDIR=`pwd`' . "\n" .
      'if [ "$TARGET" != "" ]; then' . "\n" .
      '    cd $TARGET' . "\n" .
      'fi' . "\n\n";
    fwrite($fp, $header);
    

    //create wget lines
    foreach($this->result as $row) {
      //check for upcid filter
      fwrite($fp, 'wget -nd ' . $row['source'] . "\n");
      if (isset($row['detached_label'])) {
	fwrite($fp, 'wget -nd ' . $row['detached_label'] . "\n");
      }
    }


    //write tail
    $tail =  "\r\n\r\n" . 'cd $CURRENTDIR' . "\r\n";
    fwrite($fp, $tail);

    //set headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'. basename($filename) . '"');
    readfile($filename);
    //die();
  }


  //
  function selectResults($processCheck='') {

    require_once(dirname(__FILE__) . '/../tools/json.php' );
    $output = array();

    if ($processCheck != '') {
      require_once(dirname(__FILE__) . '/instruments_helper2.php' );
      if (!empty($this->mappedArray) || !empty($this->unmappedArray)) {
	$instrumentsHelper = new InstrumentsHelper($this->target);
	$iArray = array_unique(array_merge($this->mappedArray, $this->unmappedArray));
	foreach ($iArray as $iKey => $iVal) {
	  $currentInstrumentId = is_numeric($iVal) ? $iVal : $instrumentsHelper->getIdFromDisplayName($iVal);
	  $canProcess = $instrumentsHelper->canProcess($processCheck, $currentInstrumentId);
	  if (!$canProcess) {return($output);}
	}
      }
    }

    if (empty($this->result)) {
      $this->getResult();
    }

    return($this->result);

  }


  function hashAjaxGet() {

    if ($this->hashItem == '') {return('');}
    
    //get results
    if (empty($this->result)) {
      $this->getResult();
    }
  }


}
