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



	window.setTimeout(function() {

		var spip_height  = parseFloat(window.getComputedStyle(spip, null).height);
		var spip_padding_top = parseFloat(window.getComputedStyle(spip, null).paddingTop);
		var spip_padding_bottom = parseFloat(window.getComputedStyle(spip, null).paddingBottom);
		var date_height = parseFloat(window.getComputedStyle(spipdate, null).height);
		var caption_height = parseFloat(window.getComputedStyle(spipcaption, null).height);
		var caption_margin_top = parseFloat(window.getComputedStyle(spipcaption, null).marginTop);
		var caption_margin_bottom = parseFloat(window.getComputedStyle(spipcaption, null).marginBottom);
		var available_height = spip_height - spip_padding_top - spip_padding_bottom - date_height - 
			caption_height - caption_margin_top - caption_margin_bottom;
		console.log (spip_height, spip_padding_top, spip_padding_bottom, date_height, caption_height,
			caption_margin_top, caption_margin_bottom, available_height);
		var size = 50;

		do {
			spiptext.style.fontSize=size+'%';
			var sth = spiptext.getBoundingClientRect().height;
			console.log(available_height,sth,size);
			size = size - 1;
		} while ((sth>=available_height)&&(size>=25));
	}, 10);
}
