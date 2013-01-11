// données des zones
var bgdata = null;
// arguments de la page
var args = null;
// est on en train de simuler un autre écran
var simulate = false;

var FeedList = function () {
	this.feeds = null;
}

FeedList.prototype._getOldFeed = function (zoneid, id) {
	if (this.feeds===null) return null;
	if (this.feeds.hasOwnProperty(zoneid)) {
		var z = this.feeds[zoneid];
		if (z.hasOwnProperty('feeds')) {
			var feeds = z.feeds;
			for(var i=0;i<feeds.length;i++) {
				if (feeds[i].id==id) {
					return feeds[i];
				}
			}
		}
	}
	return null;
}

// TODO: handle zone
FeedList.prototype._update = function (data) {

	var newfeeds = {};
	var ids = Object.keys(data);
	for (var i=0; i<ids.length; i++) {
		var f = data[ids[i]];
		
		var oldfeed = this._getOldFeed(f.target, f.id);

		var feed = {};
		
		feed['id'] = f.id;
		feed['first'] = f.first;
		feed['target'] = f.target;
		if (oldfeed===null) {
			feed['current'] = null;
		} else {
			feed['current'] = oldfeed.current;
		}
		
		var z = null;
		var feeds = null;

		if (newfeeds.hasOwnProperty(f.target)) {
			z = newfeeds[f.target];
			if (z.hasOwnProperty('feeds')) {
				feeds = z['feeds'];
			} else {
				feeds = new Array();
			}
		} else {
			z = {};
			var oz = null;
			if ((this.feeds!==null)&&(this.feeds.hasOwnProperty(f.target))) {
				oz = this.feeds[f.target];
			}
			if ((oz!==null)&&(oz.hasOwnProperty('current'))) {
				z['current'] = oz['current'];
			} else {
				z['current'] = null;
			}
			feeds = new Array();
		}
		feeds.push(feed);
		z['feeds'] = feeds;
		newfeeds[f.target] = z;
	}
	this.feeds = newfeeds;
}

FeedList.prototype.update = function (zone, callback) {
	var f = this;
	// récupere les flux
	$.ajax({
		url: 'screen-get-feeds.php?screenid='+args['screenid'],
		type: 'GET',
		cache: false,
		datatype: 'json',
		success: function(data, textstatus, jqXHR) {
			f._update(data);
			// now genereate url
			f._next(zone, callback);
		}
	});
}

FeedList.prototype._makeUrl = function (feed) {
	var u = 'screen-zone-simul.php?';
	u+='screenid='+args.screenid;
	u+='&zone='+feed.target;
	u+='&feedid='+feed.id;
	u+='&itemid='+feed.current;
	return u;
}

FeedList.prototype._makeNextUrl = function (feed) {
	var u = 'screen-zone-simul-next.php?';
	u+='screenid='+args.screenid;
	u+='&feedid='+feed.id;
	u+='&itemid='+feed.current;
	console.log(u);
	return u;
}

FeedList.prototype._next = function (zone, callback) {
	if (this.feeds.hasOwnProperty(zone.id)) {
		var z = this.feeds[zone.id];
		var current = null;
		if (z.hasOwnProperty('current')) {
			current = z.current;
		}
		var feeds = null;
		if (z.hasOwnProperty('feeds')) {
			feeds = z.feeds;
		}
		if (current===null) {
			current = 0;
		} else {
			if (feeds!==null) {
				current++;
				if (current>=feeds.length) {
					current = 0;
				}
			} else {
				current = null;
			}
		}
		z.current = current;
		var feed = z.feeds[current];
		if (feed.current===null) {
			feed.current = feed.first;
			var u = this._makeUrl(feed);
			callback(u);
		} else {
			// get next item in feed
			var u = this._makeNextUrl(feed);
			var f = this;
			var restart = function() {
				var u = f._makeUrl(feed);
				callback(u);
			}
			$.ajax({
				url: u,
    		type: 'GET',
	    	cache: false,
				datatype: 'json',
	    	success: function(data, textstatus, jqXHR) {
					feed.current = data.next;
					var u = f._makeUrl(feed);
					callback(u);
				},
		    error: restart,
		    timeout: restart
			});
		}
	} else { 
		callback('screen-zone.php?zone='+zone.id);
	}
}

var feeds = new FeedList();

function loadCss(css) {
	var l = document.getElementsByTagName('link');
	for (var i=0;i<l.length;i++) {
		var rel = l[i].getAttribute('rel');
		var href = l[i].getAttribute('href');
		if ((rel=='stylesheet')&&(href==css)) {
			l = null;
			rel = null;
			href = null;
			return;
		}
		rel = null;
		href = null;
	}
	l = null;

	l = document.createElement('link');
	l.setAttribute('rel','stylesheet');
	l.setAttribute('type','text/css');
	l.setAttribute('href',css);
	var h = document.getElementsByTagName('head');
	if (h.length!=1) {
		console.error('there should be only one <head> element !');
		console.error(h);
		l = null;
		h = null;
		return;
	}
	h = h[0];
	h.appendChild(l);
	h = null;
	l = null;
}

function createZone (zone) {
  var div = document.createElement('DIV');
  div.style.position='absolute';
  div.id = zone.id;
  div.style.top = zone.y;
  div.style.left = zone.x;
  div.style.width = zone.w;
  div.style.height = zone.h;
  div.style.color = zone.color;
  div.style.backgroundColor = zone.backgroundColor;
  div.style.fontSize = zone.fontSize;
	div.style.lineHeight = 1.2;
  div.style.overflow = 'hidden';
  return div;
}

function updateZone(data) {
  if (data.html||data.js) {
    var z = document.getElementById(data.zone);
		if (data.js) {
			$.ajax({
				url: data.js,
				dataType: 'script',
				cache: false,
				success: function() {
					// start the plugin furnished js
					plugin(data);
		 		},
				error: function(jqHXR, textStatus, errorThrown) {
					console.log(textStatus);
					console.log(errorThrown);
				}
			});
		} else {
			z.innerHTML = data.html;
			z.firstChild.style.visibility='visible';
		}
    return true;
  }
  console.log ('nothing to update');
	console.log (data);
  return false;
}


function refreshZone(zone) {
  function restart () {
    console.log('erreur pendant la requete ajax, restarting');
    window.setTimeout(function() {
      refreshZone(zone);
    },1000);
  }
	function fetchData (url) {
	  $.ajax({
  	  url: url,
    	type: 'GET',
	    cache: false,
			datatype: 'json',
	    success: function(data, textstatus, jqXHR) {
	
	      var updated = updateZone (data);
	      textstatus = null;
	      jqXHR = null;
	      if (updated) {
	        // sets up the timer
	        var delay = 10000;
	        // delay in the json is expressed in seconds
	        if (data.delay) delay = data.delay*1000;
	        window.setTimeout(function() {
	          refreshZone(zone);
	        }, delay);
	        delay = null;
	      }
	      updated = null;
	      data = null;
	    },
	    error: restart,
	    timeout: restart
	  });
	}

	function callRefresh(zone, callback) {
		if (simulate) {
			feeds.update(zone, callback);
		} else {
			callback('screen-zone.php?zone='+zone.id);
		}
	}
	callRefresh(zone, fetchData);
}

function zoneClosure (zone) {
  var z = zone;
  function timeoutFunction () {
    refreshZone(z);
  };
  return timeoutFunction;
}

function createBackgroundZones(data) {
  var $b = $('body');
  $b.css('background-color',data.backgroundColor);
  $b.css('margin', '0');
  $b.css('font-family', 'Ubuntu');
	// calculate font-size according to actuel height of screen
	var fontsize = (window.innerHeight*data.fontsize)+'px';
  $b.css('font-size', fontsize);
  $b.css('width', data.resolution.width);  
  $b.css('height', data.resolution.height);
  $b.css('overflow', 'hidden');
  //$b.append(createLoadIndicator());
  var z = data.zones;
  for (var i=0; i<z.length; i++) {
    $b.append(createZone (z[i]));
    window.setTimeout (zoneClosure(z[i]),500);
  }
}

function makeBackgroundUrl () {
	var url = '';
	url += 'background.php';
	
	return url;
}

function reloadBackground() {
	var url = makeBackgroundUrl();
  $.ajax({
    url: url,
    type: 'GET',
    cache: false,
    datatype: 'json',
    success: function(data,textstatus,jqXHR) {
      // data is my json object
			bgdata = data;
      createBackgroundZones(data);
      //updateScreenInfo();
    },
    error: function() {
      console.log('error while loading the background information');
    },
    timeout: function() {
      console.log('timeout while loading the background information');
    }
  });
}

function parseArgs() {
	var args = document.location.search.substring(1).split('&');
	var argsparsed = {};
	for (i=0;i<args.length;i++) {
		var a = decodeURIComponent(args[i]);
		p = a.indexOf('=');
		if (p==-1) {
			argsparsed[a.trim()] = true;
		} else {
			argsparsed[a.substring(0,p).trim()] = a.substring(p+1);
		}
	}
	return argsparsed;
}

$(document).ready(function() {
	args = parseArgs();
	if (args['screenid'] !== undefined) {
		simulate = true;
	}
  reloadBackground();
});

