<?php

//
// génère une description d'ecran en json
//
// { 
//   resolution : {
//       w:               width_of_screen,
//       h:               height_of_screen
//   },
//   backgroundColor:     background_color,
//   backgroundImage:     url_of_background_image,
//   zones : [ 
//     {
//       id:              '<id>', 
//       x:               x_position, 
//       y:               y_position, 
//       w:               width, 
//       h:               height, 
//       border:          border_width, 
//       backgroundColor: background_color;
//       fontSize:        size_of_font_in_percent;
//       color:           text_color
//     },
//    (...)
//   ]
// }
// 

// ceci n'est qu'un exemple. aller dans la bdd pour récupérer les vraies descriptions d'écran

$w = 1920;
$h = 1080;
// font size in pixels
$ts = 100;


function p($full,$value) {
	return ($value*100/$full).'%';
}

$s = array();
$res = array();
$res['w'] = 1920;
$res['h'] = 1080; 
$s['resolution'] = $res;
$s['backgroundColor'] = '#303c47';
// value of fontsize
$s['fontsize'] = $ts/$h;
$zones = array();

$z = array();
$z['id']='image';
$z['x']=p($w,0);
$z['y']=p($h,0);
$z['w']=p($w,1920);
$z['h']=p($h,980);
$z['fontSize']='100%';
$z['color']='blue';
//$z['backgroundColor']='white';
array_push($zones,$z);

$z = array();
$z['id']='_clock';
$z['x']=p($w,0);
$z['y']=p($h,980);
$z['w']=p($w,300);
$z['h']=p($h,100);
$z['fontSize']='43%';
$z['backgroundColor']='black';
$z['color']='white';
array_push($zones,$z);

$z = array();
$z['id']='_ticker';
$z['x']=p($w,300);
$z['y']=p($h,980);
$z['w']=p($w,1920-389);
$z['h']=p($h,100);
$z['fontSize']='80%';
$z['backgroundColor']='black';
$z['color']='white';
array_push($zones,$z);

$z = array();
$z['id']='_logo';
$z['x']=p($w,1920-89);
$z['y']=p($h,980);
$z['w']=p($w,89);
$z['h']=p($h,100);
array_push($zones,$z);

$s['zones']=$zones;

header('Content-type: application/json');
echo json_encode($s);
?>
