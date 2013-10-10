<?php
$d = dirname(dirname(__file__));
require_once($d.'/lib/signlib.php');

$s = new Session();

$new=false;
$id=trim(hlib_get_variable($_REQUEST,'id'));
if ($id!='new')
	$id=0+$id;
else
	$new=true;
$id_feed=hlib_get_numeric_variable($_REQUEST,'id-feed');
$action=trim(hlib_get_variable($_REQUEST,'action'));

$date = trim(hlib_get_variable($_REQUEST,'date'));
$title = trim(hlib_get_variable($_REQUEST,'title'));
$detail = trim(hlib_get_variable($_REQUEST,'detail'));
$image = null;
$active = hlib_get_checkbox($_REQUEST,'active');

$errors=array();
$success=false;
$feed=sign_feed_get($id_feed, true);
$system = ($feed['system']=='t');

switch ($action) {
	case 'create-item':
		if (strlen($title)==0)
			hlib_form_add_error($errors, 'title', 'Le titre ne peut être vide');
		if (count($errors)==0) {
			$id = sign_add_feed_entry($id_feed,$date,$title,$image,$detail,$active);
			if ($id!==false) {
				$new = false;
				$success = "L'élément a été créé avec succès";
			}
		}
		break;
	case 'modify-item':
		if (strlen($title)==0)
			hlib_form_add_error($errors, 'title', 'Le titre ne peut être vide');
		if (count($errors)==0) {
			$ok = sign_feed_modify_entry($id,$id_feed,$date,$title,$image,$detail,$active);
			if ($ok===true) {
				$success = "L'élément a été modifié avec succès";
			}
		}

}


if ($new) {
	// new item created
	$item=array();
	$item['id']=$id;
	$item['date']=$date;
	$item['title']=$title;
	$item['detail']=$detail;
	$item['image']=$image;
	$item['active']=$active;
} else
	$item=sign_feed_get_item($id, true);

hlib_style_add('css/feed-item-details.css');
hlib_top();
hlib_menu(sign_admin_menu());

echo "<div><a href=\"feed-details.php?id=".$feed['id']."\">&lt; retour au flux '".$feed['name']."'</a></div>\n";

if ($new)
	echo "<h2>Créer un nouvel élément dans un flux '".$feed['name']."'</h2>\n";
else
	echo "<h2>Modifier un élément du flux '".$feed['name']."'</h2>\n";

if ($success) 
	echo "<div class=\"success\">".$success."</div>\n";

$form=hlib_form('post','feed-item-details.php',$errors,
	array('id'=>'item',
		'stylesheet'=>'input[type=text]{width:400pt;}'.
									'textarea{width:400pt;height:200pt!important;}'));

hlib_form_hidden($form,'id',$item['id']);
hlib_form_hidden($form,'id-feed',$feed['id']);
if ($feed['dateonly']=='t') {
	$d = substr($item['date'],0,10);
	hlib_form_date($form,'Date','date',$d);
} else 
	hlib_form_datetime($form,'Date','date',$item['date']);

hlib_form_text($form,'Titre','title',$item['title']);
hlib_form_textarea($form,'Corps du texte','detail',$item['detail']);
// image plugin...
hlib_form_display($form,'Image',$item['image']);

$imgsrc = json_decode($item['image'],true);
if (is_null($imgsrc))
	$imgsrc = $item['image'];
if (!is_string($imgsrc)) {
	if (is_array($imgsrc)) {
		if (count($imgsrc)>0)
			$imgsrc=$imgsrc[0];
		else 
			$imgsrc=null;
	} else
		$imgsrc=null;
}
if (!is_null($imgsrc))
	echo "<div id=\"img-preview\"><img src=\"".$imgsrc."\"/></div>\n";

if ($system)
	// impossible de modifier pour un flux système
	hlib_form_display($form, 'Élément diffusé',(($item['active']=='t')?'oui':'non'));
else
	hlib_form_checkbox($form,'Élément diffusé','active',$item['active']);

if ($new)
	hlib_form_button($form,'Créer le nouvel élément','create-item');
else
	hlib_form_button($form,'Modifier l\'élément','modify-item');

hlib_form_end($form);


hlib_footer();

?>
