<?php

  /*
     bands_helper - helper for bands

   */

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
//require_once(dirname(__FILE__) . '/keywords_metaHelper.php' );

class BandsHelper {

  var $db;
  var $target;
  var $bandsSumTable='bands_summary';
  var $bandsTable='meta_bands';
  var $record;

  function BandsHelper($target="") {

    $this->db = new DatabasePG($target);
    $this->target = $target;
  }

  //
  // returns comma-separated list of bands
  //
  function getFilters($upcId) {

    $query = 'SELECT filter FROM ' . $this->bandsTable . ' ' .
      "WHERE upcid = '" . $upcId . "' " .
      "ORDER BY filter";
    //print('query:' . $query . '<br/>');
    $this->db->query($query);
    $this->record = $this->db->getResultArray();

    $filter = '';
    foreach ($this->record as $r) {
      $filter .= ($filter == '') ? '' : ','; 
      $filter .= $r['filter'];
    }
    return($filter);
  }


  function getBandSummary($instrumentId) {

    //$instrumentsHelper = new InstrumentsHelper($this->target);
    //$instrumentId = is_numeric($instrument) ? $instrument : $instrumentsHelper->getIdFromDisplayName($instrument);

    $query = 'SELECT * FROM ' . $this->bandsSumTable . ' b ' .
      "WHERE instrumentid = '" . $instrumentId . "' " .
      "ORDER BY centerwave, filter";
    //print('query:' . $query . '<br/>');
    $this->db->query($query);
    $this->record = $this->db->getResultArray();

    return($this->record);
  }


  function getJSONRecord() {

    if (empty($this->record)) {
      return('{}');
    }

    //*** output JSON data structure ***/
    require_once(dirname(__FILE__) . '/../tools/json.php' );
    $output = json_encode($this->record);
    return($output);
  }


}


?>