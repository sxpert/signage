<?php

//
// spip.php
// handles the feed from a spip server
//

$d = dirname(dirname(__file__));
require_once ($d.'/signlib.php');
require_once ($d.'/lib.php');

class FeedSpip {
	private $feedid;
	private $feedinfo;

	private function spipToHtml ($text) {
		$debug = false;
		if (strpos($text,'__LN__')!==false)
			$debug = true;
		$debug=false;
		/*
		states :
		 0 : nothing special
		 1 : had a space already
		10 : one '{'
		11 : second '{'
		20 : first '_'
		21 : second '_'
		100 : got only 1 '{'
		110 : got only 2 '{'
		120 : got the 3rd '{'
		*/
		$state = 0;
		// char counter
		$i = 0;
		// char being studied
		$c = '';
		// length of string
		$l = strlen($text);
		// html doc
		$h = '';
		// temp string
		$t = '';
		while ($i<$l) {
			$c = $text[$i];
			if ($debug)
				error_log ($c.' '.$state);
			switch ($state) {
				case 0:
					switch ($c) {
						case '{':
							$state = 10;
							break;
						case '_':
							$state = 20;
							break;
						case ' ':
							$h.=$c;
							$state = 1;
							break;
						default:
							$h.=$c;
					}
					break;
				// got one space already
				case 1:
					switch ($c) {
						case '{':
							$state = 10;
							break;
						case '_':
							$state = 20;
							break;
						case ' ':
							break;
						default:
							$h.=$c;
							$state = 0;
					}
					break;
				// had one '{' so far...
				case 10:
					switch ($c) {
						case '{':
							$state=11;
							break;
						default:
							// italic text
							$t = $c;
							$state = 100;
					}
					break;
				// got a second '{'...
				case 11:
					switch ($c) {
						case '{':
							$state=120;
							break;
						default :
							// bold text
							$t = $c;
							$state = 110;
					}
					break;
				case 20:
					switch ($c) {
						case '_':
							$state = 21;
							break;
						default:
							$h.='_'.$c;
							$state = 0;
					}
					break;	
				case 21:
					switch ($c) {
						case 'L':
							$state = 200;
							break;
					}
					break;
				// only one '{' => italic text
				case 100:
					switch ($c) {
						case '}':
							$h.= '<i>'.$t.'</i>';
							$t = '';
							$state = 0;
							break;
						default:
							$t.=$c;
					}
					break;
				// bold text
				case 110:
					switch ($c) {
						case '}':
							$state = 111;
							break;
						default:
							$t.=$c;
					}
					break;
				// end bold text ?
				case 111:
					switch ($c) {
						case '}':
							// really the end
							$h.='<b>'.$t.'</b>';
							$t='';
							$state = 0;
							break;
						default:
							// not finished yet...
							$t.='}'.$c;
							$state=110;
					}
					break;
				// start caption
				case 120:
					switch ($c) {
						case '}':
							// mebbe end the caption ?
							$state = 121;
							break;
						default:
							$t.=$c;
					}
					break;
				case 121:
					switch ($c) {
						case '}':
							// second closing '}'
							$state = 122;
							break;
						default:
							// wasn't closing
							$t.='}'.$c;
							$state = 120;
					}
					break;
				case 122:
					switch ($c) {
						case '}':
							// closing for real
							$h.='<div class="spip-caption">'.$t.'</div>';
							$t = '';
							$state = 0;
							break;
						default:
							$t = '}}'.$c;
							$state = 120;
					}
					break;
				case 200:
					switch ($c) {
						case 'N':
							$state = 201;
							break;
					}
					break;
				case 201:
					switch ($c) {
						case '_':
							$state = 202;
							break;
					}
					break;
				case 202: 
					switch ($c) {
						case '_':
							$h.='<br/>';
							$state = 0;
							break;
					}
					break;
			}
			$i++;
		}
		return $h;
	}

	public function update () {
    global $HTTP_OPTS;
		if (!$this->feedid) {
			error_log ("SPIP: ERROR: unknown feed");
			return;
		}
		$this->_update($this->feedid);
	}

	private function _update ($feedid) {
		error_log('SPIP: updating '.$feedid);
		$sql = "select url, last_update from feeds where id=$1 for update";
		$res = db_query ($sql, array($feedid));
		if (db_num_rows($res)!=1) {
			error_log ("SPIP: error locking row for feed ".$feedid);
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
			$updateperiod = 3600;
			if ($interval<$updateperiod) {
				//return;
			}
		}
		$lastupdate = $curdate;

		# récupérer le contenu du backend spip2spip 
		$d = get_url_contents($url);
		# chercher les item
		$dom = new DomDocument();
		if (@$dom->loadXML($d)) {
			$items = $dom->getElementsByTagName('item');
			foreach ($items as $item) {
				# récupération du titre 
				$title = $item->getElementsByTagName('titre');
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
				$desc = $item->getElementsByTagName('descriptif');
				if ($desc->length==0) 
					$desc = $item->getElementsByTagName('chapo');
				if ($desc!=null) {
					if (count($desc)==1) {
						$desc = $desc->item(0);
						$desc = dom_get_text($desc);
						$desc = $this->spipToHtml($desc);
					} else
						$desc = null;
				}
			
				# on pourrait ici récupérer une image, le cas échéant
				/*	
				error_log("date >>".print_r($date,1));
				error_log("title >>".print_r($title,1));
				error_log("descriptif".print_r($desc,1));
				*/
				if (($title!=null)&&($date!=null)) {
					if ($desc==null) {
						error_log('SPIP: warning, desc is null for item '.$date->format('c'));
						$desc = '';
					}
					# sauvegarde de l'item dans les iformations de flux.
					# note : utilisation de la date comme clé

					# cherche si on a déja une entrée
					$sql = 'select id from feed_contents where id_feed=$1 and date=$2';
					$res = db_query ($sql, array($feedid, $date->format('c')));
					if (db_num_rows($res)!=0) {
						# mise à jour ?
					} else {
						# force l'entrée comme "active" 
						sign_add_feed_entry($feedid, $date->format('c'), $title, null, $desc, True);	
					}
				}

			}
			# fin du chargement du fichier...
			# mise a jour de l'update time
			$sql = 'update feeds set last_update=$1 where id=$2';
			db_query($sql, array($lastupdate->format('c'), $feedid));

		} else 
			error_log ('SPIP: Unable to load XML doc from '.$url);	
	}

	public function getItem ($feedid, $signinfo) {
		if ($signinfo['id']===null) return null;

		$html = '<div id="rss">'.
			'<style text="text/css" scoped>'.
			'#rss{color:white;}'.
			'#rss>#date{font-size:30%;}'.
			'#rss>#caption{font-size:60%;margin-top:.5em;margin-bottom:.5em}'.
			'#rss>#text{font-size:40%;}'.
			'#rss>#text>.spip-caption{font-size:125%;margin-top:0.25em;margin-bottom:0.25em}'.
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
	$f = new FeedSpip();
 	$f->update();
}

?>
