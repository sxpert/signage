var bgdata = null;

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
  $.ajax({
    url: 'screen-zone.php?zone='+zone.id,
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


function reloadBackground() {
  $.ajax({
    url: 'background.php',
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

$(document).ready(function() {
  reloadBackground();
});

