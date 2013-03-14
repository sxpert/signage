<?php

//
// manual.php
// handles a manual feed
//

$d = dirname(__file__);
require_once ($d.'/../signlib.php');

class FeedManual {
  /*****************************************************************************
   *
   * Generate the next APOD content available
   *
   */
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

?>
