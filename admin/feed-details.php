<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/hlib.php');
require_once($d.'/lib/signlib.php');

/*
 * feed information page
 */

db_connect();

// id of feed
$id = 0+hlib_get_variable($_REQUEST,'id');
// what's to be done
$action = trim(hlib_get_variable($_REQUEST, 'action'));

// feed form
$url = trim(hlib_get_variable($_REQUEST, 'url'));
$name = trim(hlib_get_variable($_REQUEST, 'name'));
$system = hlib_get_checkbox($_REQUEST, 'system');

// feed contents list
// TODO: done with basic forms, could be done with some smart ajax too...

// position in the list and number of items
$item_list = hlib_get_variable($_REQUEST, 'item-list');
if (is_array($item_list)) {
	$current_pos = 0;
	if (array_key_exists('current-pos',$item_list))
		$current_pos = 0+$item_list['current-pos'];
	$perpage = 20;
	if (array_key_exists('perpage',$item_list))
		$nb_items = 0+$item_list['perpage'];
} else {
	$current_pos = 0;
	$perpage = 20;
}

sign_feed_exists($id, true);

//
// done checking  values
//

$errors = array ();
$success = false;

/*
 * check for action
 */

// calc list dimensions
$last_item = sign_feed_number_items($id)-1;

if ($action!='') {
  switch ($action) {
  case 'save_changes' :
    break;
	//
	// list navigation
	// TODO: for now nb_elems is forced to 20...
	//       investigate for varied numbers
	case 'list-begin' :
		$current_pos = 0;
		break;
	case 'list-prev' :
		if ($current_pos-$perpage<0) $current_pos=0;
		else $current_pos-=$perpage;
		break;
	case 'list-next' :
		if ($current_pos+$perpage<=$last_item) 
			$current_pos+=$perpage;
		break;
	case 'list-last' :
		$current_pos = floor($last_item/$perpage)*$perpage;
  }
}

$res = db_query ('select * from feeds where id=$1;', array($id));
if (db_num_rows($res)) {
  $feed=db_fetch_assoc($res);
} else {
  /* flux inexistant ! */
}

hlib_style_add('css/feed-details.css');
hlib_top();
hlib_menu(sign_admin_menu());

/*
 * afficher le formulaire
 */
echo "<h2>Détails sur un flux d'informations</h2>\n";
$form = hlib_form('post', 'feed-details.php', $errors, 'feed');
hlib_form_hidden($form, 'id', $feed['id']);

hlib_form_text($form, 'Adresse du flux', 'url', $feed['url'], '400px');
hlib_form_text($form, 'Nom du flux', 'name', $feed['name'], '400px');
hlib_form_checkbox($form, 'Flux système', 'system', $feed['system']); 

// TODO: bouton pour changer

hlib_form_end ($form);

// contenu du flux

echo "<h2>Éléments du flux</h2>\n";

// ligne de boutons

$form = hlib_form('post', 'feed-details.php', $errors, 'contents');
hlib_form_hidden($form, 'id', $feed['id']);

// display nav bar
hlib_form_nav($form, 'item-list', $current_pos, $last_item, $perpage, 
							'list-begin', 'list-prev', 'list-next', 'list-last');

// lister les $nb_items elements
$res = db_query('select * from feed_contents where id_feed=$1 order by date desc '.
								'limit $3 offset $2;',
								array($feed['id'],$current_pos, $perpage));
$items=array();
$headers=array(
	array('text'=>'Date', 'colstyle'=>'width:120pt;', 'cellstyle'=>'text-align:center;'),
	array('text'=>'Titre', 'colstyle'=>'width:300pt;', 
				'cellstyle'=>'text-align:left;text-overflow:ellipsis;'),
	array('text'=>'Publié', 'colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:center;')
);
$data=array();
while ($row=db_fetch_assoc($res)) {
	$r=array();
	$r['link']='feed-item-detail.php?id='.$row['id'];
	$values=array();
	$ts = $row['date'];
	$d = substr($ts,0,10);
	$h = substr($ts,11,8);
	if ($feed['dateonly']=='t') array_push($values,$d);
	else array_push($values, $row['date']);
	array_push($values, $row['title']);
	if ($row['active']=='t') array_push($values,'oui');
	else array_push($values,'non');
	$r['values'] = $values;
	array_push($data,$r);
}
$items['header']=$headers;
$items['data']=$data;
hlib_datatable($items);
hlib_form_end ($form);

/*
 * pied de page
 */
hlib_footer();
?>
