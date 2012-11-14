<?php
$d = dirname(dirname(dirname(__file__)));
require_once($d.'/lib/signlib.php');

// TODO: check for logged in user

$id = 0+hlib_get_variable($_REQUEST,'id');

db_connect();

$adopted = false;
$res = db_query('update screens set adopted=true where id=$1', array($id));
$adopted = (($res!=false)&&(db_affected_rows($res)==1));
$res = db_query('select enabled from screens where id=$1', array($id));
$row = db_fetch_assoc($res);
$active = false;
if ($row['enabled']=='t')
  $active = true;

header('Content-Type: application/json');
echo json_encode( array('adopted'=>$adopted, 'enabled'=>$active));
?>
