<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');

$errors=array();

/* display the list of screens */

hlib_style_add('css/screens.css');
hlib_top();
hlib_menu(sign_admin_menu());

echo "<h2>Flux d'informations disponibles</h2>\n";

// lister les écrans
db_connect();
$res = db_query ('select f.id, ft.name as type, f.url, f.name, f.system from feeds as f, feed_types as ft where f.id_type=ft.id order by f.id;');
$feeds = array();
$headers = array(
  array('text'=>'Type',   'colstyle'=>'width:50pt', 'cellstyle'=>'text-align:center;'),
  array('text'=>'URL',    'colstyle'=>'width:200pt;','cellstyle'=>'text-overflow:ellipsis;'),
  array('text'=>'Nom',    'colstyle'=>'width:200pt;', 'cellstyle'=>'text-align:center;'),
  array('text'=>'Système','colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:center;')
);
$data = array();
while ($row=db_fetch_assoc($res)) {
  $r = array();
  $r['link'] = 'feed-details.php?id='.$row['id'];
  $values = array();
  // adresse ip
  array_push($values, $row['type']);
  // url du flux
  array_push($values, $row['url']);
  // nom du flux
  if ($row['name']=='')
    array_push($values, "<i>inconnu</i>");
  else
    array_push($values, $row['name']);
  // écran activé 
  if ($row['system']=='t') 
    array_push($values, 'oui');
  else
    array_push($values, 'non');

  $r['values'] = $values;
  array_push($data, $r);
}

$feeds['header'] = $headers;
$feeds['data'] = $data;
hlib_datatable($feeds);


$form=hlib_form('post','feed-details.php',$errors,array());
hlib_form_hidden($form,'id','new');
hlib_form_button($form,'Créer un nouveau flux','create-new');
hlib_form_end($form);

//hlib_script_add('js/screens.js', -1);
hlib_footer();
?>
