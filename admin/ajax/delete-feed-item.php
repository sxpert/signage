<?php
$d = dirname(dirname(dirname(__file__)));
require_once($d.'/lib/signlib.php');

// TODO: check for logged in user

$id = 0+hlib_get_variable($_REQUEST,'id');

error_log($id);

db_connect();

$sql = 'update feed_contents set deleted=true where id=$1';
$res = db_query($sql, array($id));
if (is_bool($res)) {
	error_log('res is bool, returning false');
	$ok = false;
} else {
	$n = db_affected_rows($res);
	if ($n!=1) {
		error_log('num_rows = '.$n.' we have a problem');
		$ok=false;
	} else
		$ok=true;
}

header('Content-Type: application/json');
echo json_encode( array('ok'=>$ok));
?>
