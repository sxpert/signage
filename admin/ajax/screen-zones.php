<?php
$d = dirname(dirname(dirname(__file__)));
require_once($d.'/lib/signlib.php');

$s = new Session(false);

if ($s->initialized===false) {
	/* 403 */
	hlib_fail (403, null, true);
}

$id = 0+hlib_get_variable($_REQUEST,'id');

db_connect();
$res = db_query('select parameters from screens where id=$1', array($id));
if (($res===false)||(db_num_rows($res)==0)) {
	/* 412 */
	hlib_fail (412, null, true);
}

$screen = array();

$row = db_fetch_assoc ($res);
$screen['params'] = json_decode($row['parameters']);

$res = db_query('select zone_name, parameters from screen_zones where id_screen=$1', array($id));
if ($res===false) {
	/* 412 */
	hlib_fail (412, null, true);
}

$zones = array();
while ($row=db_fetch_assoc($res)) {
	$z = array();
	$z['name'] = $row['zone_name'];
	$z['params']  = json_decode($row['parameters']);
	array_push($zones, $z);
}

$screen['zones']=$zones;

echo json_encode($screen);
?>
