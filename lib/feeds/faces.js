function plugin (data) {
	loadCss (data.faces.style);

	var faces = document.createElement('div');
	faces.id='faces';
	
	var facespicdiv = document.createElement('div');
	facespicdiv.id = 'faces-pic-div';
	var facespic = document.createElement('img');
	facespic.id = 'faces-pic';
	facespic.src = data.faces.picture[0];
	facespicdiv.appendChild(facespic);
	faces.appendChild(facespicdiv);

	// person's name

	var facesdatadiv = document.createElement('div');
	facesdatadiv.id = 'faces-data-div';
	var facesnamediv = document.createElement('div');
	facesnamediv.id = 'faces-name-div';
	facesnamediv.appendChild(document.createTextNode(data.faces.text.firstname+' '+
		data.faces.text.lastname));
	facesdatadiv.appendChild(facesnamediv);

	// function

	var facesfunctiondiv = document.createElement('div');
	facesfunctiondiv.id = 'faces-function-div';
	facesfunctiondiv.appendChild(document.createTextNode(data.faces.text.function));
	facesdatadiv.appendChild(facesfunctiondiv);

	// teams (if any);

	var e = data.faces.text.groups;
	
	if (e.length>0) {

		var equipesdiv = document.createElement('div');
		equipesdiv.id ='faces-equipes-div';
		equipesdiv.appendChild(document.createTextNode('Équipe'+(e.length>1?'s':'')+': '));
		var equipeslistdiv = document.createElement('div');
		equipeslistdiv.id = 'faces-equipes-list-div';
		for (var i=0; i<e.length; i++) { 
			var d = document.createElement('div')
			d.appendChild(document.createTextNode(e[i]));
			equipeslistdiv.appendChild(d);
		}
		equipesdiv.appendChild(equipeslistdiv);
		facesdatadiv.appendChild(equipesdiv);
	}

	faces.appendChild (facesdatadiv);

	var logos = {
		'medical': '/admin/images/medical.png',
		'fire': '/admin/images/fire.png',
		'evac': '/admin/images/evac.png',
		'catering': '/admin/images/catering.png'
	};

	var logosdiv = document.createElement('div');
	logosdiv.id = 'faces-logos-div';
	data.faces.text.tags.forEach (function (t) {
		var u = logos[t];
		var e;
		if (u!==undefined) {
			e = document.createElement('img');
			e.className='faces-logo';
			var d = new Date();
			e.src = u+'?_='+d.getTime();
		}
		if (e!==undefined)
			logosdiv.appendChild(e);
	});

	faces.appendChild (logosdiv);
	
	console.log (data.faces.text.tags);

	var z = document.getElementById(data.zone);
	if (z.hasChildNodes()) {
	  var o = z.replaceChild(faces,z.firstChild);
	} else
		z.appendChild(faces);
}
