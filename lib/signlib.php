<?php

$d = dirname(__file__);
require_once($d.'/db.php');

function sign_add_feed_entry ($feed_id, $date, $title, $image, $detail) {
  $db = db_connect();

  db_query('insert into feed_contents (id_feed, date, title, image, detail) values ($1,$2,$3,$4,$5);',
	   array($feed_id, $date, $title, $image, $detail));
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
