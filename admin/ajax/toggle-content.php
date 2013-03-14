<?php
$d = dirname(dirname(dirname(__file__)));
require_once($d.'/lib/signlib.php');

// TODO: check for logged in user

$id = 0+hlib_get_variable($_REQUEST,'id');
$checked = hlib_get_variable($_REQUEST,'checked');

error_log($id.' '.$checked);

db_connect();

$sql = 'update feed_contents set active=$1 where id=$2';
$res = db_query($sql, array($checked, $id));
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
