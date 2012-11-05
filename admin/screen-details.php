<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/hlib.php');
require_once($d.'/lib/signlib.php');

/*
 * screen information page
 */

$id = 0+hlib_get_variable($_REQUEST,'id');
$action = trim(hlib_get_variable($_REQUEST, 'action'));
$name = trim(hlib_get_variable($_REQUEST, 'name'));
$enabled = hlib_get_checkbox($_REQUEST, 'enabled');

error_log("enabled : ".($enabled?'true':'false'));

sign_screen_exists($id, true);

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
  }
}

$res = db_query ('select * from screens where id=$1;', array($id));
if (db_num_rows($res)) {
  $screen=db_fetch_assoc($res);
} else {
  /* écran inexistant ! */
}

hlib_top();
hlib_menu(sign_admin_menu());

/*
 * afficher le formulaire
 */
echo "<h2>Détails sur un écran</h2>\n";
$form = hlib_form('post', 'screen-details.php', $errors, 'screen');
hlib_form_hidden($form, 'id', $screen['id']);
hlib_form_display($form, 'Adresse IP', $screen['screen_ip']);
hlib_form_text($form, 'Nom de l\'écran', 'name', $screen['name']);
// TODO: remplacer avec un select oui/non
hlib_form_checkbox($form, 'Écran Actif', 'enabled', $screen['enabled']); 
if ($screen['adopted']=='f')
  hlib_form_button($form, 'Adopter l\'écran', 'adopt_screen');
else 
  hlib_form_button($form, 'Valider les changements', 'save_changes');
hlib_form_end ($form);

if ($screen['adopted']=='t') {
  echo "<hr/>\n";
/* 
 * afficher les feeds
 */
  echo "<h2>Flux sur cet écran</h2>\n";

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
hlib_footer();
?>
