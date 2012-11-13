function createLoadIndicator() {
  var div = document.createElement('DIV');
  div.id='loading';
  div.style.position='absolute';
  div.style.top = 3;
  div.style.left = 1903;
  div.style.width = 14;
  div.style.height = 14;
  div.style.backgroundColor = 'red';
  div.style.borderRadius = 7;
  return div;
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
  div.style.overflow = 'hidden';
  return div;
}

function updateZone(data) {
  if (data.html) {
    var z = document.getElementById(data.zone);
    var td = document.createElement('DIV');
    td.innerHTML = data.html;
    var d = td.removeChild(td.firstChild);
    td = null;
    if (z.hasChildNodes()) {
      var old = z.replaceChild(d, z.firstChild);
      old = null;
    } else
      z.appendChild(d);
    // make first child visible
    z.firstChild.style.visibility='visible';
    d = null;
    z = null;
    try {
      window.gc();
    } catch (e) {
      // do nothing
    }
    return true;
  }
  console.log ('nothing to update');
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
  $b.css('font-size', '100px');
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

