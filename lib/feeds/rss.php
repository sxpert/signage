<?php

//
// rss.php
// handles an rss feed
//

$d = dirname(__file__);
require_once ($d.'/../signlib.php');
require_once ($d.'/../lib.php');

class FeedRss {
	private $feedid;
	private $feedinfo;

	public function update () {
    global $HTTP_OPTS;
		if (!$this->feedid) {
			error_log ("ERROR: unknown feed");
			return;
		}

		# trouver l'url dans la database
		#	pour l'instant c'est en dur
		
		$sql = "select url, last_update from feeds where id=$1 for update";
		$res = db_query ($sql, array($this->feedid));
		if (db_num_rows($res)!=1) {
			error_log ("error locking row for feed ".$this->feedid);
			return;
		}
		$row = db_fetch_assoc($res);
		$url = $row['url'];
		$lastupdate = $row['last_update'];

		$curdate = new DateTime('now');
		if (!is_null($lastupdate)) {
			$lastupdate = new DateTime ($lastupdate);
			$interval = $curdate->getTimestamp() - $lastupdate->getTimestamp();
			# updateperiod devrait etre une valeur de config
			$updateperiod = 86400;
			if ($interval<86400) {
				return;
			}
		}
		$lastupdate = $curdate;

		$url = 'http://ipag.osug.fr/spip.php?page=backend';
		# ouvrir le fichier rss
		if (array_key_exists('proxy', $HTTP_OPTS)) {
			$context = stream_context_create (array('http'=>array('proxy'=>$HTTP_OPTS['proxy'])));
			$f = fopen($url, 'r', false, $context);
		} else
    	$f = fopen($url, 'r');
		$d = '';
		while (!feof($f))
			$d.= fread($f,4096);
		fclose ($f);
		# chercher les item
		$dom = new DomDocument();
		if (@$dom->loadXML($d)) {
			$items = $dom->getElementsByTagName('item');
			foreach ($items as $item) {
				# récupération du titre 
				$title = $item->getElementsByTagName('title');
				if (count($title)==1) {
					$title = $title->item(0);
					$title = dom_get_text($title);
				} else
					$title = null;

				# récupération de la date
				$date = $item->getElementsByTagName('date');
				if (count($date)==1) {
					$date = $date->item(0);
					$date = dom_get_text($date);
					$date = new DateTime($date);
				} else
					$date = null;

				# récupération de la description de l'item
				$desc = $item->getElementsByTagName('description');
				if ($desc!=null) {
					if (count($desc)==1) {
						$desc = $desc->item(0);
						$desc = dom_get_text($desc);
					} else
						$desc = null;
				}
			
				# on pourrait ici récupérer une image, le cas échéant

				if (($title!=null)&&($date!=null)&&($desc!=null)) {
					# sauvegarde de l'item dans les iformations de flux.
					# note : utilisation de la date comme clé

					# cherche si on a déja une entrée
					$sql = 'select id from feed_contents where id_feed=$1 and date=$2';
					$res = db_query ($sql, array($this->feedid, $date->format('c')));
					if (db_num_rows($res)!=0) {
					} else {
						# force l'entrée comme "active" 
						sign_add_feed_entry($this->feedid, $date->format('c'), $title, null, $desc, True);	
					}
				}

			}
			# fin du chargement du fichier...
			# mise a jour de l'update time
			$sql = 'update feeds set last_update=$1 where id=$2';
			db_query($sql, array($lastupdate->format('c'), $this->feedid));

		} else 
			error_log ('Unable to load XML doc from '.$url);	
	}

	public function getItem ($feedid, $signinfo) {
		if ($signinfo['id']===null) return null;

		$html = '<div id="rss">'.
			'<style text="text/css" scoped>'.
			'#rss{color:white;}'.
			'#rss>#date{font-size:30%;}'.
			'#rss>#caption{font-size:70%;}'.
			'#rss>#text{font-size:40%;}'.
			'</style>'.
			'<div id="date">'.$signinfo['ts'].'</div>'.
			'<div id="caption">'.$signinfo['caption'].'</div>'.
			'<div id="text">'.$signinfo['detail'].'</div>'.
			'</div>';

	  $resp = array('html'=>$html,'delay'=>60);
	  return $resp;

	}

	public function getNext ($screenid, $feedid, $target) {
		$this->feedid = $feedid;
		$this->update();
		$signinfo = sign_feed_get_next ($screenid, $feedid);
		return $this->getItem($feedid, $signinfo);
	}

}

if (getenv('TERM')) {
	echo "command line\n";
	$f = new FeedRSS();
 	$f->update();
} else {
	error_log("web call");
}

?>
