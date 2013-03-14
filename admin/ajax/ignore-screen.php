<?php
$d = dirname(dirname(dirname(__file__)));
require_once($d.'/lib/signlib.php');

// TODO: check for logged in user

$id = 0+hlib_get_variable($_REQUEST,'id');

db_connect();

$ignored = false;
$res = db_query('update screens set ignored=true where id=$1', array($id));
$ignored = (($res!=false)&&(db_affected_rows($res)==1));

header('Content-Type: application/json');
echo json_encode( array('ignored'=>$ignored));
?>
