<?php

//
// génère une description d'ecran en json
//
// { 
//   resolution : [ x, y ],
//   zones : [ 
//     {
//       id:     '<id>', 
//       x:      x_position, 
//       y:      y_position, 
//       w:      width, 
//       h:      height, 
//       border: border_width, 
//       color : border_color
//     },
//    (...)
//   ]
// }
// 

$s = array();
$res = array();
$res['w'] = 1920;
$res['h'] = 1080; 
$s['resolution'] = $res;
$zones = array();
$z = array();
$z['id']='image';
$z['x']=30;
$z['y']=50;
$z['w']=400;
$z['h']=300;
$z['border']=5;
$z['color']='#b9d73f';
array_push($zones,$z);
$s['zones']=$zones;

header('Content-type: application/json');
echo json_encode($s);

?>