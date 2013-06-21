function plugin(data) {
	// add css if not there yet
	loadCss (data.apod.style);

	var apod = document.createElement('div');
	apod.id='apod';

	// title bar
	var apodtitle = document.createElement('div');
	apodtitle.id = 'apodtitle';

	// nasa logo
	var nasalogocell = document.createElement('span');
	nasalogocell.id = 'nasalogocell';
	var nasalogoimg = document.createElement('img');
	nasalogoimg.id = 'nasalogoimg';
	nasalogoimg.setAttribute('src','/lib/images/NASA_logo.svg');
	nasalogocell.appendChild(nasalogoimg);

	// datebar
	var apoddateinfo = document.createElement('span');
	apoddateinfo.id = 'apoddateinfo';
	apoddateinfo.appendChild(document.createTextNode('APOD'));
	apoddateinfo.appendChild(document.createElement('br'));
	apoddateinfo.appendChild(document.createTextNode(data.apod.date));

	// border width
	var b = Math.ceil(window.innerWidth*3/bgdata.resolution.w);
	apoddateinfo.style.borderRightWidth = b+'px';

	// caption
	var apodcaption = document.createElement('span');
	apodcaption.id = 'apodcaption';
	var caption = document.createTextNode(data.apod.caption);
	apodcaption.appendChild(caption);

	// add the items to the title bar
	apodtitle.appendChild(nasalogocell);
	apodtitle.appendChild(apoddateinfo);
	apodtitle.appendChild(apodcaption);

	// add to the apod
	apod.appendChild(apodtitle);

	// contents under the title bar
	var apodcontents = document.createElement('div');
	apodcontents.id = 'apodcontents';
	
	// picture
	var apodphotocell = document.createElement('span');
	apodphotocell.id = 'apodphotocell';
	var apodphotoimg = document.createElement('img');
	apodphotoimg.id = 'apodphotoimg';
	apodphotoimg.setAttribute ('src', data.apod.picture);

	// add picture
	apodphotocell.appendChild(apodphotoimg);
	apodcontents.appendChild(apodphotocell);

	// create the text items
	var text = data.apod.text;
	var atc = document.createElement('span');
	atc.id = 'apodtextcell';
	for (var i=0;i<text.length;i++) {
		t = document.createTextNode(text[i]);
		var p = document.createElement('p');
		p.appendChild(t);
		atc.appendChild(p);
	}
	
	// add the text items
	apodcontents.appendChild(atc);

	// add the contents to the screen
	apod.appendChild(apodcontents);

	// set the item as the only child to the zone
	var z = document.getElementById(data.zone);
	if (z.hasChildNodes()) {
		var o = z.replaceChild(apod,z.firstChild);
	} else 
		z.appendChild(apod);
}
