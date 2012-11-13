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

function db_last_error () {
	global $db;
	return pg_last_error($db);
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

function db_update ($table, $key, $fields) {
  $f = array ();
  $v = array ();
  $i = 1;
  foreach($fields as $k => $val) {
    array_push ($f, $k.'=$'.$i);
    if (is_bool($val))
      $val = $val?'t':'f';
    array_push ($v, $val);
    $i++;
  }
  error_log(print_r($f,1));
  error_log(print_r($v,1));
  $f = join(',',$f);
  $s = 'update '.$table.' set '.$f.' where '.$key[0].'=$'.$i.';';
  error_log($s);
  array_push($v,$key[1]);
	error_log(print_r($v,1));
  $res = db_query($s,$v);
	$nb = db_affected_rows($res);
	error_log($nb." rows updated");
  if ($nb==1) return true;
  else return false;
}

?>
