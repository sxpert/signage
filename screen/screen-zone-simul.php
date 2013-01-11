<?php
require_once (dirname(dirname(__file__)).'/lib/signlib.php');

$s = array();

$screenid = hlib_get_numeric_variable($_REQUEST,'screenid');
$zone = trim(hlib_get_variable($_REQUEST,'zone'));
$feedid = hlib_get_numeric_variable($_REQUEST,'feedid');
$itemid = hlib_get_numeric_variable($_REQUEST,'itemid');

$s['zone'] = $zone;

// check if all stars are aligned
$feed = new Feed(array('screen'=>$screenid, 'feed'=>$feedid));
if (!is_null($feed)) {
  $c = $feed->getItem($itemid);
	$s = array_merge($s,$c);
}
if (is_null($feed)) {
	$s['delay'] = 10;
	$s['html'] = 'erreur a la création du flux';
}

header('Content-type: application/json');
echo json_encode($s);
?>
