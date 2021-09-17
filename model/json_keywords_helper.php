<?php


require_once(dirname(__FILE__) . '/../tools/databaseClass.php' );


class JsonKeywordsHelper {

  var $instrumentTargetArray; //array mapping instruments to targets
  var $db;
  var $target;
  
  function __construct($target="") {
    $this->target = $target;
    $this->db = new DatabasePG($target,true);
  }
  

  function getJSONforID($upcID) {

    $query = 'SELECT * FROM json_keywords ' .
      "WHERE upcid = '" . $upcID . "'";
    $this->db->query($query);
    $json = $this->db->getResultRow();
    return($json);
  }

  function getKeywordForID($upcID, $keyword) {

    $json = $this->getJSONforID($upcID);
    $keys = json_decode($json);
    return($keys[$keyword]);
  }


}


?>