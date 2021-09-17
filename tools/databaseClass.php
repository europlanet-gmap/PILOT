<?php

/*
 *    
 */


require_once(dirname(__FILE__) . '/../configure.php' );


class DatabasePG {

  var $connect;
  var $result;
  var $time;
  var $total;
  var $targetDBs;
  var $target;
  //db settings
  var $host;
  var $port;
  var $dbname;
  var $user;
  var $password;
  var $multiDBResult;

  function DatabasePG($target="",$new=false) {

    $this->target = ($new) ? 'new' : strtolower($target);
    $this->targetDBs = array('mars','moon','other');
    $this->multiDBResult = false;
  }


 function getDBSettings($target) {


    $config = new Config();

    switch($target) {
    case "mars":
    case "phobos":
    case "deimos":
      //db for mars system
      $this->host=$config->host_mars;
      $this->port=$config->port_mars;
      $this->dbname=$config->dbname_mars;
      $this->user=$config->user_mars;
      $this->password=$config->password_mars;
      break;
    case 'moon':
    case 'earth':
      $this->host=$config->host_moon;
      $this->port=$config->port_moon;
      $this->dbname=$config->dbname_moon;
      $this->user=$config->user_moon;
      $this->password=$config->password_moon;
      break;
    case 'new':
      $this->host=$config->host_new;
      $this->port=$config->port_new;
      $this->dbname=$config->dbname_new;
      $this->user=$config->user_new;
      $this->password=$config->password_new;
      break;
    default:
      //db for rest of solar system
      $this->host=$config->host_other;
      $this->port=$config->port_other;
      $this->dbname=$config->dbname_other;
      $this->user=$config->user_other;
      $this->password=$config->password_other;
    }

  }


 function _query($query, $target) {

    $startTime = microtime(TRUE);
    $this->getDBSettings($target);
    $this->connect = pg_connect("host=$this->host " .
				"port=$this->port " .
				"dbname=$this->dbname " .
				"user=$this->user " .
				"password=$this->password") or die('Could not connect: ' . pg_last_error());
    $this->result = pg_query($query) or die('Query failed: ' . pg_last_error() . '<br/>' . $query);
    $this->total = pg_num_rows($this->result);
    $this->time = microtime(TRUE) - $startTime;
  }


  function query($query) {

    $startTime = microtime(TRUE);

    //determine what target dbs to check
    if ($this->target == '') {
      $targetDBArray = $this->targetDBs;
    } else {
      $targetDBArray = array($this->target);
    }

    //may need to run query through multiple db's
    foreach ($targetDBArray as $tVal) {

      $this->getDBSettings($tVal);
      $this->connect = pg_connect("host=$this->host " .
				  "port=$this->port " .
				  "dbname=$this->dbname " .
				  "user=$this->user " .
				  "password=$this->password") or die('Could not connect: ' . pg_last_error());
      $this->result = pg_query($query) or die('Query failed: ' . pg_last_error() . '<br/>' . $query);
      $this->total = pg_num_rows($this->result);
      if ($this->total > 0) {break;}
    }

    $this->time = microtime(TRUE) - $startTime;
  }


  function getResultRow() {

    if (!isset($this->result)) {
      return(NULL);
    }

    $row = pg_fetch_row($this->result, null, PGSQL_ASSOC);
    return($row);
  }


  function getResultArray() {

    if (!isset($this->result)) {
      return(array());
    }

    $resultArray = array();
    while($row = pg_fetch_array($this->result, null, PGSQL_ASSOC)) {
      //while($row = pg_fetch_row($this->result)) {
      $resultArray[] = $row;
    }
    return($resultArray);
  }


  function multiDBQueryResultArray($query) {

    $multiResult = array();
    $multiTotal = 0;
    foreach ($this->targetDBs as $tVal) {
      $this->_query($query, $tVal);
      $multiResult = array_merge($multiResult, $this->getResultArray());
      $multiTotal = $multiTotal + $this->total;
    }
    $this->total = $multiTotal;
    return($multiResult);
  }


  function multiDBQueryColumnSum($query) {

    $sums = array();
    foreach ($this->targetDBs as $tVal) {
      $this->_query($query, $tVal);
      $row = $this->getResultRow();
      foreach($row as $rKey => $rVal) {
	if (is_numeric($rVal)) {
	  if (!isset($sums[$rKey])) {
	    $sums[$rKey] = 0;
	  }
	  if (($sums[$rKey] > 0) && ($rVal > 0)) {
	    $this->multiDBResult = true;
	  }
	  $sums[$rKey] = $sums[$rKey] + $rVal;
	}
      }
    }
    return($sums);
  }




  function setResult(&$result) {
    $this->result =& $result;
  }

}

