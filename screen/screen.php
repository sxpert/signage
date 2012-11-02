<?php

// generates screendata

$s = array();
// clock is always updated
$s['clock'] = strftime('<div style="text-align:center;vertical-align:middle;height:100%;border-right:4px solid white;">%Y-%m-%d<br/>%H:%M</div>');

// 
$s['ticker'] = '<div style="padding-left:4px;">ticker with great potential</div>';
$s['image'] = '<img style="height:100%;" src="http://www.nasa.gov/images/content/701204main_20121029-SANDY-GOES-FULL.jpg"/>';

header('Content-type: application/json');
echo json_encode($s);
?>