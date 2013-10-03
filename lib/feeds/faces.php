<?php

//
// faces.php
// handles showing people from the database
//

$d = dirname(__file__);
require_once ($d.'/../signlib.php');

class FeedFaces {
	/*
	* updates faces
	*/
	public function update($json, $pics) {
		// trombi is a system feed
		db_connect();
		$feed_type_name="trombi";
		$res = db_query("select id from feed_types where name=$1;",array($feed_type_name));
		if (is_bool($res)&&($res===false)) {
			error_log("FATAL: unable to obtain type id for feed '".$feed_type_name."'");
			exit(1);
		}
		$row = db_fetch_assoc($res);
		db_free_result($res);
		$feed_type=$row['id'];
		error_log('feed_type : '.$feed_type);
		
		// find the feed id
		$res = db_query("select id from feeds where id_type=$1;", array($feed_type));
		if (is_bool($res)&&($res===false)) {
			error_log("FATAL: Unable to obtain feed id for feed type '".$feed_type."'");
			exit(1);
		}
		$row=db_fetch_assoc($res);
		db_free_result($res);
		$feed_id=$row['id'];
		error_log('feed_id :   '.$feed_id);

		$j = file_get_contents($json);
		$users = json_decode($j, true);
		ksort($users);
		foreach ($users as $login => $user) {
			// find if we have a picture
			$pic = null;
			if (array_key_exists('photo',$user))
				$pic = $user['photo'];
			if (is_null($pic)||(!file_exists($pic))) {
				// no picture... 
				error_log('no picture for user '.$login);
			} else {
				if (is_file($pic)) {
					// remove photo info
					unset($user['photo']);
					$js_user = json_encode($user);
					// find if we already have this user
					$res = db_query("select * from feed_contents where id_feed=$1 and title=$2",
						array($feed_id, $login));
					if (is_bool($res)&&($res===false)) {
						error_log("error while looking for user '".$login."'");
					}
					$n = db_num_rows($res);
					error_log("found ".$n." rows for user '".$login."'");
					if ($n==0) {
						db_free_result($res);
						// add user
						// part one: search for proper date
						$in = $user['indate'];
						if (is_null($in))
							$in = '2000-01-01';
						$res=db_query("select max(date) as prev from feed_contents where id_feed=$1 and date_trunc('day',date)=$2",
							array($feed_id, $in));
						if (is_bool($res)&&($res===false)) {
							error_log("error while searching for an available date");
						}
						$row = db_fetch_assoc($res);
						db_free_result($res);
						$d = $row['prev'];
						if (is_null($d)) {
							$d = $in." 00:00:00";
						} else {
							$format = "Y-m-d H:i:s";
							$d = date_create_from_format($format, $d);
							// increment date by 1 second
							$d = date_add($d, new DateInterval('PT1S'));
							$d = $d->format($format);
						}
						// handle picture storage
						$i = new ImageManager();
						$img = $i->fetch($pic, 'faces');
						// add user to db
						sign_add_feed_entry ($feed_id, $d, $login, $img, $js_user, $active=false);
					} else {
						// modify user
						// remove 'photo' value
					}
					// contents of the data
					// id      auto
					// id_feed $feed_id
					// date    ?
					// title   $login
					// image   url picture
					// detail  json_encode($user)
				}
			}
		}
	}

  public function getItem($feedid, $signinfo) {
    if ($signinfo['id']===null) return null;

    $html = '<div id="manuel">'.
      '<style text="text/css" scoped>'.
			'#manuel{color:white;}'.
			'#manuel>#date{font-size:30%;}'.
			'#manuel>#caption{font-size:70%;}'.
			'#manuel>#text{font-size:40%;}'.
      '</style>'.
      '<div id="date">'.$signinfo['ts'].'</div>'.
			'<div id="caption">'.$signinfo['caption'].'</div>'.
			'<div id="text">'.$signinfo['detail'].'</div>'.
      '</div>';
    $resp = array('html'=>$html,'delay'=>60);
    return $resp;
  }

  public function getNext($screenid, $feedid, $target) {
    $signinfo = sign_feed_get_next ($screenid, $feedid);
		return $this->getItem($feedid, $signinfo);
	}

}

function usage() {
	$prog = $_SERVER['argv'][0];
	echo $prog.": integrates people's faces database\n";
	echo "Usage: ".$prog." [JSON File] [pictures directory]\n";
}

if (array_key_exists('TERM',$_SERVER)) {
	if (array_key_exists('argv',$_SERVER)) {
		if ($_SERVER['argc']==3) {
			$argv=$_SERVER['argv'];
			$json_file=$argv[1];
			$pics_dir=$argv[2];
	
			// checks the json file
			if (!file_exists($json_file)) {
				error_log("FATAL: Unable to find json file '".$json_file."'");
				exit(1);
			}
			if (!is_file($json_file)) {
				error_log("FATAL: json file is not a file");
				exit(1);
			};

			// checks the pics dir
			if (!file_exists($pics_dir)) {
				error_log("FATAL: Unable to find pictures directory '".$pics_dir."'");
				exit(1);
			}
			if (!is_dir($pics_dir)) {
				error_log("FATAL: pictures dir is not a dir");
				exit(1);
			}
			
			$f = new FeedFaces();
			$f->update($json_file, $pics_dir);

		} else {
			error_log("FATAL: unacceptable number of arguments (2 required)");
			usage();
			exit (1);
		}
	} else {
		// fatal error, no argv variable...
		// shouldn't happen
		error_log("FATAL: unable to find argv variable in environment");
		exit (255);
	}
}
?>
