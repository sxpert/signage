<?php

require_once (dirname(dirname(__file__)).'/config.php');

$db = null;

function _db_connect ($user, $pass) {
	GLOBAL $DBHOST,$DBPORT,$DBNAME,$db;
	
	if ($db===null) {
		$connstr = "host=$DBHOST port=$DBPORT dbname=$DBNAME user=$user password=$pass";
		$db = pg_connect($connstr);
	}
	return $db;
}

function db_connect () {
	GLOBAL $DBUSER,$DBPASS;
	return _db_connect($DBUSER,$DBPASS);
}

function db_query ($s, $p=null) {
	global $db;
	if ($p==null) 
		$p = array();
	return pg_query_params ($db, $s, $p);
}

function db_num_rows ($r) {
	return pg_num_rows($r);
}

function db_affected_rows ($r) {
  return pg_affected_rows($r);
}
  
function db_fetch_assoc ($r) {
	return pg_fetch_assoc($r);
}

?>
