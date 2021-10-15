<?php

  /*
     keywords_metaHelper - helper for keywords_meta table

   */

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
//require_once(dirname(__FILE__) . '/instruments_metaHelper.php' );

class KeywordsHelper {

  var $db;
  var $target;
  
  function KeywordsHelper($target="") {
    $this->target = $target;
    $this->db = new DatabasePG($target);
  }


  function getConstraintArray($instrumentid=0) {

    if ($instrumentid > -1) {
      $whereClause = "WHERE instrumentid = " . $instrumentid;
    } else {
      $whereClause = '';
    }

    //get common id (needs to be set to zero for UI to process)
    $instrumentHelper = new InstrumentsHelper($this->target);
    $commonId = $instrumentHelper->getIdFromName('COMMON');

    //$query = 'SELECT * FROM keywords ' .
    $query = 'SELECT typeid, CASE WHEN instrumentid=' . $commonId . ' THEN 0 ELSE instrumentid END as instrumentid, datatype, typename, initcap(displayname) as displayname FROM keywords ' .
      $whereClause . ' ' .
      "ORDER BY displayname";
    $this->db->query($query);
    $results = $this->db->getResultArray();
    return($results);
  }


  //
  function getMetaTableFromKeyword($keyword) {

    $table = '';
    $query = 'SELECT datatype FROM keywords ' .
      "WHERE typename = '" . $keyword . "'";
    $this->db->query($query);
    $row = $this->db->getResultRow();

    $table = ($row['datatype'] == 'double') ? 'meta_precision' : 'meta_' . $row['datatype'];

    return($table);
  }

  //
  function getTypeIdFromKeyword($keyword, $instrumentId=-1) {

    if ($instrumentId < 0) {
      //get common instrument id
      $instrumentHelper = new InstrumentsHelper($this->target);
      $instrumentId = $instrumentHelper->getIdFromName('COMMON');
    }

    $whereClause = "AND instrumentid = " . $instrumentId;
    $query = 'SELECT typeid FROM keywords ' .
      "WHERE typename = '" . $keyword . "' "  .
      $whereClause;
    $this->db->query($query);
    $row = $this->db->getResultRow();
    //print(' query ' . $query . ' ');
    return($row['typeid']);
  }

  //  
  function getRecordFromKeyword($keyword, $instrumentId=-1) {

    //instrument check
    if ($instrumentId > -1) {

      //get common instrument id
      $instrumentHelper = new InstrumentsHelper($this->target);
      $commonId = $instrumentHelper->getIdFromName('COMMON');

      $whereClause = "AND (instrumentid = " . $instrumentId . ' OR instrumentid = ' . $commonId . ') ';
    } else {
      $whereClause = '';
    }


    //pull matching keywords
    $query = 'SELECT * FROM keywords ' .
      "WHERE typename = '" . $keyword . "' " .
      $whereClause;
    $this->db->query($query);
    $row = $this->db->getResultRow();

    return($row);
  }



  //
  function getTypeNameFromDisplayName($displayName, $instrumentid = 5) {

    $whereClause = "AND instrumentid = " . $instrumentid;

    $typeid = '';
    $query = 'SELECT typename FROM keywords ' .
      "WHERE displayname = '" . $displayName . "' " .
      $whereClause;
    $this->db->query($query);
    $row = $this->db->getResultRow();
    return($row['typename']);
  }


  //
  // get specific keyword from generic constraint
  //           - genericString is a generic constraint (e.g. phaseangle)
  //           - type should be 'max','min','center','GT','LT','EQ'
  //
  //    returns: string - specific keyword (e.g. maximumphaseangle)
  function getSpecificKeyword($genericString, $type='center', $instrumentid=0) {

    //get common instrument id
    $instrumentHelper = new InstrumentsHelper($this->target);
    $commonId = $instrumentHelper->getIdFromName('COMMON');

    $maxMatchArray = array('maximum','max','upper','end','stop','');
    $minMatchArray = array('minimum','min','lower','start','');
    $centerMatchArray = array('center','','mean','median');
    $instrumentWhere = ($instrumentid != $commonId) ? '((instrumentid = ' . $commonId . ') OR (instrumentid=' . $instrumentid . ')) ' : 'instrumentid = ' . $commonId . ' ';

    //pull matching keywords
    $query = 'SELECT typename FROM keywords ' .
      "WHERE " . $instrumentWhere .
      "AND typename ILIKE '%" . trim(strtolower(str_replace(' ','',$genericString))) . "%' ";
    //print('keywords_metaHelper:getSpecificKeyword query: ' . $query . '<br/>');
    $this->db->query($query);
    $results = $this->db->getResultArray();


    //determine match array for given type
    switch($type) {
    case 'LT':
    case 'max':
      $compareArray =& $maxMatchArray;
      break;
    case 'GT':
    case 'min':
      $compareArray =& $minMatchArray;
      break;
    case 'center':
    case 'EQ':
    default:
      $compareArray =& $centerMatchArray;
      break;
    }

    //find best match...  N**2, but very small arrays
    $groupByColumn='';
    foreach($compareArray as $mVal) {
      foreach($results as $rVal) {
	//print('=='.$mVal.'=='.$rVal['typename'].'<br/>');
	if (ereg('.*' . $mVal . '.*', $rVal['typename'])) {
	  $groupByColumn = $rVal['typename'];
	  break 2;
	}
      }
    }
    return($groupByColumn);
  }


  //  
  function getKeywordsFromInstrumentId($instrumentId=0, $datatype='') {

    //get common
    $instrumentHelper = new InstrumentsHelper($this->target);
    $commonId = $instrumentHelper->getIdFromName('COMMON');

    $datatypeClause = ($datatype == '') ? '' : " AND datatype='" . $datatype . "' ";
    
    //pull matching keywords
    $query = 'SELECT * FROM keywords ' .
      "WHERE (instrumentid= " . $instrumentId . ' OR instrumentid=' . $commonId . ') ' .
      $datatypeClause;
    $this->db->query($query);
    $results = $this->db->getResultArray();

    return($results);
  }


}


?>