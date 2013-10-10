<?php

function unparse_url($parsed_url) { 
 	$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : ''; 
	$host     = isset($parsed_url['host']) ? $parsed_url['host'] : ''; 
	$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : ''; 
	$user     = isset($parsed_url['user']) ? $parsed_url['user'] : ''; 
	$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : ''; 
	$pass     = ($user || $pass) ? "$pass@" : ''; 
	$path     = isset($parsed_url['path']) ? $parsed_url['path'] : ''; 
	$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : ''; 
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : ''; 
	return "$scheme$user$pass$host$port$path$query$fragment"; 
}

function get_install_path () {
  $dir = dirname(dirname(__file__));
  return $dir;
}

function mkdir_webserver_recurse ($base, $path) {
  if (dirname($path)!="/") {
    mkdir_webserver_recurse($base, dirname($path));
  } 
  $path = $base.$path;
  if (!file_exists($path)) {
    mkdir ($path, 0777);
    chmod ($path, 0775);
    chgrp ($path, 'www-data');
  } else {
    // check rights
    if (!is_writable($path)) {
      $posixuser = posix_getpwuid (posix_geteuid());
      echo "WARNING : ".$path." is not writable by current user ".$posixuser['name']."\n";
    }
  }
}

function make_webserver_dir ($path) {
  $basepath = get_install_path();
  $newpath = substr($path, strlen($basepath));
  mkdir_webserver_recurse($basepath,$newpath);
}

function get_url_contents($url) {
	global $HTTP_OPTS;
	$ch = curl_init();
	if (array_key_exists('timeout', $HTTP_OPTS))
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, intval($HTTP_OPTS['timeout']));
	else
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
	if (array_key_exists('proxy', $HTTP_OPTS)) {
		$u = parse_url($HTTP_OPTS['proxy']);
		if (!is_bool($u)) {
			if (!is_null($u['host']))
				curl_setopt($ch, CURLOPT_PROXY, $u['host']);
			if (!is_null($u['port']))
				curl_setopt($ch, CURLOPT_PROXYPORT, $u['port']);
		}
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$data = curl_exec($ch);
	curl_close($ch);
	return $data;
}

function cache_url_to_file ($url, $file) {
  //echo "saving url ".$url." to file ".$file."\n";
	$u = parse_url($url);
	if (array_key_exists('scheme',$u)) {
		$dest = fopen($file, 'wb');
		if ($dest) {
			global $HTTP_OPTS;
			$ch = curl_init();
			if (array_key_exists('timeout', $HTTP_OPTS))
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, intval($HTTP_OPTS['timeout']));
			else
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			if (array_key_exists('proxy', $HTTP_OPTS)) {
				$u = parse_url($HTTP_OPTS['proxy']);
				if (!is_bool($u)) {
					if (!is_null($u['host']))
						curl_setopt($ch, CURLOPT_PROXY, $u['host']);
					if (!is_null($u['port']))
						curl_setopt($ch, CURLOPT_PROXYPORT, $u['port']);
				}
			}
			curl_setopt($ch, CURLOPT_URL, $url);
		  curl_setopt($ch, CURLOPT_FILE, $dest);
			$data = curl_exec($ch);
			fclose($dest);
		} else {
			error_log("Unable to open destination file '".$file."' for writing");
		}
	} else {
		copy ($u['path'], $file);
		$data = true;
	}
	if ($data==true) {
		chmod ($file, 0664);
 		chgrp ($file, 'www-data');
	 	return true;
	}
  return false;
}

function get_remote_ip () {
  return $_SERVER['REMOTE_ADDR'];
}

function find_dirs($dir) {
  $dirs = array();
  $d = opendir($dir);
  if ($d!==false) {
    while (($td = readdir($d))!==false) {
      // skip '.' and '..'
      if (($td!='.')&&($td!='..')) {
        $fn = $dir.'/'.$td;
        // stat file to identify directories
        if (is_dir($fn)) array_push($dirs,$fn);
      }
    }
    closedir($d);
  }
  return $dirs;
}


function list_all_files($fn) {
  $files = array();
  if (is_dir($fn)) {
    // file is a directory, recurse
    $d = opendir($fn);
    if ($d!==false) {
      while (($f=readdir($d))!==false) {
        // skip '.' and '..'
        if (($f!='.')&&($f!='..')) {
          $td = list_all_files($fn.'/'.$f);
          $files = array_merge($files,$td);
        }
      }
    }
  } else {
    // basic file, just return
    array_push($files,$fn);
  }
  sort($files);
  return $files;
}

function dom_get_text ($dom) {
	$t = '';

	if (is_object($dom)) {
		if ($dom->nodeType==XML_ELEMENT_NODE) {
			$c = $dom->firstChild;
			while ($c != null) {
				$t.=dom_get_text($c);
				$c = $c->nextSibling;
			}
		} elseif ($dom->nodeType==XML_TEXT_NODE) {
			return $dom->wholeText;
		}
	}	
	return $t;
}

?> 
