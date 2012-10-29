
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

function createZone(id, x, y, w, h, borderw, borderc) {
    var s = '';
    if (borderw !==undefined) {
	w -= 2*borderw;
	h -= 2*borderw;
	s+='border:'+borderw+'px solid ';
	if (borderc !== undefined) 
	    s+=borderc;
	else
	    s+='black'; 
	s+=';';
    }
    s+='position:absolute;';
    s+='top:'+y+'px;';
    s+='left:'+x+'px;';
    s+='width:'+w+'px;';
    s+='height:'+h+'px;';

    var d = document.createElement('div');
    var st = document.createAttribute('style');
    st.nodeValue = s;
    d.setAttributeNode(st);
    st = document.createAttribute('id');
    st.nodeValue = id;
    d.setAttributeNode(st);
    return d
}

function initApplication() {
    var sw = window.innerWidth;
    var sh = window.innerHeight;

    XHR ('background.php', function (mimetype, contents) {
	if (mimetype == 'application/json') {
	    var z = JSON.parse(contents);
	    var res = z['resolution'];
	    // calcule la mise à l'échelle
	    dw = sw / res['w'];
	    dh = sh / res['h'];
	    ds = Math.min(dw, dh);
	    alert (sw+' '+sh+'\n'+
	           ds+'\n'+
	           res['w']*ds+' '+res['h']*ds);
	    
	} else 
	    alert ('unexpected mimetype : '+mimetype);
    });

}

document.onreadystatechange = function () {
    if (document.readyState == 'complete') {
	initApplication();
    }
}