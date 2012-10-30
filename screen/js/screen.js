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

function resizeBody (res) {
    sw = document.width;
    sh = document.height;
    dw = sw / res['w'];
    dh = sh / res['h'];
    scale = Math.min(dw, dh);
    w = Math.floor(res['w']*scale);
    h = Math.floor(res['h']*scale);
    // modifie le style & co
    var ebs = document.body.style;
    ebs.fontSize = 100*scale;
    if (w < sw) {
	ebs.marginTop = 0;
	ebs.marginBottom = 0;
	ebs.marginLeft = (sw - w)/2;
	ebs.marginRight = (sw - w)/2;
    } else {
	ebs.marginTop = (sh - h) / 2;
	ebs.marginBottom = (sh - h) / 2;
	ebs.marginLeft = 0;
	ebs.marginRight = 0;
    }   
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
	    window.onresize = function () {
		resizeBody (res);
	    }
	    resizeBody(res);
	    document.body.parentElement.style.backgroundColor = 'black';
	    if (screen['backgroundColor']!==undefined)
		document.body.style.backgroundColor = screen['backgroundColor'];
	    document.body.style.fontFamily = 'Sans';
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
		/*
		if (zone['borderWidth']!==undefined) {
		    border = zone['borderWidth'];
		    div.style.borderWidth = border+'px';
		    div.style.borderStyle = 'solid';
		    if (zone['borderColor']!==undefined)
			div.style.borderColor = zone['borderColor'];
		} else */
		    border = 0;
		
		div.style.left = (zone['x']/res['w'])*100+'%';
		div.style.top = (zone['y']/res['h'])*100+'%';
		div.style.width = ((zone['w']-border*2)/res['w'])*100+'%';
		div.style.height = ((zone['h']-border*2)/res['h'])*100+'%';
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