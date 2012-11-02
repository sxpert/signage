var scale = 1;

function XHR (url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
	if (xhr.readyState == 4) {
	    callback (xhr.getResponseHeader("Content-Type"),xhr.responseText);
	}
    };
    xhr.open('GET', url, true);
    xhr.send();
}

function fetchInformations () {
    XHR ('screen.php', function (mimetype, contents) {
	if (mimetype == 'application/json') {
	    var data = JSON.parse(contents);
	    var keys = Object.keys(data);
	    console.log(keys);
	    for(var i=0;i<keys.length;i++) {
		k = keys[i];
		console.log(k);
		e = document.getElementById(k);
		e.innerHTML = data[k];
	    }
	} else 
	    console.log('unexpected mimetype : '+mimetype); 
    });
}

function initApplication() {
    window.setInterval (fetchInformations,10000);
    XHR ('background.php', function (mimetype, contents) {
	if (mimetype == 'application/json') {
	    var screen = JSON.parse(contents);
	    var res = screen['resolution'];
	    document.body.parentElement.style.backgroundColor = 'black';
	    if (screen['backgroundColor']!==undefined)
		document.body.style.backgroundColor = screen['backgroundColor'];
	    document.body.style.fontFamily = 'Sans';
	    document.body.style.margin = 0;
	    document.body.style.fontSize= '100px';
	    var contents = document.createElement('div');
	    contents.style.position = 'relative';
	    contents.style.width = '100%';
	    contents.style.height = '100%';
	    document.body.appendChild(contents);
	    var z = screen['zones'];
	    for(var i=0;i<z.length;i++) {
		var zone = z[i];
		var div = document.createElement('div');
		div.id = zone['id'];
		if (zone['backgroundColor']!==undefined)
		    div.style.backgroundColor = zone['backgroundColor'];
		if (zone['color']!==undefined)
		    div.style.color=zone['color'];
		div.style.position= 'absolute';
		border = 0;
		
		div.style.left = zone['x']+'px';
		div.style.top = zone['y']+'px';
		div.style.width = zone['w']-border*2+'px';
		div.style.height = zone['h']-border*2+'px';
		div.style.overflow = 'hidden';
		if (zone['fontSize']!==undefined) 
		    div.style.fontSize = zone['fontSize'];
		else
		    div.style.fontSize = '100%';
		console.log (div);
		contents.appendChild(div);
	    }
	    fetchInformations();
	} else 
	    console.log('unexpected mimetype : '+mimetype);
    });
}

document.onreadystatechange = function () {
    if (document.readyState == 'complete') {
	initApplication();
    }
}