<?php

$d = dirname(__file__);
require_once($d.'/db.php');
require_once($d.'/lib.php');
require_once($d.'/hlib.php');

/******************************************************************************
 *
 * Utility functions
 *
 */

function sign_base_dir () {
  return dirname(dirname(__file__));
}

function sign_lib_dir () {
  return dirname(__file__);
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
  hlib_menu_add_section ($menu, 'Actions utilisateur');
  hlib_menu_add_item ($menu, 'Flux d\'information', '/admin/feeds.php');
  return $menu;
}

/******************************************************************************
 *
 * Gestion du préload
 *
 */

$_preload = array();

function sign_preload_append($file) {
  GLOBAL $_preload;
  array_push($_preload, $file);
}

function sign_preload_list () {
  GLOBAL $_preload;
  return $_preload;
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

function sign_screen_add_feed($id, $feedid, $active) {
	db_connect();
	$res = db_query('select screen_append_feed($1,$2,$3,$4) as ok;',
									array($id,$feedid,$active,'image'));
	$r = db_fetch_assoc($res);
	return $r['ok'];
}

function sign_screen_activate_feeds ($screen,$feeds) {
	db_connect();
	$k = array_keys($feeds);
	// check if all are integers, dump the rest
	$f = '{'.implode(',',$k).'}';
	$res = db_query ('select screen_active_feeds ($1,$2) as ok;',array($screen,$f));
	$r = db_fetch_assoc($res);
	return $r['ok'];
}

function get_screen_id ($ip_addr) {
  db_connect();
  $res = db_query('select get_screen_id($1) as id;', array($ip_addr));
  $row = db_fetch_assoc ($res);
  return $row['id'];
}

function get_next_feed_id ($screen_id) {
  db_connect();
  $res = db_query('select get_next_feed_id($1) as feed_id', array($screen_id));
  $row = db_fetch_assoc ($res);
  eturn $row['feed_id'];
}

/*******************************************************************************
 *
 * Gestion des flux
 * 
 */

/****
 *
 * Le flux existe t'il ?
 *
 */

function sign_feed_get($feed_id, $bomb=false) {
  db_connect();
  $res = db_query('select * from feeds where id = $1', array($feed_id));
  $exists = (db_num_rows($res)==1);
  if (!$exists) { 
  	if ($bomb)
			hlib_fatal("Le numéro de flux [".$feed_id."] n'existe pas");
		else 
			return null;
	}
	$r = db_fetch_assoc($res);
	return $r;
}

/****
 *
 *
 *
 */
function sign_feed_modify($feed) {
	db_connect();
	return db_update ('feeds', 
		array('id',$feed['id']),
		array (
			'url' => $feed['url'],
			'name' => $feed['name']
		)
	);
}

/****
 *
 * Récupères le type de flux
 * 
 */
function sign_feed_get_type ($feed_id) {
	db_connect();
	$res = db_query('select ft.name as type from feed_types as ft, feeds as f '.
									'where ft.id=f.id_type and f.id=$1;',array($feed_id));
	if ($res===false) return null;
	if (db_num_rows($res)!=1) return null;
	$r = db_fetch_assoc($res);
	return $r['type'];
}


/****
 *
 * Nombre d'items dans le feed
 *
 */
function sign_feed_number_items ($feed_id) {
	db_connect();
	$res = db_query('select count(id) as items from feed_contents where id_feed=$1;', array($feed_id));
	if (db_num_rows($res)!=1) return false;
	$row= pg_fetch_assoc($res);
	return $row['items'];
}

/****
 *
 * Ajout d'un item au flux
 *
 */
function sign_add_feed_entry ($feed_id, $date, $title, $image, $detail,$active=false) {
  db_connect();

  $res = db_query('insert into feed_contents (id_feed, date, title, image, detail,active) '.
		'values ($1,$2,$3,$4,$5,$6) returning id;',
	  array($feed_id, $date, $title, $image, $detail,$active));
	if ($res===false) return false;
	if (db_affected_rows($res)!=1) return false;
	$r = db_fetch_assoc($res);
	return $r['id'];
}

/****
 *
 * Modification d'un item de flux
 *
 */
function sign_feed_modify_entry ($id,$feed_id,$date,$title,$image,$detail,$active) {
	db_connect();
	$sql = 'update feed_contents set '.
				 'date=$1, title=$2, image=$3, detail=$4, active=$5 '.
				 'where id=$6 and id_feed=$7;';
	$arr = array($date,$title,$image,$detail,($active?'t':'f'),$id,$feed_id);
	$res = db_query($sql,$arr);
	if ($res===false) return db_last_error();
	if (db_affected_rows($res)!=1) return false;
	return true;
}

/****
 *
 * Mise à jour du nom de fichier de l'image
 *
 */
function sign_update_image_filename($itemid, $fname) {
  db_connect();
  $r = db_query('update feed_contents set image=$1 where id=$2;', array($fname, $itemid));
  $n = db_affected_rows($r);
  if ($n!=1) {
    error_log('attempted to update 1 row, '.$n.' really updated');
    return false;
  }
  return true;
}


/****
 * 
 * Crées une instance PHP du plugin d'un flux donné
 */

function sign_feed_get_instance ($feed_id) {
  // récupérer la description du type de flux
  db_connect();
  $res = db_query('select php_script, php_class from feed_types as ft, feeds as f where ft.id=f.id_type and f.id=$1',
		  array($feed_id));

  // on a rien trouvé ?!
  if ($res===false) return null;

  // on a pas exactement une ligne (WTF ?)
  if (db_num_rows($res)!=1) return null;

  $feedinfo = db_fetch_assoc($res);
  putenv("SIGNLIBLOADER=true");
	$phpscript = dirname(dirname(__file__)).$feedinfo['php_script'];
	if (!is_readable($phpscript)) return null;
  require_once ($phpscript);
  $instance = new $feedinfo['php_class']();
  return $instance;
}

function sign_feed_get_item ($id, $bomb=false) {
  db_connect();
  $res = db_query('select * from feed_contents where id = $1', array($id));
  $exists = (db_num_rows($res)==1);
  if (!$exists) {
		if ($bomb)
    	hlib_fatal("Le numéro de flux n'existe pas");
		else
			return null;
	}
	$r = db_fetch_assoc($res);
	return $r;
}

function sign_feed_get_next ($screenid, $feedid) {
  db_connect();
  $sql = 'select * from get_next_feed_content($1, $2) as ('.
         'id bigint, feed_id bigint, ts timestamp, caption text, '.
         'image text, detail text, active boolean, target text);';
  $res = db_query($sql, array($screenid, $feedid));
  if ($res===false) return null;
  if (db_num_rows($res)!=1) return null; // can't happen
  $feedinfo = db_fetch_assoc($res);
  if ($feedinfo['id']===null) $feedinfo=null;
  //error_log('sign_feed_get_next : '.print_r($feedinfo, 1));
  return $feedinfo;
}

?>
