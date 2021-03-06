<?php
require_once (dirname(dirname(__file__)).'/lib/signlib.php');

$tickervalues = array(
	'Flux d\'information IPAG',
  '<span style="font-size:50%">Mise à jour réseau UJF - internet indisponible<br/>25 Avril 2012 de 18 a 23h</span>',
);

$s = array();

$zone = trim(hlib_get_variable($_REQUEST,'zone'));

$s['zone'] = $zone;

switch ($zone) {
	case '_logo':
		$s['html'] = '<img src="/screen/logos/logo_ipag.png"/>';
		$s['delay'] = 600;
		break;
  case '_clock':
    $s['js'] = '/screen/js/clock.js';
    $s['delay'] = 5;
    break;
  case '_ticker':
    $s['html'] = '<span>'.$tickervalues[rand(0,count($tickervalues)-1)].'</span>';
    $s['delay'] = 10;
    break;
  default:
    // get the screen id
	  $screen_id = get_screen_id (get_remote_ip ());
    // get the next feed id
    // TODO: take the target into account !
    $feed_id = get_next_feed_id ($screen_id,$zone,false);
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
				if (!is_array($c)) {
					error_log('SCREEN: error while getNext('.$screen_id.','.$feed_id.',\''.$zone.'\')'); 
					$s['delay'] = 10;
					$s['html'] = 'erreur à la création du flux';
				} else {
					$s = array_merge($s,$c);
				}
			} else {
				error_log ('SCREEN: error while creating feed for ('.$screen_id.','.$zone.')');
				$s['delay'] = 10;
				$s['html'] = 'erreur à la création du flux';
			}
    }
}


header('Content-type: application/json');
echo json_encode($s);
?>
