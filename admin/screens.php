<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/hlib.php');
require_once($d.'/lib/signlib.php');

$s = new Session();

/* display the list of screens */

hlib_style_add('css/screens.css');
hlib_top();
hlib_menu(sign_admin_menu());

// lister les écrans
db_connect();
$res = db_query ('select * from screens where ignored=false order by id;');
$screens = array();
$headers = array(
  array('text'=>'Adresse IP',    'colstyle'=>'width:100pt', 'cellstyle'=>'text-align:right;'),
  array('text'=>'Nom de machine','colstyle'=>'width:200pt;','cellstyle'=>''),
  array('text'=>'Nom',           'colstyle'=>'width:200pt;','cellstyle'=>'text-overflow:ellipsis;'),
  array('text'=>'Actif',         'colstyle'=>'width:50pt;', 'cellstyle'=>'text-align:center;'),
  array('text'=>'Adopté',        'colstyle'=>'width:140px;', 'cellstyle'=>'text-align:center;')
);
$data = array();
while ($row=db_fetch_assoc($res)) {
  $r = array();
  $r['link'] = 'screen-details.php?id='.$row['id'];
  if ($row['adopted']=='f') 
    $r['class'] = 'notadopted';
  $values = array();
  // adresse ip
  array_push($values, $row['screen_ip']);
  array_push($values, gethostbyaddr($row['screen_ip']));
  // nom de l'écran
  if ($row['name']=='')
    array_push($values, "<i>inconnu</i>");
  else
    array_push($values, $row['name']);
  // écran activé 
  if ($row['adopted']=='t') {
    if ($row['enabled']=='t') 
      array_push($values, 'oui');
    else
      array_push($values, 'non');
  } else
    array_push($values, '');
  // écran adopté
  if ($row['adopted']=='f') {
    $ba = '<button value="'.$row['id'].'" class="adopt">adopter</button>';
    $bi = '<button value="'.$row['id'].'" class="ignore">ignorer</button>';
    array_push($values, $ba.$bi);
  } else
    array_push($values, 'oui');

  $r['values'] = $values;
  array_push($data, $r);
}

$screens['header'] = $headers;
$screens['data'] = $data;
hlib_datatable($screens);

hlib_script_add('js/confirm-dialog.js', -1);
hlib_script_add('js/screens.js', -1);
hlib_footer();
?>
