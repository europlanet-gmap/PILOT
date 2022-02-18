<?php

/*

  PILOT controller by mbailen@usgs.gov

*/

require_once(dirname(__FILE__) . "/model/upcquery.php"); 
//require_once(dirname(__FILE__) . "/model/upcquery2.php"); 
//require_once(dirname(__FILE__) . '/model/targets_metaHelper.php');
require_once(dirname(__FILE__) . '/model/limits_helper.php');
require_once(dirname(__FILE__) . '/model/bands_helper.php');
require_once(dirname(__FILE__) . '/model/stats_helper.php');
require_once(dirname(__FILE__) . '/model/histogram_helper.php');
require_once(dirname(__FILE__) . '/model/stereo_helper.php');
require_once(dirname(__FILE__) . '/model/datafiles_helper.php');
//require_once(dirname(__FILE__) . '/model/keywords_metaHelper.php');
require_once(dirname(__FILE__) . '/tools/loggingClass.php');
require_once(dirname(__FILE__) . '/tools/nomenclatureClass.php');
require_once(dirname(__FILE__) . '/tools/json.php');


class UpcqueryController {

  var $model; //reference to model
  var $view;  //view name
  var $cssArray = array();
  var $scriptArray = array();
  var $errorText;
  var $analyticsTag;

  //search only variables
  var $target; //target
  var $constraintArray; //spacecraft constraints
  var $constraintJson; //spacecraft constraints
  var $featureTypeArray; //for setting bounding boxes based on features
  var $instrumentSelectArray;

  //param processing variables
  var $viewParams; //array of params to be forwarded to the view
  var $urlParams;  //string of params to add to Ajax url's for restricted queries
  var $searchSelect;  //for search id searches - type of id
  var $searchId;  //for search id searches - id
  var $searchIdData; //search Id json results
  var $searchIdPreload; //search Id json results

  //constructor
  function __construct($act='') {

    $this->errorText = '';
    $this->target = isset($_REQUEST['target']) ? $_REQUEST['target'] : '';
    $this->view = isset($_REQUEST['view']) ? $_REQUEST['view'] : 'planets';
    $this->act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

    $this->configPage($this->act);
  }

  //
  function configPage($act) {

    $config = new Config(); 
    $this->analyticsTag = (isset($config->analyticsTag)) ? $config->analyticsTag : '';

    //model
    $this->model = new upcQuery($this->target);
    //$this->model2 = new upcQuery2($this->target);

    switch ($this->view) {
    case 'downloads':
      $this->scriptArray[] = $config->jqueryURL;
      $statsHelper = new StatsHelper();
      $this->model->stats = $statsHelper->getStats();
      $this->model->statsJSON = $statsHelper->getJSONStats();
      break;
    case 'faq':
      $this->scriptArray[] = $config->jqueryURL;
      break;
    case 'map':  //deprecated
    case 'planets':
    default:
      $this->view = 'planets';
      $statsHelper = new StatsHelper();
      //$this->model->stats = $statsHelper->getStats();
      //$this->model->statsJSON = $statsHelper->getJSONStats();
      $this->model->statsJSON = $statsHelper->getJSONStats2('target');
      $this->model->missionStatsJSON = $statsHelper->getJSONStats2('volume');
      $instrumentsHelper = new InstrumentsHelper();
      $this->model->missionLinks = $instrumentsHelper->getMissionLinks();

      $this->powURL = $config->powURL;

      $this->cssArray[] = $config->jqueryuiCSS;
      $this->cssArray[] = $config->openLayersCSS;
      $this->cssArray[] = $config->openLayersCSS2;

      $this->scriptArray[] = $config->jqueryURL;
      $this->scriptArray[] = $config->jqueryuiURL;
      $this->scriptArray[] = $config->sparklineURL;
      $this->scriptArray[] = 'js/pilotLockout.js';
      $this->scriptArray[] = 'js/pilotPanels.js';
      $this->scriptArray[] = 'js/pilotAJAX.js';
      $this->scriptArray[] = 'js/pilotSearch.js';
      $this->scriptArray[] = 'js/pilotConstrain.js';
      $this->scriptArray[] = 'js/pilotStereo.js';
      break;
    }

    //process params
    $this->processParams();

    //execute act
    if (($act != '') && (method_exists($this,$act))) {
      $this->$act();
    }
  }


  //download function - downloads as file
  function ajaxDownload() {

    //switch on posted value of output format
    switch ($this->model->output) {
    case 'pow':
      //cas check
      //$user = '';
      $powArray = $this->model->selectResults('POW');
      $instrumentHelper = new InstrumentsHelper();
      $instrumentTable = $instrumentHelper->getInstrumentTable();
      $powJobs = array();
      foreach ($powArray as $pVal) {
	if (!isset($powJobs[$pVal['instrument']])) {
	  $powJobs[$pVal['instrument']]['count'] = 1;
	  //$powJobs[$pVal['instrument']]['displayname'] = $pVal['displayname'];
	  $powJobs[$pVal['instrument']]['upcids'] = $pVal['upcid'];
	  $powJobs[$pVal['instrument']]['displayname'] = $instrumentTable[$pVal['instrumentid']]['displayname'];
	  $powJobs[$pVal['instrument']]['mission'] = $instrumentTable[$pVal['instrumentid']]['mission'];
	  //$powJobs[$pVal['instrument']]['comments'] = $instrumentTable[$pVal['instrumentid']]['comments'];
	  //$powJobs[$pVal['instrument']]['pow'] = $instrumentTable[$pVal['instrumentid']]['processtype'];
	  $powJobs[$pVal['instrument']]['comments'] = '';
	  $powJobs[$pVal['instrument']]['pow'] = 'POW';
	  $powJobs[$pVal['instrument']]['user'] = (isset($_SERVER['REMOTE_USER'])) ? $_SERVER['REMOTE_USER'] : '';
	} else {
	  $powJobs[$pVal['instrument']]['count']++;
	  $powJobs[$pVal['instrument']]['upcids'] = $powJobs[$pVal['instrument']]['upcids'] . ',' . $pVal['upcid'];
	}
      }
      require_once(dirname(__FILE__) . '/tools/json.php' );
      echo json_encode(array_values($powJobs));
      //print_r($_SERVER);
      break;
    case 'csv':
      $this->model->selectResultToCSVFile();
      break;
    case 'wget':
      $this->model->selectResultToWGETScript();
      break;
    }
    $log = new Logging();
    //$log->add('New Search - ' .  $this->model->time . ' secs - ' . $this->model->target . ' [query: ' . $this->model->queryText . '] ' . $this->model->select . ' ' . $this->model->renderList . ' ',1);
    die();
  }


  //proxy call to grab band stats
  function bandStatsAjaxGet() {
    if (isset($_REQUEST['path'])) {
      $bPath = 'band_summary_' . $_REQUEST['path'] . '.json';
      $statsPath = "https://upcimages.wr.usgs.gov/summaries/" .  $bPath;
      $handle = @fopen($statsPath, "rb");
      if (!$handle) {return (null);}
      $json = stream_get_contents($handle);
      fclose($handle);
      echo $json;  
    }
    die();
  }


  //bigImageAjaxGet - pull big image - no display
  function bigImageAjaxGet() {
    $datafilesHelper = new datafilesHelper($this->target);
    $datafilesHelper->getImageFromUpcId($this->model->upcid, true);
    $output = $datafilesHelper->getJSONRecord();
    echo $output;  
    die();
  }


  //proxy call to nomenclature to get types
  function featureTypesAjaxGet() {
    $nomenHelper = new NomenclatureHelper();
    $json = json_encode($nomenHelper->getFeatureTypes($this->target));
    echo $json;  
    die();
  }


  //proxy call to nomenclature to fill form
  function featureAjaxGet() {
    $nomenHelper = new NomenclatureHelper();
    $json = $nomenHelper->getFeatureNames($this->target, $_REQUEST['featureType']);
    echo $json;  
    die();
  }


  //proxy call to nomenclature to get feature lat lon
  function featureLatLonAjaxGet() {
    $nomenHelper = new NomenclatureHelper();
    $json = $nomenHelper->getFeatureLatLon($_REQUEST['featureId']);
    echo $json;  
    die();
  }


  //hashAjaxGet - pull data for restricted (Ajax) queries - no display
  function hashAjaxGet() {
    //grab hashed segment of results
    $this->model->hashAjaxGet();
    $output = $this->model->getRestrictedResultsHTML();
    echo $output;  //sends JSON-formatted data
    die();
  }


  //
  function histogramAjaxGet() {
    //grab histogram for instrument/keyword
    $histoHelper = new HistogramHelper($this->target);
    $output = $histoHelper->get($this->model->histogram, $this->model->keyword);
    echo $output;
    die();
  }


  //imageAjaxGet - pull image link for footprint - no display
  function imageAjaxGet() {
    $datafilesHelper = new datafilesHelper($this->target);
    $datafilesHelper->getImageFromUpcId($this->model->upcid);
    $output = $datafilesHelper->getJSONRecord();
    echo $output;  
    die();
  }


  //footprintAjaxGet - pull indepth data for footprint - no display
  function infoAjaxGet() {
    //grab footprint details ... not a UPC query ... need different model
    $datafilesHelper = new datafilesHelper($this->target);
    $datafilesHelper->getCompleteRecord($this->model->upcid, 'upcId');
    $output = $datafilesHelper->getJSONRecord();
    echo $output;  //sends JSON-formatted data
    die();
  }

  /*
  //
  function limitsAjaxGet() {
    //grab limits/bands for instrument
    $limitsHelper = new LimitsHelper($this->target);
    $limitsHelper->getLimits($this->model->limits);
    $output = $limitsHelper->getJSONRecord();
    //$bandsHelper = new BandsHelper($this->target);
    //$bandsHelper->getBandSummary($this->model->limits);
    //$output2 = $bandsHelper->getJSONRecord();
    //$keywordsHelper = new KeywordsHelper($this->target);
    //$output3 = json_encode($keywordsHelper->getKeywordsFromInstrumentId($this->model->limits, 'string'));
    echo '[' . $output . ',' . $output2 . ',' . $output3 . ']';
    die();
  }
  */

  //
  function missionStatsAjaxGet() {
    //grab stats on instrument
    $keywords = array('starttime','processdate','meangroundresolution');
    $histoHelper = new HistogramHelper($this->target);
    $output = '';
    foreach ($keywords as $key) {
      $histogram = $histoHelper->get($this->model->histogram, $key);
      if ($histogram != '') {
	$output .= ($output == '') ? '' : ',';
	$output .= $histogram;
      }
    }
    echo '[' . $output . ']';
    die();
  }


  //
  function results() {
    $returnData['step'] = (isset($this->model->step)) ? $this->model->step : 1; 
    $returnData['groupBy'] = $this->model->groupBy;
    $totalTime = 0;
    //    if (!isset($this->model->step) || ($this->model->step == 1)) {
    //pull total
    //  $returnData['total'] = $this->model->getTotal();
    //  $totalTime = $this->model->time;
    //}

    //run query
    $this->model->getResult();

    require_once(dirname(__FILE__) . '/model/hashResults.php' );
    $hash = new hashResults($this->model);
    $returnData['images'] = $hash->get();    
    $returnData['footprints'] = $hash->footprints;
    //$returnData['resultKeys'] = $this->model->resultKeys;

    //log
    $log = new Logging();
    $log->add('New Search - ' . (isset($returnData['total']) ? $returnData['total'] : ' unknown ') . ' hits (' . $totalTime . ')- ' . $this->model->time . ' secs - ' . $this->model->target . ' [query: ' . $this->model->queryText . ']',1);

    return($returnData);
  }


  //
  function resultsAjaxGet() {
    //json format the results, send back to browser
    require_once(dirname(__FILE__) . '/tools/json.php' );
    echo json_encode($this->results());
    die();
  }


  //
  function searchId() {

    $this->searchIdPrep();
    $searchResults = $this->results();

    //ambiguity checks
    $target = '';
    $iArray = $searchResults['images'];
    if (count($iArray) > 1) {
      //quick test on returned array (render amount)
      foreach ($iArray as $iKey => $iVal) {
	if ($target == '') {
	  $target = $iVal['targetname'];
	}
	if ($target != $iVal['targetname']) {
	  //multi-target... bomb
	  echo -1;
	  die();
	}
      }
      //EDGE CASE: need a better ambiguity check for multi-hit searchId searches over render amount (e.g. 1985).  Now it bombs if over render amount.
      if (count($iArray) == $this->model->render) {
	echo -1;
	die();
      }
    }

    //return json
    require_once(dirname(__FILE__) . '/tools/json.php' );
    $this->searchIdData =  json_encode($searchResults);
 }


  //
  function searchIdAjaxGet() {
    $this->searchId();
    echo $this->searchIdData;
    die();
  }


  //
  function searchIdPrep() {
    if (!isset($this->searchSelect) || !isset($this->searchId)) {return null;}
    $this->model->searchIdArray[$this->searchSelect] = $this->searchId;
    if (!isset($this->model->groupBy)) { 
      $this->model->groupBy = 'starttime';
      $this->model->groupDir = 'ASC';
      $this->model->render = '100';
    }
  }


  //
  function searchIdTotalAjaxGet() {
    $this->searchIdPrep();
    //don't allow 1-3 char searches
    if (strlen($this->searchId) < 4) {
      echo -1;
      die();
    }
    //search all DB's for id
    $total = $this->model->getMultiDBTotal();
    //if multi-hit, make sure not ambiguous
    if (($total > 1) && ($this->model->multiDBResult)) {
      echo -1;
      die();
    }
    echo $total;
    die();
  }


  //dummy function called by chooser... search handled by javascript
  function searchOnLoad() {

  }


  //set target variables for display
  function setTarget($newTarget) {
    //require_once(dirname(__FILE__) . '/model/targets_metaHelper.php' );
    $this->target = $newTarget;
    $this->model->viewParams['target'] = $newTarget;
    $this->model->target = $newTarget;
    $targetHelper = new TargetsHelper();
    $this->model->viewParams['targetInfo'] = $targetHelper->getRowByName($newTarget);
  }


  // calls from ODE
  //
  function singleFootprint() {
    $this->searchIdPreload = $this->searchId;
  }


  //
  function stereoProcessAjaxGet() {
    //grab stereo matches
    $stereoHelper = new StereoHelper($this->target);
    $output = $stereoHelper->get($this->model->stereos, $this->model->stereoOrder);
    echo $output;
    die();
  }


  //totalAjaxGet - pull totals for Ajax queries - no display
  function totalAjaxGet() {
    //grab hashed segment of results
    $total = $this->model->getTotal();
    echo $total;
    die();
  }


  //template post - pull in map coordinates and set the wkt
  function templatePost() {
    //process file
    require_once(dirname(__FILE__) . '/tools/upcFileClass.php' );

    //get upload
    $currentFile = new upcFileClass();
    $currentFile->getUploadedFile('mapTemplate');
    
    //get wkt from map template
    $mapInfo = $currentFile->getWKTFromMapTemplateFile();
    $this->model->wkt = $mapInfo['astroBBWKT'];
    $this->model->urlParams .= '&astroBBWKT=' . urlencode($mapInfo['astroBBWKT']);
    $this->model->viewParams['astroBBWKT'] = $mapInfo['astroBBWKT'];
    $this->model->viewParams['longitudeDirection'] = $mapInfo['longitudeDirection'];
    $this->model->viewParams['longitudeDomain'] = $mapInfo['longitudeDomain'];
    $this->model->viewParams['latitudeType'] = $mapInfo['latitudeType'];
  }


  //
  function unknownStatsAjaxGet() {
    //grab stats on images with unknown targets
    $statsHelper = new StatsHelper();
    $output = $statsHelper->getJSONUnknownStats();
    echo $output;
    die();
  }


  //process params for all methods/views
  function processParams() {

    //pull target array and target
    $this->setTarget($this->target);

    //check for query
    $upcQuery = isset($_REQUEST['upcQuery']) ? $_REQUEST['upcQuery'] : '';
    $wkt = isset($_REQUEST['astroBBWKT']) ? $_REQUEST['astroBBWKT'] : '';
    $this->model->viewParams['upcQuery'] = $upcQuery;
    $view = isset($_REQUEST['view']) ? $_REQUEST['view'] : '';
    $act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';

    //grab wkt
    if ($wkt == '') {
      $config = new Config(); 
      $wkt = $config->fullMapWKT;
    }
    $this->model->wkt = $wkt;
    $this->model->viewParams['astroBBWKT'] = $wkt;

    //loop through requested variables
    $this->model->urlParams = '';
    $baseRequestVariables = array('target', 'queryText', 'astroBBWKT', 'queryType', 'showStats', 'maxResultsRendered', 'step', 'render', 'output', 'select', 'unselect', 'astroBBDatelineWKT','astroBBTopLeftLon','astroBBTopLeftLat','astroBBBotRightLat','astroBBBotRightLat', 'isisId', 'upcGlobalCoverage', 'limits', 'histogram', 'keyword');
    foreach ($_REQUEST as $rKey => $rVal) {
      
      if ($rVal == '') {continue;} //don't create param if empty
      if (($act == 'ajaxDownload') && (strpos($rKey, 'stereo')) !== FALSE)
	{continue;} //ignore stereo params on download

      //BASE REQUEST VARIABLES
      if (in_array($rKey, $baseRequestVariables)) {
	$this->model->$rKey = $rVal;
	if (($rKey != 'queryText') && ($rVal != '')) {
	  $this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	}
	$this->model->viewParams[$rKey] = $rVal;
	continue;
      }

      //SET UP MAPPED ARRAY
      if (substr($rKey, 0, 6) == 'mapped') {
	$this->model->mappedArray[] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	continue;
      }

      //SET UP UNMAPPED ARRAY
      if (strpos($rKey, 'unmapped') !== FALSE) {
	$this->model->unmappedArray[] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	continue;
      }

      //FILTER
      if (strpos($rKey, 'filter') !== FALSE) {
	if ($rVal[0] == 'none') {continue;}
	$filter = substr($rKey, 6);
	$this->model->filterArray[$filter] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode(http_build_query($rVal));
	$this->model->viewParams[$rKey] = urlencode(http_build_query($rVal));
	continue;
      }

      //ERROR TYPE
      if (strpos($rKey, 'errorType') !== FALSE) {
	if ($rVal[0] == 'none') {continue;}
	$errorId = substr($rKey, 9);
	$this->model->errorTypesArray[$errorId] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode(http_build_query($rVal));
	$this->model->viewParams[$rKey] = urlencode(http_build_query($rVal));
	continue;
      }

      //CHECK FOR CONSTRAINTS
      if ((ereg("_(GT|LT|EQ|ST)$",$rKey)) && ($_REQUEST[$rKey] != '')) {
	$this->model->constraintArray[$rKey] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	continue;
      }

      //CHECK FOR SEARCH ID CONSTRAINTS
      if ((ereg("__searchId",$rKey)) && ($_REQUEST[$rKey] != '')) {
	$this->model->searchIdArray[$rKey] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	continue;
      }

      //CHECK FOR EDR SEARCH ID CONSTRAINTS
      if ((ereg("__edr_source",$rKey)) && ($_REQUEST[$rKey] != '')) {
	$this->model->searchIdArray[$rKey] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	continue;
      }


      //SPECIAL PROCESSING VARIABLES
      switch ($rKey) {
      case 'downloadProductId':  
	if ($_REQUEST['act'] != 'ajaxDownload') {break;} 
	//fall through to productid!
      case 'productId':
      case 'productid':
	$this->model->searchIdArray['productid'] = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      case 'upcid':
      case 'upcId':
	$this->model->searchIdArray['upcid'] = $rVal;
	$this->model->upcid = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      case 'edr_source':
      case 'EDR':
      case 'edr':
	$this->model->searchIdArray['edr_source'] = 'edr:' . $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      case 'hashItem':
	$this->model->hashItem = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      case 'subhash':
	$this->model->subhash = $rVal;
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      case 'upcDownloadSet':
	$this->model->downloadSet = $rVal;
	break;
      case 'stereos':
	$this->model->stereos = $rVal;
	break;
      case 'stereoOrder':
	$this->model->stereoOrder = $rVal;
	break;
      case 'upcSearchSelect':
	$this->searchSelect = $rVal;
	$this->model->searchSelect = $rVal;
	break;
      case 'upcSearchId':
	$this->searchId = $rVal;
	break;
      case 'groupBy':
	list($groupBy, $groupDir) = explode('-',$rVal);
	$this->model->groupBy = $groupBy;
	$this->model->groupDir = ($groupDir == 'd') ? 'DESC' : 'ASC';
	$this->model->urlParams .= '&' . $rKey . '=' . urlencode($rVal);
	$this->model->viewParams[$rKey] = urlencode($rVal);
	break;
      }
    }//foreach

  }
}

?>