<?php
require_once (dirname(dirname(__file__)).'/lib/signlib.php');

$s = array();

$screenid = hlib_get_numeric_variable($_REQUEST,'screenid');
$feedid = hlib_get_numeric_variable($_REQUEST,'feedid');
$itemid = hlib_get_numeric_variable($_REQUEST,'itemid');

// check if all stars are aligned
$feed = new Feed(array('screen'=>$screenid, 'feed'=>$feedid));
if (!is_null($feed)) {
  $c = $feed->getNextItem($itemid);
	$s['next'] = $c;
}

header('Content-type: application/json');
echo json_encode($s);
?>
