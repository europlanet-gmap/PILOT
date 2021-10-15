<?php

  /*
     datafiles_helper - helper for datafiles table

   */

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
require_once(dirname(__FILE__) . '/bands_helper.php');
require_once(dirname(__FILE__) . '/../tools/loggingClass.php');

class DatafilesHelper {

  var $db;
  var $target;
  //var $datafilesTable='datafiles_w_footprints';
  var $datafilesTable='datafiles';
  var $record;
  var $basicKeys = array('upcid', 'productid', 'source', 'isisid', 'instrumentid', 'footprint', 'targetname', 'instrument', 'displayname');
  var $csvKeys;


  function DatafilesHelper($target="") {

    $this->db = new DatabasePG($target,true);
    $this->target = $target;
  }

  function _getRecordFromId($type, $id) {

    $query = 'SELECT d.upcid, d.productid, d.source, d.isisid, t.displayname as target_name, t.system, ' .
      'i.instrument, i.displayname as instrument_name, i.spacecraft, ' .
      's.*, ST_AsText(s.isisfootprint) AS footprint, j.* ' . 
      'FROM datafiles AS d ' .
      "JOIN search_terms s ON (d.upcid = s.upcid) " . 
      "LEFT JOIN targets t ON (d.targetid = t.targetid) " .
      "JOIN instruments i ON (d.instrumentid = i.instrumentid) " .
      "JOIN json_keywords j ON (d.upcid = j.upcid) " .
      "WHERE d." . $type . "='" . $id ."'";
    $this->db->query($query);
    $this->record = $this->db->getResultRow();
    return($this->record);
  }


  function getBasicRecord($id, $type) {

    $id = pg_escape_string($id);
    //get basic record
    if (empty($this->record)) {
      switch($type) {
	case 'productId':
	  return($this->_getRecordFromId("productid",$id));
	  break;
	case 'isisId':
	  return($this->_getRecordFromId("isisid",$id));
	  break;
	case 'edrSource':
	  return($this->_getRecordFromId("source",$id));
	  break;
	case 'upcId':
	  return($this->_getRecordFromId("upcid",$id));
	  break;
        default:
	  return null;
      }
    }

  }


  function getCompleteRecord($id, $type) {

    $this->getBasicRecord($id, $type);

/*
    $bandsHelper = new BandsHelper($this->target);
    $this->record['Filters'] = $bandsHelper->getFilters($this->record['upcid']);

*/
    ksort($this->record);
    return($this->record);
  }


  function getCSVRecord($id, $type) {
    $this->record = array();
    $this->getBasicRecord($id, $type);
    $this->csvKeys = $this->basicKeys;

    //pull extra keys
    $doubleKeys = array('processdate','starttime','minimumemission','minimumincidence','minimumphase','maximumemission','maximumincidence','maximumphase','meangroundresolution','solarlongitude');
    $timeKeys = array('starttime');

    //check for detached label
    $edrLabelKey = 'detached_label';
    $query = 'SELECT ' . $edrLabelKey . ' FROM datafiles ' .
      "WHERE upcid = '" . $this->record['upcid'] . "'";
    $this->db->query($query);
    $row = $this->db->getResultRow();
    if ($row[$edrLabelKey] != '') {
      $this->csvKeys[] = $edrLabelKey;
      $this->record[$edrLabelKey] = $row[$edrLabelKey];
    }

    $query = 'SELECT * FROM search_terms ' .
      "WHERE upcid = '" . $this->record['upcid'] . "'";
    $this->db->query($query);
    $row = $this->db->getResultRow();
    foreach ($doubleKeys as $kVal) {
      $this->record[$kVal] = $row[$kVal];
    }

  }


  function getUPCRecord($id, $type, $groupBy='starttime-a') {

    $this->getBasicRecord($id, $type);
    return($this->record);
  }


  function getImageFromProductId($productId, $big=false) {

    $keyword = ($big) ? 'fullimageurl' : 'thumbnailurl';

    $imageKey = "jsonkeywords::jsonb->'" . $keyword . "' AS " . $keyword;
    $query = 'SELECT ' . $imageKey . ' FROM json_keywords ' .
      "WHERE productid = '" . $productId . "'";
    $this->db->query($query);
    $this->record = $this->db->getResultRow();
    return($this->record);
  }


  function getImageFromUpcId($upcId, $big=false) {

    $keyword = ($big) ? 'browse' : 'thumbnail';

    $imageKey = "jsonkeywords::jsonb->'" . $keyword . "' AS " . $keyword;
    $query = 'SELECT ' . $imageKey . ' FROM json_keywords ' .
      "WHERE upcid = '" . $upcId . "'";
    $this->db->query($query);
    $this->record = $this->db->getResultRow();
    return($this->record);
  }


  function setModelFromRecord(&$model) {

    if (empty($this->record)) {
      return('');
    }
    //print_r($this->record);
    $model->target = $this->record['targetname'];
  }


  function getJSONRecord() {

    if (empty($this->record)) {
      return('');
    }

    //*** output JSON data structure ***/
    require_once(dirname(__FILE__) . '/../tools/json.php' );
    return(json_encode($this->record));
  }


}


?>