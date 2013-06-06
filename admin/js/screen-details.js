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

function getZoneCount () {
	var zs = screenData['zones'];
	if (zs===null) return 0;
	return zs.length;
}

function addZone () {
	console.log(screenData);
	var zs = screenData['zones'];
	if (zs===null) {
		zs = [];
		screenData['zones'] = zs;
	}
	zs.push({});
	console.log(screenData);
}

function getZoneName (zoneid) {
	zoneid = parseInt(zoneid);
	var zs = screenData['zones'];
	if (zs===null) return null;
	if ((zoneid<0)&&(zoneid>=zs.length)) return null;
	var z = zs[zoneid];
	if (z===undefined) return '';
	return z['name'];
}

function getZoneParamLabel (param) {
	return zoneParams[param][0];
}

function getZoneParamUnit (param) {
	return zoneParams[param][1];
}

function getZoneParam (zone, param) {
	var zs = screenData['zones'];
	if (zs===null) return null;
	// find zone
	for (i in zs) {
		z = zs[i];
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
	return (zones.length-1)
}

function removeZone (zoneid) {
	/* la zone doit etre la derniere */
	var z = screenData['zones'];
	if ((z.length-1)!=zoneid) {
		console.log ('attempt to remove zone that is not last in list');
		return;
	}
	screenData['zones'] = z.slice(0, zoneid-2);
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
	/* suppression de la zone si elle est nouvelle */
	var z = screenData['zones'][zoneid];
	if (z['new']===true) {
		removeZone(zoneid);
		$row.remove();
		$('#zv_'+zoneid).remove();
	}
	$("#add-zone").show();
}

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

function getTFoot ($table) {
	var $th = $table.children('thead');
	var $tf = $table.children('tfoot');
	if ($tf.length==0) {
		$tf = $('<tfoot/>')
		$th.after($tf);
	} else 
		$tf = $tf[0];
	return $tf;
}

function iconButton(file, alt, func) {
	return $('<img/>').attr('src',file).addClass('icon-button')
		.attr('alt',alt).attr('title',alt).click(func);
}

/****
 * screen parameters
 */

function screenParamDisplay(name) {
	return [ 
           iconButton('images/edit.png','Modifier le paramètre',editScreenParam), 
					 $('<span/>').addClass('screen-param').attr('name',name).append(getScreenParamValue(name)),
           $('<span/>').addClass('screen-param-unit').append(getScreenParamUnit(name)),
				 ];
}

function screenParamForm(name) {
	return [
           iconButton('images/cancel.png','Annuler la modification du paramètre',editScreenParamCancel),
           iconButton('images/checkmark.png','Valider la modification du paramètre',editScreenParamValidate), 
					 $('<input/>').addClass('screen-param').attr('name', name).attr('type','text').attr('value',getScreenParamValue(name)),
					 $('<span/>').addClass('screen-param-unit').append(getScreenParamUnit(name)),
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
		var $tr = $('<tr/>').append($('<td/>').append($('<span/>').append(getScreenParamLabel(k))))
			.append($('<td/>').append(screenParamDisplay(k)))
			.appendTo($tbody);
		if ((i++)%2==0)
			$tr.addClass('odd');
	}
}

/****
 * zone parameters
 */

function evCancelZoneEdit (event) {
	var $button = $(event.target);
	var $line = $button.parents('tr');
	var zn = parseInt($line.attr('zoneid'));
	$line.empty().append(zoneParamDisplayRow(zn));

}

function evValidateZoneEdit (event) {
	var $line = $(event.target).parents('tr');
	var zoneid = parseInt($line.attr('zoneid'));
	var zone_name=$line.find('input.zone-param-name').val();
	var $zpv = $line.find('input.zone-param-value');
	var zone_top=parseInt($zpv[0].value);
	var zone_left=parseInt($zpv[1].value);
	var zone_width=parseInt($zpv[2].value);
	var zone_height=parseInt($zpv[3].value);
	var $csslabels = $line.find('select.css-label');
	var $cssvalues = $line.find('input.css-value');
	console.log($cssvalues);
}

function zoneParamFormZoneName (zoneid) {
	return $('<input/>').addClass('zone-param-name').val(getZoneName(zoneid));
}

function zoneParamFormDimension(zoneid, dimension) {
	return $('<span/>').addClass('zone-param').append([
					 $('<span/>').addClass('zone-param-label').append(getZoneParamLabel(dimension)),
					 $('<input/>').addClass('zone-param-value').append(getZoneParam(zoneid, dimension)),
					 $('<span/>').addClass('zone-param-unit').append(getZoneParamUnit(dimension)),
				 ]);
}

function zoneParamFormDimensions (zoneid, dimensions) {
	var dims = [];
	for (i in dimensions)
		dims.push(zoneParamFormDimension(name, dimensions[i]));
	return dims;
}

function evDelCssRule (event) {
	var $e = $(event.target);
	var $p = $e.parents('div :first');
	$p.remove();
}

function zoneParamFormCssRule (zoneid, rule) {
	var $select = $('<select/>').addClass('css-label');
	for (var i in cssParams) {
		var css = cssParams[i];
		var $option = $('<option/>').val(css).append(css);
		if (css==rule) 
			$option.attr('selected',true);
		$select.append($option);
	}
	var $input = $('<input/>').addClass('css-value');
	return [
					 $select,
					 $input,
					 iconButton('images/delete.png','Supprimer la règle CSS', evDelCssRule)
				 ];
}

function zoneParamFormCssRow (contents) {
		return $('<div/>').addClass('zone-param-row').addClass('zone-css-row').append(contents);
}

function evAddCssRule (event) {
	var $e = $(event.target);
	var $p = $e.parents('div :first');
	var $z = $e.parents('tr');
	var zoneid = parseInt($z.attr('zoneid'));	
	$p.before(zoneParamFormCssRow(zoneParamFormCssRule(zoneid, null)));
}

function zoneParamFormCssRules (zoneid) {
	var rules = [];
	for (var i in cssParams) {
		var css = cssParams[i];
		var p = getZoneParam(zoneid, css);
		console.log(css,p);
	}
	rules.push(zoneParamFormCssRow([iconButton('images/add.png','Ajouter une règle CSS', evAddCssRule),'&nbsp;']));
	return rules;
}

function zoneParamFormParams (zoneid) {
	return [
           iconButton('images/cancel.png','Annuler la modification de la zone',evCancelZoneEdit),
           iconButton('images/checkmark.png','Valider la modification de la zone',evValidateZoneEdit), 
					 $('<div/>').addClass('zone-param-row').append(zoneParamFormDimensions(zoneid,['top', 'left'])),
					 $('<div/>').addClass('zone-param-row').append(zoneParamFormDimensions(zoneid,['width', 'height'])),
				 ].concat(zoneParamFormCssRules(zoneid));
}

function zoneParamForm (zoneid) {
	return [
					 $('<td/>').append(zoneParamFormZoneName(zoneid)),
					 $('<td/>').append(zoneParamFormParams(zoneid)),
				 ];
}

function evEditZone (event) {
	var $button = $(event.target);
	var $line = $button.parents('tr');
	$line.empty().append(zoneParamForm($line.attr('zoneid')));
}

function evDeleteZone (event) {
	alert('remove zone');
}

function zoneParamDisplayDimension (zoneid, dimension) {
	var v = getZoneParam(zoneid, dimension);
	if ((v===undefined)||(v===null)||(v.length==0)) v='\'&nbsp;\'';
	return $('<span/>').addClass('zone-param').append([
					 $('<span/>').addClass('zone-param-label').append(getZoneParamLabel(dimension)),
					 $('<span/>').addClass('zone-param-value').append(v),
					 $('<span/>').addClass('zone-param-unit').append(getZoneParamUnit(dimension)),
				 ]);
}

function zoneParamDisplayDimensions (zoneid, dimensions) {
	var dims = [];
	for (i in dimensions)
		dims.push(zoneParamDisplayDimension(zoneid, dimensions[i]));
	return dims;
}

function zoneParamDisplayCssRule (zoneid, css) {
	var p = getZoneParam(zoneid, css);
	if (p===null) return null;
	return $('<div/>')
					 .append($('<span/>').append(css).addClass('css-name'))
					 .append($('<span/>').append(p));
}

function zoneParamDisplayCssRules (zoneid) {
	var rules = [];
	for (i in cssParams) 
		rules.push (zoneParamDisplayCssRule (zoneid, cssParams[i]));
	return rules;
}

function zoneParamDisplayZoneName (zoneid) {
	var name = getZoneName(zoneid);
	if ((name===undefined)||(name===null)||(name.length==0)) name = '&nbsp;';
	return $('<span/>').addClass('zone-param-name').append(name);
}

function zoneParamDisplay(zoneid) {
	return [
					 $('<div/>').addClass('zone-buttons').append([
						 iconButton('images/edit.png','Modifier les paramètres de la zone',evEditZone),
						 iconButton('images/delete.png','Supprimer la zone',evDeleteZone),
					 ]),
					 $('<div/>').addClass('zone-param-row').append(zoneParamDisplayDimensions(zoneid,['top', 'left'])),
					 $('<div/>').addClass('zone-param-row').append(zoneParamDisplayDimensions(zoneid,['width', 'height'])),
				 ].concat(zoneParamDisplayCssRules(zoneid));
}

function zoneParamDisplayRow (zoneid) {
	return [
					 $('<td/>').append(zoneParamDisplayZoneName(zoneid)),
					 $('<td/>').append(zoneParamDisplay(zoneid)),
				 ];
}

function createZone (zoneid) {
		var $tr = $('<tr/>').attr('zoneid',zoneid);
		if (zoneid%2==0)
			$tr.addClass('odd');
		return $tr;
}

function addZoneParamForm (event) {
	var $button = $(event.target);
	var $table = $button.parents('table');
	var zn = getZoneCount();
	addZone();
	// zn is the number of the new zone
	var $tbody = getTBody($table);
	var $tr = createZone(zn).append(zoneParamForm(zn))
		.appendTo($tbody);
}

function addAppendZoneButton ($tfoot) {
	$tfoot.empty().append($('<tr/>').addClass('zone-footer').append([$('<td/>'),
			$('<td/>').append(iconButton('images/add.png','Ajouter une zone',addZoneParamForm))
		]));
}

function displayZonesData() {
	var $table = getTable ('zones-info', ['nom','paramètres']);
	var $tbody = getTBody($table);
	var zones = screenData['zones'];
	for (zoneid in zones) {
		createZone(zoneid).append(zoneParamDisplayRow(zoneid))
			.appendTo($tbody);
	}
	addAppendZoneButton(getTFoot($table));
}

function displayData () {
	displayScreenData();
	displayZonesData();
}

$(function () {
	getScreenData(displayData);
});

