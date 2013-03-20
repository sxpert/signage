<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');

$s = new Session();


hlib_top();
hlib_menu(sign_admin_menu());
hlib_footer();

?>
