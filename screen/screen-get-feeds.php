<?php
$d=dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');
// send feed info as json

$screenid=hlib_get_numeric_variable($_REQUEST,'screenid');

$screen = new Screen($screenid);

// feeds is a feed list object
$feeds = $screen->feeds();

$f = array();
foreach($feeds as $order=>$feed) {
	$f1 = array();
	$f1['id'] = $feed->id;
	$f1['first'] = $feed->getFirstItem();
	$f1['target'] = $feed->target;
	$f[$order] = $f1;
}

header('Content-type: application/json');
echo json_encode($f);

?>
