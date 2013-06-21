function plugin (data) {
	loadCss (data.spip.style);

	var spip = document.createElement('div');
	spip.id='spip';
	var spipdate = document.createElement('div');
	spipdate.id='date';
	spipdate.appendChild(document.createTextNode(data.spip.date));
	spip.appendChild(spipdate);
	var spipcaption = document.createElement('div');
	spipcaption.id='caption';
	spipcaption.appendChild(document.createTextNode(data.spip.caption));
	spip.appendChild(spipcaption);
	var spiptext = document.createElement('div');
	spiptext.id='text';
	spiptext.innerHTML = data.spip.text;
	spip.appendChild(spiptext);

	var z = document.getElementById(data.zone);
	if (z.hasChildNodes()) {
	  var o = z.replaceChild(spip,z.firstChild);
	} else
		z.appendChild(spip);


	var zh = z.offsetHeight;
	var zoneh = parseFloat(window.getComputedStyle(z, null).height);
	var dateh = parseFloat(window.getComputedStyle(spipdate, null).height);
	var captionh = parseFloat(window.getComputedStyle(spipcaption, null).height);
	var availh = Math.ceil(zoneh - dateh - captionh);

	var size = 50;
	var sh;


	do {
		spiptext.style.fontSize=size+'%';
		var sth = spiptext.getBoundingClientRect().height;
		size = size - 1;
	} while ((sth>availh)&&(size>25));
}
