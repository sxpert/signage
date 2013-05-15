function uriParams (){
	var s = window.location.search;
	var a = {};
	if ((s.length!=0)&&(s[0]=='?')) {
		s = s.substr(1);
		var e = s.split('&');
		for (var i in e) {
			var param=e[i];
			var p = s.indexOf('=');
			if (p>=0) {
				var key = s.substring(0,p);
				var value = s.substr(p+1);
				a[key]=value;
			}
		}
	}
	return a;
}

/******************************************************************************
 * screen parameters
 */

var screenParams = {
	'width' : [ 'Largeur', 'px', 1920],
	'height' : [ 'Hauteur', 'px', 1080]
};

var zoneParams = {
	'top' : [ 'Pos. Haut', 'px' ],
	'left' : [ 'Pos. Gauche', 'px' ],
	'width' : [ 'Largeur', 'px' ],
	'height' : [ 'Hauteur', 'px' ]
};

var cssParams = [
	'background', 'background-color', 'color',
	'font-size'
];

var screenData = undefined;

function getScreenData (callback) {
	/* grab screen id from current url */
	var p = uriParams();
	$.ajax ({
		dataType: 'json',
		type: 'POST',
		url: '/admin/ajax/screen-zones.php',
		data: p,
		success: function (data, textStatus, jqXHR) {
			screenData = data;
			callback(data);
		},
		error: function (jqXHR, textStatus, errorThrown) {
			console.log (jqXHR);
			console.log (textStatus);
			console.log (errorThrown);
		}
	});
}

function getScreenParamLabel(param) {
	return screenParams[param][0];
}

function getScreenParamUnit(param) {
	return screenParams[param][1];
}

function getScreenParamValue(param) {
	var p = screenData['params'];
	var v = undefined;
	if (p!==null)
		v = p[param];
	if ((p===null)||(v===undefined)) {
		/* find default value */
		v = screenParams[param][2];
	}
	return v;
}

function setScreenParam(param, value) {
	var p = screenData['params']
	if (p===null) {
		p = {};
		screenData['params'] = p;
	} 
	p[param] = value;
}

function getZoneParamLabel (param) {
	return zoneParams[param][0];
}

function getZoneParamUnit (param) {
	return zoneParams[param][1];
}

function getZoneParam (zone, param) {
	console.log(screenData);
	var zs = screenData['zones'];
	if (zs===null) return null;
	// find zone
	for (i in zs) {
		z = zs[i];
		console.log (i);
		console.log (z);
		if (z.hasOwnProperty('name')) {
			if (z['name']==zone) {
				// find parameter
				if (!z.hasOwnProperty('params')) return null;
				var params = z['params'];
				if (params===null) return null;
				if (params.hasOwnProperty(param)) return params[param];
				return null;
			}
		}
	}
	return null;
}

/******************************************************************************
 *
 */

function getZoneViewer() {
	var zv = document.getElementById("zone-viewer");
	var $zv = undefined;
	console.log(zv);
	if (zv===null) {
		var div = document.createElement('div');
		div.id='zone-viewer';
		div.style.border='1px solid black';
		var $sim = $("#simulator");
		div.style.width=$sim.css('width');
		div.style.height=$sim.css('height');
		div.style.position='relative';
		$zv = $(div);
		$zv.insertAfter($sim);
	} else {
		$zv = $(zv);
	}
	return $zv;
}

/******************************************************************************
 * edition de la liste des zones
 */

function addEmptyZone () {
	var zones = screenData['zones'];
	var nz = { 'name': '', 'params': null, 'new': true };
	zones.push(nz);
	console.log(screenData);
	return (zones.length-1)
}

function removeZone (zoneid) {
	/* la zone doit etre la derniere */
	var z = screenData['zones'];
	console.log(z.length-1);
	console.log(zoneid);
	if ((z.length-1)!=zoneid) {
		console.log(screenData);
		console.log ('attempt to remove zone that is not last in list');
		return;
	}
	screenData['zones'] = z.slice(0, zoneid-2);
	console.log(screenData);
}

/******************************************************************************
 * affichage des zones
 */

function showZones () {
	var $zv = getZoneViewer();
	var zones=screenData['zones'];
	for (z in zones) {
		var zone = zones[z];
		var name = zone['name'];
		var params = zone['params'];
		var id = 'zv_'+z;
		var $zone_el = $('#'+id);
		if ($zone_el.length>0) {
			$zone_el = $zone_el[0];
		} else {
			/* not found. add zone item */
			var ze = document.createElement('div');
			ze.style.position='absolute';
			ze.id = id;
			var ze_name = document.createElement('span');
			ze_name.style.backgroundColor='black';
			ze_name.style.color='white';
			ze_name.appendChild(document.createTextNode(name));
			ze.appendChild(ze_name);
			$zone_el=$(ze);
			$zone_el.appendTo($zv);
		}
	}
}

/******************************************************************************
 * événements liés au formulaire de zone
 */

function zoneNameUpdated(event) {
	var zn_input = event.target;
	var name = zn_input.value;
	var zoneid = zn_input.getAttribute('zoneid');

	var zones = screenData['zones'];
	/* find if we already have a zone by that name 
	 * or name is empty
	 */
	var conflict = false;
	for (i=0; i<zones.length; i++) {
		if ((i!=zoneid)&&(zones[i]['name']==name)) {
			conflict = true;
		}
	}
	var c = '#99ff99';
	if ((conflict)||(name.length==0)) {
		c = '#ff9999';
	}
	zn_input.style.backgroundColor=c;

	z = zones[zoneid];
	z['name'] = name;

	var id = 'zv_'+zoneid;
	var $zd = $('#'+id);
	var $sp = $zd.children('span:first-child')
	$sp.empty();
	$(document.createTextNode(name)).appendTo($sp);
}

function findRow($item) {
	var $p = $item.parents().filter('tr')[0];
	return $($p);
}

function getZoneId($row) {
	var $cells = $row.children();
	var $cell1 = $($cells[0]);
	var input = $cell1.find('input')[0];
	var zoneid = input.getAttribute('zoneid');
	return zoneid;
}

function cancelZoneEdit (event) {
	var $button = $(event.target);
	event.stopImmediatePropagation();
	var $row = findRow($button)
	var zoneid = getZoneId($row);
	console.log(zoneid);
	/* suppression de la zone si elle est nouvelle */
	var z = screenData['zones'][zoneid];
	if (z['new']===true) {
		console.log('removing new zone '+zoneid);
		removeZone(zoneid);
		$row.remove();
		$('#zv_'+zoneid).remove();
	}
	$("#add-zone").show();
}


/******************************************************************************
 * fonctions de création du formulaire d'édition de zone
 */


function genSizeField(label, labelWidth, name, width, height, marginRight, unit, unitWidth) {
	var span = document.createElement('span');
	var span_label = document.createElement('span');
	var text_label = document.createTextNode(label);
	var input = document.createElement('input');
	var span_unit = document.createElement('span');
	var text_unit = document.createTextNode(unit);

	span_label.appendChild(text_label);
	
	span.appendChild(span_label);

	input.name=name;

	span.appendChild(input);

	span_unit.appendChild(text_unit);

	span.appendChild(span_unit);
	
	return span;
}

function genCssRuleField(height) {
	var div = document.createElement('div');
	// un select avec des noms de rules css
	var select = document.createElement('select');
	for(var i=0;i<cssParams.length;i++) {
		var option=document.createElement('option')
		option.value=cssParams[i];
		option.appendChild(document.createTextNode(cssParams[i]));
		select.appendChild(option);
	}
	div.appendChild(select);
	// un champ pour entrer les valeurs de la rule
	var value = document.createElement('input');
	value.type='text';
	div.appendChild(value);
	// un bouton pour supprimer la rule
	var del = document.createElement('button');
	del.appendChild(document.createTextNode('supprimer'));
	$(del).click(function (event) {
		$app = $(event.target);
		event.stopImmediatePropagation();
		$app.parent().remove();
	});
	div.appendChild(del);
	var $div = $(div);
	$div.addClass('css-rule');
	return $div;
}

function genZoneForm($lastrow, zoneid) {
	var newrow = document.createElement('tr');

	/* cell with the input for the name */
	var cell1 = document.createElement('td');
	cell1.style.verticalAlign='top';
	newrow.appendChild(cell1);
	var input_name = document.createElement('input');
	input_name.style.position='relative';
	var $firstcell = $lastrow.children().first();
	var h = ($firstcell.height()-4)+'px';
	input_name.name='zone-name';
	input_name.setAttribute('zoneid',zoneid);
	$(input_name).keyup(zoneNameUpdated);
	cell1.appendChild(input_name);
	
	/* cell with parameters */
	var cell2 = document.createElement('td');
	newrow.appendChild(cell2);

	/* dimensions */
	var tw = '75px';
	var w = '100px';
	var mr = '3px';
	var uw = '49px';

	/* deux lignes pour 4 dimensions */
	var l1 = document.createElement('div'); 
  l1.appendChild( genSizeField('pos. gauche', tw, 'left', w, h, mr, 'px', uw));
  l1.appendChild( genSizeField('pos. haut', tw, 'top', w, h, mr, 'px', uw));
	cell2.appendChild(l1);
	
	var l2 = document.createElement('div');
  l2.appendChild( genSizeField('largeur', tw, 'width', w, h, mr, 'px', uw));
  l2.appendChild( genSizeField('hauteur', tw, 'heigth', w, h, mr, 'px', uw));
	cell2.appendChild(l2);

	/* deux lignes de boutons */

	/* ajout de regles css */
	var l3 = document.createElement('div');
	var appendcss = document.createElement('button');
	appendcss.id = 'append-zone-css';
	appendcss.appendChild(document.createTextNode('Ajouter une règle CSS'));
	$(appendcss).click(function (event) {
		var $app = $(event.target);
		event.stopImmediatePropagation();
		/* création de l'objet */
		genCssRuleField(h).insertBefore($app.parent());
	});
	l3.appendChild(appendcss);
	cell2.appendChild(l3);
	
	/* annuler / valider */
	var l4 = document.createElement('div');
	var cancel = document.createElement('button');
	cancel.id='cancel-zone';
	cancel.appendChild(document.createTextNode('Annuler la zone'));
	$(cancel).click(cancelZoneEdit);
	l4.appendChild(cancel);
	var validate = document.createElement('button');
	validate.id='validate-zone';
	validate.appendChild(document.createTextNode('Créer la zone'));
	$(validate).click(function (event) {
		$app = $(event.target);
		event.stopImmediatePropagation();
		alert ('create zone');
	});
	l4.appendChild(validate);
	cell2.appendChild(l4);
	
	return newrow;
}

function findTableLastRow ($table) {
	var $tbody = $table.find('tbody');
	var $lastrow = $tbody.children().last();
	return $lastrow;
}

function appendRowToTable ($lastrow, newrow) {
	if (!$lastrow.hasClass('odd'))
		$(newrow).addClass('odd');
	$lastrow.parent().append(newrow);
}

function showZoneForm ($addZoneButton, zoneid) {
	/* hide button */
	$addZoneButton.hide();
	/* hide simulator */
	$("#simulator").hide();
	/* show zone viewer */
	getZoneViewer().show();
	var $table = $addZoneButton.parent().prev();
	var $lastrow = findTableLastRow($table);
	var newrow = genZoneForm($lastrow, zoneid);
	appendRowToTable ($lastrow, newrow);
	return $(newrow);
}

$("#add-zone").click(function(event) {
  /*
   * get the value of the screen id
   */
  var $addZoneButton = $(event.target)
	event.stopImmediatePropagation();

	getScreenData(function (data) {
		var id = addEmptyZone();
		var $zone = showZoneForm ($addZoneButton, id);
		showZones();
	});
});


/******************************************************************************
 *
 * Display screen data 
 * 
 */

function getTable (id, headers) {
	var $t = $('#'+id);
	/* checks if we have thead */
	var $th = $t.children('thead');
	if ($th.length==0) {
		$th = $('<thead/>').appendTo($t);
		$tr = $('<tr/>').appendTo($th);
		for (var i=0; i<headers.length; i++) {
			$h = $('<th/>').append(headers[i]).appendTo($tr);
			$h.addClass('col'+i);
		}
	}
	return $t;
}

function getTBody ($table) {
	var $tb = $table.children('tbody');
	if ($tb.length==0) {
		$tb = $('<tbody/>').appendTo($table);
	} else
	/* only one tbody per table */
		$tb = $tb[0];
	return $tb;
}

function iconButton(file, func) {
	return $('<img/>').attr('src',file).addClass('icon-button').click(func);
}

/****
 * screen parameters
 */

function screenParamDisplay(name) {
	return [ 
           iconButton('images/edit.png',editScreenParam), 
					 $('<span/>').attr('name',name).append(getScreenParamValue(name)),
           '&nbsp;'+getScreenParamUnit(name),
				 ];
}

function screenParamForm(name) {
	return [
           iconButton('images/cancel.png',editScreenParamCancel),
           iconButton('images/checkmark.png',editScreenParamValidate), 
					 $('<input/>').attr('name', name).attr('type','text').attr('value',getScreenParamValue(name)),
					 '&nbsp;'+getScreenParamUnit(name),
				 ];
}

function editScreenParam (event) {
	var $button = $(event.target);
	var $cell = $button.parents('td');
	var name = $cell.children('span').attr('name');
	$cell.empty().append(screenParamForm(name));
}

function editScreenParamCancel (event) {
	var $button = $(event.target);	
	var $cell = $button.parents('td');
	var name = $cell.children('input').attr('name');
	$cell.empty().append(screenParamDisplay(name));
}

function editScreenParamValidate (event) {
	var $button = $(event.target);	
	var $cell = $button.parents('td');
	var $input = $cell.children('input');
	var name = $input.attr('name');
	var value = $input.attr('value');
	setScreenParam (name, value);
	$cell.empty().append(screenParamDisplay(name));
}

function displayScreenData() {
	var $tbody = getTBody(getTable ('screen-info', ['','valeurs']));
	var i=0;
	for (k in screenParams) {
		var $tr = $('<tr/>').append($('<td/>').append(getScreenParamLabel(k)))
			.append($('<td/>').append(screenParamDisplay(k)))
			.appendTo($tbody);
		if ((i++)%2==0)
			$tr.addClass('odd');
	}
}

/****
 * zone parameters
 */

function editZoneParams () {
	alert ('editing zone params');
}

function zoneParamDisplayDimension (name, dimension) {
	return $('<span/>').addClass('zone-param').append([
					 $('<span/>').addClass('zone-param-label').append(getZoneParamLabel(dimension)),
					 $('<span/>').addClass('zone-param-value').append(getZoneParam(name, dimension)),
					 $('<span/>').addClass('zone-param-unit').append(getZoneParamUnit(dimension)),
				 ]);
}

function zoneParamDisplayDimensions (name, dimensions) {
	var dims = [];
	for (i in dimensions)
		dims.push(zoneParamDisplayDimension(name, dimensions[i]));
	return dims;
}

function zoneParamDisplayCssRule (name, css) {
	var p = getZoneParam(name, css);
	if (p===null) return null;
	return $('<div/>')
					 .append($('<span/>').append(css).addClass('css-name'))
					 .append($('<span/>').append(p));
}

function zoneParamDisplayCssRules (name) {
	var rules = [];
	for (i in cssParams) 
		rules.push (zoneParamDisplayCssRule (name, cssParams[i]));
	return rules;
}

function zoneParamDisplay(name) {
	return [
					 iconButton('images/edit.png',editZoneParams),
					 $('<div/>').append(zoneParamDisplayDimensions(name,['top', 'left'])),
					 $('<div/>').append(zoneParamDisplayDimensions(name,['width', 'height'])),
				 ].concat(zoneParamDisplayCssRules(name));
}

function displayZonesData() {
	var $tbody = getTBody(getTable ('zones-info', ['nom','paramètres']));
	var i=0;
	var zones = screenData['zones'];
	for (k in zones) {
		var zn = zones[k]['name'];
		var $tr = $('<tr/>')
			.append($('<td/>').append(zn))
			.append($('<td/>').append(zoneParamDisplay(zn)))
			.appendTo($tbody);
		if ((i++)%2==0)
			$tr.addClass('odd');
	}
}

function displayData () {
	displayScreenData();
	displayZonesData();
}

$(function () {
	getScreenData(displayData);
});

