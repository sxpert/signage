<?php

require_once('db.php');

function sign_add_feed_entry ($feed_id, $date, $title, $image, $detail) {
  $db = db_connect();

  db_query('insert into feed_contents (id_feed, date, title, image, detail) values ($1,$2,$3,$4,$5);',
	   array($feed_id, $date, $title, $image, $detail));
}
?>