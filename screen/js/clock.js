function plugin(data) {
	function pad(s,l) {
		if (typeof(s)=='number')
			s=s.toString();
		while(s.length<l)
			s = '0'+s;
		return s;
	}
	var d = new Date();
	var year = pad(d.getFullYear(),4);
	var month = pad(d.getMonth()+1,2);
	var day = pad(d.getDate(),2);
	var ds = year+'-'+month+'-'+day;
	var hours = pad(d.getHours(),2);
	var minutes = pad(d.getMinutes(),2);
	var hs = hours+':'+minutes;

	function checkStyle(d) {
		d.style.textAlign='center';
		d.style.borderRightColor='#ffffff';
		d.style.borderRightStyle='solid';
		var b = Math.ceil(window.innerWidth*3/bgdata.resolution.w);
		d.style.borderRightWidth = b+'px';
	}	
	var z = document.getElementById(data.zone);
	if (z.hasChildNodes()) {
		// already created
		var d = z.children[0];
		checkStyle(d);
		var c = d.childNodes;
		var nd = c[0];
		nd.textContent = ds;
		var nh = c[2];
		nh.textContent = hs;
	} else {
		// structure must be created
		var d = document.createElement('div');
		checkStyle(d);
		d.appendChild(document.createTextNode(ds));
		d.appendChild(document.createElement('br'));
		d.appendChild(document.createTextNode(hs));
		z.appendChild(d);
	}
}
