<?php

$d = dirname(__file__);
require_once($d.'/db.php');

function sign_add_feed_entry ($feed_id, $date, $title, $image, $detail) {
  db_connect();

  db_query('insert into feed_contents (id_feed, date, title, image, detail) values ($1,$2,$3,$4,$5);',
	   array($feed_id, $date, $title, $image, $detail));
}

/******************************************************************************
 *
 * Gestion des menus
 *
 */

function sign_admin_menu () {
  $menu = hlib_menu_init();
  hlib_menu_add_section ($menu, 'Actions d\'administration');
  hlib_menu_add_item ($menu, 'Gestion des écrans', '/admin/screens.php');
  return $menu;
}

/******************************************************************************
 *
 * Gestion des écrans
 *
 */

function sign_screen_exists ($screen_id, $bomb=false) {
  db_connect();
  $res = db_query('select id from screens where id = $1', array($screen_id));
  $exists = (db_num_rows($res)==1);
  if ($bomb&&(!$exists)) 
    hlib_fatal("Le numéro d'écran n'existe pas");
  return $exists;
}

function sign_update_screen ($id, $values) {
  db_connect();
  return db_update ('screens', array('id', $id), $values);
}

function get_screen_id ($ip_addr) {
  $db = db_connect();
  $res = db_query('select get_screen_id($1) as id;', array($ip_addr));
  $row = db_fetch_assoc ($res);
  return $row['id'];
}

function get_next_feed_id ($screen_id) {
  $bd = db_connect();
  $res = db_query('select get_next_feed_id($1) as feed_id', array($screen_id));
  $row = db_fetch_assoc ($res);
  return $row['feed_id'];
}
?>
