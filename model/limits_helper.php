<?php

  /*
     limits_helper - helper for limits

   */

require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );
require_once(dirname(__FILE__) . '/keywords_metaHelper.php' );


class LimitsHelper {

  var $db;
  var $target;
  var $limitsTablePrecision='target_limits_precision';
  var $limitsTableDate='target_limits_time';
  var $limitsTableInteger='target_limits_integer';
  var $record;

  function LimitsHelper($target="") {

    $this->db = new DatabasePG($target);
    $this->target = $target;
  }


  function getLimits($instrument) {

    $instrumentsHelper = new InstrumentsHelper($this->target);
    $instrumentId = is_numeric($instrument) ? $instrument : $instrumentsHelper->getIdFromDisplayName($instrument);

    if ($this->target == 'untargeted') {
      $targetWhere = "AND l.targetid IS NULL";
    } else {
      $targetsHelper = new TargetsHelper();
      $targetId = $targetsHelper->getIdFromName($this->target);
      $targetWhere = "AND l.targetid = '" . $targetId . "'";
    }

    //precision
    $query = 'SELECT l.*, k.typename, k.displayname, k.description FROM ' . $this->limitsTablePrecision . ' l ' .
      'JOIN keywords k USING (typeid) ' . 
      "WHERE l.instrumentid = '" . $instrumentId . "' " . $targetWhere;
    //print('query:' . $query . '<br/>');
    $this->db->query($query);
    $this->record = $this->db->getResultArray();

    //dates
    $query = 'SELECT l.*, k.typename, k.displayname, k.description FROM ' . $this->limitsTableDate . ' l ' .
      'JOIN keywords k USING (typeid) ' . 
      "WHERE l.instrumentid = '" . $instrumentId . "' " . $targetWhere;
    //print('query:' . $query . '<br/>');
    $this->db->query($query);
    $this->record = array_merge($this->db->getResultArray(), $this->record);

    return($this->record);
  }


  function getJSONRecord() {

    if (empty($this->record)) {
      return('');
    }

    //*** output JSON data structure ***/
    require_once(dirname(__FILE__) . '/../tools/json.php' );
    $renderOutput = json_encode($this->record);
    return($renderOutput);
  }


}


?>