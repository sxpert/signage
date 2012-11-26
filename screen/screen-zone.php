<?php
require_once (dirname(dirname(__file__)).'/lib/signlib.php');

$tickervalues = array(
  'this ticker rocks',
  'Rock-n-Roll',
  'Sugar Baby !!'
);

$s = array();

$zone = trim(hlib_get_variable($_REQUEST,'zone'));

$s['zone'] = $zone;

switch ($zone) {
  case '_clock':
    $s['js'] = '/screen/js/clock.js';
    $s['delay'] = 5;
    break;
  case '_ticker':
    $s['html'] = '<span>'.$tickervalues[rand(0,2)].'</span>';
    $s['delay'] = 10;
    break;
  default:
    // get the screen id
	  $screen_id = get_screen_id (get_remote_ip ());
    // get the next feed id
    // TODO: take the target into account !
    $feed_id = get_next_feed_id ($screen_id,false);
    //$feed_id = null;
    if ($feed_id==null) {
      // default error message
      $s['html'] = '<span style="font-size:50%;color:white;">Une erreur s\'est produite lors de la recherche d\'informations pour cet écran</span>';
      // 60 seconds so as to not overload the server
      $s['delay'] = 60;
    } else {
      $feed = sign_feed_get_instance($feed_id);
			if (!is_null($feed)) {
	      $c = $feed->getNext($screen_id, $feed_id, $zone);
				$s = array_merge($s,$c);
			} else {
				$s['delay'] = 10;
				$s['html'] = 'erreur a la création du flux';
			}
    }
}


header('Content-type: application/json');
echo json_encode($s);
?>
