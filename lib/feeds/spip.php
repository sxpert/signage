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

	private function _spipToHtml_Special (&$state) {
		error_log(__function__);
		$c = $state->string[$state->index++];
		switch ($c) {
			case '_':
				$t='';
				$stop=false;
				$underline = 0;
				while (!$stop) {
					$c = $state->string[$state->index++];
					if ($c=='_') {
						$underline++;
						if ($underline==2) 
							$stop=true;
					} else {
						while ($underline) {
							$t.='_';
							$underline--;
						}
						$t.=$c;
					}
					if ($state->index>strlen($state->string))
						$stop = true;
				}
				// go to next line
				if (strcmp($t,'LN')==0)
					return '<br/>';
				// we could have DOCxxx stuff here
				return '';
			case ' ':
				// skip over '_ '
				return '';
			default:
				return '_'.$c;
		}
	}

	private function _spipToHtml_Note (&$state) {
		error_log(__function__);
		$t='';
		$stop = false;
		$closing_bracket=0;
		while (!$stop) {
			$c = $state->string[$state->index++];
			if ($c==']') {
				$closing_bracket++;
				if ($closing_bracket==2)
					$stop=true;
			} else {
				while ($closing_bracket) {
					$t.=']';
					$closing_bracket--;
				}
				$t.=$c;
			}
			if ($state->index>strlen($state->string))
				$stop = true;
		}
		// ignore notes;
		error_log ('ignoring note \''.$t.'\'');
		return '';
	}

	private function _spipToHtml_Link (&$state) {
		error_log(__function__);
		$t='';
		$stop = false;
		$minus = false;
		$open_angle = false;
		$skip_to_closed_bracket=false;
		while (!$stop) {
			$c = $state->string[$state->index++];
			if (strlen($t)==0) {
				switch($c) {
					case '?':
						// wikipedia link -> skip
						break;
					case '[':
						// note
						return $this->_spipToHtml_Note ($state);
						break;
					default:
						$t.=$c;
				}
			} else {
				if ($skip_to_closed_bracket) {
					if ($c==']')
						$stop=true;
				} else {
					if ($open_angle) {
						if ($c=='-') $skip_to_closed_bracket=true;
						else {
							$t.=$c;
							$open_angle=false;
						}
					} elseif ($minus) {
						if ($c=='>') $skip_to_closed_bracket=true;
						else {
							$t.=$c;
							$minus=false;
						}
					} else {
						switch ($c) {
							case '-':
								$minus=true;
								break;
							case '<':
								$open_angle=true;
								break;
							default:
								$t.=$c;
						}
					}
				}
			}
			if ($state->index>strlen($state->string))
				$stop = true;
		}
		// we don't really do links
		return $t;
	}

	private function _spipToHtml_Caption (&$state) {
		error_log(__function__);
		$t = '';
		$stop = false;
		$closing_curl = 0;
		while (!$stop) {
			$c = $state->string[$state->index++];
			if ($c=='}') {
				$closing_curl++;
				if ($closing_curl==3) 
					$stop=true;
			} else {
				while ($closing_curl) {
					$closing_curl--;
					$t.='}';
				}
				switch ($c) {
					case '{':
						$t.=$this->_spipToHtml_Italic($state);
		 				break;
					default: 
						$t.=$c;
				}
			}
			if ($state->index>strlen($state->string))
				$stop = true;
		}
		return '<div>'.$t.'</div>';
	}

	private function _spipToHtml_Bold (&$state) {
		error_log(__function__);
		$t = '';
		$stop = false;
		$closing_curl = 0;
		while (!$stop) {
			$c = $state->string[$state->index++];
			if ($c=='}') {
				$closing_curl++;
				if ($closing_curl==2)
					$stop=true;
			} else {
				while ($closing_curl) {
					$closing_curl--;
					$t.='}';
				}
				switch ($c) {
					case '{': 
						if (strlen($t)==0) {
							return $this->_spipToHtml_Caption($state);
						} 
						$t.=$this->_spipToHtml_Italic($state);
						break;
					case '[':
						$t.=$this->_spipToHtml_Link($state);
						break;
					default :
						$t.=$c;
				}
			}
			if ($state->index>strlen($state->string))
				$stop = true;
		}	
		return '<b>'.$t.'</b>';
	}

	private function _spipToHtml_Italic (&$state) {
		error_log(__function__);
		$t = '';
		$stop = false;
		while (!$stop) {
			$c = $state->string[$state->index++];
			switch ($c) {
				case '{':
					if (strlen($t)==0) {
						return $this->_spipToHtml_Bold ($state);
					} else {
						$t.=$this->_spipToHtml_Italic ($state);
					}
					break;
				case '}':
					$stop=true;
					break;
				default :
					$t.=$c;
			}
			if ($state->index>strlen($state->string))
				$stop = true;
		}
		return '<i>'.$t.'</i>';
	}

	private function _spipToHtml (&$state) {
		error_log(__function__);
		$t = '';
		while ($state->index<strlen($state->string)) {
			$c = $state->string[$state->index++];
			switch ($c) {
				case '{':
					$t.=$this->_spipToHtml_Italic ($state);
					break;
				case '[':
					$t.=$this->_spipToHtml_Link ($state);
					break;
				case '_':
					$t.=$this->_spipToHtml_Special ($state);
					break;
				default : 
					$t.=$c;
			}
		}
		return $t;
	}

	public function spipToHtml ($text) {
		error_log(__function__);
		$debug = false;
		if (strpos($text,'__LN__')!==false)
			$debug = true;
		$debug=true;
		$state = (object) array('string' => $text, 'index' => 0);
		return $this->_spipToHtml ($state);
	}

	public function update () {
    global $HTTP_OPTS;
		if (!$this->feedid) {
			db_connect();
			$this->_update(2, true);
			//error_log ("SPIP: ERROR: unknown feed");
			return;
		}
		$this->_update($this->feedid);
	}

	private function _update ($feedid, $force=false) {
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
				if (!$force)
					return;
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
				
				if (getenv('TERM')) {
					error_log("date >>".print_r($date,1));
					error_log("title >>".print_r($title,1));
					error_log("descriptif".print_r($desc,1));
				}
				
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
			'</style>'.
			'<script type="text/javascript" src="/lib/feeds/spip.js"></script>'.
			'<div id="date">'.$signinfo['ts'].'</div>'.
			'<div id="caption">'.$signinfo['caption'].'</div>'.
			'<div id="text">'.$signinfo['detail'].'</div>'.
			'</div>';

		$spip = array(
			'style'=>'/lib/feeds/spip.css',
			'date'=>$signinfo['ts'],
			'caption'=>$signinfo['caption'],
			'text'=>$signinfo['detail'],
			);
	  $resp = array(
      'feedid'=>$feedid,
		  'item'=>$signinfo['id'],
		  'spip'=>$spip,
			'js'=>'/lib/feeds/spip.js',
			'delay'=>60);
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
 	$f->update(2);

/*
 * spip parser tests
 */
/*
	error_log('----------');
	error_log($f->spipToHtml ('{{Une équipe de chercheurs franco-québecoise, dont des chercheurs de l\'[IPAG->site6], a réalisé la première'.
		' image d\'une probable exoplanète située autour d\'une étoile double à une distance compatible avec une formation de type planétaire.'.
		' La découverte de ce compagnon de 12 à 14 fois la masse de Jupiter et dont le couple d\'étoiles-hôtes est faiblement massif, est peu'.
		' compatible avec les modèles traditionnels de formation stellaire et planétaire et vient donc soutenir la théorie alternative de'.
		' formation planétaire par instabilité dans le disque [[Pour en savoir plus sur ce modèle de formation planétaire par instabilité'.
		' gravitationnelle dans le disque : http://www.docstoc.com/docs/101264266/Can-Gas-Giant-Planets-Form-by-Gravitational-Instabilities'.
		'-in-Disks & http://www.psc.edu/science/quinn.html]]. Ces résultats sont publiés ce jour en couverture de la revue Astronomy'.
		' & Astrophysics.}}'));
	error_log('----------');
	error_log($f->spipToHtml ('2MASS0103AB(b) a été détecté dans l\'infrarouge à l\'aide de l\'instrument NACO installé sur le Very'.
		' Large Telescope au Chili. Les mesures de spectrométrie et d\'astrométrie révèlent un système binaire âgé de 30 millions d\'années,'.
		' dont le compagnon a une masse entre 12 et 14 fois celle de Jupiter et orbite à 84 UA (environ 12,5 milliards de kilomètres), et dont'.
		' les étoiles hôtes sont faiblement massives (respectivement 0,19 et 0,17 fois la masse du Soleil) soit à elles deux un tiers de la'.
		' masse solaire. Le compagnon a une masse qui équivaut à 3.6 % de la masse de ses étoiles-hôtes, ce qui est important pour une'.
		' exoplanète (Jupiter fait 0.1 % de la masse du Soleil), mais qui est bien moindre de ce qu\'on attendrait si ce compagnon s\'était'.
		' formé comme la troisième composante d\'un système stellaire triple. Par ces caractéristiques (couleurs, séparation projetée'.
		' inférieure à 100UA, rapport de masse), on ne connaît pas d\'analogue à 2M0103AB(b). __LN__ __LN__La masse estimée de l\'objet,'.
		' entre 12 et 14 fois celle de Jupiter, se situe dans la fourchette à la frontière entre la définition d\'une planète (en dessous'.
		' de 13 masse de Jupiter) et celle d\'une naine brune (au dessus). Indépendamment de sa masse exacte, il est toutefois plus probable'.
		' que l\'objet se soit formé comme une planète, dans un disque autour de ses étoiles-hôtes, que comme une naine brune selon un'.
		' processus de formation stellaire. __LN__ __LN____DOC195|center__ __LN__ __LN__En effet, ces caractéristiques physiques atypiques'.
		' soulèvent la question du scénario de formation d\'un tel système. Pour les auteurs, un scénario de formation planétaire par'.
		' accrétion autour d\'un noyau solide, modèle majoritairement convoqué pour expliquer la formation des planètes du Système Solaire,'.
		' est très probablement exclu dans ce cas. En effet, la séparation est trop grande pour une formation in situ et le rapport de masse'.
		' frôle le maximum d\'un disque protoplanétaire. A l\'opposé, ce compagnon est trop peu massif pour être compatible avec les modèles'.
		' de formation stellaire, qui ont nature à former des objets massifs comme le Soleil, mais peinent à former des objets d\'une dizaine'.
		' de fois la masse de Jupiter, et a fortiori de tels membres de systèmes multiples si rapprochés. Quelques objets de masse planétaire'.
		' en orbite autour d\'étoiles binaires sont connus, mais leur séparation bien supérieure à 100 UA autorise l\'hypothèse d\'une'.
		' formation stellaire, notamment par capture. Une théorie de formation planétaire relativement récente, et encore controversée, celle'.
		' de la formation par instabilité gravitationnelle dans un disque circumstellaire, expliquerait toutefois plus naturellement les'.
		' propriétés de 2MASS0103AB(b) et de ses étoiles-hôtes. Cette découverte fournit l\'un des plus forts indices observationnels pour'.
		' soutenir cette théorie alternative de formation de planètes géantes, ouvrant de nouvelles perspectives sur notre compréhension des'.
		' mécanismes de la formation planétaire. __LN__ __LN__{{Contact scientifique local}} : __LN___ Philippe Delorme, IPAG :'.
		' {philippe.delorme (at) obs.ujf-grenoble.fr}, 04 76 51 49 42 / 06 18 96 81 52. __LN__ __LN__ __LN__{{Cette actualité est également'.
		' relayée par}} __LN__- l\'Institut National des Sciences de l\'Univers du CNRS - [INSU ->http://www.insu.cnrs.fr/node/4366] __LN__'.
		' __LN__{{Source}} : __LN___ {Direct imaging discovery of 12-14 Jupiter mass object orbiting a young binary system of very low-mass'.
		' stars}, P. Delorme et al., Astronomy&Astrophysics Letters, 03/2013. __LN___ Lire l\'[article->http://arxiv.org/pdf/1303.4525v1.pdf].'.
		' __LN__ __LN__'));
*/
}

?>
