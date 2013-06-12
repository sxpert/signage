<?php

//
// apod.php
// handles the apod feed
//

$d = dirname(__file__);
require_once ($d.'/../db.php');
require_once ($d.'/../lib.php');
require_once ($d.'/../signlib.php');

class FeedAPOD {
  private $urlbase;
  private $feedinfo;

  /*****************************************************************************
   *
   * Debugging functions
   */
  private function dumpDom($dom, $item=null) {
    if ($item==null) $item = $dom;
    echo $dom->saveXML($item)."\n";
  } 

  private function dumpDomList($dom,$list) {
    if ($list->length>0) {
      for($i=0;$i<$list->length;$i++) {
	echo "------- item ".$i."\n";
	$this->dumpDom($dom,$list->item($i));
      }
    } else
      echo "------- item list empty\n";
  }
  
  /*****************************************************************************
   *
   * Extraction de données dans le dom
   *
   */

  /****
   *
   * Titre de la page
   */

  private function findCaption ($doc) {
    $caption = null;
    $title = $doc->getElementsByTagName('title');
    if ($title->length>0) {
      $title = $title->item(0);
      $title = trim($title->textContent);
      $pos = strpos($title, ' - ');
      if ($pos>0)
	$title = substr($title, $pos+3);
      $caption = trim($title);
    }
    return $caption;
  }
  
  /****
   * 
   * Fonction utilitaire de nettoyage du texte
   * TODO: traiter les mots <majuscule><point> a la fin des lignes (recoller avec la ligne d'après)
   */
  private function cleanText($string) {
    $tl = explode("\n",$string);
    $ta = [];
    foreach($tl as $l) {
      $t = explode(" ",$l);
      foreach($t as $w) {
	$w = trim($w);
	if ($w!='') {
	  $count = count($ta);
	  $last = $count-1;
	  if (($w==',')||($w=='.')||($w==')')||($w==':')||($w==';')) {
	    // append to previous string
	    if ($count>0) 
	      $ta[$last].=$w;
	  } else {
	    if ($count>0) {
	      if ($ta[$last]=='(') 
		$ta[$last].=$w;
	      else
		array_push($ta,$w);
	    } else
	      array_push($ta,$w);
	  }
	}
      }
    }
    
    if ($ta[0]=='Explanation:')
      $ta[0]=null;
    $string = implode(' ',$ta);
    // cut lines at '.'
    $tl = explode('. ',$string);
    for($i=0;$i<count($tl);$i++)
      $tl[$i] = trim($tl[$i]);
    $string = implode(".\n",$tl);
    // handle '!' 
    $tl = explode('! ',$string);
    for($i=0;$i<count($tl);$i++)
      $tl[$i] = trim($tl[$i]);
    $string = implode(" !\n",$tl);
    // handle '?'
    $tl = explode('? ',$string);
    for($i=0;$i<count($tl);$i++)
      $tl[$i] = trim($tl[$i]);
    $string = implode(" ?\n",$tl);
    
    return $string;
  }
  
  /****
   *
   * Trouve le noeud des explications
   */
  private function findExplNode ($list) {
    for ($i=0;$i<$list->length;$i++) {
      $item = $list->item($i);
      //if (trim($item->textContent)=='Explanation:')
      if (strncmp(trim($item->textContent),'Explanation',11)==0)
	return $item;
    }
    return null;
  }

  /****
   *
   * Trouve les explications
   */
  private function findExplanations ($doc) {
    $expl = null;
    $par = $doc->getElementsByTagName('p');
    if ($par->length>0) {
      for ($i=0;$i<$par->length;$i++) {
	$p = $par->item($i);
	$bold = $p->getElementsByTagName('b');
	/* skip if there's no bold */
	if ($bold->length>0) {
	  if (strncmp(trim($bold->item(0)->textContent),'Explanation',11)==0) {
	    /* nettoyage du contenu */
	    $ex = '';
	    $c = $p->childNodes;
	    for($i=0;$i<$c->length;$i++) {
	      $e = $c->item($i);
	      switch ($e->nodeType) {
	      case XML_TEXT_NODE :
		$t=trim($e->textContent);
		if ($t!='') 
		  $ex.=$t.' ';
		break;
	      case XML_ELEMENT_NODE :
		$t=trim($e->textContent);
		$ex.=$t.' ';
		break;
	      default:
		echo "Unknown>";
		$this->dumpDom($doc,$e);
		exit(0);
	      }
	    }
	    
	    $expl = $ex;
	  } 
	} 
      }
    } 
    if ($expl==null) {
      $h3 = $this->findExplNode($doc->getElementsByTagName('h3'));
      if ($h3!=null) {
	$p = $h3->nextSibling;
	while (($p->nodeType!=XML_ELEMENT_NODE)||($p->nodeName!='p')) { 
	  $expl.=trim($p->textContent)." ";
	  $p = $p->nextSibling;
	}
      }
    }
    if ($expl==null) {
      $b = $this->findExplNode($doc->getElementsByTagName('b'));
      if ($b!=null) {
	$p = $b->nextSibling;
	while ($p!=null) { 
	  if ($p->nodeType==XML_ELEMENT_NODE) {
	    if ($p->nodeName=='p') break;
	    if ($p->nodeName=='center') break;
	  }
	  $expl.=trim($p->textContent)." ";
	  $p = $p->nextSibling;
	}
	if ($p==null) {
	  $parent = $b->parentNode;
	  if (!(($parent->nodeType==XML_ELEMENT_NODE)&&($parent->nodeName=='td'))) {
	    echo $expl."\n";
	    $this->dumpDom($doc,$b->parentNode);
	    exit(0);
	  }
	  // we are at the end of the td container, all is ok
	}
      } 
    }
    
    if ($expl==null) {
      echo "ERROR FINDING EXPLANATIONS\n";
      $this->dumpDom($doc,$doc);
      exit(0);
    }
    // nettoyer le contenu
    $expl = $this->cleanText($expl);
    return $expl;
  }

  /****
   *
   * Trouve les images
   */

  private function findImage ($doc) {
    // First check the wierd cases 
    // iframe (contains a youtube video ?) 
    $iframe = $doc->getElementsByTagName('iframe');
    if ($iframe->length==1)
      return $iframe->item(0)->getAttribute('src');
    if ($iframe->length>1) {
      // more than one iframe... WTF
      $this->dumpDomList($doc,$iframe);
      exit(0);
    }
    // java applet from hell ?
    $applet = $doc->getElementsByTagName('applet');
    if ($applet->length==1) {
			error_log('Applet, nothing to see here');
      return null;
    }
    // stupid object for microsoft video feed ?
    $object = $doc->getElementsByTagName('object');
    if ($object->length>0) {
			error_log('Object, nothing to see here');
      return null;
    }
    // gah, worse, could be flash ;-)
    $embed = $doc->getElementsByTagName('embed');
    if ($embed->length>0) {
			error_log('Embed, nothing to see here');
      return null;
    }
    // get image url
    $links = $doc->getElementsByTagName('a');
		var_dump($links);
    /* finds which one contains an img tag */
    $img = null;
    $valid = [];
    for($i=0;$i<($links->length);$i++) {
      $item = $links->item($i);
      $t = $item->getElementsByTagName('img');
      if ($t->length==1){
				/* look for the one with an 'alt' */
				$image = $t->item(0);
				if ($image->attributes->getNamedItem('alt')!=null) {
					array_push($valid, $item->getAttribute('href'));
					array_push($valid, $image->attributes->getNamedItem('src')->textContent);
				}
     	}
	  }
  	if (count($valid)>0) {
      /* find the local one */
			$img = array();
    	foreach ($valid as $url) {
				var_dump($url);
			  $u = parse_url($url);
				if (array_key_exists('scheme',$u))
				  continue;
				if (array_key_exists('host',$u))
				  continue;
				array_push($img,$u['path']);
     	}
			if (count($img)==0)
				$img=null;
   	}
    if ($img==null) {
 	    $images = $doc->getElementsByTagName('img');
   	  if ($images->length==1)
				$img = $images->item(0)->getAttribute('src');
    }
 	  if ($img != null) {
			if (!is_array($img))
	   	  return $this->urlbase.'/'.$img;
			$im = array();
			foreach($img as $i)
				array_push($im,$this->urlbase.'/'.$i);
			return $im;
		}
    // could it be some crazy javascript ?
    $script = $doc->getElementsByTagName('script');
    if ($script->length>0) {
			error_log('Script, nothing to see here');
      return null;
    }
    $this->dumpDomList($doc,$images);
 	  echo "UNABLE TO FIND IMAGE\n";
   	$this->dumpDom($doc);
    exit(0);
 	}
  
  /*****************************************************************************
   *
   * Fonction de récupération du contenu
   *
   */
	
	/****
	 *
	 * Get the info about the feed from the database (in particular the URL)
	 *
	 */
	private function getFeedInfo() {
    $db = db_connect();
    if ($db==null) {
      echo "major problem, unable to connect to the database\n";
      return;
    }
    echo "connected to the database\n";
    $res = db_query('select f.id, f.url from feeds as f, feed_types ft where f.id_type=ft.id and ft.name=\'apod\';');
    if ($res==false) {
      echo "major problem, unable to find url for APOD feed\n";
      return;
    }
    if (db_num_rows($res)!=1) {
      echo "major problem, multiple feeds with apod type\n";
      return;
    }
    $this->feedinfo = db_fetch_assoc($res);
		$this->feedinfo['id'] = intval($this->feedinfo['id']);
    $u = parse_url($this->feedinfo['url']);
    $u['path'] = dirname($u['path']);
    $this->urlbase = unparse_url($u);
  }

	
	/****
	 *
	 * get the item date from the file name
	 *
	 */
	private function getDate($fname) {
	  $y = substr($fname,2,2);
	  $m = substr($fname,4,2);
	  $d = substr($fname,6,2);
	  if ($y[0]=='9') 
	    $y = '19'.$y;
	  else
	    $y = '20'.$y;
	  $date = $y.'-'.$m.'-'.$d;
		return $date;
	}

	private function getImageDir($date) {
		$s = substr($date,0,7);
		$s[4]='/';
		return $s;
	}

  /*****
   * 
   * Récupère une entrée du contenu APOD
   */
  private function getApod($url) {
		echo "\n===================================================\n".$url;
		$d = get_url_contents($url);
    if ($d) {
      echo "\n";
      // load into DOM
      $doc = new DomDocument();
      if (@$doc->loadHTML($d)) {
	      //his->dumpDom($doc,$doc);
	      //
	      echo ">>>>> caption\n";
	      $caption = $this->findCaption($doc);
	      echo $caption."\n";
      	//
	      echo ">>>>> explanations\n";
	      $explanations = $this->findExplanations($doc);
	      echo $explanations."\n";
	      //
	      $img = $this->findImage($doc);
	      echo ">>>>> img\n";
	
	      if ($img!=null) {
					// img can be an array of urls with the first one preferred
	        //get date from url
	        $uhtml = parse_url($url);
	        $pathhtml = $uhtml['path'];
	        $filehtml = basename($pathhtml); 
					$date = $this->getDate($filehtml);

					// handle storing the picture locally
					error_log('caching images');
					$mgr = new ImageManager();
					$mgr->fetch($img,'apod');
					foreach ($img as $i) {
	          $uimg = parse_url($i);
  	        $pathimg = $uimg['path'];
    	      $fileimg = basename($pathimg);
      	    // ckeck extension
        	  $extension = pathinfo($pathimg,PATHINFO_EXTENSION);
          	// TODO: look for a ? in the url...
 	 	 	    	error_log('imagefile is '.$pathimg.' file extension is '.$extension);
          	$idx = array_search($extension,array('jpg','gif','png','tif'));
          	error_log('idx='.print_r($idx,1));
          	if ($idx!==false) {
	        	  $install = get_install_path();
							$apodcache = "/cache/images/apod/".$this->getImageDir($date).'/';
	       		  $directory = $install.$apodcache;
	        	  make_webserver_dir ($directory);

	        	  if (($uimg['scheme']=='http')&&($uimg['host']=='apod.nasa.gov')) {
	        	    $apodfname= $apodcache.substr($date,8,2).'-'.basename($i);
								$fname = $install.$apodfname;
	        	    if (cache_url_to_file ($i, $fname)) {
	      					echo $i."\n";
	        	      $i = $apodfname;
	        	    } else
	        	      $i=null;
	        	  }
          	} else $i=null;
						if (!is_null($i)) {
							$img = $i;
							break;
						}
					}
	        // push this new item in the database
					// by default, new items are active
	        if ($img!=null)
	          sign_add_feed_entry ($this->feedinfo['id'], $date, $caption, $img, $explanations,true);
	        else
	          echo "Unable to grab picture, skipping";
	      }
      } else
	      echo "Error while loading document into DOM object\n";
    } else
      echo " - file not found\n";
  }	

  /****
   * 
   * Récupère une entrée de l'APOD par date
   */
  private function getApodByDate ($date) {
    $url = $this->urlbase.'/ap'.$date->format('ymd').'.html';
    $this->getApod($url);
  }

	private function getApodList($url) {
		$d = get_url_contents($url);

		$l = array();
		$doc = new DomDocument();
		if (@$doc->loadHTML($d)) {
			$b = $doc->getElementsByTagName('b')->item(0);
			$links = $b->getElementsByTagName('a');
      for ($i=($links->length-1);$i>0;$i--) {
				$link = $links->item($i);
				$href = $link->getAttribute('href');
				array_push($l,$href);
			}
		}
		return $l;
	}

  /****
   *
   * Récupère l'intégralité des entrées de l'APOD depuis un certain fichier
   * si $from est null, commence au début
   */
  private function getApodFromStart ($url,$from=null) {
   	$l = $this->getApodList($url);

		foreach($l as $item) {
			if ($from!=null) {
	  		if ($from==$item)
	    		$from=null;
			} 
			if ($from==null) {
	  		$url = $this->urlbase."/".$item;
	  		$this->getApod($url);
			}
		}
  }

	/****
	 *
	 * Identifie et corrige les bouts d'apod qui manquent
	 *
	 */
	public function fixMissing () {
		$this->getFeedInfo();
		$l = $this->getApodList($this->feedinfo['url']);
		$feed = new Feed($this->feedinfo['id']);
		foreach ($l as $item) {
			$d = DateTime::createFromFormat('Y-m-d H:i:s',$this->getDate($item).' 00:00:00');
			if (!$feed->hasItem($d)) {
    		$url = $this->urlbase.'/'.$item;
				error_log($url);
		    $this->getApod($url);
			}		
		}
	}

  /*****************************************************************************
   *
   * Mise à jour de l'APOD
   *
   */
	public function update () {
		$this->getFeedInfo();
	
    // find the highest date for this stream
    echo "finding if we have stuff in contents\n";
    $res = db_query('select * from feed_contents where id_feed=$1 and date = (select max(date) from feed_contents where id_feed=$1);',
		    array($this->feedinfo['id']));
    //if (false) {
    if (db_num_rows($res) == 1) {
      echo "found one... fetching the next ones\n";
      $row = db_fetch_assoc($res);
			
      $today = new DateTime('now');
      $oneday = new DateInterval('P1D');
      $cd = (new DateTime($row['date']))->add($oneday);
      while ($cd < $today) {
				$cd->add($oneday);
				$this->getApodByDate($cd);
      }
    } else {
      // not found anything	
      // obtain the contents of the apod archive file
      $this->getApodFromStart($this->feedinfo['url']);
    }
# désactive les Apod de plus d'1 an
		echo "deactivate apod entries older than 6 months\n";
		db_query ('update feed_contents set active=false where id_feed=$1 and date < (CURRENT_DATE - interval \'6 months\');',
			array($this->feedinfo['id']));

  }

	private function resizeJPEG ($fn) {
    // précalcul du nom de fichier
    $dir = dirname($fn);
    $fname = basename($fn);
    $ext = pathinfo($fname,PATHINFO_EXTENSION);
   
	 	// identification de la taille de l'image 
	 	$iinfo = getimagesize($fn,$info);
    $width = $iinfo[0];
    $height = $iinfo[1];

    // l'image est elle vraiment trop grande ?
    if ($width>(1920/3)) {
      $nwidth = round(1920/3);
      $nheight = round($nwidth*$height/$width);
			error_log('FeedAPOD::resizeJPEG : '.$fname.' '.$width.' x '.$height.' => '.$nwidth.' x '.$nheight);
      $nfn = $dir.'/'.substr($fname,0,2).'-w'.$nwidth.'.'.$ext;
      $img = imagecreatefromjpeg($fn);
      $imgs = imagecreatetruecolor($nwidth,$nheight);
      if (imagecopyresampled($imgs,$img,0,0,0,0,$nwidth,$nheight,$width,$height)) {
        imagedestroy($img);
        if (imagejpeg($imgs,$nfn)) {
          imagedestroy($imgs);
          @chmod ($nfn, 0664);
  				@chgrp ($nfn, 'www-data');        
          return $nfn;
        } else 
          error_log ('une erreur s\'est produite lors de la sauvegarde de l\'image');
      } else 
        error_log('une erreur s\'est produite lors du redimensionnement');
    } 
		return $fn;
	}

	private function isAnimatedGIF($fn) {
	  if(!($fh = @fopen($fn, 'rb')))
	    return false;
	  $count = 0;
	  //an animated gif contains multiple "frames", with each frame having a 
	  //header made up of:
		// * a static 4-byte sequence (\x00\x21\xF9\x04)
		// * 4 variable bytes
		// * a static 2-byte sequence (\x00\x2C)
		// We read through the file til we reach the end of the file, or we've found 
		// at least 2 frame headers
		while(!feof($fh) && $count < 2) {
		  $chunk = fread($fh, 1024 * 100); //read 100kb at a time
		  $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
		}
		fclose($fh);
		return $count;
	}

	private function resizeGIF ($fn) {
    // précalcul du nom de fichier
    $dir = dirname($fn);
    $fname = basename($fn);
    $ext = pathinfo($fname,PATHINFO_EXTENSION);
	 	
		// identification de la taille de l'image 
		$iinfo = getimagesize ($fn,$info);
    $width = $iinfo[0];
    $height = $iinfo[1];
		
		$frames = $this->isAnimatedGIF($fn);
		if ($frames===false) return $fn;
		// we don't handle animated gifs !
		if ($frames==1) {
			// this is not an animated gif
			if ($width>(1920/3)) {
      	$nwidth = round(1920/3);
      	$nheight = round($nwidth*$height/$width);
				error_log('FeedAPOD::resizeGIF->PNG : '.$fname.' '.$width.' x '.$height.' => '.$nwidth.' x '.$nheight);
				// replaces the gif file with a png
				$nfn = $dir.'/'.substr($fname,0,2).'-w'.$nwidth.'.png';
      	$img = imagecreatefromgif($fn);
      	$imgs = imagecreatetruecolor($nwidth,$nheight);
      	if (imagecopyresampled($imgs,$img,0,0,0,0,$nwidth,$nheight,$width,$height)) {
        	imagedestroy($img);
        	if (imagepng($imgs,$nfn,9,PNG_ALL_FILTERS)) {
          	imagedestroy($imgs);
          	@chmod ($nfn, 0664);
  					@chgrp ($nfn, 'www-data');        
          	return $nfn;
        	} else 
          	error_log ('une erreur s\'est produite lors de la sauvegarde de l\'image');
      	} else 
        	error_log('une erreur s\'est produite lors du redimensionnement');
			}
		}
		return $fn;
	}

	private function resizeTiff ($fn) {
    // précalcul du nom de fichier
    $dir = dirname($fn);
    $fname = basename($fn);
    $ext = pathinfo($fname,PATHINFO_EXTENSION);
	 	
		// we have a tiff picture. transform to png
		error_log('FeedAPOD::resizeTiff->PNG : '.$fname);
		$img = new Imagick($fn);
		$img->writeImage($dir.'/'.$fname.'.png');

		$dim = $img->getImageGeometry();
		$width = $dim['width'];
		$height = $dim['height'];

		// this is not an animated gif
		if ($width>(1920/3)) {
     	$nwidth = round(1920/3);
     	$nheight = round($nwidth*$height/$width);
			error_log('FeedAPOD::resizeTiff->PNG : '.$fname.' '.$width.' x '.$height.' => '.$nwidth.' x '.$nheight);
			// replaces the gif file with a png
			$nfn = $dir.'/'.substr($fname,0,2).'-w'.$nwidth.'.png';
			if ($img->resizeImage($nwidth,$nheight,imagick::FILTER_LANCZOS,1)) {
       	if ($img->writeImage($nfn)) {
         	$img->destroy();
         	@chmod ($nfn, 0664);
  				@chgrp ($nfn, 'www-data');        
         	return $nfn;
       	} else 
         	error_log ('une erreur s\'est produite lors de la sauvegarde de l\'image');
     	} else 
       	error_log('une erreur s\'est produite lors du redimensionnement');
		}
		return $fn;
	}

  private function updateImage ($fn) {
	return $fn;
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$fmime = finfo_file($finfo,$fn);
		finfo_close($finfo);
		
		$nfn=$fn;
		switch ($fmime) {
			case 'image/png':
				break;
			case 'image/jpeg':
				$nfn = $this->resizeJPEG($fn);		
				break;
			case 'image/gif':
				$nfn = $this->resizeGIF($fn);
				break;
			case 'image/tiff':
				$nfn = $this->resizeTiff($fn);
				break;
			default:
				error_log('unknown mime-type : '.$fmime);
		}
    return $nfn;
  }


  public function updatePics () {
	  $basedir = get_install_path().'/cache/images/apod';
    $f = list_all_files($basedir);
    foreach ($f as $fn) {
      if (strpos($fn,'w640')===false) {
        $this->updateImage($fn);
      }
    }
  }

  /*****************************************************************************
   *
   * Generate the next APOD content available
   *
   */
  public function getItem($feedid, $signinfo) {
    if ($signinfo['id']===null) return null;
    $id=$signinfo['id'];
    $date = substr($signinfo['ts'],0,10);
    // TODO: nasa logo ?
    $nasalogofile = '/lib/images/NASA_logo.svg';
    sign_preload_append($nasalogofile);
    $d = sign_base_dir();
    $len = strlen($d);
	
    $nfn=$fn=$signinfo['image'];
/*
    $nfn = $this->updateImage($fn);
		if (strcmp($nfn,$fn)!=0) {
			error_log('FeedAPOD::getNext::updateImage : file was changed to '.$nfn);
			$fn = $nfn;
      if (!sign_update_image_filename($id, $fn))
        error_log('une erreur s\'est produite lors de la mise a jour du nom du fichier image dans la base');
    }
	*/
error_log($fn);
    $picpath = $fn; //substr($fn,$len);

		// generate the array that will be sent with all the info
		// lauches a client side javascript to generate the content 
		$apod = array(
			'date'=>$date,
			'style'=>'/lib/feeds/apod.css',
			'caption'=>$signinfo['caption'],
			'picture'=>$picpath,
			'text'=>(explode("\n",$signinfo['detail'])));
    $resp = array(
			'feedid'=>$feedid,
			'item'=>$signinfo['id'],
			'apod'=>$apod,
			'js'=>'/lib/feeds/apod.js',
			'delay'=>60);
    return $resp;
  }

  public function getNext($screenid, $feedid, $target) {
    $signinfo = sign_feed_get_next ($screenid, $feedid);
		return $this->getItem($feedid, $signinfo);
	}
}

if (getenv('TERM')) {
  echo "command line\n";
  switch ($argc) {
    case 2 : 
      switch ($argv[1]) {
        case '--update-pics' :
          $f = new FeedAPOD();
          $f->updatePics();
          break;
				case '--fix-missing':
					$f = new FeedAPOD();
					$f->fixMissing();
					break;
      }
      break;
    default:
      $class = 'FeedAPOD';
      $f = new $class();
      $f->update();
  }
} else {
  if (!getenv('SIGNLIBLOADER')) {
    echo "<html>\n";
    echo "  <head>\n";
    echo "    <title>Signage - Apod plugin</title>\n";
    echo "  </head>\n";
    echo "  <body>\n";
    echo "    <p>Sorry, you can't call the apod plugin directly from the web</p>\n";
    echo "  </body>\n";
    echo "</html>\n";
  } else 
    putenv('SIGNLIBLOADER');
}
?>
