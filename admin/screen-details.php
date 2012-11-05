<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/hlib.php');
require_once($d.'/lib/signlib.php');

/*
 * screen information page
 */

$id = 0+hlib_get_variable($_REQUEST,'id');
$action = trim(hlib_get_variable($_REQUEST, 'action'));

$errors = array ();

/*
 * check for action
 */

if ($action!='') {
  switch ($action) {
  case 'adopt_screen' :
    break;
  case 'forget_screen' :
    break;
  case 'save_changes' :
    break;
  }
} else {
  db_connect();
  $res = db_query ('select * from screens where id=$1;', array($id));
  if (db_num_rows($res)) {
    $row=db_fetch_assoc($res);
  } else {
    /* écran inexistant ! */
  }
}

hlib_top();
hlib_menu();

/*
 * afficher le formulaire
 */
echo "<h2>Détails sur un écran</h2>\n";
$form = hlib_form('post', 'screen-details.php', $errors, 'screen');
hlib_form_hidden($form, 'id', $row['id']);
hlib_form_display($form, 'Adresse IP', $row['screen_ip']);
hlib_form_text($form, 'Nom de l\'écran', 'name', $row['name']);
hlib_form_checkbox($form, 'Écran Actif', 'enabled', $row['enabled']); 
if ($row['adopted']=='f')
  hlib_form_button($form, 'Adopter l\'écran', 'adopt_screen');
else {
  hlib_form_button($form, 'Oublier l\'écran', 'forget_screen');
  hlib_form_button($form, 'Valider les changements', 'save_changes');
}
hlib_form_end ($form);

/* 
 * afficher les feeds
 */

hlib_footer();
?>
