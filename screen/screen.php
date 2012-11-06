<?php

$d = dirname(dirname(__file__));
require_once ($d.'/lib/lib.php');
require_once ($d.'/lib/signlib.php');

// generates screendata
$s = array();

//
// clock is always updated
$s['clock'] = strftime('<div style="text-align:center;vertical-align:middle;height:100%;border-right:4px solid white;">%Y-%m-%d<br/>%H:%M</div>');

//
// handle the ticker
$s['ticker'] = '<div style="padding-left:4px;">ticker with great potential</div>';

// get the screen id
$screen_id = get_screen_id (get_remote_ip ());
// get the next feed id
$feed_id = get_next_feed_id ($screen_id);

if ($feed_id == null) {
  // sample feeds
  $s['image'] = '<img style="height:100%;" src="http://www.nasa.gov/images/content/701204main_20121029-SANDY-GOES-FULL.jpg"/>';
} else {
  // grab some info about the feed
  $feed = sign_feed_get_instance($feed_id);
  error_log(print_r($feed,1));
  $feed->getNext($screen_id, $feed_id);
  $s['image'] = $feed_id;
}

header('Content-type: application/json');
echo json_encode($s);
?>
