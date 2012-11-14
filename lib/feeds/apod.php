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
      return null;
    }
    // stupid object for microsoft video feed ?
    $object = $doc->getElementsByTagName('object');
    if ($object->length>0) {
      return null;
    }
    // gah, worse, could be flash ;-)
    $embed = $doc->getElementsByTagName('embed');
    if ($embed->length>0) {
      return null;
    }
    // could it be some crazy javascript ?
    $script = $doc->getElementsByTagName('script');
    if ($script->length>0) {
      return null;
    }
    // get image url
    $links = $doc->getElementsByTagName('a');
    /* finds which one contains an img tag */
    $img = null;
    $valid = [];
    for($i=0;$i<($links->length);$i++) {
      $item = $links->item($i);
      $t = $item->getElementsByTagName('img');
      if ($t->length==1){
	/* look for the one with an 'alt' */
	$image = $t->item(0);
	if ($image->attributes->getNamedItem('alt')!=null) 
	  array_push($valid, $item->getAttribute('href'));
      }
    }
    if (count($valid)>0) {
      /* find the local one */
      foreach ($valid as $url) {
	$u = parse_url($url);
	if (array_key_exists('scheme',$u))
	  continue;
	if (array_key_exists('host',$u))
	  continue;
	$img = $u['path'];
      }
    }
    if ($img==null) {
      $images = $doc->getElementsByTagName('img');
      if ($images->length==1)
	$img = $images->item(0)->getAttribute('src');
    }
    if ($img != null) 
      return $this->urlbase.'/'.$img;
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

  /*****
   * 
   * Récupère une entrée du contenu APOD
   */
  private function getApod($url) {
    echo "\n===================================================\n".$url;
    $f = fopen($url, 'r');
    if ($f) {
      echo "\n";
      // grab file contents
      $d = '';
      while (!feof($f))
	      $d.=fread($f,4096);
      fclose($f);
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
	      echo $img."\n";
	
	      if ($img!=null) {
	        //get date from url
	        $uhtml = parse_url($url);
	        $pathhtml = $uhtml['path'];
	        $filehtml = basename($pathhtml);
          $uimg = parse_url($img);
          $pathimg = $uimg['path'];
          $fileimg = basename($pathimg);
          // ckeck extension
          $extension = pathinfo($pathimg,PATHINFO_EXTENSION);
          // TODO: look for a ? in the url...
          error_log('imagefile is '.$pathimg.' file extension is '.$extension);
          $idx = array_search($extension,array('jpg','gif','png'));
          error_log('idx='.print_r($idx,1));
          if ($idx!==false) {
	          $install = get_install_path();
	          $y = substr($filehtml,2,2);
	          $m = substr($filehtml,4,2);
	          $d = substr($filehtml,6,2);
	          if ($y[0]=='9') 
	            $y = '19'.$y;
	          else
	            $y = '20'.$y;
	          $date = $y.'-'.$m.'-'.$d;
	          $apodcache = $install."/cache/images/apod/".$y.'/'.$m.'/';
	          make_webserver_dir ($apodcache);

	          if (($uimg['scheme']=='http')&&($uimg['host']=='apod.nasa.gov')) {
	            $fname = $apodcache.$d.'--'.basename($img);
	            if (cache_url_to_file ($img, $fname))
	              $img = $fname;
	            else
	              $img=null;
	          }
          } else $img=null;
	        // push this new item in the database
	        if ($img!=null)
	          sign_add_feed_entry ($this->feedinfo['id'], $date, $caption, $img, $explanations);
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

  /****
   *
   * Récupère l'intégralité des entrées de l'APOD depuis un certain fichier
   * si $from est null, commence au début
   */
  private function getApodFromStart ($url,$from=null) {
    $f = fopen($url, 'r');
    $d = '';
    while (!feof($f))
      $d.=fread($f, 4096);
    fclose($f);
    
    $doc = new DomDocument();
    if (@$doc->loadHTML($d)) {
      $b = $doc->getElementsByTagName('b')->item(0);
      $links = $b->getElementsByTagName('a');
      for ($i=($links->length-1);$i>0;$i--) {
	$link = $links->item($i);
	$href = $link->getAttribute('href');
	if ($from!=null) {
	  if ($from==$href)
	    $from=null;
	} 
	if ($from==null) {
	  $url = $this->urlbase."/".$href;
	  $this->getApod($url);
	}
      }
    }
  }

  /*****************************************************************************
   *
   * Mise à jour de l'APOD
   *
   */

  public function update () {
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
    $u = parse_url($this->feedinfo['url']);
    $u['path'] = dirname($u['path']);
    $this->urlbase = unparse_url($u);
	
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

  private function updateImage ($fn) {
		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$fmime = finfo_file($finfo,$fn);
		finfo_close($finfo);
		
		$nfn=$fn;
		switch ($fmime) {
			case 'image/jpeg':
				$nfn = $this->resizeJPEG($fn);		
				break;
			case 'image/gif':
				$nfn = $this->resizeGIF($fn);
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
  public function getNext($screenid, $feedid, $target) {
    //error_log ('FeedAPOD.getNext ($screenid='.$screenid.', $feedid='.$feedid.') invoked');
    $signinfo = sign_feed_get_next ($screenid, $feedid);
    if ($signinfo['id']===null) return null;
    $id=$signinfo['id'];
    $date = substr($signinfo['ts'],0,10);
    // TODO: nasa logo ?
    $nasalogofile = '/lib/images/NASA_logo.svg';
    sign_preload_append($nasalogofile);
    $d = sign_base_dir();
    $len = strlen($d);
	
		$nfn=$fn=$signinfo['image'];
    $nfn = $this->updateImage($fn);
		if (strcmp($nfn,$fn)!=0) {
			error_log('FeedAPOD::getNext::updateImage : file was changed to '.$nfn);
			$fn = $nfn;
      if (!sign_update_image_filename($id, $fn))
        error_log('une erreur s\'est produite lors de la mise a jour du nom du fichier image dans la base');
    }
    $picpath = substr($fn,$len);

		// generate the array that will be sent with all the info
		// lauches a client side javascript to generate the content 
		$apod = array(
			'date'=>$date,
			'style'=>'/lib/feeds/apod.css',
			'caption'=>$signinfo['caption'],
			'picture'=>$picpath,
			'text'=>(explode("\n",$signinfo['detail'])));
    $resp = array(
			'apod'=>$apod,
			'js'=>'/lib/feeds/apod.js',
			'delay'=>60);
    return $resp;
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
