<?php
/*******************************************************************************
 *
 * Librairie de gestion de formulaires
 * (c) Raphaël Jacquot 2012
 * Fichier sous licence GPL-3
 *
 ******************************************************************************/

define('DEBUG', false);

/******************************************************************************
 *
 * Fonctions de gestion et nettoyages des entrées
 *
 */

function hlib_get_variable ($array, $varname) {
  if (array_key_exists ($varname, $array)) {
    return $array[$varname];
  } else {
    return "";
  }
}

function hlib_get_numeric_variable($array,$varname) {
	return (0+trim(hlib_get_variable($array,$varname)));
}

function hlib_get_checkbox ($array, $varname) {
  $v = strtolower(trim(hlib_get_variable ($array, $varname)));
  if (strcmp($v, 'on')==0) return true;
  return false;
}

/*
 * retourne le chemin d'ou on vient si le host == le host de la machine sur 
 * laquelle on tourne. False sinon
 */
function hlib_check_referer () {
  $ref = '';
  if (array_key_exists('HTTP_REFERER',$_SERVER)) $ref = $_SERVER['HTTP_REFERER'];
  $srv = $_SERVER['SERVER_NAME'];
  $port = $_SERVER['SERVER_PORT'];
  
  if (strlen(trim($ref))==0) return False;

  $url = parse_url($ref);  
  if (array_key_exists('scheme', $url) and strcmp($url['scheme'],'http')!=0) return False;
  if (array_key_exists('host', $url) and strcmp($url['host'],$srv)!=0) return False;
  $u='';
  if (array_key_exists('path',$url)) $u.=$url['path'];
  if (array_key_exists('query',$url)) $u.='?'.$url['query'];
  if (array_key_exists('fragment',$url)) $u.='#'.$url['fragment'];
  return $u;
}

/****
 * force la fermeture de session
 */
function hlib_close_session() {
  session_unset();
  session_destroy();
}

/*
 * redirige vers une autre page
 */
function hlib_redirect($url) {
  header('Location: '.$url);
  exit(0);
} 

/*
 * renvoie une page d'erreur si on a un referer moisi
 */
function hlib_reject () {
  header($_SERVER['SERVER_PROTOCOL']." 403 Forbidden");
  hlib_close_session();
  
  hlib_top();
  $menu = hlib_menu_init();
  hlib_menu_add_item ($menu, 'Accueil', '/');
  hlib_menu($menu);
  
  // contenu
  echo "Vous provenez d'un serveur non reconnu, Connexion refusée";

  hlib_footer();
  exit(1);
}

function hlib_fail ($code, $message=null, $ajax=false) {
  switch($code) {
  case 403: $msg = 'Forbidden'; break;
  case 404: $msg = 'Not found'; break;
  case 405: $msg = 'Method not allowed'; break;
	case 412: $msg = 'Precondition failed'; break;
  case 500: $msg = 'Internal server error'; break;
  default: $code = 200; $msg = 'OK';
  }

  header($_SERVER['SERVER_PROTOCOL'].' '.$code.' '.$msg);
	if ($ajax===false) {
		hlib_top();
 		$menu = hlib_default_menu();
  	hlib_menu($menu);
  	echo $message;
  	hlib_footer();
	}
  exit (1);
}

function hlib_fatal($message) {
  hlib_top();
  hlib_menu();
  echo "<h1>Erreur fatale</h1>\n";
  echo "<p>".$message."</p>";
  hlib_footer();
  exit(0);
}

/******************************************************************************
 *
 * Fonctions html (entete, menu, ...)
 * 
 */

$_hlib_scripts = array();
$_hlib_styles = array();

function hlib_script_add($statement, $script_id=null) {
  GLOBAL $_hlib_scripts;
  
  // implementer $script_id = -1 et numérique
  if (is_int($script_id)) {
    if ($script_id<0) {
      $s = array_search($statement, $_hlib_scripts);
      if (is_bool($s)&&(!$s)) {
	if ($script_id==-1) array_push($_hlib_scripts,$statement);
	if ($script_id==-2) $_hlib_scripts = array_merge(array($statement),$_hlib_scripts);
      }
    } else $_hlib_scripts[$script_id] = $statement;
  } else { 
    if (is_null($script_id)) $script_id='_default';
    else if ($script_id[0]=='_') {
      switch($script_id) {
      case "_default":
      case "_begin":
	break;
      default :
	$script_id='_'.$script_id;
      }
    }
    if (array_key_exists($script_id, $_hlib_scripts)) $s = $_hlib_scripts[$script_id];
    else $s = array();
    array_push($s, $statement);
    $_hlib_scripts[$script_id] = $s;
  }
}

function hlib_style_add($style) {
  GLOBAL $_hlib_styles;
  $s = array_search($style, $_hlib_styles);
  if (is_bool($s)&&(!$s))
    array_push($_hlib_styles, $style);
}

function hlib_add_jquery() {
  GLOBAL $JQUERY_VER;
  hlib_script_add ("http://code.jquery.com/jquery-1.8.2.js", -2);
  //hlib_script_add ("/lib/jquery/core/".$JQUERY_VER.".min.js", -2);
}

function hlib_add_jqueryui() {
  GLOBAL $JQUERYUI_VER, $JQUERYUI_THEME;
  hlib_add_jquery ();
  //hlib_script_add ("/lib/jquery/ui/js/jquery-ui-".$JQUERYUI_VER.".custom.min.js", -1);
  hlib_script_add ("http://code.jquery.com/ui/1.9.1/jquery-ui.js", -1);
  //hlib_style_add ("/lib/jquery/ui/css/".$JQUERYUI_THEME."/jquery-ui-".$JQUERYUI_VER.".custom.css");
  hlib_style_add ("http://code.jquery.com/ui/1.9.1/themes/base/jquery-ui.css");
	hlib_script_add ("/lib/jquery/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.js",-1);
	hlib_style_add ("/lib/jquery/jQuery-Timepicker-Addon/jquery-ui-timepicker-addon.css");
}

/*****
 *
 * Page top
 *
 */

function hlib_xhtml_header () {
	header('Content-Type: text/html; charset=utf-8');
	echo "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n";
  echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
  echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
}

function hlib_top ($styles=null) {
  GLOBAL $_hlib_styles;
  
  // TODO: handle in a more generic fashion
  hlib_style_add("/lib/css/base.css");
  hlib_style_add("/lib/css/fonts.css");
  hlib_add_jqueryui();
  
  hlib_xhtml_header();
  echo "<head>\n";
  // TODO : handle page title
  echo "<title>hlib default title</title>\n";
  foreach ($_hlib_styles as $style)
    echo "<link rel=\"stylesheet\" href=\"".$style."\" type=\"text/css\"/>\n";
  echo "</head>\n";
  echo "<body>\n";
  echo "<div id=\"top\">";
  echo "<a href=\"/\">";
	echo $_SERVER['SERVER_NAME'];
  echo "</a>";
  echo "</div>\n";
}

/******************************************************************************
 *
 * menu
 *
 */

define ('HLIB_MENU_SECTION', 0);
define ('HLIB_MENU_ITEM', 1);
define ('HLIB_MENU_SEPARATOR', 2);
define ('HLIB_MENU_FORM', 10);
define ('HLIB_MENU_FORM_ERROR', 11);
define ('HLIB_MENU_FORM_HIDDEN', 12);
define ('HLIB_MENU_FORM_TEXT', 13);
define ('HLIB_MENU_FORM_PASS', 14);
define ('HLIB_MENU_FORM_BUTTON', 15);
define ('HLIB_MENU_FORM_END', 16);

function hlib_menu_init () {
  $menu = array();
  return $menu;
}

function hlib_menu_add_section (&$menu, $item) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_SECTION;
  $menuitem['item']=$item;
  array_push($menu, $menuitem);
}

function hlib_menu_add_item (&$menu, $item, $url) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_ITEM;
  $menuitem['item']=$item;
  $menuitem['url']=$url;
  array_push($menu, $menuitem);
}

function hlib_menu_add_separator (&$menu) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_SEPARATOR;
  array_push($menu, $menuitem);
}

function hlib_menu_add_form (&$menu, $method, $action, $id=null,$start_hidden=false) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM;
  $menuitem['method']=$method;
  $menuitem['action']=$action;
  $menuitem['id']=$id;
  $menuitem['start_hidden']=$start_hidden;
  array_push($menu, $menuitem);
}

function hlib_menu_form_add_error (&$menu, $message) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_ERROR;
  $menuitem['message']=$message;
  array_push($menu, $menuitem);
}

function hlib_menu_form_add_hidden (&$menu, $variable, $value) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_HIDDEN;
  $menuitem['variable']=$variable;
  $menuitem['value']=$value;
  array_push($menu, $menuitem);
}

function hlib_menu_form_add_text (&$menu, $label, $variable) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_TEXT;
  $menuitem['label']=$label;
  $menuitem['variable']=$variable;
  array_push($menu, $menuitem);
}

function hlib_menu_form_add_password (&$menu, $label, $variable) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_PASS;
  $menuitem['label']=$label;
  $menuitem['variable']=$variable;
  array_push($menu, $menuitem);
}

function hlib_menu_form_add_button (&$menu, $text) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_BUTTON;
  $menuitem['text']=$text;
  array_push($menu, $menuitem);
}

function hlib_menu_form_end (&$menu) {
  $menuitem = array();
  $menuitem['type']=HLIB_MENU_FORM_END;
  array_push($menu,$menuitem);
}

/*****
 *
 * Output the contents of the menu
 *
 */
function hlib_menu($menu=null) {
  echo "<div id=\"main\">\n";
  echo "<div id=\"menu\">\n";
  if ($menu==null) $menu=array();
  foreach ($menu as $menuitem) {
    switch ($menuitem['type']) {
    case HLIB_MENU_SECTION: echo "<div>".$menuitem['item']."</div>\n"; break;
    case HLIB_MENU_ITEM: echo "<a href=\"".$menuitem['url']."\">".$menuitem['item']."</a>\n"; break;
    case HLIB_MENU_SEPARATOR: echo "<hr/>\n";break;
    case HLIB_MENU_FORM : {
      echo "<form method=\"".$menuitem['method']."\" action=\"".$menuitem['action']."\"";
      if (!is_null($menuitem['id'])) {
	      echo " id=\"".$menuitem['id']."\"";
	      if ($menuitem['start_hidden']) {
	        hlib_script_add('/lib/js/hide.js',-1);
	        hlib_script_add("init_hidden('".$menuitem['id']."');","window.onload");   
	      }
      }
      echo ">\n"; 
      break;
    }
    case HLIB_MENU_FORM_ERROR: echo "<div class=\"error\">".$menuitem['message']."</div>\n"; break;
    case HLIB_MENU_FORM_HIDDEN: echo "<input type=\"hidden\" name=\"".$menuitem['variable']."\" value=\"".hlib_form_escape_value($menuitem['value'])."\"/>"; break;
    case HLIB_MENU_FORM_TEXT: echo "<div><label for=\"".$menuitem['variable']."\">".$menuitem['label']."</label><input type=\"text\" name=\"".$menuitem['variable']."\"></input></div>\n"; break;
    case HLIB_MENU_FORM_PASS: echo "<div><label for=\"".$menuitem['variable']."\">".$menuitem['label']."</label><input type=\"password\" name=\"".$menuitem['variable']."\"></input></div>\n"; break;
    case HLIB_MENU_FORM_BUTTON: {
      echo "<div class=\"button\"><button>".$menuitem['text']."</button></div>\n"; 
      break;
    }
    case HLIB_MENU_FORM_END: echo "</form>"; break;
    }
  }
  if (count($menu)==0) echo "&nbsp;";
  echo "</div><div id=\"menusepbar\">&nbsp;</div>\n";
  echo "<div id=\"content\">\n";
}

/******************************************************************************
 *
 * Formulaires
 *
 */

function hlib_form_escape_value ($value) {
  /*
   * nettoies les chaines de caracteres entrées des caracteres spéciaux
   * pour limiter les possibilités de XSS
   */
  return htmlentities($value,ENT_COMPAT,'UTF-8',false);
}

/****
 * Fonctions de gestions et d'affichage des erreurs dans les formulaires
 */

function hlib_form_add_error(&$errors, $variable, $message) {
  if (array_key_exists($variable, $errors)) $ve = $errors[$variable];
  else $ve = array();
  array_push($ve, $message);
  $errors[$variable] = $ve;
}

function hlib_form_display_errors($msg) {
  echo "<div class=\"error\">";
  echo $msg;
  echo "</div>\n";
}

function hlib_form_check_errors($form, $variable) {
  if (is_null($form)) return;
  if (array_key_exists($variable, $form)) {
    $msg = '';
    foreach ($form[$variable] as $error) {
      if (mb_strlen($msg)>0) 
	echo $msg.="<br/>\n";
      $msg.="$error";
    }
    hlib_form_display_errors($msg);
  }
}

/****
 * Fonctions de vérification et de nettoyage
 */

/* mot de passe */

function hlib_form_check_password($pass) {
  $p = trim ($pass);
  if (strlen($p)<8)
    return False;

  return True;
}

/* telephone */

function hlib_form_check_phone($phone) {
  $expr = '/^\ *(\+[0-9]+)?\ ?(\(\ *[0-9]+\ *\))?[0-9\ ]*$/';
  $v = preg_match($expr,$phone);
  return $v==1;
}

function hlib_form_clean_phone($phone) {
  // remove all extraneous space chars
  $phone = trim($phone);
  // TODO: should remove all occurences of multiple spaces between number blocks

  // all done
  return $phone;
}

/* code postal */

function hlib_form_check_post_code_fr ($codepostal) {
  $expr = '/^\ *[0-9]{5}\ *$/';
  $v = preg_match($expr,$codepostal);
  return $v == 1;
}

function hlib_form_check_post_code ($codepostal) {
  return hlib_form_check_post_code_fr ($codepostal);
}

function hlib_form_clean_post_code ($codepostal) {
  return trim($codepostal);
}

/* date */ 

function hlib_form_clean_date(&$date) {
  $date = trim($date);
  $expr = '/^\d{4}-\d{2}-\d{2}$/';
  $v = preg_match($expr,$date);
  return $v==1;  
}

function hlib_form_check_date ($date) {
  $y = intval(substr($date,0,4));
  $m = intval(substr($date,5,2));
  $d = intval(substr($date,8,2));
  return checkdate($m,$d,$y);
}

/* checkbox */

function hlib_form_clean_checkbox ($box) {
  if (trim($box)=='on') return true;
  else return false;
}

/* url */

function hlib_form_clean_url($url) {
  return trim($url);
}

function hlib_form_check_url($url,&$e) {
  GLOBAL $HTTP_OPTS;
  error_log('HTTP_OPTS : '.print_r($HTTP_OPTS,1));
  error_log('checking '.$url); 
  $u = parse_url($url);
  if (is_bool($u)&&!$u) {
    $e = 'Adresse mal formée';
    return false;
  }
  error_log('url could be parsed');
  if (array_key_exists('scheme',$u)) {
    if (array_key_exists('port',$u)) $port = $u['port'];
    else $port = 0;
    switch ($u['scheme']) {
    case 'http':
      if ($port==0) $port=80;
      break;
    case 'https':
      if ($port==0) $port=443 ;
      break;
    default:
      $e = 'Protocole \''.$u['scheme'].'\' inconnu (\'http\' ou \'https\' attendu)';
      return false;
    }
    $u['port']=$port;
  } else {
    $e = 'Type de protocole manquant (\'http\' ou \'https\' attendu)';
    return false; /* force http ?? better not */
  }
  error_log('scheme ok');
  if (array_key_exists('host',$u)) {
    $ipv4 = dns_get_record($u['host'],DNS_A);
		$ipv6 = dns_get_record($u['host'],DNS_AAAA);
		$ips = array_merge($ipv4, $ipv6);
    if ((is_bool($ips)&&!$ips)||(count($ips)==0)) {
      $e = 'Serveur \''.$u['host'].'\' introuvable';
      return false;
    }
    error_log('dns ok');
    //error_log(print_r($ips,1));
    if (!array_key_exists('proxyhost',$HTTP_OPTS)) {
      /* trouve si une des adresses réponds */
      $ok = false;
      foreach ($ips as $ip) {
        switch ($ip['type']) {
        case 'A':
  	  		$s=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
	  			$ok |= @socket_connect($s,$ip['ip'],$u['port']);
	  			error_log($ip['type'].' - '.$ip['ip'].':'.$u['port'].' '.($ok?'ok':'nok'));
	  			socket_close($s);
	  			break;
        case 'AAAA':
 	  			$s=socket_create(AF_INET6,SOCK_STREAM,SOL_TCP);
	  			$ok |= @socket_connect($s,$ip['ipv6'],$u['port']);
	  			error_log($ip['type'].' - ['.$ip['ipv6'].']:'.$u['port'].' '.($ok?'ok':'nok'));
	  			socket_close($s);
	  			break;
        default:
  	  		continue;
        }
      }
    } else {
      error_log('we have a proxy, can\'t check direct connexion');
      $ok = true;
    }
    if (!$ok) {
      $e = 'Connection au serveur impossible';
      return false;
    }
  } else {
    $e = 'Nom de serveur manquant';
    return false; /* si on a pas de host, c'est compromis... */
  }
  /* timeout a 5 secondes */
  /* TODO: handle when http_head doesn't exist */

  //$r = http_head($url,$HTTP_OPTS,$info);
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	$head = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
  error_log($url.' => '.$httpcode);  
  error_log($head);
  if ($httpcode>=400) {
    $e = 'Accès au document impossible';
    return false;
  }
  return true;
}

/* select */

function hlib_form_check_select ($value, $table) {
  GLOBAL $db;
  if (strlen($value)==0) return false;
  $sql="select key, value from ".$table." where key=$1";
  $r=pg_query_params($db, $sql, array(intval($value)));
  if (pg_num_rows($r)==0) $ok=false;
  else $ok=true;
  pg_free_result($r);
  return $ok;
}

/* multi select */

function hlib_form_clean_multi ($values) {
  if (is_null($values)) return $values;
  if (!is_array($values)) return $values;
  $val = array();
  sort($values, SORT_NUMERIC);
  foreach($values as $v) {
    $v = intval($v);
    if ($v==0) continue;
    if (!in_array($v, $val)) array_push($val, $v);
  }
  return $val;
}

function hlib_form_check_multi ($values, $table) {
  GLOBAL $db;
  $sql="select key, value from ".$table." where key=$1;";
  $ok = true;
  foreach($values as $v) {
    $r = pg_query_params($db, $sql, array($v));
    if (pg_num_rows($r)==0) $ok=false;
    pg_free_result($r);
  }
  return $ok;
}

/******************************************************************************
 *
 * Fonction de génération du HTML pour les formulaires
 *
 */

/*****
 *
 * Début de formulaire
 *
 */
function hlib_form ($method, $action, $errors, $options=null) {
	$id=null;
	$style=null;
	$stylesheet=null;
	if (is_array($options)) {
		if (array_key_exists('id',$options)) $id=$options['id'];
		if (array_key_exists('style',$options)) $style=$options['style'];
		if (array_key_exists('stylesheet',$options)) $stylesheet=$options['stylesheet'];
	}
  if ($id!==null)
    hlib_form_check_errors ($errors, $id);
  echo "<form method=\"".$method. "\" action=\"".$action."\"";
  if (!is_null($id)) echo " id=\"".$id."\"";
  if (!is_null($style)) echo " style=\"".$style."\"";
  echo ">\n";
	if (!is_null($stylesheet)) {
		echo "<style type=\"text/css\" scoped>";
		echo $stylesheet;
		echo "</style>\n";
	}
  return $errors;
}

/*****
 *
 * Champ caché
 *
 */
function hlib_form_hidden($form, $variable, $value="") {
  echo hlib_form_check_errors ($form, $variable);
  echo "<input type=\"hidden\" name=\"".$variable."\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo "/>";
}

/*****
 *
 * Champ informatif (ne peut être changé)
 *
 */
function hlib_form_display ($form, $label, $value="") {
  echo "<div>";
  echo "<label>".$label."</label>";
  echo "<input type=\"text\" disabled=\"true\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo "/>";
  echo "</div>\n";
}

/*****
 *
 * Champ texte
 *
 */
function hlib_form_text ($form, $label, $variable, $value="", $width=null, $length=null, $help=null) {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<input type=\"text\" name=\"".$variable."\"";
  if (!is_null($width)) echo " style=\"width:".$width."\"";
  if (!is_null($length)) echo " maxlength=\"".$length."\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo "/>";
  if (!is_null($help)) echo "<div class=\"formhelp\">".$help."</div>";
  echo "</div>\n";
}

/*****
 *
 * Champ date (avec un calendrier en jqueryui)
 *
 */
function hlib_form_date ($form, $label, $variable, $value="") {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
	echo "<style type=\"text/css\" scoped>";
	echo "input.hasDatepicker{width:60pt;text-align:center;}";
	echo "</style>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<input type=\"text\" id=\"".$variable."\" name=\"".$variable."\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo "/></div>\n";

  // https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.js
  
  hlib_script_add("$(function() { $(\"#".$variable."\").datepicker({".
		 "showOn:\"button\",".
		 "buttonImage: \"/lib/images/calendar.gif\",".
		 "buttonImageOnly: true,".
		 "dateFormat: \"yy-mm-dd\"".
		 "});});","_begin");
  
}

/*****
 *
 * Champ date (avec un calendrier en jqueryui)
 *
 */
function hlib_form_datetime ($form, $label, $variable, $value="") {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
	echo "<style type=\"text/css\" scoped>";
	echo "input.hasDatepicker{width:100pt;text-align:center;}";
	echo "</style>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<input type=\"text\" id=\"".$variable."\" name=\"".$variable."\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo "/></div>\n";

	hlib_script_add(
		"$(function() { $(\"#".$variable."\").datetimepicker({".
		"  showOn: \"button\",".
		"  buttonImage: \"/lib/images/calendar.gif\",".
		"  buttonImageOnly: true,".
		"  showSecond: true,".
		 "dateFormat: \"yy-mm-dd\",".
		 "timeFormat: \"HH:mm:ss\"".
		"}) });","_begin");
}

/*****
 *
 * Champ mot de passe
 *
 */
function hlib_form_password ($form, $label, $variable, $value="", $help=null) {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<input type=\"password\" name=\"".$variable."\"";
  if (strlen($value)>0) echo " value=\"".hlib_form_escape_value($value)."\"";
  echo ">"; 
  if (!is_null($help)) echo "<div class=\"formhelp\">".$help."</div>";  
  echo "</div>\n";
}

/*****
 *
 * Champ zone de texte
 *
 */
function hlib_form_textarea ($form, $label, $variable, $value="", $width=null, $height=null, $help=null) {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<textarea name=\"".$variable."\"";
  $s='';
  if (!is_null($width)) $s.="width:".$width.";";
  if (!is_null($height)) $s.="height:".$height.";";
  if (strlen($s)>0) $s=" style=\"".$s."\"";
  echo $s.">";
  if (strlen($value)>0) echo hlib_form_escape_value($value);
  echo "</textarea>";
  if (!is_null($help)) echo "<div class=\"formhelp\">".$help."</div>";
  echo "</div>\n";
}

/*****
 *
 * Champ select.
 * Gère la sélection de plusieurs valeurs dans la meme liste
 * TODO: rendre générique l'accès à la base de donnée pour les vues
 *
 */
function hlib_form_select ($form, $label, $variable, $value="", $values=null, $options=null) {
  GLOBAL $db;

  $onchange = null;
  $multi=false;
  $width=null;
  $help=null;
  if (!is_null($options) and is_array($options)) {
    if (array_key_exists('onchange',$options)) $onchange = $options['onchange'];
    if (array_key_exists('multi',$options)) $multi=$options['multi'];
    if (array_key_exists('width',$options)) $width=$options['width'];
    if (array_key_exists('help',$options)) $help=$options['help'];
  }
  echo hlib_form_check_errors ($form, $variable);
  if ($multi) {
    GLOBAL $_hlib_scripts;
    hlib_script_add('/lib/js/multiselect.js', -1);
    echo "<div>";
    echo "<label for=\"".$variable."[]\">".$label;
    if (array_key_exists('operator', $options)) {
      $op = $options['operator'];
      if (array_key_exists('type',$op)) $op_type=$op['type']; else $op_type=null;
      if (array_key_exists('name',$op)) $op_name=$op['name']; else $op_name=null;
      if (array_key_exists('value',$op)) $op_value=$op['value']; else $op_value=null;
      if (array_key_exists('labels',$op)) $op_labels=$op['labels']; else $op_labels=null;
      if (array_key_exists('values',$op)) $op_values=$op['values']; else $op_values=null;
      if (!(is_null($op_type)   ||
	    is_null($op_name)   ||
	    is_null($op_labels) ||
	    is_null($op_values)) &&
	  ($op_type=='radio' ||
	   $op_type=='checkbox')
	  ) {
	echo "<br/>\n";
	$o = array();
	for($i=0;$i<count($op_labels);$i++) {
	  $s = "<input type=\"".$op_type."\" ";
	  $s.= "name=\"".$op_name;
	  if ($op_type=='checkbox') $s.="[]";
	  $s.= "\" ";
	  $s.="value=\"".$op_values[$i]."\" ";
	  if (!is_null($op_value)) 
	    if ($op_type=='radio') 
	      if ($op_value==$op_values[$i])
		$s.="checked";
	  $s.="/>".$op_labels[$i];
	  array_push($o,$s);
	}
	echo implode("&nbsp;",$o);
      }
    }
    echo "</label>";
    echo "<div id=\"".$variable."\" class=\"wrapper\">";
    echo "</div>";
    if (!is_null($help)) echo "<div class=\"formhelp\">".$help."</div>";
    echo "</div>\n";
    $sql = "select key, value from ".$values.";";
    pg_send_query ($db, $sql);
    $r = pg_get_result ($db);
    $_val = array();
    while ($row = pg_fetch_assoc ($r)) array_push($_val, array($row['key'],$row['value']));
    hlib_script_add("var ".$variable." = new Array();","_begin");
    hlib_script_add($variable."['name']= \"".$variable."\";","_begin");
    hlib_script_add($variable."['init']= ".((!is_array($value))?"null":json_encode($value)).";","_begin");
    hlib_script_add($variable."['width']= ".(is_null($width)?"null":"'".$width."'").";","_begin");
    hlib_script_add($variable."['values'] = ".json_encode($_val).";","_begin");
    pg_free_result ($r);
    hlib_script_add( "ms_init(".$variable.");",'window.onload');
  } else {
    echo "<div>";
    echo "<label for=\"".$variable."\">".$label."</label>";
    echo "<select name=\"".$variable."\"";
    if (!is_null($onchange)) echo " onchange=\"".hlib_form_escape_value($onchange)."\"";
    if (!is_null($width)) echo " style=\"width:".$width.";\"";
    echo ">\n";
    if (!is_null($values)) {
      if (is_string($values)) {
	// option de base - vide
	echo "<option value=\"\"></option>\n";
	// nom de la vue dans la base de données
	$sql = "select key, value from ".$values.";";
	pg_send_query($db,$sql);
	$r = pg_get_result($db);
	while ($row = pg_fetch_assoc($r)) {
	  echo "<option value=\"".hlib_form_escape_value($row['key'])."\"";
	  if (strcmp($row['key'],$value)==0) echo " selected=\"1\"";
	  echo ">".$row['value']."</option>\n";
	}
	pg_free_result($r);
      } else if (is_array($values)) {
	// associative array de valeurs
	foreach($values as $key => $val) {
	  $s = "";
	  if ($key==$value) $s = " selected";
	  echo "<option value=\"".$key."\"".$s.">".$val."</option>\n";
	}
      } else {
	// unknown type
	error_log("hlib_form_select : type ".gettype($values)." not supported for values");
      }
    }
    echo "</select>";
    if (!is_null($help)) echo "<div class=\"formhelp\">".$help."</div>";
    echo "</div>";
  }
}

/*****
 *
 * Boîte à cocher
 *
 */
function hlib_form_checkbox ($form, $label, $variable, $value="", $group=null) {
  echo hlib_form_check_errors ($form, $variable);
  echo "<div>";
  echo "<label for=\"".$variable."\">".$label."</label>";
  echo "<input type=\"checkbox\" name=\"".$variable."\"";
  /*
   * si on a un type booléen et qu'il est a true
   * si on a la chaine 'on' (comme dans un formulaire)
   * si on a la chaine 't' (comme en sql)
   */
  if ((is_bool($value)&&$value)||
      (!strcmp($value,'on'))||
      (!strcmp($value,'t'))) 
    echo " checked";
  echo "/></div>\n";
}

/*****
 *
 * Bouton (validation de formulaire)
 *
 */
function hlib_form_button ($form, $text, $action="") {
  echo hlib_form_check_errors ($form, 'action');
  echo "<div>";
  echo "<button";
  if (strlen($action)>0) echo " name=\"action\" value=\"".$action."\"";
  echo ">".$text."</button>";
  echo "</div>\n";
}

/*****
 *
 * Boutons multiples
 *
 */
function hlib_form_buttons ($form, $buttons) {
	if (is_array($buttons)) {
  	echo "<div><span>";
		foreach ($buttons as $b) {
			if (array_key_exists('text',$b)) $text = $b['text'];
			else $text = 'bouton vide';
			if (array_key_exists('action',$b)) $action = $b['action'];
			else $action = '';
  		echo "<button";
  		if (strlen($action)>0) echo " name=\"action\" value=\"".$action."\"";
  		echo ">".$text."</button>";
		}
  	echo "</span></div>\n";
	}
}

/*****
 *
 * Navigation dans une liste
 *
 */
function hlib_form_nav ($form, $nav, $current_pos, $last_item, $nb_elems, 
												$first_action, $prev_action, $next_action, $last_action) {
	echo hlib_form_check_errors ($form, 'nav');
	echo "<div id=\"".$nav."\">";
	echo "<style type=\"text/css\" scoped>";
	echo "#".$nav."{width:600pt;}";
	echo "#".$nav." > span{display:inline-block;margin-left:0px !important;}";
	echo "#".$nav." > span > button{width:5em;}";
	$w1 = 'calc(10em + 6px);';
	$w2 = 'calc(600px - 20em - 14px);';
	$wk = '-webkit-';
	$wm = '-moz-';
	$w  = 'width:';
	$w1 = $w.$wk.$w1.$w.$wm.$w1.$w.$w1;
	$w2 = $w.$wk.$w2.$w.$wm.$w2.$w.$w2;
	echo "span#".$nav."-left{text-align:left;".$w1."}";
	echo "span#".$nav."-center{text-align:center;".$w2."}";
	echo "span#".$nav."-right{text-align:left;".$w1."}";
	echo "</style>";
	echo "<span id=\"".$nav."-left\"";
	if ($current_pos==0) echo "style=\"visibility:hidden;\" ";
	echo "><button name=\"action\" value=\"".$first_action."\">&lt;&lt;</button>";
	echo "<button name=\"action\" value=\"".$prev_action."\">&lt;</button></span>";
	
	// variables 
	echo "<input type=\"hidden\" name=\"".$nav."[current-pos]\" value=\"".$current_pos."\"/>";
	echo "<input type=\"hidden\" name=\"".$nav."[perpage]\" value=\"".$nb_elems."\"/>";
	// fill in
	echo "<span id=\"".$nav."-center\">";
	$last_page_item = $current_pos+$nb_elems-1;
	if ($last_page_item>$last_item)
		$last_page_item = $last_item;
	// les valeurs sont calculées en commençant à 0...
	echo "éléments ".($current_pos+1)." à ".($last_page_item+1)." sur ".($last_item+1);
	echo "</span>";

	echo "<span id=\"".$nav."-right\"";
	if (($current_pos+$nb_elems)>=$last_item) echo  "style=\"visibility:hidden;\" ";
	echo "><button name=\"action\" value=\"".$next_action."\">&gt;</button>";
	echo "<button name=\"action\" value=\"".$last_action."\">&gt;&gt;</button></span>";
	echo "</div>\n";
}

/*****
 *
 * Fin de formulaire
 *
 */
function hlib_form_end () {
  echo "</form>\n";
}

/******************************************************************************
 *
 * Tableaux de données
 *
 * Le tableau est un tableau contenant
 * {
     "id"     => < id du tableau > (optionnel),
     "header" => [
                   {
                     "text"     => < nom de colonne >,
                     "class"    => < nom de class css > (optionnel),
                     "colclass" => < nom de la class css du col > (optionnel) 
                   },
                   (... plus d'entêtes )
                 ],
     "data"   => [
                   {
                     "class"    => < nom de class css > (optionnel),
                     "style"    => < style a appliquer a la ligne > (optionnel),
                     "link"     => < lien vers une page de détails > (optionnel),
                     "checkbox" => {
                                     "variable" => < nom de la variable dans la checkbox >,
                                     "value"    => < valeur de la checkbox >
                                   } (optionnel),
                     "values"   => [
                                     < valeur colonne 1 >,
                                     < valeur colonne 2 >,
                                     (...)
                                   ]
                   },
                   (... plus de lignes )
                 ]
 * }
 */

function hlib_datatable ($table) {
  /* haut de la table */
  echo "<table";
  if (array_key_exists('id',$table))
    echo " id=\"".$table['id']."\"";
  echo ">\n";

  /* entêtes de la table */
  if (array_key_exists('header', $table))
    $cellstyle = hlib_datatable_headers ($table['header']);
  else { 
    error_log ('ERROR : hlib_datatable - "header" is compulsory');
    return;
  }

  /* contenu de la table */
  if (array_key_exists('data', $table))
    hlib_datatable_data ($table['data'], $cellstyle);
  else {
    error_log ('ERROR : hlib_datatable - "header" is compulsory');
    return;
  }
  /* pied de la table */
  echo "</table>\n";
}

function hlib_datatable_headers ($headers) {
  $cgroup = '';
  $hdr = '';
  $cellstyle = array();
  foreach ($headers as $h) {
    // contents of the colgroup
    $cgroup.="<col";
    // options de colonne
    if (array_key_exists('colclass',$h))
      $cgroup.=" class=\"".$h['colclass']."\"";
    if (array_key_exists('colstyle',$h))
      $cgroup.=" style=\"".$h['colstyle']."\"";
    if (array_key_exists('cellstyle',$h))
      array_push($cellstyle,$h['cellstyle']);
    else
      array_push($cellstyle,null);

    $cgroup.="/>";
    // contents of the table headers
    $hdr.="<th";
    if (array_key_exists('class',$h))
      $hdr.=" class=\"".$h['class']."\"";
    $hdr.=">".$h['text']."</th>\n";
  }
  echo "  <colgroup>".$cgroup."</colgroup>\n";
  echo "  <thead>\n";
  echo "    <tr>".$hdr."</tr>\n";
  echo "  </thead>\n";
  return $cellstyle;
}

function hlib_datatable_data ($data, $cellstyle) {
  echo "<tbody>\n";
  $nrow = 1;
  foreach ($data as $row) {
    // début de ligne et classes
    echo "<tr";
    if ($nrow%2==1)
      $class = 'odd';
    else
      $class = '';
    if (array_key_exists('class', $row)) {
      if (strlen($class)>0)
        $class.=' ';
      $class.=$row['class'];
    }
    if (strlen($class)>0)
      echo " class=\"".$class."\"";
    // style
    if (array_key_exists('style', $row))
      echo " style=\"".$row['style']."\"";

    // lien
    $haslink=false;
    if (array_key_exists('link', $row)) {
      echo " onclick=\"document.location.href='".$row['link']."'\"";
      $haslink=true;
    }
    echo ">";
    // TODO: handle checkbox column

    // colonnes
    if (array_key_exists('values', $row))
      for($i=0;$i<count($row['values']);$i++) {
        $v = $row['values'][$i];
        $s = $cellstyle[$i];
        if ($haslink)
          $s.="cursor:pointer;";
        echo "<td";
        if ($s!=null)
          echo " style=\"".$s."\"";
        echo ">".$v."</td>";
      }
    else {
      error_log ('ERROR : hlib_datatable_data - "values" in row is compulsory');
      return;
    }
    // fin de ligne
    echo "</tr>\n";
    $nrow++;
  }
  echo "</tbody>\n";
}

/******************************************************************************
 *
 * pied de page
 *
 */

/*****
 *
 * Fonction utilitaire pour ajouter les scripts dans le pied de page
 *
 */
function _append_scripts($scripts=null) {
  GLOBAL $_hlib_scripts;
  
  if (is_null($scripts)) $scripts=$_hlib_scripts;

  // d'abord les scripts a l'index numérique (aka les fichiers
  $nbts = 0;
  foreach($scripts as $key => $values) {
    if (!is_int($key)) {
      $nbts++;
      continue;
    }
    echo "<script type=\"text/javascript\" src=\"".$values."\"></script>\n";
  }

  // puis les scripts a index texte (les statements)
  if ($nbts==0) return;
  echo "<script type=\"text/javascript\">\n";
  if (array_key_exists("_begin",$scripts)) {
    foreach($scripts["_begin"] as $statement) echo $statement."\n";
  }
  echo "\n";
  foreach($scripts as $key => $values) {
    if (is_int($key)) continue;
    if (strcmp($key, "_begin")==0) continue;
    if (strcmp($key, "_default")==0) {
      foreach($values as $statement) echo $statement."\n";
    } else {
      echo $key." = function () {\n";
      foreach($values as $statement) echo "    ".$statement."\n";
      echo "};\n";
    }
  }
  echo "</script>\n";
}

/*****
 *
 * Génération du pied de page
 *
 */
function hlib_footer($scripts=null) {
  GLOBAL $_hlib_scripts;

  /* 
   * ajout de jquery 
   */
  hlib_add_jquery ();

  /* 
   * Ajout de la mention de copyright
   */
  echo "</div></div>\n<div id=\"footer\">Conception Raphaël Jacquot 2011-2012<br/>\n";
  echo "<a href=\"http://ipag.obs.ujf-grenoble.fr/?lang=fr\">";
  echo "<img src=\"/lib/images/logo-ipag-small.png\"/>";
  echo "</a>";
  echo "</div>\n";
  /*
   * Ajout des scripts
   */
  if (!is_null($scripts)) 
    _append_scripts($scripts);
  _append_scripts();
  echo "</body>\n</html>\n";
}

?>
