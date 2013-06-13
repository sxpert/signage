<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/hlib.php');
require_once($d.'/lib/signlib.php');

$s = new Session();

/*
 * screen information page
 */

$id = 0+hlib_get_variable($_REQUEST,'id');
$action = trim(hlib_get_variable($_REQUEST, 'action'));
$name = trim(hlib_get_variable($_REQUEST, 'name'));
$enabled = hlib_get_checkbox($_REQUEST, 'enabled');

$feeds = hlib_get_variable($_REQUEST,'feeds');

$id_feed = hlib_get_numeric_variable($_REQUEST,'id-feed');
$feed_active = hlib_get_checkbox($_REQUEST,'feed-active');
$disp_time = hlib_get_variable($_REQUEST,'disp-time');
$disp_zone = hlib_get_variable($_REQUEST,'disp-zone');

if (!sign_screen_exists($id, false))
	hlib_redirect ('/admin/screens.php');

$errors = array ();
$success = false;

/*
 * check for action
 */

db_connect();
if ($action!='') {
  switch ($action) {
  case 'adopt_screen' :
    sign_update_screen ($id, array('name'=>$name, 'adopted'=>true, 'enabled'=>$enabled));
    break;
  case 'forget_screen' :
    error_log('forget screen');
    sign_update_screen ($id, array('adopted'=>false));
    break;
  case 'save_changes' :
    $success = sign_update_screen ($id, array('name'=>$name, 'enabled'=>$enabled));
    break;
	case 'modify-active-feeds':
		if (sign_screen_activate_feeds($id,$feeds))

			$success = 'Liste des flux actifs modifiée avec succès';
		break;
	case 'add-feed':
		$v = sign_screen_add_feed($id,$id_feed,$feed_active,$disp_time,$disp_zone);
		switch ($v) {
			case 0:
				$success = 'Flux ajouté avec succès';
				break;
			case 1:
			case 2:	
 				hlib_form_add_error($errors,'add-feed','Une erreur est survenue pendant l\'ajout du flux');
				break;
			case 3:
				hlib_form_add_error($errors,'add-feed','Ce flux est déjà dans la liste');
		}
		break;
  }
}

$res = db_query ('select * from screens where id=$1;', array($id));
if (db_num_rows($res)) {
  $screen=db_fetch_assoc($res);
} else {
  /* écran inexistant ! */
}

hlib_style_add('css/screen-details.css');
hlib_top();
hlib_menu(sign_admin_menu());

//var_dump($_REQUEST);

if ($success)
 echo "<div class=\"success\">".$success."</div>\n";	

/*
 * afficher le formulaire
 */
echo "<h2>Détails sur un écran</h2>\n";
$form = hlib_form('post', 'screen-details.php', $errors, 
	array(
		'id'=>'screen',
		'stylesheet'=>'input[type=text]{width:300pt}'
	));
hlib_form_hidden($form, 'id', $screen['id']);
hlib_form_display($form, 'Adresse IP', $screen['screen_ip']);
hlib_form_display($form, 'Nom de la machine', gethostbyaddr($screen['screen_ip']));
hlib_form_text($form, 'Nom de l\'écran', 'name', $screen['name']);
// TODO: remplacer avec un select oui/non
hlib_form_checkbox($form, 'Écran Actif', 'enabled', $screen['enabled']); 
if ($screen['adopted']=='f')
  hlib_form_button($form, 'Adopter l\'écran', 'adopt_screen');
else 
  hlib_form_button($form, 'Valider les changements', 'save_changes');
hlib_form_end ($form);

if ($screen['adopted']=='t') {

	/*
	 * liste des zones de l'écran
	 */
	echo "<h2>Configuration de l'écran <span style=\"color:red;\">(non encore fonctionnel)</span></h2>\n";

	$params = json_decode($screen['parameters'], true);
	if (is_null($params)) 
		$params = array();
	
	echo "<h3>Paramètres de l'écran</h3>\n";

	echo "<table id=\"screen-info\"></table>\n";
	
	echo "<h3>Paramètres des zônes d'affichage</h3>\n";

	echo "<table id=\"zones-info\"></table>\n";

	echo "<hr/>\n";

	/*
	 * screen simulation
	 */
	$w = 500;
	$h = floor(($w*9)/16);
	echo "<iframe id=\"simulator\" src=\"/screen/index.php?screenid=".$screen['id']."\" ".
			 "style=\"border:1px solid black;width:".$w."px;height:".$h."px;\"".
		   "></iframe>\n";


	echo "<hr/>\n";
/* 
 * afficher les feeds
 */
  echo "<h2>Flux sur cet écran</h2>\n";

  // create the data array
  $res = db_query('select sf.id_feed, sf.feed_order, sf.active, ft.name as type, '.
			'f.name as name, sf.duration as duration, sf.target as target '.
		  'from screen_feeds as sf, feeds as f, feed_types as ft '.
      'where sf.id_feed=f.id and f.id_type=ft.id and sf.id_screen=$1 '.
		  'order by sf.feed_order;', array($screen['id']));
  $feeds = array();
  $headers = array(
		   array('text'=>'Type de flux','colstyle'=>'width:70pt;'),
		   array('text'=>'Nom du flux', 'colstyle'=>'width:200pt;'),
		   array('text'=>'Actif',       'colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:center;'),
			 array('text'=>'Durée Aff.',  'colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:right;'),
			 array('text'=>'Zone Aff.',   'colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:center;')
  );
  $data = array();
  while($row=db_fetch_assoc($res)) {
    $r = array();
    $values = array();
    
    // type de flux
    array_push ($values, $row['type']);
		array_push ($values, $row['name']);
    // flux actif TODO: boite a cocher ?
	  //if ($row['active']=='f')
    //  array_push($values, 'non');
    //else
    //  array_push($values, 'oui');
		$chb = '<input type="checkbox" name="feeds['.$row['id_feed'].']" '.(($row['active']=='t')?'checked':'').'/>';
		array_push($values,$chb);
		array_push($values,$row['duration']);
		array_push($values,$row['target']);

    // TODO: petit nom du flux !

    $r['values'] = $values;
    array_push($data,$r);
  }
  $feeds['header'] = $headers;
  $feeds['data'] = $data;


	echo "<h3>Liste des flux</h3>\n";
	$form = hlib_form('post','screen-details.php',$errors, array('id'=>'feeds'));
  hlib_form_hidden($form, 'id', $screen['id']);
	hlib_datatable($feeds);
	hlib_form_button($form,'Modifier les flux actifs','modify-active-feeds');
	hlib_form_end($form);

	// formulaire pour ajouter un flux
	echo "<h3>Ajouter un flux à l'écran</h3>\n";

	$form = hlib_form('post','screen-details.php',$errors, array('id'=>'add-feed'));
  hlib_form_hidden($form, 'id', $screen['id']);
	hlib_form_select($form,'Nom du flux', 'id-feed', $id_feed, 'feed_list');
	hlib_form_checkbox($form,'Flux actif', 'feed-active',$feed_active);
	hlib_form_text($form,'Durée d\'affichage (secondes)', 'disp-time', $disp_time);
	hlib_form_select($form,'Zone d\'affichage', 'disp-zone', $disp_zone, 'zone_list where id='.intval($screen['id']));
	hlib_form_button($form,'Ajouter un flux','add-feed');
	hlib_form_end($form);
	

  echo "<hr/>\n";

  echo "<h2>Oublier l'écran</h2>\n";
  $form = hlib_form('post', 'screen-details.php', $errors, 'forget');
  hlib_form_hidden($form, 'id', $screen['id']);
  hlib_form_button($form, 'Oublier l\'écran', 'forget_screen');
  hlib_form_end ($form);
}

/*
 * pied de page
 */
hlib_script_add('js/screen-details.js', -1);
hlib_footer();
?>
