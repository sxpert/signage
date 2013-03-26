<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');

$s = new Session();

$s->disconnect();

# redirect to index
hlib_redirect ('index.php')

?>
