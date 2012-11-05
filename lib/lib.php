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

function cache_url_to_file ($url, $file) {
  echo "saving url ".$url." to file ".$file."\n";
  $src = fopen ($url, 'rb');
  if ($src) {
    $dest = fopen ($file, 'wb');
    while (!feof($src))
      fwrite ($dest, fread ($src, 4096));
    fclose($src);
    fclose($dest);
    chmod ($file, 0664);
    chgrp ($file, 'www-data');
    return true;
  }
  echo "Unable to open source ".$url."\n";
  return false;
}

function get_remote_ip () {
  return $_SERVER['REMOTE_ADDR'];
}

?> 
