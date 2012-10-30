<?php

//
// apod.php
// handles the apod feed
//

require_once ('db.php');
require_once ('lib.php');

class feedApod {
	private $urlbase;

	private function getApod($date) {
		echo $this->urlbase.'/ap'.$date->format('ymd').'.html'."\n";
		
	}	

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
		$row = db_fetch_assoc($res);
		$u = parse_url($row['url']);
		$u['path'] = dirname($u['path']);
		$this->urlbase = unparse_url($u);
	
		// find the highest date for this stream
		echo "finding if we have stuff in contents\n";
		$res = db_query('select * from feed_contents where id_feed=$1 and date = (select max(date) from feed_contents where id_feed=$1);',
				array($row['id']));
		if (db_num_rows($res) == 1) {
			echo "found one... fetching the next ones\n";
			$row = db_fetch_assoc($res);
			
			$today = new DateTime('now');
			$cd = new DateTime($row['date']);
			$oneday = new DateInterval('P1D');
			while ($cd <= $today) {
				$cd->add($oneday);
				$this->getApod($cd);
			}
			
		} else {
			// not found anything	
			// obtain the contents of the apod archive file
			$f = fopen($row['url'], 'r');
			$d = '';
			while (!feof($f)) {
				$t = fread($f, 4096);
				$d.=$t;
			}
			fclose($f);
			echo $d;
		}
		
	}
}

if (getenv('TERM')) {
	echo "command line\n";
	$f = new feedApod ();
	$f->update();
} else {
	echo "<pre>web server\n";
	$f = new feedApod ();
	$f->update();
	echo "</pre>";
}

?>
