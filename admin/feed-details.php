<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');

//
// done checking  values
//

$errors = array ();
$success = false;

/*
 * feed information page
 */

db_connect();

// id of feed
$new=false;
$id = trim(hlib_get_variable($_REQUEST,'id'));
if (strcmp($id,'new')==0)
	$new = true;
else
	$id=0+$id;
// what's to be done
$action = trim(hlib_get_variable($_REQUEST, 'action'));

// feed form
$type = hlib_get_numeric_variable($_REQUEST, 'type');
$url = trim(hlib_get_variable($_REQUEST, 'url'));
$name = trim(hlib_get_variable($_REQUEST, 'name'));
$system = hlib_get_checkbox($_REQUEST, 'system');
$dateonly = hlib_get_checkbox($_REQUEST, 'system');

// search feature
$item_date = trim(hlib_get_variable($_REQUEST,'item-date'));

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

if ($new) {
	$feed = array(
		'id'      => $id,
		'type'    => $type,
		'url'     => $url,
		'name'    => $name,
		'system'  => $system,
		'dateonly'=> $dateonly
	);
} else {
	// fatal error at this point ! (shouldn't happen)
	if (is_numeric($id)) {
 		$feed = sign_feed_get($id, true);
		// calc list dimensions
		$last_item = sign_feed_number_items($id)-1;
	}
}

/*
 * check for action
 */


if ($action!='') {
  switch ($action) {
	//
	// search by date
	//
	case 'date-search' :
		if (!hlib_form_clean_date($item_date))
			hlib_form_add_error($errors, 'item-date', 'Format de date invalide, \'yyyy-mm-dd\' attendu');
		elseif (!hlib_form_check_date($item_date))
			hlib_form_add_error($errors, 'item-date', 'Date invalide');
		break;
	//
	// redirect for the creation of a new feed item
	// 
	case 'new-item':
		hlib_redirect('/admin/feed-item-details.php?id-feed='.$id.'&id=new');
		break;
	//
	// create a new feed
	//
	case 'create-feed' :
		if (!hlib_form_check_select($type, 'feed_type_list'))
		 	hlib_form_add_error($errors, 'type', 'Type de flux invalide');  
		if (strlen($name)==0)
			hlib_form_add_error($errors, 'name', 'Le nom ne peut etre vide');
		$url = hlib_form_clean_url($url);
		if ((strlen($url)>0)&&(!hlib_form_check_url($url, $e)))
		  hlib_form_add_error($errors, 'url', 'Adresse de document invalide.<br/>'.$e);
		if (count($errors)==0) {
			// ajouter le nouveau flux
			$sql = 'insert into feeds (id_type,url,name,system,dateonly) values ($1,$2,$3,$4,$5) returning id;';
			$arr = array($type,$url,$name,'f',($dateonly?'t':'f'));
			var_dump($arr);
			$res = db_query($sql,$arr);
			if (($res===false)||(db_affected_rows($res)!=1))
				hlib_form_add_error($errors, 'feed', db_last_error());
			else {
				$r = db_fetch_assoc($res);
				$id = $r['id'];
				$feed['id'] = $id;
				$new = false;
				$success = 'Flux créé avec succès';
			}
		}
		break;
	// 
	// modify the feed definition
	//
	case 'modify-feed' :
		if (strlen($name)==0) 
			hlib_form_add_error($errors, 'name', 'Le nom ne peut etre vide');
		$url = hlib_form_clean_url($url);
		if ((strlen($url)>0)&&(!hlib_form_check_url($url, $e)))
		  hlib_form_add_error($errors, 'url', 'Adresse de document invalide.<br/>'.$e);
		if (count($errors)==0) {
			$f['id']=$id;
			$f['url']=$url;
			$f['name']=$name;
			if (sign_feed_modify($f))	{
				$feed['url'] = $url;
				$feed['name'] = $name;
				$success = 'Flux modifié avec succès';
			}
		}
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
		break;
  }
}

/*
 * flux systeme ?
 */
$system = ($feed['system']=='t');

hlib_top();
hlib_menu(sign_admin_menu());

//
// success
if ($success)
	echo "<div class=\"success\">".$success."</div>\n";


/*
 * afficher le formulaire
 */
echo "<h2>Détails sur un flux d'informations</h2>\n";
$form = hlib_form('post', 'feed-details.php', $errors, 
	array(
		'id'=>'feed',
		'stylesheet'=>'input[type=text]{width:300pt;}'.
									'input.hasDatepicker{width:60pt;text-align:center;}'
	));
hlib_form_hidden($form, 'id', $feed['id']);

// TODO: select pour le type de flux
if ($new) 
	hlib_form_select($form,'Type de flux', 'type', $feed['type'], 'feed_type_list');
else
	hlib_form_display($form,'Type de flux', sign_feed_get_type($feed['id']));
if ($system) {
	hlib_form_display($form, 'Adresse du flux', $feed['url']);
	hlib_form_display($form, 'Nom du flux', $feed['name']);
} else {
	hlib_form_text($form, 'Adresse du flux', 'url', $feed['url']);
	hlib_form_text($form, 'Nom du flux', 'name', $feed['name']);
}

// TODO: bouton pour changer
// on ne peut pas créer de nouveaux flux système avec l'interface
hlib_form_display($form, 'Flux système', (($feed['system']=='t')?'oui':'non'));
if ($system) 
	hlib_form_display($form, 'Date uniquement', (($feed['dateonly']=='t')?'oui':'non'));
else
	hlib_form_checkbox($form, 'Date uniquement', 'dateonly', $feed['dateonly']);

if ($new)
	hlib_form_button($form,'Créer le nouveau flux', 'create-feed');
elseif (!$system) 
	hlib_form_button($form,'Modifier la définition du flux', 'modify-feed');
hlib_form_end ($form);

if ($new) {
	// skip the useless stuff
	hlib_footer();
	exit(0);
}

// contenu du flux
echo "<hr/>\n";
echo "<h2>Éléments du flux</h2>\n";

// ligne de boutons

$form = hlib_form('post', 'feed-details.php', $errors, 'contents');
hlib_form_hidden($form, 'id', $feed['id']);

echo "<h3>Recherche par date</h3>\n";
// recherche par date
hlib_form_date ($form, "Date de recherche", "item-date", $item_date);
hlib_form_buttons($form, 
	array(
		array('text'=>'Rechercher par date', 'action'=>'date-search'),
		array('text'=>'Revenir a la liste', 'action'=>''),
	));


echo "<h3>Liste des éléments</h3>\n";


// lister les $nb_items elements
switch ($action) {
	case 'date-search':
		// for the search date, no pager needed 
		$res = db_query('select * from feed_contents where id_feed = $1 and '.
										'date >= $2::timestamp and date < ($2::timestamp + interval \'1 day\') '.
										'order by date desc;',
										array($feed['id'], $item_date));
		// save old display values
		hlib_form_hidden($form,'item-list[current-pos]',$current_pos);
		hlib_form_hidden($form,'item-list[perpage]',$perpage);
		break;
	default:
		$res = db_query('select * from feed_contents where id_feed=$1 order by date desc limit $3 offset $2;',
										array($feed['id'],$current_pos, $perpage));
}

hlib_form_button($form,'Ajouter un élément','new-item');

if (db_num_rows($res)>0) {
	// display nav bar
	hlib_form_nav($form, 'item-list', $current_pos, $last_item, $perpage, 
								'list-begin', 'list-prev', 'list-next', 'list-last');
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
		$r['link']='feed-item-details.php?id-feed='.$feed['id'].'&id='.$row['id'];
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
} else 
	echo "<div><i>Aucun enregistrement trouvé</i></div>";

hlib_form_end ($form);

/*
 * pied de page
 */
hlib_footer();
?>
